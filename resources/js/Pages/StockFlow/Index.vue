<script setup>
import { ref, watch } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    resource: Object,
    filters: Object,
    types: Array
});

// Konfigurasi kolom laporan
const columns = [
    { label: 'Waktu', key: 'created_at', sortable: true },
    { label: 'Produk', key: 'product.product.id', sortable: false },
    { label: 'Tipe', key: 'transaction_type', sortable: true },
    { label: 'Referensi ID', key: 'reference_id', sortable: true },
    { label: 'Perubahan Stok', key: 'quantity_change', sortable: true },
    { label: 'Operator', key: 'created_by', sortable: true }
];

const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const selectedType = ref(props.filters.type || '');

// Fungsi sinkronisasi filter ke server
const updateFilter = debounce(() => {
    router.get(
        route('stock-flow.index'),
        {
            search: props.filters.search,
            start_date: startDate.value,
            end_date: endDate.value,
            type: selectedType.value,
            sort: props.filters.sort,
            direction: props.filters.direction
        },
        { preserveState: true, replace: true }
    );
}, 500);

// Watcher untuk filter tanggal dan tipe
watch([startDate, endDate, selectedType], () => {
    updateFilter();
});

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short'
    });
};

const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value);
</script>

<template>
    <Head title="Laporan Mutasi Stok" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-6 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Rentang Tanggal</label>
                    <div class="flex items-center gap-2">
                        <input v-model="startDate" type="date" class="w-full border border-gray-200 rounded-lg p-2 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500/20 transition-all" />
                        <span class="text-gray-300">to</span>
                        <input v-model="endDate" type="date" class="w-full border border-gray-200 rounded-lg p-2 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500/20 transition-all" />
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tipe Transaksi</label>
                    <select v-model="selectedType" class="w-full border border-gray-200 rounded-lg p-2 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                        <option value="">SEMUA TIPE</option>
                        <option v-for="t in types" :key="t" :value="t">{{ t.toUpperCase() }}</option>
                    </select>
                </div>

                <div class="flex flex-col justify-end">
                    <button 
                        v-if="startDate || endDate || selectedType"
                        @click="startDate = ''; endDate = ''; selectedType = '';" 
                        class="text-[10px] font-black text-red-500 uppercase tracking-widest hover:text-red-700 transition-colors flex items-center gap-1 p-2"
                    >
                        ✕ Bersihkan Filter
                    </button>
                </div>
            </div>

            <DataTable 
                title="Riwayat Mutasi Stok"
                :resource="resource" 
                :columns="columns"
                :filters="filters"
                routeName="stock-flow.index" 
                :initialSearch="filters.search"
                :showAddButton="false"
            >
                <template #created_at="{ value }">
                    <span class="text-[11px] text-gray-400 font-bold uppercase tracking-tighter">
                        {{ formatDateTime(value) }}
                    </span>
                </template>

                <template #store_product_id="{ row }">
                    <div class="flex flex-col">
                        <span class="text-[11px] font-black text-gray-800 uppercase leading-none mb-1">
                            {{ row.product?.product.name || 'Produk Tidak Dikenal' }}
                        </span>
                        <span class="text-[9px] text-gray-400 font-bold tracking-widest italic">
                            SKU: {{ row.product?.product.sku || '---' }}
                        </span>
                    </div>
                    
                </template>

                <template #transaction_type="{ value }">
                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-tighter border"
                        :class="{
                            'bg-blue-50 text-blue-600 border-blue-100': value === 'sale',
                            'bg-green-50 text-green-600 border-green-100': ['restock', 'inbound', 'purchase'].includes(value),
                            'bg-amber-50 text-amber-600 border-amber-100': value === 'adjustment',
                            'bg-gray-50 text-gray-500 border-gray-200': !['sale', 'restock', 'inbound', 'adjustment'].includes(value)
                        }"
                    >
                        {{ value }}
                    </span>
                </template>

                <template #reference_id="{ value }">
                    <span class="text-[10px] font-black text-gray-300">
                        #{{ value || 'N/A' }}
                    </span>
                </template>

                <template #quantity_change="{ value }">
                    <div 
                        class="inline-flex items-center px-2 py-1 rounded-lg font-black text-[12px]" 
                        :class="value > 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'"
                    >
                        {{ value > 0 ? '+' : '' }}{{ formatNumber(value) }}
                    </div>
                </template>

                <template #created_by="{ row }">
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] font-black text-gray-500 uppercase italic">
                            👤 {{ row.creator?.name || 'SYSTEM' }}
                        </span>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>