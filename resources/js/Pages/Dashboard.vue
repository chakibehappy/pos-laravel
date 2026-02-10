<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage, Head } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue';
import Chart from 'chart.js/auto';

const page = usePage()
const user = page.props.auth?.user
const canvas = ref(null);

onMounted(() => {
    new Chart(canvas.value, {
        type: 'bar',
        data: {
            labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
            datasets: [{
                label: 'Penjualan Mingguan (Rp)',
                data: [450000, 590000, 800000, 810000, 560000, 950000, 1200000],
                backgroundColor: '#111827', // Dark Slate
                hoverBackgroundColor: '#FACC15', // Yellow on hover
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
                    ticks: { font: { size: 10, weight: '600' } }
                },
                x: { 
                    grid: { display: false },
                    ticks: { font: { size: 10, weight: '600' } }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <div class="p-8 space-y-8">
            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 bg-yellow-400 rounded-2xl shadow-sm border border-yellow-500/20 group hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-black uppercase text-yellow-900 tracking-wider">Pendapatan Hari Ini</p>
                    <p class="text-3xl font-bold mt-2 text-black">Rp 1.200.000</p>
                </div>

                <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Total Produk</p>
                    <p class="text-3xl font-bold mt-2 text-gray-900">142</p>
                </div>

                <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Staff Aktif</p>
                    <p class="text-3xl font-bold mt-2 text-gray-900">8</p>
                </div>
            </div>

            <div class="p-8 bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-sm font-black uppercase tracking-widest text-gray-900 italic">Analisis Penjualan Mingguan</h2>
                    <div class="flex gap-2">
                        <div class="w-3 h-3 bg-black rounded-full"></div>
                        <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                    </div>
                </div>
                <div class="h-[320px]">
                    <canvas ref="canvas"></canvas>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>