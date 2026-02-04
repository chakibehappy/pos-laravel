<?php

namespace App\Http\Controllers;

use App\Models\PosUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PosUserController extends Controller
{
    public function index(Request $request)
    {
        // Tetap menggunakan with('creator') agar relasi terbawa
        $query = PosUser::with('creator')->where('role', '!=', 'developer');

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
        // Cari ID pengguna POS berdasarkan email admin yang sedang login
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
        } else {
            // --- PROSES CREATE ---
            $data['pin']        = Hash::make($data['pin']);
            $data['is_active']  = 1;
            
            // Isi created_by untuk user baru
            $data['created_by'] = $currentEditorId;
            
            PosUser::create($data);
        }

        return back()->with('message', 'Data Berhasil Diperbarui!');
    }

    public function destroy($id)
    {
        PosUser::findOrFail($id)->delete();
        return back()->with('message', 'User Berhasil Dihapus.');
    }
}