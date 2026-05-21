<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    stores: { type: Array, default: () => [] },
    storeTypes: { type: Array, default: () => [] },
    filters: Object,
    purchases: { type: Object, default: () => ({ data: [], links: [] }) }
});

const filterState = reactive({
    store_type_id: props.filters?.store_type_id || '',
    store_id: props.filters?.store_id || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    search: props.filters?.search || '',
});

const items = computed(() => props.purchases.data || []);
const selectedPurchase = ref(null);
const isModalOpen = ref(false);

const filteredStores = computed(() => {
    if (!filterState.store_type_id) return props.stores;
    return props.stores.filter(store => store.store_type_id == filterState.store_type_id);
});

watch(() => filterState.store_type_id, () => {
    filterState.store_id = '';
});

const formatCurrency = (val) => new Intl.NumberFormat('id-ID').format(val || 0);

const pageTotal = computed(() => {
    return items.value.reduce((acc, curr) => acc + parseFloat(curr.total || 0), 0);
});

const openDetail = (row) => {
    selectedPurchase.value = row;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedPurchase.value = null;
};

const updateFilters = debounce(() => {
    router.get(route('report-buying.index'), 
    { 
        ...filterState, 
        page: 1 
    }, 
    {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 400);

watch(() => filterState, updateFilters, { deep: true });

const handleExport = () => {
    const params = new URLSearchParams(filterState).toString();
    window.location.href = route('report-buying.export') + '?' + params;
};
</script>

<template>
    <Head title="Laporan Pembelian Barang" />

    <AuthenticatedLayout page-title="Laporan Pembelian Barang" page-subtitle="Maar Company">
        <div class="p-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="p-8 border-b border-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                        <div class="hidden md:block pointer-events-auto">
                            <h2 class="text-xl font-black text-gray-800 uppercase tracking-tight italic">Laporan Pembelian Barang</h2>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Status: Logistik & Inventaris (Update Real-time)</p>
                        </div>
                        
                        <!-- <div class="flex items-center gap-3">
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 text-xs">🔍</span>
                                <input 
                                    v-model="filterState.search"
                                    type="text" 
                                    placeholder="CARI NOMOR FAKTUR / PEMASOK..." 
                                    class="pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-[10px] font-bold uppercase focus:ring-2 focus:ring-gray-400 outline-none w-64 transition-all"
                                />
                            </div>
                            
                            <button 
                                @click="handleExport" 
                                class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 transition-all shadow-md active:scale-95"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Export Excel
                            </button>
                        </div> -->
                    </div>

                    <div class="flex flex-wrap gap-6 items-end">
                        <div class="w-64">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Jenis Usaha</label>
                            <select 
                                v-model="filterState.store_type_id" 
                                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-gray-400 outline-none transition-all uppercase appearance-none bg-white cursor-pointer"
                                style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%236b7280%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1rem;">
                                <option value="">SEMUA JENIS USAHA</option>
                                <option v-for="type in storeTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                            </select>
                        </div>

                        <div class="w-72">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Pilih Cabang</label>
                            <SearchableSelect 
                                v-model="filterState.store_id" 
                                :options="filteredStores" 
                                placeholder="SEMUA CABANG" 
                            />
                        </div>

                        <div class="w-44">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Mulai Tanggal</label>
                            <input type="date" v-model="filterState.start_date" class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-gray-400 outline-none transition-all" />
                        </div>

                        <div class="w-44">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Sampai Tanggal</label>
                            <input type="date" v-model="filterState.end_date" class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-gray-400 outline-none transition-all" />
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto overflow-y-auto max-h-[600px] relative">
                    <table class="w-full text-[11px] border-separate border-spacing-0">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="sticky top-0 left-0 z-50 px-6 py-4 text-left uppercase font-black tracking-widest border-b border-r border-gray-200 bg-gray-50 text-gray-500">Tanggal</th>
                                <th class="sticky top-0 z-40 px-4 py-4 text-left uppercase font-black tracking-widest border-b border-gray-200 bg-gray-50 text-gray-500">ID Pembelian</th>
                                <th class="sticky top-0 z-40 px-4 py-4 text-left uppercase font-black tracking-widest border-b border-gray-200 bg-gray-50 text-gray-500">Toko</th>
                                <th class="sticky top-0 z-40 px-4 py-4 text-left uppercase font-black tracking-widest border-b border-gray-200 bg-gray-50 text-gray-500">Diinput Oleh</th>
                                <th class="sticky top-0 z-40 px-4 py-4 text-right uppercase font-black tracking-widest border-b border-gray-200 bg-gray-50 text-gray-500">Qty</th>
                                <th class="sticky top-0 z-40 px-6 py-4 text-right uppercase font-black tracking-widest border-b border-gray-200 bg-gray-50 text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr 
                                v-for="row in items" 
                                :key="row.id" 
                                @click="openDetail(row)"
                                class="hover:bg-blue-50/50 transition-colors group cursor-pointer"
                            >
                                <td class="sticky left-0 z-10 px-6 py-4 border-r border-gray-200 bg-white group-hover:bg-blue-50 transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)] font-bold text-gray-600">
                                    {{ row.tanggal }}
                                </td>
                                <td class="px-4 py-4 font-black text-blue-600 uppercase">{{ row.nomor_faktur }}</td>
                                <td class="px-4 py-4 font-bold text-gray-800">{{ row.pemasok }}</td>
                                <td class="px-4 py-4 text-gray-500 font-bold uppercase">{{ row.user?.name || row.user_name || '-' }}</td>
                                <td class="px-4 py-4 text-right font-bold text-gray-700">{{ formatCurrency(row.kuantitas) }}</td>
                                <td class="px-6 py-4 text-right font-black text-gray-900 bg-gray-50/30 group-hover:bg-transparent transition-colors">
                                    {{ formatCurrency(row.total) }}
                                </td>
                            </tr>

                            <tr v-if="items.length > 0" class="font-black uppercase tracking-widest border-t-2 border-gray-200 text-black">
                                <td colspan="5" class="sticky left-0 px-6 py-5 border-r border-yellow-600 bg-[#FDC700] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] text-right">
                                    Total Pembelian:
                                </td>
                                <td class="px-6 py-5 text-right bg-[#FDC700]">
                                    Rp {{ formatCurrency(pageTotal) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="purchases.links && purchases.links.length > 3" class="p-8 bg-gray-50 border-t border-gray-100 flex justify-center">
                    <div class="flex flex-wrap gap-1 bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
                        <template v-for="(link, k) in purchases.links" :key="k">
                            <div v-if="link.url === null" 
                                class="px-4 py-2 text-[10px] font-black uppercase text-gray-300 bg-gray-50 border border-gray-100 rounded-lg cursor-not-allowed"
                                v-html="link.label"
                            />
                            <Link v-else
                                :href="link.url"
                                class="px-4 py-2 text-[10px] font-black uppercase border rounded-lg transition-all duration-200"
                                :class="link.active 
                                    ? 'bg-blue-600 text-white border-blue-600 shadow-md scale-105 z-10' 
                                    : 'bg-white text-gray-600 border-gray-200 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200'"
                                v-html="link.label"
                                preserve-scroll
                            />
                        </template>
                    </div>
                </div>

                <div v-if="items.length === 0" class="p-20 text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest italic">Tidak ada data pembelian ditemukan</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closeModal"></div>
        
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xl w-full max-w-xl overflow-hidden transform transition-all relative z-10 animate-[fadeIn_0.2s_ease-out]">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <div>
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Detail Informasi</h3>
                    <p class="text-sm font-black text-blue-600 uppercase mt-0.5">ID: {{ selectedPurchase?.nomor_faktur }}</p>
                </div>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-200/50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="p-6 space-y-5 text-xs font-bold uppercase tracking-wide text-gray-600">
                <div class="flex flex-wrap gap-x-8 gap-y-4 border-b border-gray-100 pb-4">
                    <div>
                        <span class="text-gray-400 font-black tracking-widest text-[10px] block mb-0.5">Tanggal</span>
                        <span class="text-gray-900">{{ selectedPurchase?.tanggal }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 font-black tracking-widest text-[10px] block mb-0.5">Nama Toko</span>
                        <span class="text-gray-900">{{ selectedPurchase?.pemasok }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 font-black tracking-widest text-[10px] block mb-0.5">Diinput Oleh</span>
                        <span class="text-gray-900">{{ selectedPurchase?.user?.name || selectedPurchase?.user_name || '-' }}</span>
                    </div>
                </div>

                <div>
                    <span class="text-gray-400 font-black tracking-widest text-[10px] block mb-2">Rincian Item</span>
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <table class="w-full text-[11px] text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-gray-500 font-black tracking-widest">
                                    <th class="px-4 py-2.5">Deskripsi</th>
                                    <th class="px-4 py-2.5 text-right w-20">Qty</th>
                                    <th class="px-4 py-2.5 text-right w-32">Harga Satuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="(item, index) in selectedPurchase?.items_list" 
                                    :key="index" 
                                    class="text-gray-900 font-bold border-b border-gray-100 last:border-none"
                                >
                                    <td class="px-4 py-3 normal-case font-medium text-gray-600 whitespace-normal break-words">
                                        {{ item.product_name }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-gray-700">
                                        {{ formatCurrency(item.qty) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-500">
                                        Rp {{ formatCurrency(item.buying_price) }}
                                    </td>
                                </tr>
                                <tr v-if="!selectedPurchase?.items_list || selectedPurchase.items_list.length === 0">
                                    <td colspan="3" class="px-4 py-3 text-center text-gray-400 italic">
                                        Tidak ada rincian item.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-3 bg-blue-50/50 -mx-6 -mb-6 p-6 mt-6 border-t border-gray-100">
                    <span class="text-blue-900 font-black tracking-widest text-[10px] flex items-center">Total Pembelian</span>
                    <span class="col-span-2 text-right text-base font-black text-gray-900">
                        Rp {{ formatCurrency(selectedPurchase?.total) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
table th, table td {
    white-space: nowrap;
}

/* Custom Scrollbar */
.overflow-x-auto::-webkit-scrollbar { height: 8px; width: 8px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f8fafc; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.overflow-x-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* Sticky Style */
.sticky { background-clip: padding-box; }

/* Custom Date Indicator */
input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    filter: invert(0.5);
}

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>