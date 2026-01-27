<script setup>
import { Link } from '@inertiajs/vue3';

const menuItems = [
    { label: 'Dashboard', icon: '📊', route: route('dashboard') },
    { label: 'Pengguna', icon: '👤', route: route('users.index') },      // Simple Admin user
    { label: 'Toko', icon: '🏪', route: route('stores.index') },    // Storefront icon
    { label: 'Staff', icon: '🪪', route: route('pos_users.index') }, // ID Card for POS Staff
    { label: 'Produk', icon: '📦', route: route('products.index') },
    { label: 'Akun', icon: '⚙️', route: route('accounts.index') },
    { label: 'Transaksi', icon: '🛒', route: route('transactions.index') }
];
</script>

<template>
    <div class="w-64 bg-black text-white flex flex-col h-screen">
        <div class="p-6 text-l font-bold border-b border-gray-800">
            MAAR COMPANY
        </div>
        
        <nav class="flex-1 p-4 space-y-1">
            <Link v-for="item in menuItems" :key="item.route" :href="item.route"
                class="flex items-center gap-3 p-3 hover:bg-white hover:text-black transition-colors rounded"
                :class="{ 'bg-white text-black font-bold': $page.url.startsWith(item.route) }">
                <span>{{ item.icon }}</span>
                {{ item.label }}
            </Link>
        </nav>

        <div class="p-4 border-t border-gray-800">
            <Link :href="route('logout')" method="post" as="button" 
                class="w-full text-left p-3 text-gray-400 hover:text-red-500">
                Keluar
            </Link>
        </div>
    </div>
</template>