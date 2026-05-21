<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $table = 'purchases';

    protected $fillable = [
        'store_id',
        'created_by',
        'status',
        'total_harga', // 👈 WAJIB TAMBAHKAN INI
    ];

    /**
     * Relasi ke PurchaseDetail (Satu purchase memiliki banyak detail item)
     */
    public function details()
    {
        return $this->hasMany(PurchaseDetail::class, 'purchase_id');
    }
}