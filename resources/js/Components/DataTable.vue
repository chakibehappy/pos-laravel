<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    resource: Object, 
    columns: Array    
});
</script>

<template>
    <div class="w-full bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th v-for="col in columns" :key="col.key" 
                        class="p-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        {{ col.label }}
                    </th>
                    <th class="p-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="row in resource.data" :key="row.id" 
                    class="hover:bg-gray-50 transition-colors duration-150">
                    <td v-for="col in columns" :key="col.key" class="p-4 text-sm text-gray-700">
                        <slot :name="col.key" :value="row[col.key]" :row="row">
                            <template v-if="col.key === 'shift'">
                                <span :class="row.shift === 'pagi' ? 'text-orange-600 bg-orange-50' : 'text-indigo-600 bg-indigo-50'" 
                                      class="px-2 py-1 rounded-full text-[10px] font-bold uppercase">
                                    {{ row.shift === 'pagi' ? '☀️' : '🌙' }} {{ row.shift }}
                                </span>
                            </template>
                            <template v-else>
                                {{ row[col.key] }}
                            </template>
                        </slot>
                    </td>
                    <td class="p-4 text-right text-sm">
                        <slot name="actions" :row="row" />
                    </td>
                </tr>
                <tr v-if="resource.data.length === 0">
                    <td :colspan="columns.length + 1" class="p-8 text-center text-gray-400 italic text-sm">
                        Tidak ada data yang tersedia
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="p-4 flex justify-between items-center border-t border-gray-200 bg-gray-50/50">
            <span class="text-xs text-gray-500 font-medium">
                Menampilkan <span class="font-semibold text-gray-800">{{ resource.from }}</span> - <span class="font-semibold text-gray-800">{{ resource.to }}</span> dari <span class="font-semibold text-gray-800">{{ resource.total }}</span> data
            </span>
            
            <div class="flex items-center gap-1">
                <template v-for="link in resource.links" :key="link.label">
                    <div v-if="!link.url" 
                        v-html="link.label" 
                        class="px-3 py-1 text-xs border border-gray-200 text-gray-300 rounded bg-white cursor-not-allowed" 
                    />
                    
                    <a v-else 
                        :href="link.url" 
                        class="px-3 py-1 text-xs border rounded transition-all duration-200 font-medium"
                        :class="link.active 
                            ? 'bg-blue-600 border-blue-600 text-white shadow-sm' 
                            : 'bg-white border-gray-300 text-gray-600 hover:border-gray-400 hover:bg-gray-50'"
                    >
                        <span v-html="link.label"></span>
                    </a>
                </template>
            </div>
        </div>
    </div>
</template>