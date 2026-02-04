<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Store extends Model
{
    /**
     * Kolom yang bisa diisi secara mass-assignment.
     * Berdasarkan gambar database: account_id, name, keyname, password, store_type_id, address, created_by.
     */
    protected $guarded = [];

    /**
     * Boot function untuk menangani logika otomatis sebelum data disimpan.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($store) {
            // Otomatis buat keyname (slug) jika belum diisi agar tidak error SQL
            if (empty($store->keyname)) {
                $store->keyname = Str::slug($store->name);
            }
            
            // Set default password jika database mewajibkan namun tidak diisi di form
            if (empty($store->password)) {
                $store->password = bcrypt('password123'); 
            }
        });
    }

    /**
     * Relasi ke tipe toko (store_types table).
     */
    public function store_type(): BelongsTo
    {
        return $this->belongsTo(StoreType::class, 'store_type_id');
    }

    /**
     * Relasi ke staf atau user POS.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(PosUser::class, 'store_id');
    }

    /**
     * Relasi ke produk yang tersedia di toko ini.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'store_id');
    }

    /**
     * Relasi ke operator (Many to Many).
     */
    public function operators(): BelongsToMany
    {
        return $this->belongsToMany(PosUser::class, 'pos_user_store', 'store_id', 'pos_user_id');
    }
}