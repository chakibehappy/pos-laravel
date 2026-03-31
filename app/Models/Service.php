<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use SoftDeletes;

    protected $table = 'services';

    protected $fillable = [
        'name',
        'description',
        'price',
        'status',
        'created_by',
        'delete_requested_by',
        'delete_reason',
        'admin_approved_by', // Perubahan dari deleted_by
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'status'     => 'integer',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke User (Admin yang membuat layanan)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }

    /**
     * Relasi ke User (Admin yang menyetujui penghapusan/aksi)
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'admin_approved_by');
    }

    /**
     * Relasi ke User (User yang meminta penghapusan)
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'delete_requested_by');
    }

    public function isActive(): bool
    {
        return $this->status === 0;
    }
}