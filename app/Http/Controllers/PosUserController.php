<?php

namespace App\Http\Controllers;

use App\Models\PosUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ActivityLogger; // Import Helper

class PosUserController extends Controller
{
    public function index(Request $request)
    {
        // Tetap menggunakan with('creator') agar relasi terbawa
        $query = PosUser::with('creator')->where('role', '!=', 'developer');

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
            
            // Tambahkan logika jika kolom sort adalah kolom relasi atau alias jika perlu
            $query->orderBy($sortField, $direction);
        } else {
            // Default sorting jika tidak ada request sort
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

        if ($request->id) {
            // --- PROSES UPDATE ---
            $user = PosUser::findOrFail($request->id);
            
            if (empty($data['pin']) || $data['pin'] === '****') {
                unset($data['pin']);
            } else {
                $data['pin'] = Hash::make($data['pin']);
            }

            // Update created_by dengan ID pengedit terakhir sesuai permintaan Anda
            $data['created_by'] = $currentEditorId;

            $user->update($data);

            // LOG ACTIVITY UPDATE
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
            
            // Isi created_by untuk user baru
            $data['created_by'] = $currentEditorId;
            
            $newUser = PosUser::create($data);

            // LOG ACTIVITY CREATE
            ActivityLogger::log(
                'create',
                'pos_users',
                $newUser->id,
                "Membuat user POS baru: {$newUser->name} sebagai {$newUser->role}",
                $currentEditorId
            );
        }

        return back()->with('message', 'Data Berhasil Diperbarui!');
    }

    public function destroy($id)
    {
        try {
            $user = PosUser::findOrFail($id);

            // Identifikasi admin yang menghapus
            $adminEmail = Auth::user()->email;
            $currentUserPos = PosUser::where('username', $adminEmail)->first();
            $currentEditorId = $currentUserPos ? $currentUserPos->id : null;

            // LOG ACTIVITY DELETE (Sebelum hapus)
            ActivityLogger::log(
                'delete',
                'pos_users',
                $id,
                "Menghapus user POS: {$user->name} ",
                $currentEditorId
            );

            $user->delete();
            return back()->with('message', 'User Berhasil Dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Gagal menghapus user.']);
        }
    }
}