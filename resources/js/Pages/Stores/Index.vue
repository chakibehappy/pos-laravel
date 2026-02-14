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

const showAddForm = ref(false); 
const showEditModal = ref(false); 
const showPasswordIds = ref([]); 
const activeType = ref(props.filters?.type || 'all');

const form = useForm({
    id: null,
    name: '',
    keyname: '',
    store_type_id: '',
    address: '',
    password: '', 
    created_by: '',
});

const togglePassword = (id) => {
    if (showPasswordIds.value.includes(id)) {
        showPasswordIds.value = showPasswordIds.value.filter(i => i !== id);
    } else {
        showPasswordIds.value.push(id);
    }
};

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
    showEditModal.value = false;
    showAddForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.reset('password'); 
    form.id = row.id;
    form.name = row.name;
    form.keyname = row.keyname;
    form.store_type_id = row.store_type_id;
    form.address = row.address;
    form.created_by = row.creator_name;
    
    showAddForm.value = false;
    showEditModal.value = true;
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
    <Head title="Daftar Toko" />

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
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Keyname (Slug/Kode)</label>
                        <input v-model="form.keyname" type="text" placeholder="MISAL: TOKO-PUSAT-01" class="border border-gray-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold uppercase" />
                        <span v-if="form.errors.keyname" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors.keyname }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tipe Bisnis</label>
                        <select v-model="form.store_type_id" class="border border-gray-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 font-bold outline-none">
                            <option value="">PILIH TIPE</option>
                            <option v-for="t in store_types" :key="t.id" :value="t.id">{{ t.name.toUpperCase() }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Password</label>
                        <input v-model="form.password" type="password" placeholder="MIN. 4 KARAKTER" class="border border-gray-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 font-bold outline-none" />
                        <span v-if="form.errors.password" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors.password }}</span>
                    </div>
                    <div class="md:col-span-2 flex flex-col gap-1">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Alamat</label>
                        <input v-model="form.address" type="text" class="border border-gray-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 font-bold outline-none" />
                    </div>
                    <div class="md:col-span-3 flex gap-3 mt-2 border-t pt-5">
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
                        <div class="flex flex-col">
                            <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Edit Data Toko</h2>
                            <span class="text-[9px] font-bold text-gray-400 uppercase italic">Dibuat oleh: {{ form.created_by || 'System' }}</span>
                        </div>
                        <button @click="closeForms" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
                    </div>
                    <form @submit.prevent="submit" class="p-6">
                        <div class="space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Nama Toko</label>
                                    <input v-model="form.name" type="text" class="border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold" />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Keyname</label>
                                    <input v-model="form.keyname" type="text" class="border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold bg-gray-50 uppercase" />
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tipe Bisnis</label>
                                <select v-model="form.store_type_id" class="border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 font-bold outline-none">
                                    <option v-for="t in store_types" :key="t.id" :value="t.id">{{ t.name.toUpperCase() }}</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1">
                                <div class="flex justify-between items-center">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Ganti Password</label>
                                    <span class="text-[8px] font-black text-orange-400 uppercase italic">*KOSONGKAN JIKA TIDAK DIGANTI</span>
                                </div>
                                <input v-model="form.password" type="password" placeholder="MIN. 4 KARAKTER" class="border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold" />
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
                    <label class="pl-3 text-[10px] font-black uppercase text-gray-400 tracking-widest">Jenis Usaha</label>
                    <select @change="filterByType($event.target.value)" v-model="activeType" class="bg-transparent border-none text-gray-800 text-xs rounded-lg focus:ring-0 px-4 py-2 font-black outline-none min-w-[180px] uppercase">
                        <option value="all"> SEMUA TIPE</option>
                        <option v-for="type in store_types" :key="type.id" :value="type.id.toString()">🏷️ {{ type.name }}</option>
                    </select>
                </div>
            </div>

            <DataTable 
                title="Daftar Toko"
                :resource="stores" 
                :columns="[
                    { label: 'Identitas Toko', key: 'name', sortable: true }, 
                    { label: 'Tipe', key: 'type_name', sortable: true }, 
                    { label: 'Password', key: 'password' }, 
                    { label: 'Alamat', key: 'address' },
                    { label: 'Tanggal', key: 'created_at', sortable: true },
                    { label: 'Admin', key: 'creator_name', sortable: true }
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
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="text-[9px] text-blue-500 font-black uppercase tracking-widest bg-blue-50 px-1.5 rounded">ID: {{ row.keyname || '-' }}</span>
                        </div>
                    </div>
                </template>

                <template #password="{ row }">
                    <div class="flex items-center gap-2 group min-w-[100px]">
                        <span class="text-[10px] font-mono tracking-tighter" :class="showPasswordIds.includes(row.id) ? 'text-blue-600 font-bold' : 'text-gray-300'">
                            {{ showPasswordIds.includes(row.id) ? (row.password_plain || '******') : '••••••••' }}
                        </span>
                        <button @click="togglePassword(row.id)" class="text-[10px] opacity-0 group-hover:opacity-100 transition-opacity">
                            {{ showPasswordIds.includes(row.id) ? '🙈' : '👁️' }}
                        </button>
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

                <template #creator_name="{ row }">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded-full bg-blue-100 flex items-center justify-center text-[8px]">👤</div>
                            <span class="text-gray-600 text-[10px] font-black uppercase">{{ row.creator_name || 'System' }}</span>
                        </div>
                        <span class="text-[8px] text-gray-300 font-bold uppercase ml-6">Creator</span>
                    </div>
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