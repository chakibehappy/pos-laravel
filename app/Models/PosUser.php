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

    protected $fillable = [
        'name',
        'username',
        'pin',
        'role',
        'shift',
        'is_active',
        'created_by',
        'status',      // Tambahkan untuk Soft Delete manual
        'deleted_at',
        'edit_cashstore',
        'reset_cashstore',
        'edit_digitalwalletstore',
        'edit_products',
        'delete_products',
        'add_transactions',
        'edit_transactions',
        'delete_transactions',
        'detail_transactions',
        'add_expense',
        'edit_expense',
        'delete_expense',
        'add_topup_rules',
        'edit_topup_rules',
        'delete_topup_rules',
        'add_withdraw_rules',
        'edit_withdraw_rules',
        'delete_withdraw_rules',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
        'edit_cashstore' => 'boolean',
        'reset_cashstore' => 'boolean', // Cast agar menjadi objek Carbon/Date
        'edit_digitalwalletstore' => 'boolean', // Cast agar menjadi objek Carbon/Date
        'edit_products' => 'boolean', // Cast agar menjadi objek Carbon/Date
        'delete_products' => 'boolean',
        'add_transactions' => 'boolean',
        'edit_transactions' => 'boolean',
        'delete_transactions' => 'boolean',
        'detail_transactions' => 'boolean', // Cast agar menjadi objek Carbon/Date
        'add_expense' => 'boolean',
        'edit_expense' => 'boolean',
        'delete_expense' => 'boolean',
        'add_topup_rules' => 'boolean',
        'edit_topup_rules' => 'boolean',
        'delete_topup_rules' => 'boolean',
        'add_withdraw_rules' => 'boolean',
        'edit_withdraw_rules' => 'boolean',
        'delete_withdraw_rules' => 'boolean',
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