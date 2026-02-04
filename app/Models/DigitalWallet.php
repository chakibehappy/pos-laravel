<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalWallet extends Model
{
    use HasFactory;

    protected $table = 'digital_wallet';


    protected $fillable = [
        'name',
        'created_by'
    ];


    public function storeAssignments(): HasMany
    {
        return $this->hasMany(DigitalWalletStore::class, 'digital_wallet_id');
    }
}