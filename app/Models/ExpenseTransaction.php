<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseTransaction extends Model
{
    use HasFactory;

    protected $table = 'expense_transactions';

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'store_id',
        'pos_user_id',
        'amount',
        'description',
        'image',
        'transaction_at',
        'status',
        'created_by',
        'deleted_at'
    ];

    /**
     * Casting tipe data agar lebih mudah digunakan di sistem.
     */
    protected $casts = [
        'transaction_at' => 'datetime',
        'amount' => 'float',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Toko
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Relasi ke Karyawan yang menggunakan uang (PIC/Staf)
     */
    public function posUser()
    {
        return $this->belongsTo(PosUser::class, 'pos_user_id');
    }

    /**
     * Relasi ke User yang menginput data ini ke sistem
     * Digunakan untuk kolom 'Input Oleh' di view
     */
    public function creator()
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }

    /**
     * Scope untuk mempermudah pengambilan data yang aktif saja (status 0)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope untuk mengecualikan data yang sudah dihapus secara manual (status 2)
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('status', '!=', 2);
    }
}