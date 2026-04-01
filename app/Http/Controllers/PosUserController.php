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
        // Filter status != 2 agar data yang di-delete manual tidak muncul
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

        $validatedData = $request->validate($rules);

        // Identifikasi Admin/Editor
        $adminEmail = Auth::user()->email;
        $currentUserPos = PosUser::where('username', $adminEmail)->first();
        $currentEditorId = $currentUserPos ? $currentUserPos->id : null;

        return DB::transaction(function () use ($request, $validatedData, $currentEditorId) {
            if ($request->id) {
                // --- PROSES UPDATE ---
                $user = PosUser::findOrFail($request->id);
                $oldData = $user->getRawOriginal(); // TANGKAP DATA LAMA

                $updateData = $validatedData;
                
                // Cek Perubahan PIN
                if (empty($updateData['pin']) || $updateData['pin'] === '****') {
                    unset($updateData['pin']);
                } else {
                    $updateData['pin'] = Hash::make($updateData['pin']);
                }

                $updateData['created_by'] = $currentEditorId;
                $updateData['status'] = 0; 

                $user->update($updateData);

                // Hilangkan PIN dari log agar tidak menampilkan hash yang panjang/sensitif
                $cleanOld = collect($oldData)->except(['pin'])->toArray();
                $cleanNew = collect($user->getAttributes())->except(['pin'])->toArray();
                $cleanOld['pin'] = '[PROTECTED]';
                $cleanNew['pin'] = isset($updateData['pin']) ? '[CHANGED]' : '[UNCHANGED]';

                ActivityLogger::log(
                    'update',
                    'pos_users',
                    $user->id,
                    "Memperbarui data user POS: {$user->name} (Username: {$user->username})",
                    $currentEditorId,
                    ['old' => $cleanOld, 'new' => $cleanNew],
                    null
                );
            } else {
                // --- PROSES CREATE ---
                $createData = $validatedData;
                $createData['pin']        = Hash::make($createData['pin']);
                $createData['is_active']  = 1;
                $createData['status']     = 0; 
                $createData['created_by'] = $currentEditorId;
                
                $newUser = PosUser::create($createData);

                // Payload untuk Create
                $cleanNew = collect($newUser->getAttributes())->except(['pin'])->toArray();
                $cleanNew['pin'] = '[PROTECTED]';

                ActivityLogger::log(
                    'create',
                    'pos_users',
                    $newUser->id,
                    "Membuat user POS baru: {$newUser->name} sebagai {$newUser->role}",
                    $currentEditorId,
                    ['old' => null, 'new' => $cleanNew],
                    null
                );
            }

            return back()->with('message', 'Data Berhasil Disimpan!');
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $user = PosUser::findOrFail($id);
            $oldData = $user->getRawOriginal();

            // Identifikasi admin yang menghapus
            $adminEmail = Auth::user()->email;
            $currentUserPos = PosUser::where('username', $adminEmail)->first();
            $currentEditorId = $currentUserPos ? $currentUserPos->id : null;

            // Soft Delete Manual
            $user->status = 2;
            $user->deleted_at = now();
            $user->save();

            // Bersihkan data PIN untuk log
            $cleanOld = collect($oldData)->except(['pin'])->toArray();
            $cleanOld['pin'] = '[PROTECTED]';

            // LOG ACTIVITY DELETE
            ActivityLogger::log(
                'delete',
                'pos_users',
                $id,
                "Menghapus user POS: {$user->name}",
                $currentEditorId,
                ['old' => $cleanOld, 'new' => $user->getAttributes()],
                null
            );

            return back()->with('message', 'User Berhasil Dihapus.');
        });
    }
}