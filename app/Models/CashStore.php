<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashStore extends Model
{
    use HasFactory;

    protected $table = 'cash_store';

    protected $fillable = [
        'store_id',
        'cash'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}