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
        'total',
        'status',
        'delete_requested_by',
        'delete_reason',
        'admin_approved_by',
        'deleted_at'
    ];

    /**
     * Relasi ke Toko
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relasi ke pembuat transaksi (pos_user_id)
     */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'pos_user_id');
    }

    /**
     * Relasi untuk mengambil nama pengaju hapus (ID 66)
     * Kita tambahkan 'delete_requested_by' sebagai foreign key 
     * dan 'id' sebagai owner key agar pencocokan data lebih akurat.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'delete_requested_by', 'id');
    }

    /**
     * Relasi ke Detail Transaksi
     */
    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    /**
     * Relasi ke Metode Pembayaran
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_id');
    }

    /**
     * Relasi ke Admin yang menyetujui
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }
}