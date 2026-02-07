<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDetail extends Model
{
    protected $fillable = [
        'transaction_id',
        'product_id',
        'topup_transaction_id',
        'cash_withdrawal_id',
        'buying_prices',
        'selling_prices',
        'quantity',
        'subtotal',
        'created_by'
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function topupTransaction(): BelongsTo
    {
        return $this->belongsTo(TopupTransaction::class, 'topup_transaction_id');
    }
    
    public function cashWithdrawal(): BelongsTo
    {
        return $this->belongsTo(CashWithdrawal::class, 'cash_withdrawal_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(PosUser::class, 'created_by');
    }
}