<script setup>
import { ref } from 'vue'; 
import { useForm, Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    methods: Object, // Menggunakan Object untuk Paginasi
    filters: Object
});

const showForm = ref(false);
const isEditMode = ref(false);
const errorMessage = ref('');

const form = useForm({
    id: null,
    name: '',
    items: [] // Untuk Batch Create
});

const singleEntry = ref({
    name: ''
});

const openCreate = () => {
    isEditMode.value = false;
    errorMessage.value = '';
    form.reset();
    form.items = [];
    singleEntry.value.name = '';
    showForm.value = true;
};

const openEdit = (row) => {
    isEditMode.value = true;
    errorMessage.value = '';
    form.clearErrors();
    form.id = row.id;
    singleEntry.value.name = row.name;
    showForm.value = true;
};

const addToBatch = () => {
    errorMessage.value = ''; 
    if (!singleEntry.value.name) {
        errorMessage.value = "Nama metode tidak boleh kosong!";
        return;
    }

    const isInQueue = form.items.some(item => 
        item.name.toLowerCase() === singleEntry.value.name.trim().toLowerCase()
    );

    if (isInQueue) {
        errorMessage.value = "Metode ini sudah ada di antrian.";
        return;
    }

    form.items.push({ name: singleEntry.value.name.trim() });
    singleEntry.value.name = '';
};

const removeFromBatch = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    errorMessage.value = '';

    if (isEditMode.value) {
        if (!singleEntry.value.name) {
            alert("Nama tidak boleh kosong!");
            return;
        }
        form.name = singleEntry.value.name;
        form.items = [];
    } else {
        if (form.items.length === 0) return;
        form.id = null;
        form.name = '';
    }

    const confirmMsg = isEditMode.value 
        ? "Simpan perubahan metode pembayaran?" 
        : `Simpan ${form.items.length} metode pembayaran baru?`;

    if (confirm(confirmMsg)) {
        form.post(route('payment-methods.store'), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
                singleEntry.value.name = '';
            },
            onError: (err) => {
                if(err.name) errorMessage.value = err.name;
            }
        });
    }
};

const destroy = (row) => {
    if (confirm(`Hapus metode pembayaran "${row.name}"?`)) {
        router.delete(route('payment-methods.destroy', row.id));
    }
};
</script>

<template>
    <Head title="Metode Pembayaran" />

    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="showForm" :class="isEditMode ? 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm' : 'mb-8 p-6 bg-white rounded-xl border border-gray-200 shadow-md relative animate-in fade-in zoom-in duration-200'">
                
                <button @click="showForm = false" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-red-500 hover:text-white transition-all z-10 shadow-sm">✕</button>

                <div :class="isEditMode ? 'bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-visible border border-gray-100 relative' : 'w-full'">
                    
                    <div v-if="isEditMode" class="p-6 border-b border-gray-100 bg-gray-50 rounded-t-2xl">
                        <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Edit Metode Pembayaran</h2>
                    </div>

                    <div class="px-4 pt-4" v-if="errorMessage">
                        <div class="p-3 bg-red-50 border-l-4 border-red-500 rounded-r-lg flex items-center gap-3">
                            <span class="text-red-500 font-bold">⚠️</span>
                            <p class="text-[11px] font-black text-red-700 uppercase tracking-tight">{{ errorMessage }}</p>
                        </div>
                    </div>

                    <div :class="isEditMode ? 'p-6' : 'flex flex-col md:flex-row gap-4 bg-gray-50 p-4 rounded-lg border border-gray-200'">
                        <div class="flex-1 flex flex-col gap-1">
                            <label class="font-bold text-gray-400 uppercase text-xs">Nama Metode Pembayaran</label>
                            <input 
                                v-model="singleEntry.name" 
                                type="text" 
                                placeholder="Contoh: QRIS, BANK TRANSFER, dll"
                                class="border border-gray-300 rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-blue-500 font-bold uppercase" 
                                @keyup.enter="!isEditMode ? addToBatch() : submit()"
                            />
                        </div>

                        <div v-if="!isEditMode" class="flex flex-col gap-1 text-xs min-w-[120px]">
                            <label class="font-bold text-gray-400 uppercase tracking-tighter text-center">Tambah</label>
                            <button @click="addToBatch" class="bg-black text-white rounded-lg py-2.5 font-bold hover:bg-blue-600 transition-all shadow-sm">
                                + Antrian
                            </button>
                        </div>
                    </div>

                    <div v-if="isEditMode" class="p-6 bg-gray-50 border-t border-gray-100 flex gap-3 rounded-b-2xl">
                        <button @click="submit" :disabled="form.processing" class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black uppercase tracking-widest transition-all shadow-lg">
                            {{ form.processing ? 'Menyimpan...' : 'Update Data' }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showForm && !isEditMode && form.items.length > 0" class="mb-8 border-2 border-dashed border-blue-200 rounded-xl overflow-hidden shadow-sm bg-white animate-in fade-in slide-in-from-top-4">
                <div class="bg-blue-50 px-4 py-2 border-b border-blue-100 flex justify-between items-center">
                    <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest">Antrian Metode Baru</span>
                    <span class="text-[10px] bg-blue-600 text-white px-2 py-0.5 rounded-full">{{ form.items.length }} Item</span>
                </div>
                <div class="p-4 flex flex-wrap gap-2">
                    <div v-for="(item, idx) in form.items" :key="idx" class="flex items-center gap-2 bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200 group">
                        <span class="font-bold uppercase text-xs">{{ item.name }}</span>
                        <button @click="removeFromBatch(idx)" class="text-gray-400 hover:text-red-500">✕</button>
                    </div>
                </div>
                <div class="p-4 bg-gray-50 border-t flex justify-end">
                    <button @click="submit" :disabled="form.processing" class="bg-blue-600 text-white px-8 py-2.5 rounded-lg font-black uppercase tracking-tighter hover:bg-blue-700 shadow-sm">
                        Simpan Semua Antrian
                    </button>
                </div>
            </div>

            <DataTable 
                title="Master Metode Pembayaran"
                :resource="methods" 
                :columns="[
                    { label: 'Metode Pembayaran', key: 'name' }, 
                    { label: 'Dibuat Pada', key: 'created_at' },
                    { label: 'Dibuat Oleh', key: 'creator' },
                ]"
                routeName="payment-methods.index" 
                :initialSearch="filters?.search || ''"
                :showAddButton="!showForm"
                @on-add="openCreate"
            >
                <template #name="{ row }">
                    <span class="font-black text-gray-800 uppercase tracking-tight italic">{{ row.name }}</span>
                </template>
                
                <template #created_at="{ value }">
                    <span class="text-[10px] font-bold text-gray-400 uppercase">
                        {{ new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                    </span>
                </template>

                <template #creator="{ row }">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded bg-blue-600 text-white flex items-center justify-center text-[8px] font-bold uppercase shadow-sm">
                            {{ row.creator?.name?.charAt(0) || row.user?.name?.charAt(0) || 'U' }}
                        </div>
                        <span class="text-[10px] font-bold uppercase text-gray-600 tracking-tight">
                            {{ row.creator?.name || row.user?.name || 'Admin' }}
                        </span>
                    </div>
                </template>

                <template #actions="{ row }">
                    <div class="flex gap-4 justify-end items-center">
                        <button @click="openEdit(row)" class="text-gray-300 hover:text-blue-600 transition-colors transform hover:scale-125">✏️</button>
                        <button @click="destroy(row)" class="text-gray-300 hover:text-red-600 transition-colors transform hover:scale-125">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>