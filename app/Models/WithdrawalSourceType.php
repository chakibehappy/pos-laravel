<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalSourceType extends Model
{
    use HasFactory;

    protected $table = 'withdrawal_source_type';

    protected $fillable = [
        'name',
        'created_by', // Menambahkan kolom created_by agar bisa diisi
    ];

    /**
     * Relasi ke pos_users untuk menampilkan siapa yang membuat/mengedit.
     * Menggunakan foreign key 'created_by' yang merujuk ke ID di tabel pos_users.
     */
    public function creator()
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }

    /**
     * Relasi ke transaksi penarikan (CashWithdrawal).
     */
    public function withdrawals()
    {
        return $this->hasMany(CashWithdrawal::class, 'withdrawal_source_id');
    }
}