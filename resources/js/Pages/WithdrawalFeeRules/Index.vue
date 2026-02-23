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

// Konfigurasi kolom dengan properti sortable
const columns = [
    { label: 'Min Limit', key: 'min_limit', sortable: true }, 
    { label: 'Max Limit', key: 'max_limit', sortable: true },
    { label: 'Biaya (Fee)', key: 'fee', sortable: true },
    { label: 'Dibuat Oleh', key: 'created_by', sortable: true }
];

const search = ref(props.filters.search || '');
const errorMessage = ref('');

// Sync pencarian dengan server
watch(search, debounce((value) => {
    router.get(
        route('withdrawal-fee-rules.index'), 
        { 
            search: value,
            sort: props.filters.sort,
            direction: props.filters.direction
        }, 
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
    if (confirm('Apakah Anda yakin ingin menghapus/mengarsipkan aturan ini?')) {
        router.delete(route('withdrawal-fee-rules.destroy', id));
    }
};

const formatCurrency = (value) => new Intl.NumberFormat('id-ID').format(value);
</script>

<template>
    <Head title="Aturan Biaya Tarik Tunai" />

    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="showForm" class="mb-8 bg-white rounded-xl border border-gray-200 shadow-md overflow-hidden animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                    <h2 class="text-xs font-black text-gray-700 uppercase tracking-widest">
                        {{ form.id ? '✏️ Edit Aturan Biaya' : '➕ Tambah Aturan Baru' }}
                    </h2>
                    <button @click="showForm = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-white border border-gray-100 text-gray-400 hover:text-red-500 hover:shadow-sm transition-all">✕</button>
                </div>

                <div class="p-8">
                    <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg flex items-center gap-3 animate-pulse">
                        <span class="text-red-500 font-bold">⚠️</span>
                        <p class="text-[10px] font-black text-red-700 uppercase tracking-tight">{{ errorMessage }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Minimal Limit</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-bold">Rp</span>
                                <input v-model="form.min_limit" type="number" 
                                    class="w-full border border-gray-200 rounded-xl pl-10 p-3 text-sm font-black focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Maksimal Limit</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-bold">Rp</span>
                                <input v-model="form.max_limit" type="number" 
                                    class="w-full border border-gray-200 rounded-xl pl-10 p-3 text-sm font-black focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black text-blue-600 uppercase tracking-widest">Biaya (Fee)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-400 text-xs font-bold">Rp</span>
                                <input v-model="form.fee" type="number" 
                                    class="w-full border border-blue-100 bg-blue-50/30 rounded-xl pl-10 p-3 text-sm font-black text-blue-700 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-10 flex gap-3 border-t border-gray-50 pt-8">
                        <button @click="submit" :disabled="form.processing" 
                            class="bg-blue-600 text-white px-10 py-3 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/20 active:scale-95 disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Data' }}
                        </button>
                        <button @click="showForm = false" 
                            class="bg-white border border-gray-200 text-gray-500 px-10 py-3 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-gray-50 transition-all">
                            Batal
                        </button>
                    </div>
                </div>
            </div>

            <DataTable 
                title="Aturan Biaya Tarik Tunai"
                :resource="resource" 
                :columns="columns"
                :filters="filters"
                :showAddButton="!showForm"
                routeName="withdrawal-fee-rules.index" 
                :initialSearch="filters.search"
                @on-add="openCreate" 
            >
                <template #min_limit="{ value }">
                    <span class="text-gray-400 text-[11px] font-bold">Rp {{ formatCurrency(value) }}</span>
                </template>
                
                <template #max_limit="{ value }">
                    <span class="font-black text-gray-800 text-[11px]">Rp {{ formatCurrency(value) }}</span>
                </template>
                
                <template #fee="{ value }">
                    <div class="inline-flex items-center px-3 py-1 rounded-lg bg-blue-50 border border-blue-100">
                        <span class="text-blue-600 font-black text-[11px]">Rp {{ formatCurrency(value) }}</span>
                    </div>
                </template>

                <template #created_by="{ row }">
                    <span class="text-[10px] font-black uppercase tracking-tighter text-gray-500 italic">
                        BY {{ row.creator?.name || 'SYSTEM' }}
                    </span>
                </template>

                <template #actions="{ row }">
                    <div class="flex flex-row gap-5 justify-end items-center px-2">
                        <button @click="openEdit(row)" class="text-gray-300 hover:text-blue-600 transition-all transform hover:scale-125" title="Edit">
                            ✏️
                        </button>
                        <button @click="destroy(row.id)" class="text-gray-300 hover:text-red-600 transition-all transform hover:scale-125" title="Hapus">
                            ❌
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>