<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DigitalWallet extends Model
{
    use HasFactory;

    // Paksa Eloquent menggunakan nama tabel sesuai screenshot kamu
    protected $table = 'digital_wallet';

    protected $fillable = [
        'name',
        'balance',
    ];

    // Pastikan balance selalu terbaca sebagai angka (float/decimal)
    protected $casts = [
        'balance' => 'float',
    ];

    /**
     * Scope untuk mempermudah pencarian berdasarkan nama jika dibutuhkan
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}