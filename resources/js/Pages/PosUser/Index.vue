<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import debounce from 'lodash/debounce';

const props = defineProps({ 
    staff: Object, 
    stores: Array,
    filters: Object // Wajib ada untuk menangkap state search dari server
});

const columns = [
    { label: 'Nama Staff', key: 'name' }, 
    { label: 'Toko', key: 'store_name' }, 
    { label: 'Jabatan', key: 'role' },
    { label: 'Shift', key: 'shift' }
];

// --- LOGIKA PENCARIAN (Ubah Variabel search agar sinkron) ---
const search = ref(props.filters?.search || '');

watch(search, debounce((v) => {
    router.get(route('pos_users.index'), { search: v }, { preserveState: true, replace: true });
}, 500));

const handleSearch = (v) => {
    search.value = v;
};

// --- FORM STATE ---
const showForm = ref(false);
const form = useForm({
    id: null,
    store_id: '',
    name: '',
    pin: '',
    role: 'cashier',
    shift: 'pagi', 
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
    form.pin = ''; 
    form.role = row.role;
    form.shift = row.shift || 'pagi';
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
    if (confirm('Apakah Anda yakin ingin menghapus staff ini?')) {
        form.delete(route('pos_users.destroy', id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div v-if="showForm" class="mb-8 p-6 border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] transition-all">
            <h2 class="font-black uppercase mb-6 italic text-2xl border-b-4 border-black pb-2 inline-block">
                {{ form.id ? '✏️ Edit Staff' : '➕ Add New Staff' }}
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="font-black text-xs uppercase">Nama</label>
                    <input v-model="form.name" type="text" placeholder="NAME" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]" />
                    <span v-if="form.errors.name" class="text-red-500 text-[10px] font-bold uppercase mt-1">{{ form.errors.name }}</span>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="font-black text-xs uppercase">Toko</label>
                    <select v-model="form.store_id" class="border-2 border-black p-2 font-bold bg-white focus:bg-yellow-50 outline-none shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                        <option value="" disabled>PILIH TOKO</option>
                        <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="font-black text-xs uppercase">PIN</label>
                    <input v-model="form.pin" type="text" placeholder="4-6 DIGITS" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="font-black text-xs uppercase">Jabatan</label>
                    <select v-model="form.role" class="border-2 border-black p-2 font-bold bg-white focus:bg-yellow-50 outline-none shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                        <option value="cashier">KASIR</option>
                        <option value="manager">MANAGER</option>
                        <option value="admin">ADMIN</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="font-black text-xs uppercase">Shift</label>
                    <select v-model="form.shift" class="border-2 border-black p-2 font-bold bg-white focus:bg-yellow-50 outline-none shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                        <option value="pagi">☀️ PAGI</option>
                        <option value="malam">🌙 MALAM</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex gap-x-3">
                <button @click="submit" :disabled="form.processing" class="bg-black text-white px-8 py-2 font-black uppercase shadow-[4px_4px_0px_0px_rgba(0,0,0,0.3)] active:translate-x-1 active:translate-y-1 active:shadow-none transition-all">
                    {{ form.id ? 'Update Staff' : 'Simpan Staff' }}
                </button>
                <button @click="showForm = false" class="border-4 border-black px-8 py-2 font-black uppercase hover:bg-gray-100 transition-all">
                    Batalkan
                </button>
            </div>
        </div>

        <DataTable 
            title="Daftar Staff" 
            :resource="staff" 
            :columns="columns"
            :initialSearch="search"
            :showAddButton="!showForm"
            placeholder="Cari Nama Staff..."
            @on-search="handleSearch"
            @on-add="openCreate"
        >
            <template #name="{ row }">
                <span class="font-black uppercase italic tracking-tighter">{{ row.name }}</span>
            </template>

            <template #role="{ row }">
                <span class="px-2 py-1 border-2 border-black font-black text-[10px] uppercase shadow-[1px_1px_0px_0px_rgba(0,0,0,1)] bg-white">
                    {{ row.role }}
                </span>
            </template>

            <template #shift="{ row }">
                <span v-if="row.shift === 'pagi'" class="font-bold text-orange-600">☀️ PAGI</span>
                <span v-else class="font-bold text-indigo-600">🌙 MALAM</span>
            </template>

            <template #actions="{ row }">
                <div class="flex flex-row gap-x-[15px] justify-end">
                    <button @click="openEdit(row)" class="font-black hover:scale-125 transition-all">✏️</button>
                    <button @click="destroy(row.id)" class="font-black text-red-500 hover:scale-125 transition-all">❌</button>
                </div>
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>