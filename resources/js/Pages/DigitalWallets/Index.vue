<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    resource: Object, 
    filters: Object,
});

const showCreateForm = ref(false);
const showEditModal = ref(false);

const form = useForm({
    id: null,
    name: '',
});

const openCreate = () => {
    form.reset();
    form.id = null;
    showCreateForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.name = row.name;
    showEditModal.value = true;
};

const closeForms = () => {
    showCreateForm.value = false;
    showEditModal.value = false;
    form.reset();
};

const submit = () => {
    form.post(route('digital-wallets.store'), {
        onSuccess: () => {
            closeForms();
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
            
            <div v-if="showCreateForm" class="mb-8 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="text-xl font-black uppercase tracking-tighter text-black flex items-center gap-2">
                        <span class="w-2 h-6 bg-green-500 rounded-full"></span>
                        Tambah Platform Baru
                    </h2>
                </div>
                
                <form @submit.prevent="submit" class="p-8 italic">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col">
                            <label class="font-black uppercase text-[10px] tracking-widest text-gray-400 mb-2 not-italic">Nama Platform / Provider</label>
                            <input 
                                v-model="form.name" 
                                type="text" 
                                class="border border-gray-300 rounded-lg p-3 font-bold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none uppercase bg-white transition-all placeholder:text-gray-300"
                                placeholder="MISAL: DANA, OVO, GOPAY..."
                                required
                            />
                        </div>
                    </div>

                    <div class="flex gap-3 pt-8">
                        <button type="submit" :disabled="form.processing" class="bg-black text-white px-8 py-2.5 rounded-lg font-black uppercase text-xs tracking-widest hover:bg-gray-800 transition-all shadow-lg disabled:opacity-50">
                            Simpan Platform
                        </button>
                        <button type="button" @click="showCreateForm = false" class="bg-white border border-gray-300 text-gray-600 px-8 py-2.5 rounded-lg font-black uppercase text-xs tracking-widest hover:bg-gray-50 transition-all">
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
                :showAddButton="!showCreateForm"
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
                        <button @click="openEdit(row)" title="Edit" class="opacity-60 hover:opacity-100 hover:scale-125 transition-all text-xl">✏️</button>
                        <button @click="destroy(row.id)" title="Hapus" class="opacity-60 hover:opacity-100 hover:scale-125 transition-all text-xl">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>

        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-gray-100 overflow-hidden animate-in zoom-in-95 duration-200">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h2 class="text-xl font-black uppercase tracking-tighter text-black flex items-center gap-2">
                        <span class="w-2 h-6 bg-blue-600 rounded-full"></span>
                        Edit Platform
                    </h2>
                    <button @click="closeForms" class="text-gray-400 hover:text-black transition-colors text-xl font-black">✕</button>
                </div>
                
                <form @submit.prevent="submit" class="p-8 italic">
                    <div class="flex flex-col">
                        <label class="font-black uppercase text-[10px] tracking-widest text-gray-400 mb-2 not-italic">Nama Platform / Provider</label>
                        <input 
                            v-model="form.name" 
                            type="text" 
                            class="border border-gray-300 rounded-lg p-4 font-black focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none uppercase bg-gray-50/50 transition-all text-lg"
                            required
                        />
                        <span v-if="form.errors.name" class="text-red-500 text-[10px] font-black uppercase mt-2 not-italic italic">{{ form.errors.name }}</span>
                    </div>

                    <div class="flex flex-col gap-3 pt-8">
                        <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 text-white py-4 rounded-xl font-black uppercase text-sm tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-100 disabled:opacity-50">
                            {{ form.processing ? 'Memproses...' : 'Update Platform' }}
                        </button>
                        <button type="button" @click="closeForms" class="w-full bg-white text-gray-400 py-3 rounded-xl font-black uppercase text-[10px] tracking-widest hover:text-gray-600 transition-all">
                            Tutup
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>