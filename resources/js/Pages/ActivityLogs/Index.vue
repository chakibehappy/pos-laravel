<script setup>
import { ref, reactive, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import UpdModalTransaction from '@/Components/Audit/UpdModalTransaction.vue';
import UpdModalMaster from '@/Components/Audit/UpdModalMaster.vue';
import TransactionDetailModal from '@/Pages/TransactionsDetails/Index.vue';

const props = defineProps({
    logs: Object, 
    stores: Array,      // Data untuk select toko
    posUsers: Array,    // Data untuk select eksekutor (Hanya yang memiliki riwayat log)
    filters: Object
});

// --- STATE MANAGEMENT ---
const showLogModal = ref(false);    
const showMasterModal = ref(false); 
const selectedLog = ref(null);      
const showDetailModal = ref(false);
const selectedTransactionId = ref(null);

// State untuk Filter Dinamis
const filterState = reactive({
    search: props.filters?.search || '',
    store_id: props.filters?.store_id || '',
    pos_user_id: props.filters?.pos_user_id || '', 
    action: props.filters?.action || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const columns = [
    { label: 'Waktu Aktivitas', key: 'created_at', sortable: true },
    { label: 'Store', key: 'store_name', sortable: true },
    { label: 'Eksekutor', key: 'user_name', sortable: true },
    { label: 'Tindakan', key: 'action', sortable: true },
    { label: 'Referensi', key: 'reference_type', sortable: true },
    { label: 'Detail Keterangan', key: 'description', sortable: false },
];

// Otomatis reload data saat filter berubah dengan debounce 500ms
watch(filterState, debounce(() => {
    router.get(route('activity-logs.index'), filterState, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, 500));

/**
 * LOGIKA TEMA WARNA (UI Feedback)
 */
const getActionTheme = (action) => {
    const act = (action || '').toUpperCase();
    if (act.includes('CREATE') || act.includes('RESTOCK')) return 'text-emerald-700 bg-emerald-50 border-emerald-200';
    if (act.includes('UPDATE') || act.includes('ADJUSTMENT') || act.includes('SALE')) return 'text-amber-700 bg-amber-50 border-amber-200';
    if (act.includes('DELETE') || act.includes('VOID')) return 'text-red-700 bg-red-50 border-red-200';
    if (act.includes('APPROVE') || act.includes('RESTORE') || act.includes('REJECT')) return 'text-blue-700 bg-blue-50 border-blue-200';
    if (act.includes('LOGIN') || act.includes('LOGOUT')) return 'text-indigo-700 bg-indigo-50 border-indigo-200';
    return 'text-gray-600 bg-gray-50 border-gray-200';
};

const openActivityDetail = (row) => {
    const action = (row.action || '').toUpperCase();
    const refType = (row.reference_type || '').toLowerCase();
    
    if (action.includes('LOGIN') || action.includes('LOGOUT')) return;

    selectedLog.value = row;
    if (refType === 'transactions') {
        showLogModal.value = true;
    } else {
        showMasterModal.value = true;
    }
};
</script>

<template>
    <Head title="Riwayat Aktivitas" />

    <AuthenticatedLayout>
        <div class="p-8">
            <DataTable 
                title="Riwayat Aktivitas"
                :resource="logs" 
                :columns="columns"
                :filters="filters"
                :showAddButton="false" 
                route-name="activity-logs.index" 
                :initial-search="filters?.search || ''"
            >
                <template #extra-filters>
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="w-48">
                            <SearchableSelect 
                                v-model="filterState.store_id"
                                :options="stores"
                                label="Lokasi Toko"
                                placeholder="Semua Toko"
                            />
                        </div>

                        <div class="w-48">
                            <SearchableSelect 
                                v-model="filterState.pos_user_id"
                                :options="posUsers"
                                label="Eksekutor"
                                placeholder="Semua Eksekutor"
                            />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Mulai Tanggal</label>
                            <input type="date" v-model="filterState.start_date" class="border border-gray-300 rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 shadow-sm" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sampai Tanggal</label>
                            <input type="date" v-model="filterState.end_date" class="border border-gray-300 rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 shadow-sm" />
                        </div>
                    </div>
                </template>

                <template #created_at="{ value, row }">
                    <div @click="openActivityDetail(row)" class="cursor-pointer py-2 px-1 rounded hover:bg-gray-50 transition-colors">
                        <span class="text-[11px] font-bold text-gray-500 font-mono tracking-tighter">{{ value }}</span>
                    </div>
                </template>

                <template #store_name="{ value, row }">
                    <div @click="openActivityDetail(row)" class="cursor-pointer py-2">
                        <span 
                            :class="[
                                'font-bold text-xs uppercase',
                                value === 'Global' ? 'text-gray-400' : 'text-blue-600'
                            ]"
                        >
                            {{ value }}
                        </span>
                    </div>
                </template>

                <template #user_name="{ value, row }">
                    <div @click="openActivityDetail(row)" class="cursor-pointer py-2">
                        <span class="font-black text-gray-700 text-xs uppercase tracking-tight flex items-center gap-2">
                            <span class="bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded text-[9px] border border-gray-200">ID:{{ row.created_by }}</span>
                            {{ value }}
                        </span>
                    </div>
                </template>

                <template #action="{ value, row }">
                    <div @click="openActivityDetail(row)" class="cursor-pointer py-2">
                        <span :class="['px-2 py-1 rounded border text-[10px] font-black italic uppercase shadow-sm', getActionTheme(value)]">
                            {{ value }}
                        </span>
                    </div>
                </template>

                <template #reference_type="{ row }">
                    <div 
                        @click="openActivityDetail(row)" 
                        :class="[
                            'py-2 flex flex-col gap-0.5 transition-all px-1 rounded',
                            !row.action.toUpperCase().includes('LOGIN') ? 'cursor-pointer group hover:bg-blue-50' : 'cursor-default opacity-50'
                        ]"
                    >
                        <span class="text-[10px] font-extrabold text-gray-400 uppercase leading-none group-hover:text-blue-600">
                            {{ row.reference_type }}
                        </span>
                        <span v-if="!row.action.toUpperCase().includes('LOGIN')" class="text-[9px] font-bold text-blue-600 italic">
                            Klik untuk detail audit
                        </span>
                    </div>
                </template>

                <template #description="{ value, row }">
                    <div @click="openActivityDetail(row)" class="cursor-pointer py-2">
                        <span class="text-xs text-gray-600 font-medium line-clamp-1 hover:line-clamp-none">{{ value }}</span>
                    </div>
                </template>

                <template #actions>
                    <div class="hidden"></div>
                </template>
            </DataTable>
        </div>

        <UpdModalTransaction :show="showLogModal" :logData="selectedLog" @close="showLogModal = false" />
        <UpdModalMaster :show="showMasterModal" :logData="selectedLog" @close="showMasterModal = false" />
        <TransactionDetailModal :show="showDetailModal" :transaction-id="selectedTransactionId" @close="showDetailModal = false" />

    </AuthenticatedLayout>
</template>