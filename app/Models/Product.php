<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    public $timestamps = true;

    protected $fillable = [
        'product_category_id',
        'name',
        'image',
        'buying_price',
        'sku',
        'selling_price',
        'stock',
        'unit_type_id',
        'created_by', // Sesuai kolom di gambar
    ];

    /**
     * Relasi ke User yang membuat produk.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke Model StoreProduct (Stok di Cabang-cabang).
     */
    public function storeStocks(): HasMany
    {
        return $this->hasMany(StoreProduct::class, 'product_id');
    }

    /**
     * Relasi ke Model ProductCategory.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Relasi ke Model UnitType (Satuan).
     */
    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id');
    }

    /**
     * Relasi ke Model Store (Gudang/Toko Utama).
     * Catatan: Pastikan kolom store_id ada di database jika ingin menggunakan relasi ini.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}