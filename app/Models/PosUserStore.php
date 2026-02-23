<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosUserStore extends Model
{
    use HasFactory;

    protected $table = 'pos_user_store';

    /**
     * Menggunakan guarded kosong agar semua kolom bisa diisi (mass-assignment).
     * Kolom baru: status (0: active, 2: deleted), deleted_at.
     */
    protected $guarded = [];

    /**
     * Casting kolom ke tipe data tertentu.
     */
    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot function untuk logika otomatis.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pivot) {
            // Set default status aktif saat relasi dibuat
            if (!isset($pivot->status)) {
                $pivot->status = 0;
            }
        });
    }

    /**
     * Relasi ke User POS.
     */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'pos_user_id');
    }

    /**
     * Relasi ke Toko.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Relasi ke pembuat record (Admin).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}