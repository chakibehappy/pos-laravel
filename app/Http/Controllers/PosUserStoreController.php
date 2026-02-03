<?php

namespace App\Http\Controllers;

use App\Models\PosUserStore;
use App\Models\Store;
use App\Models\PosUser; // TAMBAHKAN INI: Agar class PosUser ditemukan
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PosUserStoreController extends Controller
{
    /**
     * Menampilkan daftar penugasan user ke toko.
     */
    public function index(Request $request)
    {
        // Query utama dengan filter sembunyikan developer
        $query = PosUserStore::with(['posUser', 'store', 'creator'])
            ->whereHas('posUser', function($q) {
                $q->where('role', '!=', 'developer'); 
            });
        
        // Logic Pencarian
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->whereHas('posUser', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%");
                })->orWhereHas('store', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%");
                });
            });
        }

        return Inertia::render('PosUserStores/Index', [
            'resource' => $query
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            // Sekarang PosUser sudah bisa digunakan karena sudah di-import di atas
            'posUsers' => PosUser::where('role', '!=', 'developer')->get(['id', 'name']),
            'stores'   => Store::all(['id', 'name']),
            'filters'  => $request->only(['search']),
            'flash'    => ['message' => session('message')]
        ]);
    }

    /**
     * Menyimpan atau memperbarui penugasan user.
     */
    public function store(Request $request)
    {
        $request->validate([
            // PERBAIKAN: pastikan exists mengecek ke tabel pos_users, bukan users
            'pos_user_id' => 'required|exists:pos_users,id', 
            'store_id'    => 'required|exists:stores,id',
        ]);

        PosUserStore::updateOrCreate(
            [
                'pos_user_id' => $request->pos_user_id,
                'store_id'    => $request->store_id
            ],
            [
                'created_by'  => Auth::id(),
            ]
        );

        return back()->with('message', 'Penugasan user ke toko berhasil diperbarui!');
    }

    /**
     * Menghapus penugasan.
     */
    public function destroy($id)
    {
        $assignment = PosUserStore::findOrFail($id);
        $assignment->delete();

        return back()->with('message', 'Akses user ke toko telah dicabut.');
    }
}