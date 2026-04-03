<script setup>
import { ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

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
            { label: 'Pegawai Toko', name: 'pos-user-stores.index' ,route: route('pos-user-stores.index') },
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
        activeOn: ['stock-flow.*','digital-wallets.*','store-types.*', 'topup-trans-types.*', 'withdrawal-source-types.*', 'payment-methods.*' ],//'expense-types.*'
        children: [
            { label: 'Mutasi Stok', name: 'stock-flow.index', route: route('stock-flow.index') },
            { label: 'Jenis Wallet', name: 'digital-wallets.index', route: route('digital-wallets.index') },
            { label: 'Jenis Topup', name: 'topup-trans-types.index', route: route('topup-trans-types.index') },
            { label: 'Jenis Tarik Tunai', name: 'withdrawal-source-types.index', route: route('withdrawal-source-types.index') },
            { label: 'Metode Pembayaran', name: 'payment-methods.index', route: route('payment-methods.index') }
            // { label: 'Tipe Pengeluaran', name: 'expense-types.index', route: route('expense-types.index') }
        ]
    },
    { 
        label: 'Transaksi', icon: '🔄', isDropdown: true,
        activeOn: ['transactions.*', 'topup-transactions.*', 'cash-withdrawals.*', 'expenses.*', 'topup-fee-rules.*', 'withdrawal-fee-rules.*'],
        children: [
            { label: 'Riwayat Transaksi', name: 'transactions.index', route: route('transactions.index') },
            { label: 'Riwayat Top Up', name: 'topup-transactions.index', route: route('topup-transactions.index') },
            { label: 'Riwayat Tarik Tunai', name: 'cash-withdrawals.index', route: route('cash-withdrawals.index') },
            { label: 'Riwayat Pengeluaran', name: 'expenses.index', route: route('expenses.index') },
            { label: 'Aturan Biaya', name: 'topup-fee-rules.index', route: route('topup-fee-rules.index') },
            { label: 'Aturan Tarik Tunai', name: 'withdrawal-fee-rules.index', route: route('withdrawal-fee-rules.index') }
        ]
    },
    { label: 'Jenis Layanan', icon: '📋', name: 'services.index', route: route('services.index') },
    { label: 'Aktifitas', icon: '📋', name: 'activity-logs.index', route: route('activity-logs.index') },
];

const isItemActive = (item) => {
    if (!item.isDropdown) return route().current(item.name);
    return item.activeOn.some(r => route().current(r));
};

const openSheet = ref(null);

const toggleSheet = (label) => {
    openSheet.value = openSheet.value === label ? null : label;
};

watch(() => page.url, () => { openSheet.value = null; });
</script>

<template>
    <div class="md:hidden">
        <Transition name="fade">
            <div v-if="openSheet" @click="openSheet = null" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[110]"></div>
        </Transition>

        <Transition name="sheet">
            <div v-if="openSheet" class="fixed bottom-[72px] left-4 right-4 bg-[#1a1a1a] rounded-3xl z-[120] p-5 shadow-2xl border border-white/10 max-h-[60vh] overflow-y-auto custom-scrollbar">
                <div class="flex items-center justify-between mb-4 px-2">
                    <span class="text-xs font-black uppercase text-yellow-400 tracking-widest">Menu {{ openSheet }}</span>
                    <button @click="openSheet = null" class="text-gray-500 text-xl">&times;</button>
                </div>
                <div class="grid grid-cols-1 gap-2">
                    <template v-for="item in menuItems" :key="item.label">
                        <template v-if="item.label === openSheet">
                            <Link v-for="child in item.children" :key="child.name" :href="child.route"
                                class="flex items-center gap-4 p-4 bg-white/5 rounded-2xl active:bg-yellow-400 active:text-black transition-all">
                                <span class="w-2 h-2 rounded-full" :class="route().current(child.name) ? 'bg-yellow-400' : 'bg-gray-700'"></span>
                                <span class="text-[11px] font-bold uppercase tracking-tight text-white group-active:text-black">
                                    {{ child.label }}
                                </span>
                            </Link>
                        </template>
                    </template>
                </div>
            </div>
        </Transition>

        <nav class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f] border-t border-white/10 z-[130] px-4 pb-safe-area shadow-lg">
            <div class="flex items-center gap-2 h-16 overflow-x-auto no-scrollbar py-2">
                <template v-for="item in menuItems" :key="item.label">
                    <component 
                        :is="item.isDropdown ? 'button' : Link"
                        :href="!item.isDropdown ? item.route : undefined"
                        @click="item.isDropdown ? toggleSheet(item.label) : null"
                        class="flex flex-col items-center justify-center min-w-[70px] h-full rounded-xl transition-all relative shrink-0"
                        :class="isItemActive(item) ? 'text-yellow-400' : 'text-gray-500'"
                    >
                        <span class="text-xl mb-1 transition-transform" :class="isItemActive(item) ? 'scale-110' : 'opacity-60'">
                            {{ item.icon }}
                        </span>
                        <span class="text-[9px] font-black uppercase tracking-tighter whitespace-nowrap">
                            {{ item.label }}
                        </span>
                        
                        <div v-if="isItemActive(item)" class="absolute -bottom-1 w-6 h-1 bg-yellow-400 rounded-full shadow-[0_0_8px_rgba(250,204,21,0.5)]"></div>
                    </component>
                </template>

                <Link :href="route('logout')" method="post" as="button" class="flex flex-col items-center justify-center min-w-[70px] h-full text-gray-500 shrink-0">
                    <span class="text-xl mb-1 opacity-60">🚪</span>
                    <span class="text-[9px] font-black uppercase tracking-tighter">Keluar</span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<style scoped>
.pb-safe-area { padding-bottom: env(safe-area-inset-bottom); }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

.sheet-enter-active, .sheet-leave-active { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.sheet-enter-from { transform: translateY(100%) scale(0.9); opacity: 0; }
.sheet-leave-to { transform: translateY(100%) scale(0.9); opacity: 0; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>