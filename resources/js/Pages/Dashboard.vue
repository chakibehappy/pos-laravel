<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage, Head, router } from '@inertiajs/vue3'
import { onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';

// Menerima props dari DashboardController
const props = defineProps({
    storeTypes: Array,
    filters: Object,
    staffStats: Object,
    totalProductStock: Number,
    stockBreakdown: Array,
    totalRevenue: Number,
    revenueBreakdown: Array,
    salesChart: Object,
});

const page = usePage()
const user = page.props.auth?.user
const canvas = ref(null);
let chartInstance = null;

// State untuk filter - Diinisialisasi dari props
const businessUnit = ref(props.filters?.businessUnit || ''); 
const dateFilter = ref(props.filters?.dateFilter || 'minggu');
const singleDate = ref(props.filters?.singleDate || ''); 
const startDate = ref(props.filters?.startDate || '');  
const endDate = ref(props.filters?.endDate || '');     

/**
 * Sinkronisasi State dengan Props (PENTING untuk Refresh/Back)
 * Ini memastikan jika URL berubah atau halaman di-refresh, 
 * inputan di layar mengikuti data terbaru dari Controller.
 */
watch(() => props.filters, (newFilters) => {
    businessUnit.value = newFilters?.businessUnit || '';
    dateFilter.value = newFilters?.dateFilter || 'minggu';
    singleDate.value = newFilters?.singleDate || '';
    startDate.value = newFilters?.startDate || '';
    endDate.value = newFilters?.endDate || '';
}, { deep: true });

/**
 * Fungsi helper untuk format Mata Uang Rupiah
 */
const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

/**
 * Fungsi untuk mengirim request ke Controller
 */
const applyFilter = () => {
    router.get(route('dashboard'), {
        businessUnit: businessUnit.value,
        dateFilter: dateFilter.value,
        singleDate: singleDate.value,
        startDate: startDate.value,
        endDate: endDate.value,
    }, {
        // preserveState: false memastikan state lokal Vue direset 
        // mengikuti data terbaru (default) dari server saat navigasi.
        preserveState: false, 
        preserveScroll: true,
        only: ['filters', 'staffStats', 'totalProductStock', 'stockBreakdown', 'totalRevenue', 'revenueBreakdown', 'salesChart'], 
    });
};

/**
 * Watcher untuk interaksi filter
 */
watch(businessUnit, (newVal, oldVal) => {
    // Hanya eksekusi jika nilai benar-benar berubah secara manual
    if (newVal !== oldVal) {
        dateFilter.value = 'minggu';
        singleDate.value = '';
        startDate.value = '';
        endDate.value = '';
        applyFilter();
    }
});

watch([dateFilter, singleDate, startDate, endDate], ([newDate], [oldDate]) => {
    // Mencegah trigger ganda saat pergantian unit bisnis
    if (newDate === oldDate) return; 
    applyFilter();
});

/**
 * Watcher untuk update Chart
 */
watch(() => props.salesChart, (newData) => {
    if (chartInstance && newData) {
        chartInstance.data.labels = newData.labels;
        chartInstance.data.datasets[0].data = newData.datasets;
        chartInstance.update();
    }
}, { deep: true });

onMounted(() => {
    chartInstance = new Chart(canvas.value, {
        type: 'bar',
        data: {
            labels: props.salesChart.labels,
            datasets: [{
                label: 'Penjualan (Rp)',
                data: props.salesChart.datasets,
                backgroundColor: '#111827',
                hoverBackgroundColor: '#FACC15',
                borderRadius: 6,
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: '#F3F4F6' },
                    ticks: { 
                        font: { size: 10, weight: '600' },
                        callback: (value) => 'Rp ' + value.toLocaleString('id-ID')
                    }
                },
                x: { 
                    grid: { display: false },
                    ticks: { font: { size: 10, weight: '600' } }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => 'Total: ' + formatRupiah(context.parsed.y)
                    }
                }
            }
        }
    });
});
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <div class="p-8 space-y-8">
            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">
                        Selamat Datang, <span class="text-yellow-500">{{ user?.name }}</span>!
                    </h1>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                            Status sistem: <span class="text-gray-900">Online / Terkoneksi ke VPS</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-2 min-w-[260px]">
                    <div class="flex items-center gap-2 ml-1">
                        <span class="text-[10px] font-black uppercase tracking-wider text-gray-400">Filter Unit Bisnis</span>
                        <div class="h-[1px] flex-1 bg-gray-100"></div>
                    </div>
                    <div class="relative group">
                        <select 
                            v-model="businessUnit"
                            class="appearance-none bg-gray-50 border border-gray-200 text-gray-900 text-xs font-bold rounded-xl focus:ring-yellow-400 focus:border-yellow-400 block w-full p-3.5 pr-10 transition-all cursor-pointer hover:bg-gray-100 outline-none"
                        >
                            <option value="">Semua Jenis Usaha</option>
                            <option v-for="type in props.storeTypes" :key="type.id" :value="type.id">
                                🏢 {{ type.name }}
                            </option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 group-hover:text-yellow-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 bg-yellow-400 rounded-2xl shadow-sm border border-yellow-500/20 group hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-black uppercase text-yellow-900 tracking-wider">
                        {{ businessUnit ? 'Pendapatan Terfilter' : 'Pendapatan hari ini' }}
                    </p>
                    <div class="mt-2">
                        <p class="text-3xl font-bold text-black">{{ formatRupiah(props.totalRevenue) }}</p>
                        <div v-if="!businessUnit" class="mt-3 pt-3 border-t border-yellow-500/30 flex flex-wrap items-center gap-x-3 gap-y-1">
                            <div v-for="item in props.revenueBreakdown" :key="item.name" class="flex items-center gap-1">
                                <span class="text-[9px] font-bold text-yellow-900 uppercase">{{ item.name }}:</span>
                                <span class="text-[10px] font-black text-black">{{ formatRupiah(item.total).replace('Rp', '').trim() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Total Stok Produk</p>
                    <div class="mt-2">
                        <p class="text-3xl font-bold text-gray-900">{{ props.totalProductStock }}</p>
                        <div v-if="!businessUnit" class="mt-3 pt-3 border-t border-gray-50 flex flex-wrap items-center gap-x-3 gap-y-1">
                            <div v-for="item in props.stockBreakdown" :key="item.name" class="flex items-center gap-1">
                                <span class="text-[9px] font-bold text-gray-400 uppercase">{{ item.name }}:</span>
                                <span class="text-[10px] font-black text-gray-700">{{ item.total }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-shadow relative">
                    <div class="flex justify-between items-start">
                        <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider">
                            {{ businessUnit ? 'Staff Aktif Unit' : 'Total Pegawai' }}
                        </p>
                        <span v-if="!businessUnit" class="bg-blue-50 text-blue-600 text-[9px] font-black px-2 py-0.5 rounded-full border border-blue-100">
                            GLOBAL
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-3xl font-bold text-gray-900">{{ props.staffStats.total }}</p>
                        <div class="mt-3 pt-3 border-t border-gray-50 flex flex-wrap items-center gap-x-4 gap-y-1">
                            <div class="flex items-center gap-1">
                                <span class="text-[9px] font-bold text-gray-400 uppercase">Kasir:</span>
                                <span class="text-[10px] font-black text-gray-700">{{ props.staffStats.cashier }}</span>
                            </div>
                            <div v-if="props.staffStats.collector > 0" class="flex items-center gap-1">
                                <span class="text-[9px] font-bold text-gray-400 uppercase">KOL:</span>
                                <span class="text-[10px] font-black text-gray-700">{{ props.staffStats.collector }}</span>
                            </div>
                            <div v-if="!businessUnit" class="flex items-center gap-1">
                                <span class="text-[9px] font-bold text-gray-400 uppercase">ADM:</span>
                                <span class="text-[10px] font-black text-gray-700">{{ props.staffStats.admin }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-8 bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                    <h2 class="text-sm font-black uppercase tracking-widest text-gray-900 italic">Analisis Penjualan</h2>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="relative min-w-[160px]">
                            <select 
                                v-model="dateFilter"
                                class="appearance-none bg-gray-100 border-none text-gray-900 text-[11px] font-bold rounded-lg focus:ring-2 focus:ring-yellow-400 block w-full p-2.5 pr-8 cursor-pointer transition-all hover:bg-gray-200 outline-none"
                            >
                                <option value="minggu">Minggu Ini</option>
                                <option value="bulan">Bulan Ini</option>
                                <option value="tanggal">Per Tanggal</option>
                                <option value="periode">Per Periode</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <div v-if="dateFilter === 'tanggal'" class="animate-in-fade">
                            <input type="date" v-model="singleDate" class="bg-gray-50 border-gray-200 text-[11px] font-bold rounded-lg focus:ring-yellow-400 focus:border-yellow-400 p-2 shadow-sm outline-none">
                        </div>

                        <div v-if="dateFilter === 'periode'" class="flex items-center gap-2 animate-in-fade">
                            <input type="date" v-model="startDate" class="bg-gray-50 border-gray-200 text-[11px] font-bold rounded-lg focus:ring-yellow-400 focus:border-yellow-400 p-2 shadow-sm outline-none">
                            <span class="text-gray-400 font-bold text-[10px]">S/D</span>
                            <input type="date" v-model="endDate" class="bg-gray-50 border-gray-200 text-[11px] font-bold rounded-lg focus:ring-yellow-400 focus:border-yellow-400 p-2 shadow-sm outline-none">
                        </div>
                    </div>
                </div>

                <div class="h-[320px]">
                    <canvas ref="canvas"></canvas>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
select { -webkit-appearance: none; -moz-appearance: none; appearance: none; }
.animate-in-fade { animation: slide-in 0.4s ease-out; }
@keyframes slide-in { 
    from { opacity: 0; transform: translateX(10px); } 
    to { opacity: 1; transform: translateX(0); } 
}
</style>