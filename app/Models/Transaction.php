<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'store_id',
        'pos_user_id',
        'payment_id', // 1. Tambahkan ini agar metode bayar tersimpan
        'transaction_at',
        'subtotal',
        'tax',
        'total'
    ];

    /**
     * Relasi ke Metode Pembayaran (Master Data)
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_id');
    }

    /**
     * Relasi ke Detail Transaksi (Item yang dibeli)
     */
    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    /**
     * Relasi ke Toko
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relasi ke Staff/Kasir yang melayani
     */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'pos_user_id');
    }
}