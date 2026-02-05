<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'payment_methods';

    protected $fillable = [
        'name',
        'created_by', // Menambahkan kolom created_by agar bisa diisi (mass-assignment)
    ];

    /**
     * Relasi ke pos_users untuk menampilkan siapa yang membuat/mengedit.
     * Mengaitkan created_by ke ID di tabel pos_users.
     */
    public function creator()
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }

    /**
     * Relasi ke transaksi.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}