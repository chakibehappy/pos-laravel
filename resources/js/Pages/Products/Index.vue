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
    { label: 'Harga', key: 'price' },
    { label: 'Stok', key: 'stock' }
];

const showForm = ref(false);
const form = useForm({
    id: null,
    store_id: '',
    name: '',
    sku: '',
    price: 0,
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
    form.price = row.price;
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
            <h2 class="font-black uppercase mb-4 italic">{{ form.id ? 'Edit Produk' : 'Tambah Produk' }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input v-model="form.name" type="text" placeholder="NAME" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                <input v-model="form.sku" type="text" placeholder="SKU" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                
                <select v-model="form.store_id" class="border-2 border-black p-2 font-bold bg-white focus:bg-yellow-50 outline-none">
                    <option value="" disabled>SELECT STORE</option>
                    <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>

                <input v-model="form.price" type="number" step="0.01" placeholder="PRICE" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
                <input v-model="form.stock" type="number" placeholder="STOCK" class="border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" />
            </div>
            <div class="mt-4 flex gap-x-2">
                <button @click="submit" class="bg-black text-white px-6 py-2 font-bold uppercase">Simpan</button>
                <button @click="showForm = false" class="border-2 border-black px-6 py-2 font-bold uppercase">Batalkan</button>
            </div>
        </div>

        <div class="mb-4 flex justify-between items-end">
            <h1 class="text-2xl font-black uppercase tracking-tighter italic">Daftar Produk</h1>
            <button v-if="!showForm" @click="openCreate" class="bg-black text-white px-6 py-2 font-bold uppercase border-2 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,0.25)] transition-all active:shadow-none">
                Tambahkan
            </button>
        </div>

        <DataTable :resource="products" :columns="columns">
            <template #price="{ value }">
                <span class="font-mono font-bold">Rp.{{ value }}</span>
            </template>
            <template #actions="{ row }">
                <div class="flex flex-row gap-x-[15px] justify-end uppercase text-xs font-black">
                    <button @click="openEdit(row)" class="underline hover:text-blue-600">Edit</button>
                    <button @click="$inertia.delete(route('products.destroy', row.id))" class="underline text-red-500">Hapus</button>
                </div>
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>