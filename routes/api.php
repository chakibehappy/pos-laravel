<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Product;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

// Public Login
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_name' => 'required', 
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // Generate the Sanctum Token
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