<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\ActivityLogger;
use App\Models\DigitalWalletStore;
use App\Models\StoreProduct;

class TransactionApprovalController extends Controller
{
    /**
     * Menampilkan daftar permintaan penghapusan dengan Sorting & Search dinamis.
     */
    public function index(Request $request)
    {
        $sortField = $request->input('sort', 'created_at'); 
        $sortDirection = $request->input('direction', 'desc'); 
        $search = $request->input('search');

        if ($sortField === 'operator_name') {
            $sortField = 'delete_requested_by';
        }

        if (empty($sortField)) {
            $sortField = 'created_at';
        }

        $requests = Transaction::with(['store', 'posUser', 'details', 'requester'])
            ->where('transactions.status', 1) 
            ->when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('delete_reason', 'like', "%{$search}%")
                      ->orWhereHas('store', function ($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('requester', function ($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Transactions/Approval', [
            'requests' => $requests,
            'filters'  => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    /**
     * Helper: Mapping User Admin ke ID PosUser.
     */
    private function getPosUserId()
    {
        $adminEmail = Auth::user()->email;
        $posUser = DB::table('pos_users')->where('username', $adminEmail)->first();
        return $posUser ? $posUser->id : null;
    }

    /**
     * Memproses Approve atau Reject penghapusan
     */
    public function handleAction(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $transaction = Transaction::with(['requester', 'details'])->findOrFail($id);
            $posUserId = $this->getPosUserId();
            $requesterName = $transaction->requester ? $transaction->requester->name : 'Tidak Diketahui';

            // Simpan data lama untuk audit log
            $oldData = $transaction->getRawOriginal();

            if ($request->action === 'approve') {
                // 1. Rollback Stok, Saldo Wallet, dan Kas
                $this->rollbackAssets($transaction);

                // 2. Rollback Kas Toko (Subtotal Transaksi)
                DB::table('cash_store')
                    ->where('store_id', $transaction->store_id)
                    ->decrement('cash', $transaction->subtotal);

                // 3. Update Status Transaksi ke Terhapus (status 2)
                $transaction->update([
                    'status' => 2,
                    'admin_approved_by' => $posUserId,
                    'deleted_at' => now(),
                ]);

                // LOG ACTIVITY APPROVE
                ActivityLogger::log(
                    'approve', 
                    'transactions',
                    $transaction->id,
                    "Menyetujui penghapusan transaksi ID: #{$transaction->id} yang diajukan oleh: {$requesterName}",
                    $posUserId,
                    ['old' => $oldData, 'new' => $transaction->getAttributes()]
                );

                $message = 'Permintaan penghapusan disetujui. Aset telah di-rollback.';

            } else {
                // Jika REJECT: Kembalikan status ke Aktif (status 0)
                $transaction->update([
                    'status' => 0,
                    'delete_reason' => null,
                    'delete_requested_by' => null,
                ]);

                // LOG ACTIVITY REJECT
                ActivityLogger::log(
                    'reject', 
                    'transactions',
                    $transaction->id,
                    "Menolak penghapusan transaksi ID: #{$transaction->id} yang diajukan oleh: {$requesterName}", 
                    $posUserId,
                    ['old' => $oldData, 'new' => $transaction->getAttributes()]
                );

                $message = 'Permintaan penghapusan ditolak.';
            }

            return redirect()->back()->with('message', $message);
        });
    }

    /**
     * Logika Rollback Stok dan Saldo
     */
    private function rollbackAssets($transaction)
    {
        foreach ($transaction->details as $detail) {
            // Rollback Stok Produk
            if ($detail->product_id) {
                StoreProduct::where('store_id', $transaction->store_id)
                    ->where('product_id', $detail->product_id)
                    ->increment('stock', $detail->quantity);
            }

            // Rollback Saldo Topup
            if ($detail->topup_transaction_id) {
                $topup = DB::table('topup_transactions')->where('id', $detail->topup_transaction_id)->first();
                if ($topup) {
                    DigitalWalletStore::where('id', $topup->digital_wallet_store_id)
                        ->increment('balance', $topup->nominal_request);
                    
                    // Tandai atau hapus record topup
                    DB::table('topup_transactions')->where('id', $topup->id)->delete();
                }
            }

            // Rollback Tarik Tunai
            if ($detail->cash_withdrawal_id) {
                $withdraw = DB::table('cash_withdrawals')->where('id', $detail->cash_withdrawal_id)->first();
                if ($withdraw) {
                    DB::table('cash_store')->where('store_id', $transaction->store_id)
                        ->increment('cash', $withdraw->withdrawal_count);
                    
                    DB::table('cash_withdrawals')->where('id', $withdraw->id)->delete();
                }
            }
        }
    }
}