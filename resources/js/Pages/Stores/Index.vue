<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ 
    stores: Object,
    store_types: Array,
    filters: Object 
});

const showForm = ref(false);
const form = useForm({
    id: null,
    name: '',
    store_type_id: '',
    address: '',
});

const openCreate = () => {
    form.reset();
    form.id = null;
    showForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.name = row.name;
    form.store_type_id = row.store_type_id;
    form.address = row.address;
    showForm.value = true;
};

const submit = () => {
    form.post(route('stores.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const destroy = (id) => {
    if (confirm('Hapus toko ini?')) {
        form.delete(route('stores.destroy', id));
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric'
});
</script>

<template>
    <Head title="Daftar Toko" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div v-if="showForm" class="mb-8 p-6 bg-white rounded-xl border border-gray-200 shadow-sm">
                <h2 class="text-xl font-bold text-gray-800 mb-6">{{ form.id ? 'Edit Toko' : 'Tambah Toko' }}</h2>
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-500 uppercase">Nama Toko</label>
                        <input v-model="form.name" type="text" class="border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Nama Toko..." />
                        <span v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-500 uppercase">Tipe</label>
                        <select v-model="form.store_type_id" class="border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Pilih Tipe</option>
                            <option v-for="t in store_types" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-500 uppercase">Alamat</label>
                        <input v-model="form.address" type="text" class="border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Alamat..." />
                    </div>
                    <div class="md:col-span-3 flex gap-3 mt-2">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition-all">Simpan</button>
                        <button type="button" @click="showForm = false" class="bg-gray-100 text-gray-600 px-6 py-2 rounded-lg font-bold hover:bg-gray-200 transition-all">Batal</button>
                    </div>
                </form>
            </div>

            <DataTable 
                title="Manajemen Toko"
                :resource="stores" 
                :columns="[
                    { label: 'Nama Toko', key: 'name' }, 
                    { label: 'Tipe', key: 'type_name' }, 
                    { label: 'Alamat', key: 'address' },
                    { label: 'Tanggal', key: 'created_at' }
                ]"
                routeName="stores.index" 
                :initialSearch="filters?.search || ''"
                :showAddButton="!showForm"
                @on-add="openCreate"
            >
                <template #type_name="{ row }">
                    <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide border border-blue-100">
                        {{ row.type_name }}
                    </span>
                </template>

                <template #created_at="{ row }">
                    <span class="text-gray-500 text-xs font-medium">{{ formatDate(row.created_at) }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex gap-4 justify-end">
                        <button @click="openEdit(row)" class="text-gray-400 hover:text-blue-600 transition-colors">✏️</button>
                        <button @click="destroy(row.id)" class="text-gray-400 hover:text-red-600 transition-colors">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>