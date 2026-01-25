<script setup>
import { Link } from '@inertiajs/vue3';

// We use 'routeName' which matches the ->name() in web.php
const menuItems = [
    { label: 'Dashboard', icon: '📊', routeName: 'dashboard' },
    { label: 'Users',     icon: '👤', routeName: 'users.index' },
    { label: 'Stores',    icon: '🏪', routeName: 'stores.index' },
    { label: 'Staff',     icon: '🪪', routeName: 'pos_users.index' },
    { label: 'Products',  icon: '📦', routeName: 'products.index' },
];
</script>

<template>
    <div class="w-64 bg-black text-white flex flex-col h-screen">
        <div class="p-6 text-l font-bold border-b border-gray-800 uppercase tracking-tighter">
            MAARS COMPANY
        </div>
        
        <nav class="flex-1 p-4 space-y-1">
            <Link v-for="item in menuItems" 
                :key="item.routeName" 
                :href="route(item.routeName)"
                class="flex items-center gap-3 p-3 hover:bg-white hover:text-black transition-colors rounded"
                :class="{ 'bg-white text-black font-bold shadow-[4px_4px_0px_0px_rgba(255,255,255,0.3)]': route().current(item.routeName) }">
                <span>{{ item.icon }}</span>
                {{ item.label }}
            </Link>
        </nav>

        <div class="p-4 border-t border-gray-800">
            <Link :href="route('logout')" method="post" as="button" 
                class="w-full text-left p-3 text-gray-400 hover:text-red-500 font-bold uppercase text-xs">
                LOGOUT
            </Link>
        </div>
    </div>
</template>