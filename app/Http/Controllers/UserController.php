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
    /**
     * Menampilkan daftar user yang belum dihapus.
     */
    public function index(Request $request) 
    {
        // Filter status != 2  
        $query = User::where('status', '!=', 2);

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
            'columns' => [
                ['key' => 'name', 'label' => 'Nama User', 'sortable' => true],
                ['key' => 'email', 'label' => 'Email / Username', 'sortable' => true],
                ['key' => 'created_at', 'label' => 'Terdaftar Pada', 'sortable' => true],
            ]
        ]);
    }

    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
    }

    /**
     * Simpan atau update data user.
     */
    public function store(Request $request) 
    {
        $messages = [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.unique' => 'Nama ini sudah digunakan, silakan gunakan nama lain.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi untuk user baru.',
            'password.min' => 'Password minimal harus 6 karakter.',
        ];

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $request->id,
            'email' => 'required|email|unique:users,email,' . $request->id,
            'password' => $request->id ? 'nullable|min:6' : 'required|min:6',
        ], $messages);

        return DB::transaction(function () use ($request, $data) {
            $posUserId = $this->getPosUserId();
            $logType = $request->id ? 'update' : 'create';
            $actionLabel = $request->id ? 'Memperbarui' : 'Membuat';

            $oldData = null;
            $oldEmail = null;

            if ($request->id) {
                $userBefore = User::findOrFail($request->id);
                $oldData = $userBefore->getRawOriginal(); // Simpan data lama untuk log
                $oldEmail = $userBefore->email; // Simpan email lama untuk sinkronisasi POS
            }

            // Hash password jika diisi
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            } else {
                unset($data['password']); 
            }

            // Status aktif untuk user baru
            if (!$request->id) {
                $data['status'] = 0;
            }

            // 1. Simpan/Update User Admin
            $user = User::updateOrCreate(
                ['id' => $request->id], 
                $data
            );

            // 2. Sinkronisasi ke PosUser
            $targetUsername = $oldEmail ?? $user->email;

            PosUser::updateOrCreate(
                ['username' => $targetUsername],
                [
                    'name' => $user->name,
                    'username' => $user->email, 
                    'role' => 'admin',
                    'shift' => 'pagi',
                    'is_active' => 1,
                    'status' => 0,
                    'pin' => $request->id ? DB::raw('pin') : Hash::make('1234'),
                    'created_by' => $posUserId
                ]
            );

            // LOG ACTIVITY dengan OLD & NEW
            ActivityLogger::log(
                $logType,
                'users',
                $user->id,
                "$actionLabel akun Admin: {$user->name} ({$user->email}) dan sinkronisasi POS User.",
                $posUserId,
                ['old' => $oldData, 'new' => $user->getAttributes()],
                null
            );

            return back()->with('message', 'User dan Akun POS berhasil disinkronkan');
        });
    }

    /**
     * Hapus Akun (Ubah Status ke 2).
     */
    public function destroy($id) 
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return back()->withErrors(['message' => 'Anda tidak bisa menghapus akun sendiri!']);
        }

        $oldData = $user->getRawOriginal(); // Simpan snapshot data sebelum dihapus

        return DB::transaction(function () use ($user, $id, $oldData) {
            $posUserId = $this->getPosUserId();

            // 1. Update Akun POS terkait
            PosUser::where('username', $user->email)->update([
                'status' => 2,
                'deleted_at' => now()
            ]);

            // 2. Update Status User Admin
            $user->status = 2;
            $user->deleted_at = now();
            $user->save();

            // LOG ACTIVITY dengan OLD & NEW
            ActivityLogger::log(
                'delete',
                'users',
                $id,
                "Menghapus akun Admin & POS User: {$user->name} ({$user->email})",
                $posUserId,
                ['old' => $oldData, 'new' => $user->getAttributes()],
                null
            );

            return back()->with('message', 'User dan Akun POS berhasil dihapus');
        });
    }
}