<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\StoreType;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    public $timestamps = true;

    /**
     * Kolom yang dapat diisi secara massal.
     * Ditambahkan 'store_type_id' agar bisa disimpan dan diupdate lewat controller.
     */
    protected $fillable = [
        'product_category_id',
        'store_type_id', // TAMBAHAN: Diizinkan untuk mass assignment
        'name',
        'image',
        'buying_price',
        'sku',
        'selling_price',
        'stock',
        'type_stock',  
        'unit_type_id',
        'created_by',
        'status',      // 0: Aktif, 2: Dihapus
        'deleted_at',  // Kolom catatan waktu penghapusan
    ];

    /**
     * Casting kolom ke tipe data tertentu.
     * Mengubah string deleted_at menjadi objek Carbon (datetime).
     */
    protected $casts = [
        'deleted_at' => 'datetime',
        'buying_price' => 'float',
        'selling_price' => 'float',
        'stock' => 'integer',
        'type_stock' => 'integer', 
    ];

    /**
     * Relasi ke User yang membuat produk (Admin/Operator).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
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
     * Relasi ke Model Store (Tipe Toko).
     * PENYESUAIAN: Mengubah foreign key dari 'store_id' menjadi 'store_type_id'
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(StoreType::class, 'store_type_id');
    }
}