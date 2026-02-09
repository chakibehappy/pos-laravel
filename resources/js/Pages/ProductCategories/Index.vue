<script setup>
import { ref } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    categories: Object, // Harus berupa LengthAwarePaginator dari Controller
    filters: Object
});

const columns = [
    { label: 'ID', key: 'id' },
    { label: 'Nama Kategori', key: 'name' }
];

const showInlineForm = ref(false); // Untuk Tambah
const showModalForm = ref(false);  // Untuk Edit

const form = useForm({
    id: null,
    name: ''
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    showModalForm.value = false;
    showInlineForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.name = row.name;
    showInlineForm.value = false;
    showModalForm.value = true;
};

const submit = () => {
    form.post(route('product-categories.store'), {
        onSuccess: () => {
            showInlineForm.value = false;
            showModalForm.value = false;
            form.reset();
        },
    });
};

const deleteCategory = (id) => {
    if (confirm('Hapus kategori ini? Semua produk terkait mungkin akan kehilangan kategorinya.')) {
        router.delete(route('product-categories.destroy', id));
    }
};
</script>

<template>
    <Head title="Kategori Produk" />

    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="showInlineForm" class="mb-8 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">➕ Tambah Kategori Baru</h2>
                    <button @click="showInlineForm = false" class="text-gray-400 hover:text-red-500 transition-colors">✕</button>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Nama Kategori</label>
                            <input v-model="form.name" type="text" placeholder="CONTOH: MAKANAN, MINUMAN, ELEKTRONIK..." 
                                class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none transition-all uppercase"
                                @keyup.enter="submit" />
                            <span v-if="form.errors.name" class="text-[10px] font-bold text-red-500 uppercase mt-1">{{ form.errors.name }}</span>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3 border-t border-gray-100 pt-6">
                        <button @click="submit" :disabled="form.processing" 
                            class="bg-blue-600 text-white px-8 py-2.5 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-sm active:scale-95 disabled:opacity-50">
                            Simpan Kategori
                        </button>
                        <button @click="showInlineForm = false" 
                            class="bg-white border border-gray-300 text-gray-600 px-8 py-2.5 rounded-lg text-xs font-bold uppercase hover:bg-gray-50 transition-all">
                            Batal
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showModalForm" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-in fade-in duration-200">
                <div class="bg-white w-full max-w-md rounded-xl shadow-2xl border border-gray-200 overflow-hidden animate-in zoom-in-95 duration-200">
                    <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                        <h2 class="text-sm font-black text-gray-700 uppercase">✏️ Edit Kategori</h2>
                        <button @click="showModalForm = false" class="text-gray-400 hover:text-red-500">✕</button>
                    </div>
                    <div class="p-8 space-y-5">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Nama Kategori</label>
                            <input v-model="form.name" type="text" 
                                class="w-full border-2 border-blue-200 rounded-lg p-3 text-lg font-black text-blue-700 focus:ring-4 focus:ring-blue-100 outline-none transition-all uppercase" 
                                @keyup.enter="submit" />
                            <span v-if="form.errors.name" class="text-[10px] font-bold text-red-500 uppercase mt-1">{{ form.errors.name }}</span>
                        </div>
                        <div class="flex gap-3 pt-4">
                            <button @click="submit" :disabled="form.processing" 
                                class="flex-1 bg-blue-600 text-white py-3 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all active:scale-95">
                                Simpan Perubahan
                            </button>
                            <button @click="showModalForm = false" 
                                class="px-6 py-3 border border-gray-300 rounded-lg text-xs font-bold uppercase text-gray-500 hover:bg-gray-50">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <DataTable 
                title="Kategori Produk"
                :resource="categories" 
                :columns="columns"
                :showAddButton="!showInlineForm"
                routeName="product-categories.index" 
                :initialSearch="filters?.search"
                @on-add="openCreate" 
            >
                <template #id="{ value }">
                    <span class="font-mono text-xs text-gray-400 font-bold">#{{ value }}</span>
                </template>

                <template #name="{ value }">
                    <span class="font-bold text-gray-800 uppercase tracking-tight">{{ value }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex flex-row gap-4 justify-end">
                        <button @click="openEdit(row)" class="text-gray-300 hover:text-blue-600 transition-colors" title="Edit">✏️</button>
                        <button @click="deleteCategory(row.id)" class="text-gray-300 hover:text-red-600 transition-colors" title="Hapus">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>