<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topup_trans_type', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 50)->default('digital');   
            $table->timestamps();
        });

        Schema::create('digital_wallet', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->decimal('balance', 15, 2)->default(0);  
            $table->timestamps();
        });

        Schema::create('digital_wallet_store', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->foreignId('digital_wallet_id')->constrained('digital_wallet')->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0);  
            $table->timestamps();
        });

        Schema::create('cash_store', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->decimal('cash', 15, 2)->default(0);  
            $table->timestamps();
        });

        Schema::create('topup_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('cust_account_number', 100);
            $table->decimal('nominal_request', 15, 2)->default(0);  
            $table->decimal('nominal_pay', 15, 2)->default(0);  
            $table->foreignId('digital_wallet_store_id')->constrained('digital_wallet_store')->onDelete('cascade');
            $table->foreignId('topup_trans_type_id')->constrained('topup_trans_type')->onDelete('cascade');
            $table->timestamps();
        });

        
        Schema::create('withdrawal_source_type', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('cash_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('customer_name', 100);
            $table->foreignId('withdrawal_source_id')->constrained('withdrawal_source_type')->onDelete('cascade');
            $table->decimal('withdrawal_count', 15, 2)->default(0);  
            $table->decimal('admin_fee', 15, 2)->default(0);  
            $table->timestamps();
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->foreignId('topup_transaction_id')
                ->nullable()
                ->after('transaction_id')
                ->constrained('topup_transactions')
                ->nullOnDelete();

            $table->foreignId('cash_withdrawal_id')
                ->nullable()
                ->after('topup_transaction_id')
                ->constrained('cash_withdrawals')
                ->nullOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('topup_trans_type');
        Schema::dropIfExists('digital_wallet');
        Schema::dropIfExists('digital_wallet_store');
        Schema::dropIfExists('topup_transaction');
        Schema::dropIfExists('withdrawal_source_type');
        Schema::dropIfExists('cash_withdrawals');
    }
};
