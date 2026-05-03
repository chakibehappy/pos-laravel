<script setup>
import Sidebar from '@/Components/Sidebar.vue';
import BottomNav from '@/Components/BottomNav.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();

// State untuk kontrol minimize
const isMinimized = ref(false);

// Fungsi toggle pemicu sidebar
const toggleSidebar = () => {
    isMinimized.value = !isMinimized.value;
};

// Fungsi untuk memaksa sidebar terbuka (dipicu dari Sidebar.vue)
const expandSidebar = () => {
    isMinimized.value = false;
};

// Logika untuk membatasi angka maksimal 999+
const formattedCount = computed(() => {
    const count = page.props.pendingApprovalsCount ?? 0;
    return count > 999 ? '999+' : count;
});

const canSeeNotifications = computed(() => {
    return ['owner', 'developer'].includes(page.props.auth.role);
});

// Props untuk menerima judul yang dikirim oleh halaman (page)
defineProps({
    pageTitle: { type: String, default: 'Laporan Penjualan' }, 
    pageSubtitle: { type: String, default: 'Percabang' }
});
</script>

<template>
    <div class="flex flex-col h-screen bg-gray-100 overflow-hidden relative font-sans">
        
        <header class="absolute top-0 left-0 right-0 h-16 flex items-center justify-between px-[4vw] md:px-8 z-[60] pointer-events-none">
            
            <div class="md:hidden pointer-events-auto bg-white/70 backdrop-blur-md -ml-[4vw] pl-[5vw] pr-[4vw] py-[1vh] rounded-r-[4vw] border-y border-r border-white/50 shadow-sm transition-all duration-300">
                <div class="pl-[2vw]">
                    <h2 class="text-[3.5vw] font-black text-gray-800 uppercase tracking-tight truncate max-w-[45vw]">
                        {{ pageTitle }}
                    </h2>
                    <p v-if="pageSubtitle" class="text-[2.8vw] font-bold text-gray-400 uppercase tracking-widest mt-0.5">
                        {{ pageSubtitle }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-[2vw] pointer-events-auto ml-auto transition-all duration-300">
                <Link v-if="canSeeNotifications"
                    :href="route('transactions.approval')" 
                    class="w-[10vw] h-[10vw] md:w-9 md:h-9 flex items-center justify-center text-gray-500 hover:text-black transition-all relative bg-white rounded-full shadow border border-gray-100 p-[1vw] md:p-0"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-[4.5vw] w-[4.5vw] md:h-4 md:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    
                    <div v-if="$page.props.pendingApprovalsCount > 0" 
                        class="absolute -top-[0.5vw] -right-[0.5vw] md:-top-1 md:-right-1 flex items-center justify-center 
                                bg-red-600 text-white font-black italic rounded-full 
                                min-w-[3.5vw] h-[3.5vw] md:min-w-[16px] md:h-[16px] px-1 text-[2.2vw] md:text-[8px] 
                                border border-white shadow-sm"
                    >
                        {{ formattedCount }}
                    </div>
                </Link>

                <Link 
                    :href="route('logout')" 
                    method="post" 
                    as="button" 
                    class="w-[10vw] h-[10vw] md:w-9 md:h-9 flex items-center justify-center text-gray-500 hover:text-red-600 transition-all bg-white rounded-full shadow border border-gray-100 p-[1vw] md:p-0"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-[4.5vw] w-[4.5vw] md:h-4 md:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </Link>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden">
            <Sidebar 
                :isMinimized="isMinimized" 
                @expand="expandSidebar" 
                @toggle="toggleSidebar"
            />

            <main class="flex-1 overflow-y-auto p-[4vw] md:p-8 custom-scrollbar">
                <div class="h-12 md:h-4"></div> 
                <slot />
            </main>
        </div>
        
        <BottomNav />
    </div>
</template>

<style scoped>
.font-sans {
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}
</style>