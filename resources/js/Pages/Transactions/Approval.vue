<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
// Menyesuaikan path sesuai lokasi yang Anda berikan
import TransactionDetailModal from '@/Pages/TransactionsDetails/Index.vue';

const props = defineProps({
    requests: Object,
    filters: Object // Tambahkan props filters untuk menangkap state dari backend
});

// State untuk Modal Detail
const showDetailModal = ref(false);
const selectedTransactionId = ref(null);
const detailModalRef = ref(null);

const openDetail = (id) => {
    selectedTransactionId.value = id;
    showDetailModal.value = true;
    // Delay agar ref komponen tersedia sebelum fetch dijalankan
    setTimeout(() => {
        if (detailModalRef.value && typeof detailModalRef.value.fetchDetails === 'function') {
            detailModalRef.value.fetchDetails();
        }
    }, 100);
};

const handleAction = (id, action) => {
    const message = action === 'approve' 
        ? 'Apakah Anda yakin ingin MENYETUJUI penghapusan data ini?' 
        : 'Apakah Anda yakin ingin MENOLAK permintaan hapus ini?';

    if (confirm(message)) {
        var url = route('transactions.approve-request', id)
        router.post(url, {
            action: action
        }, {
            onSuccess: () => alert('Berhasil diproses'),
            preserveScroll: true
        });
    }
};

// Aktifkan sortable: true pada kolom yang diinginkan
const columns = [
    { label: 'TOKO', key: 'store_id', sortable: true }, // Menggunakan key ID agar mudah di-sort di DB
    { label: 'OPERATOR', key: 'requested_by', sortable: true },
    { label: 'DETAIL', key: 'transaction_details', sortable: false },
    { label: 'ALASAN', key: 'delete_reason', sortable: true },
    { label: 'WAKTU', key: 'created_at', sortable: true }, // Tambahan kolom waktu biasanya penting di sini
];
</script>

<template>
    <Head title="Persetujuan Hapus" />

    <AuthenticatedLayout>
        <div class="p-4">
            <DataTable 
                title="Permintaan Penghapusan"
                :resource="requests" 
                :columns="columns"
                :filters="filters"
                :showAddButton="false"
                route-name="transactions.delete-requests" 
                :initial-search="filters?.search || ''"
            >
                <template #store_id="{ row }">
                    <span class="font-bold text-gray-800">{{ row.store?.name || '-' }}</span>
                </template>

                <template #requested_by="{ row }">
                    <span class="text-xs font-medium text-gray-600">{{ row.requester?.name || '-' }}</span>
                </template>

                <template #transaction_details="{ row }">
                    <button 
                        @click="openDetail(row.id)"
                        class="text-left group transition-all focus:outline-none block w-full"
                    >
                        <span class="text-xs text-blue-600 font-bold group-hover:text-blue-800 underline decoration-dotted block">
                            {{ row.details?.length || 0 }} Produk
                        </span>
                        <p class="text-[10px] text-gray-400 line-clamp-1 leading-tight mt-0.5">
                            {{ 
                                row.details
                                    ?.map(d => d.product_name)
                                    ?.filter(name => name && name.trim() !== '') 
                                    ?.join(', ') || '-'
                            }}
                        </p>
                    </button>
                </template>

                <template #created_at="{ value }">
                    <span class="text-[10px] text-gray-500 font-mono">{{ value }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex gap-2 justify-end">
                        <button 
                            @click="handleAction(row.id, 'reject')"
                            class="px-3 py-1 bg-gray-100 border border-black text-xs font-bold hover:bg-gray-200"
                        >
                            TOLAK
                        </button>
                        <button 
                            @click="handleAction(row.id, 'approve')"
                            class="px-3 py-1 bg-red-600 text-white border border-black text-xs font-bold hover:bg-red-700 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:shadow-none"
                        >
                            HAPUS
                        </button>
                    </div>
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