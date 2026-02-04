<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopupTransaction extends Model
{
    use HasFactory;

    /**
     * Nama tabel sesuai dengan database Anda.
     */
    protected $table = 'topup_transactions';

    /**
     * Kolom yang dapat diisi (fillable) sesuai urutan di database.
     */
    protected $fillable = [
        'store_id',
        'cust_account_number',
        'nominal_request',
        'nominal_pay',
        'digital_wallet_store_id',
        'topup_trans_type_id',
        'profit_fee',
        'provider_fee'
    ];

    /**
     * Relasi ke tabel Store (Toko).
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Relasi ke tabel Master Jenis Topup.
     */
    public function transType()
    {
        return $this->belongsTo(TopupTransType::class, 'topup_trans_type_id');
    }

    /**
     * Relasi ke tabel Saldo Toko (Digital Wallet Store).
     * Disesuaikan dengan nama class model Anda: DigitalWalletStore.
     */
    public function digitalWalletStore()
    {
        // Menghubungkan ke DigitalWalletStore menggunakan foreign key digital_wallet_store_id
        return $this->belongsTo(DigitalWalletStore::class, 'digital_wallet_store_id');
    }
}