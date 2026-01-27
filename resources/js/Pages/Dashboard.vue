<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage } from '@inertiajs/vue3'
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
                data: [450, 590, 800, 810, 560, 950, 1200],
                backgroundColor: '#000000', // Black bars
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="p-8 border-2 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)]">
                <h1 class="text-4xl font-black uppercase italic tracking-tighter">
                    Selamat Datang, {{ user?.name }}!
                </h1>
                <p class="font-bold text-gray-500 mt-2 uppercase text-xs">
                    Status sistem: <span class="text-green-600 underline">Terkoneksi ke VPS</span>
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 border-2 border-black bg-yellow-400 shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)]">
                    <p class="text-xs font-black uppercase">Pendapatan Hari Ini</p>
                    <p class="text-3xl font-black mt-2">Rp.1,200.00</p>
                </div>
                <div class="p-6 border-2 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)]">
                    <p class="text-xs font-black uppercase">Total Produk</p>
                    <p class="text-3xl font-black mt-2">142</p>
                </div>
                <div class="p-6 border-2 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)]">
                    <p class="text-xs font-black uppercase">Staff Aktif</p>
                    <p class="text-3xl font-black mt-2">8</p>
                </div>
            </div>

            <div class="p-8 border-2 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)]">
                <h2 class="text-xl font-black uppercase mb-6 tracking-tight">Penjualan Mingguan</h2>
                <div class="h-[300px]">
                    <canvas ref="canvas"></canvas>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>