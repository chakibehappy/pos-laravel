<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    
    const STATUS_ACTIVE = 0;
    const STATUS_PENDING_DELETE = 1;
    const STATUS_DELETED = 2;

    protected $fillable = [
        'store_id',
        'pos_user_id',
        'payment_id',
        'transaction_at',
        'subtotal',
        'tax',
        'total',
        // new deletion-related fields
        'status',
        'delete_requested_by',
        'delete_reason',
        'admin_approved_by',
        'deleted_at',
    ];

    protected $casts = [
        'transaction_at' => 'datetime',
        'deleted_at' => 'datetime',
        'status' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePendingDelete($query)
    {
        return $query->where('status', self::STATUS_PENDING_DELETE);
    }

    public function scopeDeleted($query)
    {
        return $query->where('status', self::STATUS_DELETED);
    }
    
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