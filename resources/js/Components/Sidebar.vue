<script setup>
import { ref, watch, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    isMinimized: { type: Boolean, default: false }
});

const emit = defineEmits(['expand', 'toggle']);
const page = usePage();
const openDropdown = ref(null);

const toggleSidebar = () => emit('toggle');

const toggleDropdown = (label) => {
    if (props.isMinimized) {
        emit('expand');
        openDropdown.value = label;
        return;
    }
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
            { label: 'Riwayat Transaksi', name: 'transactions.index', route: route('transactions.index') },
            { label: 'Riwayat Top Up', name: 'topup-transactions.index', route: route('topup-transactions.index') },
            { label: 'Riwayat Tarik Tunai', name: 'cash-withdrawals.index', route: route('cash-withdrawals.index') },
            { label: 'Aturan Biaya', name: 'topup-fee-rules.index', route: route('topup-fee-rules.index') },
            { label: 'Aturan Tarik Tunai', name: 'withdrawal-fee-rules.index', route: route('withdrawal-fee-rules.index') },
        ]
    },
    { label: 'Riwayat Aktifitas', icon: '📋', name: 'activity-logs.index', route: route('activity-logs.index') },
];

const isItemActive = (item) => {
    if (!item.isDropdown) return route().current(item.name);
    return item.activeOn.some(r => route().current(r));
};

const checkActiveDropdown = () => {
    if (props.isMinimized) {
        openDropdown.value = null;
        return;
    }
    for (const item of menuItems) {
        if (item.isDropdown && isItemActive(item)) {
            openDropdown.value = item.label;
            return;
        }
    }
};

onMounted(checkActiveDropdown);
watch(() => page.url, checkActiveDropdown);
watch(() => props.isMinimized, (min) => min ? (openDropdown.value = null) : setTimeout(checkActiveDropdown, 150));
</script>

<template>
    <div 
        class="relative bg-[#0f0f0f] text-white flex flex-col flex-shrink-0 z-[50] rounded-r-[60px] shadow-xl sidebar-main-transition"
        style="height: calc(100vh - 64px);" 
        :class="isMinimized ? 'w-20' : 'w-64'"
    >
        <button @click="toggleSidebar" class="absolute right-[-10px] top-1/2 -translate-y-1/2 w-[6px] h-32 bg-gray-600/30 rounded-full z-[100] transition-all hover:bg-gray-500/50 cursor-pointer flex items-center justify-center group">
            <div class="w-1 h-4 bg-white/10 rounded-full group-hover:bg-white/40 transition-colors"></div>
        </button>

        <div class="flex items-center pt-10 sidebar-main-transition" 
             :class="isMinimized ? 'px-0 justify-center' : 'px-5 gap-4'">
            <img src="/storage/img/maarlogo.png" 
                 class="w-10 h-10 object-contain shrink-0 transition-all duration-700 ease-in-out" 
                 :class="isMinimized ? 'scale-90 translate-x-0' : 'scale-110'" />
            
            <Transition name="text-pop">
                <div v-if="!isMinimized" class="flex flex-col justify-center overflow-visible">
                    <div class="text-lg font-black uppercase italic tracking-tighter text-white leading-none whitespace-nowrap">
                        MAAR COMPANY
                    </div>
                </div>
            </Transition>
        </div>

        <nav class="flex-1 pt-10 space-y-4 overflow-y-auto overflow-x-hidden custom-scrollbar pl-4">
            <template v-for="item in menuItems" :key="item.label">
                <div class="relative w-full">
                    <div v-if="isItemActive(item)" 
                        class="absolute top-0 right-0 h-14 bg-yellow-400 rounded-l-[35px] z-0 w-full sidebar-main-transition">
                        <div class="absolute -top-[25px] right-0 w-[25px] h-[25px] bg-yellow-400 after:content-[''] after:absolute after:inset-0 after:bg-[#0f0f0f] after:rounded-br-[25px]"></div>
                        <div class="absolute -bottom-[25px] right-0 w-[25px] h-[25px] bg-yellow-400 after:content-[''] after:absolute after:inset-0 after:bg-[#0f0f0f] after:rounded-tr-[25px]"></div>
                    </div>

                    <component 
                        :is="item.isDropdown ? 'button' : Link"
                        :href="!item.isDropdown ? item.route : undefined"
                        @click="item.isDropdown ? toggleDropdown(item.label) : (isMinimized && emit('expand'))"
                        class="flex items-center sidebar-main-transition uppercase cursor-pointer relative z-10 w-full"
                        :class="[
                            isItemActive(item) ? 'text-black h-14' : 'text-gray-400 hover:bg-white/5 rounded-l-xl h-12',
                            isMinimized ? 'justify-center px-0' : 'px-4 gap-4'
                        ]"
                    >
                        <div class="flex items-center justify-center transition-all duration-700 ease-in-out shrink-0"
                             :class="isMinimized ? 'w-full' : 'w-6'">
                            <span class="text-xl transition-all duration-700" 
                                  :class="isItemActive(item) ? 'scale-125' : 'scale-100'">
                                {{ item.icon }}
                            </span>
                        </div>
                        
                        <Transition name="text-pop">
                            <span v-if="!isMinimized" class="whitespace-nowrap text-xs font-bold flex-1 text-left flex items-center justify-between overflow-hidden">
                                {{ item.label }}
                                <svg v-if="item.isDropdown" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300 ml-2" :class="{ 'rotate-180': openDropdown === item.label }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </Transition>
                    </component>

                    <Transition name="dropdown-bounce">
                        <div v-if="item.isDropdown && openDropdown === item.label && !isMinimized" 
                            class="mt-4 ml-8 relative z-10 border-l border-white/10">
                            <Link v-for="child in item.children" :key="child.name" :href="child.route"
                                class="flex items-center gap-3 py-2.5 pl-5 text-[10px] font-semibold uppercase transition-all"
                                :class="route().current(child.name) ? 'text-yellow-400' : 'text-gray-500 hover:text-gray-300'">
                                <span class="w-1.5 h-1.5 rounded-full shrink-0 transition-colors" 
                                    :class="route().current(child.name) ? 'bg-yellow-400' : 'bg-gray-800'"></span>
                                {{ child.label }}
                            </Link>
                        </div>
                    </Transition>
                </div>
            </template>
        </nav>

        <div class="p-4 mb-4 border-t border-white/5">
            <Link :href="route('logout')" method="post" as="button" 
                  class="flex items-center w-full sidebar-main-transition text-xs font-bold uppercase text-gray-400 hover:text-red-400 rounded-l-xl hover:bg-red-400/5 group" 
                  :class="isMinimized ? 'justify-center px-0 h-12' : 'px-4 py-3 gap-4'">
                
                <div class="flex items-center justify-center transition-all duration-700 ease-in-out shrink-0"
                     :class="isMinimized ? 'w-full' : 'w-6'">
                    <span class="text-xl group-hover:scale-125 transition-transform duration-300">🚪</span>
                </div>

                <Transition name="text-pop">
                    <span v-if="!isMinimized">Keluar</span>
                </Transition>
            </Link>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 0px; }

/* Global Spring Transition */
.sidebar-main-transition {
    transition: all 0.65s cubic-bezier(0.68, -0.6, 0.32, 1.6);
}

/* Animasi Teks Pop In/Out */
.text-pop-enter-active {
    animation: pop-in 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
.text-pop-leave-active {
    animation: pop-out 0.25s cubic-bezier(0.4, 0, 1, 1) forwards;
}

@keyframes pop-in {
    0% { opacity: 0; transform: translateX(-20px) scale(0.7); filter: blur(8px); }
    100% { opacity: 1; transform: translateX(0) scale(1); filter: blur(0); }
}

@keyframes pop-out {
    0% { opacity: 1; transform: scale(1); filter: blur(0); }
    100% { opacity: 0; transform: scale(0.9) translateX(10px); filter: blur(4px); }
}

/* Dropdown Animation */
.dropdown-bounce-enter-active {
    animation: d-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    transform-origin: top;
}
.dropdown-bounce-leave-active {
    animation: d-out 0.2s ease-in;
}

@keyframes d-in {
    from { opacity: 0; transform: translateY(-10px) scaleY(0.7); }
    to { opacity: 1; transform: translateY(0) scaleY(1); }
}

@keyframes d-out {
    to { opacity: 0; transform: translateY(-10px) scaleY(0.8); }
}
</style>