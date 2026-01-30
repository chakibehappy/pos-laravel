<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Product;
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

use Illuminate\Support\Facades\DB;


function getPosData($storeId) {
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

    // Topup Types
    $topupTypes = TopupTransType::select('id', 'name', 'type')
        ->orderBy('id', 'desc')->get();

    $withdrawalSrcTypes = WithdrawalSourceType::select( 'id', 'name')
        ->orderBy('id', 'desc')
        ->get();

    $cashStore = CashStore::where('cash_store.store_id', $storeId)
        ->select('cash_store.*')
        ->get();

    return [
        'products' => $products,
        'store_wallets' => $storeWallets,
        'topup_types' => $topupTypes,
        'withdrawal_src_types' => $withdrawalSrcTypes,
        'cash_store' => $cashStore,
    ];
}

Route::post('/login', function (Request $request) {
    $request->validate([
        'name' => 'required',        // Or use email if you add it to the table
        'pin' => 'required',         // Using PIN for POS access
        'device_name' => 'required', 
    ]);

    $user = PosUser::with('store')  
        ->where('name', $request->name)
        //->where('is_active', true)
        ->first();

    if (! $user || ! Hash::check($request->pin, $user->pin)) {
        return response()->json(['message' => 'Invalid POS credentials'], 401);
    }

    // Generate the Sanctum Token for the PosUser model
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

    $storeId = $request->user()->store_id;

    // Products
    $products = Product::join('store_products', 'products.id', '=', 'store_products.product_id')
        ->where('store_products.store_id', $storeId)
        ->select(
            'products.*',
            'store_products.stock as store_stock'
        )
        ->get();

    // Store wallets
    $storeWallets = DigitalWalletStore::join(
            'digital_wallet',
            'digital_wallet_store.digital_wallet_id',
            '=',
            'digital_wallet.id'
        )
        ->where('digital_wallet_store.store_id', $storeId)
        ->select(
            'digital_wallet_store.id',
            'digital_wallet.id as wallet_id',
            'digital_wallet.name',
            'digital_wallet_store.balance'
        )
        ->get();

    // Topup / Bill transaction types
    $topupTypes = TopupTransType::select( 'id', 'name', 'type' )
        ->orderBy('id', 'desc')
        ->get();
        
    $withdrawalSrcTypes = WithdrawalSourceType::select( 'id', 'name')
        ->orderBy('id', 'desc')
        ->get();

        
    $cashStore = CashStore::where('cash_store.store_id', $storeId)
        ->select('cash_store.*')
        ->get();


    return response()->json([
        'products' => $products,
        'store_wallets' => $storeWallets,
        'topup_types' => $topupTypes,
        'withdrawal_src_types' => $withdrawalSrcTypes,
        'cash_store' => $cashStore,
    ]);
});

Route::middleware('auth:sanctum')->post('/transactions', function (Request $request) {

    $request->validate([
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
            'store_id'       => $posUser->store_id,
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
                    'store_id'                => $posUser->store_id,
                    'cust_account_number'     => $topupData['cust_account_number'],
                    'nominal_request'         => $topupData['nominal_request'],
                    'nominal_pay'             => $topupData['nominal_pay'],
                    'digital_wallet_store_id' => $topupData['digital_wallet_store_id'],
                    'topup_trans_type_id'     => $topupData['topup_trans_type_id'],
                ]);
                
                $topupId = $topup->id;

                // Reduce store wallet balance 
                DigitalWalletStore::where('id', $topupData['digital_wallet_store_id'])
                    ->decrement('balance', $topupData['nominal_request']);
            }
            
            // --- Handle Withdrawal Logic ---
            if (!empty($item['cash_withdrawal'])) {
                $wdData = $item['cash_withdrawal'];
                $withdrawal = CashWithdrawal::create([
                    'store_id'             => $posUser->store_id,
                    'customer_name'        => $wdData['customer_name'],
                    'withdrawal_source_id' => $wdData['withdrawal_source_id'],
                    'withdrawal_count'     => $wdData['withdrawal_count'],
                    'admin_fee'            => $wdData['admin_fee'] ?? 0,
                ]);
                $withdrawalId = $withdrawal->id;

                // Decrement the physical store cash balance
                CashStore::where('store_id', $posUser->store_id)
                    ->decrement('cash', $wdData['withdrawal_count']);
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
                Product::where('id', $productId)->decrement('stock', $item['quantity']);
            }
        }

        DB::commit();
        // Fetch FRESH data to sync Unity UI immediately
        $updatedData = getPosData($posUser->store_id);
        
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