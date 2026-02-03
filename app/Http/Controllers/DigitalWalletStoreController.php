<?php

namespace App\Http\Controllers;

use App\Models\DigitalWallet;
use App\Models\DigitalWalletStore;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DigitalWalletStoreController extends Controller
{
    /**
     * Menampilkan data yang dikelompokkan berdasarkan Toko.
     */
    public function index(Request $request)
    {
        // 1. Ambil data dengan relasi
        $data = DigitalWalletStore::with(['store', 'wallet'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('store', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('wallet', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->get();

        // 2. Transformasi data: Kelompokkan berdasarkan Toko
        $grouped = $data->groupBy('store_id')->map(function ($items) {
            $first = $items->first();
            return [
                'id' => $first->store_id, 
                'store_name' => $first->store->name ?? 'N/A',
                'total_balance' => $items->sum('balance'),
                'wallets' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'wallet_name' => $item->wallet->name ?? 'N/A',
                        'balance' => $item->balance,
                        'updated_at' => $item->updated_at,
                        'raw' => $item // Untuk data form edit
                    ];
                })
            ];
        })->values();

        // 3. Bungkus dalam 'resource' agar DataTable.vue tidak error
        $resource = [
            'data' => $grouped,
            'links' => [] // Manual grouping mematikan pagination standar
        ];

        return Inertia::render('DigitalWalletStores/Index', [
            'resource' => $resource,
            'stores'   => Store::where('store_type_id', 1)->get(['id', 'name']),
            'wallets'  => DigitalWallet::all(['id', 'name']),
            'filters'  => $request->only(['search']),
        ]);
    }

    /**
     * Update atau Create data saldo.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id'                => 'nullable|exists:digital_wallet_store,id',
            'store_id'          => 'required|exists:stores,id',
            'digital_wallet_id' => 'required|exists:digital_wallet,id',
            'balance'           => 'required|numeric|min:0',
        ]);

        DigitalWalletStore::updateOrCreate(
            ['id' => $request->id],
            [
                'store_id'          => $request->store_id,
                'digital_wallet_id' => $request->digital_wallet_id,
                'balance'           => $request->balance,
                'created_by'        => auth()->id(),
            ]
        );

        return back()->with('message', 'Data berhasil diperbarui!');
    }

    /**
     * Hapus data.
     */
    public function destroy($id)
    {
        DigitalWalletStore::findOrFail($id)->delete();
        return back()->with('message', 'Data berhasil dihapus!');
    }
}