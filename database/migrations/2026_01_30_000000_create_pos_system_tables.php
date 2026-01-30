<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->text('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }
        // 1. Users
        Schema::create('users', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name', 150);
            $blueprint->string('email', 150)->unique();
            $blueprint->string('password');
            $blueprint->timestamps();
        });

        // 2. Accounts
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 150);
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });

        // 3. Account Users (Pivot)
        Schema::create('account_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role', 50)->default('owner');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['account_id', 'user_id'], 'uniq_account_user');
        });

        // 4. Reference Tables (Master Data)
        Schema::create('store_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });

        // 5. Stores
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->string('name', 150);
            $table->foreignId('store_type_id')->nullable()->constrained('store_types')->onDelete('set null');
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // 6. POS Users (Staff)
        Schema::create('pos_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('name', 150);
            $table->string('pin');
            $table->string('role', 50)->default('cashier');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 7. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->foreignId('product_category_id')->default(1)->constrained('product_categories');
            $table->string('name', 150);
            $table->string('image')->nullable();
            $table->decimal('buying_price', 15, 2)->default(0.00);
            $table->string('sku', 50)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->foreignId('unit_type_id')->nullable()->default(1)->constrained('unit_types')->onDelete('set null');
            $table->timestamps();
        });

        // 8. Store Products (Inventory Distribution)
        Schema::create('store_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('stock')->default(0);
            $table->timestamps();
        });

        // 9. Transactions
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->default(1)->constrained('payment_methods')->onDelete('set null');
            $table->foreignId('pos_user_id')->constrained('pos_users');
            $table->datetime('transaction_at');
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2)->default(0.00);
            $table->index(['store_id', 'transaction_at']);
            $table->timestamps();
        });

        // 10. Transaction Details
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->decimal('buying_prices', 15, 2)->default(0.00);
            $table->integer('quantity')->default(1);
            $table->decimal('selling_prices', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('store_products');
        Schema::dropIfExists('products');
        Schema::dropIfExists('pos_users');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('unit_types');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('store_types');
        Schema::dropIfExists('account_users');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('users');
    }
};