<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosUserStore extends Model
{
    use HasFactory;

    // Mendefinisikan nama tabel secara eksplisit
    protected $table = 'pos_user_store';

    // Kolom yang boleh diisi secara massal
    protected $fillable = [
        'pos_user_id',
        'store_id',
        'created_by',
    ];

    /**
     * Relasi ke model User (Kasir/Pegawai)
     */
    public function posUser(): BelongsTo
    {
        // Sesuaikan 'PosUser' dengan nama Model User Anda
        return $this->belongsTo(posUser::class, 'pos_user_id');
    }

    /**
     * Relasi ke model Store (Toko/Cabang)
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Relasi ke pembuat data (User)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}