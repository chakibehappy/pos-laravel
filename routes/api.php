<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\PosUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;

// Public Login
// Route::post('/login', function (Request $request) {
//     $request->validate([
//         'email' => 'required|email',
//         'password' => 'required',
//         'device_name' => 'required', 
//     ]);

//     $user = User::where('email', $request->email)->first();

//     if (! $user || ! Hash::check($request->password, $user->password)) {
//         return response()->json(['message' => 'Invalid credentials'], 401);
//     }

//     // Generate the Sanctum Token
//     $token = $user->createToken($request->device_name)->plainTextToken;

//     return response()->json([
//         'user' => $user,
//         'token' => $token,
//         'token_type' => 'Bearer'
//     ]);
// });

Route::post('/login', function (Request $request) {
    $request->validate([
        'name' => 'required',        // Or use email if you add it to the table
        'pin' => 'required',         // Using PIN for POS access
        'device_name' => 'required', 
    ]);

    // Find the POS User by name (or add an 'email' column to pos_users if preferred)
    $user = PosUser::where('name', $request->name)
                //    ->where('is_active', true)
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

Route::middleware('auth:api')->get('/test', function (Request $request) {
    return response()->json([
        'user' => $request->user(),
        'message' => 'API token is working!'
    ]);
})->name('api.test'); 



// Protected Routes (Requires Token)
Route::middleware('auth:api')->group(function () {
    Route::get('/products', function () {
        return Product::all()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'stock' => (int) $product->stock,
                // Automatically generates: https://kitxel.com/pos/storage/products/item.jpg
                // 'image_url' => $product->image_path ? asset('storage/' . $product->image_path) : null,
            ];
        });
    });

});

Route::middleware('auth:api')->post('/transactions', function (Request $request) {

    $request->validate([
        'transaction_at' => 'required|date',
        'subtotal' => 'required|numeric',
        'tax' => 'required|numeric',
        'total' => 'required|numeric',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'nullable|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.price' => 'required|numeric',
    ]);

    /** @var \App\Models\PosUser $posUser */
    $posUser = $request->user();

    DB::beginTransaction();

    try {
        // 1️⃣ Create Transaction Header
        $transaction = Transaction::create([
            'store_id'       => $posUser->store_id,
            'pos_user_id'    => $posUser->id,
            'transaction_at' => $request->transaction_at,
            'subtotal'       => $request->subtotal,
            'tax'            => $request->tax,
            'total'          => $request->total,
        ]);

        // 2️⃣ Create Transaction Items
        foreach ($request->items as $item) {

            $lineSubtotal = $item['quantity'] * $item['price'];

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_id'     => $item['product_id'] ?? null,
                'quantity'       => $item['quantity'],
                'price'          => $item['price'],
                'subtotal'       => $lineSubtotal,
            ]);

            // 3️⃣ Reduce stock (if product exists)
            if (!empty($item['product_id'])) {
                Product::where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Transaction created successfully',
            'transaction_id' => $transaction->id,
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Transaction failed',
            'error' => $e->getMessage()
        ], 500);
    }
});