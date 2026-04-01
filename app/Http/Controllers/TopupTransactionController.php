<?php

namespace App\Http\Controllers;

use App\Models\TopupTransaction; 
use App\Models\TopupTransType;    
use App\Models\Store;
use App\Models\DigitalWalletStore; 
use App\Models\DigitalWallet; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;

class TopupTransactionController extends Controller
{
    /**
     * Helper untuk mendapatkan ID User (PosUser)
     */
    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
    }

    /**
     * READ: Menampilkan daftar transaksi
     */
    public function index(Request $request)
    {
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        $transactions = TopupTransaction::with(['store', 'transType'])
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('cust_account_number', 'like', "%{$search}%")
                      ->orWhereHas('store', function ($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('transType', function ($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('TopupTransaction/Index', [
            'transactions' => $transactions,
            'stores' => Store::where('store_type_id', 1)->get(['id', 'name']),
            'transTypes' => TopupTransType::all(['id', 'name']),
            'walletStores' => DigitalWalletStore::all(),
            'wallets' => DigitalWallet::all(['id', 'name']), 
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    /**
     * CREATE: Simpan Transaksi + Potong Saldo + Log
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
            $posUserId = $this->getPosUserId();
            $transaction = null;

            DB::transaction(function () use ($request, &$transaction) {
                $walletStore = DigitalWalletStore::findOrFail($request->digital_wallet_store_id);

                if ($walletStore->balance < $request->nominal_request) {
                    throw new \Exception('Saldo tidak mencukupi pada wallet toko ini.');
                }

                // Potong saldo
                $walletStore->decrement('balance', $request->nominal_request);

                // Catat transaksi
                $transaction = TopupTransaction::create($request->all());
            });

            // LOG ACTIVITY (Create)
            ActivityLogger::log(
                'create',
                'topup_transactions',
                $transaction->id,
                "Menambahkan transaksi Topup: {$transaction->cust_account_number} (Nominal: " . number_format($transaction->nominal_request) . ")",
                $posUserId,
                ['new' => $transaction->toArray(), 'old' => null],
                $transaction->store_id
            );

            return back()->with('message', 'Transaksi berhasil dan saldo telah dipotong.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * UPDATE: Edit Transaksi + Penyesuaian Saldo + Log (Audit Trail)
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
            $posUserId = $this->getPosUserId();
            $oldData = null;
            $updatedTransaction = null;

            DB::transaction(function () use ($request, $id, &$oldData, &$updatedTransaction) {
                $transaction = TopupTransaction::findOrFail($id);
                $oldData = $transaction->toArray(); // Simpan Snapshot lama
                
                $walletStore = DigitalWalletStore::findOrFail($request->digital_wallet_store_id);

                // Hitung selisih nominal
                $diff = $request->nominal_request - $transaction->nominal_request;

                if ($diff > 0 && $walletStore->balance < $diff) {
                    throw new \Exception('Saldo tidak cukup untuk penyesuaian kenaikan nominal ini.');
                }

                $walletStore->decrement('balance', $diff);
                $transaction->update($request->all());
                $updatedTransaction = $transaction;
            });

            // LOG ACTIVITY (Update)
            ActivityLogger::log(
                'update',
                'topup_transactions',
                $id,
                "Mengubah transaksi Topup #{$id} - Akun: {$updatedTransaction->cust_account_number}",
                $posUserId,
                ['old' => $oldData, 'new' => $updatedTransaction->toArray()],
                $updatedTransaction->store_id
            );

            return back()->with('message', 'Transaksi berhasil diperbarui dan saldo disesuaikan.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * DELETE: Hapus Transaksi + Refund Saldo + Log
     */
    public function destroy($id)
    {
        try {
            $posUserId = $this->getPosUserId();
            $oldDataSnapshot = null;

            DB::transaction(function () use ($id, &$oldDataSnapshot) {
                $transaction = TopupTransaction::findOrFail($id);
                $oldDataSnapshot = $transaction->toArray(); // Snapshot sebelum hapus
                
                $walletStore = DigitalWalletStore::find($transaction->digital_wallet_store_id);
                if ($walletStore) {
                    $walletStore->increment('balance', $transaction->nominal_request);
                }

                $transaction->delete();
            });

            // LOG ACTIVITY (Delete)
            ActivityLogger::log(
                'delete',
                'topup_transactions',
                $id,
                "MENGHAPUS (Refund) transaksi Topup #{$id} Akun: " . ($oldDataSnapshot['cust_account_number'] ?? '-'),
                $posUserId,
                ['old' => $oldDataSnapshot, 'new' => ['status' => 'DELETED', 'refunded_at' => now()]],
                $oldDataSnapshot['store_id']
            );

            return back()->with('message', 'Riwayat transaksi dihapus dan saldo dikembalikan.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus data.']);
        }
    }
}