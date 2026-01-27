<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    /**
     * Nama tabel (opsional jika nama tabel Anda 'payment_methods')
     */
    protected $table = 'payment_methods';

    /**
     * Kolom yang boleh diisi secara massal
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Jika nanti Anda ingin melihat transaksi apa saja 
     * yang menggunakan metode pembayaran ini.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}