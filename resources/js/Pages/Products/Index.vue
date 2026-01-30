<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import debounce from 'lodash/debounce'; // Laravel biasanya sudah menyertakan lodash

const props = defineProps({ 
    products: Object, 
    stores: Array,
    categories: Array,
    unitTypes: Array
});

const columns = [
    { label: 'Gambar', key: 'image_url' },
    { label: 'SKU', key: 'sku' },
    { label: 'Nama', key: 'name' }, 
    { label: 'Kategori', key: 'category_name' },
    { label: 'Modal (Rp)', key: 'buying_price' }, 
    { label: 'Jual (Rp)', key: 'selling_price' }, 
    { label: 'Stok', key: 'stock' },
    { label: 'Satuan', key: 'unit_name' }
];

const showForm = ref(false);
const imagePreview = ref(null);
const search = ref('');
const selectedCategory = ref('');

// --- LOGIC FILTER & SEARCH ---
watch([search, selectedCategory], debounce(([newSearch, newCat]) => {
    router.get(route('products.index'), { 
        search: newSearch, 
        category: newCat 
    }, { 
        preserveState: true, 
        replace: true 
    });
}, 500));

// --- LOGIC FORM ---
const form = useForm({
    id: null,
    product_category_id: '',
    unit_type_id: 1,
    name: '',
    sku: '',
    buying_price: 0,
    selling_price: 0, 
    stock: 0,
    image: null,
});

const openCreate = () => {
    form.reset();
    form.id = null;
    form.unit_type_id = 1;
    imagePreview.value = null;
    showForm.value = true;
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
    form.stock = row.stock;
    form.image = null;
    imagePreview.value = row.image_url;
    showForm.value = true;
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    form.image = file;
    if (file) {
        imagePreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('products.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
            imagePreview.value = null;
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <div v-if="showForm" class="mb-8 p-6 border-2 border-black bg-white  -[4px_4px_0px_0px_rgba(0,0,0,0.25)]">
            <h2 class="font-black uppercase mb-4 italic text-lg   decoration-yellow-400">
                {{ form.id ? 'Edit Produk' : 'Tambah Produk' }}
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div class="flex flex-col gap-1 md:row-span-2">
                    <label class="text-[10px] font-black uppercase text-gray-400">Foto Produk</label>
                    <div class="border-2 border-black aspect-square flex items-center justify-center overflow-hidden bg-gray-50 relative group">
                        <img v-if="imagePreview" :src="imagePreview" class="object-cover w-full h-full" />
                        <span v-else class="text-gray-300 font-black text-2xl">NO IMAGE</span>
                        <input type="file" @change="handleFileChange" class="absolute inset-0 opacity-0 cursor-pointer" />
                    </div>
                </div>

                <div class="flex flex-col gap-1 md:col-span-2">
                    <label class="text-[10px] font-black uppercase text-gray-400">Nama Produk</label>
                    <input v-model="form.name" type="text" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none uppercase" />
                </div>
                
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-gray-400">SKU / Kode</label>
                    <input v-model="form.sku" type="text" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none uppercase" />
                </div>

                <!-- <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-gray-400">Toko</label>
                    <select v-model="form.store_id" class="border-2 border-black p-2 font-bold bg-white focus:bg-yellow-50 outline-none uppercase">
                        <option value="" disabled>PILIH TOKO</option>
                        <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div> -->

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-gray-400 text-yellow-600">Kategori</label>
                    <select v-model="form.product_category_id" class="border-2 border-black p-2 font-bold bg-white focus:bg-yellow-50 outline-none uppercase">
                        <option value="" disabled>PILIH KATEGORI</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-red-500 italic">Harga Modal</label>
                    <input v-model="form.buying_price" type="number" class="border-2 border-black p-2 font-bold bg-red-50 outline-none" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-blue-600 italic">Harga Jual</label>
                    <input v-model="form.selling_price" type="number" class="border-2 border-black p-2 font-bold bg-blue-50 outline-none" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-gray-400">Stok</label>
                    <input v-model="form.stock" type="number" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-green-600">Satuan</label>
                    <select v-model="form.unit_type_id" class="border-2 border-black p-2 font-bold bg-white focus:bg-yellow-50 outline-none uppercase">
                        <option v-for="u in unitTypes" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex gap-x-2">
                <button @click="submit" :disabled="form.processing" class="bg-black text-white px-8 py-2 font-bold uppercase  -[2px_2px_0px_0px_rgba(0,0,0,1)] active: -none transition-all">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Produk' }}
                </button>
                <button @click="showForm = false" class="border-2 border-black px-8 py-2 font-bold uppercase hover:bg-gray-100">Batalkan</button>
            </div>
        </div>

        <div class="mb-4 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h1 class="text-2xl font-black uppercase tracking-tighter italic">Daftar Produk</h1>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Manajemen stok & inventaris</p>
            </div>
            
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Cari..." 
                    class="border-2 border-black px-4 py-2 font-bold uppercase outline-none focus:bg-yellow-50 w-full md:w-64 "
                />
                <select 
                    v-model="selectedCategory" 
                    class="border-2 border-black px-4 py-2 font-bold uppercase outline-none "
                >
                    <option value="">SEMUA KATEGORI</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <button v-if="!showForm" @click="openCreate" class="bg-black text-white px-6 py-2 font-bold uppercase border-2 border-black  hover:-translate-y-1 transition-all">
                    + Produk
                </button>
            </div>
        </div>

        <DataTable 
            :resource="products" 
            :columns="columns"
        >
            <template #image_url="{ value }">
                <div class="w-10 h-10 border border-black overflow-hidden bg-gray-100 ">
                    <img v-if="value" :src="value" class="w-full h-full object-cover" />
                    <div v-else class="flex items-center justify-center h-full text-[8px] text-gray-400 font-bold">N/A</div>
                </div>
            </template>

            <template #category_name="{ value }">
                <span class="px-2 py-1 border border-black bg-yellow-100 text-[10px] font-black uppercase italic ">{{ value }}</span>
            </template>

            <template #stock="{ value }">
                <span class="font-bold" :class="value <= 5 ? 'text-red-500' : ''">{{ value }}</span>
            </template>

            <template #unit_name="{ value }">
                <span class="px-2 py-1 border border-black bg-green-50 text-[10px] font-black uppercase italic  -[1px_1px_0px_0px_rgba(0,0,0,1)]">{{ value }}</span>
            </template>

            <template #buying_price="{ value }">{{ Number(value).toLocaleString('id-ID') }}</template>
            <template #selling_price="{ value }">{{ Number(value).toLocaleString('id-ID') }}</template>

            <template #actions="{ row }">
                <div class="flex flex-row gap-x-[15px] justify-end uppercase text-xs font-black">
                    <button @click="openEdit(row)" title="Edit" class="hover:scale-125 transition-transform">✏️</button>
                    <button @click="$inertia.delete(route('products.destroy', row.id))" title="Hapus" class="text-red-500 hover:scale-125 transition-transform">❌</button>
                </div>
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>