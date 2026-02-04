<?php

namespace App\Helpers;

use App\Models\Product;
use App\Models\StoreProduct;
use App\Models\DigitalWalletStore;
use App\Models\TopupTransType;
use App\Models\WithdrawalSourceType;
use App\Models\CashStore;
use App\Models\TopupFeeRule;
use App\Models\WithdrawalFeeRule;

class PosHelper
{
    public static function getPosData($storeId)
    {
        // Products
        $products = Product::join('store_products', 'products.id', '=', 'store_products.product_id')
            ->where('store_products.store_id', $storeId)
            ->select('products.*', 'store_products.stock as store_stock')
            ->get();

        // Store wallets
        $storeWallets = DigitalWalletStore::join('digital_wallet', 'digital_wallet_store.digital_wallet_id', '=', 'digital_wallet.id')
            ->where('digital_wallet_store.store_id', $storeId)
            ->select('digital_wallet_store.id', 'digital_wallet.id as wallet_id', 'digital_wallet.name', 'digital_wallet_store.balance')
            ->get();

        // Topup / Bill transaction types
        $topupTypes = TopupTransType::select('id', 'name', 'type')
            ->orderBy('id', 'desc')->get();

        $withdrawalSrcTypes = WithdrawalSourceType::select('id', 'name')
            ->orderBy('id', 'desc')->get();

        $cashStore = CashStore::where('store_id', $storeId)->first();

        $topupFeeRules = TopupFeeRule::select('id', 'topup_trans_type_id', 'wallet_target_id', 'min_limit', 'max_limit', 'fee', 'admin_fee as adm_fee')
            ->orderBy('topup_trans_type_id')
            ->get();

        $withdrawalFeeRules = WithdrawalFeeRule::select('id', 'min_limit', 'max_limit', 'fee')
            ->orderBy('min_limit')
            ->get();

        return [
            'products' => $products,
            'store_wallets' => $storeWallets,
            'topup_types' => $topupTypes,
            'withdrawal_src_types' => $withdrawalSrcTypes,
            'cash_store' => $cashStore,
            'topup_fee_rules' => $topupFeeRules,
            'wd_fee_rules' => $withdrawalFeeRules,
        ];
    }
}
