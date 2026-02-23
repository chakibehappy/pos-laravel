<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class WithdrawalFeeRule extends Model
{
    use HasFactory;

    protected $table = 'withdrawal_fee_rules';

    protected $fillable = [
        'min_limit',
        'max_limit',
        'fee',
        'created_by',
        'status',      // 0 = Active, 2 = Deleted
        'deleted_at'   // Timestamp manual
    ];

    protected $casts = [
        'min_limit'  => 'double',
        'max_limit'  => 'double',
        'fee'        => 'double',
        'created_by' => 'integer',
        'status'     => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot function untuk Global Scope.
     * Konsisten: Hanya mengambil data dengan status bukan 2 (Active).
     * Menggunakan prefix tabel agar aman saat Join.
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('withdrawal_fee_rules.status', '!=', 2);
        });
    }

    /**
     * Relasi ke PosUser (karena database dikunci secara Foreign Key ke pos_users)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}