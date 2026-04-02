<script setup>
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const emit = defineEmits(['on-add', 'on-export']) // Tambahkan emit untuk export

const props = defineProps({
    resource: Object, 
    columns: Array,
    title: String,
    showAddButton: Boolean,
    showExportButton: Boolean, // Prop baru untuk mengontrol tombol export
    routeName: String, 
    placeholder: { type: String, default: 'Cari data...' },
    initialSearch: { type: String, default: '' },
    filters: { type: Object, default: () => ({}) }
});

const search = ref(props.initialSearch);

// State Internal untuk Sorting
const sortKey = ref(props.filters?.sort || '');
const sortDirection = ref(props.filters?.direction || 'asc');

// Sinkronisasi internal state saat props filters berubah dari server
watch(() => props.filters, (newFilters) => {
    sortKey.value = newFilters?.sort || '';
    sortDirection.value = newFilters?.direction || 'asc';
}, { deep: true });

const handleSort = (key) => {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDirection.value = 'asc';
    }
    executeRequest();
};

const executeRequest = () => {
    if (props.routeName) {
        router.get(
            route(props.routeName), 
            { 
                ...props.filters,
                search: search.value,
                sort: sortKey.value,
                direction: sortDirection.value
            }, 
            { preserveState: true, replace: true, preserveScroll: true }
        );
    }
};

watch(search, debounce(() => {
    executeRequest();
}, 500));
</script>

<template>
    <div class="w-full flex flex-col">
        <div class="mb-4 flex justify-between items-end">
            <h1 class="text-2xl font-black uppercase tracking-tighter">{{ title }}</h1>
            
            <div class="flex items-center gap-3">
                <slot name="table-actions" />

                <button 
                    v-if="showExportButton"    
                    @click="emit('on-export')"
                    type="button"
                    class="bg-white text-black px-6 py-2 font-bold uppercase border-2 border-black hover:bg-emerald-500 hover:text-white transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px] flex items-center"
                >
                    <span class="mr-2">📥</span> Export
                </button>

                <button 
                    v-if="showAddButton"    
                    @click="emit('on-add')"
                    type="button"
                    class="bg-[#fdc702] text-black px-6 py-2 font-bold uppercase border-2 border-black hover:bg-blue-600 hover:text-white transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px]"
                >
                    Tambahkan
                </button>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4 items-center mb-6">
            <div v-if="routeName" class="flex flex-col md:flex-row gap-3 items-end w-full"> <div class="flex flex-col gap-1 w-full md:w-80">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider ml-1">
                        Pencarian
                    </label>
                    <div class="relative w-full">
                        <input 
                            v-model="search"
                            type="text" 
                            :placeholder="placeholder"
                            class="w-full border border-gray-300 rounded-lg pl-4 pr-4 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white shadow-sm transition-all placeholder:text-gray-400 font-medium"
                        />
                    </div>
                </div>

                <div class="flex gap-2 w-full md:w-auto">
                    <slot name="extra-filters" />
                </div>
            </div>
        </div>

    
  
    <div id="table-container" class="w-full bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
    <table class="w-px min-w-max md:w-full table-auto border-collapse">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
                <th v-for="col in columns" :key="col.key" 
                    @click="col.sortable ? handleSort(col.key) : null"

                    class="py-2 px-1 md:p-4 text-left text-[10px] md:text-xs font-bold text-gray-600 uppercase tracking-tighter select-none group whitespace-nowrap"
                    :class="col.sortable ? 'cursor-pointer hover:bg-gray-100 transition-colors' : ''"
                >
                    <div class="flex items-center gap-1">
                        {{ col.label }}
                        <div v-if="col.sortable" class="flex items-center">
                            <svg v-if="sortKey !== col.key" xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5">
                                <path d="M7 15l5 5 5-5M7 9l5-5 5 5" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-blue-600" :class="sortDirection === 'desc' ? 'rotate-180' : 'rotate-0'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4">
                                <path d="M12 19V5M5 12l7-7 7 7" />
                            </svg>
                        </div>
                    </div>
                </th>
                <th class="py-2 px-2 md:p-4 text-right text-[10px] md:text-xs font-bold text-gray-600 uppercase whitespace-nowrap">
                    Aksi
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <tr v-for="row in resource.data" :key="row.id" class="hover:bg-gray-50 transition-colors">
                <td v-for="col in columns" :key="col.key" 

                    class="py-2 px-1 md:p-4 text-[11px] md:text-sm text-gray-700 font-medium whitespace-nowrap">
                    <slot :name="col.key" :value="row[col.key]" :row="row">
                        <template v-if="col.key === 'shift'">
                            <span :class="row.shift === 'pagi' ? 'text-orange-600 bg-orange-50' : 'text-indigo-600 bg-indigo-50'" 
                                class="px-1.5 py-0.5 rounded-full text-[9px] font-bold uppercase inline-block whitespace-nowrap">
                                {{ row.shift === 'pagi' ? '☀️' : '🌙' }}
                            </span>
                        </template>
                        <template v-else>
                            {{ row[col.key] }}
                        </template>
                    </slot>
                </td>
                <td class="py-2 px-2 md:p-4 text-right text-[11px] md:text-sm whitespace-nowrap">
                    <slot name="actions" :row="row" />
                </td>
            </tr>
        </tbody>
    </table>

          <div class="pagination-mobile-fixed p-4 flex flex-col md:flex-row justify-between items-center border-t border-gray-200 bg-white md:bg-gray-50/50">
                <span class="text-[10px] md:text-xs text-gray-500 font-medium mb-3 md:mb-0 text-center w-full md:w-auto">
                    Menampilkan <span class="font-semibold text-gray-800">{{ resource.from || 0 }}</span> - <span class="font-semibold text-gray-800">{{ resource.to || 0 }}</span> dari <span class="font-semibold text-gray-800">{{ resource.total }}</span> data
                </span>
                
                <div class="flex items-center gap-1 w-full justify-center md:w-auto">
                    <template v-for="(link, index) in resource.links" :key="index">
                        <div v-if="!link.url" 
                            v-show="index === 0 || index === resource.links.length - 1 || link.active || (resource.links[index-1]?.active) || (resource.links[index+1]?.active)"
                            v-html="link.label" 
                            class="page-node px-3 py-2 text-[10px] md:text-xs border border-gray-200 text-gray-300 rounded bg-gray-50 cursor-not-allowed min-w-[38px] text-center" 
                            :class="{ 'is-active': link.active }"
                        />
                        
                        <a v-else 
                            v-show="index === 0 || index === resource.links.length - 1 || link.active || (resource.links[index-1]?.active) || (resource.links[index+1]?.active)"
                            :href="link.url" 
                            v-html="link.label"
                            class="page-node px-3 py-2 text-[10px] md:text-xs border rounded transition-all duration-200 font-bold min-w-[38px] text-center"
                            :class="link.active 
                                ? 'is-active bg-blue-600 border-blue-600 text-white shadow-sm' 
                                : 'bg-white border-gray-300 text-gray-700'"
                        ></a>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
/* Khusus untuk tampilan layar HP (di bawah 768px) */
@media (max-width: 767px) {
    #table-container {
        /* Menghilangkan pembatasan agar bisa melebar keluar layar */
        overflow: visible !important; 
        width: 180% !important; /* Lebar card sengaja dibuat melampaui layar */
        min-width: 700px; /* Memastikan kolom tidak ciut */
        margin-left: -53px;
         margin-bottom: 200px;
    }

    #table-container table {
        width: 100% !important;
        table-layout: auto !important; /* Membiarkan kolom mengambil lebar sesuai kontennya */
    }
    .pagination-mobile-fixed {
        position: fixed;
        bottom: 64px; /* Tepat di atas Bottom Nav hitam Anda */
        left: 0;
        right: 0;
        z-index: 100;
        background-color: white;
        box-shadow: 0 -8px 20px rgba(0, 0, 0, 0.1);
        padding: 12px 16px;
        /* Membuat teks dan tombol tersusun vertikal di HP */
        display: flex !important;
        flex-direction: column !important;
        align-items: center;
        border-top: 1px solid #f3f4f6;
    }

    /* Memaksa ukuran tombol navigasi agar mudah ditekan jari */
    .pagination-mobile-fixed a, 
    .pagination-mobile-fixed div {
        min-width: 44px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .page-node {
        flex-shrink: 0;
    }
    /* Berikan margin bawah pada container tabel agar data terakhir 
       tidak tertutup oleh paginasi yang melayang ini */

}
@media (min-width: 768px) {
    .pagination-mobile-fixed {
        /* Kembalikan ke posisi normal (menyatu di bawah tabel) */
        position: static;
        box-shadow: none;
        /* Tetap gunakan style asli Anda sebelumnya */
        background-color: rgba(249, 250, 251, 0.5); /* bg-gray-50/50 */
        border-radius: 0 0 0.5rem 0.5rem; /* rounded-b-lg */
    }

    .pagination-mobile-fixed .page-node {
        display: flex !important;
    }
}
</style>