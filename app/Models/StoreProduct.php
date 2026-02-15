<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StoreProduct extends Model
{
    use HasFactory;

    protected $table = 'store_products';

    /**
     * Menggunakan guarded kosong agar konsisten dengan sistem sebelumnya.
     * Kolom tambahan: status (0: active, 2: deleted), deleted_at.
     */
    protected $guarded = [];

    /**
     * Casting kolom ke tipe data tertentu.
     */
    protected $casts = [
        'deleted_at' => 'datetime',
        'stock' => 'integer',
    ];

    /**
     * Boot function untuk logika otomatis status saat create.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!isset($model->status)) {
                $model->status = 0;
            }
        });
    }

    public function buyingPrice(): HasOne
    {
        return $this->hasOne(BuyingPrice::class, 'product_id', 'product_id');
    }

    /**
     * Relasi ke Cabang/Store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relasi ke Produk Pusat
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relasi ke User POS yang menginput data ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}