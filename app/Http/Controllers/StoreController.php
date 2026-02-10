<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function index(Request $request) 
    {
        $query = Store::join('store_types', 'stores.store_type_id', '=', 'store_types.id')
            ->leftJoin('pos_users', 'stores.created_by', '=', 'pos_users.id')
            ->select(
                'stores.*', 
                'stores.password as password_plain',
                'store_types.name as type_name',
                'pos_users.name as creator_name'
            );

        // Filter Pencarian
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $searchTerm = "%{$request->search}%";
                $q->where('stores.name', 'like', $searchTerm)
                  ->orWhere('stores.keyname', 'like', $searchTerm)
                  ->orWhere('stores.address', 'like', $searchTerm)
                  ->orWhere('store_types.name', 'like', $searchTerm)
                  ->orWhere('pos_users.name', 'like', $searchTerm);
            });
        }

        // Filter Tipe Toko
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
        // Validasi
        $rules = [
            'name'          => 'required|string|max:150',
            'store_type_id' => 'required|exists:store_types,id',
            'address'       => 'nullable|string',
            'keyname'       => 'required|string|unique:stores,keyname,' . $request->id,
        ];

        if (!$request->id) {
            $rules['password'] = 'required|string|min:4';
        } else {
            $rules['password'] = 'nullable|string|min:4';
        }

        $request->validate($rules);

        /**
         * LOGIKA KHUSUS KOLOM ACCOUNT_ID
         * Diambil dari ID tabel accounts
         */
        $account = DB::table('accounts')->first(['id']);
        
        if (!$account) {
            return back()->withErrors(['message' => 'Gagal: Tabel Accounts kosong. Mohon isi data account terlebih dahulu.']);
        }

        /**
         * LOGIKA KHUSUS KOLOM CREATED_BY
         * Mencocokkan email login dengan username di pos_users untuk ambil ID
         */
        $posUser = DB::table('pos_users')
            ->where('username', auth()->user()->email)
            ->first(['id']);

        if (!$posUser) {
            return back()->withErrors([
                'message' => 'Gagal: Email (' . auth()->user()->email . ') tidak ditemukan di tabel pos_users.'
            ]);
        }

        // Persiapan data untuk Save/Update
        $updateData = [
            'account_id'    => $account->id, // Hasil dari tb accounts
            'name'          => $request->name,
            'keyname'       => Str::upper($request->keyname),
            'store_type_id' => $request->store_type_id,
            'address'       => $request->address,
        ];

        // Hanya update password jika input diisi (untuk fitur edit)
        if ($request->filled('password')) {
            // $updateData['password'] = $request->password;
            $updateData['password'] = Hash::make($request->password);
        }

        // created_by hanya diisi saat membuat data baru (Insert)
        if (!$request->id) {
            $updateData['created_by'] = $posUser->id; // Hasil jembatan email -> username
        }

        Store::updateOrCreate(
            ['id' => $request->id], 
            $updateData
        );

        return back()->with('message', 'Data toko berhasil diproses.');
    }

    public function destroy($id) {
        Store::findOrFail($id)->delete();
        return back()->with('message', 'Toko telah dihapus.');
    }
}