<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =========================
        // Transactions (Header)
        // =========================
        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();

                $table->foreignId('store_id')
                    ->constrained('stores')
                    ->onDelete('cascade');

                $table->foreignId('pos_user_id')
                    ->constrained('pos_users')
                    ->onDelete('restrict');

                $table->dateTime('transaction_at');

                $table->decimal('subtotal', 15, 2)->default(0.00);
                $table->decimal('tax', 15, 2)->default(0.00);
                $table->decimal('total', 15, 2)->default(0.00);

                $table->timestamps();

                // Performance index for reports
                $table->index(['store_id', 'transaction_at']);
            });
        }

        // =========================
        // Transaction Details (Items)
        // =========================
        if (!Schema::hasTable('transaction_details')) {
            Schema::create('transaction_details', function (Blueprint $table) {
                $table->id();

                $table->foreignId('transaction_id')
                    ->constrained('transactions')
                    ->onDelete('cascade');

                $table->foreignId('product_id')
                    ->nullable()
                    ->constrained('products')
                    ->nullOnDelete();

                $table->integer('quantity')->default(1);
                $table->decimal('price', 15, 2)->default(0.00);
                $table->decimal('subtotal', 15, 2)->default(0.00);

                $table->timestamps();

                $table->index('transaction_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
        Schema::dropIfExists('transactions');
    }
};
