<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    public $timestamps = true;

    protected $fillable = [
        'store_id',
        'product_category_id',
        'unit_type_id', // Tambahkan ini agar bisa disimpan
        'name',
        'image',
        'sku',
        'buying_price',
        'selling_price',
        'stock',
    ];

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
     * Relasi ke Model Store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}