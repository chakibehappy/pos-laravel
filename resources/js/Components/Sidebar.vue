<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

// State untuk menyimpan label menu dropdown yang sedang terbuka
const openDropdown = ref(null);

const toggleDropdown = (label) => {
    openDropdown.value = openDropdown.value === label ? null : label;
};

const menuItems = [
    { label: 'Aturan Biaya', icon: '🛒', name: 'topup_fee_rules.*', route: route('topup-fee-rules.index') },
    { label: 'Aturan Tarik Tunai', icon: '🛒', name: 'withdrawal_fee_rules.*', route: route('withdrawal-fee-rules.index') },
    { label: 'Pengguna Toko', icon: '🛒', name: 'pos_user_store.*', route: route('pos-user-stores.index') },
    { label: 'Staff', icon: '🪪', name: 'pos_users.*', route: route('pos_users.index') },
    { label: 'Pengguna', icon: '👤', name: 'users.*', route: route('users.index') },
    { label: 'Toko', icon: '🏪', name: 'stores.*', route: route('stores.index') },
    { label: 'Kas Toko', icon: '🏪', name: 'cash-stores.*', route: route('cash-stores.index') },
     { 
        label: 'Master Saldo', 
        icon: '💳', 
        isDropdown: true,
        activeOn: ['digital-wallets.*', 'wallet-stores.*'],
        children: [
            { label: '💳 Saldo Gudang', name: 'digital-wallets.index', route: route('digital-wallets.index') },
            { label: '💳 Saldo Toko', name: 'digital-wallet-store.index', route: route('digital-wallet-store.index') },
        ]
    },
    { 
        label: 'Master Kategori', 
        icon: '🗂️', 
        isDropdown: true,
        activeOn: ['store-types.*', 'topup-trans-types.*', 'withdrawal-source-types.*', 'payment-methods.*'],
        children: [
            { label: '💼 Jenis Usaha', name: 'store-types.index', route: route('store-types.index') },
            { label: '📱 Jenis Topup', name: 'topup-trans-types.index', route: route('topup-trans-types.index') },
            { label: '🏧 Jenis Tarik Tunai', name: 'withdrawal-source-types.index', route: route('withdrawal-source-types.index') },
            { label: '💵 Metode Pembayaran', name: 'payment-methods.*', route: route('payment-methods.index') },
        ]
    },

    { label: 'Transaksi', icon: '🛒', name: 'transactions.*', route: route('transactions.index') },
    // DROPDOWN TRANSAKSI DIGITAL
    { 
        label: 'Transaksi Digital', 
        icon: '📱', 
        isDropdown: true,
        activeOn: ['topup-transactions.*', 'cash-withdrawals.*'],
        children: [
            { label: '📲 Topup / Saldo', name: 'topup-transactions.index', route: route('topup-transactions.index') },
            { label: '🏧 Tarik Tunai', name: 'cash-withdrawals.index', route: route('cash-withdrawals.index') },
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
    
    { label: 'Dashboard', icon: '📊', name: 'dashboard', route: route('dashboard') },
    { label: 'Akun', icon: '⚙️', name: 'accounts.*', route: route('accounts.index') }
    
];

// Otomatis buka jika anak aktif
menuItems.forEach(item => {
    if (item.isDropdown && item.activeOn.some(r => route().current(r))) {
        openDropdown.value = item.label;
    }
});
</script>

<template>
    <div class="w-64 bg-black text-white flex flex-col min-h-screen flex-shrink-0 border-r border-gray-900 shadow-2xl">
        <div class="p-6 text-l font-black italic tracking-tighter border-b border-gray-900 shadow-sm">
            MAAR <span class="text-yellow-400">COMPANY</span>
        </div>
        
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto custom-scrollbar">
            <template v-for="item in menuItems" :key="item.label">
                
                <Link v-if="!item.isDropdown" :href="item.route"
                    class="flex items-center gap-3 p-3 transition-all rounded font-medium text-xs uppercase italic tracking-tight group"
                    :class="route().current(item.name) 
                        ? 'bg-yellow-400 text-black font-black' 
                        : 'text-gray-400 hover:bg-gray-900 hover:text-yellow-400'">
                    <span class="text-lg transition-all" :class="route().current(item.name) ? 'grayscale-0' : 'grayscale group-hover:grayscale-0'">{{ item.icon }}</span>
                    {{ item.label }}
                </Link>

                <div v-else class="flex flex-col">
                    <button @click="toggleDropdown(item.label)"
                        class="flex items-center gap-3 p-3 transition-all rounded w-full text-left font-medium text-xs uppercase italic tracking-tight group"
                        :class="openDropdown === item.label ? 'text-yellow-400 bg-gray-900/50' : 'text-gray-400 hover:bg-gray-900 hover:text-yellow-400'">
                        <span class="text-lg transition-all" :class="openDropdown === item.label ? 'grayscale-0' : 'grayscale group-hover:grayscale-0'">{{ item.icon }}</span>
                        <div class="flex-1 flex justify-between items-center">
                            {{ item.label }}
                            <span class="text-[8px] transition-transform duration-300" :class="{ 'rotate-180 text-yellow-400': openDropdown === item.label }">▼</span>
                        </div>
                    </button>

                    <div v-if="openDropdown === item.label" class="mt-1 space-y-1 bg-gray-900/20 border-l-2 border-yellow-400 ml-4 rounded-r">
                        <Link v-for="child in item.children" :key="child.name" :href="child.route"
                            class="flex items-center gap-3 p-3 pl-6 transition-all rounded text-[11px] font-bold uppercase italic"
                            :class="route().current(child.name) 
                                ? 'bg-yellow-400/10 text-yellow-400 font-black' 
                                : 'text-gray-500 hover:text-yellow-200 hover:bg-gray-900'">
                            {{ child.label }}
                        </Link>
                    </div>
                </div>

            </template>
        </nav>

        <div class="p-4 border-t border-gray-900 bg-black">
            <Link :href="route('logout')" method="post" as="button" 
                class="w-full text-left p-3 text-xs font-black uppercase italic text-gray-600 hover:text-red-500 transition-colors">
                🚪 Keluar
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
    background: #facc15; /* Yellow-400 */
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #eab308; /* Yellow-500 */
}
</style>