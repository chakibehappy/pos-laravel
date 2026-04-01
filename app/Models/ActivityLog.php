<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    // Karena kita hanya menggunakan created_at secara manual
    public $timestamps = false; 

    protected $fillable = [
        'created_by',
        'reference_id',
        'reference_type',
        'action',
        'description',
        'created_at',
        'payload', 
    ];

    /**
     * Sesi 5 Preparations: 
     * Memastikan payload dikirim sebagai array ke Vue agar mapping berhasil.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'payload'    => 'array', 
    ];

    /**
     * Relasi ke user yang melakukan aktivitas
     */
    public function user(): BelongsTo
    {
        // Pastikan model PosUser sudah ada di namespace App\Models
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}