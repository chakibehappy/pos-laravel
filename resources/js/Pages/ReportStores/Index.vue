<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    stores: { type: Array, default: () => [] },
    storeTypes: { type: Array, default: () => [] },
    productCategories: { type: Array, default: () => [] },
    dynamicWallets: { type: Array, default: () => [] },
    filters: Object,
    reportData: { type: Array, default: () => [] },
    globalExpense: { type: Number, default: 0 } // Menerima data global expense dari backend
});

const filterState = reactive({
    // Default otomatis ke jenis usaha pertama jika tidak ada filter aktif
    store_type_id: props.filters?.store_type_id || (props.storeTypes.length > 0 ? props.storeTypes[0].id : ''),
    store_id: props.filters?.store_id || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

// --- LOGIKA FILTER CABANG BERDASARKAN JENIS USAHA ---
const filteredStores = computed(() => {
    if (!filterState.store_type_id) {
        return props.stores;
    }
    return props.stores.filter(store => store.store_type_id == filterState.store_type_id);
});

// Reset store_id jika jenis usaha berubah
watch(() => filterState.store_type_id, () => {
    filterState.store_id = ''; 
    filterState.start_date = ''; 
    filterState.end_date = '';   
});

/**
 * LOGIKA RESET TANGGAL SAAT CABANG DI-KOSONGKAN
 */
watch(() => filterState.store_id, (newVal, oldVal) => {
    if (oldVal && !newVal) {
        filterState.start_date = '';
        filterState.end_date = '';
    }
});

const totalPenjualanColumns = computed(() => {
    const catCols = (props.productCategories?.length || 0) * 2;
    const walletCols = (props.dynamicWallets?.length || 0) * 2;
    return catCols + walletCols + 2; 
});

// --- LOGIKA HITUNG TOTAL UNTUK FOOTER ---
const totals = computed(() => {
    const res = {
        qty: 0,
        total: 0,
        laba_kotor: 0,
        operasional: 0,
        laba_cabang: 0,
        tarik_tunai_beli: 0,
        tarik_tunai_jual: 0,
        categories: {},
        wallets: {}
    };

    props.reportData.forEach(item => {
        res.qty += parseFloat(item.qty || 0);
        res.total += parseFloat(item.total || 0);
        res.laba_kotor += parseFloat(item.laba_kotor || 0);
        res.operasional += parseFloat(item.operasional || 0);
        res.laba_cabang += parseFloat(item.laba_cabang || 0);
        res.tarik_tunai_beli += parseFloat(item.tarik_tunai_beli || 0);
        res.tarik_tunai_jual += parseFloat(item.tarik_tunai_jual || 0);

        props.productCategories.forEach(cat => {
            const keyJual = `${cat.name.toLowerCase()}_jual`;
            const keyBeli = `${cat.name.toLowerCase()}_beli`;
            res.categories[keyJual] = (res.categories[keyJual] || 0) + parseFloat(item[keyJual] || 0);
            res.categories[keyBeli] = (res.categories[keyBeli] || 0) + parseFloat(item[keyBeli] || 0);
        });

        props.dynamicWallets.forEach(wallet => {
            const cleanKey = wallet.name.toLowerCase().replace(/\s+/g, '_');
            const keyJual = `${cleanKey}_jual`;
            const keyBeli = `${cleanKey}_beli`;
            res.wallets[keyJual] = (res.wallets[keyJual] || 0) + parseFloat(item[keyJual] || 0);
            res.wallets[keyBeli] = (res.wallets[keyBeli] || 0) + parseFloat(item[keyBeli] || 0);
        });
    });

    return res;
});

// Logika hitung Laba Bersih Akhir
const labaBersihTotal = computed(() => {
    return totals.value.laba_cabang - props.globalExpense;
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

const exportExcel = () => {
    const selectedStoreType = props.storeTypes.find(t => t.id == filterState.store_type_id);
    const storeTypeName = selectedStoreType ? selectedStoreType.name : 'SEMUA JENIS USAHA';

    const selectedStore = props.stores.find(s => s.id == filterState.store_id);
    const storeName = selectedStore ? selectedStore.name : 'SELURUH TOKO';

    const exportParams = {
        ...filterState,
        store_type_name: storeTypeName,
        store_name: storeName
    };

    const params = new URLSearchParams(exportParams).toString();
    window.location.href = route('report-stores.export') + '?' + params;
};
</script>

<template>
    <Head title="Laporan Penjualan " />

    <AuthenticatedLayout>
        <div class="p-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="p-8 border-b border-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                        <div>
                            <h2 class="text-xl font-black text-gray-800 uppercase tracking-tight">Laporan Penjualan Percabang</h2>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Status: Arus Jual-Beli Terintegrasi (Real-time)</p>
                        </div>
                        
                        <button 
                            @click="exportExcel" 
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 transition-all shadow-md active:scale-95"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Export Excel
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-6 items-end">
                        <div class="w-64">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Jenis Usaha</label>

                            <select 
                                v-model="filterState.store_type_id" 
                                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-emerald-500 outline-none transition-all uppercase appearance-none bg-white cursor-pointer"
                                style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%236b7280%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1rem;">
                                <option value="">SEMUA JENIS USAHA</option>
                                <option v-for="type in storeTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>

                        <div class="w-72">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Pilih Cabang</label>

                            <SearchableSelect 
                                v-model="filterState.store_id" 
                                :options="filteredStores" 
                                placeholder="SEMUA CABANG" 
                            />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Mulai Tanggal</label>
                            <input type="date" v-model="filterState.start_date" class="border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none transition-all uppercase" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sampai Tanggal</label>
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
                                <th :colspan="totalPenjualanColumns" class="px-4 py-3 text-center uppercase font-black tracking-widest border-b border-r border-gray-200 bg-gray-100 text-gray-600">Rincian Penjualan (Omzet & Modal)</th>
                                <th rowspan="3" class="px-4 py-4 text-right uppercase font-black tracking-widest border-b border-r border-gray-200 bg-gray-50 text-gray-500">Total Omzet</th>
                                <th rowspan="3" class="px-4 py-4 text-right uppercase font-black tracking-widest border-b border-r border-gray-200 bg-gray-50 text-gray-500">Laba Kotor</th>
                                <th rowspan="3" class="px-4 py-4 text-right uppercase font-black tracking-widest border-b border-r border-gray-200 bg-gray-50 text-gray-500">Operasional</th>
                                <th rowspan="3" class="px-4 py-4 text-right uppercase font-black tracking-widest border-b border-r border-gray-200 bg-gray-50 text-gray-500">Laba Cabang</th>
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

                        <tbody class="divide-y divide-gray-50 text-black">
                            <tr v-for="(row, index) in reportData" :key="index" class="hover:bg-blue-50/30 transition-colors group">
                                <td class="sticky-column left-0 z-10 px-6 py-4 border-r border-gray-200 bg-white group-hover:bg-blue-50 transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <span class="font-black uppercase tracking-tight italic">{{ row.nama_cabang }}</span>
                                </td>
                                
                                <td class="px-4 py-4 text-right font-bold border-r border-gray-100">{{ formatNumber(row.qty) }}</td>
                                
                                <template v-for="cat in productCategories" :key="'val-cat-' + cat.id">
                                    <td class="px-2 py-4 text-right italic bg-gray-50/10">
                                        {{ formatNumber(row[cat.name?.toLowerCase() + '_beli']) }}
                                    </td>
                                    <td class="px-2 py-4 text-right font-bold border-r border-gray-100">
                                        {{ formatNumber(row[cat.name?.toLowerCase() + '_jual']) }}
                                    </td>
                                </template>

                                <template v-for="wallet in dynamicWallets" :key="'val-wal-' + wallet.id">
                                    <td class="px-2 py-4 text-right italic bg-gray-50/10">
                                        {{ formatNumber(row[wallet.name?.toLowerCase().replace(/\s+/g, '_') + '_beli']) }}
                                    </td>
                                    <td class="px-2 py-4 text-right font-bold border-r border-gray-100">
                                        {{ formatNumber(row[wallet.name?.toLowerCase().replace(/\s+/g, '_') + '_jual']) }}
                                    </td>
                                </template>

                                <td class="px-2 py-4 text-right italic bg-amber-50/5">{{ formatNumber(row.tar_beli || row.tarik_tunai_beli) }}</td>
                                <td class="px-2 py-4 text-right font-bold bg-amber-50/20 border-r border-gray-200">{{ formatNumber(row.tar_jual || row.tarik_tunai_jual) }}</td>

                                <td class="px-4 py-4 text-right font-black border-r border-gray-200">{{ formatNumber(row.total) }}</td>
                                <td class="px-4 py-4 text-right font-bold border-r border-gray-200 bg-blue-50/10 italic">
                                    {{ formatNumber(row.laba_kotor) }}
                                </td>
                                <td class="px-4 py-4 text-right font-bold border-r border-gray-200 bg-red-50/10 italic">
                                    {{ formatNumber(row.operasional || 0) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-3 py-1 rounded-lg bg-emerald-50 font-black tracking-tighter">
                                        {{ formatNumber(row.laba_cabang) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>

                        <tfoot v-if="reportData.length > 0" class="sticky bottom-0 z-50">
                            <tr class="bg-gray-100 text-black font-black uppercase tracking-widest">
                                <td class="sticky-column left-0 px-6 py-4 border-r border-black/10 bg-gray-100 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.2)]">TOTAL</td>
                                
                                <td class="px-4 py-4 text-right border-r border-black/10 bg-gray-100">{{ formatNumber(totals.qty) }}</td>
                                
                                <template v-for="cat in productCategories" :key="'foot-cat-' + cat.id">
                                    <td class="px-2 py-4 text-right border-r border-black/10 bg-gray-100">{{ formatNumber(totals.categories[cat.name.toLowerCase() + '_beli']) }}</td>
                                    <td class="px-2 py-4 text-right border-r border-black/10 bg-gray-100">{{ formatNumber(totals.categories[cat.name.toLowerCase() + '_jual']) }}</td>
                                </template>

                                <template v-for="wallet in dynamicWallets" :key="'foot-wal-' + wallet.id">
                                    <td class="px-2 py-4 text-right border-r border-black/10 bg-gray-100">{{ formatNumber(totals.wallets[wallet.name.toLowerCase().replace(/\s+/g, '_') + '_beli']) }}</td>
                                    <td class="px-2 py-4 text-right border-r border-black/10 bg-gray-100">{{ formatNumber(totals.wallets[wallet.name.toLowerCase().replace(/\s+/g, '_') + '_jual']) }}</td>
                                </template>

                                <td class="px-2 py-4 text-right border-r border-black/10 bg-gray-100">{{ formatNumber(totals.tarik_tunai_beli) }}</td>
                                <td class="px-2 py-4 text-right border-r border-black/10 bg-gray-100">{{ formatNumber(totals.tarik_tunai_jual) }}</td>

                                <td class="px-4 py-4 text-right border-r border-black/10 bg-gray-100">{{ formatNumber(totals.total) }}</td>
                                <td class="px-4 py-4 text-right border-r border-black/10 bg-gray-100">{{ formatNumber(totals.laba_kotor) }}</td>
                                <td class="px-4 py-4 text-right border-r border-black/10 bg-gray-100">{{ formatNumber(totals.operasional) }}</td>
                                <td class="px-6 py-4 text-right bg-gray-100">{{ formatNumber(totals.laba_cabang) }}</td>
                            </tr>
                            
                            <tr class="bg-gray-100 text-black font-black uppercase tracking-widest border-t border-black/20 ">
                                <td class="sticky-column left-0 px-6 py-4 border-r border-black/10 bg-gray-100 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.2)]">PENGELUARAN GLOBAL</td>
                                <td :colspan="totalPenjualanColumns + 4" class="bg-gray-100  shadow-[2px_0_5px_-2px_rgba(0,0,0,0.2)]"></td>
                                <td class="px-6 py-4 text-right bg-gray-100 font-black shadow-[2px_0_5px_-2px_rgba(0,0,0,0.2)]">
                                    {{ formatNumber(globalExpense) }}
                                </td>
                            </tr>

                            <tr class="bg-[#FDC700] text-black font-black uppercase tracking-widest border-t border-black/20">
                                <td class="sticky-column left-0 px-6 py-4 border-r border-black/10 bg-[#FDC700] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.2)]">LABA BERSIH</td>
                                <td :colspan="totalPenjualanColumns + 4" class="bg-[#FDC700] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.2)]"></td>
                                <td class="px-6 py-5 text-right bg-[#FDC700] font-black text-xl tracking-tighter shadow-[2px_0_5px_-2px_rgba(0,0,0,0.2)]">
                                    RP {{ formatNumber(labaBersihTotal) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div v-if="reportData.length === 0" class="p-20 text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Tidak ada data transaksi pada periode ini</p>
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

.header-row-1 th {
    top: 0;
    z-index: 40;
}

.header-row-2 th {
    top: 45px; 
    z-index: 39;
}

.header-row-3 th {
    top: 77px; 
    z-index: 38;
}

.sticky-column {
    position: sticky;
    left: 0;
    z-index: 20;
}

thead th.sticky-column {
    z-index: 60;
}

/* Footer Sticky */
tfoot td {
    position: sticky;
    bottom: 0;
    z-index: 45;
}

tfoot td.sticky-column {
    z-index: 61;
}

/* Custom Scrollbar */
.overflow-x-auto::-webkit-scrollbar { height: 8px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f8fafc; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.overflow-x-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>