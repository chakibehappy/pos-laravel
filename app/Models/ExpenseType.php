<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseType extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     */
    protected $table = 'expense_types';

    /**
     * Kolom yang dapat diisi secara massal (Mass Assignable).
     * Disesuaikan dengan kolom audit di gambar database.
     */
    protected $fillable = [
        'name',
        'created_by',
        'status',     // 0 = Aktif, 1 = Pending Delete, 2 = Dihapus
        'deleted_at', // Timestamp penghapusan manual
    ];

    /**
     * Casting kolom ke tipe data tertentu.
     */
    protected $casts = [
        'deleted_at' => 'datetime',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot function untuk Global Scope.
     * Secara default hanya menarik data yang aktif (status 0).
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('expense_types.status', 0);
        });
    }

    /**
     * Relasi ke PosUser untuk menampilkan siapa yang membuat kategori ini.
     * Menggunakan ID 66 seperti yang ada pada contoh gambar Anda.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }

    /**
     * Relasi ke transaksi pengeluaran (ExpenseTransaction).
     * Satu tipe (misal: General) bisa memiliki banyak transaksi.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(ExpenseTransaction::class, 'expense_type_id');
    }
}