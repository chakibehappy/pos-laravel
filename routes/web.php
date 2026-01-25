<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\DashboardLoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\PosUserController;
use App\Http\Controllers\ProductController;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->guard('web')->check()) {
        return redirect('/dashboard');
    }

    return redirect('/login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware('auth:web')->name('dashboard');

Route::get('/login', [DashboardLoginController::class, 'show'])
    ->middleware('guest:web')
    ->name('login');

Route::post('/login', [DashboardLoginController::class, 'login'])
    ->middleware('guest:web');

Route::post('/logout', [DashboardLoginController::class, 'logout'])
    ->middleware('auth:web');

Route::middleware(['auth'])->group(function () {
    // Users (Dashboard Admins)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

    // Store
    Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
    Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
    Route::delete('/stores/{id}', [StoreController::class, 'destroy'])->name('stores.destroy');

    // Pos User
    Route::get('/pos_users', [PosUserController::class, 'index'])->name('pos_users.index');
    Route::post('/pos_users', [PosUserController::class, 'store'])->name('pos_users.store');
    Route::delete('/pos_users/{id}', [PosUserController::class, 'destroy'])->name('pos_users.destroy');

      // Pos Product
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');

});
