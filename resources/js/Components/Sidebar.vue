<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

// State untuk dropdown
const isProductMenuOpen = ref(false);

const menuItems = [
    { label: 'Dashboard', icon: '📊', name: 'dashboard', route: route('dashboard') },
    { label: 'Pengguna', icon: '👤', name: 'users.*', route: route('users.index') },
    { label: 'Jenis Usaha', icon: '💼', name: 'store-types.*', route: route('store-types.index') },
    { label: 'Toko', icon: '🏪', name: 'stores.*', route: route('stores.index') },
    { label: 'Staff', icon: '🪪', name: 'pos_users.*', route: route('pos_users.index') },
    
    // Group Pengaturan Produk
    { 
        label: 'Pengaturan Produk', 
        icon: '📦', 
        isDropdown: true,
        activeOn: ['products.*', 'product-categories.*', 'unit-types.*'],
        children: [
            { label: '🏷️ Data Produk', name: 'products.*', route: route('products.index') },
            { label: '🏷️ Produk Kategori', name: 'product-categories.*', route: route('product-categories.index') },
            { label: '🏷️ Satuan Produk', name: 'unit-types.*', route: route('unit-types.index') },
        ]
    },
    
    { label: 'Akun', icon: '⚙️', name: 'accounts.*', route: route('accounts.index') },
    { label: 'Metode Pembayaran', icon: '💳', name: 'payment-methods.*', route: route('payment-methods.index') },
    { label: 'Transaksi', icon: '🛒', name: 'transactions.*', route: route('transactions.index') }
];

// Otomatis buka jika anak aktif
menuItems.forEach(item => {
    if (item.isDropdown && item.activeOn.some(r => route().current(r))) {
        isProductMenuOpen.value = true;
    }
});
</script>

<template>
    <div class="w-64 bg-black text-white flex flex-col h-screen">
        <div class="p-6 text-l font-bold border-b border-gray-800">
            MAAR COMPANY
        </div>
        
        <nav class="flex-1 p-4 space-y-1">
            <template v-for="item in menuItems" :key="item.label">
                
                <Link v-if="!item.isDropdown" :href="item.route"
                    class="flex items-center gap-3 p-3 hover:bg-white hover:text-black transition-colors rounded"
                    :class="{ 'bg-white text-black font-bold': route().current(item.name) }">
                    <span>{{ item.icon }}</span>
                    {{ item.label }}
                </Link>

                <div v-else class="flex flex-col">
                    <button @click="isProductMenuOpen = !isProductMenuOpen"
                        class="flex items-center gap-3 p-3 hover:bg-white hover:text-black transition-colors rounded w-full text-left"
                        :class="{ 'text-white font-normal': !isProductMenuOpen, 'bg-gray-900 text-white': isProductMenuOpen && !item.activeOn.some(r => route().current(r)) }">
                        <span>{{ item.icon }}</span>
                        <div class="flex-1 flex justify-between items-center">
                            {{ item.label }}
                            <span class="text-[10px] transform transition-transform" :class="{ 'rotate-180': isProductMenuOpen }">▼</span>
                        </div>
                    </button>

                    <div v-if="isProductMenuOpen" class="mt-1 space-y-1">
                        <Link v-for="child in item.children" :key="child.route" :href="child.route"
                            class="flex items-center gap-3 p-3 pl-11 hover:bg-white hover:text-black transition-colors rounded text-sm"
                            :class="{ 'bg-white text-black font-bold': route().current(child.name) }">
                            {{ child.label }}
                        </Link>
                    </div>
                </div>

            </template>
        </nav>

        <div class="p-4 border-t border-gray-800">
            <Link :href="route('logout')" method="post" as="button" 
                class="w-full text-left p-3 text-gray-400 hover:text-red-500">
                Keluar
            </Link>
        </div>
    </div>
</template>