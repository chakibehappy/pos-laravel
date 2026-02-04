<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PosUser extends Model
{
    use HasFactory;

    protected $table = 'pos_users';

    protected $fillable = [
        'name',
        'username',
        'pin',
        'role',
        'shift',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relasi Jembatan:
     * Mengambil Nama dari tabel pos_users sendiri berdasarkan ID 
     * yang disimpan di kolom created_by.
     */
    public function creator()
    {
        return $this->belongsTo(PosUser::class, 'created_by', 'id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'pos_user_store', 'pos_user_id', 'store_id');
    }
}