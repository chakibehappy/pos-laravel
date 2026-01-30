<?php

namespace App\Http\Controllers;

use App\Models\DigitalWallet;
use App\Models\DigitalWalletStore;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DigitalWalletStoreController extends Controller
{
    /**
     * Menampilkan halaman index dengan filter Toko berdasarkan store_type_id = 1.
     */
    public function index()
    {
        return Inertia::render('DigitalWalletStores/Index', [
            'storeBalances' => DigitalWalletStore::with(['store', 'wallet'])->latest()->get(),
            
            // Filter hanya toko yang memiliki store_type_id = 1 (Konter)
            'stores' => Store::where('store_type_id', 1)->get(['id', 'name']),
            
            'wallets' => DigitalWallet::all(['id', 'name', 'balance']),
        ]);
    }

    /**
     * Menyimpan atau mengupdate distribusi saldo dari Gudang ke Cabang.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:digital_wallet_store,id',
            'store_id' => 'required|exists:stores,id',
            'digital_wallet_id' => 'required|exists:digital_wallet,id',
            'balance' => 'required|numeric|min:0'
        ]);

        try {
            DB::transaction(function () use ($request) {
                $walletGudang = DigitalWallet::findOrFail($request->digital_wallet_id);
                
                if ($request->id) {
                    // EDIT MODE: Kembalikan saldo lama ke gudang dulu
                    $currentStoreWallet = DigitalWalletStore::findOrFail($request->id);
                    $walletGudang->increment('balance', $currentStoreWallet->balance);
                }

                // Cek ketersediaan saldo di gudang
                if ($walletGudang->balance < $request->balance) {
                    throw new \Exception("Saldo " . $walletGudang->name . " tidak mencukupi di gudang!");
                }

                // Kurangi saldo gudang
                $walletGudang->decrement('balance', $request->balance);

                // Eksekusi Simpan/Update
                DigitalWalletStore::updateOrCreate(
                    ['id' => $request->id],
                    [
                        'store_id' => $request->store_id,
                        'digital_wallet_id' => $request->digital_wallet_id,
                        'balance' => $request->balance
                    ]
                );
            });

            return back()->with('message', 'Distribusi saldo berhasil diperbarui!');
        } catch (\Exception $e) {
            return back()->withErrors(['balance' => $e->getMessage()]);
        }
    }

    /**
     * Menghapus alokasi dan mengembalikan saldo cabang ke gudang.
     */
    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $storeWallet = DigitalWalletStore::findOrFail($id);
                $walletGudang = DigitalWallet::findOrFail($storeWallet->digital_wallet_id);
                
                $walletGudang->increment('balance', $storeWallet->balance);
                $storeWallet->delete();
            });

            return back()->with('message', 'Data dihapus dan saldo ditarik ke gudang!');
        } catch (\Exception $e) {
            return back()->withErrors(['balance' => 'Gagal menghapus data.']);
        }
    }
}