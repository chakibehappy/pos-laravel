<?php

namespace App\Http\Controllers;

use App\Models\DigitalWallet;
use App\Models\DigitalWalletStore;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Pagination\LengthAwarePaginator;

class DigitalWalletStoreController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil data mentah dengan filter search
        $query = DigitalWalletStore::with(['store', 'wallet'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('store', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('wallet', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });

        // 2. Kelompokkan data berdasarkan store_id di level Collection
        $groupedData = $query->get()->groupBy('store_id')->map(function ($items) {
            $first = $items->first();
            return [
                'id' => $first->store_id, 
                'store_name' => $first->store->name ?? 'N/A',
                'total_balance' => $items->sum('balance'),
                'wallets' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'store_id' => $item->store_id,
                        'digital_wallet_id' => $item->digital_wallet_id,
                        'wallet_name' => $item->wallet->name ?? 'N/A',
                        'balance' => $item->balance,
                        'updated_at' => $item->updated_at,
                    ];
                })
            ];
        })->values();

        // 3. Implementasi Manual Pagination (10 data per halaman)
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $currentItems = $groupedData->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $paginatedItems = new LengthAwarePaginator(
            $currentItems, 
            $groupedData->count(), 
            $perPage, 
            $currentPage, 
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return Inertia::render('DigitalWalletStores/Index', [
            'resource' => $paginatedItems, // Sekarang berisi data, links, from, to, total
            'stores'   => Store::where('store_type_id', 1)->get(['id', 'name']),
            'wallets'  => DigitalWallet::all(['id', 'name']),
            'filters'  => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id'                => 'nullable|exists:digital_wallet_store,id',
            'store_id'          => 'required|exists:stores,id',
            'digital_wallet_id' => 'required|exists:digital_wallet,id',
            'balance'           => 'required|numeric|min:0',
            'action_type'       => 'required|in:add,subtract,reset',
        ]);

        $walletStore = DigitalWalletStore::find($request->id);
        $newBalance = $request->balance;

        if ($walletStore && $request->action_type !== 'reset') {
            $newBalance = $request->action_type === 'add' 
                ? $walletStore->balance + $request->balance 
                : $walletStore->balance - $request->balance;
        }

        DigitalWalletStore::updateOrCreate(
            ['id' => $request->id],
            [
                'store_id'          => $request->store_id,
                'digital_wallet_id' => $request->digital_wallet_id,
                'balance'           => max(0, $newBalance),
                'created_by'        => auth()->id(),
            ]
        );

        return back()->with('message', 'Saldo berhasil diperbarui!');
    }

    public function destroy($id)
    {
        DigitalWalletStore::findOrFail($id)->delete();
        return back()->with('message', 'Data berhasil dihapus!');
    }
}