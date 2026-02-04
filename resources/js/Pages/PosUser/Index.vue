<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import debounce from 'lodash/debounce';

const props = defineProps({ 
    resource: Object,
    filters: Object 
});

// Konfigurasi Kolom Tabel
const columns = [
    { label: 'Nama Lengkap', key: 'name' }, 
    { label: 'Username', key: 'username' },
    { label: 'Jabatan (Role)', key: 'role' },
    { label: 'Status', key: 'is_active' }
];

// --- FITUR PENCARIAN ---
const search = ref(props.filters.search);

watch(search, debounce((value) => {
    router.get(
        route('pos_users.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 300));

// --- FORM LOGIC ---
const showForm = ref(false);
const form = useForm({
    id: null,
    name: '',
    username: '',
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
    form.name = row.name;
    form.username = row.username;
    form.role = row.role;
    form.pin = '****'; // Visual placeholder untuk PIN lama
    showForm.value = true;
};

const submit = () => {
    // Peringatan PIN untuk User Baru
    if (!form.id && !form.pin) {
        alert("PERINGATAN: PIN WAJIB DIISI UNTUK USER BARU!");
        return;
    }

    // Jika edit dan PIN tidak diubah, kosongkan agar tidak di-hash ulang di controller
    if (form.id && form.pin === '****') {
        form.pin = '';
    }

    form.post(route('pos_users.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const destroy = (id) => {
    if (confirm('APAKAH ANDA YAKIN INGIN MENGHAPUS USER INI?')) {
        router.delete(route('pos_users.destroy', id));
    }
};
</script>

<template>
    <Head title="Manajemen User POS" />
    <AuthenticatedLayout>
        <div v-if="showForm" class="mb-8 p-6 border-2 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] text-black">
            <h2 class="font-black uppercase mb-4 text-xl italic">
                {{ form.id ? 'Edit Informasi User' : 'Tambah User POS Baru' }}
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="flex flex-col">
                    <label class="font-black uppercase text-[10px] mb-1">Nama Lengkap</label>
                    <input v-model="form.name" type="text" placeholder="NAMA..." 
                        class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] uppercase text-sm" />
                    <div v-if="form.errors.name" class="text-[9px] text-red-600 font-black mt-1 uppercase">{{ form.errors.name }}</div>
                </div>

                <div class="flex flex-col">
                    <label class="font-black uppercase text-[10px] mb-1">Username</label>
                    <input v-model="form.username" type="text" placeholder="USERNAME..." 
                        class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] text-sm" />
                    <div v-if="form.errors.username" class="text-[9px] text-red-600 font-black mt-1 uppercase">{{ form.errors.username }}</div>
                </div>

                <div class="flex flex-col">
                    <label class="font-black uppercase text-[10px] mb-1">PIN (4-6 DIGIT)</label>
                    <input v-model="form.pin" type="text" maxlength="6" @focus="$event.target.select()"
                        class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] text-sm" />
                    <div v-if="form.errors.pin" class="text-[9px] text-red-600 font-black mt-1 uppercase">{{ form.errors.pin }}</div>
                </div>

                <div class="flex flex-col">
                    <label class="font-black uppercase text-[10px] mb-1">Jabatan / Role</label>
                    <select v-model="form.role" 
                        class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] text-sm uppercase italic">
                        <option value="cashier">Kasir</option>
                        <option value="collector">Kolektor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex gap-x-2">
                <button @click="submit" :disabled="form.processing" 
                    class="bg-black text-white px-8 py-2 font-black uppercase text-xs shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] active:translate-y-[2px] active:translate-x-[2px] active:shadow-none transition-all disabled:opacity-50">
                    {{ form.id ? 'Simpan Perubahan' : 'Daftarkan User' }}
                </button>
                <button @click="showForm = false" 
                    class="border-2 border-black bg-white px-8 py-2 font-black uppercase text-xs active:translate-y-[2px] active:translate-x-[2px] transition-all">
                    Batal
                </button>
            </div>
        </div>

        <DataTable 
            title="Daftar Pengguna Sistem POS"
            :resource="resource" 
            :columns="columns"
            :showAddButton="!showForm"
            route-name="pos_users.index" 
            :initialSearch="filters.search"
            @on-add="openCreate" 
        >
            <template #username="{ row }">
                <span class="font-medium text-gray-500 lowercase">{{ row.username }}</span>
            </template>

            <template #role="{ row }">
                <span class="font-black uppercase text-[9px] italic bg-gray-100 px-2 py-0.5 border border-black shadow-[1px_1px_0px_0px_rgba(0,0,0,1)]">
                    {{ row.role }}
                </span>
            </template>

            <template #is_active="{ row }">
                <span :class="row.is_active ? 'text-green-600' : 'text-red-600'" class="font-black text-[10px] uppercase">
                    {{ row.is_active ? '● Aktif' : '○ Non-Aktif' }}
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