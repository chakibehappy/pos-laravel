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
            // Kolom status tidak ditampilkan agar konsisten dengan DataTable
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

            // Pastikan status aktif jika user baru
            if (!$request->id) {
                $data['status'] = 0;
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
                    'status' => 0, // Reset status jika terupdate
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

    /**
     *   Manual (User & PosUser).
     */
    public function destroy($id) 
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return back()->withErrors(['message' => 'Anda tidak bisa menghapus akun sendiri!']);
        }

        return DB::transaction(function () use ($user, $id) {
            $posUserId = $this->getPosUserId();

            // 1.   Akun POS
            PosUser::where('username', $user->email)->update([
                'status' => 2,
                'deleted_at' => now()
            ]);

            // 2.   User Admin (Gunakan cara ini agar lebih "galak" ke database)
            $user->status = 2;
            $user->deleted_at = now();
            $user->save(); // Menggunakan save() seringkali lebih aman daripada update()

            ActivityLogger::log(
                'delete',
                'users',
                $id,
                "Menghapus akun Admin & POS User: {$user->name} ({$user->email})",
                $posUserId
            );

            return back()->with('message', 'User dan Akun POS berhasil dihapus');
        });
    }
}