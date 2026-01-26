<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class PosUser extends Authenticatable
{
    use HasApiTokens;

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    
    protected $table = 'pos_users';

    // No timestamps in your table
    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'name',
        'pin',
        'role',
        'is_active',
    ];

    // protected $hidden = [
    //     'pin',
    // ];
}
