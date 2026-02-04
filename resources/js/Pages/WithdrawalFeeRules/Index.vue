<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import debounce from 'lodash/debounce';

const props = defineProps({ 
    resource: Object,
    filters: Object 
});

const columns = [
    { label: 'Min Limit', key: 'min_limit' }, 
    { label: 'Max Limit', key: 'max_limit' },
    { label: 'Biaya (Fee)', key: 'fee' },
    { label: 'Dibuat Oleh', key: 'creator' }
];

const search = ref(props.filters.search || '');
const errorMessage = ref('');

// Sync pencarian dengan server
watch(search, debounce((value) => {
    router.get(
        route('withdrawal-fee-rules.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 500));

const showForm = ref(false);
const form = useForm({
    id: null,
    min_limit: '',
    max_limit: '',
    fee: '',
});

const openCreate = () => {
    errorMessage.value = '';
    form.reset();
    form.id = null;
    showForm.value = true;
};

const openEdit = (row) => {
    errorMessage.value = '';
    form.clearErrors();
    form.id = row.id;
    form.min_limit = row.min_limit;
    form.max_limit = row.max_limit;
    form.fee = row.fee;
    showForm.value = true;
};

const submit = () => {
    errorMessage.value = '';

    // Alert jika data kosong saat mencoba mengirim
    if (!form.min_limit || !form.max_limit || !form.fee) {
        errorMessage.value = "Semua kolom wajib diisi agar data dapat disimpan!";
        return;
    }

    form.post(route('withdrawal-fee-rules.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const destroy = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus aturan ini?')) {
        router.delete(route('withdrawal-fee-rules.destroy', id));
    }
};

const formatCurrency = (value) => new Intl.NumberFormat('id-ID').format(value);
</script>

<template>
    <Head title="Withdrawal Fee Rules" />

    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="showForm" class="mb-8 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">
                        {{ form.id ? '✏️ Edit Aturan Biaya' : '➕ Tambah Aturan Baru' }}
                    </h2>
                    <button @click="showForm = false" class="text-gray-400 hover:text-red-500 transition-colors">✕</button>
                </div>

                <div class="p-6">
                    <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3 animate-pulse">
                        <span class="text-red-500 font-bold">⚠️</span>
                        <p class="text-xs font-bold text-red-700 uppercase tracking-tight">{{ errorMessage }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Min Limit</label>
                            <input v-model="form.min_limit" type="number" 
                                class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Max Limit</label>
                            <input v-model="form.max_limit" type="number" 
                                class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Biaya (Fee)</label>
                            <input v-model="form.fee" type="number" 
                                class="w-full border border-blue-200 bg-blue-50/30 rounded-lg p-2.5 text-sm font-bold text-blue-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all" />
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3 border-t border-gray-100 pt-6">
                        <button @click="submit" :disabled="form.processing" 
                            class="bg-blue-600 text-white px-8 py-2.5 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-sm active:scale-95 disabled:opacity-50">
                            {{ form.id ? 'Simpan Perubahan' : 'Simpan Aturan' }}
                        </button>
                        <button @click="showForm = false" 
                            class="bg-white border border-gray-300 text-gray-600 px-8 py-2.5 rounded-lg text-xs font-bold uppercase hover:bg-gray-50 transition-all">
                            Batal
                        </button>
                    </div>
                </div>
            </div>

            <DataTable 
                title="Withdrawal Fee Rules"
                :resource="resource" 
                :columns="columns"
                :showAddButton="!showForm"
                routeName="withdrawal-fee-rules.index" 
                :initialSearch="filters.search"
                @on-add="openCreate" 
            >
                <template #min_limit="{ value }">
                    <span class="text-gray-500">Rp {{ formatCurrency(value) }}</span>
                </template>
                <template #max_limit="{ value }">
                    <span class="font-bold text-gray-800">Rp {{ formatCurrency(value) }}</span>
                </template>
                <template #fee="{ value }">
                    <span class="text-blue-600 font-black">Rp {{ formatCurrency(value) }}</span>
                </template>

                <template #creator="{ row }">
                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase bg-gray-100 text-gray-600">
                        👤 {{ row.creator?.name || 'SYSTEM' }}
                    </span>
                </template>

                <template #actions="{ row }">
                    <div class="flex flex-row gap-4 justify-end">
                        <button @click="openEdit(row)" class="text-gray-400 hover:text-blue-600 transition-colors" title="Edit">✏️</button>
                        <button @click="destroy(row.id)" class="text-gray-400 hover:text-red-600 transition-colors" title="Hapus">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>