<script setup>
import Sidebar from '@/Components/Sidebar.vue';
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
</script>

<template>
    <div class="flex flex-col h-screen bg-gray-100 overflow-hidden">
        
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 shrink-0 z-[60]">
            <div class="flex items-center gap-4">
                <div class="flex flex-col justify-center">
                    <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                        Current Page: <span class="text-black">{{ $page.component }}</span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <Link 
                    :href="route('transactions.approval')" 
                    class="p-2 text-gray-400 hover:text-black transition-colors relative"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    
                    <div v-if="$page.props.pendingApprovalsCount > 0" 
                         class="absolute -top-1 -right-1 flex items-center justify-center 
                                bg-red-600 text-white font-black italic rounded-full 
                                min-w-[18px] h-[18px] px-1 text-[9px] 
                                border-2 border-white shadow-sm ring-1 ring-red-600/20"
                    >
                        {{ formattedCount }}
                    </div>
                </Link>

                <span class="text-xs font-mono bg-white px-3 py-1.5 border border-gray-100 rounded-lg font-bold shadow-inner">
                    {{ $page.props.auth.user.email }}
                </span>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden">
            <Sidebar 
                :isMinimized="isMinimized" 
                @expand="expandSidebar" 
                @toggle="toggleSidebar"
            />

            <main class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Scrollbar halus untuk area konten utama */
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