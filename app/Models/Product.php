<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    // Matches your SQL schema (it has created_at/updated_at)
    public $timestamps = true;

    protected $fillable = [
        'store_id',
        'name',
        'sku',
        'price',
        'stock',
    ];
}