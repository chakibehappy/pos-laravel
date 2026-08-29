<script setup>
import { reactive, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    stores: { type: Array, default: () => [] },
    filters: Object,
    itemsData: { type: Array, default: () => [] }
});

const filterState = reactive({
    store_id: props.filters?.store_id || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

// Calculate Totals for Footer
const totals = computed(() => {
    return props.itemsData.reduce((acc, item) => {
        acc.qty += parseFloat(item.total_qty || 0);
        acc.modal += parseFloat(item.total_modal || 0);
        acc.omzet += parseFloat(item.total_omzet || 0);
        acc.laba += parseFloat(item.laba || 0);
        return acc;
    }, { qty: 0, modal: 0, omzet: 0, laba: 0 });
});

const formatNumber = (val) => new Intl.NumberFormat('id-ID').format(val || 0);

const updateFilters = debounce(() => {
    router.get(route('report-items.index'), filterState, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch(() => filterState, updateFilters, { deep: true });

const exportExcel = () => {
    const params = new URLSearchParams(filterState).toString();
    window.location.href = route('report-items.export') + '?' + params;
};
</script>

<template>
    <Head title="Laporan Per Item" />

    <AuthenticatedLayout page-title="Laporan Penjualan" page-subtitle="Per Item">
        <div class="p-2 md:p-4 pb-20 md:pb-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="p-4 border-b border-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <div>
                            <h2 class="text-lg md:text-xl font-black text-gray-800 uppercase tracking-tight">Laporan Penjualan Per Item</h2>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Status: Arus Jual-Beli Terintegrasi (Real-time)</p>
                        </div>
                        
                        <!-- <button @click="exportExcel" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-md">
                            Export Excel
                        </button> -->
                    </div>

                    <div class="flex flex-col md:flex-row flex-wrap gap-4 items-end">
                        <div class="w-full md:w-72">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pilih Cabang</label>
                            <select v-model="filterState.store_id" class="w-full border border-gray-200 rounded-xl p-2 text-xs font-bold focus:ring-2 focus:ring-emerald-500 outline-none transition-all uppercase bg-white cursor-pointer">
                                <option value="">-- SEMUA CABANG --</option>
                                <option v-for="store in stores" :key="store.id" :value="store.id">
                                    {{ store.name }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="flex flex-col gap-1 w-full md:w-auto">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Mulai Tanggal</label>
                            <input type="date" v-model="filterState.start_date" class="border border-gray-200 rounded-xl p-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 uppercase" />
                        </div>
                        
                        <div class="flex flex-col gap-1 w-full md:w-auto">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sampai Tanggal</label>
                            <input type="date" v-model="filterState.end_date" class="border border-gray-200 rounded-xl p-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 uppercase" />
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto overflow-y-auto max-h-[500px] relative">
                    <table class="w-full text-[10px] border-separate border-spacing-0">
                        <thead class="bg-gray-50 sticky top-0 z-30 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 text-left uppercase font-black tracking-widest border-b border-gray-200 text-gray-500">Nama Item</th>
                                <th class="px-4 py-3 text-left uppercase font-black tracking-widest border-b border-gray-200 text-gray-500">Kategori</th>
                                <th class="px-3 py-3 text-right uppercase font-black tracking-widest border-b border-gray-200 text-gray-500">Qty Terjual</th>
                                <th class="px-3 py-3 text-right uppercase font-black tracking-widest border-b border-gray-200 text-blue-600">Total Modal (Beli)</th>
                                <th class="px-3 py-3 text-right uppercase font-black tracking-widest border-b border-gray-200 text-blue-800">Total Omzet (Jual)</th>
                                <th class="px-3 py-3 text-right uppercase font-black tracking-widest border-b border-gray-200 text-emerald-600">Laba Kotor</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-50 text-black">
                            <tr v-for="(item, index) in itemsData" :key="index" class="hover:bg-blue-50/30 transition-colors group">
                                <td class="px-4 py-3 border-r border-gray-100 font-bold">{{ item.product_name }}</td>
                                <td class="px-4 py-3 border-r border-gray-100 text-gray-500 font-semibold">{{ item.category_name }}</td>
                                <td class="px-3 py-3 border-r border-gray-100 text-right font-black">{{ formatNumber(item.total_qty) }}</td>
                                <td class="px-3 py-3 border-r border-gray-100 text-right italic text-gray-600">{{ formatNumber(item.total_modal) }}</td>
                                <td class="px-3 py-3 border-r border-gray-100 text-right font-bold">{{ formatNumber(item.total_omzet) }}</td>
                                <td class="px-3 py-3 text-right">
                                    <span class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-black tracking-tighter">
                                        {{ formatNumber(item.laba) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>

                        <tfoot v-if="itemsData.length > 0" class="sticky bottom-0 z-40 bg-gray-100 shadow-[0_-2px_5px_rgba(0,0,0,0.05)]">
                            <tr class="text-black font-black uppercase tracking-widest text-[10px]">
                                <td colspan="2" class="px-4 py-3 border-r border-black/10 text-right">TOTAL KESELURUHAN</td>
                                <td class="px-3 py-3 text-right border-r border-black/10">{{ formatNumber(totals.qty) }}</td>
                                <td class="px-3 py-3 text-right border-r border-black/10">{{ formatNumber(totals.modal) }}</td>
                                <td class="px-3 py-3 text-right border-r border-black/10">{{ formatNumber(totals.omzet) }}</td>
                                <td class="px-3 py-3 text-right">{{ formatNumber(totals.laba) }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    <div v-if="itemsData.length === 0" class="p-20 text-center">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Tidak ada item terjual pada periode ini</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>