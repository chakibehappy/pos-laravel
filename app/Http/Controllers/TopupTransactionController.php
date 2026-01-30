<?php

namespace App\Http\Controllers;

use App\Models\TopupTransaction; 
use App\Models\TopupTransType;   
use App\Models\Store;
use App\Models\DigitalWalletStore; 
use App\Models\DigitalWallet; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TopupTransactionController extends Controller
{
    /**
     * READ: Menampilkan daftar transaksi
     */
    public function index()
    {
        return Inertia::render('TopupTransaction/Index', [
            'transactions' => TopupTransaction::with(['store', 'transType'])->latest()->get(),
            'stores' => Store::where('store_type_id', 1)->get(),
            'transTypes' => TopupTransType::all(),
            'walletStores' => DigitalWalletStore::all(),
            'wallets' => DigitalWallet::all(['id', 'name']), 
        ]);
    }

    /**
     * CREATE: Simpan Transaksi + Potong Saldo Otomatis
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'cust_account_number' => 'required|string|max:50',
            'nominal_request' => 'required|numeric|min:0',
            'nominal_pay' => 'required|numeric|min:0',
            'digital_wallet_store_id' => 'required|exists:digital_wallet_store,id',
            'topup_trans_type_id' => 'required|exists:topup_trans_type,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // 1. Ambil data saldo wallet di toko tersebut
                $walletStore = DigitalWalletStore::findOrFail($request->digital_wallet_store_id);

                // 2. Cek apakah saldo mencukupi
                if ($walletStore->balance < $request->nominal_request) {
                    throw new \Exception('Saldo tidak mencukupi.');
                }

                // 3. Potong saldo
                $walletStore->decrement('balance', $request->nominal_request);

                // 4. Catat transaksi
                TopupTransaction::create($request->all());
            });

            return back()->with('message', 'Transaksi berhasil dan saldo telah dipotong.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * UPDATE: Edit Transaksi + Penyesuaian Saldo Otomatis
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'cust_account_number' => 'required|string|max:50',
            'nominal_request' => 'required|numeric|min:0',
            'nominal_pay' => 'required|numeric|min:0',
            'digital_wallet_store_id' => 'required|exists:digital_wallet_store,id',
            'topup_trans_type_id' => 'required|exists:topup_trans_type,id',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $transaction = TopupTransaction::findOrFail($id);
                $walletStore = DigitalWalletStore::findOrFail($request->digital_wallet_store_id);

                // Hitung selisih nominal (nominal lama vs nominal baru)
                // Jika nominal_request naik, saldo berkurang lagi. Jika turun, saldo bertambah (refund selisih).
                $diff = $request->nominal_request - $transaction->nominal_request;

                if ($walletStore->balance < $diff) {
                    throw new \Exception('Saldo tidak cukup untuk penyesuaian nominal ini.');
                }

                // Update saldo berdasarkan selisih
                $walletStore->decrement('balance', $diff);

                // Update data transaksi
                $transaction->update($request->all());
            });

            return back()->with('message', 'Transaksi berhasil diperbarui dan saldo disesuaikan.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * DELETE: Hapus Transaksi + Refund Saldo Otomatis
     */
    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $transaction = TopupTransaction::findOrFail($id);
                
                // Kembalikan saldo ke toko (Refund)
                $walletStore = DigitalWalletStore::find($transaction->digital_wallet_store_id);
                if ($walletStore) {
                    $walletStore->increment('balance', $transaction->nominal_request);
                }

                $transaction->delete();
            });

            return back()->with('message', 'Riwayat transaksi dihapus dan saldo dikembalikan.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data.']);
        }
    }
}