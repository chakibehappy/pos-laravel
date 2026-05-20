<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import axios from 'axios'; 

const props = defineProps({ 
    products: Object, 
    all_products_reference: Array, 
    categories: Array, 
    unitTypes: Array, 
    stores: Array, 
    storeTypes: Array, 
    filters: Object 
});

const selectedCategory = ref(props.filters?.category || '');

watch(selectedCategory, (v) => router.get(route('product-tests.index'), { ...props.filters, category: v, page: 1 }, { preserveState: true, replace: true }));

const columns = [
    { label: 'Tanggal', key: 'created_at', sortable: true }, { label: 'Gambar', key: 'image_url' }, { label: 'SKU', key: 'sku', sortable: true }, 
    { label: 'Nama Produk', key: 'name', sortable: true }, { label: 'Kategori', key: 'category_name' }, { label: 'Modal', key: 'buying_price', sortable: true }, 
    { label: 'Jual', key: 'selling_price', sortable: true }, { label: 'Satuan', key: 'unit_name' }, { label: 'Admin', key: 'created_by' }
];

const showInlineForm = ref(false), showModalForm = ref(false), imagePreview = ref(null), searchQuery = ref(''), showDropdown = ref(false), batchItems = ref([]);
const selectedReferenceName = ref('');

const closeProductDropdown = () => setTimeout(() => showDropdown.value = false, 200);
const filteredProductsReference = computed(() => {
    const refs = props.all_products_reference || [];
    if (!searchQuery.value) return refs;
    
    const q = searchQuery.value.toLowerCase().trim();
    
    return refs.filter(p => {
        const productName = p.name ? p.name.toLowerCase().trim() : '';
        const productSku = p.sku ? p.sku.toLowerCase().trim() : '';
        
        return productName.startsWith(q) || productSku.startsWith(q);
    });
});

const form = useForm({ 
    id: null, 
    store_type_id: '', 
    store_id: '', 
    product_category_id: '', 
    type_stock: '0', 
    unit_type_id: '', 
    name: '', 
    sku: '', 
    buying_price: 0, 
    selling_price: 0, 
    stock: 0, 
    image: null 
});

const inputForm = ref({ product_reference_id: '', product_category_id: '', store_type_id: '', type_stock: '0', unit_type_id: '', name: '', sku: '', buying_price: 0, selling_price: 0, current_stock: 0, stock: 0, image: null, image_preview_url: null });

// Memfilter toko berdasarkan Jenis Usaha yang dipilih. Jika kosong, tampilkan semua toko.
const filteredStores = computed(() => {
    if (!form.store_type_id) return props.stores || [];
    return (props.stores || []).filter(s => String(s.store_type_id) === String(form.store_type_id));
});

// Jika Jenis Usaha berubah, reset pilihan Toko
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
        router.get(route('product-tests.index'), { ...props.filters, store_id: newStoreId }, { preserveState: true, preserveScroll: true, replace: true });
        
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
        const response = await axios.get(route('product-tests.get-stock', {
            product_id: productReferenceId,
            store_id: storeId
        }));
        inputForm.value.current_stock = response.data.stock || 0;
    } catch (error) {
        console.error('Gagal mengambil data stok dari store_products:', error);
        inputForm.value.current_stock = 0;
    }
};

const formFields = computed(() => [
    { name: 'sku', label: 'SKU / Kode Barang', type: 'text', placeholder: 'Contoh: BRG-001' },
    { name: 'product_category_id', label: 'Kategori', type: 'select', options: props.categories },
    { name: 'type_stock', label: 'Jenis Stok', type: 'select', options: [{ id: '0', name: 'TERBATAS' }, { id: '1', name: 'TIDAK TERBATAS' }] },
    { name: 'buying_price', label: 'Harga Modal (Beli)', type: 'number', class: 'text-blue-600' },
    { name: 'selling_price', label: 'Harga Jual', type: 'number', class: 'text-green-600' },
    { name: 'unit_type_id', label: 'Satuan Barang', type: 'select', options: props.unitTypes }
]);

const openCreate = () => { form.reset(); form.clearErrors(); form.id = null; form.store_type_id = ''; form.store_id = ''; form.type_stock = '0'; batchItems.value = []; resetInputForm(true); showModalForm.value = false; showInlineForm.value = true; };

const closeInline = () => { showInlineForm.value = false; batchItems.value = []; form.reset(); form.store_id = ''; form.store_type_id = ''; form.type_stock = '0'; resetInputForm(true); };

const openEdit = (row) => {
    form.clearErrors(); form.id = row.id; form.store_type_id = row.store_type_id || ''; form.store_id = row.store_id || '';
    ['product_category_id', 'type_stock', 'unit_type_id', 'name', 'sku', 'buying_price', 'selling_price', 'stock'].forEach(k => {
        if (k === 'type_stock' && row[k] !== null && row[k] !== undefined) {
            form[k] = String(row[k]);
        } else {
            form[k] = row[k];
        }
    });
    form.image = null; searchQuery.value = row.name; imagePreview.value = row.image_url; showInlineForm.value = false; showModalForm.value = true;
};

const selectProduct = async (p) => {
    ['id:product_reference_id', 'name', 'sku', 'buying_price', 'selling_price', 'product_category_id', 'unit_type_id', 'type_stock'].forEach(k => {
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
    inputForm.value = { product_reference_id: '', product_category_id: '', store_type_id: '', type_stock: '0', unit_type_id: '', name: forceClearSearch ? '' : searchQuery.value, sku: '', buying_price: 0, selling_price: 0, current_stock: 0, stock: 0, image: null, image_preview_url: null };
    
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
    const cObj = props.categories.find(c => c.id === inputForm.value.product_category_id), uObj = props.unitTypes.find(u => u.id === inputForm.value.unit_type_id);
    
    batchItems.value.push({ ...inputForm.value, status_tab: determinedTab, category_name: cObj ? cObj.name.toUpperCase() : '-', unit_name: uObj ? uObj.name.toUpperCase() : '-' });
    
    resetInputForm(false);
};

const removeCartItem = (i) => batchItems.value.splice(i, 1);

const handleFileChange = (e) => {
    const f = e.target.files[0];
    if (showModalForm.value) { form.image = f; if (f) imagePreview.value = URL.createObjectURL(f); }
    else { inputForm.value.image = f; if (f) inputForm.value.image_preview_url = imagePreview.value = URL.createObjectURL(f); }
};

const submitBatch = () => {
    if (!form.store_id) return alert('Silakan pilih alokasi toko terlebih dahulu!');
    if (!batchItems.value.length) return alert('Keranjang batch kosong! Tambahkan minimal satu produk.');
    
    const cleanBatchItems = batchItems.value.map(item => {
        // Jika form.store_type_id kosong (Semua Jenis Usaha), ambil store_type_id bawaan dari toko yang dipilih
        const currentStore = props.stores.find(s => s.id === form.store_id);
        return {
            ...item,
            stock: Number(item.stock) || 0,
            store_type_id: form.store_type_id || (currentStore ? currentStore.store_type_id : null)
        };
    });

    router.post(route('product-tests.store'), {
        store_id: form.store_id,
        products_batch: cleanBatchItems
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
            form.type_stock = '0';
            resetInputForm(true); 
        }
    });
};

const submitSingleEdit = () => form.post(route('product-tests.store'), { forceFormData: true, preserveScroll: true, onSuccess: () => { showModalForm.value = false; form.reset(); } });
const destroy = (id) => confirm('APAKAH ANDA YAKIN INGIN MENGHAPUS PRODUK INI? (DATA AKAN DIARSIPKAN)') && router.delete(route('product-tests.destroy', id), { preserveScroll: true });

const totalBatchPurchase = computed(() => {
    return batchItems.value.reduce((sum, item) => {
        const qty = Number(item.stock) || 0;
        const price = Number(item.buying_price) || 0;
        return sum + (qty * price);
    }, 0);
});
</script>

<template>
    <AuthenticatedLayout page-title="Daftar Produk Test" page-subtitle="Maar Company">
        <div class="p-8">
            <div v-if="showInlineForm" class="mb-8 bg-white rounded-xl border border-gray-200 shadow-sm animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="bg-gray-50 border-b px-6 py-4 flex justify-between items-center text-sm font-bold uppercase tracking-widest text-gray-700 rounded-t-xl overflow-hidden">
                    <span>➕ Tambahkan Data Produk (Batch Mode)</span>
                    <button @click="closeInline" class="text-gray-400 hover:text-red-500 transition-colors">✕</button>
                </div>
                <div class="p-6">
                    <div class="w-full mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1 w-full">
                            <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Jenis Usaha</label>
                            <select v-model="form.store_type_id" :class="['border border-gray-300 rounded p-2 text-sm uppercase bg-white focus:ring-1 focus:ring-blue-500 outline-none font-bold w-full', form.store_type_id ? 'text-black' : 'text-gray-400']">
                                <option value="" class="text-gray-400">-- SEMUA JENIS USAHA --</option>
                                <option v-for="st in storeTypes" :key="st.id" :value="st.id" class="text-black">
                                    {{ st.name.toUpperCase() }}
                                </option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1 w-full">
                            <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Alokasikan Untuk Toko</label>
                            <select v-model="form.store_id" :class="['border border-gray-300 rounded p-2 text-sm uppercase bg-white focus:ring-1 focus:ring-blue-500 outline-none font-bold w-full', form.store_id ? 'text-black' : 'text-gray-400']">
                                <option value="" class="text-gray-400">PILIH TOKO</option>
                                <option v-for="s in filteredStores" :key="s.id" :value="s.id" class="text-black">{{ s.name.toUpperCase() }}</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="!form.store_id" class="p-8 text-center border border-dashed border-gray-300 rounded-xl bg-gray-50 my-6 text-xs font-black uppercase tracking-widest text-gray-400">
                        ⚠️ PILIH TERLEBIH DAHULU TOKO YANG AKAN DIALOKASIKAN
                    </div>

                    <div v-else class="flex flex-col md:flex-row gap-6 w-full relative z-[50]">
                        <div class="w-full md:w-56 flex-shrink-0 flex flex-col">
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 tracking-widest">
                                <span v-if="!!inputForm.product_reference_id" class="text-gray-400 mr-1">🔒</span>Foto
                            </label>
                            <div class="border-2 border-dashed border-gray-200 rounded-xl flex items-center justify-center overflow-hidden relative group bg-gray-50 hover:bg-gray-100 transition-colors w-full h-56">
                                <img v-if="imagePreview" :src="imagePreview" class="object-cover w-full h-full" />
                                <span v-else class="text-[9px] font-bold text-gray-300 uppercase text-center p-2 leading-tight">Klik/Seret Foto</span>
                                <input type="file" @change="handleFileChange" :disabled="!!inputForm.product_reference_id" class="absolute inset-0 opacity-0 cursor-pointer disabled:cursor-not-allowed" />
                            </div>
                        </div>
                        <div class="flex-1 flex flex-col justify-between">
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
                                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">
                                        <span v-if="!!inputForm.product_reference_id" class="text-gray-400 mr-1">🔒</span>{{ field.label }}
                                    </label>
                                    <select v-if="field.type === 'select'" v-model="inputForm[field.name]" :disabled="!!inputForm.product_reference_id" :class="['border border-gray-300 rounded p-2 text-sm uppercase bg-white focus:ring-1 focus:ring-blue-500 outline-none font-bold disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed', inputForm[field.name] !== '' && inputForm[field.name] !== undefined ? 'text-black' : 'text-gray-400']">
                                        <option value="" class="text-gray-400">PILIH {{ field.label }}</option>
                                        <option v-for="opt in field.options" :key="opt.id" :value="opt.id" class="text-black">{{ opt.name.toUpperCase() }}</option>
                                    </select>
                                    <input v-else v-model="inputForm[field.name]" :type="field.type" :placeholder="field.placeholder" :disabled="!!inputForm.product_reference_id" :class="['border border-gray-300 rounded p-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none font-bold uppercase disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed', field.class]" />
                                </div>
                            </div>
                            <div class="w-full flex justify-end mt-4">
                                <button @click="addToCart" type="button" class="border border-blue-600 text-blue-600 px-5 py-2 rounded text-xs font-black uppercase hover:bg-blue-50 transition-all whitespace-nowrap">+ Tambahkan Ke Keranjang</button>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="form.errors && Object.keys(form.errors).length > 0" class="mt-6 p-3 bg-red-50 border border-red-200 rounded text-red-600 text-xs font-bold uppercase tracking-wide">
                        <ul>
                            <li v-for="(error, key) in form.errors" :key="key">⚠️ {{ error }}</li>
                        </ul>
                    </div>

                    <div v-if="batchItems.length > 0" class="mt-8 mb-6 animate-in fade-in duration-200">
                        <div class="border border-gray-300 rounded-lg overflow-hidden bg-gray-50">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-600 tracking-widest border-b border-gray-300">
                                        <th v-for="h in ['Status','Foto','Nama Produk','SKU / Kode','Kategori','Harga Modal','Harga Jual','Satuan','Stok Saat Ini','Stok Tambahan','']" :key="h" class="py-3 px-4" :class="{'w-24': h==='Foto', 'w-12 text-center': h===''}">{{ h }}</th>
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
                                        <td class="py-3 px-4">{{ item.unit_name }}</td>
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

            <DataTable title="Daftar Produk Test" :resource="products" :columns="columns" :showAddButton="!showInlineForm" routeName="product-tests.index" :initialSearch="filters?.search" :filters="filters" @on-add="openCreate">
                <template #extra-filters>
                    <select v-model="selectedCategory" :class="['border border-gray-300 rounded p-2 text-xs font-bold uppercase bg-white focus:ring-1 focus:ring-blue-500 outline-none min-w-[200px] shadow-sm', selectedCategory ? 'text-black' : 'text-gray-400']">
                        <option value="" class="text-gray-400">-- SEMUA KATEGORI --</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id" class="text-black">{{ c.name.toUpperCase() }}</option>
                    </select>
                </template>
                <template #created_at="{ value }"><span class="text-[10px] text-gray-400 font-bold whitespace-nowrap">{{ value }}</span></template>
                <template #image_url="{ value }">
                    <img v-if="value" :src="value" class="w-10 h-10 object-cover rounded border border-gray-200 shadow-sm" />
                    <div v-else class="w-10 h-10 bg-gray-100 rounded border border-gray-200 flex items-center justify-center text-[8px] text-gray-400 font-bold">NO IMG</div>
                </template>
                <template #sku="{ value }">
                    <span class="text-xs font-bold uppercase">{{ value }}</span>
                </template>
                <template #name="{ value }">
                    <span class="text-xs font-semibold uppercase">{{ value }}</span>
                </template>
                <template #category_name="{ value }"><span class="text-[9px] font-black uppercase px-2 py-1 bg-blue-50 text-blue-600 rounded-md border border-blue-100">{{ value }}</span></template>
                <template #buying_price="{ value }">
                    <span class="text-gray-400 text-[10px] mr-1">Rp</span>
                    <span class="font-medium">{{ Number(value).toLocaleString('id-ID') }}</span>
                </template>
                <template #selling_price="{ value }">
                    <span class="text-gray-400 text-[10px] mr-1">Rp</span>
                    <span class="font-black text-blue-700">{{ Number(value).toLocaleString('id-ID') }}</span>
                </template>
                <template #created_by="{ value }"><span class="text-[10px] font-bold text-gray-500 uppercase whitespace-nowrap bg-gray-100 px-2 py-1 rounded">👤 {{ value }}</span></template>
                <template #actions="{ row }"><div class="flex gap-4 justify-end"><button @click="openEdit(row)" class="text-gray-300 hover:text-blue-600 transition-colors">✏️</button><button @click="destroy(row.id)" class="text-gray-300 hover:text-red-600 transition-colors">❌</button></div></template>
            </DataTable>
        </div>
    </AuthenticatedLayout>

    <div v-if="showModalForm" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white w-full max-w-3xl rounded-xl p-8 shadow-2xl overflow-y-auto max-h-[90vh] animate-in zoom-in-95 duration-200">
            <h2 class="text-sm font-black uppercase mb-6 flex items-center gap-2 border-b pb-4 tracking-widest text-gray-700">✏️ Edit: <span class="text-blue-600">{{ form.name }}</span></h2>
            <div class="flex flex-col md:flex-row gap-8">
                <div class="w-full md:w-32 flex-shrink-0">
                    <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest block mb-2">Foto Produk</label>
                    <div class="border-2 border-dashed border-gray-200 rounded-xl aspect-square flex items-center justify-center overflow-hidden relative bg-gray-50 group hover:bg-gray-100 transition-colors w-full md:w-32 h-32">
                        <img v-if="imagePreview" :src="imagePreview" class="object-cover w-full h-full" /><span v-else class="text-[10px] font-bold text-gray-300 uppercase text-center p-2 leading-tight">Tidak Ada Foto</span>
                        <input type="file" @change="handleFileChange" class="absolute inset-0 opacity-0 cursor-pointer" />
                    </div>
                </div>
                <div class="flex-1 flex flex-col uppercase text-xs font-bold text-gray-600">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1 md:col-span-2">
                            <label class="text-[10px] text-gray-400">Nama Produk</label>
                            <input v-model="form.name" type="text" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none text-sm font-bold" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-gray-400">Jenis Usaha</label>
                            <select v-model="form.store_type_id" :class="['border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none text-sm bg-white font-bold', form.store_type_id ? 'text-black' : 'text-gray-400']">
                                <option value="" class="text-gray-400">PILIH JENIS USAHA</option>
                                <option v-for="st in storeTypes" :key="st.id" :value="st.id" class="text-black">{{ st.name.toUpperCase() }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-gray-400">SKU / Kode</label>
                            <input v-model="form.sku" type="text" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none text-sm font-bold" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-gray-400">Kategori</label>
                            <select v-model="form.product_category_id" :class="['border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none text-sm bg-white font-bold', form.product_category_id ? 'text-black' : 'text-gray-400']">
                                <option value="" class="text-gray-400">PILIH KATEGORI</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id" class="text-black">{{ c.name.toUpperCase() }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-gray-400">Jenis Stok</label>
                            <select v-model="form.type_stock" :class="['border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none text-sm bg-white font-bold', form.type_stock !== '' && form.type_stock !== undefined ? 'text-black' : 'text-gray-400']">
                                <option value="" class="text-gray-400">PILIH JENIS STOK</option>
                                <option value="0" class="text-black">TERBATAS</option>
                                <option value="1" class="text-black">TIDAK TERBATAS</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-gray-400">Satuan</label>
                            <select v-model="form.unit_type_id" :class="['border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none text-sm bg-white font-bold', form.unit_type_id ? 'text-black' : 'text-gray-400']">
                                <option value="" class="text-gray-400">PILIH SATUAN</option>
                                <option v-for="u in unitTypes" :key="u.id" :value="u.id" class="text-black">{{ u.name.toUpperCase() }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-gray-400">Harga Modal</label>
                            <input v-model="form.buying_price" type="number" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none text-sm font-bold text-blue-600" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] text-gray-400">Harga Jual</label>
                            <input v-model="form.selling_price" type="number" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none text-sm font-bold text-green-600" />
                        </div>
                    </div>
                    <div class="mt-8 pt-6 border-t flex justify-end gap-2">
                        <button @click="submitSingleEdit" :disabled="form.processing" class="bg-blue-600 text-white px-8 py-2.5 rounded text-xs font-black uppercase disabled:opacity-50 shadow-sm hover:bg-blue-700 transition-all">Simpan Perubahan</button>
                        <button @click="showModalForm = false" class="border border-gray-300 px-8 py-2.5 rounded text-xs font-bold uppercase text-gray-500 hover:bg-gray-50 transition-all">Batal</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>