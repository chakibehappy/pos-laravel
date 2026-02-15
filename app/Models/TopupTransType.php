<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopupTransType extends Model
{
    use HasFactory;

    protected $table = 'topup_trans_type';

    /**
     * Kolom yang dapat diisi secara massal.
     * Menambahkan status dan deleted_at untuk sistem pengarsipan.
     */
    protected $fillable = [
        'name',
        'type',
        'created_by',
        'status',     // 0 = Aktif, 2 = Dihapus
        'deleted_at', // Catatan waktu penghapusan
    ];

    /**
     * Casting kolom ke tipe data tertentu.
     */
    protected $casts = [
        'deleted_at' => 'datetime',
        'status' => 'integer',
    ];

    /**
     * Boot function untuk Global Scope.
     * Secara default hanya mengambil tipe transaksi yang aktif (status 0).
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('status', 0);
        });
    }

    /**
     * Relasi ke tabel pos_users.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by', 'id');
    }

    // --- SCOPES (Helper untuk filter) ---

    public function scopeDigital($query)
    {
        return $query->where('type', 'digital');
    }

    public function scopeBill($query)
    {
        return $query->where('type', 'bill');
    }
}