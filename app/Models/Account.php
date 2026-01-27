<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    // Nama tabel di database
    protected $table = 'accounts';
    
    // Kolom yang boleh diisi (mass assignable)
    protected $fillable = [
        'company_name', 
        'status'
    ];

    /**
     * Jika status adalah boolean, Laravel bisa otomatis mengubahnya 
     * menjadi true/false saat diakses di codingan.
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Relasi: Satu Account bisa memiliki banyak Store.
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'account_id');
    }
}