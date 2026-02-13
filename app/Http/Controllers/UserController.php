<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger; // Import Helper

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

    /**
     * Logika Private: Mapping User Admin ke ID PosUser.
     */
    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
    }

    public function store(Request $request) 
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $request->id,
            'email' => 'required|email|unique:users,email,' . $request->id,
            'password' => $request->id ? 'nullable|min:6' : 'required|min:6',
        ], [
            'name.unique' => 'Nama ini sudah digunakan, silakan gunakan nama lain.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.min' => 'Password minimal harus 6 karakter.',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']); 
        }

        $posUserId = $this->getPosUserId();
        $logType = $request->id ? 'update' : 'create';
        $actionLabel = $request->id ? 'Memperbarui' : 'Membuat';

        // updateOrCreate akan mengecek berdasarkan ID
        $user = User::updateOrCreate(
            ['id' => $request->id], 
            $data
        );

        // LOG ACTIVITY (Tetap mencatat eksekutor meski tabel users tidak punya created_by)
        ActivityLogger::log(
            $logType,
            'users',
            $user->id,
            "$actionLabel akun Admin/User: {$user->name} ({$user->email})",
            $posUserId
        );

        return back()->with('message', 'User berhasil disimpan');
    }

    public function destroy($id) 
    {
        $user = User::findOrFail($id);
        
        // Mencegah menghapus diri sendiri
        if ($user->id === auth()->id()) {
            return back()->withErrors(['message' => 'Anda tidak bisa menghapus akun sendiri!']);
        }

        $posUserId = $this->getPosUserId();

        // LOG ACTIVITY (Sebelum hapus)
        ActivityLogger::log(
            'delete',
            'users',
            $id,
            "Menghapus akun Admin/User: {$user->name} ({$user->email})",
            $posUserId
        );

        $user->delete();
        return back();
    }
}