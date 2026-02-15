<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalWallet extends Model
{
    use HasFactory;

    protected $table = 'digital_wallet';

    /**
     * Kolom yang dapat diisi secara massal.
     * Menambahkan status dan deleted_at untuk sistem pengarsipan.
     */
    protected $fillable = [
        'name',
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
     * Secara default hanya mengambil wallet yang aktif (status 0).
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('status', 0);
        });
    }

    /**
     * Relasi ke model DigitalWalletStore (Penugasan ke Toko).
     */
    public function storeAssignments(): HasMany
    {
        return $this->hasMany(DigitalWalletStore::class, 'digital_wallet_id');
    }

    /**
     * Relasi ke PosUser untuk mengetahui pembuat data.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}