<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitType extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang didefinisikan secara eksplisit.
     * Secara default Laravel akan mencari tabel bernama 'unit_categories'.
     *
     * @var string
     */
    protected $table = 'unit_types';

    /**
     * Kolom yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Relasi One-to-Many ke model unit.
     * Satu kategori memiliki banyak produk.
     * * @return HasMany
     */
    public function units(): HasMany
    {
        return $this->hasMany(unit::class, 'unit_type_id');
    }
}