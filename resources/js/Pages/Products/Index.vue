<script setup>
import { ref } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ 
    products: Object, 
    categories: Array,
    unitTypes: Array,
    filters: Object
});

// Kolom Stok dihapus dari daftar kolom
const columns = [
    { label: 'Tanggal', key: 'created_at' },
    { label: 'Gambar', key: 'image_url' },
    { label: 'SKU', key: 'sku' },
    { label: 'Nama Produk', key: 'name' }, 
    { label: 'Kategori', key: 'category_name' },
    { label: 'Modal', key: 'buying_price' }, 
    { label: 'Jual', key: 'selling_price' }, 
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
        onError: (errors) => {
            console.error("Submit Error:", errors);
            alert("Gagal menyimpan data. Cek console atau pastikan semua field terisi.");
        }
    });
};
</script>

<template>
    <Head title="Produk" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div v-if="showInlineForm" class="mb-8 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-50 border-b px-6 py-4 flex justify-between items-center text-sm font-bold uppercase">
                    <span>📦 Input Produk Baru</span>
                    <button @click="showInlineForm = false" class="text-gray-400">✕</button>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="md:row-span-2">
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2">Foto Produk</label>
                            <div class="border-2 border-dashed border-gray-200 rounded-xl aspect-square flex items-center justify-center overflow-hidden relative group">
                                <img v-if="imagePreview" :src="imagePreview" class="object-cover w-full h-full" />
                                <span v-else class="text-[10px] font-bold text-gray-300 uppercase text-center p-4">Klik/Seret Foto Ke Sini</span>
                                <input type="file" @change="handleFileChange" class="absolute inset-0 opacity-0 cursor-pointer" />
                            </div>
                        </div>

                        <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500">Nama Produk</label>
                                <input v-model="form.name" type="text" placeholder="Masukkan Nama Produk" class="border border-gray-300 rounded p-2 text-sm uppercase focus:ring-1 focus:ring-blue-500 outline-none" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500">SKU / Kode Barang</label>
                                <input v-model="form.sku" type="text" placeholder="Contoh: BRG-001" class="border border-gray-300 rounded p-2 text-sm uppercase focus:ring-1 focus:ring-blue-500 outline-none" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500">Kategori</label>
                                <select v-model="form.product_category_id" class="border border-gray-300 rounded p-2 text-sm uppercase bg-white focus:ring-1 focus:ring-blue-500 outline-none">
                                    <option value="">PILIH KATEGORI</option>
                                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500">Harga Modal (Beli)</label>
                                <input v-model="form.buying_price" type="number" class="border border-gray-300 rounded p-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500">Harga Jual</label>
                                <input v-model="form.selling_price" type="number" class="border border-gray-300 rounded p-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black uppercase text-gray-500">Satuan Barang</label>
                                <select v-model="form.unit_type_id" class="border border-gray-300 rounded p-2 text-sm uppercase bg-white focus:ring-1 focus:ring-blue-500 outline-none">
                                    <option value="">PILIH SATUAN</option>
                                    <option v-for="u in unitTypes" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-2">
                        <button @click="submit" :disabled="form.processing" class="bg-blue-600 text-white px-6 py-2 rounded text-xs font-black uppercase disabled:opacity-50 shadow-sm hover:bg-blue-700 transition-all">Simpan Produk</button>
                        <button @click="showInlineForm = false" class="border border-gray-300 px-6 py-2 rounded text-xs font-bold uppercase text-gray-500 hover:bg-gray-50">Batal</button>
                    </div>
                </div>
            </div>

            <div v-if="showModalForm" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white w-full max-w-2xl rounded-xl p-8 shadow-2xl">
                    <h2 class="text-sm font-black uppercase mb-6 flex items-center gap-2 border-b pb-4">✏️ Edit: <span class="text-blue-600">{{ form.name }}</span></h2>
                    <div class="grid grid-cols-2 gap-6 uppercase text-xs font-bold text-gray-600">
                        <div class="flex flex-col gap-2">
                            <label>Nama Produk</label>
                            <input v-model="form.name" type="text" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label>SKU / Kode</label>
                            <input v-model="form.sku" type="text" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label>Kategori</label>
                            <select v-model="form.product_category_id" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none">
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label>Satuan</label>
                            <select v-model="form.unit_type_id" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none">
                                <option v-for="u in unitTypes" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label>Harga Modal</label>
                            <input v-model="form.buying_price" type="number" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label>Harga Jual</label>
                            <input v-model="form.selling_price" type="number" class="border border-gray-300 p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none" />
                        </div>
                    </div>
                    <div class="mt-8 pt-6 border-t flex gap-2">
                        <button @click="submit" :disabled="form.processing" class="bg-blue-600 text-white px-6 py-2 rounded text-xs font-black uppercase disabled:opacity-50">Update Data</button>
                        <button @click="showModalForm = false" class="border border-gray-300 px-6 py-2 rounded text-xs font-bold uppercase text-gray-400">Tutup</button>
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
                @on-add="openCreate"
            >
                <template #created_at="{ value }">
                    <span class="text-[10px] text-gray-500 font-medium whitespace-nowrap">{{ value }}</span>
                </template>

                <template #image_url="{ value }">
                    <img v-if="value" :src="value" class="w-10 h-10 object-cover rounded border border-gray-200" />
                    <div v-else class="w-10 h-10 bg-gray-100 rounded border border-gray-200 flex items-center justify-center text-[8px] text-gray-400 font-bold">NO IMG</div>
                </template>

                <template #category_name="{ value }">
                    <span class="text-[10px] font-bold uppercase px-2 py-1 bg-yellow-100 text-yellow-700 rounded">{{ value }}</span>
                </template>

                <template #buying_price="{ value }">
                    <span class="text-gray-400 text-[10px]">Rp</span>{{ Number(value).toLocaleString('id-ID') }}
                </template>
                <template #selling_price="{ value }">
                    <span class="text-gray-400 text-[10px]">Rp</span><span class="font-bold text-blue-600">{{ Number(value).toLocaleString('id-ID') }}</span>
                </template>

                <template #created_by="{ value }">
                    <span class="text-[10px] font-bold text-blue-600 uppercase italic whitespace-nowrap">👤 {{ value }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex gap-4 justify-end">
                        <button @click="openEdit(row)" class="text-gray-400 hover:text-blue-600 transition-colors">✏️</button>
                        <button @click="router.delete(route('products.destroy', row.id))" 
                                class="text-gray-400 hover:text-red-600 transition-colors">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>