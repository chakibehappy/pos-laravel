<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class PosUser extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'pos_users';

    public $timestamps = true;

    protected $fillable = [
        'store_id',
        'name',
        'pin',
        'role',
        'shift', // Kolom baru ditambahkan di sini
        'is_active',
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'shift' => 'string', // Memastikan enum dibaca sebagai string
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'pos_user_store', 'pos_user_id', 'store_id');
    }

    // protected $hidden = [
    //     'pin',
    // ];
}