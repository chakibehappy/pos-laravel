<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    logData: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['close']);

/**
 * LOGIKA DETEKSI TINDAKAN
 */
const actionType = computed(() => {
    const act = (props.logData?.action || '').toUpperCase();
    if (act.includes('CREATE')) return 'CREATE';
    if (act.includes('DELETE') || act.includes('VOID')) return 'DELETE';
    return 'UPDATE'; // Default untuk update/adjustment
});

/**
 * Helper untuk menghitung total secara manual
 */
const calculateTotal = (data) => {
    const items = data?.details || [];
    if (items.length > 0) {
        return items.reduce((acc, item) => acc + parseFloat(item.subtotal || 0), 0);
    }
    return data?.total || data?.total_bill || data?.grand_total || 0;
};

/**
 * Mapping data Lama (Old)
 */
const oldTransaction = computed(() => {
    const data = props.logData?.old || props.logData?.payload?.old || props.logData?.properties?.old;
    return {
        total: calculateTotal(data),
        items: data?.details || []
    };
});

/**
 * Mapping data Baru (New)
 */
const newTransaction = computed(() => {
    const data = props.logData?.new || props.logData?.payload?.new || props.logData?.properties?.new;
    return {
        total: calculateTotal(data),
        items: data?.details || []
    };
});

/**
 * Mendapatkan Nama Item
 */
const getItemName = (item) => {
    if (item.product?.name) return item.product.name;
    if (item.topup_transaction) {
        return `TOPUP: ${item.topup_transaction.cust_account_number || '-'}`;
    }
    if (item.cash_withdrawal) {
        return `TARIK TUNAI: ${item.cash_withdrawal.customer_name || 'Pelanggan'}`;
    }
    return item.name || 'Item Tidak Diketahui';
};

/**
 * Mengecek apakah item baru ditambahkan atau diubah nilainya
 */
const isChanged = (newItem) => {
    if (actionType.value === 'CREATE') return false; 
    const oldItems = oldTransaction.value.items;
    const newItemName = getItemName(newItem);
    
    const match = oldItems.find(oldItem => getItemName(oldItem) === newItemName);
    
    if (!match) return true;
    return parseFloat(match.subtotal || 0) !== parseFloat(newItem.subtotal || 0) || 
           (match.quantity || match.qty) !== (newItem.quantity || newItem.qty);
};

const close = () => emit('close');
</script>

<template>
    <div v-if="show" 
         class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-sm"
         @click.self="close">
        
        <div class="bg-white w-full max-w-6xl max-h-[95vh] rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-200 flex flex-col animate-in">
            
            <div class="p-6 md:px-10 border-b border-gray-100 flex justify-between items-center bg-white">
                <div>
                    <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">
                        <span class="text-blue-600">Audit</span> 
                        {{ actionType === 'CREATE' ? 'Pencatatan' : (actionType === 'DELETE' ? 'Penghapusan' : 'Perubahan') }} Transaksi
                    </h2>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">
                        ID TRANSAKSI: #{{ logData?.reference_id || logData?.new?.id || logData?.old?.id || '-' }} 
                    </p>
                </div>
                <button @click="close" class="bg-gray-100 hover:bg-red-50 text-gray-400 hover:text-red-500 w-10 h-10 rounded-full transition-all flex items-center justify-center text-2xl">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scrollbar">
                <div :class="['grid gap-8 items-start', actionType === 'UPDATE' ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1 max-w-3xl mx-auto']">
                    
                    <div v-if="actionType !== 'CREATE'" class="flex flex-col h-full">
                        <div class="mb-3 px-2 flex items-center gap-2">
                            <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                            <span class="text-xs font-black uppercase tracking-widest text-gray-500">
                                {{ actionType === 'DELETE' ? 'Data Yang Dihapus' : 'Data Sebelumnya (OLD)' }}
                            </span>
                        </div>
                        <div class="border border-gray-100 rounded-3xl overflow-hidden bg-white shadow-sm ring-1 ring-gray-100 h-full flex flex-col">
                            <div class="overflow-x-auto flex-1">
                                <table class="w-full text-left text-[12px] min-w-[400px]">
                                    <thead class="bg-gray-50 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                        <tr>
                                            <th class="px-6 py-4">Item</th>
                                            <th class="px-6 py-4 text-center">Qty</th>
                                            <th class="px-6 py-4 text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 italic font-bold">
                                        <tr v-for="(item, index) in oldTransaction.items" :key="'old-'+index">
                                            <td class="px-6 py-4 text-gray-700">{{ getItemName(item) }}</td>
                                            <td class="px-6 py-4 text-center font-mono text-gray-700">{{ item.quantity || item.qty }}</td>
                                            <td class="px-6 py-4 text-right font-mono text-gray-700">Rp{{ Number(item.subtotal || 0).toLocaleString() }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="bg-gray-50/80 p-5 border-t border-gray-100 flex justify-between items-center mt-auto">
                                <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Total</span>
                                <span class="font-mono font-bold text-gray-800">Rp{{ Number(oldTransaction.total || 0).toLocaleString() }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="actionType !== 'DELETE'" class="flex flex-col h-full">
                        <div class="mb-3 px-2 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full" style="background-color: #FDC700;"></span>
                            <span class="text-xs font-black uppercase tracking-widest" style="color: #c99e00;">
                                {{ actionType === 'CREATE' ? 'Data Transaksi Baru' : 'Kondisi Terbaru (NEW)' }}
                            </span>
                        </div>
                        <div class="rounded-3xl overflow-hidden bg-white shadow-xl flex flex-col h-full border" style="border-color: #FDC70044; ring: 2px solid #FDC70022;">
                            <div class="overflow-x-auto flex-1">
                                <table class="w-full text-left text-[12px] min-w-[400px]">
                                    <thead class="border-b border-gray-100 text-[10px] font-black text-black uppercase tracking-widest" style="background-color: #FDC70011;">
                                        <tr>
                                            <th class="px-6 py-4">Item</th>
                                            <th class="px-6 py-4 text-center">Qty</th>
                                            <th class="px-6 py-4 text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 font-bold">
                                        <tr v-for="(item, index) in newTransaction.items" :key="'new-'+index">
                                            <td :class="['px-6 py-4 uppercase tracking-tight', (actionType === 'UPDATE' && isChanged(item)) ? 'text-red-600' : 'text-black']">
                                                {{ getItemName(item) }}
                                            </td>
                                            <td :class="['px-6 py-4 text-center font-mono', (actionType === 'UPDATE' && isChanged(item)) ? 'text-red-600' : 'text-black']">
                                                {{ item.quantity || item.qty }}
                                            </td>
                                            <td :class="['px-6 py-4 text-right font-mono', (actionType === 'UPDATE' && isChanged(item)) ? 'text-red-600' : 'text-black']">
                                                Rp{{ Number(item.subtotal || 0).toLocaleString() }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-5 flex justify-between items-center mt-auto" style="background-color: #FDC700;">
                                <span class="text-[10px] font-black uppercase text-black tracking-widest">Total Final</span>
                                <span class="font-mono font-bold text-black text-lg">Rp{{ Number(newTransaction.total || 0).toLocaleString() }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="p-6 md:px-10 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-tight text-center sm:text-left">
                    Audit By: <span class="text-gray-700">{{ logData?.causer?.name || 'System' }}</span> | 
                    Date: <span class="text-gray-700">{{ logData?.created_at || '-' }}</span>
                </div>
                <button @click="close" class="w-full sm:w-auto px-10 py-4 bg-gray-900 text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-black transition-all shadow-lg active:scale-95">
                    Selesai Review
                </button>
            </div>

        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

.animate-in { animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.98) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>