<script setup>
import { ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const menuItems = [
    { label: 'Dashboard', icon: '📊', name: 'dashboard', route: route('dashboard') },
    { label: 'Laporan Penjualan', icon: '📋', name: 'report-stores.index', route: route('report-stores.index') },
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
        activeOn: ['stock-flow.*','digital-wallets.*','store-types.*', 'topup-trans-types.*', 'withdrawal-source-types.*', 'payment-methods.*' , 'services.*'],
        children: [
            { label: 'Mutasi Stok', name: 'stock-flow.index', route: route('stock-flow.index') },
            { label: 'Jenis Wallet', name: 'digital-wallets.index', route: route('digital-wallets.index') },
            { label: 'Jenis Topup', name: 'topup-trans-types.index', route: route('topup-trans-types.index') },
            { label: 'Jenis Tarik Tunai', name: 'withdrawal-source-types.index', route: route('withdrawal-source-types.index') },
            { label: 'Metode Pembayaran', name: 'payment-methods.index', route: route('payment-methods.index') },
            { label: 'Jenis Layanan',  name: 'services.index', route: route('services.index') }
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
    { 
        label: 'Laporan', icon: '📋', isDropdown: true, roles: ['owner', 'developer'],
        activeOn: ['report-buying.*','report-stores.*'],
        children: [
            { label: 'Laporan Pembelian',  name: 'report-buying.index', route: route('report-buying.index') },
            { label: 'Laporan Penjualan', name: 'report-stores.index', route: route('report-stores.index') },
        ]
    },
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

/**
 * Logika Mouse Drag-to-Scroll
 */
const navRef = ref(null);
const isDown = ref(false);
const startX = ref(0);
const scrollLeft = ref(0);

const startDragging = (e) => {
    isDown.value = true;
    startX.value = e.pageX - navRef.value.offsetLeft;
    scrollLeft.value = navRef.value.scrollLeft;
};

const stopDragging = () => {
    isDown.value = false;
};

const moveDragging = (e) => {
    if (!isDown.value) return;
    e.preventDefault();
    const x = e.pageX - navRef.value.offsetLeft;
    const walk = (x - startX.value) * 2;
    navRef.value.scrollLeft = scrollLeft.value - walk;
};
</script>

<template>
    <div class="md:hidden">
        <Transition name="fade">
            <div v-if="openSheet" @click="openSheet = null" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[110]"></div>
        </Transition>

        <Transition name="sheet">
            <div v-if="openSheet" class="fixed bottom-[10.5vh] left-[4vw] right-[4vw] bg-[#1a1a1a] rounded-3xl z-[120] p-[4vw] shadow-2xl border border-white/10 max-h-[55vh] overflow-y-auto custom-scrollbar">
                <div class="flex items-center justify-between mb-3 px-[1vw]">
                    <span class="text-[3vw] font-black uppercase text-yellow-400 tracking-widest">Menu {{ openSheet }}</span>
                    <button @click="openSheet = null" class="text-gray-500 text-[5vw] leading-none">&times;</button>
                </div>
                <div class="grid grid-cols-1 gap-2">
                    <template v-for="item in menuItems" :key="item.label">
                        <template v-if="item.label === openSheet">
                            <Link v-for="child in item.children" :key="child.name" :href="child.route"
                                class="flex items-center gap-[3vw] p-[3.5vw] bg-white/5 rounded-2xl active:bg-yellow-400 active:text-black transition-all">
                                <span class="w-[2vw] h-[2vw] rounded-full" :class="route().current(child.name) ? 'bg-yellow-400' : 'bg-gray-700'"></span>
                                <span class="text-[3vw] font-bold uppercase tracking-tight text-white group-active:text-black">
                                    {{ child.label }}
                                </span>
                            </Link>
                        </template>
                    </template>
                </div>
            </div>
        </Transition>

        <nav class="fixed bottom-0 left-0 right-0 bg-[#0f0f0f] border-t border-white/10 z-[130] px-[1.5vw] pb-safe-area shadow-lg">
            <div 
                ref="navRef"
                class="flex items-center h-[9.5vh] min-h-[48px] max-h-[56px] overflow-x-auto no-scrollbar py-[0.6vh] px-[1vw] gap-[2vw] cursor-grab active:cursor-grabbing select-none"
                @mousedown="startDragging"
                @mouseleave="stopDragging"
                @mouseup="stopDragging"
                @mousemove="moveDragging"
            >
                <template v-for="item in menuItems" :key="item.label">
                    <component 
                        :is="item.isDropdown ? 'button' : Link"
                        :href="!item.isDropdown ? item.route : undefined"
                        @click="item.isDropdown ? toggleSheet(item.label) : null"
                        class="flex flex-col items-center justify-center min-w-[17vw] h-full rounded-xl transition-all relative shrink-0"
                        :class="isItemActive(item) ? 'text-yellow-400' : 'text-gray-500'"
                    >
                        <span class="text-[4.5vw] md:text-xl mb-0.5 transition-transform" :class="isItemActive(item) ? 'scale-105' : 'opacity-60'">
                            {{ item.icon }}
                        </span>

                        <span class="text-[2.6vw] md:text-[8px] font-black uppercase tracking-tighter whitespace-nowrap px-[0.5vw]">
                            {{ item.label }}
                        </span>
                        
                        <div v-if="isItemActive(item)" class="absolute -bottom-0.5 w-[5vw] md:w-5 h-[4px] bg-yellow-400 rounded-full shadow-[0_0_6px_rgba(250,204,21,0.4)]"></div>
                    </component>
                </template>
                <div class="min-w-[4vw] shrink-0"></div>
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

.sheet-enter-active, .sheet-leave-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.sheet-enter-from { transform: translateY(100%) scale(0.92); opacity: 0; }
.sheet-leave-to { transform: translateY(100%) scale(0.92); opacity: 0; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.cursor-grab { cursor: grab; }
.cursor-grabbing { cursor: grabbing; }
</style>