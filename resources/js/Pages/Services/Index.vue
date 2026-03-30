<script setup>
import { ref } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    services: Object,
    filters: Object
});

// Konfigurasi Kolom DataTable
const columns = [
    { label: 'Nama Layanan', key: 'name', sortable: true },
    { label: 'Deskripsi', key: 'description', sortable: false },
    { label: 'Harga (Rp)', key: 'price', sortable: true },
    { label: 'Dibuat Oleh', key: 'user.name', sortable: false }, 
    { label: 'Tanggal', key: 'created_at', sortable: true },
];

const showForm = ref(false);
const isEditMode = ref(false);
const showDeleteModal = ref(false);
const errorMessage = ref('');

// Form Utama (Create/Update)
const form = useForm({
    id: null,
    name: '',
    description: '',
    price: 0,
});

// State untuk Konfirmasi Hapus
const deleteData = ref({
    id: null,
    name: ''
});

const openCreate = () => {
    isEditMode.value = false;
    form.reset();
    showForm.value = true;
};

const openEdit = (row) => {
    isEditMode.value = true;
    form.id = row.id;
    form.name = row.name;
    form.description = row.description;
    form.price = row.price;
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    form.reset();
    errorMessage.value = '';
};

const submit = () => {
    const options = {
        onSuccess: () => closeForm(),
        onError: (err) => {
            errorMessage.value = Object.values(err)[0] || "Gagal menyimpan data.";
        }
    };

    if (isEditMode.value) {
        form.post(route('services.update', form.id), options);
    } else {
        form.post(route('services.store'), options);
    }
};

// Konfirmasi Hapus
const confirmDelete = (row) => {
    deleteData.value = { id: row.id, name: row.name };
    showDeleteModal.value = true;
};

const submitDelete = () => {
    router.delete(route('services.destroy', deleteData.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            deleteData.value = { id: null, name: '' };
        },
    });
};
</script>

<template>
    <Head title="Manajemen Layanan" />

    <AuthenticatedLayout>
        <div class="p-8">
            <!-- MODAL FORM (CREATE/EDIT) -->
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm animate-in fade-in duration-200">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 relative animate-in zoom-in duration-250">
                    <div class="p-6 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center">
                        <h2 class="text-lg font-black uppercase tracking-tighter text-gray-800">
                            {{ isEditMode ? 'Update Layanan' : 'Tambah Layanan Baru' }}
                        </h2>
                        <button @click="closeForm" class="w-8 h-8 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-100 transition-all shadow-sm">✕</button>
                    </div>

                    <div class="p-6 space-y-5">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Nama Layanan</label>
                            <input v-model="form.name" type="text" placeholder="Masukkan nama layanan..." class="border border-gray-200 rounded-xl p-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                            <span v-if="form.errors.name" class="text-red-500 text-[10px] font-bold uppercase italic mt-1">{{ form.errors.name }}</span>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Harga Jasa (Rp)</label>
                            <input v-model.number="form.price" type="number" class="border border-gray-200 rounded-xl p-3 text-sm font-black text-blue-600 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                            <span v-if="form.errors.price" class="text-red-500 text-[10px] font-bold uppercase italic mt-1">{{ form.errors.price }}</span>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Deskripsi Pekerjaan</label>
                            <textarea v-model="form.description" rows="3" placeholder="Jelaskan detail layanan..." class="border border-gray-200 rounded-xl p-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all resize-none"></textarea>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50 border-t border-gray-100 flex items-center gap-3">
                        <div v-if="errorMessage" class="flex-1 bg-red-50 text-red-600 text-[10px] font-bold uppercase p-2 rounded-lg border border-red-100">
                            ⚠️ {{ errorMessage }}
                        </div>
                        <button @click="submit" :disabled="form.processing" class="flex-1 px-6 py-3 bg-gray-900 text-white rounded-xl font-black uppercase text-[11px] tracking-widest hover:bg-blue-600 transition-all shadow-lg shadow-gray-200 disabled:opacity-50">
                            {{ form.processing ? 'Memproses...' : (isEditMode ? 'Simpan Perubahan' : 'Posting Layanan') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL KONFIRMASI HAPUS -->
            <div v-if="showDeleteModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm animate-in fade-in duration-200">
                <div class="bg-white p-8 rounded-2xl max-w-sm w-full shadow-2xl border border-gray-100 animate-in zoom-in duration-250">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-5 text-2xl rotate-3 shadow-inner">🗑️</div>
                        <h3 class="text-xl font-black text-gray-900 uppercase tracking-tighter">Hapus Layanan?</h3>
                        <p class="text-xs text-gray-400 mt-2 leading-relaxed">Data <span class="font-bold text-gray-800 italic">"{{ deleteData.name }}"</span> akan dihapus permanen dari sistem.</p>
                    </div>

                    <div class="mt-8 flex flex-col gap-2">
                        <button @click="submitDelete" class="w-full py-3.5 bg-red-500 text-white rounded-xl text-[11px] font-black uppercase tracking-widest hover:bg-red-600 transition-all shadow-lg shadow-red-100">
                            Ya, Hapus Sekarang
                        </button>
                        <button @click="showDeleteModal = false" class="w-full py-3 text-[11px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-600 transition-colors">
                            Batalkan
                        </button>
                    </div>
                </div>
            </div>

            <!-- DATA TABLE -->
            <DataTable 
                title="Service Management" 
                :resource="services" 
                :columns="columns" 
                :filters="filters"
                :showAddButton="true" 
                route-name="services.index" 
                :initial-search="filters?.search || ''"
                @on-add="openCreate"
            >
                <template #name="{ value }">
                    <span class="font-black text-gray-800 uppercase tracking-tight">{{ value }}</span>
                </template>

                <template #description="{ value }">
                    <span class="text-gray-400 text-[11px] italic truncate max-w-[200px] inline-block">{{ value || 'Tidak ada deskripsi' }}</span>
                </template>

                <template #price="{ value }">
                    <div class="bg-blue-50 px-3 py-1 rounded-lg border border-blue-100 inline-block">
                        <span class="font-black text-blue-700 text-xs">
                            Rp {{ Number(value).toLocaleString('id-ID') }}
                        </span>
                    </div>
                </template>

                <!-- Template Slot Created By -->
                <template #["user.name"]="{ row }">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-gray-900 flex items-center justify-center text-[10px] font-black text-white shadow-sm uppercase italic">
                            {{ row.user?.name?.charAt(0) || 'S' }}
                        </div>
                        <span class="text-gray-600 text-[10px] font-black uppercase tracking-tight">{{ row.user?.name || 'SYSTEM' }}</span>
                    </div>
                </template>

                <template #created_at="{ value }">
                    <span class="text-gray-400 text-[10px] font-bold uppercase">
                        {{ new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                    </span>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center gap-3 justify-end">
                        <button @click="openEdit(row)" class="w-8 h-8 flex items-center justify-center bg-white text-gray-400 rounded-lg border border-gray-100 hover:text-blue-600 hover:border-blue-200 hover:shadow-sm transition-all text-xs">
                            ✏️
                        </button>
                        <button @click="confirmDelete(row)" class="w-8 h-8 flex items-center justify-center bg-white text-gray-400 rounded-lg border border-gray-100 hover:text-red-600 hover:border-red-200 hover:shadow-sm transition-all text-xs">
                            ❌
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>