<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

// 1. Receive the dropdown data from the backend
const props = defineProps({ 
    stores: Object,
    store_types: Array 
});

const columns = [
    { label: 'NAME', key: 'name' }, 
    { label: 'TYPE', key: 'type_name' }, // Dot-walk to the name
    { label: 'ADDRESS', key: 'address' }
];

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
    if (confirm('Are you sure?')) {
        form.delete(route('stores.destroy', id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div v-if="showForm" class="mb-8 p-6 border-2 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)]">
            <h2 class="font-black uppercase mb-4 italic">{{ form.id ? 'Edit Store' : 'Add New Store' }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col">
                    <input v-model="form.name" type="text" placeholder="NAME" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                    <span v-if="form.errors.name" class="text-red-500 text-xs font-bold uppercase mt-1">{{ form.errors.name }}</span>
                </div>

                <div class="flex flex-col">
                    <select v-model="form.store_type_id" class="border-2 border-black p-2 font-bold bg-white focus:bg-yellow-50 outline-none">
                        <option value="" disabled>SELECT TYPE</option>
                        <option v-for="type in store_types" :key="type.id" :value="type.id">
                            {{ type.name }}
                        </option>
                    </select>
                    <span v-if="form.errors.store_type_id" class="text-red-500 text-xs font-bold uppercase mt-1">{{ form.errors.store_type_id }}</span>
                </div>

                <div class="flex flex-col">
                    <input v-model="form.address" type="text" placeholder="ADDRESS" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                    <span v-if="form.errors.address" class="text-red-500 text-xs font-bold uppercase mt-1">{{ form.errors.address }}</span>
                </div>
            </div>
            <div class="mt-4 flex gap-x-2">
                <button @click="submit" :disabled="form.processing" class="bg-black text-white px-6 py-2 font-bold uppercase disabled:opacity-50">
                    {{ form.id ? 'Save Changes' : 'Create Store' }}
                </button>
                <button @click="showForm = false" class="border-2 border-black px-6 py-2 font-bold uppercase">Cancel</button>
            </div>
        </div>

        <div class="mb-4 flex justify-between items-end">
            <h1 class="text-2xl font-black uppercase tracking-tighter italic">Store Management</h1>
            <button v-if="!showForm" @click="openCreate" class="bg-black text-white px-6 py-2 font-bold uppercase border-2 border-black hover:bg-white hover:text-black transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)] active:shadow-none">
                Add New
            </button>
        </div>

        <DataTable :resource="stores" :columns="columns">
            <template #actions="{ row }">
                <div class="flex flex-row gap-x-[15px] justify-end uppercase text-xs">
                    <button @click="openEdit(row)" class="font-black underline hover:text-blue-600">Edit</button>
                    <button @click="destroy(row.id)" class="font-black underline text-red-500 hover:text-red-700">Delete</button>
                </div>
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>