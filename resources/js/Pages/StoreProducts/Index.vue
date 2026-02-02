<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    stocks: Object,
    stores: Array,
    products: Array,
    flash: Object
});

const showForm = ref(false);
const search = ref('');

// Logic Search Produk
const searchQuery = ref(''); 
const showDropdown = ref(false); 

// Logic Search Cabang (Ganti Select2)
const storeSearchQuery = ref('');
const showStoreDropdown = ref(false);

// Search table utama
watch(search, debounce((v) => {
    router.get(route('store-products.index'), { search: v }, { preserveState: true });
}, 500));

const form = useForm({
    id: null,
    store_id: '',
    product_id: '',
    stock: 0
});

// Filter Produk
const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products;
    return props.products.filter(p => 
        p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (p.sku && p.sku.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
});

// Filter Cabang
const filteredStores = computed(() => {
    if (!storeSearchQuery.value) return props.stores;
    return props.stores.filter(s => 
        s.name.toLowerCase().includes(storeSearchQuery.value.toLowerCase())
    );
});

const selectProduct = (p) => {
    form.product_id = p.id;
    searchQuery.value = p.name;
    showDropdown.value = false;
    form.clearErrors('product_id');
};

const selectStore = (s) => {
    form.store_id = s.id;
    storeSearchQuery.value = s.name;
    showStoreDropdown.value = false;
    form.clearErrors('store_id');
};

const handleBlur = (type) => {
    setTimeout(() => { 
        if(type === 'product') showDropdown.value = false;
        if(type === 'store') showStoreDropdown.value = false;
    }, 200);
};

const submit = () => {
    form.post(route('store-products.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
            searchQuery.value = '';
            storeSearchQuery.value = '';
        },
    });
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.product_id = row.product_id;
    form.stock = row.stock;
    searchQuery.value = row.product_name; 
    storeSearchQuery.value = row.store_name; // Isi nama cabang saat edit
    showForm.value = true;
};

const deleteStock = (id) => {
    if (confirm('Apakah Anda yakin ingin menarik kembali stok ini ke gudang pusat?')) {
        router.delete(route('store-products.destroy', id));
    }
};
</script>

<template>
    <Head title="Manajemen Stok Cabang" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-6">
                <div>
                    <h1 class="text-6xl font-black uppercase italic tracking-tighter leading-none">
                        Stok <span class="text-yellow-400 drop-shadow-[4px_4px_0_#000]">Cabang</span>
                    </h1>
                </div>
                <button @click="showForm = true; form.reset(); form.id = null; searchQuery = ''; storeSearchQuery = ''" 
                    class="bg-black text-white px-10 py-4 font-black text-xl border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-2 hover:translate-y-2 transition-all uppercase">
                    + Alokasi Stok
                </button>
            </div>

            <div class="mb-8 text-left">
                <input v-model="search" type="text" placeholder="Cari cabang atau produk..." 
                    class="w-full md:w-1/3 border-4 border-black p-4 font-black uppercase outline-none focus:bg-yellow-50 transition-all">
            </div>

            <div class="bg-white border-4 border-black shadow-[12px_12px_0px_0px_rgba(0,0,0,1)]">
                <DataTable :resource="stocks" :columns="[{ label: 'Cabang', key: 'store_name' }, { label: 'Produk', key: 'product_name' }, { label: 'SKU', key: 'product_sku' }, { label: 'Jumlah Stok', key: 'stock' }]">
                    <template #stock="{ row }">
                        <span class="bg-yellow-300 border-2 border-black px-4 py-1 font-black text-xl shadow-[3px_3px_0_#000]">
                            {{ row.stock }}
                        </span>
                    </template>
                    <template #actions="{ row }">
                        <div class="flex gap-4 justify-end">
                            <button @click="openEdit(row)" class="font-black text-xs uppercase hover:underline text-blue-600">Edit</button>
                            <button @click="deleteStock(row.id)" class="text-red-500 font-black text-xs uppercase hover:bg-black hover:text-white px-1 transition-all">Tarik Stok</button>
                        </div>
                    </template>
                </DataTable>
            </div>

            <div v-if="showForm" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-[60] flex items-center justify-center p-4">
                <div class="bg-white border-[8px] border-black p-10 w-full max-w-2xl shadow-[20px_20px_0px_0px_rgba(255,215,0,1)] relative">
                    
                    <button @click="showForm = false" class="absolute top-4 right-4 font-black text-2xl">✕</button>
                    
                    <h2 class="text-4xl font-black uppercase italic mb-8 underline decoration-yellow-400 decoration-8">
                        {{ form.id ? 'Update Alokasi' : 'Kirim Stok Baru' }}
                    </h2>

                    <div v-if="form.errors.message" class="bg-red-500 text-white p-4 mb-6 font-black uppercase border-4 border-black shadow-[4px_4px_0_#000]">
                        ⚠️ {{ form.errors.message }}
                    </div>
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div class="relative text-left">
                            <label class="block font-black uppercase mb-2 italic">Cabang Tujuan</label>
                            <input 
                                v-model="storeSearchQuery" 
                                @focus="showStoreDropdown = true"
                                @blur="handleBlur('store')"
                                type="text" 
                                placeholder="CARI NAMA CABANG..."
                                class="w-full border-4 border-black p-4 font-black uppercase outline-none bg-gray-50 focus:bg-white"
                                :disabled="form.id"
                            />
                            <div v-if="showStoreDropdown && !form.id" 
                                class="absolute z-[110] w-full bg-white border-4 border-black mt-1 max-h-48 overflow-y-auto shadow-[8px_8px_0_#000]">
                                <div v-for="s in filteredStores" :key="s.id"
                                    @mousedown="selectStore(s)"
                                    class="p-4 font-black uppercase border-b-2 border-gray-100 hover:bg-yellow-400 cursor-pointer transition-all"
                                >
                                    {{ s.name }}
                                </div>
                            </div>
                            <p v-if="form.errors.store_id" class="text-red-600 font-black text-xs mt-1 uppercase italic">Wajib pilih cabang</p>
                        </div>

                        <div class="relative text-left">
                            <label class="block font-black uppercase mb-2 italic">Produk (Cari Nama/SKU)</label>
                            <input 
                                v-model="searchQuery" 
                                @focus="showDropdown = true"
                                @blur="handleBlur('product')"
                                type="text" 
                                placeholder="KETIK NAMA PRODUK..."
                                class="w-full border-4 border-black p-4 font-black uppercase outline-none bg-gray-50 focus:bg-white"
                                :disabled="form.id"
                            />
                            <div v-if="showDropdown && !form.id" 
                                class="absolute z-[100] w-full bg-white border-4 border-black mt-1 max-h-60 overflow-y-auto shadow-[8px_8px_0_#000]">
                                <div v-for="p in filteredProducts" :key="p.id"
                                    @mousedown="selectProduct(p)"
                                    class="p-4 font-black uppercase border-b-2 border-gray-100 hover:bg-yellow-400 cursor-pointer flex justify-between items-center transition-all"
                                >
                                    <div>
                                        <div class="text-sm">{{ p.name }}</div>
                                        <div class="text-[10px] text-gray-500 italic">SKU: {{ p.sku || '-' }}</div>
                                    </div>
                                    <span class="bg-black text-white px-2 py-1 text-[10px]">GUDANG: {{ p.stock }}</span>
                                </div>
                            </div>
                            <p v-if="form.errors.product_id" class="text-red-600 font-black text-xs mt-1 uppercase italic">Wajib pilih produk</p>
                        </div>

                        <div class="text-left">
                            <label class="block font-black uppercase mb-2 italic">Jumlah Stok di Cabang</label>
                            <input v-model="form.stock" type="number" min="0" class="w-full border-4 border-black p-4 font-black text-4xl outline-none shadow-[inner_4px_4px_0_#ccc]">
                            <p v-if="form.errors.stock" class="text-red-600 font-black text-xs mt-1 uppercase italic">{{ form.errors.stock }}</p>
                        </div>

                        <div class="flex gap-4 pt-6">
                            <button type="submit" :disabled="form.processing" class="flex-1 bg-black text-white p-5 font-black uppercase text-xl border-4 border-black shadow-[6px_6px_0_#000] active:translate-x-1 active:translate-y-1 transition-all disabled:opacity-50">
                                {{ form.id ? 'SIMPAN PERUBAHAN' : 'KONFIRMASI PENGIRIMAN' }}
                            </button>
                            <button type="button" @click="showForm = false" class="bg-white text-black px-8 py-5 font-black uppercase border-4 border-black hover:bg-gray-100 transition-all text-xl">
                                BATAL
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: #f1f1f1; }
::-webkit-scrollbar-thumb { background: #000; }
</style>