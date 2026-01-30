<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalSourceType extends Model
{
    use HasFactory;

    /**
     * Nama tabel eksplisit sesuai database di gambar.
     */
    protected $table = 'withdrawal_source_type';

    /**
     * Kolom yang dapat diisi berdasarkan struktur tabel.
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Relasi ke transaksi penarikan (CashWithdrawal).
     * Jika Anda ingin menghubungkan kategori ini ke data transaksi tarik tunai.
     */
    public function withdrawals()
    {
        return $this->hasMany(CashWithdrawal::class, 'withdrawal_source_id');
    }
}