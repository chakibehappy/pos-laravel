<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    resource: Object, 
    posUsers: Array,
    stores: Array,
    filters: Object,
});

const showForm = ref(false);
const search = ref(props.filters.search || '');

// Search dengan Debounce (Konsisten Server-side)
watch(search, debounce((v) => {
    router.get(route('pos-user-stores.index'), { search: v }, { preserveState: true });
}, 500));

const form = useForm({
    id: null,
    pos_user_id: '',
    store_id: '',
});

// 1. Wired Actions sesuai standar baru
const openCreate = () => {
    form.reset();
    form.id = null;
    showForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.pos_user_id = row.pos_user_id;
    form.store_id = row.store_id;
    showForm.value = true;
};

const submit = () => {
    // Menggunakan POST karena logic updateOrCreate di Controller
    form.post(route('pos-user-stores.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const destroy = (id) => {
    if (confirm('Cabut akses user ini dari toko?')) {
        router.delete(route('pos-user-stores.destroy', id));
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric'
});
</script>

<template>
    <Head title="Akses User Toko" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div v-if="showForm" class="mb-10 p-8 border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                <h2 class="text-2xl font-black uppercase italic mb-6">
                    {{ form.id ? 'Edit Akses User' : 'Assign User Ke Toko' }}
                </h2>
                
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-6 italic">
                    <div class="flex flex-col">
                        <label class="font-black uppercase text-xs mb-1">Pilih Kasir / Pegawai</label>
                        <select v-model="form.pos_user_id" class="border-4 border-black p-3 font-bold focus:bg-blue-50 outline-none uppercase">
                            <option value="">-- PILIH USER --</option>
                            <option v-for="u in posUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <span v-if="form.errors.pos_user_id" class="text-red-600 text-[10px] font-black uppercase mt-1">{{ form.errors.pos_user_id }}</span>
                    </div>

                    <div class="flex flex-col">
                        <label class="font-black uppercase text-xs mb-1">Unit Toko Tujuan</label>
                        <select v-model="form.store_id" class="border-4 border-black p-3 font-bold focus:bg-blue-50 outline-none uppercase">
                            <option value="">-- PILIH TOKO --</option>
                            <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <span v-if="form.errors.store_id" class="text-red-600 text-[10px] font-black uppercase mt-1">{{ form.errors.store_id }}</span>
                    </div>

                    <div class="md:col-span-2 flex gap-4 pt-2">
                        <button type="submit" :disabled="form.processing" class="bg-black text-white px-8 py-3 font-black uppercase shadow-[4px_4px_0_#3b82f6] hover:shadow-none transition-all disabled:opacity-50">
                            {{ form.id ? 'Simpan Perubahan' : 'Proses Penugasan' }}
                        </button>
                        <button type="button" @click="showForm = false" class="border-4 border-black px-8 py-3 font-black uppercase hover:bg-gray-100 transition-all">
                            Batalkan
                        </button>
                    </div>
                </form>
            </div>

            <div class="mb-6" v-if="!showForm">
                <input v-model="search" type="text" placeholder="CARI USER ATAU TOKO..." 
                    class="w-full md:w-1/3 border-4 border-black p-3 font-black uppercase outline-none focus:ring-4 focus:ring-blue-500/20 shadow-[4px_4px_0_#000]">
            </div>

            <DataTable 
                title="Akses User Toko"
                :resource="resource" 
                :columns="[
                    { label: 'Nama User', key: 'pos_user_name' }, 
                    { label: 'Unit Toko', key: 'store_name' }, 
                    { label: 'Tanggal', key: 'created_at' }
                ]"
                :showAddButton="!showForm"
                @on-add="openCreate"
            >
                <template #pos_user_name="{ row }">
                    <span class="font-black uppercase tracking-tight italic">{{ row.pos_user?.name }}</span>
                </template>

                <template #store_name="{ row }">
                    <span class="bg-blue-600 text-white px-3 py-1 font-black text-[10px] uppercase italic">
                        {{ row.pos_user.role == "admin" ? row.pos_user?.role : row.store?.name }}
                    </span>
                </template>

                <template #created_at="{ row }">
                    <span class="font-mono text-xs text-gray-400">{{ formatDate(row.created_at) }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex flex-row gap-x-4 justify-end">
                        <button @click="openEdit(row)" title="Edit" class="font-black hover:scale-125 transition-transform">✏️</button>
                        <button @click="destroy(row.id)" title="Hapus" class="font-black text-red-500 hover:scale-125 transition-transform">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>