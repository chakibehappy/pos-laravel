<?php

namespace App\Http\Controllers;

use App\Models\PosUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\ActivityLogger;

class PosUserController extends Controller
{
    public function index(Request $request)
    {
        // Filter status != 2 agar data yang di-  tidak muncul
        $query = PosUser::with('creator')
            ->where('role', '!=', 'developer')
            ->where('status', '!=', 2);

        // --- LOGIKA SEARCH ---
        if ($request->filled('search')) {
            $term = "%{$request->search}%";
            $query->where(function($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('username', 'like', $term);
            });
        }

        // --- LOGIKA SORTING DINAMIS ---
        if ($request->filled('sort') && $request->filled('direction')) {
            $sortField = $request->sort;
            $direction = $request->direction === 'desc' ? 'desc' : 'asc';
            $query->orderBy($sortField, $direction);
        } else {
            $query->latest();
        }

        return Inertia::render('PosUser/Index', [
            'resource' => $query->paginate(10)->withQueryString(),
            'filters'  => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'name'     => 'required|string|max:150',
            'username' => 'required|string|max:100|unique:pos_users,username,' . $request->id,
            'role'     => 'required|string',
            'shift'    => 'required|string',
        ];

        if (!$request->id) {
            $rules['pin'] = 'required|numeric|digits_between:4,6';
        } else {
            $rules['pin'] = 'nullable|numeric|digits_between:4,6';
        }

        $data = $request->validate($rules);

        // --- LOGIKA IDENTIFIKASI PENGEDIT/PEMBUAT ---
        $adminEmail = Auth::user()->email;
        $currentUserPos = PosUser::where('username', $adminEmail)->first();
        $currentEditorId = $currentUserPos ? $currentUserPos->id : null;

        return DB::transaction(function () use ($request, $data, $currentEditorId) {
            if ($request->id) {
                // --- PROSES UPDATE ---
                $user = PosUser::findOrFail($request->id);
                
                if (empty($data['pin']) || $data['pin'] === '****') {
                    unset($data['pin']);
                } else {
                    $data['pin'] = Hash::make($data['pin']);
                }

                $data['created_by'] = $currentEditorId;
                // Pastikan status kembali 0 jika sebelumnya terhapus tapi diupdate
                $data['status'] = 0; 

                $user->update($data);

                ActivityLogger::log(
                    'update',
                    'pos_users',
                    $user->id,
                    "Memperbarui data user POS: {$user->name} (Username: {$user->username})",
                    $currentEditorId
                );
            } else {
                // --- PROSES CREATE ---
                $data['pin']        = Hash::make($data['pin']);
                $data['is_active']  = 1;
                $data['status']     = 0; 
                $data['created_by'] = $currentEditorId;
                
                $newUser = PosUser::create($data);

                ActivityLogger::log(
                    'create',
                    'pos_users',
                    $newUser->id,
                    "Membuat user POS baru: {$newUser->name} sebagai {$newUser->role}",
                    $currentEditorId
                );
            }

            return back()->with('message', 'Data Berhasil Disimpan!');
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $user = PosUser::findOrFail($id);

            // Identifikasi admin yang menghapus
            $adminEmail = Auth::user()->email;
            $currentUserPos = PosUser::where('username', $adminEmail)->first();
            $currentEditorId = $currentUserPos ? $currentUserPos->id : null;

            //   Manual
            $user->status = 2;
            $user->deleted_at = now();
            $user->save();

            // LOG ACTIVITY DELETE
            ActivityLogger::log(
                'delete',
                'pos_users',
                $id,
                "Menghapus user POS: {$user->name}  ",
                $currentEditorId
            );

            return back()->with('message', 'User Berhasil Dihapus.');
        });
    }
}