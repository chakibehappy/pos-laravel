<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // Tambahkan ini
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    public $timestamps = true;

    protected $fillable = [
        'product_category_id',
        'unit_type_id',
        'name',
        'image',
        'sku',
        'buying_price',
        'selling_price',
        'stock',
    ];

    /**
     * Relasi ke Model StoreProduct (Stok di Cabang-cabang).
     * Memungkinkan kita melihat produk ini ada di toko mana saja dan berapa stoknya.
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
     * Relasi ke Model Store (Gudang/Toko Utama pemilik produk).
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}