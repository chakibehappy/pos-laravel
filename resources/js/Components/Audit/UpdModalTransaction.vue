<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    logData: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close']);

// Data Dummy Simulasi
const oldTransaction = {
    total: 150000,
    items: [
        { id: 1, name: 'Semen Padang', qty: 2, price: 70000, subtotal: 140000, changed: true },
        { id: 2, name: 'Paku 5cm', qty: 1, price: 10000, subtotal: 10000, changed: false },
    ]
};

const newTransaction = {
    total: 155000, 
    items: [
        { id: 1, name: 'Semen Padang', qty: 2, price: 72500, subtotal: 145000, changed: true },
        { id: 2, name: 'Paku 5cm', qty: 1, price: 10000, subtotal: 10000, changed: false },
    ]
};

const close = () => {
    emit('close');
};
</script>

<template>
    <div v-if="show" 
         class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
         @click.self="close">
        
        <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl overflow-hidden border border-gray-200 animate-in fade-in zoom-in duration-200">
            <div class="p-6">
                
                <!-- Header -->
                <div class="flex justify-between items-start border-b border-gray-100 pb-4 mb-8">
                    <div>
                        <h2 class="text-lg font-black text-gray-800 uppercase tracking-tight">
                            <span class="text-blue-600">Audit</span> Perubahan Transaksi
                        </h2>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">
                            Perbandingan Data Sebelum dan Sesudah Update
                        </p>
                    </div>
                    <button @click="close" class="text-gray-400 hover:text-gray-600 text-3xl leading-none transition-colors">&times;</button>
                </div>

                <!-- Scroll Area -->
                <div class="flex flex-col gap-8 max-h-[65vh] overflow-y-auto pr-2 custom-scrollbar">
                    
                    <!-- KONDISI SEBELUM (OLD) -->
                    <div class="border border-gray-100 rounded-2xl overflow-hidden bg-white shadow-sm ring-1 ring-gray-100">
                        <div class="bg-gray-50 border-b border-gray-100 px-5 py-3 flex justify-between items-center relative">
                            <span class="text-[11px] font-black text-black uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                                Kondisi Sebelumnya (Old)
                            </span>
                            <span class="text-[10px] font-bold text-gray-400 uppercase italic">Data Lama</span>
                        </div>
                        <table class="w-full text-left text-[12px]">
                            <thead class="bg-gray-50 border-b border-gray-100 text-[10px] font-black text-black uppercase tracking-wider">
                                <tr>
                                    <th class="px-5 py-2.5">Nama Produk</th>
                                    <th class="px-5 py-2.5 text-center">Qty</th>
                                    <th class="px-5 py-2.5 text-right uppercase">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 italic font-medium">
                                <tr v-for="(item, index) in oldTransaction.items" :key="'old-'+index">
                                    <td class="px-5 py-3.5 text-black font-semibold">{{ item.name }}</td>
                                    <td class="px-5 py-3.5 text-center font-black text-black font-mono">{{ item.qty }}</td>
                                    <!-- Warna diubah menjadi text-black -->
                                    <td class="px-5 py-3.5 text-right font-black font-mono text-black">
                                        Rp {{ item.subtotal.toLocaleString() }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="border-t border-gray-100">
                                <tr class="bg-gray-50/50">
                                    <td colspan="2" class="px-5 py-3 text-right text-[11px] font-black uppercase text-black tracking-wider">Total Sebelum</td>
                                    <!-- Warna diubah menjadi text-black -->
                                    <td class="px-5 py-3 text-right text-xs font-black font-mono text-black">Rp {{ oldTransaction.total.toLocaleString() }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- KONDISI TERBARU (NEW) -->
                    <div class="border border-emerald-100 rounded-2xl overflow-hidden bg-white shadow-lg ring-2 ring-emerald-500/10">
                        <div class="bg-emerald-50/70 border-b border-emerald-100 px-5 py-3.5 flex justify-between items-center">
                            <span class="text-[11px] font-black text-black uppercase tracking-widest flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full"></span>
                                Kondisi Terbaru (New)
                            </span>
                        </div>
                        <table class="w-full text-left text-[12px]">
                            <thead class="bg-gray-50 border-b border-gray-100 text-[10px] font-black text-black uppercase tracking-wider">
                                <tr>
                                    <th class="px-5 py-2.5">Nama Produk</th>
                                    <th class="px-5 py-2.5 text-center">Qty</th>
                                    <th class="px-5 py-2.5 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-semibold">
                                <tr v-for="(item, index) in newTransaction.items" :key="'new-'+index">
                                    <td class="px-5 py-4 font-black uppercase tracking-tight" :class="item.changed ? 'text-red-600' : 'text-black'">
                                        {{ item.name }}
                                    </td>
                                    <td class="px-5 py-4 text-center font-black font-mono" :class="item.changed ? 'text-red-600' : 'text-black'">
                                        {{ item.qty }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-black font-mono" :class="item.changed ? 'text-red-600' : 'text-black'">
                                        Rp {{ item.subtotal.toLocaleString() }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="border-t border-emerald-100">
                                <tr class="bg-emerald-50">
                                    <td colspan="2" class="px-5 py-4 text-right text-[11px] font-black uppercase text-black tracking-wider">Grand Total Final</td>
                                    <td class="px-5 py-4 text-right text-base font-black font-mono text-red-600">Rp {{ newTransaction.total.toLocaleString() }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Footer Audit Info -->
                <div class="mt-8 flex justify-between items-center pt-4 border-t border-gray-100">
                    <div class="text-[10px] font-bold text-gray-400 uppercase leading-relaxed">
                        Audit By: {{ logData?.user_name || 'KITXELS' }} <br>
                        Time: {{ logData?.created_at || '31/03/2026 12:01' }}
                    </div>
                    <button @click="close" class="px-12 py-3.5 bg-gray-900 text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-black transition-all shadow-lg active:scale-95">
                        Selesai Review
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.animate-in {
    animation: zoomIn 0.3s ease-out forwards;
}
@keyframes zoomIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>