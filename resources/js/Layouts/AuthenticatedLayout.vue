<script setup>
import Sidebar from '@/Components/Sidebar.vue';
</script>

<template>
    <div class="flex h-screen bg-gray-100">
        <Sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white border-b border-black flex items-center justify-between px-8">
                <div class="text-sm font-bold uppercase">
                    Current Page: <span class="text-gray-500">{{ $page.component }}</span>
                </div>
                
                <div class="flex items-center gap-4">
                    <button class="p-2 text-gray-400 hover:text-black transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>

                    <span class="text-xs font-mono bg-gray-100 p-1">{{ $page.props.auth.user.email }}</span>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-8">
                <slot />
            </main>
        </div>
    </div>
</template>