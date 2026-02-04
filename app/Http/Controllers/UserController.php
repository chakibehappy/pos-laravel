<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request) 
    {
        // Menambahkan filter pencarian sederhana jika diperlukan di masa depan
        $query = User::latest();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('Users/Index', [
            'users' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request) 
    {
        $data = $request->validate([
            // Nama sekarang wajib unik, kecuali untuk ID user yang sedang di-edit
            'name' => 'required|string|max:255|unique:users,name,' . $request->id,
            'email' => 'required|email|unique:users,email,' . $request->id,
            'password' => $request->id ? 'nullable|min:6' : 'required|min:6',
        ], [
            // Custom pesan error jika ingin menggunakan Bahasa Indonesia
            'name.unique' => 'Nama ini sudah digunakan, silakan gunakan nama lain.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.min' => 'Password minimal harus 6 karakter.',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            // Hapus password dari array data agar tidak menimpa password lama dengan null
            unset($data['password']); 
        }

        // updateOrCreate akan mengecek berdasarkan ID
        User::updateOrCreate(
            ['id' => $request->id], 
            $data
        );

        return back()->with('message', 'User berhasil disimpan');
    }

    public function destroy($id) 
    {
        $user = User::findOrFail($id);
        
        // Mencegah menghapus diri sendiri (opsional)
        if ($user->id === auth()->id()) {
            return back()->withErrors(['message' => 'Anda tidak bisa menghapus akun sendiri!']);
        }

        $user->delete();
        return back();
    }
}