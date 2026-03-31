<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import TransactionDetailModal from '@/Pages/TransactionsDetails/Index.vue';
import UpdModalTransaction from '@/Components/Audit/UpdModalTransaction.vue';
import UpdModalMaster from '@/Components/Audit/UpdModalMaster.vue'; // 1. Import Komponen Baru

const props = defineProps({
    logs: Object,
    filters: Object
});

// State Modal Transaksi (Lama - Struk)
const showDetailModal = ref(false);
const selectedTransactionId = ref(null);
const detailModalRef = ref(null);

// State Modal Audit (Baru)
const showLogModal = ref(false);    // Untuk Update Transaksi
const showMasterModal = ref(false); // Untuk Update Master Data (User/Produk/dll)
const selectedLog = ref(null);

const columns = [
    { label: 'Waktu Aktivitas', key: 'created_at', sortable: true },
    { label: 'Eksekutor', key: 'user_name', sortable: true },
    { label: 'Tindakan', key: 'action', sortable: true },
    { label: 'Referensi', key: 'reference_type', sortable: true },
    { label: 'Detail Keterangan', key: 'description', sortable: false },
];

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
    const refType = (row.reference_type || '').toLowerCase(); // Normalisasi string
    
    // 1. Abaikan jika Login/Logout
    if (action.includes('LOGIN') || action.includes('LOGOUT')) return;

    // 2. Tentukan apakah ini tindakan update yang butuh perbandingan data
    const isUpdateAction = action.includes('UPDATE') || 
                           action.includes('ADJUSTMENT') || 
                           action.includes('VOID');

    // 3. Logika Penentuan Modal
    if (refType === 'transactions') {
        if (!isUpdateAction || action.includes('REJECT')) {
            // Buka Struk/Invoice Original
            selectedTransactionId.value = row.reference_id;
            showDetailModal.value = true;
            
            setTimeout(() => {
                if (detailModalRef.value && typeof detailModalRef.value.fetchDetails === 'function') {
                    detailModalRef.value.fetchDetails();
                }
            }, 100);
        } else {
            // Buka Audit Update Transaksi (Perbandingan Item Tabel)
            selectedLog.value = row;
            showLogModal.value = true;
        }
    } 
    else {
        // 4. Selain transaksi (User, Supplier, Produk, dll)
        // Gunakan Modal Perbandingan Field (Master)
        selectedLog.value = row;
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
                <template #created_at="{ value, row }">
                    <div @click="openActivityDetail(row)" class="cursor-pointer py-2 px-1 rounded hover:bg-gray-50 transition-colors">
                        <span class="text-[11px] font-bold text-gray-500 font-mono tracking-tighter">{{ value }}</span>
                    </div>
                </template>

                <template #user_name="{ value, row }">
                    <div @click="openActivityDetail(row)" class="cursor-pointer py-2">
                        <span class="font-black text-gray-700 text-xs uppercase tracking-tight">👤 {{ value }}</span>
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
                            Klik untuk detail
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

        <!-- Modal 1: Detail Struk Transaksi -->
        <TransactionDetailModal 
            ref="detailModalRef"
            :show="showDetailModal"
            :transaction-id="selectedTransactionId"
            @close="showDetailModal = false"
        />
        
        <!-- Modal 2: Audit Update Transaksi (Tabel Item) -->
        <UpdModalTransaction 
            :show="showLogModal" 
            :logData="selectedLog" 
            @close="showLogModal = false" 
        />

        <!-- Modal 3: Audit Update Master Data (Field Comparison) -->
        <UpdModalMaster 
            :show="showMasterModal" 
            :logData="selectedLog" 
            @close="showMasterModal = false" 
        />

    </AuthenticatedLayout>
</template>