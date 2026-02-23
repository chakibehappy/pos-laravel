<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitType extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang didefinisikan secara eksplisit.
     *
     * @var string
     */
    protected $table = 'unit_types';

    /**
     * Kolom yang dapat diisi secara massal (mass assignable).
     * Menambahkan 'status' dan 'deleted_at' untuk sistem soft delete.
     *
     * @var array<int, string>
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
     * Secara default hanya mengambil unit yang aktif (status 0).
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('unit_types.status', 0);
        });
    }

    /**
     * Relasi One-to-Many ke model Product.
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_type_id');
    }

    /**
     * Relasi ke PosUser untuk mengetahui siapa yang membuat data.
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}