<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    resource: Object, 
    columns: Array    
});
</script>

<template>
    <div class="w-full bg-white border border-black shadow-sm">
        <table class="w-full border-collapse">
            <thead>
                <tr class="border-b-2 border-black bg-gray-100">
                    <th v-for="col in columns" :key="col.key" class="p-3 text-left font-bold text-sm uppercase">
                        {{ col.label }}
                    </th>
                    <th class="p-3 text-right">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in resource.data" :key="row.id" 
                    class="border-b border-gray-200 odd:bg-white even:bg-gray-50 hover:bg-blue-50 transition-colors">
                    <td v-for="col in columns" :key="col.key" class="p-3">
                        <slot :name="col.key" :value="row[col.key]" :row="row">
                            {{ row[col.key] }}
                        </slot>
                    </td>
                    <td class="p-3 text-rigth">
                        <slot name="actions" :row="row" />
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="p-4 flex justify-between items-center border-t border-black bg-white">
            <span class="text-xs font-medium">
                Showing {{ resource.from }} to {{ resource.to }} of {{ resource.total }}
            </span>
            <div class="flex gap-1">
                <template v-for="link in resource.links" :key="link.label">
                    <div v-if="!link.url" 
                        v-html="link.label" 
                        class="px-3 py-1 text-xs border border-gray-200 text-gray-400 cursor-not-allowed bg-gray-50" 
                    />
                    
                    <Link v-else 
                        :href="link.url" 
                        v-html="link.label"
                        class="px-3 py-1 text-xs border border-black transition-colors hover:bg-black hover:text-white"
                        :class="{ 'bg-black text-white': link.active }" 
                    />
                </template>
            </div>
        </div>
    </div>
</template>