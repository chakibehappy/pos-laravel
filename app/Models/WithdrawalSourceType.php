<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalSourceType extends Model
{
    use HasFactory;

    protected $table = 'withdrawal_source_type';

    protected $fillable = [
        'name',
    ];

    public function withdrawals()
    {
        return $this->hasMany(CashWithdrawal::class, 'withdrawal_source_id');
    }
}