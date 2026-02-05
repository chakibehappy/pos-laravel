<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreType extends Model
{
    use HasFactory;

    /**
     * Kolom yang dapat diisi secara mass-assignment.
     * Pastikan 'created_by' sudah ada di database server Anda.
     */
    protected $fillable = [
        'name',
        'created_by'
    ];

    /**
     * Relasi ke tabel pos_users.
     * Digunakan untuk menampilkan siapa yang membuat jenis usaha ini.
     */
    public function creator()
    {
        // Menghubungkan created_by ke id di model PosUser
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}