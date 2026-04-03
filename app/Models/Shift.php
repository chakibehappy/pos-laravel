<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    protected $fillable = [
        'store_id',
        'pos_user_id',
        'start_at',
        'end_at',
        'start_cash',
        'end_cash',
        'exp_start_cash',
        'exp_end_cash',
        'collector_pickup',
        'collector_name',
        'notes',
        'status'
    ];

    // Casts for date and decimal handling
    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'start_cash' => 'decimal:2',
        'end_cash' => 'decimal:2',
        'status' => 'integer',
    ];

    /**
     * Get the store that owns the shift.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Get the POS user that owns the shift.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'pos_user_id');
    }
}