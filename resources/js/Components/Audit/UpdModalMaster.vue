<script setup>
import { computed } from 'vue';

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

// 1. DUMMY DATA FALLBACK
const dummyData = {
    user_name: "SYSTEM",
    created_at: "-",
    properties: {
        old: {},
        new: {}
    }
};

/**
 * LOGIKA PENCARIAN DATA (SMART DETECTION)
 * Mencari data di properties.old, payload.old, atau .old (root)
 */
const activeLog = computed(() => props.logData ? props.logData : dummyData);

const oldData = computed(() => {
    return activeLog.value?.properties?.old || 
           activeLog.value?.payload?.old || 
           activeLog.value?.old || 
           {};
});

const newData = computed(() => {
    return activeLog.value?.properties?.new || 
           activeLog.value?.payload?.new || 
           activeLog.value?.new || 
           {};
});

// LOGIKA DETEKSI AKSI
const isCreateAction = computed(() => Object.keys(oldData.value).length === 0);

const isDeleteAction = computed(() => {
    const statusOld = oldData.value?.status;
    const statusNew = newData.value?.status;
    const action = (activeLog.value?.action || '').toUpperCase();
    
    // Terdeteksi delete jika status berubah ke 2 atau ada keyword DELETE/VOID di action
    return (statusNew == 2 && statusOld != 2) || action.includes('DELETE') || action.includes('VOID');
});

// 2. FORMATTER NILAI
const formatValue = (val) => {
    if (val === undefined || val === null || val === '') return '-';
    
    // Jika nilainya object (seperti nested JSON), ubah jadi string agar tidak error di template
    if (typeof val === 'object') return JSON.stringify(val);
    
    if (!isNaN(val) && typeof val !== 'boolean') {
        return Number(val).toString(); 
    }
    return val;
};

// 3. LOGIKA HIGHLIGHT PERUBAHAN
const isChanged = (key) => {
    if (isCreateAction.value || isDeleteAction.value) return false;

    const valOld = formatValue(oldData.value[key]);
    const valNew = formatValue(newData.value[key]);
    return valOld !== valNew;
};

// 4. MAPPING KEYS UNTUK DITAMPILKAN
const displayKeys = computed(() => {
    const allKeys = [...new Set([...Object.keys(oldData.value), ...Object.keys(newData.value)])];
    
    // Daftar field teknis yang disembunyikan agar audit bersih
    const technicalFields = [
        'id', 'created_at', 'updated_at', 'deleted_at', 
        'created_by', 'details', 'total_price', 'payload', 'properties',
        'status', 'is_active', 'remember_token',
        'delete_requested_by', 'delete_reason', 'admin_approved_by'
    ];
    
    return allKeys.filter(key => {
        if (technicalFields.includes(key)) return false;
        
        const valOld = oldData.value[key];
        const valNew = newData.value[key];
        
        // Sembunyikan jika field tersebut benar-benar kosong di kedua sisi
        const isEmpty = (v) => v === null || v === undefined || v === '' || v === '-';
        return !isEmpty(valOld) || !isEmpty(valNew);
    });
});

const formatKey = (key) => key.replace(/_/g, ' ').toUpperCase();
const close = () => emit('close');
</script>

<template>
    <div v-if="show" 
         class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
         @click.self="close">
        
        <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl overflow-hidden border border-gray-200 animate-in duration-200">
            <div class="p-6">
                
                <div class="flex justify-between items-start border-b border-gray-100 pb-4 mb-8">
                    <div>
                        <h2 class="text-lg font-black text-gray-800 uppercase tracking-tight">
                            <span class="text-blue-600">Audit</span> Perubahan Data
                        </h2>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">
                            {{ isDeleteAction ? 'Data ini telah dihapus / diarsipkan dari sistem' : 'Teks merah miring menunjukkan data yang mengalami perubahan' }}
                        </p>
                    </div>
                    <button @click="close" class="text-gray-400 hover:text-gray-600 text-3xl leading-none transition-colors">&times;</button>
                </div>

                <div class="flex flex-col gap-8 max-h-[65vh] overflow-y-auto pr-2 custom-scrollbar">
                    
                    <div v-if="!isCreateAction" 
                         class="border border-gray-100 rounded-2xl overflow-hidden bg-white shadow-sm ring-1 ring-gray-100">
                        <div class="bg-gray-50 border-b border-gray-100 px-5 py-3">
                            <span class="text-[11px] font-black text-black uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2 h-2 bg-red-500 rounded-full" :class="{'animate-pulse': !isDeleteAction}"></span>
                                {{ isDeleteAction ? 'Data Sebelum Dihapus' : 'Kondisi Sebelumnya (Old)' }}
                            </span>
                        </div>
                        <table class="w-full text-left text-[12px]">
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="key in displayKeys" :key="'old-' + key">
                                    <td class="px-5 py-4 text-gray-400 font-bold uppercase text-[9px] w-1/3 tracking-wider">
                                        {{ formatKey(key) }}
                                    </td>
                                    <td class="px-5 py-4 text-[13px] transition-all duration-300"
                                        :class="isChanged(key) ? 'text-red-600 font-black italic' : 'text-black font-medium'">
                                        {{ formatValue(oldData[key]) }}
                                    </td>
                                </tr>
                                <tr v-if="displayKeys.length === 0">
                                    <td colspan="2" class="px-5 py-8 text-center text-gray-400 italic font-medium">Tidak ada detail data untuk ditampilkan</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="!isDeleteAction" 
                         class="border border-emerald-100 rounded-2xl overflow-hidden bg-white shadow-lg ring-2 ring-emerald-500/10">
                        <div class="bg-emerald-50/70 border-b border-emerald-100 px-5 py-3.5 flex justify-between items-center">
                            <span class="text-[11px] font-black text-black uppercase tracking-widest flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full"></span>
                                Kondisi Terbaru (New)
                            </span>
                        </div>
                        <table class="w-full text-left text-[12px]">
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="key in displayKeys" :key="'new-' + key">
                                    <td class="px-5 py-4 text-gray-400 font-bold uppercase text-[9px] w-1/3 tracking-wider">
                                        {{ formatKey(key) }}
                                    </td>
                                    <td class="px-5 py-4 text-[13px] transition-all duration-300"
                                        :class="isChanged(key) ? 'text-red-600 font-black italic' : 'text-gray-700 font-medium'">
                                        {{ formatValue(newData[key]) }}
                                    </td>
                                </tr>
                                <tr v-if="displayKeys.length === 0">
                                    <td colspan="2" class="px-5 py-8 text-center text-gray-400 italic font-medium">Tidak ada detail data untuk ditampilkan</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-8 flex justify-between items-center pt-4 border-t border-gray-100">
                    <div class="text-[10px] font-bold text-gray-400 uppercase text-left leading-relaxed">
                        Audit By: <span class="text-gray-700">{{ activeLog?.user_name || activeLog?.causer?.name || 'SYSTEM' }}</span> <br>
                        Time: <span class="text-gray-700">{{ activeLog?.created_at || '-' }}</span>
                    </div>
                    <button @click="close" class="px-12 py-3.5 bg-gray-900 text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-black transition-all shadow-md active:scale-95">
                        Selesai Review
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.animate-in { animation: zoomIn 0.2s ease-out forwards; }
@keyframes zoomIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>