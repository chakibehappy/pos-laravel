<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import axios from 'axios';

const props = defineProps({
    stocks: Object,
    all_stocks_reference: Array, 
    stores: Array,
    storeTypes: Array,
    products: Array, 
    categories: Array,
    filters: Object
});

// --- STATE UNTUK MINIMIZE FOTO (DEFAULT TRUE / MINIMIZED) ---
const isPhotoMinimized = ref(true);

// --- HELPER UNTUK FIX ERROR setTimeout DI TEMPLATE ---
const closeStoreDropdown = () => {
    setTimeout(() => { showStoreDropdown.value = false; }, 200);
};

const closeProductDropdown = () => {
    setTimeout(() => { showDropdown.value = false; }, 200);
};

// --- FILTER STATE ---
const selectedStore = ref(props.filters?.store_id || '');
const selectedStoreType = ref(props.filters?.store_type_id || '');
const selectedProductCategories = ref(props.filters?.product_category_id || '');

const getCurrentParams = () => {
    return {
        ...props.filters,
        store_id: selectedStore.value,
        store_type_id: (selectedStoreType.value === 'all' || selectedStoreType.value === '') ? null : selectedStoreType.value,
        product_category_id: selectedProductCategories.value,
    };
};

const filteredStoresList = computed(() => {
    if (!selectedStoreType.value || selectedStoreType.value === 'all') return props.stores;
    return props.stores.filter(s => s.store_type_id == selectedStoreType.value);
});

watch(selectedStoreType, () => { selectedStore.value = ''; });

watch([selectedStore, selectedStoreType, selectedProductCategories], () => {
    router.get(route('store-products.index'), getCurrentParams(), { 
        preserveState: true, 
        replace: true, 
        preserveScroll: true 
    });
});

const columns = [
    { label: 'Cabang', key: 'store_name', sortable: true }, 
    { label: 'Produk', key: 'product_name', sortable: true }, 
    { label: 'SKU', key: 'product_sku', sortable: true },
    { label: 'Modal', key: 'product_buying_price', sortable: true },
    { label: 'Harga Jual', key: 'product_selling_price', sortable: true }, 
    { label: 'Jumlah Stok', key: 'stock', sortable: true },
    { label: 'Dibuat Oleh', key: 'creator' }
];

const showInlineForm = ref(false);
const showModalForm = ref(false);

const searchQuery = ref(''); 
const showDropdown = ref(false); 
const storeSearchQuery = ref('');
const showStoreDropdown = ref(false);

const imagePreview = ref(null);
const selectedReferenceName = ref('');

// Form utama untuk pengiriman batch & edit data single
const form = useForm({ 
    id: null, 
    store_type_id: '',
    store_id: '', 
    supplier_name: '', // Ditambahkan state untuk nama supplier
    product_id: '', 
    stock: 0 
});

// State untuk sistem Keranjang Baru (Batch Mode)
const batchItems = ref([]);
const inputForm = ref({ 
    product_reference_id: '', 
    product_category_id: '', 
    store_type_id: '', 
    type_stock: '0', 
    unit_type_id: '', 
    name: '', 
    sku: '', 
    buying_price: 0, 
    selling_price: 0, 
    current_stock: 0, 
    stock: 0, 
    image: null, 
    image_preview_url: null 
});

const formFields = computed(() => [
    { name: 'sku', label: 'SKU / Kode Barang', type: 'text', placeholder: 'Contoh: BRG-001' },
    { name: 'product_category_id', label: 'Kategori', type: 'select', options: props.categories },
    { name: 'type_stock', label: 'Jenis Stok', type: 'select', options: [{ id: '0', name: 'TERBATAS' }, { id: '1', name: 'TIDAK TERBATAS' }] },
    { name: 'buying_price', label: 'Harga Modal (Beli)', type: 'number', class: 'text-blue-600' },
    { name: 'selling_price', label: 'Harga Jual', type: 'number', class: 'text-green-600' }
]);

// Memfilter toko berdasarkan Jenis Usaha di dalam inline form penambahan baru
const filteredStores = computed(() => {
    if (!form.store_type_id) return props.stores || [];
    return (props.stores || []).filter(s => String(s.store_type_id) === String(form.store_type_id));
});

// Pencarian produk referensi dari daftar produk terdaftar
const filteredProductsReference = computed(() => {
    const refs = props.products || [];
    if (!searchQuery.value) return refs;
    const q = searchQuery.value.toLowerCase().trim();
    return refs.filter(p => {
        const productName = p.name ? p.name.toLowerCase().trim() : '';
        const productSku = p.sku ? p.sku.toLowerCase().trim() : '';
        return productName.startsWith(q) || productSku.startsWith(q);
    });
});

watch(() => form.store_type_id, () => {
    form.store_id = '';
});

watch(searchQuery, (newVal) => {
    inputForm.value.name = newVal;
    if (!newVal || newVal.trim() === '' || (inputForm.value.product_reference_id && newVal !== selectedReferenceName.value)) {
        resetInputForm(false);
    }
});

watch(() => form.store_id, async (newStoreId) => {
    if (newStoreId) {
        if (inputForm.value.product_reference_id) {
            await fetchCurrentStock(inputForm.value.product_reference_id, newStoreId);
        } else {
            inputForm.value.current_stock = 0;
        }
    } else {
        inputForm.value.current_stock = 0;
    }
});

const fetchCurrentStock = async (productReferenceId, storeId) => {
    if (!productReferenceId || !storeId) {
        inputForm.value.current_stock = 0;
        return;
    }
    try {
        const response = await axios.get(route('store-products.get-stock'), {
            params: { product_id: productReferenceId, store_id: storeId }
        });
        inputForm.value.current_stock = response.data.stock || 0;
    } catch (error) {
        console.error('Gagal mengambil data stok:', error);
        inputForm.value.current_stock = 0;
    }
};

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    form.store_type_id = '';
    form.store_id = '';
    form.supplier_name = '';
    batchItems.value = [];
    isPhotoMinimized.value = true;
    resetInputForm(true);
    showModalForm.value = false;
    showInlineForm.value = true;
};

const closeInline = () => {
    showInlineForm.value = false;
    batchItems.value = [];
    form.reset();
    form.store_id = '';
    form.store_type_id = '';
    form.supplier_name = '';
    resetInputForm(true);
};

const openEdit = (row) => {
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

const selectProduct = async (p) => {
    ['id:product_reference_id', 'name', 'sku', 'buying_price', 'selling_price', 'product_category_id', 'type_stock'].forEach(k => {
        const [pk, fk] = k.includes(':') ? k.split(':') : [k, k];
        if (fk === 'type_stock' && p[pk] !== null && p[pk] !== undefined) {
            inputForm.value[fk] = String(p[pk]); 
        } else {
            inputForm.value[fk] = p[pk] || (typeof inputForm.value[fk] === 'number' ? 0 : '');
        }
    });
    
    selectedReferenceName.value = p.name;
    searchQuery.value = p.name;
    showDropdown.value = false;
    
    if (form.store_id) {
        await fetchCurrentStock(p.id, form.store_id);
    } else {
        inputForm.value.current_stock = 0;
    }
};

const resetInputForm = (forceClearSearch = false) => {
    inputForm.value = { 
        product_reference_id: '', 
        product_category_id: '', 
        store_type_id: '', 
        type_stock: '0', 
        unit_type_id: '', 
        name: forceClearSearch ? '' : searchQuery.value, 
        sku: '', 
        buying_price: 0, 
        selling_price: 0, 
        current_stock: 0, 
        stock: 0, 
        image: null, 
        image_preview_url: null 
    };
    
    if (forceClearSearch) {
        imagePreview.value = null;
        searchQuery.value = '';
        selectedReferenceName.value = '';
    } else if (!searchQuery.value) {
        selectedReferenceName.value = '';
    }
};

const addToCart = () => {
    if (!inputForm.value.name) return alert('Nama produk tidak boleh kosong!');
    if (!inputForm.value.stock || Number(inputForm.value.stock) <= 0) return alert('Jumlah stok tambahan tidak boleh 0 atau kosong!');
    
    const determinedTab = inputForm.value.product_reference_id ? 'terdaftar' : 'belum_terdaftar';
    const cObj = props.categories.find(c => c.id === inputForm.value.product_category_id);
    
    batchItems.value.push({ 
        ...inputForm.value, 
        status_tab: determinedTab, 
        category_name: cObj ? cObj.name.toUpperCase() : '-' 
    });
    
    resetInputForm(false);
};

const removeCartItem = (i) => batchItems.value.splice(i, 1);

const handleFileChange = (e) => {
    const f = e.target.files[0];
    if (!showModalForm.value) { 
        inputForm.value.image = f; 
        if (f) inputForm.value.image_preview_url = imagePreview.value = URL.createObjectURL(f); 
    }
};
const submitBatch = () => {
    if (!form.store_id) return alert('Silakan pilih alokasi toko terlebih dahulu!');
    if (!batchItems.value.length) return alert('Keranjang batch kosong! Tambahkan minimal satu produk.');
    
    const cleanBatchItems = batchItems.value.map(item => {
        const currentStore = props.stores.find(s => s.id === form.store_id);
        return {
            store_id: form.store_id,
            product_id: item.product_reference_id || null, // Nilai null menandakan produk baru ke backend
            name: item.name,
            sku: item.sku || '',
            product_category_id: item.product_category_id || null,
            type_stock: item.type_stock || '0',
            buying_price: Number(item.buying_price) || 0,
            selling_price: Number(item.selling_price) || 0,
            stock: Number(item.stock) || 0,
            store_type_id: form.store_type_id || (currentStore ? currentStore.store_type_id : null)
        };
    });

    router.post(route('store-products.store'), {
        store_id: form.store_id,
        supplier_name: form.supplier_name, 
        batch: cleanBatchItems 
    }, {
        preserveScroll: true,
        onBefore: () => { form.processing = true; },
        onFinish: () => { form.processing = false; },
        onSuccess: () => { 
            showInlineForm.value = false; 
            batchItems.value = []; 
            form.reset(); 
            form.store_id = '';
            form.store_type_id = '';
            form.supplier_name = '';
            resetInputForm(true); 
        }
    });
};

const submitSingleEdit = () => {
    form.post(route('store-products.store'), {
        onSuccess: () => { 
            showModalForm.value = false; 
            form.reset(); 
        },
    });
};

const destroy = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus data stok ini? (Data akan diarsipkan)')) {
        router.delete(route('store-products.destroy', id), {
            preserveScroll: true
        });
    }
};

const handleExport = () => {
    window.location.href = route('store-products.export', getCurrentParams());
};

const totalBatchPurchase = computed(() => {
    return batchItems.value.reduce((sum, item) => {
        const qty = Number(item.stock) || 0;
        const price = Number(item.buying_price) || 0;
        return sum + (qty * price);
    }, 0);
});
</script>

<template>
    <Head title="Manajemen Stok Cabang" />

    <AuthenticatedLayout page-title="Manajemen Stok Cabang" page-subtitle="Maar Company">
        <div class="p-8">
            <div v-if="showInlineForm" class="mb-8 bg-white rounded-xl border border-gray-200 shadow-sm animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="bg-gray-50 border-b px-6 py-4 flex flex-row justify-between items-center text-sm font-bold uppercase tracking-widest text-gray-700 rounded-t-xl overflow-hidden">
                    <div>
                        <span class="whitespace-nowrap">➕ Tambahkan Data Produk Ke Cabang (Batch Mode)</span>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="flex items-center normal-case tracking-normal">
                            <select v-model="form.store_type_id" :class="['border border-gray-300 rounded p-1.5 text-xs uppercase bg-white focus:ring-1 focus:ring-blue-500 outline-none font-bold w-64 shadow-sm', form.store_type_id ? 'text-black' : 'text-gray-400']">
                                <option value="" class="text-gray-400">-- SEMUA JENIS USAHA --</option>
                                <option v-for="st in storeTypes" :key="st.id" :value="st.id" class="text-black">
                                    {{ st.name.toUpperCase() }}
                                </option>
                            </select>
                        </div>
                        <button @click="closeInline" class="text-gray-400 hover:text-red-500 transition-colors text-base font-normal">✕</button>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="w-full mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1 w-full">
                            <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Alokasikan Untuk Toko / Cabang</label>
                            <select v-model="form.store_id" :class="['border border-gray-300 rounded p-2 text-sm uppercase bg-white focus:ring-1 focus:ring-blue-500 outline-none font-bold w-full', form.store_id ? 'text-black' : 'text-gray-400']">
                                <option value="" class="text-gray-400">PILIH TOKO</option>
                                <option v-for="s in filteredStores" :key="s.id" :value="s.id" class="text-black">{{ s.name.toUpperCase() }}</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1 w-full">
                            <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Nama Supplier</label>
                            <input v-model="form.supplier_name" type="text" placeholder="MASUKKAN NAMA SUPPLIER" class="border border-gray-300 rounded p-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none font-bold uppercase bg-white w-full" />
                        </div>
                    </div>

                    <div v-if="!form.store_id" class="p-8 text-center border border-dashed border-dashed border-gray-300 rounded-xl bg-gray-50 my-6 text-xs font-black uppercase tracking-widest text-gray-400">
                        ⚠️ PILIH TERLEBIH DAHULU TOKO YANG AKAN DIALOKASIKAN
                    </div>

                    <div v-else class="flex flex-col md:flex-row gap-6 w-full relative z-[50]" :class="isPhotoMinimized ? 'items-start' : ''">
                        <div class="flex-shrink-0 flex flex-col transition-all duration-200" :class="isPhotoMinimized ? 'w-full md:w-40' : 'w-full md:w-56'">
                            <div class="flex items-center justify-between mb-1 gap-2">
                                <label class="block text-[10px] font-black uppercase text-gray-400 tracking-widest whitespace-nowrap">Foto</label>
                                <button type="button" @click="isPhotoMinimized = !isPhotoMinimized" class="text-xs text-gray-400 hover:text-blue-600 font-bold focus:outline-none select-none px-1">
                                    {{ isPhotoMinimized ? '🔽' : '🔼' }}
                                </button>
                            </div>
                            
                            <div v-if="!isPhotoMinimized" 
                                :class="['border-2 border-dashed border-gray-200 rounded-xl flex items-center justify-center overflow-hidden relative group transition-colors w-full h-56', inputForm.product_reference_id ? 'bg-gray-100' : 'bg-gray-50 hover:bg-gray-100']">
                                <img v-if="imagePreview" :src="imagePreview" class="object-cover w-full h-full" />
                                <span v-else class="text-[9px] font-bold text-gray-300 uppercase text-center p-2 leading-tight">Klik/Seret Foto</span>
                                <input type="file" :disabled="!!inputForm.product_reference_id" @change="handleFileChange" class="absolute inset-0 opacity-0" />
                            </div>

                            <div v-else 
                                :class="['border border-gray-300 rounded-lg flex items-center justify-between w-full h-9 px-2 relative group overflow-hidden', inputForm.product_reference_id ? 'bg-gray-100' : 'bg-gray-50']">
                                <div class="flex items-center gap-2 overflow-hidden mr-2">
                                    <img v-if="imagePreview" :src="imagePreview" class="w-5 h-5 object-cover rounded border border-gray-200 flex-shrink-0" />
                                    <span v-else class="text-xs flex-shrink-0">🖼️</span>
                                    <span class="text-[10px] font-bold text-gray-400 truncate uppercase">
                                        {{ imagePreview ? 'Terpilih' : 'Kosong' }}
                                    </span>
                                </div>
                                <input type="file" :disabled="!!inputForm.product_reference_id" @change="handleFileChange" class="absolute inset-0 opacity-0" />
                            </div>
                        </div>

                        <div class="flex-1 flex flex-col justify-between w-full">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="flex flex-col gap-1 relative md:col-span-2">
                                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Nama Produk</label>
                                    <input v-model="searchQuery" @focus="showDropdown = true" @blur="closeProductDropdown" type="text" placeholder="MASUKKAN NAMA PRODUK" class="border border-gray-300 rounded p-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none font-bold uppercase bg-white w-full" />
                                    
                                    <div v-if="showDropdown" class="absolute left-0 right-0 top-[100%] z-[100] bg-white border border-gray-200 rounded-lg mt-1 max-h-60 overflow-y-auto shadow-2xl">
                                        <div v-for="p in filteredProductsReference" :key="p.id" @mousedown="selectProduct(p)" class="p-3 text-xs font-bold uppercase hover:bg-blue-50 cursor-pointer border-b border-gray-50 flex justify-between items-center">
                                            <div class="flex flex-col text-gray-800 gap-0.5 text-left">
                                                <span>{{ p.name }}</span>
                                                <span class="text-blue-600 text-[10px] font-black">MODAL: RP {{ Number(p.buying_price).toLocaleString('id-ID') }}</span>
                                                <span class="text-green-600 text-[10px] font-black">J: RP {{ Number(p.selling_price).toLocaleString('id-ID') }}</span>
                                            </div>
                                        </div>
                                        <div v-if="filteredProductsReference.length === 0" class="p-3 text-xs text-gray-400 font-bold text-center uppercase">
                                            Produk tidak ditemukan
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-1 md:col-span-1">
                                    <label class="text-[10px] font-black uppercase text-blue-600 tracking-widest">
                                        STOK SAAT INI: <span class="text-red-600">({{ Number(inputForm.current_stock || 0).toLocaleString('id-ID') }} UNIT)</span>
                                    </label>
                                    <input v-model.number="inputForm.stock" type="number" min="1" placeholder="0" class="border border-blue-300 focus:ring-1 focus:ring-blue-500 text-blue-700 font-black rounded p-2 text-sm outline-none bg-white w-full" />
                                </div>

                                <div v-for="field in formFields" :key="field.name" class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">{{ field.label }}</label>
                                
                                <select v-if="field.type === 'select'" 
                                        v-model="inputForm[field.name]" 
                                        :disabled="!!inputForm.product_reference_id"
                                        :class="['border border-gray-300 rounded p-2 text-sm uppercase focus:ring-1 focus:ring-blue-500 outline-none font-bold', inputForm.product_reference_id ? 'bg-gray-100 text-gray-500' : 'bg-white text-black']">
                                    <option value="" class="text-gray-400">PILIH {{ field.label }}</option>
                                    <option v-for="opt in field.options" :key="opt.id" :value="opt.id">{{ opt.name.toUpperCase() }}</option>
                                </select>
                                
                                <input v-else 
                                    v-model="inputForm[field.name]" 
                                    :type="field.type" 
                                    :placeholder="field.placeholder" 
                                    :readonly="field.name === 'sku' && !!inputForm.product_reference_id"
                                    :class="[
                                        'border border-gray-300 rounded p-2 text-sm outline-none font-bold uppercase w-full', 
                                        field.class, 
                                        (field.name === 'sku' && !!inputForm.product_reference_id) 
                                            ? 'bg-gray-100 text-gray-500 focus:ring-0 focus:border-gray-300' 
                                            : 'bg-white text-black focus:ring-1 focus:ring-blue-500'
                                    ]" />
                            </div>
                            </div>
                            <div class="w-full flex justify-end mt-4">
                                <button @click="addToCart" type="button" class="border border-blue-600 text-blue-600 px-5 py-2 rounded text-xs font-black uppercase hover:bg-blue-50 transition-all whitespace-nowrap">+ Tambahkan Ke Keranjang</button>
                            </div>
                        </div>
                    </div>

                    <div v-if="batchItems.length > 0" class="mt-8 mb-6 animate-in fade-in duration-200">
                        <div class="border border-gray-300 rounded-lg overflow-hidden bg-gray-50">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-600 tracking-widest border-b border-gray-300">
                                        <th v-for="h in ['Status','Foto','Nama Produk','SKU','Kategori','Harga Modal','Harga Jual','Stok Saat Ini','Stok Tambahan','']" :key="h" class="py-3 px-4" :class="{'w-24': h==='Foto', 'w-12 text-center': h===''}">{{ h }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in batchItems" :key="idx" class="border-b border-gray-200 last:border-none bg-white text-xs font-bold text-gray-700 uppercase">
                                        <td class="py-3 px-4">
                                            <span :class="['text-[9px] font-black uppercase px-2 py-0.5 rounded border whitespace-nowrap', item.status_tab === 'terdaftar' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-amber-50 text-amber-700 border-amber-200']">{{ item.status_tab === 'terdaftar' ? 'TERDAFTAR' : 'BARU' }}</span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <img v-if="item.image_preview_url" :src="item.image_preview_url" class="w-10 h-10 object-cover rounded border border-gray-200 shadow-sm" />
                                            <div v-else class="w-10 h-10 bg-gray-100 rounded border border-gray-200 flex items-center justify-center text-[8px] text-gray-400 font-bold">NO IMG</div>
                                        </td>
                                        <td class="py-3 px-4">{{ item.name || '-' }}</td>
                                        <td class="py-3 px-4">{{ item.sku || '-' }}</td>
                                        <td class="py-3 px-4"><span class="text-[9px] font-black uppercase px-2 py-1 bg-blue-50 text-blue-600 rounded-md border border-blue-100">{{ item.category_name }}</span></td>
                                        <td class="py-3 px-4 text-blue-600">Rp {{ Number(item.buying_price).toLocaleString('id-ID') }}</td>
                                        <td class="py-3 px-4 text-green-600">Rp {{ Number(item.selling_price).toLocaleString('id-ID') }}</td>
                                        <td class="py-3 px-4 text-gray-400 font-bold">{{ Number(item.current_stock || 0).toLocaleString('id-ID') }}</td>
                                        <td class="py-3 px-4 text-blue-600 font-black">{{ Number(item.stock || 0).toLocaleString('id-ID') }}</td>
                                        <td class="py-3 px-4 text-center"><button @click="removeCartItem(idx)" type="button">❌</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-t pt-4" :class="{'mt-6': !batchItems.length}">
                        <div class="flex items-center gap-6 text-gray-600">
                            <div v-if="batchItems.length > 0" class="flex flex-col">
                                <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Total Produk</span>
                                <span class="text-sm font-black text-gray-800">{{ batchItems.length }} ITEM</span>
                            </div>
                            <div v-if="batchItems.length > 0" class="h-8 w-px bg-gray-200"></div>
                            <div v-if="batchItems.length > 0" class="flex flex-col">
                                <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Total Pembelian (Modal)</span>
                                <span class="text-sm font-black text-blue-600">RP {{ totalBatchPurchase.toLocaleString('id-ID') }}</span>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button @click="submitBatch" :disabled="form.processing" class="bg-blue-600 text-white px-8 py-2.5 rounded text-xs font-black uppercase disabled:opacity-50 shadow-sm hover:bg-blue-700 transition-all">Simpan Produk</button>
                            <button @click="closeInline" class="border border-gray-300 px-8 py-2.5 rounded text-xs font-bold uppercase text-gray-500 hover:bg-gray-50 transition-all">Batal</button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showModalForm" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                        <h2 class="text-sm font-black text-gray-700 uppercase">✏️ Edit Alokasi Stok</h2>
                        <button @click="showModalForm = false" class="text-gray-400 hover:text-red-500">✕</button>
                    </div>
                    <div class="p-8 space-y-5">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Cabang & Produk</label>
                            <div class="bg-gray-100 p-3 rounded-lg text-xs font-bold text-gray-500 uppercase">{{ storeSearchQuery }} - {{ searchQuery }}</div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Update Stok</label>
                            <input v-model="form.stock" type="number" class="w-full border-2 border-blue-200 rounded-lg p-3 text-lg font-black text-blue-700 outline-none" />
                        </div>
                        <div class="flex gap-3 pt-4">
                            <button @click="submitSingleEdit" :disabled="form.processing" class="flex-1 bg-blue-600 text-white py-3 rounded-lg text-xs font-black uppercase hover:bg-blue-700 shadow-lg transition-all active:scale-95">Simpan Perubahan</button>
                            <button @click="showModalForm = false" class="px-6 py-3 border border-gray-300 rounded-lg text-xs font-bold uppercase text-gray-500 hover:bg-gray-50">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <div class="inline-flex bg-white p-1.5 rounded-xl border border-gray-200 items-center gap-3 shadow-sm">
                    <label class="pl-3 text-[10px] font-black uppercase text-gray-400 tracking-widest">Jenis Usaha</label>
                    <select v-model="selectedStoreType" class="bg-transparent border-none text-gray-800 text-xs rounded-lg focus:ring-0 px-4 py-2 font-black min-w-[180px] uppercase cursor-pointer">
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
                :showExportButton="true"
                route-name="store-products.index" 
                :initialSearch="filters?.search || ''"
                :filters="filters"
                @on-add="openCreate" 
                @on-export="handleExport"
            >
                <template #extra-filters>
                    <select v-model="selectedStore" class="border border-gray-300 rounded-lg p-2.5 text-xs font-bold bg-white focus:ring-2 focus:ring-blue-500/20 outline-none min-w-[200px] uppercase shadow-sm">
                        <option value="">-- SEMUA TOKO --</option>
                        <option v-for="s in filteredStoresList" :key="s.id" :value="s.id">{{ s.name.toUpperCase() }}</option>
                    </select>

                    <select v-model="selectedProductCategories" class="border border-gray-300 rounded-lg p-2.5 text-xs font-bold bg-white focus:ring-2 focus:ring-blue-500/20 outline-none min-w-[200px] uppercase shadow-sm">
                        <option value="">-- KATEGORI PRODUK --</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name.toUpperCase() }}</option>
                    </select>
                </template>

                <template #product_buying_price="{ value }">
                    <span class="font-medium text-gray-600">Rp {{ value ? new Intl.NumberFormat('id-ID').format(value) : '0' }}</span>
                </template>

                <template #stock="{ value }">
                    <span class="font-black text-blue-600">{{ value }} <small class="text-[10px] text-gray-400 font-bold">UNIT</small></span>
                </template>

                <template #creator="{ row }">
                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase bg-gray-100 text-gray-600 border border-gray-200">👤 {{ row.creator_name || 'SYSTEM' }}</span>
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