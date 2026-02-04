<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class PosUser extends Model
{
    use HasFactory;
    use HasApiTokens;

    protected $table = 'pos_users';

    /**
     * Kolom yang boleh diisi secara massal.
     * Disesuaikan dengan gambar: name, username, pin, role, shift, is_active.
     */
    protected $fillable = [
        'name',
        'username',
        'pin',
        'role',
        'shift',
        'is_active',
        'created_by'
    ];

    /**
     * Casting tipe data agar lebih mudah dikelola di Frontend.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'pos_user_store', 'pos_user_id', 'store_id');
    }
}