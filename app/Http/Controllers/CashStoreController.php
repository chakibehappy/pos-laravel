<?php

namespace App\Http\Controllers;

use App\Models\CashStore;
use App\Models\Store;
use App\Models\StoreType; // Pastikan model StoreType di-import
use Illuminate\Http\Request;
use Inertia\Inertia;

class CashStoreController extends Controller
{
    /**
     * Menampilkan dashboard kas dengan data pendukung filter.
     */
    public function index()
    {
        return Inertia::render('CashStores/Index', [
            // Mengambil saldo kas beserta relasi toko
            'cashBalances' => CashStore::with('store')->latest()->get(),
            
            // Mengambil semua toko; store_type_id wajib ada untuk filter di Vue
            'stores' => Store::all(['id', 'name', 'store_type_id']),
            
            // Mengambil semua jenis usaha untuk dropdown filter global
            'storeTypes' => StoreType::all(['id', 'name']),
        ]);
    }

    /**
     * Menyimpan data baru atau memperbarui data kas yang sudah ada.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id'       => 'nullable|exists:cash_store,id',
            'store_id' => 'required|exists:stores,id',
            'cash'     => 'required|numeric|min:0'
        ]);

        CashStore::updateOrCreate(
            ['id' => $request->id],
            [
                'store_id' => $request->store_id,
                'cash'     => $request->cash
            ]
        );

        return back()->with('message', 'Data kas berhasil diperbarui!');
    }

    /**
     * Menghapus pencatatan kas berdasarkan ID.
     */
    public function destroy($id)
    {
        try {
            $cash = CashStore::findOrFail($id);
            $cash->delete();
            
            return back()->with('message', 'Data kas berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data kas.']);
        }
    }
}