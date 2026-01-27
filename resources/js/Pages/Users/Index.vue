<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ users: Object });

const columns = [
    { label: 'Nama', key: 'name' }, 
    { label: 'Email', key: 'email' }
];

// 1. Setup the Form State
const showForm = ref(false);
const form = useForm({
    id: null, // If null, we Create. If has value, we Update.
    name: '',
    email: '',
    password: '', // Optional for updates
});

// 2. Wired Actions
const openCreate = () => {
    form.reset();
    form.id = null;
    showForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.name = row.name;
    form.email = row.email;
    form.password = ''; // Keep empty on edit unless changing
    showForm.value = true;
};

const submit = () => {
    form.post(route('users.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const destroy = (id) => {
    if (confirm('Are you sure?')) {
        form.delete(route('users.destroy', id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div v-if="showForm" class="mb-8 p-6 border-2 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)]">
            <h2 class="font-black uppercase mb-4">{{ form.id ? 'Edit Pengguna' : 'Tambahkan Pengguna Baru' }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col">
                    <input v-model="form.name" type="text" placeholder="Nama" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                    <span v-if="form.errors.name" class="text-red-500 text-xs font-bold uppercase mt-1">{{ form.errors.name }}</span>
                </div>
                <div class="flex flex-col">
                    <input v-model="form.email" type="email" placeholder="EMAIL" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                    <span v-if="form.errors.email" class="text-red-500 text-xs font-bold uppercase mt-1">{{ form.errors.email }}</span>
                </div>
                <div class="flex flex-col">
                    <input v-model="form.password" type="password" :placeholder="form.id ? 'PASSWORD (LEAVE BLANK)' : 'PASSWORD'" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                </div>
            </div>
            <div class="mt-4 flex gap-x-2">
                <button @click="submit" :disabled="form.processing" class="bg-black text-white px-6 py-2 font-bold uppercase disabled:opacity-50">
                    {{ form.id ? 'Simpan' : 'Tambahkan' }}
                </button>
                <button @click="showForm = false" class="border-2 border-black px-6 py-2 font-bold uppercase">Batalkan</button>
            </div>
        </div>

        <div class="mb-4 flex justify-between items-end">
            <h1 class="text-2xl font-black uppercase tracking-tighter">Daftar Pengguna</h1>
            <button v-if="!showForm" @click="openCreate" class="bg-black text-white px-6 py-2 font-bold uppercase border-2 border-black hover:bg-white hover:text-black transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)] active:shadow-none">
                Tambahkan
            </button>
        </div>

        <DataTable :resource="users" :columns="columns">
            <template #actions="{ row }">
                <div class="flex flex-row gap-x-[15px] justify-end uppercase text-xs">
                    <button @click="openEdit(row)" class="font-black underline hover:text-blue-600">Edit</button>
                    <button @click="destroy(row.id)" class="font-black underline text-red-500 hover:text-red-700">Hapus</button>
                </div>
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>