<script setup>
import { ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const openDropdown = ref(null);

const toggleDropdown = (label) => {
    openDropdown.value = openDropdown.value === label ? null : label;
};

const menuItems = [
    { label: 'Dashboard', icon: '📊', name: 'dashboard', route: route('dashboard') },
    { 
        label: 'Toko', 
        icon: '🏪', 
        isDropdown: true,
        activeOn: [ 'stores.*','pos-user-stores.*','cash-stores.*','digital-wallet-store.*'],
        children: [
                { label: 'Daftar Toko', name: 'stores.index', route: route('stores.index') },
                { label: 'Pegawai Toko',  name: 'pos-user-stores.index' ,route: route('pos-user-stores.index') },
                { label: 'Kas Toko', name: 'cash-stores.index', route: route('cash-stores.index') },
                { label: 'Saldo Toko', name: 'digital-wallet-store.index', route: route('digital-wallet-store.index') } 
        ]
    },
    { 
        label: 'Master Kategori', 
        icon: '🗂️', 
        isDropdown: true,
        activeOn: ['digital-wallets.*','store-types.*', 'topup-trans-types.*', 'withdrawal-source-types.*', 'payment-methods.*'],
        children: [
            { label: 'Jenis Wallet', name: 'digital-wallets.index', route: route('digital-wallets.index') },
            { label: 'Jenis Usaha', name: 'store-types.index', route: route('store-types.index') },
            { label: 'Jenis Topup', name: 'topup-trans-types.index', route: route('topup-trans-types.index') },
            { label: 'Jenis Tarik Tunai', name: 'withdrawal-source-types.index', route: route('withdrawal-source-types.index') },
            { label: 'Metode Pembayaran', name: 'payment-methods.index', route: route('payment-methods.index') }
        ]
    },
    { 
        label: 'Transaksi', 
        icon: '🔄', 
        isDropdown: true,
        activeOn: ['transactions.*', 'topup-transactions.*', 'cash-withdrawals.*', 'topup-fee-rules.*', 'withdrawal-fee-rules.*'],
        children: [
            { label: 'Riwayat Transaksi',  name: 'transactions.index', route: route('transactions.index') },
            { label: 'Riwayat Top Up', name: 'topup-transactions.index', route: route('topup-transactions.index') },
            { label: 'Riwayat Tarik Tunai', name: 'cash-withdrawals.index', route: route('cash-withdrawals.index') },
            { label: 'Aturan Biaya', name: 'topup-fee-rules.index', route: route('topup-fee-rules.index') },
            { label: 'Aturan Tarik Tunai', name: 'withdrawal-fee-rules.index', route: route('withdrawal-fee-rules.index') },
        ]
    },
    { 
        label: 'Master Produk', 
        icon: '📦', 
        isDropdown: true,
        activeOn: [ 'store-products.*', 'products.*', 'product-categories.*', 'unit-types.*'],
        children: [
            { label: 'Stok Produk Toko', name: 'store-products.index', route: route('store-products.index') },
            { label: 'Daftar Produk', name: 'products.index', route: route('products.index') },
            { label: 'Kategori Produk', name: 'product-categories.index', route: route('product-categories.index') },
            { label: 'Satuan Produk', name: 'unit-types.index', route: route('unit-types.index') }
        ]
    },
    { 
        label: 'Pengguna', 
        icon: '👤', 
        isDropdown: true,
        activeOn: ['users.*', 'pos_users.*', 'accounts.*'],
        children: [
            { label: 'Daftar Pengguna', name: 'users.index', route: route('users.index') },
            { label: 'Daftar Staff', name: 'pos_users.index', route: route('pos_users.index') },
            { label: 'Daftar Akun', name: 'accounts.index', route: route('accounts.index') }
        ]
    },
];

const checkActiveDropdown = () => {
    menuItems.forEach(item => {
        if (item.isDropdown && item.activeOn.some(r => route().current(r))) {
            openDropdown.value = item.label;
        }
    });
};

checkActiveDropdown();

watch(() => page.url, () => {
    checkActiveDropdown();
});
</script>

<template>
    <div class="w-64 bg-[#0f0f0f] text-white flex flex-col min-h-screen flex-shrink-0 border-r border-white/5">
        <div class="p-8 flex items-center gap-3 border-b border-white/5">
            <img 
                src="/storage/img/maarlogo.png" 
                alt="MAAR Logo" 
                class="w-10 h-10 object-contain rounded-lg shadow-lg shadow-yellow-400/5"
            />
            <div class="text-base font-bold tracking-tight uppercase italic text-gray-200">
                MAAR COMPANY
            </div>
        </div>
        
        <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto custom-scrollbar">
            <template v-for="item in menuItems" :key="item.label">
                
                <Link v-if="!item.isDropdown" :href="item.route"
                    class="flex items-center gap-3 px-4 py-3 transition-all rounded-xl text-xs font-bold uppercase tracking-wide group"
                    :class="route().current(item.name) 
                        ? 'bg-yellow-400 text-black shadow-lg shadow-yellow-400/20' 
                        : 'text-gray-400 hover:bg-white/5 hover:text-white'">
                    <span class="text-xl transition-all" :class="route().current(item.name) ? 'opacity-100' : 'opacity-40 group-hover:opacity-100'">{{ item.icon }}</span>
                    {{ item.label }}
                </Link>

                <div v-else class="flex flex-col">
                    <button @click="toggleDropdown(item.label)"
                        class="flex items-center gap-3 px-4 py-3 transition-all rounded-xl w-full text-left text-xs font-bold uppercase tracking-wide group"
                        :class="openDropdown === item.label ? 'text-white bg-white/5' : 'text-gray-400 hover:bg-white/5 hover:text-white'">
                        <span class="text-xl transition-all" :class="openDropdown === item.label ? 'opacity-100' : 'opacity-40 group-hover:opacity-100'">{{ item.icon }}</span>
                        <div class="flex-1 flex justify-between items-center">
                            {{ item.label }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" :class="{ 'rotate-180 text-yellow-400': openDropdown === item.label }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>

                    <transition 
                        enter-active-class="transition duration-200 ease-out" 
                        enter-from-class="transform -translate-y-2 opacity-0" 
                        enter-to-class="transform translate-y-0 opacity-100">
                        <div v-if="openDropdown === item.label" class="mt-1 mb-2 space-y-1 ml-6 border-l border-white/10">
                            <Link v-for="child in item.children" :key="child.name" :href="child.route"
                                class="flex items-center gap-3 py-2.5 pl-5 transition-all rounded-r-lg text-xs font-semibold uppercase tracking-normal"
                                :class="route().current(child.name) 
                                    ? 'text-yellow-400 bg-yellow-400/5' 
                                    : 'text-gray-500 hover:text-gray-300 hover:bg-white/5'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="route().current(child.name) ? 'bg-yellow-400' : 'bg-gray-800'"></span>
                                {{ child.label }}
                            </Link>
                        </div>
                    </transition>
                </div>

            </template>
        </nav>

        <div class="p-6 border-t border-white/5">
            <Link :href="route('logout')" method="post" as="button" 
                class="w-full flex items-center gap-3 px-4 py-3 text-xs font-bold uppercase tracking-wide text-gray-500 hover:text-red-400 transition-all rounded-xl hover:bg-red-400/5">
                <span class="text-lg">🚪</span> Keluar
            </Link>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(250, 204, 21, 0.2);
    border-radius: 10px;
}
</style>