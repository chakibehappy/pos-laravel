<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

// WAJIB: Import model-model terkait agar relasi tidak null
use App\Models\TopupTransType;
use App\Models\DigitalWallet;
use App\Models\PosUser; 

class TopupFeeRule extends Model
{
    use HasFactory;

    protected $table = 'topup_fee_rules';

    protected $fillable = [
        'topup_trans_type_id',
        'wallet_target_id',
        'min_limit',
        'max_limit',
        'fee',
        'admin_fee',
        'created_by',
        'status',      // 0 = Active, 2 = Deleted/Archived
        'deleted_at'   // Timestamp penghapusan manual
    ];

    protected $casts = [
        'min_limit'  => 'double',
        'max_limit'  => 'double',
        'fee'        => 'double',
        'admin_fee'  => 'double',
        'created_by' => 'integer',
        'status'     => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot function untuk Global Scope.
     * Menggunakan prefix 'topup_fee_rules.' untuk menghindari error 'ambiguous column'.
     * Konsisten dengan StoreProduct: Mengambil data yang statusnya BUKAN 2.
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('topup_fee_rules.status', '!=', 2);
        });
    }

    /**
     * Relasi ke Tipe Transaksi.
     */
    public function topup_trans_type()
    {
        return $this->belongsTo(TopupTransType::class, 'topup_trans_type_id');
    }

    /**
     * Relasi ke Target Wallet (DigitalWallet).
     */
    public function wallet_target()
    {
        return $this->belongsTo(DigitalWallet::class, 'wallet_target_id');
    }

    /**
     * Relasi ke Pembuat Rule (Jembatan ke pos_users).
     */
    public function creator()
    {
        /** * Catatan: Jika primary key di tabel pos_users BUKAN 'id', 
         * tambahkan parameter ketiga, misal: return $this->belongsTo(PosUser::class, 'created_by', 'id_user');
         */
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}