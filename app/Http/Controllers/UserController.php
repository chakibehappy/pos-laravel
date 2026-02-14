<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PosUser;
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

        return DB::transaction(function () use ($request, $data) {
            $posUserId = $this->getPosUserId();
            $logType = $request->id ? 'update' : 'create';
            $actionLabel = $request->id ? 'Memperbarui' : 'Membuat';

            // Simpan email lama untuk pencocokan username POS jika email diubah
            $oldEmail = null;
            if ($request->id) {
                $oldEmail = User::where('id', $request->id)->value('email');
            }

            // Hash password jika diisi
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            } else {
                unset($data['password']); 
            }

            // 1. Simpan/Update User Admin
            $user = User::updateOrCreate(
                ['id' => $request->id], 
                $data
            );

            // 2. Sinkronisasi ke PosUser (Email User = Username PosUser)
            $targetUsername = $oldEmail ?? $user->email;

            PosUser::updateOrCreate(
                ['username' => $targetUsername],
                [
                    'name' => $user->name,
                    'username' => $user->email, // Email terbaru
                    'role' => 'admin',
                    'shift' => 'pagi',
                    'is_active' => 1,
                    // PIN default 1234 hanya untuk user baru
                    'pin' => $request->id ? DB::raw('pin') : Hash::make('1234'),
                    'created_by' => $posUserId
                ]
            );

            ActivityLogger::log(
                $logType,
                'users',
                $user->id,
                "$actionLabel akun Admin: {$user->name} ({$user->email}) dan sinkronisasi POS User.",
                $posUserId
            );

            return back()->with('message', 'User dan Akun POS berhasil disinkronkan');
        });
    }

    public function destroy($id) 
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return back()->withErrors(['message' => 'Anda tidak bisa menghapus akun sendiri!']);
        }

        return DB::transaction(function () use ($user, $id) {
            $posUserId = $this->getPosUserId();

            // Hapus juga akun POS yang bersangkutan
            PosUser::where('username', $user->email)->delete();

            ActivityLogger::log(
                'delete',
                'users',
                $id,
                "Menghapus akun Admin & POS User: {$user->name} ({$user->email})",
                $posUserId
            );

            $user->delete();
            return back()->with('message', 'User dan Akun POS berhasil dihapus');
        });
    }
}