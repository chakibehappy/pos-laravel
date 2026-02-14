<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class UserController extends Controller
{
    public function index(Request $request) 
    {
        $query = User::query();

        // Logika Pencarian
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        /**
         * LOGIKA SORTING DINAMIS
         */
        $sort = $request->input('sort');
        $direction = $request->input('direction', 'desc');

        if ($sort) {
            $allowedSorts = ['name', 'email', 'created_at'];
            $dbColumn = in_array($sort, $allowedSorts) ? $sort : 'created_at';
            $query->orderBy($dbColumn, $direction);
        } else {
            $query->latest();
        }

        return Inertia::render('Users/Index', [
            'users' => $query->paginate(10)->withQueryString(),
            // PENTING: Mengirim sort & direction agar icon panah menyala
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

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

        $user = User::updateOrCreate(
            ['id' => $request->id], 
            $data
        );

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
        
        if ($user->id === auth()->id()) {
            return back()->withErrors(['message' => 'Anda tidak bisa menghapus akun sendiri!']);
        }

        $posUserId = $this->getPosUserId();

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