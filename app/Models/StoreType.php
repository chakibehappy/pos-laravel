<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreType extends Model
{
    use HasFactory;

    /**
     * Kolom yang dapat diisi secara mass-assignment.
     */
    protected $fillable = [
        'name',
        'created_by'
    ];

    /**
     * Mapping: Satu Jenis Usaha memiliki banyak Toko.
     * Digunakan untuk memfilter data dashboard berdasarkan kategori usaha.
     */
    public function stores(): HasMany
    {
        // Menghubungkan id ke store_type_id di model Store
        return $this->hasMany(Store::class, 'store_type_id');
    }

    /**
     * Relasi ke tabel pos_users.
     * Digunakan untuk menampilkan siapa yang membuat jenis usaha ini.
     */
    public function creator(): BelongsTo
    {
        // Menghubungkan created_by ke id di model PosUser
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}