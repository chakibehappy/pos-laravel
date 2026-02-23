<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'payment_methods';

    /**
     * Kolom yang dapat diisi secara massal.
     * Menambahkan status dan deleted_at untuk sistem pengarsipan manual.
     */
    protected $fillable = [
        'name',
        'created_by',
        'status',     // 0 = Aktif, 2 = Terhapus/Arsip
        'deleted_at', // Timestamp penghapusan
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
     * Secara default hanya mengambil data dengan status 0 (Aktif).
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('payment_methods.status', 0);
        });
    }

    /**
     * Relasi ke pos_users untuk menampilkan siapa yang membuat/mengedit.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }

    /**
     * Relasi ke transaksi.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payment_method_id');
    }
}