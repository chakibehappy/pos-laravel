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
                    <span class="text-xs font-mono bg-gray-100 p-1">{{ $page.props.auth.user.email }}</span>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-8">
                <slot />
            </main>
        </div>
    </div>
</template>