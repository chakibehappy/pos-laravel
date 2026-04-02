<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    /**
     * Matikan timestamps otomatis karena kita menggunakan kolom 'created_at' secara manual
     * untuk mencatat waktu persis saat aktivitas terjadi tanpa kolom 'updated_at'.
     */
    public $timestamps = false; 

    protected $fillable = [
        'store_id',      // Lokasi toko tempat aktivitas terjadi
        'created_by',    // ID dari Eksekutor (PosUser)
        'reference_id',  // ID dari data yang dimanipulasi (misal: ID Transaksi)
        'reference_type',// Jenis tabel referensi (misal: 'transactions', 'products')
        'action',        // Jenis tindakan (create, update, delete, dll)
        'description',   // Keterangan detail aktivitas
        'created_at',    // Waktu eksekusi
        'payload',       // Data JSON berisi perbandingan data lama dan baru (old vs new)
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'payload'    => 'array', // Casting otomatis dari JSON ke Array PHP
    ];

    /**
     * Relasi ke Toko (Store)
     * Digunakan untuk mengelompokkan log aktivitas berdasarkan cabang toko.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id')->withDefault([
            'name' => 'Global'
        ]);
    }

    /**
     * Relasi ke Eksekutor (POS User)
     * Menggantikan relasi 'user' sebelumnya untuk memperjelas peran operator
     * yang mengeksekusi perintah di sistem POS.
     */
    public function executor(): BelongsTo
    {
        // Tetap mereferensikan kolom 'created_by' ke model PosUser
        return $this->belongsTo(PosUser::class, 'created_by')->withDefault([
            'name' => 'AUTOMATED SYSTEM'
        ]);
    }
}