<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosUserStore extends Model
{
    use HasFactory;

    protected $table = 'pos_user_store';

    protected $fillable = [
        'pos_user_id',
        'store_id',
        'created_by',
    ];

    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'pos_user_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function creator(): BelongsTo
    {
        // Relasi ke PosUser menggunakan foreign key created_by
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}