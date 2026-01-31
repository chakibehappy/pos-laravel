<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopupFeeRule extends Model
{
    use HasFactory;

    protected $table = 'topup_fee_rules';

    // If you want to allow mass assignment
    protected $fillable = [
        'topup_trans_type_id',
        'min_limit',
        'max_limit',
        'fee',
    ];

    // Relationship to topup type (optional)
    public function transType()
    {
        return $this->belongsTo(TopupTransType::class, 'topup_trans_type_id');
    }
}
