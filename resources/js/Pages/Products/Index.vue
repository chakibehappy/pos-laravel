<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ 
    products: Object, 
    stores: Array 
});

const columns = [
    { label: 'SKU', key: 'sku' },
    { label: 'Nama', key: 'name' }, 
    { label: 'Toko', key: 'store_name' },
    { label: 'Modal (Rp)', key: 'buying_price' }, // Kolom Baru
    { label: 'Jual (Rp)', key: 'selling_price' }, 
    { label: 'Stok', key: 'stock' }
];

const showForm = ref(false);
const form = useForm({
    id: null,
    store_id: '',
    name: '',
    sku: '',
    buying_price: 0,  // Field Baru
    selling_price: 0, 
    stock: 0
});

const openCreate = () => {
    form.reset();
    form.id = null;
    showForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.name = row.name;
    form.sku = row.sku;
    form.buying_price = row.buying_price; // Ambil data buying_price
    form.selling_price = row.selling_price; 
    form.stock = row.stock;
    showForm.value = true;
};

const submit = () => {
    form.post(route('products.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <div v-if="showForm" class="mb-8 p-6 border-2 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)]">
            <h2 class="font-black uppercase mb-4 italic text-lg underline decoration-yellow-400">
                {{ form.id ? 'Edit Produk' : 'Tambah Produk' }}
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-gray-400">Nama Produk</label>
                    <input v-model="form.name" type="text" placeholder="NAME" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                    <span v-if="form.errors.name" class="text-red-500 text-[10px] font-bold">{{ form.errors.name }}</span>
                </div>
                
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-gray-400">SKU / Kode</label>
                    <input v-model="form.sku" type="text" placeholder="SKU" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                    <span v-if="form.errors.sku" class="text-red-500 text-[10px] font-bold">{{ form.errors.sku }}</span>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-gray-400">Toko</label>
                    <select v-model="form.store_id" class="border-2 border-black p-2 font-bold bg-white focus:bg-yellow-50 outline-none">
                        <option value="" disabled>SELECT STORE</option>
                        <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <span v-if="form.errors.store_id" class="text-red-500 text-[10px] font-bold">{{ form.errors.store_id }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-red-500 italic">Harga Beli (Modal)</label>
                    <input v-model="form.buying_price" type="number" step="0.01" class="border-2 border-black p-2 font-bold bg-red-50 focus:bg-white outline-none" />
                    <span v-if="form.errors.buying_price" class="text-red-500 text-[10px] font-bold">{{ form.errors.buying_price }}</span>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-blue-600 italic">Harga Jual</label>
                    <input v-model="form.selling_price" type="number" step="0.01" class="border-2 border-black p-2 font-bold bg-blue-50 focus:bg-white outline-none" />
                    <span v-if="form.errors.selling_price" class="text-red-500 text-[10px] font-bold">{{ form.errors.selling_price }}</span>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-black uppercase text-gray-400">Stok Tersedia</label>
                    <input v-model="form.stock" type="number" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                    <span v-if="form.errors.stock" class="text-red-500 text-[10px] font-bold">{{ form.errors.stock }}</span>
                </div>
            </div>

            <div class="mt-6 flex gap-x-2">
                <button @click="submit" :disabled="form.processing" class="bg-black text-white px-8 py-2 font-bold uppercase hover:bg-gray-800 disabled:bg-gray-400">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </button>
                <button @click="showForm = false" class="border-2 border-black px-8 py-2 font-bold uppercase hover:bg-gray-100">
                    Batalkan
                </button>
            </div>
        </div>

        <div class="mb-4 flex justify-between items-end">
            <h1 class="text-2xl font-black uppercase tracking-tighter italic">Daftar Produk</h1>
            <button v-if="!showForm" @click="openCreate" class="bg-black text-white px-6 py-2 font-bold uppercase border-2 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)] transition-all active:shadow-none">
                Tambahkan
            </button>
        </div>

        <DataTable :resource="products" :columns="columns">
            <template #buying_price="{ value }">
                <span class="font-mono text-gray-500 text-sm">
                    {{ Number(value).toLocaleString('id-ID') }}
                </span>
            </template>

            <template #selling_price="{ value }">
                <span class="font-mono font-black text-blue-700">
                    {{ Number(value).toLocaleString('id-ID') }}
                </span>
            </template>

            <template #actions="{ row }">
                <div class="flex flex-row gap-x-[15px] justify-end uppercase text-xs font-black">
                    <button @click="openEdit(row)" title="Edit" class="hover:scale-125 transition-transform">✏️</button>
                    <button @click="$inertia.delete(route('products.destroy', row.id))" title="Hapus" class="hover:scale-125 transition-transform text-red-500">❌</button>
                </div>
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>