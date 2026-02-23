<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import debounce from 'lodash/debounce';

const props = defineProps({ 
    data: Object, 
    filters: Object 
});

// Definisi kolom tabel dengan fitur sortable: true
const columns = [
    { label: 'Nama Sumber Penarikan', key: 'name', sortable: true }, 
    { label: 'Tanggal', key: 'created_at', sortable: true },
    { label: 'Dibuat Oleh', key: 'creator' },
];

const showForm = ref(false); 
const isEditMode = ref(false);
const errorMessage = ref('');
const search = ref(props.filters.search || '');

// Form utama untuk pengiriman data
const form = useForm({
    id: null,
    name: '',     
    items: []     
});

// Entry sementara untuk input satu per satu ke antrian
const singleEntry = ref({
    name: '',
});

// Sync pencarian
watch(search, debounce((value) => {
    router.get(route('withdrawal-source-types.index'), { search: value }, { preserveState: true, replace: true });
}, 500));

const openCreate = () => {
    isEditMode.value = false;
    errorMessage.value = '';
    form.reset();
    form.items = [];
    resetSingleEntry();
    showForm.value = true;
};

const openEdit = (row) => {
    isEditMode.value = true;
    errorMessage.value = '';
    form.clearErrors();
    form.id = row.id;
    // Set data ke singleEntry untuk input v-model
    singleEntry.value = {
        name: row.name,
    };
    showForm.value = true;
};

const resetSingleEntry = () => {
    singleEntry.value = { name: '' };
};

const addToBatch = () => {
    errorMessage.value = '';
    if (!singleEntry.value.name) {
        errorMessage.value = "Nama sumber dana wajib diisi!";
        return;
    }

    // Cek duplikat di antrian
    const isInQueue = form.items.some(item => item.name.toUpperCase() === singleEntry.value.name.toUpperCase());
    if (isInQueue) {
        errorMessage.value = "Nama ini sudah ada di daftar antrian.";
        return;
    }

    form.items.push({ ...singleEntry.value });
    resetSingleEntry();
};

const removeFromBatch = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    errorMessage.value = '';

    if (isEditMode.value) {
        form.name = singleEntry.value.name;
        
        // PERBAIKAN: Menggunakan .patch sesuai dengan rute Laravel yang tersedia
        form.patch(route('withdrawal-source-types.update', form.id), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
            onError: (errors) => {
                if (errors.error) errorMessage.value = errors.error;
            }
        });
    } else {
        if (form.items.length === 0) {
            errorMessage.value = "Antrian masih kosong!";
            return;
        }

        if (confirm(`Simpan ${form.items.length} data dalam antrian ini?`)) {
            form.post(route('withdrawal-source-types.store'), {
                onSuccess: () => {
                    showForm.value = false;
                    form.reset();
                    form.items = [];
                },
            });
        }
    }
};

const destroy = (id) => {
    if (confirm('Arsip sumber dana ini? Data tidak akan muncul di form transaksi namun tetap ada di riwayat laporan.')) {
        router.delete(route('withdrawal-source-types.destroy', id), {
            preserveScroll: true,
            onError: (errors) => {
                if (errors.error) alert(errors.error);
            }
        });
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Master Sumber Penarikan" />

    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="showForm" :class="isEditMode ? 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm' : 'mb-8 p-6 bg-white rounded-xl border border-gray-200 shadow-md relative animate-in fade-in zoom-in duration-200'">
                
                <button @click="showForm = false" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-red-500 hover:text-white transition-all z-10 shadow-sm">✕</button>

                <div :class="isEditMode ? 'bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-100 relative' : 'w-full'">
                    
                    <div v-if="isEditMode" class="p-6 border-b border-gray-100 bg-gray-50 rounded-t-2xl">
                        <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Edit Sumber Penarikan</h2>
                    </div>

                    <div class="px-4 pt-4" v-if="errorMessage">
                        <div class="p-3 bg-red-50 border-l-4 border-red-500 rounded-r-lg flex items-center gap-3">
                            <span class="text-red-500 font-bold">⚠️</span>
                            <p class="text-[11px] font-black text-red-700 uppercase tracking-tight">{{ errorMessage }}</p>
                        </div>
                    </div>

                    <div :class="isEditMode ? 'p-8 grid grid-cols-1 gap-6' : 'grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-200'">
                        
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nama Sumber Dana</label>
                            <input v-model="singleEntry.name" type="text" placeholder="MASUKKAN NAMA (CONTOH: KAS TOKO)..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none uppercase" @keyup.enter="isEditMode ? submit() : addToBatch()" />
                        </div>

                        <div v-if="!isEditMode" class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter text-center">Simpan</label>
                            <button @click="addToBatch" class="bg-black text-white rounded-lg py-2.5 font-bold hover:bg-blue-600 transition-all shadow-sm uppercase text-xs">
                                + Antrian
                            </button>
                        </div>
                    </div>

                    <div v-if="isEditMode" class="p-6 bg-gray-50 border-t border-gray-100 flex gap-3 rounded-b-2xl">
                        <button @click="submit" :disabled="form.processing" class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black uppercase tracking-widest transition-all shadow-lg active:scale-95">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                        <button @click="showForm = false" class="px-6 py-3 bg-white border border-gray-300 rounded-xl font-bold text-gray-600 hover:bg-gray-100 transition-all uppercase text-xs">Batal</button>
                    </div>
                </div>
            </div>

            <div v-if="showForm && !isEditMode && form.items.length > 0" class="mb-8 border-2 border-dashed border-blue-200 rounded-xl overflow-hidden shadow-sm bg-white animate-in fade-in slide-in-from-top-4">
                <div class="bg-blue-50 px-4 py-2 border-b border-blue-100 flex justify-between items-center">
                    <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest">Daftar Antrian Sumber Dana Baru</span>
                    <span class="text-[10px] bg-blue-600 text-white px-2 py-0.5 rounded-full">{{ form.items.length }} Data</span>
                </div>
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-gray-50 border-b border-gray-100 text-[10px] uppercase text-gray-400 font-black">
                        <tr>
                            <th class="p-3">Nama Sumber Dana</th>
                            <th class="p-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(tr, idx) in form.items" :key="idx" class="hover:bg-blue-50/30 transition-colors">
                            <td class="p-3 font-bold uppercase italic text-gray-700">{{ tr.name }}</td>
                            <td class="p-3 text-right">
                                <button @click="removeFromBatch(idx)" class="w-6 h-6 rounded-full bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition-all text-[10px]">✕</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="p-4 bg-white border-t flex justify-end">
                    <button @click="submit" :disabled="form.processing" class="bg-blue-600 text-white px-8 py-2.5 rounded-lg font-black uppercase tracking-tighter hover:bg-blue-700 shadow-sm transition-all active:scale-95">
                        {{ form.processing ? 'Memproses...' : 'Simpan Semua Antrian' }}
                    </button>
                </div>
            </div>

            <DataTable 
                title="Master Sumber Penarikan"
                :resource="data" 
                :columns="columns" 
                :showAddButton="!showForm"
                routeName="withdrawal-source-types.index" 
                :filters="filters"
                :initialSearch="filters.search" 
                @on-add="openCreate" 
            >
                <template #name="{ row }">
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-800 uppercase italic">{{ row.name }}</span>
                        <span class="text-[8px] font-bold text-gray-400 uppercase tracking-widest leading-none mt-1 italic">ID_SOURCE: #WS-TYPE-{{ row.id }}</span>
                    </div>
                </template>
                
                <template #created_at="{ value }">
                    <span class="text-[10px] font-medium text-gray-500 uppercase">{{ formatDate(value) }}</span>
                </template>

                <template #creator="{ row }">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded bg-blue-600 text-white flex items-center justify-center text-[8px] font-bold uppercase shadow-sm">
                            {{ row.creator?.name?.charAt(0) || 'U' }}
                        </div>
                        <span class="text-[10px] font-bold uppercase text-gray-600 tracking-tight">
                            {{ row.creator?.name || 'Admin' }}
                        </span>
                    </div>
                </template>
                
                <template #actions="{ row }">
                    <div class="flex flex-row gap-4 justify-end items-center">
                        <button @click="openEdit(row)" class="text-gray-300 hover:text-blue-600 transition-colors transform hover:scale-125" title="Edit">✏️</button>
                        <button @click="destroy(row.id)" class="text-gray-300 hover:text-red-600 transition-colors transform hover:scale-125" title="Arsip">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>