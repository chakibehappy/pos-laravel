<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    show: Boolean,
    transactionId: [Number, String]
});

const emit = defineEmits(['close']);
const details = ref([]);
const loading = ref(false);

const totalAmount = computed(() => {
    return details.value.reduce((acc, item) => acc + Number(item.subtotal), 0);
});

const fetchDetails = async () => {
    if (!props.transactionId) return;
    loading.value = true;
    details.value = []; 
    try {
        const response = await fetch(`/transaction-details/${props.transactionId}`);
        if (!response.ok) throw new Error('Gagal mengambil data');
        const data = await response.json();
        details.value = data;
    } catch (error) {
        console.error("Error Fetching Details:", error);
    } finally {
        loading.value = false;
    }
};

defineExpose({ fetchDetails });
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/40 backdrop-blur-md p-4">
        <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] animate-in fade-in zoom-in duration-300">
            
            <div class="px-8 pt-8 pb-4 flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-light text-gray-900 tracking-tight">Rincian <span class="font-black">Transaksi</span></h2>
                    <p class="text-[11px] font-medium text-gray-400 uppercase tracking-[0.2em] mt-1">ID Transaksi: #{{ transactionId }}</p>
                </div>
                <button @click="emit('close')" class="p-2 hover:bg-gray-100 rounded-full transition-colors text-gray-400 hover:text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="px-8 py-4 overflow-y-auto flex-grow">
                <div v-if="loading" class="py-20 text-center">
                    <div class="h-8 w-8 border-2 border-gray-100 border-t-black rounded-full animate-spin mx-auto mb-4"></div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Sinkronisasi Data...</span>
                </div>

                <div v-else-if="details.length > 0">
                    <div class="grid grid-cols-12 pb-3 mb-6 border-b border-gray-50 text-[10px] font-black text-gray-300 uppercase tracking-widest">
                        <div class="col-span-4">Deskripsi Produk</div>
                        <div class="col-span-3">Keterangan</div>
                        <div class="col-span-2 text-center">Qty</div>
                        <div class="col-span-3 text-right">Subtotal</div>
                    </div>

                    <div class="space-y-6">
                        <div v-for="item in details" :key="item.id" class="grid grid-cols-12 items-start group">
                            <div class="col-span-4">
                                <h4 class="text-sm font-bold text-gray-800 group-hover:text-blue-600 transition-colors uppercase leading-tight">
                                    {{ item.product_name }}
                                </h4>
                                <div class="mt-1 space-y-0.5">
                                    <p class="text-[10px] text-gray-400 font-medium italic">dibuat oleh: {{ item.admin_name }}</p>
                                    
                                    <p v-if="item.is_edited" class="text-[9px] text-blue-500 font-bold uppercase tracking-tight flex items-center gap-1">
                                        <span class="w-1 h-1 bg-blue-500 rounded-full"></span>
                                        Terakhir Diubah: <span class="text-gray-400 font-normal italic ml-0.5">{{ item.updated_at }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="col-span-3 pr-2 pt-0.5">
                                <div class="text-[11px] leading-tight">
                                    <template v-if="item.type === 'topup'">
                                        <span class="block text-gray-900 font-bold tracking-tight">{{ item.target_number }}</span>
                                        <span class="text-[8px] text-gray-300 uppercase font-black tracking-widest">No. Tujuan</span>
                                    </template>
                                    <template v-else-if="item.type === 'withdrawal'">
                                        <span class="block text-gray-900 font-bold uppercase tracking-tight">{{ item.customer_name }}</span>
                                        <span class="text-[8px] text-gray-300 uppercase font-black tracking-widest">Nama Pelanggan</span>
                                    </template>
                                    <template v-else>
                                        <span class="text-gray-400 font-medium">{{ item.note || '-' }}</span>
                                    </template>
                                </div>
                            </div>

                            <div class="col-span-2 text-center text-xs font-medium text-gray-500 pt-1">
                                {{ item.quantity }}<span class="text-[10px] ml-0.5 text-gray-300 uppercase">x</span>
                            </div>

                            <div class="col-span-3 text-right font-bold text-gray-900 text-sm tracking-tight pt-1">
                                Rp {{ Number(item.subtotal).toLocaleString('id-ID') }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-12 bg-gray-50 rounded-2xl p-6 space-y-3">
                        <div class="flex justify-between text-xs text-gray-500 font-medium">
                            <span>Waktu Input</span>
                            <span>{{ details[0]?.created_at || '-' }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 font-medium pb-3 border-b border-gray-200/50">
                            <span>Jumlah Item</span>
                            <span>{{ details.length }} Unit</span>
                        </div>
                        <div class="flex justify-between items-end pt-2">
                            <span class="text-xs font-black text-gray-900 uppercase">Total Akhir</span>
                            <span class="text-2xl font-black text-gray-900 tracking-tighter italic">
                                Rp {{ totalAmount.toLocaleString('id-ID') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-else class="py-20 text-center text-gray-300 font-bold uppercase text-[10px] tracking-widest">
                    Data tidak ditemukan
                </div>
            </div>

            <div class="px-8 py-6 bg-white border-t border-gray-50 flex justify-between items-center">
                <p class="text-[9px] text-gray-300 font-bold uppercase tracking-widest leading-none max-w-[150px]">
                    Generated by Kitxel Studio
                </p>
                <button @click="emit('close')" class="bg-gray-900 text-white px-8 py-3 rounded-full text-[11px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-gray-200 active:scale-95">
                    Selesai
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #f1f1f1; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #e2e2e2; }

.animate-in {
    animation: zoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes zoomIn {
    from { opacity: 0; transform: scale(0.98) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>