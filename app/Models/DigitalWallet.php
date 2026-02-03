<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalWallet extends Model
{
    use HasFactory;

    // Nama tabel sesuai gambar: digital_wallet
    protected $table = 'digital_wallet';

    // Kolom yang dapat diisi (Fillable)
    protected $fillable = [
        'name',
        'created_by'
    ];

    /**
     * Relasi ke distribusi wallet di toko-toko.
     */
    public function storeAssignments(): HasMany
    {
        return $this->hasMany(DigitalWalletStore::class, 'digital_wallet_id');
    }
}