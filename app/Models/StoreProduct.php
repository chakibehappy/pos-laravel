<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreProduct extends Model
{
    use HasFactory;

    protected $table = 'store_products';

    protected $fillable = [
        'store_id',
        'product_id',
        'stock',
        'created_by', // Ditambahkan sesuai struktur tabel di gambar
    ];
    public function buyingPrice(): HasOne
    {
        // Parameter kedua 'product_id' memastikan Laravel mencari kolom tersebut di tb buying_price
        return $this->hasOne(BuyingPrice::class, 'product_id');
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
     * Mengarah ke tabel pos_users melalui kolom created_by.
     */
    public function creator(): BelongsTo
    {
        // Pastikan model PosUser sudah ada, jika nama modelnya berbeda silakan sesuaikan
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}