<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockFlow extends Model
{
    use HasFactory;

    // Nama tabel sesuai dump: stock_flow
    protected $table = 'stock_flow';

    /**
     * Karena tabel di dump hanya memiliki created_at dan tidak memiliki updated_at,
     * kita set timestamps ke false jika Anda tidak ingin Laravel error saat simpan data,
     * atau definisikan konstanta UPDATED_AT sebagai null.
     */
    public $timestamps = false;

    /**
     * Kolom yang dapat diisi sesuai struktur tabel.
     */
    protected $fillable = [
        'store_product_id',
        'created_by',
        'quantity_change',
        'transaction_type', // enum: 'initial','sale','restock','return','adjustment','audit'
        'reference_id',
        'reference_type',
        'created_at',
    ];

    /**
     * Casting data sesuai dengan tipe data di MySQL.
     */
    protected $casts = [
        'id'               => 'integer',
        'store_product_id' => 'integer',
        'created_by'       => 'integer',
        'quantity_change'  => 'integer', // Di SQL dump bertipe int(11)
        'reference_id'     => 'integer',
        'created_at'       => 'datetime',
    ];

    /**
     * Relasi ke produk (store_products).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(StoreProduct::class, 'store_product_id');
    }

    /**
     * Relasi ke User/Operator yang melakukan perubahan.
     * Mengacu pada tabel users/pos_users melalui kolom created_by.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi Polymorphic untuk melacak asal transaksi.
     * Digunakan jika reference_type berisi namespace Model (contoh: App\Models\Sale).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}