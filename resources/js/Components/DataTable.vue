<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const emit = defineEmits(['on-add'])

const props = defineProps({
    resource: Object, 
    columns: Array,
    title: String,
    showAddButton: Boolean,
    routeName: String, // Untuk trigger pencarian otomatis
    placeholder: { type: String, default: 'Cari data...' },
    initialSearch: { type: String, default: '' }
});

const search = ref(props.initialSearch);

// OTOMATIS: Sistem pencarian server-side identik
watch(search, debounce((value) => {
    if (props.routeName) {
        router.get(
            route(props.routeName), 
            { search: value }, 
            { preserveState: true, replace: true }
        );
    }
}, 500));
</script>

<template>
    <div class="w-full flex flex-col">
        
        <div class="mb-4 flex justify-between items-end">
            <h1 class="text-2xl font-black uppercase tracking-tighter">{{ title }}</h1>
            <button 
                v-if="showAddButton"    
                @click="emit('on-add')"
                class="bg-[#fdc702] text-black px-6 py-2 font-bold uppercase border-2 border-black hover:bg-blue-600 hover:text-white transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px]"
            >
                Tambahkan
            </button>
        </div>

        <div class="flex flex-col md:flex-row gap-4 items-center mb-6">
    
   <div v-if="routeName" class="mb-6 flex flex-col md:flex-row gap-3 items-center">
            <div class="relative w-full md:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                </span>
                <input 
                    v-model="search"
                    type="text" 
                    :placeholder="placeholder"
                    class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white shadow-sm transition-all transition-duration-200 placeholder:text-gray-400"
                />
            </div>

            <div class="flex gap-2 w-full md:w-auto">
                <slot name="extra-filters" />
            </div>
        </div>


    </div>

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
                        <td v-for="col in columns" :key="col.key" class="p-4 text-sm text-gray-700 font-medium">
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
                        <td :colspan="columns.length + 1" class="p-12 text-center text-gray-400 font-medium italic text-sm">
                            --- Data tidak ditemukan ---
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="p-4 flex flex-col md:flex-row justify-between items-center border-t border-gray-200 bg-gray-50/50">
                <span class="text-xs text-gray-500 font-medium mb-4 md:mb-0">
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
    </div>
</template>