<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\StoreType;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
   
    public function run(): void
    {

        User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        $storeTypes = [
            ['name' => 'Konter'],
            ['name' => 'Koveksi'],
        ];

        foreach ($storeTypes as $type) {
            StoreType::updateOrCreate(['name' => $type['name']], $type);
        }

        // 3. Seed Payment Methods
        $paymentMethods = [
            ['name' => 'Tunai'],
            ['name' => 'QRIS'],
            ['name' => 'Transfer Bank'],

        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(['name' => $method['name']], $method);
        }
    }
}