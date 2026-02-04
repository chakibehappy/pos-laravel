<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'created_by'
    ];

    protected $casts = [
        'min_limit' => 'double',
        'max_limit' => 'double',
        'fee'       => 'double',
        'admin_fee' => 'double',
        'created_by'=> 'integer',
    ];

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