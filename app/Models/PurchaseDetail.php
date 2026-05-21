<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
{
    use HasFactory;

    protected $table = 'purchase_details';

    public $timestamps = false; 

    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_name',
        'buying_price',
        'qty',
        'total', // 👈 Ditambahkan di sini
    ];

    /**
     * Relasi kembali ke Purchase
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    /**
     * Relasi ke Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}