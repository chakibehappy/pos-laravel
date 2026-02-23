<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
     * Menambahkan 'deleted_at' agar konsisten dengan StoreProduct.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'created_by', // ID admin/operator
        'status',     // 0 = Aktif, 2 = Dihapus
        'deleted_at', // Kolom catatan waktu penghapusan
    ];

    /**
     * Casting kolom ke tipe data tertentu.
     * Memastikan deleted_at diperlakukan sebagai objek tanggal.
     */
    protected $casts = [
        'deleted_at' => 'datetime',
        'status' => 'integer',
    ];

    /**
     * Boot function untuk Global Scope.
     * Secara default hanya mengambil kategori dengan status 0 (Aktif).
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('product_categories.status', 0);
        });
    }

    /**
     * Relasi One-to-Many ke model Product.
     * Satu kategori memiliki banyak produk.
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_category_id');
    }

    /**
     * Relasi ke PosUser untuk mengetahui siapa yang membuat/mengedit kategori.
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        // Disesuaikan menggunakan PosUser agar konsisten dengan relasi store_products
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}