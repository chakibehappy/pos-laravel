<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class PosUser extends Authenticatable
{
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
