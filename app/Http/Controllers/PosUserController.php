<?php

namespace App\Http\Controllers;

use App\Models\PosUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;

class PosUserController extends Controller
{
    public function index(Request $request)
    {
        // Filter agar developer tidak muncul di list
        $query = PosUser::where('role', '!=', 'developer');

        // Fitur Pencarian Berdasarkan Nama atau Username
        if ($request->filled('search')) {
            $term = "%{$request->search}%";
            $query->where(function($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('username', 'like', $term);
            });
        }

        return Inertia::render('PosUser/Index', [
            'resource' => $query->latest()->paginate(10)->withQueryString(),
            'filters'  => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validasi Data
        $rules = [
            'name'     => 'required|string|max:150',
            'username' => 'required|string|max:100|unique:pos_users,username,' . $request->id,
            'role'     => 'required|string',
        ];

        // PIN wajib diisi jika User Baru, Opsional jika Edit
        if (!$request->id) {
            $rules['pin'] = 'required|numeric|digits_between:4,6';
        } else {
            $rules['pin'] = 'nullable|numeric|digits_between:4,6';
        }

        $data = $request->validate($rules);

        // 2. Logika Simpan
        if ($request->id) {
            // PROSES UPDATE
            $user = PosUser::findOrFail($request->id);
            
            // Hapus pin dari array jika tidak diisi atau berisi dummy stars agar tidak di-update
            if (empty($data['pin']) || $data['pin'] === '****') {
                unset($data['pin']);
            } else {
                // Gunakan Hash/Bcrypt jika PIN diubah (sesuai gambar DB Anda)
                $data['pin'] = Hash::make($data['pin']);
            }

            $user->update($data);
        } else {
            // PROSES CREATE (USER BARU)
            // Tambahkan default value untuk kolom tambahan di DB Anda
            $data['pin']       = Hash::make($data['pin']);
            $data['shift']     = 'pagi'; // Default sesuai gambar
            $data['is_active'] = 1;      // Default aktif
            
            PosUser::create($data);
        }

        return back()->with('message', 'Data User Berhasil Disimpan!');
    }

    public function destroy($id)
    {
        $user = PosUser::findOrFail($id);
        $user->delete();

        return back()->with('message', 'User Berhasil Dihapus.');
    }
}