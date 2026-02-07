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
        'payment_id',
        'transaction_at',
        'subtotal',
        'tax',
        'total'
    ];

    /**
     * Relasi ke pembuat transaksi (TAMBAHKAN INI UNTUK MEMPERBAIKI ERROR 500)
     */
    public function creator(): BelongsTo
    {
        // Biasanya creator merujuk ke pos_user_id atau user yang sedang login
        return $this->belongsTo(PosUser::class, 'pos_user_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'pos_user_id');
    }
}