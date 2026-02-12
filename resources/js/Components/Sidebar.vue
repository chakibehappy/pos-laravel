<script setup>
import { ref, watch, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    isMinimized: {
        type: Boolean,
        default: false
    }
});

// Definisikan emit agar bisa memerintah layout untuk melebar
const emit = defineEmits(['expand']);

const page = usePage();
const openDropdown = ref(null);

const toggleDropdown = (label) => {
    // Jika diklik saat sidebar kecil, minta layout untuk melebarkan sidebar
    if (props.isMinimized) {
        emit('expand');
        openDropdown.value = label;
        return;
    }
    
    // Jika sidebar normal, toggle seperti biasa
    openDropdown.value = openDropdown.value === label ? null : label;
};

const menuItems = [
    { label: 'Dashboard', icon: '📊', name: 'dashboard', route: route('dashboard') },
    { 
        label: 'Pengguna', icon: '👤', isDropdown: true,
        activeOn: ['users.*', 'pos_users.*', 'accounts.*'],
        children: [
            { label: 'Daftar Pengguna', name: 'users.index', route: route('users.index') },
            { label: 'Daftar Staff', name: 'pos_users.index', route: route('pos_users.index') },
        ]
    },
    { 
        label: 'Toko', icon: '🏪', isDropdown: true,
        activeOn: [ 'stores.*','pos-user-stores.*','cash-stores.*','digital-wallet-store.*'],
        children: [
                { label: 'Daftar Toko', name: 'stores.index', route: route('stores.index') },
                { label: 'Pegawai Toko',  name: 'pos-user-stores.index' ,route: route('pos-user-stores.index') },
                { label: 'Kas Toko', name: 'cash-stores.index', route: route('cash-stores.index') },
                { label: 'Saldo Toko', name: 'digital-wallet-store.index', route: route('digital-wallet-store.index') } 
        ]
    },
    { 
        label: 'Master Produk', icon: '📦', isDropdown: true,
        activeOn: [ 'store-products.*', 'products.*', 'product-categories.*', 'unit-types.*'],
        children: [
            { label: 'Stok Produk Toko', name: 'store-products.index', route: route('store-products.index') },
            { label: 'Daftar Produk', name: 'products.index', route: route('products.index') },
            { label: 'Kategori Produk', name: 'product-categories.index', route: route('product-categories.index') },
            { label: 'Satuan Produk', name: 'unit-types.index', route: route('unit-types.index') }
        ]
    },
    { 
        label: 'Master Kategori', icon: '🗂️', isDropdown: true,
        activeOn: ['digital-wallets.*','store-types.*', 'topup-trans-types.*', 'withdrawal-source-types.*', 'payment-methods.*'],
        children: [
            { label: 'Jenis Wallet', name: 'digital-wallets.index', route: route('digital-wallets.index') },
            { label: 'Jenis Topup', name: 'topup-trans-types.index', route: route('topup-trans-types.index') },
            { label: 'Jenis Tarik Tunai', name: 'withdrawal-source-types.index', route: route('withdrawal-source-types.index') },
            { label: 'Metode Pembayaran', name: 'payment-methods.index', route: route('payment-methods.index') }
        ]
    },
    { 
        label: 'Transaksi', icon: '🔄', isDropdown: true,
        activeOn: ['transactions.*', 'topup-transactions.*', 'cash-withdrawals.*', 'topup-fee-rules.*', 'withdrawal-fee-rules.*'],
        children: [
            { label: 'Riwayat Transaksi',  name: 'transactions.index', route: route('transactions.index') },
            { label: 'Riwayat Top Up', name: 'topup-transactions.index', route: route('topup-transactions.index') },
            { label: 'Riwayat Tarik Tunai', name: 'cash-withdrawals.index', route: route('cash-withdrawals.index') },
            { label: 'Aturan Biaya', name: 'topup-fee-rules.index', route: route('topup-fee-rules.index') },
            { label: 'Aturan Tarik Tunai', name: 'withdrawal-fee-rules.index', route: route('withdrawal-fee-rules.index') },
        ]
    },
];

const checkActiveDropdown = () => {
    if (props.isMinimized) {
        openDropdown.value = null;
        return;
    }
    for (const item of menuItems) {
        if (item.isDropdown && item.activeOn.some(r => route().current(r))) {
            openDropdown.value = item.label;
            return;
        }
    }
};

onMounted(checkActiveDropdown);
watch(() => page.url, checkActiveDropdown);
watch(() => props.isMinimized, (minimized) => {
    if (minimized) openDropdown.value = null;
    else setTimeout(checkActiveDropdown, 150);
});
</script>

<template>
    <div 
        class="bg-[#0f0f0f] text-white flex flex-col min-h-screen flex-shrink-0 border-r border-white/5 transition-all duration-300 ease-in-out"
        :class="isMinimized ? 'w-20' : 'w-64'"
    >
        <div class="p-6 h-20 flex items-center gap-3 border-b border-white/5 overflow-hidden shrink-0">
            <img src="/storage/img/maarlogo.png" class="w-10 h-10 shrink-0 object-contain" />
            <div v-show="!isMinimized" class="text-base font-bold uppercase italic whitespace-nowrap">
                MAAR COMPANY
            </div>
        </div>
        
        <nav class="flex-1 p-3 space-y-1.5 overflow-y-auto overflow-x-hidden custom-scrollbar">
            <template v-for="item in menuItems" :key="item.label">
                
                <Link v-if="!item.isDropdown" :href="item.route"
                    @click="isMinimized && emit('expand')"
                    class="flex items-center gap-4 px-4 py-3 transition-all rounded-xl text-xs font-bold uppercase group cursor-pointer"
                    :class="route().current(item.name) ? 'bg-yellow-400 text-black' : 'text-gray-400 hover:bg-white/5'">
                    <span class="text-xl shrink-0">{{ item.icon }}</span>
                    <span v-show="!isMinimized" class="whitespace-nowrap">{{ item.label }}</span>
                </Link>

                <div v-else class="flex flex-col">
                    <button @click="toggleDropdown(item.label)"
                        class="flex items-center gap-4 px-4 py-3 transition-all rounded-xl w-full text-left text-xs font-bold uppercase group cursor-pointer"
                        :class="[
                            item.activeOn.some(r => route().current(r)) 
                                ? 'bg-yellow-400 text-black' 
                                : (openDropdown === item.label ? 'text-white bg-white/5' : 'text-gray-400 hover:bg-white/5')
                        ]">
                        <span class="text-xl shrink-0">{{ item.icon }}</span>
                        <div v-show="!isMinimized" class="flex-1 flex justify-between items-center whitespace-nowrap">
                            {{ item.label }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" :class="{ 'rotate-180': openDropdown === item.label }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>

                    <div v-show="openDropdown === item.label && !isMinimized" class="mt-1 ml-6 border-l border-white/10">
                        <Link v-for="child in item.children" :key="child.name" :href="child.route"
                            class="flex items-center gap-3 py-2.5 pl-5 text-[10px] font-semibold uppercase transition-all cursor-pointer"
                            :class="route().current(child.name) ? 'text-yellow-400' : 'text-gray-500 hover:text-gray-300'">
                            <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route().current(child.name) ? 'bg-yellow-400' : 'bg-gray-800'"></span>
                            {{ child.label }}
                        </Link>
                    </div>
                </div>
            </template>
        </nav>

        <div class="p-4 border-t border-white/5 shrink-0">
            <Link :href="route('logout')" method="post" as="button" 
                class="w-full flex items-center gap-4 px-4 py-3 text-xs font-bold uppercase text-gray-500 hover:text-red-400 rounded-xl transition-all hover:bg-red-400/5 group cursor-pointer">
                <span class="text-xl shrink-0 group-hover:scale-110 transition-transform">🚪</span>
                <span v-show="!isMinimized" class="whitespace-nowrap">Keluar</span>
            </Link>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(250, 204, 21, 0.1); border-radius: 10px; }
</style>