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
        'created_by', // Menampung ID pembuat
        'delete_requested_by',
        'delete_reason',
        'deleted_by',
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
        // Ganti PosUser::class dengan User::class jika Anda menggunakan model default Laravel
        return $this->belongsTo(PosUser::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === 0;
    }
}