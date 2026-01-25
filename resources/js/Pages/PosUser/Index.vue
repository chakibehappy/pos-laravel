<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ 
    staff: Object, 
    stores: Array 
});

const columns = [
    { label: 'STAFF NAME', key: 'name' }, 
    { label: 'STORE', key: 'store_name' }, // Using the alias from backend
    { label: 'PIN', key: 'pin' },
    { label: 'ROLE', key: 'role' }
];

const showForm = ref(false);
const form = useForm({
    id: null,
    store_id: '',
    name: '',
    pin: '',
    role: 'cashier',
});

const openCreate = () => {
    form.reset();
    form.id = null;
    showForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.name = row.name;
    form.pin = row.pin;
    form.role = row.role;
    showForm.value = true;
};

const submit = () => {
    form.post(route('pos_users.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const destroy = (id) => {
    if (confirm('Are you sure?')) {
        form.delete(route('pos_users.destroy', id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div v-if="showForm" class="mb-8 p-6 border-2 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)]">
            <h2 class="font-black uppercase mb-4 italic">{{ form.id ? 'Edit Staff' : 'Add New Staff' }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="flex flex-col">
                    <input v-model="form.name" type="text" placeholder="NAME" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                    <span v-if="form.errors.name" class="text-red-500 text-xs font-bold uppercase mt-1">{{ form.errors.name }}</span>
                </div>

                <div class="flex flex-col">
                    <select v-model="form.store_id" class="border-2 border-black p-2 font-bold bg-white focus:bg-yellow-50 outline-none">
                        <option value="" disabled>SELECT STORE</option>
                        <option v-for="store in stores" :key="store.id" :value="store.id">
                            {{ store.name }}
                        </option>
                    </select>
                    <span v-if="form.errors.store_id" class="text-red-500 text-xs font-bold uppercase mt-1">{{ form.errors.store_id }}</span>
                </div>

                <div class="flex flex-col">
                    <input v-model="form.pin" type="text" placeholder="PIN (4-6 DIGITS)" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                    <span v-if="form.errors.pin" class="text-red-500 text-xs font-bold uppercase mt-1">{{ form.errors.pin }}</span>
                </div>

                <div class="flex flex-col">
                    <select v-model="form.role" class="border-2 border-black p-2 font-bold bg-white focus:bg-yellow-50 outline-none">
                        <option value="cashier">CASHIER</option>
                        <option value="manager">MANAGER</option>
                        <option value="admin">ADMIN</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex gap-x-2">
                <button @click="submit" :disabled="form.processing" class="bg-black text-white px-6 py-2 font-bold uppercase disabled:opacity-50">
                    {{ form.id ? 'Save Changes' : 'Save New Staff' }}
                </button>
                <button @click="showForm = false" class="border-2 border-black px-6 py-2 font-bold uppercase">Cancel</button>
            </div>
        </div>

        <div class="mb-4 flex justify-between items-end">
            <h1 class="text-2xl font-black uppercase tracking-tighter italic">Staff Management</h1>
            <button v-if="!showForm" @click="openCreate" class="bg-black text-white px-6 py-2 font-bold uppercase border-2 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)] active:shadow-none transition-all">
                Add New Staff
            </button>
        </div>

        <DataTable :resource="staff" :columns="columns">
            <template #actions="{ row }">
                <div class="flex flex-row gap-x-[15px] justify-end uppercase text-xs">
                    <button @click="openEdit(row)" class="font-black underline hover:text-blue-600">Edit</button>
                    <button @click="destroy(row.id)" class="font-black underline text-red-500 hover:text-red-700">Delete</button>
                </div>
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>