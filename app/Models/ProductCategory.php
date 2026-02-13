<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCategory extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang didefinisikan secara eksplisit.
     *
     * @var string
     */
    protected $table = 'product_categories';

    /**
     * Kolom yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'created_by', // Ditambahkan agar bisa menyimpan ID admin/operator
    ];

    /**
     * Relasi One-to-Many ke model Product.
     * Satu kategori memiliki banyak produk.
     * * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_category_id');
    }

    /**
     * Relasi ke PosUser untuk mengetahui siapa yang membuat/mengedit kategori.
     * * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}