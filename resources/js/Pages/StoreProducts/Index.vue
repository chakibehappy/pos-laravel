<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    stocks: Object,
    stores: Array,
    storeTypes: Array,
    products: Array,
    categories: Array,
    filters: Object
});

const selectedStore = ref(props.filters?.store_id || '');
const selectedStoreType = ref(props.filters?.store_type_id || '');
const selectedProductCategories = ref(props.filters?.product_category_id || '');

// Filter Daftar Toko berdasarkan Jenis Usaha (Untuk Dropdown Filter)
const filteredStoresList = computed(() => {
    if (!selectedStoreType.value || selectedStoreType.value === 'all') {
        return props.stores;
    }
    return props.stores.filter(s => s.store_type_id == selectedStoreType.value);
});

// Reset pilihan toko jika Jenis Usaha berubah
watch(selectedStoreType, () => {
    selectedStore.value = '';
});

watch([selectedStore, selectedStoreType, selectedProductCategories], ([newStore, newType, newCategories]) => {
    router.get(route('store-products.index'), { 
        ...props.filters, 
        store_id: newStore,
        store_type_id: (newType === 'all' || newType === '') ? null : newType,
        product_category_id: newCategories,
        page: 1 
    }, { 
        preserveState: true, 
        replace: true,
        preserveScroll: true 
    });
});

const columns = [
    { label: 'Cabang', key: 'store_name' }, 
    { label: 'Produk', key: 'product_name' }, 
    { label: 'SKU', key: 'product_sku' }, 
    { label: 'Jumlah Stok', key: 'stock' },
    { label: 'Dibuat Oleh', key: 'creator' }
];

const errorMessage = ref('');
const showInlineForm = ref(false);
const showModalForm = ref(false);

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
    showInlineForm.value = true;
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
    showModalForm.value = true;
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

            <div class="mb-6">
                <div class="inline-flex bg-white p-1.5 rounded-xl border border-gray-200 items-center gap-3 shadow-sm">
                    <label class="pl-3 text-[10px] font-black uppercase text-gray-400 tracking-widest">Jenis Usaha</label>
                    <select v-model="selectedStoreType" class="bg-transparent border-none text-gray-800 text-xs rounded-lg focus:ring-0 px-4 py-2 font-black outline-none min-w-[180px] uppercase">
                        <option value="all"> SEMUA TIPE</option>
                        <option v-for="st in storeTypes" :key="st.id" :value="st.id">🏷️ {{ st.name }}</option>
                    </select>
                </div>
            </div>
            
            <DataTable 
                title="Stok Produk Toko"
                :resource="stocks" 
                :columns="columns"
                :showAddButton="!showInlineForm"
                routeName="store-products.index" 
                :initialSearch="filters.search"
                @on-add="openCreate" 
            >
                <template #table-actions>
                    <a v-if="!showInlineForm" 
                    :href="route('store-products.export', props.filters)" 
                    class="group bg-gray-300 text-black px-6 font-bold uppercase border-2 border-black hover:bg-emerald-600 hover:text-white transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px] flex items-center justify-center gap-2 h-[44px] text-sm box-border">
                        
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            class="w-5 h-5 text-black group-hover:text-white transition-colors duration-200" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="2.5" 
                            stroke-linecap="round" 
                            stroke-linejoin="round">
                            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <path d="M8 13l4 4"/>
                            <path d="M12 13l-4 4"/>
                        </svg>

                        Eksport Excel
                    </a>
                </template>
                
                <template #extra-filters>
                    <select 
                        v-model="selectedStore"
                        class="border border-gray-300 rounded-lg p-2.5 text-sm font-medium bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none min-w-[200px] shadow-sm transition-all"
                    >
                        <option value="">-- SEMUA TOKO --</option>
                        <option v-for="s in filteredStoresList" :key="s.id" :value="s.id">
                            {{ s.name.toUpperCase() }}
                        </option>
                    </select>

                    <select 
                        v-model="selectedProductCategories"
                        class="border border-gray-300 rounded-lg p-2.5 text-sm font-medium bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none min-w-[200px] shadow-sm transition-all">
                        <option value="">-- KATEGORI PRODUK --</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">
                            {{ c.name.toUpperCase() }}
                        </option>
                    </select>
                </template>

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