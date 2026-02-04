<?php

namespace App\Http\Controllers;

use App\Models\DigitalWallet;
use App\Models\DigitalWalletStore;
use App\Models\Store;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DigitalWalletStoreController extends Controller
{
    public function index(Request $request)
    {
        $konterTypeIds = StoreType::where('name', 'LIKE', 'konter')->pluck('id');

        if ($konterTypeIds->isNotEmpty()) {
            $konterStores = Store::whereIn('store_type_id', $konterTypeIds)->get();
            $allWallets = DigitalWallet::all();
            
            $posUser = DB::table('pos_users')
                ->where('username', auth()->user()->email)
                ->first();
                
            $creatorId = $posUser ? $posUser->id : null;

            foreach ($konterStores as $store) {
                foreach ($allWallets as $wallet) {
                    DigitalWalletStore::firstOrCreate([
                        'store_id' => $store->id,
                        'digital_wallet_id' => $wallet->id,
                    ], [
                        'balance' => 0,
                        'created_by' => $creatorId,
                    ]);
                }
            }
        }

        $query = DigitalWalletStore::with(['store', 'wallet'])
            ->whereHas('store', function ($q) use ($konterTypeIds) {
                $q->whereIn('store_type_id', $konterTypeIds);
            })
            ->when($request->search, function ($query, $search) {
                $query->whereHas('store', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('wallet', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });

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
            'resource' => $paginatedItems,
            'stores'   => Store::whereIn('store_type_id', $konterTypeIds)->get(['id', 'name']),
            'wallets'  => DigitalWallet::all(['id', 'name']),
            'filters'  => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id'          => 'required',
            'balance'     => 'required|numeric',
            'action_type' => 'required|in:add,subtract,reset',
        ]);

        // 1. Ambil data lama menggunakan Query Builder agar lebih ringan dan pasti
        $walletStore = DB::table('digital_wallet_store')->where('id', $request->id)->first();
        
        if (!$walletStore) {
            return back()->withErrors(['message' => 'Data tidak ditemukan']);
        }

        // 2. Kalkulasi Saldo
        $currentBalance = (float) $walletStore->balance;
        $inputAmount = (float) $request->balance;
        $finalBalance = $currentBalance;

        if ($request->action_type === 'add') {
            $finalBalance = $currentBalance + $inputAmount;
        } elseif ($request->action_type === 'subtract') {
            $finalBalance = $currentBalance - $inputAmount;
        } elseif ($request->action_type === 'reset') {
            $finalBalance = 0;
        }

        // 3. Ambil ID pembuat
        $posUser = DB::table('pos_users')->where('username', auth()->user()->email)->first();

        // 4. JALUR QUERY BUILDER (Sama seperti cara mentah, pasti masuk database)
        DB::table('digital_wallet_store')
            ->where('id', $request->id)
            ->update([
                'balance'    => max(0, $finalBalance),
                'created_by' => $posUser ? $posUser->id : $walletStore->created_by,
                'updated_at' => now(),
            ]);

        return back()->with('message', 'Saldo berhasil diperbarui!');
    }

    public function destroy($id)
    {
        DB::table('digital_wallet_store')->where('id', $id)->delete();
        return back()->with('message', 'Data berhasil dihapus!');
    }
}