<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashWithdrawal extends Model
{
    use HasFactory;

    // Nama tabel sesuai gambar
    protected $table = 'cash_withdrawals';

    protected $fillable = [
        'store_id',
        'customer_name',
        'withdrawal_source_id',
        'withdrawal_count',
        'admin_fee'
    ];

    // Relasi ke Toko yang melakukan transaksi
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // Relasi ke Sumber Saldo (Digital Wallet)
    // Asumsi: withdrawal_source_id merujuk ke tabel digital_wallets
    public function source()
    {
        return $this->belongsTo(DigitalWallet::class, 'withdrawal_source_id');
    }
}