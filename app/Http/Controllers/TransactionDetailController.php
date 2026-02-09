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
            // Memastikan relasi topupTransaction dan cashWithdrawal ikut dimuat
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
                    'is_edited' => $isEdited,
                    
                    // Data Tambahan untuk kolom Keterangan di Vue (Tanpa Nominal)
                    'type' => $item->topup_transaction_id ? 'topup' : ($item->cash_withdrawal_id ? 'withdrawal' : 'produk'),
                    'target_number' => $item->topupTransaction->cust_account_number ?? null,
                    'customer_name' => $item->cashWithdrawal->customer_name ?? null,
                    'note' => $item->note // Tetap menyertakan note umum jika ada
                ];
            });

            return response()->json($data);

        } catch (\Exception $e) {
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
            return "Topup";
        }
        if ($item->cash_withdrawal_id) {
            return "Tarik Tunai";
        }
        return "Item Tidak Diketahui";
    }
}