<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\DashboardLoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\PosUserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\StoreTypeController;
use App\Http\Controllers\ProductCategoryController; // TAMBAHKAN INI
use App\Http\Controllers\UnitTypeController;     
use App\Http\Controllers\StoreProductController;  
use App\Http\Controllers\DigitalWalletController;
use App\Http\Controllers\DigitalWalletStoreController;
use App\Http\Controllers\CashStoreController;
use App\Http\Controllers\CashWithdrawalController;
use App\Http\Controllers\TopupTransTypeController;
use App\Http\Controllers\WithdrawalSourceTypeController;
use App\Http\Controllers\TopupTransactionController;
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
    ->middleware('auth:web')->name('logout');

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

    // Product Categories (Kategori)
    Route::get('/product-categories', [ProductCategoryController::class, 'index'])->name('product-categories.index');
    Route::post('/product-categories', [ProductCategoryController::class, 'store'])->name('product-categories.store');
    Route::delete('/product-categories/{id}', [ProductCategoryController::class, 'destroy'])->name('product-categories.destroy');

    // Unit Types (Satuan)
    Route::get('/unit-types', [UnitTypeController::class, 'index'])->name('unit-types.index');
    Route::post('/unit-types', [UnitTypeController::class, 'store'])->name('unit-types.store');
    Route::delete('/unit-types/{id}', [UnitTypeController::class, 'destroy'])->name('unit-types.destroy');

    // Pos Account
    Route::get('/account', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/account', [AccountController::class, 'store'])->name('accounts.store');
    Route::delete('/account/{id}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    
    // Pos PaymentMethods
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
    Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
    
    // Pos Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('/transactions/{id}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    // Store Types
    Route::get('/store-types', [StoreTypeController::class, 'index'])->name('store-types.index');
    Route::post('/store-types', [StoreTypeController::class, 'store'])->name('store-types.store');
    Route::delete('/store-types/{id}', [StoreTypeController::class, 'destroy'])->name('store-types.destroy');

    // --- TAMBAHKAN MULAI DARI SINI ---
    // Manajemen Stok Cabang (Store Products)
    Route::get('/store-products', [StoreProductController::class, 'index'])->name('store-products.index');
    Route::post('/store-products', [StoreProductController::class, 'store'])->name('store-products.store');
    Route::delete('/store-products/{id}', [StoreProductController::class, 'destroy'])->name('store-products.destroy');
    // --- SAMPAI DI SINI ---

    // --- TAMBAHKAN MULAI DARI SINI ---
    // Manajemen Digital Wallet (CRUD Lengkap)
    Route::get('/digital-wallets', [DigitalWalletController::class, 'index'])->name('digital-wallets.index');
    Route::post('/digital-wallets', [DigitalWalletController::class, 'store'])->name('digital-wallets.store'); // Create & Update
    Route::post('/digital-wallets/{id}/update-balance', [DigitalWalletController::class, 'updateBalance'])->name('wallets.update-balance'); // Khusus Saldo
    Route::delete('/digital-wallets/{id}', [DigitalWalletController::class, 'destroy'])->name('digital-wallets.destroy');
    // --- SAMPAI DI SINI ---
    Route::get('/digital-wallet-stores', [DigitalWalletStoreController::class, 'index'])->name('wallet-stores.index');
    Route::post('/digital-wallet-stores', [DigitalWalletStoreController::class, 'store'])->name('wallet-stores.store');
    Route::delete('/digital-wallet-stores/{id}', [DigitalWalletStoreController::class, 'destroy'])->name('wallet-stores.destroy');



    // Rute untuk Manajemen Kas Toko
    Route::get('/cash-stores', [CashStoreController::class, 'index'])->name('cash-stores.index');
    Route::post('/cash-stores', [CashStoreController::class, 'store'])->name('cash-stores.store');
    Route::delete('/cash-stores/{id}', [CashStoreController::class, 'destroy'])->name('cash-stores.destroy');



    // Rute untuk Manajemen Tarik Tunai
    Route::get('/cash-withdrawals', [CashWithdrawalController::class, 'index'])->name('cash-withdrawals.index');
    Route::post('/cash-withdrawals', [CashWithdrawalController::class, 'store'])->name('cash-withdrawals.store');
    Route::patch('/cash-withdrawals/{id}', [CashWithdrawalController::class, 'update'])->name('cash-withdrawals.update');
    Route::delete('/cash-withdrawals/{id}', [CashWithdrawalController::class, 'destroy'])->name('cash-withdrawals.destroy');

    // Rute untuk CRUD Master Tipe Transaksi (topup_trans_type)
    Route::get('/topup-trans-types', [TopupTransTypeController::class, 'index'])->name('topup-trans-types.index');
    Route::post('/topup-trans-types', [TopupTransTypeController::class, 'store'])->name('topup-trans-types.store');
    Route::patch('/topup-trans-types/{id}', [TopupTransTypeController::class, 'update'])->name('topup-trans-types.update');
    Route::delete('/topup-trans-types/{id}', [TopupTransTypeController::class, 'destroy'])->name('topup-trans-types.destroy');

    Route::get('/withdrawal-source-types', [WithdrawalSourceTypeController::class, 'index'])->name('withdrawal-source-types.index');
    Route::post('/withdrawal-source-types', [WithdrawalSourceTypeController::class, 'store'])->name('withdrawal-source-types.store');
    Route::patch('/withdrawal-source-types/{id}', [WithdrawalSourceTypeController::class, 'update'])->name('withdrawal-source-types.update');
    Route::delete('/withdrawal-source-types/{id}', [WithdrawalSourceTypeController::class, 'destroy'])->name('withdrawal-source-types.destroy');



    // Routes untuk Transaksi Topup
    Route::get('/topup-transactions', [TopupTransactionController::class, 'index'])->name('topup-transactions.index');
    Route::post('/topup-transactions', [TopupTransactionController::class, 'store'])->name('topup-transactions.store');
    Route::patch('/topup-transactions/{id}', [TopupTransactionController::class, 'update'])->name('topup-transactions.update');
    Route::delete('/topup-transactions/{id}', [TopupTransactionController::class, 'destroy'])->name('topup-transactions.destroy');
});