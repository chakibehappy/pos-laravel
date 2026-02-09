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
        $query = PosUserStore::with(['posUser', 'store', 'creator'])
            ->whereHas('posUser', function($q) {
                $q->where('role', '!=', 'developer'); 
            });
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $term = "%{$request->search}%";
                $q->whereHas('posUser', function($sq) use ($term) {
                    $sq->where('name', 'like', $term);
                })->orWhereHas('store', function($sq) use ($term) {
                    $sq->where('name', 'like', $term);
                });
            });
        }

        return Inertia::render('PosUserStores/Index', [
            'resource' => $query->latest()->paginate(10)->withQueryString(),
            'posUsers' => PosUser::where('role', '!=', 'developer')->get(['id', 'name']),
            'stores'   => Store::all(['id', 'name']),
            'filters'  => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pos_user_id' => 'required|exists:pos_users,id', 
            'store_id'    => 'required|exists:stores,id',
        ]);

        // FILTER: Cek apakah user sudah terdaftar di tabel (toko mana saja)
        // $exists = PosUserStore::where('pos_user_id', $request->pos_user_id)->exists();

        // if ($exists) {
        //     return back()->withErrors([
        //         'pos_user_id' => 'USER INI SUDAH TERDAFTAR DI SEBUAH TOKO. TIDAK BOLEH DOUBLE AKSES!'
        //     ]);
        // }

        $creator = PosUser::where('username', Auth::user()->email)->first();
        $creatorId = $creator ? $creator->id : null;

        PosUserStore::create([
            'pos_user_id' => $request->pos_user_id,
            'store_id'    => $request->store_id,
            'created_by'  => $creatorId,
        ]);

        return back()->with('message', 'Penugasan user ke toko berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'pos_user_id' => 'required|exists:pos_users,id',
            'store_id'    => 'required|exists:stores,id',
        ]);

        // FILTER: Cek apakah user sudah dipakai oleh data lain (kecuali data ini sendiri)
        $exists = PosUserStore::where('pos_user_id', $request->pos_user_id)
                              ->where('id', '!=', $id)
                              ->exists();

        if ($exists) {
            return back()->withErrors([
                'pos_user_id' => 'USER INI SUDAH TERDAFTAR DI TOKO LAIN!'
            ]);
        }

        $akses = PosUserStore::findOrFail($id);
        
        $akses->update([
            'pos_user_id' => $request->pos_user_id,
            'store_id'    => $request->store_id,
        ]);

        return back()->with('message', 'Akses user berhasil diperbarui!');
    }

    public function destroy($id)
    {
        PosUserStore::findOrFail($id)->delete();
        return back()->with('message', 'Akses user ke toko telah dicabut.');
    }
}