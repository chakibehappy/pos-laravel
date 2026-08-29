<script setup>
import { reactive, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    reports: Object,
    stores: Array,
    filters: Object,
    summary: Object
});

// Definisi Kolom (Kolom Estimasi Margin Dihapus)
const columns = [
    { label: 'Nama Item', key: 'item_name', sortable: true },
    { label: 'Tipe', key: 'item_type', sortable: false },
    { label: 'Lokasi Toko', key: 'store_name', sortable: true },
    { label: 'Terjual (Qty)', key: 'total_qty', sortable: true },
    { label: 'Total Omset (Rp)', key: 'total_sales', sortable: true },
    { label: 'Transaksi Terakhir', key: 'last_transaction_at', sortable: true }
];

const typeOptions = [
    { id: '', name: 'Semua Tipe' },
    { id: 'produk', name: '📦 Produk Fisik' },
    { id: 'topup', name: '📱 Top Up' },
    { id: 'tarik_tunai', name: '💸 Tarik Tunai' }
];

// Reactive Filter State
const filterState = reactive({
    search: props.filters?.search || '',
    store_id: props.filters?.store_id || '',
    type: props.filters?.type || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

// Trigger pencarian/filter otomatis
watch(filterState, debounce(() => {
    router.get(route('reports.items.index'), filterState, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, 500));

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Laporan Penjualan Per Item" />
    <AuthenticatedLayout page-title="Laporan Penjualan Per Item" page-subtitle="Analisis Performa Produk & Layanan">
        <div class="p-8">
            
            <!-- RINGKASAN TOTAL / SUMMARY CARDS (Diubah ke 2 kolom) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                    <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Total Volume Terjual</span>
                    <div class="text-3xl font-black text-gray-900 mt-2">
                        {{ Number(summary?.grand_total_qty || 0).toLocaleString('id-ID') }} <span class="text-xs text-gray-400 font-bold uppercase">Unit</span>
                    </div>
                </div>

                <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                    <span class="text-[10px] font-black uppercase text-blue-500 tracking-wider">Total Omset Penjualan</span>
                    <div class="text-3xl font-black text-blue-600 italic mt-2">
                        Rp {{ Number(summary?.grand_total_sales || 0).toLocaleString('id-ID') }}
                    </div>
                </div>
            </div>

            <!-- INTEGRASI DENGAN REUSABLE DATATABLE.VUE -->
            <DataTable 
                title="Rincian Penjualan Per Item" 
                :resource="reports" 
                :columns="columns" 
                :filters="filters"
                :showAddButton="false" 
                :showExportButton="false"
                route-name="reports.items.index" 
                :initial-search="filters?.search || ''"
            >
                <!-- SLOT EXTRA FILTERS (TOKO, TIPE, & TANGGAL) -->
                <template #extra-filters>
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="w-48">
                            <SearchableSelect 
                                v-model="filterState.store_id"
                                :options="stores"
                                label="Lokasi Toko"
                                placeholder="Semua Toko"
                            />
                        </div>

                        <div class="w-44">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Tipe Item</label>
                            <select 
                                v-model="filterState.type" 
                                class="w-full border border-gray-300 rounded-lg p-2 text-xs h-[38px] bg-white outline-none focus:ring-2 focus:ring-blue-500 shadow-sm font-bold"
                            >
                                <option v-for="t in typeOptions" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Mulai</label>
                            <input 
                                type="date" 
                                v-model="filterState.start_date" 
                                class="border border-gray-300 rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 shadow-sm" 
                            />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sampai</label>
                            <input 
                                type="date" 
                                v-model="filterState.end_date" 
                                class="border border-gray-300 rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 shadow-sm" 
                            />
                        </div>
                    </div>
                </template>

                <!-- CUSTOM CELL FORMATTING SLOTS -->
                <template #item_name="{ row }">
                    <span class="font-bold text-gray-900 uppercase">{{ row.item_name }}</span>
                </template>

                <template #item_type="{ row }">
                    <span 
                        class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider"
                        :class="{
                            'bg-blue-50 text-blue-600': row.item_type === 'produk',
                            'bg-amber-50 text-amber-600': row.item_type === 'topup',
                            'bg-purple-50 text-purple-600': row.item_type === 'tarik_tunai',
                            'bg-gray-100 text-gray-600': !['produk','topup','tarik_tunai'].includes(row.item_type)
                        }"
                    >
                        {{ row.item_type ? row.item_type.replace('_', ' ') : '-' }}
                    </span>
                </template>

                <template #store_name="{ value }">
                    <span class="text-xs font-semibold text-gray-600">{{ value || 'Semua Toko' }}</span>
                </template>

                <template #total_qty="{ value }">
                    <span class="font-bold text-gray-800">{{ Number(value).toLocaleString('id-ID') }}</span>
                </template>

                <template #total_sales="{ value }">
                    <span class="font-black text-blue-600 italic text-sm">Rp {{ Number(value).toLocaleString('id-ID') }}</span>
                </template>

                <template #last_transaction_at="{ value }">
                    <span class="text-xs text-gray-400 font-medium">{{ formatDate(value) }}</span>
                </template>

                <template #actions="{ row }">
                    <span class="text-[10px] text-gray-400 italic">Report Only</span>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>