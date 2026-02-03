<?php

namespace App\Http\Controllers;

use App\Models\PosUserStore;
use App\Models\Store;
use App\Models\PosUser; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PosUserStoreController extends Controller
{
    public function index(Request $request)
    {
        // 1. Inisialisasi Query dengan Eager Loading relasi yang dibutuhkan
        $query = PosUserStore::with(['posUser', 'store', 'creator'])
            ->whereHas('posUser', function($q) {
                // Filter agar developer tidak muncul di daftar penugasan
                $q->where('role', '!=', 'developer'); 
            });
        
        // 2. Sistem Search yang konsisten dengan DataTable.vue
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $term = "%{$request->search}%";
                // Cari berdasarkan Nama User atau Nama Toko
                $q->whereHas('posUser', function($sq) use ($term) {
                    $sq->where('name', 'like', $term);
                })->orWhereHas('store', function($sq) use ($term) {
                    $sq->where('name', 'like', $term);
                });
            });
        }

        return Inertia::render('PosUserStores/Index', [
            // Resource dipaginasi 10 data per halaman
            'resource' => $query->latest()->paginate(10)->withQueryString(),
            
            // Data untuk Dropdown Form
            'posUsers' => PosUser::where('role', '!=', 'developer')->get(['id', 'name']),
            'stores'   => Store::all(['id', 'name']),
            
            // Melempar kembali nilai search ke frontend
            'filters'  => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pos_user_id' => 'required|exists:pos_users,id', 
            'store_id'    => 'required|exists:stores,id',
        ]);

        /** * LOGIKA CREATOR:
         * Mencari user di pos_users yang memiliki username sama dengan email login.
         * Ini karena Auth::user() merujuk ke tabel 'users' (web), 
         * sedangkan created_by merujuk ke tabel 'pos_users'.
         */
        $creator = PosUser::where('username', Auth::user()->email)->first();
        
        // Fallback: jika tidak ditemukan, gunakan pos_user_id yang sedang diedit
        $creatorId = $creator ? $creator->id : $request->pos_user_id;

        // Gunakan updateOrCreate untuk mencegah duplikasi user di toko yang sama
        PosUserStore::updateOrCreate(
            [
                'pos_user_id' => $request->pos_user_id,
                'store_id'    => $request->store_id
            ],
            [
                'created_by'  => $creatorId,
            ]
        );

        return back()->with('message', 'Penugasan user ke toko berhasil diperbarui!');
    }

    public function destroy($id)
    {
        PosUserStore::findOrFail($id)->delete();
        return back()->with('message', 'Akses user ke toko telah dicabut.');
    }
}