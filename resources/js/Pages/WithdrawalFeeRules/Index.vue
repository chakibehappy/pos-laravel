<script setup>
import { ref, watch } from 'vue'; // Tambah watch
import { useForm, router } from '@inertiajs/vue3'; // Tambah router
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import debounce from 'lodash/debounce'; // Optional: agar pencarian tidak terlalu berat (jika ada)

const props = defineProps({ 
    resource: Object,
    filters: Object // Pastikan filters diterima dari Controller
});

const columns = [
    { label: 'Min Limit', key: 'min_limit' }, 
    { label: 'Max Limit', key: 'max_limit' },
    { label: 'Biaya (Fee)', key: 'fee' },
    { label: 'Dibuat Oleh', key: 'creator' }
];

// --- FITUR PENCARIAN ---
const search = ref(props.filters.search);

// Fungsi untuk mengirim request pencarian
watch(search, debounce((value) => {
    router.get(
        route('withdrawal-fee-rules.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 300)); // Delay 300ms agar tidak spam request saat mengetik

// --- FORM LOGIC ---
const showForm = ref(false);
const form = useForm({
    id: null,
    min_limit: '',
    max_limit: '',
    fee: '',
});

const openCreate = () => {
    form.reset();
    form.id = null;
    showForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.min_limit = row.min_limit;
    form.max_limit = row.max_limit;
    form.fee = row.fee;
    showForm.value = true;
};

const submit = () => {
    form.post(route('withdrawal-fee-rules.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const destroy = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus aturan ini?')) {
        form.delete(route('withdrawal-fee-rules.destroy', id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div v-if="showForm" class="mb-8 p-6 border-2 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
            <h2 class="font-black uppercase mb-4 text-xl">
                {{ form.id ? 'Edit Aturan Biaya' : 'Tambah Aturan Biaya Baru' }}
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col">
                    <label class="font-black uppercase text-xs mb-1">Minimal Limit</label>
                    <input v-model="form.min_limit" type="number" placeholder="0" 
                        class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]" />
                </div>
                <div class="flex flex-col">
                    <label class="font-black uppercase text-xs mb-1">Maksimal Limit</label>
                    <input v-model="form.max_limit" type="number" placeholder="0" 
                        class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]" />
                </div>
                <div class="flex flex-col">
                    <label class="font-black uppercase text-xs mb-1">Biaya (Fee)</label>
                    <input v-model="form.fee" type="number" placeholder="0" 
                        class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]" />
                </div>
            </div>

            <div class="mt-6 flex gap-x-2">
                <button @click="submit" :disabled="form.processing" 
                    class="bg-black text-white px-8 py-2 font-bold uppercase shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:translate-y-[2px] active:translate-x-[2px] active:shadow-none transition-all">
                    {{ form.id ? 'Simpan' : 'Tambahkan' }}
                </button>
                <button @click="showForm = false" 
                    class="border-2 border-black px-8 py-2 font-bold uppercase active:translate-y-[2px] active:translate-x-[2px]">
                    Batal
                </button>
            </div>
        </div>

        <div class="mb-4 max-w-sm">
            <input 
                v-model="search" 
                type="text" 
                placeholder="CARI ATURAN..." 
                class="w-full border-2 border-black p-2 font-black uppercase shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] focus:outline-none focus:bg-yellow-50"
            />
        </div>

        <DataTable 
            title="Daftar Aturan Biaya Penarikan"
            :resource="resource" 
            :columns="columns"
            :showAddButton="!showForm"
            @on-add="openCreate" 
        >
            <template #creator="{ row }">
                <span class="font-black uppercase text-xs italic">
                    {{ row.creator?.name || 'SYSTEM' }}
                </span>
            </template>

            <template #actions="{ row }">
                <div class="flex flex-row gap-x-[15px] justify-end uppercase text-xs">
                    <button @click="openEdit(row)" class="font-black hover:text-blue-600 transition-transform hover:scale-125">✏️</button>
                    <button @click="destroy(row.id)" class="font-black text-red-500 hover:text-red-700 transition-transform hover:scale-125">❌</button>
                </div>
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>