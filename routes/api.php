<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/test', function (Request $request) {
    return response()->json([
        'user' => $request->user(),
        'message' => 'API token is working!'
    ]);
})->name('api.test'); 

Route::get('/products', function () {
    $products = Product::all()->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'stock' => (int) $product->stock,
            // Automatically generates: https://kitxel.com/pos/storage/products/item.jpg
            // 'image_url' => $product->image_path ? asset('storage/' . $product->image_path) : null,
        ];
    });

    return response()->json($products);
});