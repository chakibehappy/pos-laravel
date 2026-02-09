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

const columns = [
    { label: 'Nama Lengkap', key: 'name' }, 
    { label: 'Username', key: 'username' },
    { label: 'PIN', key: 'pin_status' }, 
    { label: 'Shift', key: 'shift' },
    { label: 'Jabatan (Role)', key: 'role' },
    { label: 'Status', key: 'is_active' }, // Status sekarang di posisi ke-6
    { label: 'Dibuat Oleh', key: 'created_by' }    // Oleh sekarang di posisi terakhir sebelum Action
];

// --- SEARCH ---
const search = ref(props.filters.search);
watch(search, debounce((value) => {
    router.get(route('pos_users.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

// --- FORM & MODAL LOGIC ---
const showForm = ref(false); 
const showModal = ref(false); 
const localErrors = ref({}); 

const form = useForm({
    id: null,
    name: '',
    username: '',
    pin: '',
    role: 'cashier',
    shift: 'pagi',
});

const onlyNumber = (event) => {
    if (!/[0-9]/.test(event.key)) {
        event.preventDefault();
    }
};

const openCreate = () => {
    form.reset();
    form.clearErrors();
    localErrors.value = {};
    form.id = null;
    showModal.value = false;
    showForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    localErrors.value = {};
    form.id = row.id;
    form.name = row.name;
    form.username = row.username;
    form.role = row.role;
    form.shift = row.shift || 'pagi';
    form.pin = '****'; 
    showForm.value = false;
    showModal.value = true;
};

const submit = () => {
    localErrors.value = {}; 
    let hasError = false;

    if (!form.name) { localErrors.value.name = "Nama wajib diisi!"; hasError = true; }
    if (!form.username) { localErrors.value.username = "Username wajib diisi!"; hasError = true; }

    const pinRegex = /^[0-9]+$/;
    
    if (!form.id) {
        if (!form.pin) {
            localErrors.value.pin = "PIN Wajib diisi!";
            hasError = true;
        } else if (!pinRegex.test(form.pin)) {
            localErrors.value.pin = "Gunakan angka saja!";
            hasError = true;
        } else if (form.pin.length < 4) {
            localErrors.value.pin = "Minimal 4 angka!";
            hasError = true;
        }
    } else {
        if (form.pin !== '****' && form.pin !== '') {
            if (!pinRegex.test(form.pin)) {
                localErrors.value.pin = "Gunakan angka saja!";
                hasError = true;
            } else if (form.pin.length < 4) {
                localErrors.value.pin = "Minimal 4 angka!";
                hasError = true;
            }
        }
    }

    if (hasError) return;

    if (form.id && form.pin === '****') form.pin = '';

    form.post(route('pos_users.store'), {
        onSuccess: () => {
            showForm.value = false;
            showModal.value = false;
            form.reset();
            localErrors.value = {};
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
        <div class="p-8">
            
            <div v-if="showForm" class="mb-6 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden animate-in fade-in slide-in-from-top-2 duration-300">
                <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-700 uppercase">➕ Tambah Pengguna Baru</h3>
                    <button @click="showForm = false" class="text-xs text-gray-400 hover:text-red-500 font-bold uppercase transition-colors italic">❌</button>
                </div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                    <div class="flex flex-col">
                        <label v-if="localErrors.name" class="text-[9px] text-red-500 font-bold uppercase mb-1">{{ localErrors.name }}</label>
                        <input v-model="form.name" type="text" placeholder="NAMA LENGKAP..." class="border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all" />
                    </div>
                    <div class="flex flex-col">
                        <label v-if="localErrors.username" class="text-[9px] text-red-500 font-bold uppercase mb-1">{{ localErrors.username }}</label>
                        <input v-model="form.username" type="text" placeholder="USERNAME..." class="border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all" />
                    </div>
                    <div class="flex flex-col">
                        <label v-if="localErrors.pin" class="text-[9px] text-red-500 font-bold uppercase mb-1 italic">⚠️ {{ localErrors.pin }}</label>
                        <input v-model="form.pin" type="text" inputmode="numeric" @keypress="onlyNumber" placeholder="PIN (ANGKA)..." maxlength="6" class="border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all" />
                    </div>
                    <select v-model="form.shift" class="border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white font-bold cursor-pointer h-[42px]">
                        <option value="pagi">PAGI</option>
                        <option value="malam">MALAM</option>
                    </select>
                    <select v-model="form.role" class="border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white font-bold cursor-pointer h-[42px]">
                        <option value="cashier">KASIR</option>
                        <option value="collector">KOLEKTOR</option>
                        <option value="admin">ADMIN</option>
                    </select>
                    <button @click="submit" :disabled="form.processing" class="bg-black text-white h-[42px] rounded-lg font-bold text-xs uppercase hover:bg-gray-800 transition-all shadow-sm active:scale-95">
                        Simpan
                    </button>
                </div>
            </div>

            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="bg-white w-full max-w-md rounded-xl shadow-2xl border border-gray-200 overflow-hidden animate-in zoom-in-95 duration-200">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="font-black text-gray-800 uppercase italic">✏️ Edit Informasi User</h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-black font-bold">❌</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="space-y-1">
                            <div class="flex justify-between">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-1">Nama Lengkap</label>
                                <span v-if="localErrors.name" class="text-[9px] text-red-500 font-bold uppercase">{{ localErrors.name }}</span>
                            </div>
                            <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none font-semibold transition-all" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase ml-1">Username</label>
                                    <span v-if="localErrors.username" class="text-[9px] text-red-500 font-bold uppercase">{{ localErrors.username }}</span>
                                </div>
                                <input v-model="form.username" type="text" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none font-semibold transition-all" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase ml-1">PIN (ANGKA)</label>
                                    <span v-if="localErrors.pin" class="text-[9px] text-red-500 font-bold uppercase italic">{{ localErrors.pin }}</span>
                                </div>
                                <input v-model="form.pin" type="text" inputmode="numeric" @keypress="onlyNumber" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none font-semibold transition-all" maxlength="6" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-1">Shift Kerja</label>
                                <select v-model="form.shift" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white font-bold cursor-pointer">
                                    <option value="pagi">PAGI</option>
                                    <option value="malam">MALAM</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase ml-1">Jabatan / Role</label>
                                <select v-model="form.role" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white font-bold cursor-pointer">
                                    <option value="cashier">KASIR</option>
                                    <option value="collector">KOLEKTOR</option>
                                    <option value="admin">ADMIN</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 bg-gray-50 flex gap-3 border-t border-gray-100">
                        <button @click="showModal = false" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg font-bold text-xs uppercase hover:bg-white transition-all">Batal</button>
                        <button @click="submit" :disabled="form.processing" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg font-bold text-xs uppercase hover:bg-blue-700 shadow-md transition-all active:scale-95 disabled:opacity-50">
                            Update Data
                        </button>
                    </div>
                </div>
            </div>

            <DataTable 
                title="Daftar Staff"
                :resource="resource" 
                :columns="columns"
                :showAddButton="!showForm"
                route-name="pos_users.index" 
                :initialSearch="filters.search"
                @on-add="openCreate" 
            >
                <template #username="{ row }">
                    <span class="text-gray-400 font-normal lowercase">{{ row.username }}</span>
                </template>
                
                <template #pin_status="{ row }">
                    <span class="font-mono text-[10px] bg-gray-50 px-2 py-1 rounded border border-gray-200 text-gray-400 italic">
                        {{ row.pin ? '••••••' : 'KOSONG' }}
                    </span>
                </template>

                <template #is_active="{ row }">
                    <span 
                        class="px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter border shadow-sm"
                        :class="row.is_active 
                            ? 'bg-green-50 text-green-600 border-green-200' 
                            : 'bg-red-50 text-red-600 border-red-200'"
                    >
                        {{ row.is_active ? '● Aktif' : '○ Tidak Aktif' }}
                    </span>
                </template>

                <template #created_by="{ row }">
                    <span class="text-[10px] font-semibold text-gray-400 uppercase italic tracking-tighter">👤 {{ row.creator?.name || 'System' }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex flex-row gap-4 justify-end items-center">
                        <button @click="openEdit(row)" class="text-lg hover:scale-125 transition-transform" title="Edit Data">✏️</button>
                        <button @click="destroy(row.id)" class="text-lg hover:scale-125 transition-transform" title="Hapus User">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>