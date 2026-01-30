<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // User Admin
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'), // Gunakan password yang sesuai
            'created_at' => now(),
        ]);

        // Account
        DB::table('accounts')->insert([
            'id' => 1,
            'company_name' => 'maarcell',
            'status' => 'active',
        ]);

        // Reference Data
        DB::table('payment_methods')->insert([
            ['id' => 1, 'name' => 'TUNAI'],
            ['id' => 2, 'name' => 'QRIS'],
        ]);

        DB::table('store_types')->insert([
            ['id' => 1, 'name' => 'konter'],
            ['id' => 2, 'name' => 'Konveksi'],
        ]);

        DB::table('product_categories')->insert([
            ['id' => 1, 'name' => 'TAS SEKOLAH'],
        ]);

        DB::table('unit_types')->insert([
            ['id' => 1, 'name' => 'pcs'],
            ['id' => 2, 'name' => 'Lusin'],
        ]);

        // Store
        DB::table('stores')->insert([
            'id' => 1,
            'account_id' => 1,
            'name' => 'Radenmat',
            'store_type_id' => 2,
            'address' => 'Jl. MP. Mangkunegara No.9, Palembang',
            'created_at' => now(),
        ]);

        // POS User (Staff)
        DB::table('pos_users')->insert([
            'id' => 3,
            'store_id' => 1,
            'name' => 'rina',
            'pin' => Hash::make('123456'),
            'role' => 'cashier',
            'is_active' => 1,
            'created_at' => now(),
        ]);

        // Product
        DB::table('products')->insert([
            'id' => 17,
            'store_id' => 1,
            'product_category_id' => 1,
            'name' => 'ALTO 41476G',
            'image' => 'products/1769761680_697c6b90c464f.webp',
            'buying_price' => 150000.00,
            'sku' => null,
            'selling_price' => 300000.00,
            'stock' => 1,
            'unit_type_id' => 1,
            'created_at' => now(),
        ]);

        // Store Product Stock
        DB::table('store_products')->insert([
            'id' => 5,
            'store_id' => 1,
            'product_id' => 17,
            'stock' => 1,
            'created_at' => now(),
        ]);

        // Transaction
        DB::table('transactions')->insert([
            'id' => 41,
            'store_id' => 1,
            'payment_id' => 1,
            'pos_user_id' => 3,
            'transaction_at' => '2026-01-30 08:29:00',
            'subtotal' => 300000.00,
            'tax' => 30000.00,
            'total' => 330000.00,
            'created_at' => now(),
        ]);

        // Transaction Detail
        DB::table('transaction_details')->insert([
            'id' => 76,
            'transaction_id' => 41,
            'product_id' => 17,
            'buying_prices' => 150000.00,
            'quantity' => 1,
            'selling_prices' => 300000.00,
            'subtotal' => 300000.00,
            'created_at' => now(),
        ]);
    }
}