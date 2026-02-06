<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;

use \App\Models\Store;
use App\Models\Product;
use App\Models\StoreProduct;
use App\Models\PosUser;
use App\Models\User;
use App\Models\DigitalWalletStore;
use App\Models\DigitalWallet;
use App\Models\TopupTransType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TopupTransaction;
use App\Models\WithdrawalSourceType;
use App\Models\CashStore;
use App\Models\CashWithdrawal;
use App\Models\TopupFeeRule;
use App\Models\WithdrawalFeeRule;

use App\Helpers\PosHelper;
use Illuminate\Support\Facades\DB;

Route::prefix('test-api')->group(function () {
    Route::get('/ping', fn() => response()->json(['message' => 'pong']));
});

Route::post('/store-login', function (Request $request) {
    $request->validate([
        'keyname' => 'required|string',
        'password' => 'required|string',
    ]);

    $store = Store::where('keyname', $request->keyname)->first();

    if (!$store || !Hash::check($request->password, $store->password)) {
        return response()->json(['message' => 'Invalid store credentials'], 401);
    }

    $operators = $store->operators()->where('is_active', 1)
        ->whereNotIn('pos_users.role', ['admin', 'developer'])
        ->select('pos_users.id', 'pos_users.name', 'pos_users.username', 'pos_users.role', 'pos_users.shift')
        ->get();

    return response()->json([
        'store' => $store,
        'operators' => $operators
    ]);
});

// pos-user-login
Route::post('/pos-user-login', function (Request $request) {
    $request->validate([
        'pos_user_id' => 'required|integer',
        'pin' => 'required|string',
        'device_name' => 'required|string',
    ]);

    $user = PosUser::find($request->pos_user_id);

    if (!$user || !Hash::check($request->pin, $user->pin)) {
        return response()->json(['message' => 'Invalid POS user credentials'], 401);
    }

    $token = $user->createToken($request->device_name)->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token,
        'token_type' => 'Bearer'
    ]);
});

// Protected Routes (Requires Token)
Route::middleware('auth:sanctum')->get('/products', function (Request $request) {

    $storeId = $request->user()->store_id;

    $products = Product::join('store_products', 'products.id', '=', 'store_products.product_id')
        ->where('store_products.store_id', $storeId)
        ->select(
            'products.*',
            'store_products.stock as store_stock'
        )
        ->get();
    return response()->json($products);
});

Route::middleware('auth:sanctum')->get('/pos_data', function (Request $request) {
    $storeId = $request->query('store_id'); // get store ID from query param
    if (!$storeId) {
        return response()->json(['error' => 'Store ID is required'], 400);
    }
    return response()->json(PosHelper::getPosData($storeId));
});

Route::middleware('auth:sanctum')->post('/transactions', function (Request $request) {

    $request->validate([
        'store_id' => 'required|numeric',
        'transaction_at' => 'required|date',
        'subtotal' => 'required|numeric',
        'tax' => 'required|numeric',
        'total' => 'required|numeric',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'nullable|integer',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.price' => 'required|numeric',
    ]);

    $posUser = $request->user();

    DB::beginTransaction();

    try {
        // Create Transaction Header
        $transaction = Transaction::create([
            'store_id'       => $request->store_id,
            'pos_user_id'    => $posUser->id,
            'transaction_at' => $request->transaction_at,
            'subtotal'       => $request->subtotal,
            'tax'            => $request->tax,
            'total'          => $request->total,
        ]);

        // Create Transaction Items
        foreach ($request->items as $item) {
            $topupId = null;
            $withdrawalId = null;
            $productId = null;
            if($item['product_id'] > 0){
                $productId = $item['product_id'];
            }

            // Handle Topup Transaction logic
            if (!empty($item['topup_transaction'])) {
                $topupData = $item['topup_transaction'];
                
                $topup = TopupTransaction::create([
                    'store_id'                => $request->store_id,
                    'cust_account_number'     => $topupData['cust_account_number'],
                    'nominal_request'         => $topupData['nominal_request'],
                    'nominal_pay'             => $topupData['nominal_pay'],
                    'digital_wallet_store_id' => $topupData['digital_wallet_store_id'],
                    'topup_trans_type_id'     => $topupData['topup_trans_type_id'],
                    'profit_fee'              => $topupData['profit_fee'],
                    'provider_fee'            => $topupData['provider_fee'],
                ]);
                
                $topupId = $topup->id;

                // Reduce store wallet balance 
                DigitalWalletStore::where('id', $topupData['digital_wallet_store_id'])
                    ->decrement('balance', $topupData['nominal_request']);
                DigitalWalletStore::where('id', $topupData['digital_wallet_store_id'])
                    ->decrement('balance', $topupData['provider_fee']);
                    
                CashStore::where('store_id', $request->store_id)
                    ->increment('cash', $topupData['nominal_pay']);
            }
            
            // --- Handle Withdrawal Logic ---
            if (!empty($item['cash_withdrawal'])) {
                $wdData = $item['cash_withdrawal'];
                $amount = $wdData['withdrawal_count'];

                // Calculate admin fee from backend rules
                $feeRule = WithdrawalFeeRule::where('min_limit', '<=', $amount)
                    ->where(function ($q) use ($amount) {
                        $q->where('max_limit', '>=', $amount)
                        ->orWhere('max_limit', '<', 0); // unlimited
                    })
                    ->orderBy('min_limit', 'desc')
                    ->first();

                $adminFee = $feeRule?->fee ?? 0;
                
                $withdrawal = CashWithdrawal::create([
                    'store_id'             => $request->store_id,
                    'customer_name'        => $wdData['customer_name'],
                    'withdrawal_source_id' => $wdData['withdrawal_source_id'],
                    'withdrawal_count'     => $wdData['withdrawal_count'],
                    // 'admin_fee'            => $wdData['admin_fee'] ?? 0,
                    'admin_fee'            => $adminFee,
                ]);
                $withdrawalId = $withdrawal->id;
                $nominal = $amount - $adminFee;
                // Decrement the physical store cash balance
                CashStore::where('store_id', $request->store_id)
                    ->decrement('cash', $nominal);
            }

            $lineSubtotal = $item['quantity'] * $item['price'];

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_id'     => $productId,
                'topup_transaction_id' => $topupId, 
                'cash_withdrawal_id'   => $withdrawalId,
                'quantity'       => $item['quantity'],
                'price'          => $item['price'],
                'subtotal'       => $lineSubtotal,
            ]);

            // Reduce stock (if product exists)
            if (!empty($productId)) {
                StoreProduct::where('store_id', $request->store_id)
                    ->where('product_id', $productId)
                    ->decrement('stock', $item['quantity']);

                CashStore::where('store_id', $request->store_id)
                    ->increment('cash', $lineSubtotal);
            }
        }

        DB::commit();
        // Fetch FRESH data to sync Unity UI immediately
        $updatedData = PosHelper::getPosData($request->store_id);
        
        return response()->json([
            'message' => 'Transaction created successfully',
            'transaction_id' => $transaction->id,
            'updatedData' => $updatedData,
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Transaction failed',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::middleware('auth:sanctum')->get('/get-transactions', function (Request $request) {

    $request->validate([
        'store_id' => 'required|integer|exists:stores,id',
    ]);

    $storeId = $request->store_id;

    $timezone = 'Asia/Jakarta';

    $startOfDay = Carbon::now($timezone)->startOfDay();
    $endOfDay   = Carbon::now($timezone)->endOfDay();

    $transactions = Transaction::with([
            'posUser',
            'details.product',
            // 'details.topupTransaction',
            'details.topupTransaction.transType',
            'details.topupTransaction.digitalWalletStore.wallet',
            'details.cashWithdrawal'
        ])
        ->where('store_id', $storeId)
        ->whereBetween('transaction_at', [$startOfDay, $endOfDay])
        ->orderBy('transaction_at', 'desc')
        ->get();

    return response()->json([
        'timezone' => $timezone,
        'date' => $startOfDay->toDateString(),
        'store_id' => $storeId,
        'count' => $transactions->count(),
        'transactions' => $transactions,
    ]);
});