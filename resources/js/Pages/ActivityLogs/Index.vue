<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import TransactionDetailModal from '@/Pages/TransactionsDetails/Index.vue';

const props = defineProps({
    logs: Object,
    filters: Object
});

// State untuk Modal Detail Transaksi
const showDetailModal = ref(false);
const selectedTransactionId = ref(null);
const detailModalRef = ref(null);

// Menambahkan sortable: true pada kolom yang relevan
const columns = [
    { label: 'Waktu Aktivitas', key: 'created_at', sortable: true },
    { label: 'Eksekutor', key: 'user_name', sortable: true },
    { label: 'Tindakan', key: 'action', sortable: true },
    { label: 'Referensi', key: 'reference_type', sortable: true },
    { label: 'Detail Keterangan', key: 'description', sortable: false },
];

const getActionTheme = (action) => {
    const act = action.toUpperCase();
    if (act.includes('CREATE')) return 'text-emerald-700 bg-emerald-50 border-emerald-200';
    if (act.includes('UPDATE')) return 'text-amber-700 bg-amber-50 border-amber-200';
    if (act.includes('DELETE')) return 'text-red-700 bg-red-50 border-red-200';
    return 'text-gray-600 bg-gray-50 border-gray-200';
};

// Fungsi membuka detail transaksi
const openTransactionDetail = (id) => {
    selectedTransactionId.value = id;
    showDetailModal.value = true;
    
    // Delay agar ref komponen tersedia sebelum fetch dijalankan
    setTimeout(() => {
        if (detailModalRef.value && typeof detailModalRef.value.fetchDetails === 'function') {
            detailModalRef.value.fetchDetails();
        }
    }, 100);
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
                <template #created_at="{ value }">
                    <span class="text-[11px] font-bold text-gray-500 font-mono tracking-tighter">{{ value }}</span>
                </template>

                <template #user_name="{ value }">
                    <span class="font-black text-gray-700 text-xs uppercase tracking-tight">👤 {{ value }}</span>
                </template>

                <template #action="{ value }">
                    <span :class="['px-2 py-1 rounded border text-[10px] font-black italic uppercase shadow-sm', getActionTheme(value)]">
                        {{ value }}
                    </span>
                </template>

                <template #reference_type="{ row }">
                    <button 
                        v-if="row.reference_type === 'transactions'"
                        @click="openTransactionDetail(row.reference_id)"
                        class="flex flex-col gap-0.5 text-left group hover:opacity-80 transition-all focus:outline-none"
                    >
                        <span class="text-[10px] font-extrabold text-gray-400 uppercase leading-none group-hover:text-blue-500">
                            {{ row.reference_type }}
                        </span>
                        <span class="text-[9px] text-blue-600 font-bold bg-blue-50 w-fit px-1 rounded border border-blue-200 group-hover:bg-blue-600 group-hover:text-white">
                            LIHAT ID #{{ row.reference_id }}
                        </span>
                    </button>
                    
                    <div v-else class="flex flex-col gap-0.5">
                        <span class="text-[10px] font-extrabold text-gray-400 uppercase leading-none">{{ row.reference_type }}</span>
                        <span class="text-[9px] text-gray-500 font-bold bg-gray-50 w-fit px-1 rounded border border-gray-200 italic">ID #{{ row.reference_id }}</span>
                    </div>
                </template>

                <template #description="{ value }">
                    <span class="text-xs text-gray-600 font-medium">{{ value }}</span>
                </template>

                <template #actions>
                    <div class="hidden"></div>
                </template>
            </DataTable>
        </div>

        <TransactionDetailModal 
            ref="detailModalRef"
            :show="showDetailModal"
            :transaction-id="selectedTransactionId"
            @close="showDetailModal = false"
        />
    </AuthenticatedLayout>
</template>