<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function index(Request $request) 
    {
        $query = Store::join('store_types', 'stores.store_type_id', '=', 'store_types.id')
            // Join ke pos_users untuk mendapatkan nama pembuat berdasarkan ID
            ->leftJoin('pos_users', 'stores.created_by', '=', 'pos_users.id')
            ->select(
                'stores.*', 
                'store_types.name as type_name',
                'pos_users.name as creator_name'
            );

        // Filter berdasarkan Pencarian (Search)
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $searchTerm = "%{$request->search}%";
                $q->where('stores.name', 'like', $searchTerm)
                  ->orWhere('stores.address', 'like', $searchTerm)
                  ->orWhere('store_types.name', 'like', $searchTerm)
                  ->orWhere('pos_users.name', 'like', $searchTerm);
            });
        }

        // Filter berdasarkan Tipe (Combo Box)
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('stores.store_type_id', $request->type);
        }

        return Inertia::render('Stores/Index', [
            'stores' => $query->latest('stores.created_at')
                ->paginate(10)
                ->withQueryString(), 
            
            'store_types' => StoreType::all(['id', 'name']),
            
            'filters' => $request->only(['search', 'type']), 
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'store_type_id' => 'required|exists:store_types,id',
            'address'       => 'nullable|string',
        ]);

        // Jembatan: Mencari ID di pos_users berdasarkan email user yang login (tabel users)
        $posUser = DB::table('pos_users')
            ->where('username', auth()->user()->email)
            ->first(['id']);

        // Proteksi jika jembatan akun tidak ditemukan
        if (!$posUser) {
            return back()->withErrors([
                'message' => 'Gagal menyimpan: Email (' . auth()->user()->email . ') tidak terdaftar sebagai Username di sistem POS.'
            ]);
        }

        $additionalData = [
            'account_id' => 1, 
            'keyname'    => Str::slug($request->name),
        ];

        // Logika saat Insert data baru
        if (!$request->id) {
            $additionalData['password']   = bcrypt('password123'); // Default password
            $additionalData['created_by'] = $posUser->id; // Mengisi kolom created_by dengan ID pos_users
        }

        Store::updateOrCreate(
            ['id' => $request->id], 
            array_merge($data, $additionalData) 
        );

        return back()->with('message', 'Store saved successfully');
    }

    public function destroy($id) {
        Store::findOrFail($id)->delete();
        return back()->with('message', 'Store deleted');
    }
}