<script setup>
import { ref, watch, reactive, computed } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    expenses: { 
        type: Object, 
        default: () => ({ data: [] }) 
    },
    stores: Array,
    expenseCategories: Array,
    filters: Object
});

// --- FILTER STATE ---
const filterState = reactive({
    type: props.filters?.type || '',
    category: props.filters?.category || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    search: props.filters?.search || ''
});

// --- LOGIKA HITUNG TOTAL UNTUK FOOTER ---
const totalAmount = computed(() => {
    return props.expenses.data.reduce((acc, curr) => acc + parseFloat(curr.amount || 0), 0);
});

const formatNumber = (val) => new Intl.NumberFormat('id-ID').format(val || 0);

// --- UPDATE FILTERS DENGAN DEBOUNCE ---
const updateFilters = debounce(() => {
    router.get(route('report-expense.index'), filterState, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300);

watch(() => filterState, updateFilters, { deep: true });

const handleExport = () => {
    const params = new URLSearchParams(filterState).toString();
    window.location.href = route('report-expense.export') + '?' + params;
};
</script>

<template>
    <Head title="Laporan Biaya Operasional" />

    <AuthenticatedLayout page-title="Laporan Biaya Operasional" page-subtitle="Maar Company">
        <div class="p-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="p-8 border-b border-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                        <div class="hidden md:block pointer-events-auto">
                            <h2 class="text-xl font-black text-gray-800 uppercase tracking-tight">Laporan Biaya Operasional</h2>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Status: Data Pengeluaran Terverifikasi</p>
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
                    </div>

                    <div class="flex flex-wrap gap-6 items-end">
                        <div class="w-64">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Jenis Pengeluaran</label>
                            <select 
                                v-model="filterState.type" 
                                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-gray-400 outline-none transition-all uppercase appearance-none bg-white cursor-pointer"
                                style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%236b7280%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1rem;"
                            >
                                <option value="">-- SEMUA JENIS --</option>
                                <option value="global">PENGELUARAN GLOBAL</option>
                                <option value="store">PENGELUARAN TOKO</option>
                            </select>
                        </div>

                        <div class="w-64">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Kategori Biaya</label>
                            <select 
                                v-model="filterState.category" 
                                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-gray-400 outline-none transition-all uppercase appearance-none bg-white cursor-pointer"
                                style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%236b7280%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1rem;"
                            >
                                <option value="">-- SEMUA KATEGORI --</option>
                                <option v-for="cat in expenseCategories" :key="cat" :value="cat">{{ cat.toUpperCase() }}</option>
                            </select>
                        </div>

                        <div class="w-44">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Mulai Tanggal</label>
                            <input 
                                type="date" 
                                v-model="filterState.start_date"
                                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-gray-400 outline-none transition-all"
                            />
                        </div>

                        <div class="w-44">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Sampai Tanggal</label>
                            <input 
                                type="date" 
                                v-model="filterState.end_date"
                                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-gray-400 outline-none transition-all"
                            />
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto overflow-y-auto max-h-[600px] relative">
                    <table class="w-full text-[11px] border-separate border-spacing-0">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="sticky top-0 left-0 z-50 px-6 py-4 text-left uppercase font-black tracking-widest border-b border-r border-gray-200 bg-gray-50 text-gray-500">Kategori Biaya</th>
                                <th class="sticky top-0 z-40 px-6 py-4 text-center uppercase font-black tracking-widest border-b border-gray-200 bg-gray-50 text-gray-500">Tanggal</th>
                                <th class="sticky top-0 z-40 px-6 py-4 text-left uppercase font-black tracking-widest border-b border-gray-200 bg-gray-50 text-gray-500">Deskripsi</th>
                                <th class="sticky top-0 z-40 px-6 py-4 text-right uppercase font-black tracking-widest border-b border-gray-200 bg-gray-50 text-gray-500">Jumlah (Rp)</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(row, index) in expenses.data" :key="index" class="hover:bg-blue-50/30 transition-colors group">
                                <td class="sticky left-0 z-10 px-6 py-4 border-r border-gray-200 bg-white group-hover:bg-blue-50 transition-colors shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    <span class="font-black text-gray-800 uppercase tracking-tight italic">{{ row.category }}</span>
                                </td>
                                <td class="px-6 py-4 text-center text-gray-500 font-bold">{{ row.date }}</td>
                                <td class="px-6 py-4 text-left text-gray-500 font-medium">{{ row.description || '-' }}</td>
                                <td class="px-6 py-4 text-right font-black text-red-600 bg-red-50/5">{{ formatNumber(row.amount) }}</td>
                            </tr>
                        </tbody>

                        <tfoot v-if="expenses.data.length > 0" class="sticky bottom-0 z-50">
                            <tr class="font-black uppercase tracking-widest border-t-2 border-gray-200 text-black">
                                <td colspan="3" class="sticky left-0 px-6 py-5 border-r border-yellow-600 bg-[#FDC700] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] text-right">
                                    TOTAL BIAYA:
                                </td>
                                <td class="px-6 py-5 text-right bg-[#FDC700]">
                                    {{ formatNumber(totalAmount) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div v-if="expenses.data.length === 0" class="p-20 text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest text-italic">Tidak ada data biaya pada periode ini</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
table th, table td {
    white-space: nowrap;
}

.overflow-x-auto::-webkit-scrollbar { height: 8px; width: 8px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f8fafc; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.overflow-x-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.sticky { background-clip: padding-box; }

input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    filter: invert(0.5);
}
</style>