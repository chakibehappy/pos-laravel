<script setup>
import { ref } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    resource: Object, 
    posUsers: Array,
    stores: Array,
    filters: Object,
});

const showForm = ref(false);
const errorMessage = ref('');

const form = useForm({
    id: null,
    pos_user_id: '',
    store_id: '',
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
    form.pos_user_id = row.pos_user_id;
    form.store_id = row.store_id;
    showForm.value = true;
};

const submit = () => {
    errorMessage.value = '';

    // Validasi: Cek jika ada field yang belum dipilih
    if (!form.pos_user_id || !form.store_id) {
        errorMessage.value = "Pilih User dan Unit Toko terlebih dahulu!";
        return;
    }

    form.post(route('pos-user-stores.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const destroy = (id) => {
    if (confirm('Cabut akses user ini dari toko?')) {
        router.delete(route('pos-user-stores.destroy', id));
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric'
});
</script>

<template>
    <Head title="Akses User Toko" />

    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="showForm" class="mb-8 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">
                        {{ form.id ? '✏️ Edit Akses User' : '➕ Assign User Ke Toko' }}
                    </h2>
                    <button @click="showForm = false" class="text-gray-400 hover:text-red-500 transition-colors">✕</button>
                </div>

                <div class="p-6">
                    <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3 animate-pulse">
                        <span class="text-red-500 font-bold">⚠️</span>
                        <p class="text-xs font-bold text-red-700 uppercase tracking-tight">{{ errorMessage }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Unit Toko Tujuan</label>
                            <select v-model="form.store_id" 
                                class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all uppercase">
                                <option value="">-- PILIH TOKO --</option>
                                <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <span v-if="form.errors.store_id" class="text-red-600 text-[10px] font-bold mt-1 uppercase">{{ form.errors.store_id }}</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Pilih Kasir / Pegawai</label>
                            <select v-model="form.pos_user_id" 
                                class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all uppercase">
                                <option value="">-- PILIH USER --</option>
                                <option v-for="u in posUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                            <span v-if="form.errors.pos_user_id" class="text-red-600 text-[10px] font-bold mt-1 uppercase">{{ form.errors.pos_user_id }}</span>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3 border-t border-gray-100 pt-6">
                        <button @click="submit" :disabled="form.processing" 
                            class="bg-blue-600 text-white px-8 py-2.5 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-sm active:scale-95 disabled:opacity-50">
                            {{ form.id ? 'Simpan Perubahan' : 'Proses Penugasan' }}
                        </button>
                        <button @click="showForm = false" 
                            class="bg-white border border-gray-300 text-gray-600 px-8 py-2.5 rounded-lg text-xs font-bold uppercase hover:bg-gray-50 transition-all">
                            Batal
                        </button>
                    </div>
                </div>
            </div>

            <DataTable 
                title="Akses User Toko"
                :resource="resource" 
                :columns="[
                    { label: 'Nama User', key: 'pos_user_name' }, 
                    { label: 'Unit Toko', key: 'store_name' }, 
                    { label: 'Diberikan Oleh', key: 'creator_name' }, 
                    { label: 'Tanggal', key: 'created_at' }
                ]"
                routeName="pos-user-stores.index" 
                :initialSearch="filters?.search || ''"
                :showAddButton="!showForm"
                @on-add="openCreate"
            >
                <template #pos_user_name="{ row }">
                    <span class="font-bold text-gray-800 uppercase text-xs tracking-tight italic">{{ row.pos_user?.name }}</span>
                </template>

                <template #store_name="{ row }">
                    <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-[10px] font-black uppercase border border-blue-100 shadow-sm">
                        {{ row.pos_user?.role == 'admin' ? 'ALL STORES' : row.store?.name }}
                    </span>
                </template>

                <template #creator_name="{ row }">
                    <span class="text-[10px] font-bold uppercase text-gray-500 italic">
                        👤 {{ row.creator?.name || 'System' }}
                    </span>
                </template>

                <template #created_at="{ row }">
                    <span class="font-mono text-[11px] text-gray-400">{{ formatDate(row.created_at) }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex flex-row gap-4 justify-end">
                        <button @click="openEdit(row)" title="Edit" class="text-gray-400 hover:text-blue-600 transition-colors transform hover:scale-125">✏️</button>
                        <button @click="destroy(row.id)" title="Hapus" class="text-gray-400 hover:text-red-600 transition-colors transform hover:scale-125">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>