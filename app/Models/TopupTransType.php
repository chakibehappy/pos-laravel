<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopupTransType extends Model
{
    use HasFactory;

    protected $table = 'topup_trans_type';

    protected $fillable = [
        'name',
        'type',
    ];
    
    // Optional helpers (nice for filtering)
    public function scopeDigital($query)
    {
        return $query->where('type', 'digital');
    }

    public function scopeBill($query)
    {
        return $query->where('type', 'bill');
    }
}