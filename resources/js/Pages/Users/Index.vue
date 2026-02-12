<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ 
    users: Object,
    filters: Object // Untuk menangani state pencarian
});

const isUpdate = ref(false);

const columns = [
    { label: 'Nama Lengkap', key: 'name' }, 
    { label: 'Alamat Email', key: 'email' }
];

const showForm = ref(false);
const form = useForm({
    id: null, 
    name: '',
    email: '',
    password: '', 
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    isUpdate.value = false;
    showForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.name = row.name;
    form.email = row.email;
    form.password = ''; 
    isUpdate.value = true;
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
    if (confirm('APAKAH ANDA YAKIN INGIN MENGHAPUS DATA INI?')) {
        form.delete(route('users.destroy', id));
    }
};
</script>

<template>
    <Head title="Manajemen User" />
    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="showForm" class="mb-8 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden animate-in fade-in slide-in-from-top-2 duration-300">
                <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="text-sm font-black text-gray-700 uppercase tracking-tight italic">
                        {{ form.id ? '✏️ Edit Informasi Pengguna' : '➕ Tambah Pengguna Baru' }}
                    </h3>
                    <button @click="showForm = false" class="text-[10px] font-bold text-gray-400 hover:text-red-500 uppercase tracking-widest transition-colors">Tutup [X]</button>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Nama Lengkap</label>
                            <input v-model="form.name" type="text" placeholder="Masukkan nama..." 
                                class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white transition-all placeholder:text-gray-300" />
                            <span v-if="form.errors.name" class="text-[9px] text-red-500 font-bold uppercase mt-1 ml-1">{{ form.errors.name }}</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Email</label>
                            <input v-model="form.email" type="email" placeholder="contoh@mail.com" :readonly="isUpdate"
                            :class="isUpdate ? 'text-gray-500' : ''"
                                class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white transition-all placeholder:text-gray-300" />
                            <span v-if="form.errors.email" class="text-[9px] text-red-500 font-bold uppercase mt-1 ml-1">{{ form.errors.email }}</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Keamanan (Password)</label>
                            <input v-model="form.password" type="password" :placeholder="form.id ? 'BIARKAN KOSONG JIKA TIDAK DIGANTI' : 'MINIMAL 6 KARAKTER'" 
                                class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white transition-all placeholder:text-gray-300" />
                            <span v-if="form.errors.password" class="text-[9px] text-red-500 font-bold uppercase mt-1 ml-1">{{ form.errors.password }}</span>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3">
                        <button @click="submit" :disabled="form.processing" 
                            class="bg-black text-white px-8 py-2.5 rounded-lg font-black text-xs uppercase tracking-widest hover:bg-gray-800 transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px] disabled:opacity-50">
                            {{ form.id ? 'Simpan Perubahan' : 'Daftarkan User' }}
                        </button>
                        <button @click="showForm = false" 
                            class="bg-white text-gray-500 border border-gray-200 px-8 py-2.5 rounded-lg font-black text-xs uppercase tracking-widest hover:bg-gray-50 transition-all">
                            Batalkan
                        </button>
                    </div>
                </div>
            </div>

            <DataTable 
                title="Daftar Pengguna"
                :resource="users" 
                :columns="columns"
                :showAddButton="!showForm"
                route-name="users.index" 
                :initial-search="filters?.search || ''"
                @on-add="openCreate" 
            >
                <template #actions="{ row }">
                    <div class="flex flex-row gap-4 justify-end items-center">
                        <button @click="openEdit(row)" class="text-lg hover:scale-125 transition-transform" title="Edit Data">✏️</button>
                        <button @click="destroy(row.id)" class="text-lg hover:scale-125 transition-transform" title="Hapus Data">❌</button>
                    </div>
                </template>
            </DataTable>

        </div>
    </AuthenticatedLayout>
</template>