<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
// Menyesuaikan path sesuai lokasi yang Anda berikan
import TransactionDetailModal from '@/Pages/TransactionsDetails/Index.vue';

const props = defineProps({
    requests: Object
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
        router.post(`/api/transactions-approval/${id}`, {
            action: action
        }, {
            onSuccess: () => alert('Berhasil diproses'),
            preserveScroll: true
        });
    }
};

const columns = [
    { label: 'TOKO', key: 'store_name' },
    { label: 'OPERATOR', key: 'operator_name' },
    { label: 'DETAIL', key: 'transaction_details' },
    { label: 'ALASAN', key: 'delete_reason' },
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
            >
                <template #store_name="{ row }">
                    {{ row.store?.name || '-' }}
                </template>

                <template #operator_name="{ row }">
                    {{ row.requester?.name || '-' }}
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