<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDetail extends Model
{
    /**
     * Nama tabel didefinisikan secara eksplisit (opsional jika nama sudah sesuai konvensi)
     */
    protected $table = 'transaction_details';

    /**
     * Kolom created_by dimasukkan ke dalam fillable karena kolomnya tersedia di tabel 
     * untuk mencatat user ID dari pos_users yang melakukan input per baris.
     */
    protected $fillable = [
        'transaction_id',
        'product_id',
        'topup_transaction_id',
        'cash_withdrawal_id',
        'buying_prices',
        'selling_prices',
        'quantity',
        'subtotal',
        'created_by'
    ];

    /**
     * Relasi ke header transaksi utama
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Relasi ke tabel produk
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relasi ke transaksi topup jika tipe item adalah topup
     */
    public function topupTransaction(): BelongsTo
    {
        return $this->belongsTo(TopupTransaction::class, 'topup_transaction_id');
    }
    
    /**
     * Relasi ke transaksi tarik tunai jika tipe item adalah cash withdrawal
     */
    public function cashWithdrawal(): BelongsTo
    {
        return $this->belongsTo(CashWithdrawal::class, 'cash_withdrawal_id');
    }

    /**
     * RELASI UTAMA UNTUK VIEW DETAIL:
     * Menghubungkan created_by di tb transaction_details ke id di tb pos_users
     * untuk mendapatkan nama admin yang melakukan input.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}