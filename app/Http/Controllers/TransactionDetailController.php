<?php

namespace App\Http\Controllers;

use App\Models\TransactionDetail;
use Illuminate\Http\Request;

class TransactionDetailController extends Controller
{
    /**
     * Mengambil detail transaksi untuk ditampilkan di modal.
     * Disesuaikan dengan ketersediaan kolom di database (tanpa updated_by).
     */
    public function show($transaction_id)
    {
        try {
            // Relasi 'editor' dihapus karena kolom 'updated_by' tidak ditemukan di database
            $details = TransactionDetail::with([
                'product:id,name', 
                'creator:id,name', 
                'topupTransaction',
                'cashWithdrawal'
            ])
            ->where('transaction_id', $transaction_id)
            ->get();

            $data = $details->map(function ($item) {
                // Logika untuk mendeteksi jika data pernah diedit
                // Membandingkan created_at dan updated_at
                $isEdited = $item->updated_at && $item->updated_at->ne($item->created_at);

                return [
                    'id' => $item->id,
                    'product_name' => $item->product ? $item->product->name : $this->getAlternativeName($item),
                    'admin_name' => $item->creator ? $item->creator->name : '-',
                    'price' => $item->selling_prices,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                    'created_at' => $item->created_at->format('d M Y H:i'),
                    'updated_at' => $item->updated_at ? $item->updated_at->format('d M Y H:i') : null,
                    'is_edited' => $isEdited, // Flag untuk tampilan di Vue
                ];
            });

            return response()->json($data);

        } catch (\Exception $e) {
            // Memberikan pesan error yang lebih spesifik jika terjadi kegagalan
            return response()->json([
                'message' => 'Gagal memuat detail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logika untuk mendapatkan nama jika product_id null (Topup/Tarik Tunai)
     */
    private function getAlternativeName($item)
    {
        if ($item->topup_transaction_id) {
            return "Topup: " . ($item->topupTransaction->cust_account_number ?? '-');
        }
        if ($item->cash_withdrawal_id) {
            return "Tarik Tunai: " . ($item->cashWithdrawal->customer_name ?? '-');
        }
        return "Item Tidak Diketahui";
    }
}