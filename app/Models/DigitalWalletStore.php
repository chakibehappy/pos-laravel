<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DigitalWalletStore extends Model
{
    use HasFactory;

    // Pastikan nama tabel sesuai dengan di database (singular/plural)
    protected $table = 'digital_wallet_store';

    protected $fillable = [
        'store_id',
        'digital_wallet_id',
        'balance'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function wallet()
    {
        return $this->belongsTo(DigitalWallet::class, 'digital_wallet_id');
    }
}