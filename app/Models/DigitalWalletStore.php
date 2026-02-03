<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalWalletStore extends Model
{
    use HasFactory;

    protected $table = 'digital_wallet_store';

    protected $fillable = [
        'store_id',
        'digital_wallet_id',
        'balance',
        'created_by'
    ];

    // Relasi hanya untuk mengambil properti 'name' dari wallet
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(DigitalWallet::class, 'digital_wallet_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}