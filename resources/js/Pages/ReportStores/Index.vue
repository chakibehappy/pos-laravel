<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    stores: { type: Array, default: () => [] },
    productCategories: { type: Array, default: () => [] },
    dynamicWallets: { type: Array, default: () => [] },
    filters: Object,
    reportData: { type: Array, default: () => [] }
});

const filterState = reactive({
    store_id: props.filters?.store_id || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const totalPenjualanColumns = computed(() => {
    return (props.productCategories?.length * 2) + (props.dynamicWallets?.length * 2) + 2;
});

const formatNumber = (val) => new Intl.NumberFormat('id-ID').format(val || 0);

const updateFilters = debounce(() => {
    router.get(route('report-stores.index'), filterState, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch(() => filterState, updateFilters, { deep: true });

const exportExcel = () => console.log("Exporting...");
</script>

<template>
    <Head title="Laporan Neraca Penjualan Detail" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="p-8 border-b border-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                        <div>
                            <h2 class="text-xl font-black text-gray-800 uppercase tracking-tight">Rekapitulasi Penjualan Detail</h2>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Status: Arus Jual-Beli Terintegrasi</p>
                        </div>
                        <button @click="exportExcel" class="bg-gray-900 hover:bg-gray-800 text-white px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 transition-all shadow-md active:scale-95">
                            Export Excel
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-6 items-end">
                        <div class="w-72">
                            <label class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2 block">Pilih Cabang</label>
                            <SearchableSelect v-model="filterState.store_id" :options="stores" placeholder="SEMUA CABANG" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Mulai</label>
                            <input type="date" v-model="filterState.start_date" class="border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none transition-all uppercase" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Selesai</label>
                            <input type="date" v-model="filterState.end_date" class="border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none transition-all uppercase" />
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto overflow-y-auto max-h-[700px] relative border-separate">
                    <table class="w-full text-[10px] border-separate border-spacing-0">
                        <thead class="bg-gray-50">
                            <tr class="header-row-1">
                                <th rowspan="3" class="sticky-column left-0 px-6 py-4 text-left uppercase font-black tracking-widest border-b border-r border-gray-200 bg-gray-50 text-gray-500 z-50">Cabang</th>
                                <th rowspan="3" class="px-4 py-4 text-right uppercase font-black tracking-widest border-b border-r border-gray-200 bg-gray-50 text-gray-500">Qty</th>
                                <th :colspan="totalPenjualanColumns" class="px-4 py-3 text-center uppercase font-black tracking-widest border-b border-r border-gray-200 bg-gray-100 text-gray-600">Rincian Penjualan</th>
                                <th rowspan="3" class="px-4 py-4 text-right uppercase font-black tracking-widest border-b border-r border-gray-200 bg-gray-50 text-gray-500">Total</th>
                                <th rowspan="3" class="px-4 py-4 text-right uppercase font-black tracking-widest border-b border-r border-gray-200 bg-gray-50 text-gray-500">Laba Kotor</th>
                                <th rowspan="3" class="px-6 py-4 text-right uppercase font-black tracking-widest border-b border-gray-200 bg-gray-50 text-gray-500">Laba Bersih</th>
                            </tr>

                            <tr class="header-row-2">
                                <th v-for="cat in productCategories" :key="'h2-cat-' + cat.id" colspan="2" class="px-4 py-2 text-center font-black uppercase tracking-tighter text-red-600 border-b border-r border-gray-200 bg-gray-50">
                                    {{ cat.name }}
                                </th>
                                <th v-for="wallet in dynamicWallets" :key="'h2-wal-' + wallet.id" colspan="2" class="px-4 py-2 text-center font-black uppercase tracking-tighter text-blue-600 border-b border-r border-gray-200 bg-gray-50">
                                    {{ wallet.name }}
                                </th>
                                <th colspan="2" class="px-4 py-2 text-center font-black uppercase tracking-tighter text-amber-600 bg-amber-50 border-b border-r border-gray-200">
                                    Tarik Tunai
                                </th>
                            </tr>

                            <tr class="header-row-3">
                                <template v-for="n in (productCategories.length + dynamicWallets.length + 1)" :key="'sub-label-' + n">
                                    <th class="px-2 py-2 text-right font-bold text-gray-400 bg-gray-50 border-b border-r border-gray-200">PEMBELIAN</th>
                                    <th class="px-2 py-2 text-right font-bold text-gray-600 bg-white border-b border-r border-gray-200">PENJUALAN</th>
                                </template>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(row, index) in reportData" :key="index" class="hover:bg-blue-50/30 transition-colors group">
                                <td class="sticky-column left-0 z-10 px-6 py-4 border-r border-gray-200 bg-white group-hover:bg-blue-50 transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <span class="font-black text-gray-800 uppercase tracking-tight italic">{{ row.nama_cabang }}</span>
                                </td>
                                <td class="px-4 py-4 text-right font-bold text-gray-500 border-r border-gray-100">{{ formatNumber(row.qty) }}</td>
                                
                                <template v-for="cat in productCategories" :key="'val-cat-' + cat.id">
                                    <td class="px-2 py-4 text-right text-gray-400 italic bg-gray-50/10">{{ formatNumber(row[cat.name?.toLowerCase() + '_beli']) }}</td>
                                    <td class="px-2 py-4 text-right font-bold text-red-600 border-r border-gray-100">{{ formatNumber(row[cat.name?.toLowerCase() + '_jual']) }}</td>
                                </template>

                                <template v-for="wallet in dynamicWallets" :key="'val-wal-' + wallet.id">
                                    <td class="px-2 py-4 text-right text-gray-400 italic bg-gray-50/10">{{ formatNumber(row[wallet.name?.toLowerCase() + '_beli']) }}</td>
                                    <td class="px-2 py-4 text-right font-bold text-blue-600 border-r border-gray-100">{{ formatNumber(row[wallet.name?.toLowerCase() + '_jual']) }}</td>
                                </template>

                                <td class="px-2 py-4 text-right text-gray-400 italic bg-amber-50/5">{{ formatNumber(row.tarik_tunai_beli) }}</td>
                                <td class="px-2 py-4 text-right font-bold text-amber-700 bg-amber-50/20 border-r border-gray-200">{{ formatNumber(row.tarik_tunai_jual) }}</td>

                                <td class="px-4 py-4 text-right font-black text-gray-900 border-r border-gray-200">{{ formatNumber(row.total) }}</td>
                                <td class="px-4 py-4 text-right font-bold text-blue-600 border-r border-gray-200">{{ formatNumber(row.laba_kotor) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-black tracking-tighter">{{ formatNumber(row.laba_bersih) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
table th, table td {
    white-space: nowrap;
}

.border-separate {
    border-collapse: separate;
    border-spacing: 0;
}

thead th {
    position: sticky;
    background-clip: padding-box;
    z-index: 30;
}

/* Kalibrasi Tinggi Baru (Biasanya tinggi baris p-2/p-3 sekitar 35-45px) */
.header-row-1 th {
    top: 0;
    z-index: 40;
}

.header-row-2 th {
    top: 39.5px; /* Sesuaikan dengan tinggi Baris 1 */
    z-index: 39;
}

.header-row-3 th {
    top: 71.5px; /* Total tinggi Baris 1 + Baris 2 */
    z-index: 38;
}

.sticky-column {
    position: sticky;
    left: 0;
    z-index: 20;
}

/* Prioritas untuk pojok kiri atas */
thead th.sticky-column {
    z-index: 60;
}

/* Scrollbar Modern */
.overflow-x-auto::-webkit-scrollbar { height: 8px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f8fafc; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>