<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Accounts
        if (!Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->string('company_name', 150);
                $table->string('status', 30)->default('active');
                $table->timestamps();
            });
        }

        // Users
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('email', 150)->unique();
                $table->string('password');
                $table->timestamps();
            });
        }

        // Account Users
        if (!Schema::hasTable('account_users')) {
            Schema::create('account_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 50)->default('owner');
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['account_id', 'user_id'], 'uniq_account_user');
            });
        }

        // Store Types
        if (!Schema::hasTable('store_types')) {
            Schema::create('store_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Stores
        if (!Schema::hasTable('stores')) {
            Schema::create('stores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
                $table->string('name', 150);
                $table->foreignId('store_type_id')
                      ->nullable()
                      ->constrained('store_types')
                      ->nullOnDelete();
                $table->text('address')->nullable();
                $table->timestamps();
            });
        }

        // POS Users
        if (!Schema::hasTable('pos_users')) {
            Schema::create('pos_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->string('name', 150);
                $table->string('pin', 10);
                $table->string('role', 50)->default('cashier');
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }

        // Products
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->string('name', 150);
                $table->string('sku', 50)->nullable();
                $table->decimal('price', 15, 2)->default(0.00);
                $table->integer('stock')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('pos_users');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('store_types');
        Schema::dropIfExists('account_users');
        Schema::dropIfExists('users');
        Schema::dropIfExists('accounts');
    }
};
