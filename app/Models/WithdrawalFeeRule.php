<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalFeeRule extends Model
{
    use HasFactory;

    protected $table = 'withdrawal_fee_rules';

    protected $fillable = [
        'min_limit',
        'max_limit',
        'fee',
        'created_by',
    ];

    protected $casts = [
        'min_limit' => 'decimal:2',
        'max_limit' => 'decimal:2',
        'fee'       => 'decimal:2',
    ];

    /**
     * Relasi ke PosUser (karena database dikunci secara Foreign Key ke pos_users)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}