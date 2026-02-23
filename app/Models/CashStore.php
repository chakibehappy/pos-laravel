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
        'cash',
        'created_by',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function creator()
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}