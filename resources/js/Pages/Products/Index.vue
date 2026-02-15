<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ 
    products: Object, 
    categories: Array,
    unitTypes: Array,
    filters: Object
});

const selectedCategory = ref(props.filters?.category || '');

// Menangani Filter Kategori
watch(selectedCategory, (newValue) => {
    router.get(route('products.index'), { 
        ...props.filters, 
        category: newValue,
        page: 1 
    }, { preserveState: true, replace: true });
});

// Definisi Kolom Tabel
const columns = [
    { label: 'Tanggal', key: 'created_at', sortable: true },
    { label: 'Gambar', key: 'image_url' },
    { label: 'SKU', key: 'sku', sortable: true },
    { label: 'Nama Produk', key: 'name', sortable: true }, 
    { label: 'Kategori', key: 'category_name' },
    { label: 'Modal', key: 'buying_price', sortable: true }, 
    { label: 'Jual', key: 'selling_price', sortable: true }, 
    { label: 'Satuan', key: 'unit_name' },
    { label: 'Admin', key: 'created_by' }
];

const showInlineForm = ref(false); 
const showModalForm = ref(false);  
const imagePreview = ref(null);

const form = useForm({
    id: null,
    product_category_id: '',
    unit_type_id: '',
    name: '',
    sku: '',
    buying_price: 0,
    selling_price: 0, 
    image: null,
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    imagePreview.value = null;
    showModalForm.value = false;
    showInlineForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.product_category_id = row.product_category_id;
    form.unit_type_id = row.unit_type_id;
    form.name = row.name;
    form.sku = row.sku;
    form.buying_price = row.buying_price; 
    form.selling_price = row.selling_price; 
    form.image = null;
    imagePreview.value = row.image_url;
    showInlineForm.value = false;
    showModalForm.value = true;
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    form.image = file;
    if (file) imagePreview.value = URL.createObjectURL(file);
};

const submit = () => {
    form.post(route('products.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showInlineForm.value = false;
            showModalForm.value = false;
            form.reset();
            imagePreview.value = null;
        },
    });
};

const destroy = (id) => {
    if (confirm('APAKAH ANDA YAKIN INGIN MENGHAPUS PRODUK INI? (DATA AKAN DIARSIPKAN)')) {
        router.delete(route('products.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Produk" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div v-if="showInlineForm" class="mb-8 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="bg-gray-50 border-b px-6 py-4 flex justify-between items-center text-sm font-bold uppercase tracking-widest text-gray-700">
                    <span>➕ Input Produk Baru</span>
                    <button @click="showInlineForm = false" class="text-gray-400 hover:text-red-500 transition-colors">✕</button>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="md:row-span-2">
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 tracking-widest">Foto Produk</label>
                            <div class="border-2 border-dashed border-gray-200 rounded-xl aspect-square flex items-center justify-center overflow-hidden relative group bg-gray-50 hover:bg-gray-100 transition-colors">
                                <img v-if="imagePreview" :src="imagePreview" class="object-cover w-full h-full" />
                                <span v-else class="text-[10px] font-bold text-gray-300 uppercase text-center p-4">Klik/Seret Foto Ke Sini</span>
                                <input type="file" @change="handleFileChange" class="absolute inset-0 opacity-0 cursor-pointer" />
                            </div>
                        </div>

                        <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Nama Produk</label>
                                <input v-model="form.name" type="text" placeholder="Masukkan Nama Produk" class="border border-gray-300 rounded p-2 text-sm uppercase focus:ring-1 focus:ring-blue-500 outline-none font-bold" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">SKU / Kode Barang</label>
                                <input v-model="form.sku" type="text" placeholder="Contoh: BRG-001" class="border border-gray-300 rounded p-2 text-sm uppercase focus:ring-1 focus:ring-blue-500 outline-none font-bold" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Kategori</label>
                                <select v-model="form.product_category_id" class="border border-gray-300 rounded p-2 text-sm uppercase bg-white focus:ring-1 focus:ring-blue-500 outline-none font-bold">
                                    <option value="">PILIH KATEGORI</option>
                                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name.toUpperCase() }}</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Harga Modal (Beli)</label>
                                <input v-model="form.buying_price" type="number" class="border border-gray-300 rounded p-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none font-bold text-blue-600" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Harga Jual</label>
                                <input v-model="form.selling_price" type="number" class="border border-gray-300 rounded p-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none font-bold text-green-600" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Satuan Barang</label>
                                <select v-model="form.unit_type_id" class="border border-gray-300 rounded p-2 text-sm uppercase bg-white focus:ring-1 focus:ring-blue-500 outline-none font-bold">
                                    <option value="">PILIH SATUAN</option>
                                    <option v-for="u in unitTypes" :key="u.id" :value="u.id">{{ u.name.toUpperCase() }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-2 border-t pt-6">
                        <button @click="submit" :disabled="form.processing" class="bg-blue-600 text-white px-8 py-2.5 rounded text-xs font-black uppercase disabled:opacity-50 shadow-sm hover:bg-blue-700 transition-all active:scale-95">Simpan Produk</button>
                        <button @click="showInlineForm = false" class="border border-gray-300 px-8 py-2.5 rounded text-xs font-bold uppercase text-gray-500 hover:bg-gray-50 transition-all">Batal</button>
                    </div>
                </div>
            </div>

            <DataTable 
                title="Daftar Produk"
                :resource="products" 
                :columns="columns"
                :showAddButton="!showInlineForm"
                routeName="products.index"
                :initialSearch="filters?.search"
                :filters="filters" 
                @on-add="openCreate"
            >
                <template #extra-filters>
                    <select 
                        v-model="selectedCategory"
                        class="border border-gray-300 rounded p-2 text-xs font-bold uppercase bg-white focus:ring-1 focus:ring-blue-500 outline-none min-w-[200px] shadow-sm"
                    >
                        <option value="">-- SEMUA KATEGORI --</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">
                            {{ c.name.toUpperCase() }}
                        </option>
                    </select>
                </template>

                <template #created_at="{ value }">
                    <span class="text-[10px] text-gray-400 font-bold whitespace-nowrap">{{ value }}</span>
                </template>

                <template #image_url="{ value }">
                    <img v-if="value" :src="value" class="w-10 h-10 object-cover rounded border border-gray-200 shadow-sm" />
                    <div v-else class="w-10 h-10 bg-gray-100 rounded border border-gray-200 flex items-center justify-center text-[8px] text-gray-400 font-bold">NO IMG</div>
                </template>

                <template #category_name="{ value }">
                    <span class="text-[9px] font-black uppercase px-2 py-1 bg-blue-50 text-blue-600 rounded-md border border-blue-100">{{ value }}</span>
                </template>

                <template #buying_price="{ value }">
                    <span class="text-gray-400 text-[10px] mr-1">Rp</span><span class="font-medium">{{ Number(value).toLocaleString('id-ID') }}</span>
                </template>

                <template #selling_price="{ value }">
                    <span class="text-gray-400 text-[10px] mr-1">Rp</span><span class="font-black text-blue-700">{{ Number(value).toLocaleString('id-ID') }}</span>
                </template>

                <template #created_by="{ value }">
                    <span class="text-[10px] font-bold text-gray-500 uppercase whitespace-nowrap bg-gray-100 px-2 py-1 rounded">👤 {{ value }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex gap-4 justify-end">
                        <button @click="openEdit(row)" class="text-gray-300 hover:text-blue-600 transition-colors">✏️</button>
                        <button @click="destroy(row.id)" class="text-gray-300 hover:text-red-600 transition-colors">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>

    <div v-if="showModalForm" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white w-full max-w-3xl rounded-xl p-8 shadow-2xl overflow-y-auto max-h-[90vh] animate-in zoom-in-95 duration-200">
            <h2 class="text-sm font-black uppercase mb-6 flex items-center gap-2 border-b pb-4 tracking-widest text-gray-700">
                ✏️ Edit: <span class="text-blue-600">{{ form.name }}</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Foto Produk</label>
                    <div class="border-2 border-dashed border-gray-200 rounded-xl aspect-square flex items-center justify-center overflow-hidden relative bg-gray-50 group hover:bg-gray-100 transition-colors">
                        <img v-if="imagePreview" :src="imagePreview" class="object-cover w-full h-full" />
                        <span v-else class="text-[10px] font-bold text-gray-300 uppercase text-center p-4">Tidak Ada Foto</span>
                        <input type="file" @change="handleFileChange" class="absolute inset-0 opacity-0 cursor-pointer" />
                    </div>
                </div>
                <div class="md:col-span-2 grid grid-cols-2 gap-4 uppercase text-xs font-bold text-gray-600">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] text-gray-400">Nama Produk</label>
                        <input v-model="form.name" type="text" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none uppercase text-sm font-bold" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] text-gray-400">SKU / Kode</label>
                        <input v-model="form.sku" type="text" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none uppercase text-sm font-bold" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] text-gray-400">Kategori</label>
                        <select v-model="form.product_category_id" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none text-sm bg-white font-bold">
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name.toUpperCase() }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] text-gray-400">Satuan</label>
                        <select v-model="form.unit_type_id" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none text-sm bg-white font-bold">
                            <option v-for="u in unitTypes" :key="u.id" :value="u.id">{{ u.name.toUpperCase() }}</option>
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
            </div>
            <div class="mt-8 pt-6 border-t flex gap-2">
                <button @click="submit" :disabled="form.processing" class="bg-blue-600 text-white px-8 py-2.5 rounded text-xs font-black uppercase disabled:opacity-50 hover:bg-blue-700 shadow-sm transition-all active:scale-95">Update Data Produk</button>
                <button @click="showModalForm = false" class="border border-gray-300 px-8 py-2.5 rounded text-xs font-bold uppercase text-gray-500 hover:bg-gray-50 transition-all">Batal</button>
            </div>
        </div>
    </div>
</template>