<script setup>
import { ref } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ 
    stores: Object,
    store_types: Array,
    filters: Object 
});

const showAddForm = ref(false); // Kontrol form inline (Tambah)
const showEditModal = ref(false); // Kontrol modal popup (Edit)
const activeType = ref(props.filters?.type || 'all');

const form = useForm({
    id: null,
    name: '',
    store_type_id: '',
    address: '',
});

const filterByType = (typeId) => {
    activeType.value = typeId;
    router.get(route('stores.index'), { 
        ...props.filters, 
        type: typeId === 'all' ? null : typeId 
    }, { preserveState: true, replace: true });
};

const openCreate = () => {
    form.reset();
    form.id = null;
    showEditModal.value = false; // Pastikan modal tutup
    showAddForm.value = true; // Buka form inline
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.name = row.name;
    form.store_type_id = row.store_type_id;
    form.address = row.address;
    
    showAddForm.value = false; // Tutup form inline jika sedang terbuka
    showEditModal.value = true; // Buka modal popup
};

const closeForms = () => {
    showAddForm.value = false;
    showEditModal.value = false;
    form.reset();
};

const submit = () => {
    form.post(route('stores.store'), {
        onSuccess: () => {
            closeForms();
        },
    });
};

const destroy = (id) => {
    if (confirm('Hapus toko ini?')) {
        router.delete(route('stores.destroy', id));
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric'
});
</script>

<template>
    <Head title="Manajemen Toko" />

    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="$page.props.errors.message" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 text-xs font-black uppercase rounded-xl shadow-sm">
                ❌ {{ $page.props.errors.message }}
            </div>

            <div v-if="showAddForm" class="mb-8 p-6 bg-white rounded-xl border border-gray-200 shadow-sm animate-in fade-in slide-in-from-top-2 duration-300">
                <h2 class="text-lg font-black text-gray-800 mb-6 uppercase tracking-tighter">Tambah Toko Baru</h2>
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Nama Toko</label>
                        <input v-model="form.name" type="text" class="border border-gray-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold" />
                        <span v-if="form.errors.name" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors.name }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tipe Bisnis</label>
                        <select v-model="form.store_type_id" class="border border-gray-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 font-bold outline-none">
                            <option value="">PILIH TIPE</option>
                            <option v-for="t in store_types" :key="t.id" :value="t.id">{{ t.name.toUpperCase() }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Alamat</label>
                        <input v-model="form.address" type="text" class="border border-gray-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 font-bold outline-none" />
                    </div>
                    <div class="md:col-span-3 flex gap-3 mt-2">
                        <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-black text-xs uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-100">
                            {{ form.processing ? 'MENYIMPAN...' : 'SIMPAN TOKO' }}
                        </button>
                        <button type="button" @click="showAddForm = false" class="bg-gray-100 text-gray-500 px-6 py-2 rounded-lg font-black text-xs uppercase tracking-widest hover:bg-gray-200">BATAL</button>
                    </div>
                </form>
            </div>

            <div v-if="showEditModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-in fade-in duration-200">
                <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-gray-100 animate-in zoom-in-95 duration-200">
                    <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50 rounded-t-2xl">
                        <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Edit Data Toko</h2>
                        <button @click="closeForms" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
                    </div>
                    <form @submit.prevent="submit" class="p-6">
                        <div class="space-y-5">
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Nama Toko</label>
                                <input v-model="form.name" type="text" class="border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold" />
                                <span v-if="form.errors.name" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors.name }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tipe Bisnis</label>
                                <select v-model="form.store_type_id" class="border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 font-bold outline-none">
                                    <option v-for="t in store_types" :key="t.id" :value="t.id">{{ t.name.toUpperCase() }}</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Alamat</label>
                                <input v-model="form.address" type="text" class="border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 font-bold outline-none" />
                            </div>
                        </div>
                        <div class="flex gap-3 mt-8">
                            <button type="submit" :disabled="form.processing" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest shadow-xl shadow-blue-100 active:scale-95">
                                UPDATE DATA
                            </button>
                            <button type="button" @click="closeForms" class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-xl font-black text-xs uppercase tracking-widest">BATAL</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mb-6">
                <div class="inline-flex bg-white p-1.5 rounded-xl border border-gray-200 items-center gap-3 shadow-sm">
                    <label class="pl-3 text-[10px] font-black uppercase text-gray-400 tracking-widest">Filter Kategori</label>
                    <select @change="filterByType($event.target.value)" v-model="activeType" class="bg-transparent border-none text-gray-800 text-xs rounded-lg focus:ring-0 px-4 py-2 font-black outline-none min-w-[180px] uppercase">
                        <option value="all"> SEMUA TIPE</option>
                        <option v-for="type in store_types" :key="type.id" :value="type.id.toString()">🏷️ {{ type.name }}</option>
                    </select>
                </div>
            </div>

            <DataTable 
                title="Manajemen Toko"
                :resource="stores" 
                :columns="[
                    { label: 'Nama Toko', key: 'name' }, 
                    { label: 'Tipe', key: 'type_name' }, 
                    { label: 'Alamat', key: 'address' },
                    { label: 'Pembuat', key: 'creator_name' }, 
                    { label: 'Tanggal', key: 'created_at' }
                ]"
                routeName="stores.index" 
                :initialSearch="filters?.search || ''"
                :showAddButton="!showAddForm"
                @on-add="openCreate"
                @on-edit="openEdit" 
            >
                <template #name="{ row }">
                    <div class="flex flex-col leading-tight">
                        <span class="font-black text-gray-800 text-sm uppercase">{{ row.name }}</span>
                        <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">{{ row.keyname }}</span>
                    </div>
                </template>

                <template #creator_name="{ row }">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded-full bg-blue-50 flex items-center justify-center text-[10px]">👤</div>
                        <span class="text-gray-600 text-[10px] font-black uppercase">{{ row.creator_name || 'System' }}</span>
                    </div>
                </template>

                <template #type_name="{ row }">
                    <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-md text-[10px] font-black uppercase tracking-widest border border-blue-100">
                        {{ row.type_name }}
                    </span>
                </template>

                <template #created_at="{ row }">
                    <span class="text-gray-400 text-[10px] font-black uppercase">{{ formatDate(row.created_at) }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex gap-4 justify-end items-center mr-2">
                        <button @click="openEdit(row)" class="text-gray-300 hover:text-blue-600 transition-colors transform hover:scale-125">✏️</button>
                        <button @click="destroy(row.id)" class="text-gray-300 hover:text-red-600 transition-colors transform hover:scale-125">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>