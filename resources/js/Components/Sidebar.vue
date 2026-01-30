<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

// State untuk menyimpan label menu dropdown yang sedang terbuka
const openDropdown = ref(null);

const toggleDropdown = (label) => {
    openDropdown.value = openDropdown.value === label ? null : label;
};

const menuItems = [
    { label: 'Dashboard', icon: '📊', name: 'dashboard', route: route('dashboard') },
    { label: 'Pengguna', icon: '👤', name: 'users.*', route: route('users.index') },
    { label: 'Jenis Usaha', icon: '💼', name: 'store-types.*', route: route('store-types.index') },
    { label: 'Toko', icon: '🏪', name: 'stores.*', route: route('stores.index') },
    { label: 'Staff', icon: '🪪', name: 'pos_users.*', route: route('pos_users.index') },
    { label: 'Kas Toko', icon: '🏪', name: 'stores.*', route: route('cash-stores.index') },
    { label: 'Tarik Tunai', icon: '🏪', name: 'stores.*', route: route('cash-withdrawals.index') },
    { 
        label: 'Master Saldo', 
        icon: '💳', 
        isDropdown: true,
        activeOn: ['digital-wallets.*', 'wallet-stores.*'],
        children: [
            { label: '💳 Saldo Gudang', name: 'digital-wallets.index', route: route('digital-wallets.index') },
            { label: '💳 Saldo Toko', name: 'wallet-stores.index', route: route('wallet-stores.index') },
        ]
    },
    
    { 
        label: 'Master Produk', 
        icon: '📦', 
        isDropdown: true,
        activeOn: ['products.*', 'product-categories.*', 'unit-types.*', 'store-products.*'],
        children: [
            { label: '🏷️ Produk Gudang', name: 'products.index', route: route('products.index') },
            { label: '🏷️ Produk Kategori', name: 'product-categories.index', route: route('product-categories.index') },
            { label: '🏷️ Satuan Produk', name: 'unit-types.index', route: route('unit-types.index') },
            { label: '🏷️ Produk Toko', name: 'store-products.index', route: route('store-products.index') },
        ]
    },
    
    { label: 'Akun', icon: '⚙️', name: 'accounts.*', route: route('accounts.index') },
    { label: 'Metode Pembayaran', icon: '💵', name: 'payment-methods.*', route: route('payment-methods.index') },
    { label: 'Transaksi', icon: '🛒', name: 'transactions.*', route: route('transactions.index') }
];

// Otomatis buka jika anak aktif
menuItems.forEach(item => {
    if (item.isDropdown && item.activeOn.some(r => route().current(r))) {
        openDropdown.value = item.label;
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
                    <button @click="toggleDropdown(item.label)"
                        class="flex items-center gap-3 p-3 hover:bg-white hover:text-black transition-colors rounded w-full text-left"
                        :class="{ 'bg-gray-900 text-white': openDropdown === item.label && !item.activeOn.some(r => route().current(r)) }">
                        <span>{{ item.icon }}</span>
                        <div class="flex-1 flex justify-between items-center">
                            {{ item.label }}
                            <span class="text-[10px] transform transition-transform" :class="{ 'rotate-180': openDropdown === item.label }">▼</span>
                        </div>
                    </button>

                    <div v-if="openDropdown === item.label" class="mt-1 space-y-1">
                        <Link v-for="child in item.children" :key="child.name" :href="child.route"
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
<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #000;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #333;
}
</style>