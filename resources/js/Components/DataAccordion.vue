<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    resource: {
        type: Object,
        default: () => ({ data: [], links: [] }) // Default state aman
    }, 
    columns: Array,
    title: String,
    routeName: String, 
    placeholder: { type: String, default: 'Cari data...' },
    initialSearch: { type: String, default: '' }
});

const search = ref(props.initialSearch);

watch(search, debounce((value) => {
    if (props.routeName) {
        router.get(
            route(props.routeName), 
            { search: value }, 
            { preserveState: true, replace: true }
        );
    }
}, 500));

const expandedId = ref(null);
const toggle = (id) => {
    expandedId.value = expandedId.value === id ? null : id;
};
</script>

<template>
    <div class="w-full flex flex-col">
        
        <div class="mb-4">
            <h1 class="text-2xl font-black uppercase tracking-tighter">{{ title }}</h1>
        </div>

        <div v-if="routeName" class="mb-6">
            <input 
                v-model="search"
                type="text" 
                :placeholder="placeholder.toUpperCase()"
                class="w-full md:w-1/3 border-2 border-black p-2.5 text-sm font-black focus:bg-yellow-50 outline-none shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] uppercase transition-all"
            />
        </div>

        <div class="w-full bg-white border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-black text-white italic uppercase text-[10px] tracking-widest">
                        <th v-for="col in columns" :key="col.key" 
                            class="p-4 text-left border-r border-gray-800 last:border-0">
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y-4 divide-black italic font-black text-black">
                    <template v-for="row in resource?.data || []" :key="row.id">
                        <tr @click="toggle(row.id)" class="hover:bg-yellow-50 cursor-pointer transition-colors group">
                            <slot name="row" :row="row" :isExpanded="expandedId === row.id">
                                <td v-for="col in columns" :key="col.key" class="p-4">
                                    {{ row[col.key] }}
                                </td>
                            </slot>
                        </tr>

                        <tr v-if="expandedId === row.id" class="bg-gray-50 border-t-2 border-black">
                            <td :colspan="columns?.length || 1" class="p-6">
                                <slot name="content" :row="row" />
                            </td>
                        </tr>
                    </template>

                    <tr v-if="!resource?.data || resource?.data?.length === 0">
                        <td :colspan="columns?.length || 1" class="p-12 text-center text-red-500 font-black uppercase italic bg-gray-50">
                            ⚠️ Data tidak ditemukan
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="resource?.links?.length > 0" class="p-4 flex flex-col md:flex-row justify-between items-center border-t-4 border-black bg-gray-100">
                <span class="text-[10px] font-black uppercase mb-4 md:mb-0 text-black">
                    Menampilkan {{ resource?.from || 0 }} - {{ resource?.to || 0 }} dari {{ resource?.total || 0 }} data
                </span>
                
                <div class="flex items-center gap-2">
                    <template v-for="(link, index) in resource.links" :key="index">
                        <div v-if="!link.url" 
                            v-html="link.label" 
                            class="px-3 py-1 text-[10px] font-black border-2 border-gray-300 text-gray-300 bg-white cursor-not-allowed uppercase" 
                        />
                        
                        <a v-else 
                            :href="link.url" 
                            class="px-3 py-1 text-[10px] font-black border-2 border-black transition-all uppercase shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-[1px] active:translate-y-[1px]"
                            :class="link.active 
                                ? 'bg-yellow-400 text-black' 
                                : 'bg-white text-black hover:bg-black hover:text-white'"
                        >
                            <span v-html="link.label"></span>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>