<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashWithdrawal extends Model
{
    use HasFactory;

    /**
     * Nama tabel sesuai dengan database (Sesuai Gambar).
     */
    protected $table = 'cash_withdrawals';

    /**
     * Kolom yang dapat diisi secara massal (Mass Assignment).
     * Menambahkan 'created_by' sesuai struktur tabel di gambar.
     */
    protected $fillable = [
        'store_id',
        'customer_name',
        'withdrawal_source_id',
        'withdrawal_count',
        'admin_fee',
        'created_by', // Menambahkan kolom eksekutor
        'status',
    ];

    /**
     * Casting tipe data agar konsisten saat digunakan di Vue/Frontend.
     */
    protected $casts = [
        'withdrawal_count' => 'float',
        'admin_fee'        => 'float',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'status'           => 'integer',
    ];

    /**
     * Relasi ke Toko (Store)
     * Transaksi ini terjadi di cabang mana.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Relasi ke Sumber Dana (Digital Wallet)
     * Misal: DANA, OVO, GOPAY, dll.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(DigitalWallet::class, 'withdrawal_source_id');
    }

    /**
     * Relasi ke Pembuat Transaksi (Eksekutor)
     * Sesuai kolom 'created_by' yang ada di database.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}