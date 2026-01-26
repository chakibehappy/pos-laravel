<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
     protected $fillable = [
        'store_id',
        'pos_user_id',
        'transaction_at',
        'subtotal',
        'tax',
        'total'
    ];

    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
