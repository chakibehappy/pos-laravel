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
        // Load relasi 'creator' agar namanya muncul di tabel
        $query = PosUserStore::with(['posUser', 'store', 'creator'])
            ->whereHas('posUser', function($q) {
                $q->where('role', '!=', 'developer'); 
            });
        
        if ($request->search) {
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

        // JEMBATAN LOGIC: Cari ID di pos_users yang username-nya sama dengan email login
        $creator = PosUser::where('username', Auth::user()->email)->first();
        
        // Jika tidak ketemu (misal akun admin berbeda), gunakan ID target sebagai fallback
        $creatorId = $creator ? $creator->id : $request->pos_user_id;

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