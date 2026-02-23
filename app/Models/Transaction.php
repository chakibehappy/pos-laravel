<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'store_id',
        'pos_user_id',
        'payment_id',
        'transaction_at',
        'subtotal',
        'tax',
        'total',
        'status',              // 0 = Active, 2 = Deleted/Archived
        'delete_requested_by',
        'delete_reason',
        'admin_approved_by',
        'deleted_at'
    ];

    /**
     * Casting kolom ke tipe data tertentu.
     */
    protected $casts = [
        'deleted_at' => 'datetime',
        'transaction_at' => 'datetime',
        'status' => 'integer',
    ];

    /**
     * Boot function untuk Global Scope.
     * Menggunakan prefix 'transactions.' untuk menghindari error 'ambiguous column' saat Join.
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            // Perbaikan: Menambahkan nama tabel secara spesifik
            $builder->where('transactions.status', 0);
        });
    }

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
     * Relasi untuk mengambil nama pengaju hapus
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
     * Relasi ke Admin yang menyetujui (Tabel users admin)
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }
}