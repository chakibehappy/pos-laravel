<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopupTransType extends Model
{
    use HasFactory;

    protected $table = 'topup_trans_type';

    protected $fillable = [
        'name',
        'type',
        'created_by', // WAJIB ditambahkan agar bisa diisi oleh Controller
    ];

    /**
     * Relasi ke tabel pos_users
     * Logika: Mencocokkan created_by dengan id di pos_users
     */
    public function creator()
    {
        // Parameter: NamaModel, foreign_key_di_tabel_ini, owner_key_di_tabel_tujuan
        return $this->belongsTo(PosUser::class, 'created_by', 'id');
    }

    // --- SCOPES (Helper untuk filter) ---

    public function scopeDigital($query)
    {
        return $query->where('type', 'digital');
    }

    public function scopeBill($query)
    {
        return $query->where('type', 'bill');
    }
}