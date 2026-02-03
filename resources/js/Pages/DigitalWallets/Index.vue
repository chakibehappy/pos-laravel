<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    resource: Object, 
    filters: Object,
});

const showForm = ref(false);

const form = useForm({
    id: null,
    name: '',
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
    showForm.value = true;
};

const submit = () => {
    form.post(route('digital-wallets.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const destroy = (id) => {
    if (confirm('Hapus master platform ini?')) {
        form.delete(route('digital-wallets.destroy', id));
    }
};
</script>

<template>
    <Head title="Master Wallet" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div v-if="showForm" class="mb-10 p-8 border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                <h2 class="text-2xl font-black uppercase italic mb-6">
                    {{ form.id ? 'Edit Platform Wallet' : 'Tambah Platform Baru' }}
                </h2>
                
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-6 italic">
                    <div class="flex flex-col">
                        <label class="font-black uppercase text-xs mb-1">Nama Platform / Provider</label>
                        <input 
                            v-model="form.name" 
                            type="text" 
                            class="border-4 border-black p-3 font-bold focus:bg-blue-50 outline-none uppercase shadow-[inner_4px_4px_0_rgba(0,0,0,0.05)]"
                            placeholder="MISAL: DANA, OVO, GOPAY..."
                            required
                        />
                        <span v-if="form.errors.name" class="text-red-600 text-[10px] font-black uppercase mt-1">
                            {{ form.errors.name }}
                        </span>
                    </div>

                    <div class="md:col-span-2 flex gap-4 pt-2">
                        <button type="submit" :disabled="form.processing" class="bg-black text-white px-8 py-3 font-black uppercase shadow-[4px_4px_0_#3b82f6] hover:shadow-none transition-all disabled:opacity-50">
                            {{ form.id ? 'Update Platform' : 'Simpan Platform' }}
                        </button>
                        <button type="button" @click="showForm = false" class="border-4 border-black px-8 py-3 font-black uppercase hover:bg-gray-100 transition-all">
                            Batalkan
                        </button>
                    </div>
                </form>
            </div>

            <DataTable 
                title="Master Digital Wallet"
                :resource="resource" 
                :columns="[
                    { label: 'ID', key: 'id' }, 
                    { label: 'Nama Platform', key: 'name' }
                ]"
                routeName="digital-wallets.index" 
                :initialSearch="filters?.search || ''"
                :showAddButton="!showForm"
                @on-add="openCreate"
            >
                <template #id="{ row }">
                    <span class="font-mono text-xs font-bold text-gray-400">#{{ row.id }}</span>
                </template>

                <template #name="{ row }">
                    <span class="font-black uppercase tracking-tight italic text-lg text-black">{{ row.name }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex flex-row gap-x-6 justify-end mr-4">
                        <button @click="openEdit(row)" title="Edit" class="font-black hover:scale-125 transition-transform text-xl">✏️</button>
                        <button @click="destroy(row.id)" title="Hapus" class="font-black text-red-500 hover:scale-125 transition-transform text-xl">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>