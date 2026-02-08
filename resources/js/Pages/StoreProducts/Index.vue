<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    stocks: Object,
    stores: Array,
    products: Array,
    filters: Object
});

const columns = [
    { label: 'Cabang', key: 'store_name' }, 
    { label: 'Produk', key: 'product_name' }, 
    { label: 'SKU', key: 'product_sku' }, 
    { label: 'Jumlah Stok', key: 'stock' },
    { label: 'Dibuat Oleh', key: 'creator' }
];

const errorMessage = ref('');
const showInlineForm = ref(false); // Untuk Tambah
const showModalForm = ref(false);  // Untuk Edit

// Logic Dropdown Search dalam Form
const searchQuery = ref(''); 
const showDropdown = ref(false); 
const storeSearchQuery = ref('');
const showStoreDropdown = ref(false);

const form = useForm({
    id: null,
    store_id: '',
    product_id: '',
    stock: 0,
});

const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products;
    return props.products.filter(p => 
        p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (p.sku && p.sku.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
});

const filteredStores = computed(() => {
    if (!storeSearchQuery.value) return props.stores;
    return props.stores.filter(s => 
        s.name.toLowerCase().includes(storeSearchQuery.value.toLowerCase())
    );
});

const openCreate = () => {
    errorMessage.value = '';
    form.reset();
    form.id = null;
    searchQuery.value = '';
    storeSearchQuery.value = '';
    showModalForm.value = false;
    showInlineForm.value = true; // Munculkan Inline
};

const openEdit = (row) => {
    errorMessage.value = '';
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.product_id = row.product_id;
    form.stock = row.stock;
    searchQuery.value = row.product_name; 
    storeSearchQuery.value = row.store_name; 
    showInlineForm.value = false;
    showModalForm.value = true; // Munculkan Popup
};

const selectProduct = (p) => {
    form.product_id = p.id;
    searchQuery.value = p.name;
    showDropdown.value = false;
};

const selectStore = (s) => {
    form.store_id = s.id;
    storeSearchQuery.value = s.name;
    showStoreDropdown.value = false;
};

const handleBlur = (type) => {
    setTimeout(() => { 
        if(type === 'product') showDropdown.value = false;
        if(type === 'store') showStoreDropdown.value = false;
    }, 200);
};

const submit = () => {
    errorMessage.value = '';
    form.post(route('store-products.store'), {
        onSuccess: () => {
            showInlineForm.value = false;
            showModalForm.value = false;
            form.reset();
        },
    });
};

const destroy = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus data stok ini?')) {
        router.delete(route('store-products.destroy', id));
    }
};
</script>

<template>
    <Head title="Manajemen Stok Cabang" />

    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="showInlineForm" class="mb-8 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">➕ Tambah Alokasi Baru</h2>
                    <button @click="showInlineForm = false" class="text-gray-400 hover:text-red-500 transition-colors">✕</button>
                </div>
                <div class="p-6">
                    <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
                        <p class="text-xs font-bold text-red-700 uppercase tracking-tight">{{ errorMessage }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="flex flex-col gap-1 relative">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Cabang Tujuan</label>
                            <input v-model="storeSearchQuery" @focus="showStoreDropdown = true" @blur="handleBlur('store')" type="text" placeholder="CARI CABANG..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none transition-all" />
                            <div v-if="showStoreDropdown" class="absolute z-[100] w-full bg-white border border-gray-200 rounded-lg mt-14 max-h-40 overflow-y-auto shadow-xl">
                                <div v-for="s in filteredStores" :key="s.id" @mousedown="selectStore(s)" class="p-2.5 text-xs font-bold uppercase hover:bg-blue-50 cursor-pointer border-b border-gray-50">{{ s.name }}</div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 relative">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Produk</label>
                            <input v-model="searchQuery" @focus="showDropdown = true" @blur="handleBlur('product')" type="text" placeholder="CARI PRODUK..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none transition-all" />
                            <div v-if="showDropdown" class="absolute z-[100] w-full bg-white border border-gray-200 rounded-lg mt-14 max-h-40 overflow-y-auto shadow-xl">
                                <div v-for="p in filteredProducts" :key="p.id" @mousedown="selectProduct(p)" class="p-2.5 text-xs font-bold uppercase hover:bg-blue-50 cursor-pointer border-b border-gray-50 flex justify-between">
                                    <span>{{ p.name }}</span> <span class="text-gray-400 italic font-medium">{{ p.sku }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Jumlah Stok</label>
                            <input v-model="form.stock" type="number" class="w-full border border-blue-200 bg-blue-50/30 rounded-lg p-2.5 text-sm font-bold text-blue-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all" />
                        </div>
                    </div>
                    <div class="mt-8 flex gap-3 border-t border-gray-100 pt-6">
                        <button @click="submit" :disabled="form.processing" class="bg-blue-600 text-white px-8 py-2.5 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-sm active:scale-95 disabled:opacity-50">Simpan Alokasi</button>
                        <button @click="showInlineForm = false" class="bg-white border border-gray-300 text-gray-600 px-8 py-2.5 rounded-lg text-xs font-bold uppercase hover:bg-gray-50 transition-all">Batal</button>
                    </div>
                </div>
            </div>

            <div v-if="showModalForm" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in duration-200">
                <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl border border-gray-200 overflow-hidden animate-in zoom-in-95 duration-200">
                    <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                        <h2 class="text-sm font-black text-gray-700 uppercase">✏️ Edit Alokasi Stok</h2>
                        <button @click="showModalForm = false" class="text-gray-400 hover:text-red-500">✕</button>
                    </div>
                    <div class="p-8 space-y-5">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Cabang (Disabled)</label>
                            <input :value="storeSearchQuery" disabled class="bg-gray-100 border-gray-200 rounded-lg p-3 text-sm font-bold text-gray-500 cursor-not-allowed" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Produk (Disabled)</label>
                            <input :value="searchQuery" disabled class="bg-gray-100 border-gray-200 rounded-lg p-3 text-sm font-bold text-gray-500 cursor-not-allowed" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Update Stok</label>
                            <input v-model="form.stock" type="number" class="w-full border-2 border-blue-200 rounded-lg p-3 text-lg font-black text-blue-700 focus:ring-4 focus:ring-blue-100 outline-none transition-all" />
                        </div>
                        <div class="flex gap-3 pt-4">
                            <button @click="submit" :disabled="form.processing" class="flex-1 bg-blue-600 text-white py-3 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all active:scale-95">Simpan Perubahan</button>
                            <button @click="showModalForm = false" class="px-6 py-3 border border-gray-300 rounded-lg text-xs font-bold uppercase text-gray-500 hover:bg-gray-50">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            <DataTable 
                title="Stok Produk Cabang"
                :resource="stocks" 
                :columns="columns"
                :showAddButton="!showInlineForm"
                routeName="store-products.index" 
                :initialSearch="filters.search"
                @on-add="openCreate" 
            >
                <template #stock="{ value }">
                    <span class="font-bold text-blue-600">{{ value }} <small class="text-[10px] text-gray-400 font-bold">UNIT</small></span>
                </template>
                <template #creator="{ row }">
                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase bg-gray-100 text-gray-600">👤 {{ row.creator_name || 'SYSTEM' }}</span>
                </template>
                <template #actions="{ row }">
                    <div class="flex flex-row gap-4 justify-end">
                        <button @click="openEdit(row)" class="text-gray-300 hover:text-blue-600 transition-colors">✏️</button>
                        <button @click="destroy(row.id)" class="text-gray-300 hover:text-red-600 transition-colors">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>