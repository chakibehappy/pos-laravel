<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    stores: { 
        type: Array, 
        default: () => [] 
    },
    productCategories: { 
        type: Array, 
        default: () => [] 
    },
    dynamicWallets: { 
        type: Array, 
        default: () => [] 
    },
    filters: Object,
    reportData: { 
        type: Array, 
        default: () => [] 
    }
});

const filterState = reactive({
    store_id: props.filters?.store_id || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const totalPenjualanColumns = computed(() => {
    const categoriesCount = props.productCategories?.length || 0;
    const walletsCount = props.dynamicWallets?.length || 0;
    return categoriesCount + walletsCount;
});

const formatNumber = (val) => {
    return new Intl.NumberFormat('id-ID').format(val || 0);
};

const updateFilters = debounce(() => {
    router.get(route('report-stores.index'), filterState, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch(() => filterState, updateFilters, { deep: true });

const exportExcel = () => {
    console.log("Exporting to Excel...");
};
</script>

<template>
    <Head title="Laporan Neraca Penjualan" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="p-8 border-b border-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                        <div>
                            <h2 class="text-xl font-black text-gray-800 uppercase tracking-tight">Rekapitulasi Penjualan</h2>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Laporan performa harian per cabang</p>
                        </div>
                        <button 
                            @click="exportExcel"
                            class="bg-gray-900 hover:bg-gray-800 text-white px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 transition-all shadow-md active:scale-95"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Excel
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-6 items-end">
                        <div class="w-72">
                            <label class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2 block">Pilih Cabang</label>
                            <SearchableSelect 
                                v-model="filterState.store_id"
                                :options="stores"
                                placeholder="SEMUA CABANG"
                            />
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

                <div class="overflow-x-auto">
                    <table class="w-full text-[11px]">
                        <thead>
                            <tr class="bg-gray-50/50 text-gray-400 border-b border-gray-100">
                                <th rowspan="2" class="px-6 py-4 text-left uppercase font-black tracking-widest">Cabang</th>
                                <th rowspan="2" class="px-4 py-4 text-right uppercase font-black tracking-widest">Qty</th>
                                <th :colspan="totalPenjualanColumns" class="px-4 py-2 text-center uppercase font-black tracking-widest border-b border-gray-100">Penjualan</th>
                                <th rowspan="2" class="px-4 py-4 text-right uppercase font-black tracking-widest">Total</th>
                                <th rowspan="2" class="px-4 py-4 text-right uppercase font-black tracking-widest">Laba Kotor</th>
                                <th rowspan="2" class="px-6 py-4 text-right uppercase font-black tracking-widest">Laba Bersih</th>
                            </tr>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th v-for="cat in productCategories" :key="'cat-' + cat.id" 
                                    class="px-4 py-3 text-right font-bold uppercase tracking-tighter text-red-600">
                                    {{ cat.name }}
                                </th>
                                <th v-for="wallet in dynamicWallets" :key="'wallet-' + wallet.id" 
                                    class="px-4 py-3 text-right font-bold uppercase tracking-tighter text-blue-600">
                                    {{ wallet.name }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(row, index) in reportData" :key="index" class="hover:bg-blue-50/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <span class="font-black text-gray-800 uppercase tracking-tight italic">
                                        {{ row.nama_cabang }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right font-bold text-gray-500">
                                    {{ formatNumber(row.qty) }}
                                </td>
                                
                                <td v-for="cat in productCategories" :key="'val-cat-' + cat.id" class="px-4 py-4 text-right text-gray-600">
                                    {{ formatNumber(row[cat.name?.toLowerCase()]) }}
                                </td>

                                <td v-for="wallet in dynamicWallets" :key="'val-wallet-' + wallet.id" class="px-4 py-4 text-right text-gray-600">
                                    {{ formatNumber(row[wallet.name?.toLowerCase()]) }}
                                </td>

                                <td class="px-4 py-4 text-right font-black text-gray-900">
                                    {{ formatNumber(row.total) }}
                                </td>
                                <td class="px-4 py-4 text-right font-bold text-blue-600">
                                    {{ formatNumber(row.laba_kotor) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-black tracking-tighter">
                                        {{ formatNumber(row.laba_bersih) }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="reportData.length === 0">
                                <td :colspan="5 + totalPenjualanColumns" class="py-10 text-center text-gray-400 font-bold uppercase tracking-widest italic text-xs">
                                    Data Tidak Ditemukan
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
/* Styling Scrollbar tipis agar tetap elegan */
.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}
.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>