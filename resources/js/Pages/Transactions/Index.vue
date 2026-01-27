<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ 
    transactions: Object, 
    stores: Array,
    pos_users: Array,
    products: Array 
});

const columns = [
    { label: 'Tanggal', key: 'transaction_at' },
    { label: 'Toko', key: 'store_name' }, 
    { label: 'Kasir', key: 'cashier_name' },
    { label: 'Total', key: 'total' }
];

const showForm = ref(false);
const showDetail = ref(false); 
const selectedTransaction = ref(null); 
const selectedProductId = ref(''); 
const qtyInput = ref(1);

const form = useForm({
    id: null,
    store_id: '',
    pos_user_id: '',
    transaction_at: new Date().toISOString().slice(0, 16),
    details: [], 
    subtotal: 0,
    tax: 0,
    total: 0
});

const addItem = () => {
    const product = props.products.find(p => p.id === parseInt(selectedProductId.value));
    if (!product) return;

    const existingIndex = form.details.findIndex(d => d.product_id === product.id);
    if (existingIndex > -1) {
        form.details[existingIndex].quantity += qtyInput.value;
        form.details[existingIndex].subtotal = form.details[existingIndex].quantity * form.details[existingIndex].price;
    } else {
        form.details.push({
            product_id: product.id,
            name: product.name,
            price: Number(product.price),
            quantity: Number(qtyInput.value),
            subtotal: Number(product.price) * Number(qtyInput.value)
        });
    }

    selectedProductId.value = '';
    qtyInput.value = 1;
    calculateAll();
};

const removeItem = (index) => {
    form.details.splice(index, 1);
    calculateAll();
};

const calculateAll = () => {
    form.subtotal = form.details.reduce((acc, item) => acc + Number(item.subtotal), 0);
    form.tax = form.subtotal * 0.1; 
    form.total = form.subtotal + form.tax;
};

const openCreate = () => {
    form.reset();
    form.details = [];
    showForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.pos_user_id = row.pos_user_id;
    form.transaction_at = row.transaction_at.replace(' ', 'T').slice(0, 16);
    
    form.details = row.details.map(d => ({
        product_id: d.product_id,
        name: d.product.name,
        price: Number(d.price),
        quantity: Number(d.quantity),
        subtotal: Number(d.subtotal)
    }));
    
    calculateAll();
    showForm.value = true;
};

const openDetail = (row) => {
    selectedTransaction.value = row;
    showDetail.value = true;
};

const submit = () => {
    if (form.details.length === 0) {
        alert("Add at least one item!");
        return;
    }
    form.post(route('transactions.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <div v-if="showForm" class="mb-8 p-6 border-4 border-black bg-white">
            <h2 class="font-black uppercase mb-6 italic text-2xl underline decoration-yellow-400">
                {{ form.id ? 'Edit Transaksi' : 'Transaksi Baru' }}
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="flex flex-col gap-2">
                    <label class="font-black text-xs uppercase text-gray-400">Waktu</label>
                    <input v-model="form.transaction_at" type="datetime-local" class="border-2 border-black p-3 font-bold" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-black text-xs uppercase text-gray-400">Toko</label>
                    <select v-model="form.store_id" class="border-2 border-black p-3 font-bold bg-white">
                        <option value="" disabled>Pilih Toko</option>
                        <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-black text-xs uppercase text-gray-400">Staff</label>
                    <select v-model="form.pos_user_id" class="border-2 border-black p-3 font-bold bg-white">
                        <option value="" disabled>Pilih Staff</option>
                        <option v-for="u in pos_users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>
            </div>

            <div class="mb-6 p-4 bg-gray-50 border-2 border-dashed border-black">
                <p class="font-black uppercase text-xs mb-2 italic">Tambahkan Produk Pembelian:</p>
                <div class="flex flex-col md:flex-row gap-4">
                    <select v-model="selectedProductId" class="flex-1 border-2 border-black p-2 font-bold">
                        <option value="" disabled>Pilih Produk</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} - Rp{{ Number(p.price).toLocaleString() }}</option>
                    </select>
                    <input v-model.number="qtyInput" type="number" min="1" class="w-24 border-2 border-black p-2 font-bold text-center" />
                    <button @click="addItem" type="button" class="bg-blue-500 text-white px-6 py-2 font-black border-2 border-black transition-all uppercase">Tambahkan</button>
                </div>
            </div>

            <div class="overflow-x-auto mb-8">
                <table class="w-full border-collapse border-2 border-black">
                    <thead class="bg-black text-white italic uppercase text-xs">
                        <tr>
                            <th class="p-3 text-left">Product</th>
                            <th class="p-3 text-right">Harga</th>
                            <th class="p-3 text-center">Qty</th>
                            <th class="p-3 text-right">Subtotal</th>
                            <th class="p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="font-bold">
                        <tr v-for="(item, index) in form.details" :key="index" class="border-b-2 border-black">
                            <td class="p-3 uppercase">{{ item.name }}</td>
                            <td class="p-3 text-right">Rp {{ Number(item.price).toLocaleString() }}</td>
                            <td class="p-3 text-center">{{ item.quantity }}</td>
                            <td class="p-3 text-right text-blue-600">Rp {{ Number(item.subtotal).toLocaleString() }}</td>
                            <td class="p-3 text-center">
                                <button @click="removeItem(index)" class="text-red-500 underline hover:bg-red-50 px-2 uppercase text-xs">Remove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col items-end gap-2 mb-8">
                <div class="font-bold uppercase text-sm">Subtotal: Rp. {{ Number(form.subtotal).toLocaleString() }}</div>
                <div class="font-bold uppercase text-sm">Pajak (10%): Rp. {{ Number(form.tax).toLocaleString() }}</div>
                <div class="text-3xl font-black bg-yellow-300 border-4 border-black p-4">
                    TOTAL: Rp {{ Number(form.total).toLocaleString() }}
                </div>
            </div>

            <div class="flex gap-x-4">
                <button @click="submit" class="bg-black text-white px-8 py-4 font-black uppercase hover:bg-gray-800 transition-all">Simpan</button>
                <button @click="showForm = false" class="border-2 border-black px-8 py-4 font-black uppercase hover:bg-gray-100 transition-all">Batalkan</button>
            </div>
        </div>

        <div v-if="showDetail" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white border-4 border-black w-full max-w-2xl overflow-hidden">
                <div class="p-4 border-b-4 border-black flex justify-between items-center bg-gray-100">
                    <h3 class="font-black uppercase italic text-xl">Detail Transaksi</h3>
                    <button @click="showDetail = false" class="font-black text-2xl hover:text-red-500 transition-colors">×</button>
                </div>
                <div class="p-6">
                    <table class="w-full border-2 border-black mb-6">
                        <thead class="bg-black text-white text-[10px] uppercase italic">
                            <tr>
                                <th class="p-2 text-left">Nama Produk</th>
                                <th class="p-2 text-center">Qty</th>
                                <th class="p-2 text-right">Harga</th>
                                <th class="p-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="font-bold text-sm">
                            <tr v-for="d in selectedTransaction.details" :key="d.id" class="border-b-2 border-black">
                                <td class="p-2 uppercase">{{ d.product.name }}</td>
                                <td class="p-2 text-center">{{ d.quantity }}</td>
                                <td class="p-2 text-right">Rp {{ Number(d.price).toLocaleString() }}</td>
                                <td class="p-2 text-right text-blue-600">Rp {{ Number(d.subtotal).toLocaleString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="flex justify-between items-center font-black uppercase text-lg bg-yellow-300 border-2 border-black p-2">
                        <span>Total Paid</span>
                        <span>Rp {{ Number(selectedTransaction.total).toLocaleString() }}</span>
                    </div>
                </div>
                <div class="flex border-t-4 border-black">
                    <button @click="() => { showDetail = false; openEdit(selectedTransaction); }" class="flex-1 p-4 bg-blue-500 text-white font-black uppercase border-r-4 border-black hover:bg-blue-600 transition-all">
                        Edit
                    </button>
                    <button @click="showDetail = false" class="flex-1 p-4 bg-red-500 text-white font-black uppercase hover:bg-red-600 transition-all">
                        Keluar
                    </button>
                </div>
            </div>
        </div>

        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-4xl font-black uppercase italic tracking-tighter">Daftar Transaksi</h1>
            <button v-if="!showForm" @click="openCreate" class="bg-yellow-400 border-4 border-black px-8 py-3 font-black uppercase hover:translate-x-1 hover:translate-y-1 transition-all">Tambahkan</button>
        </div>

        <DataTable :resource="transactions" :columns="columns">
            <template #total="{ value }">
                <span class="font-mono font-black text-lg text-green-700">
                    Rp {{ Number(value).toLocaleString('id-ID') }}
                </span>
            </template>
            <template #actions="{ row }">
                <div class="flex flex-row gap-x-4 justify-end font-black text-xs uppercase italic">
                    <button @click="openDetail(row)" class="underline decoration-2 text-blue-600">Detail</button>
                    <button @click="openEdit(row)" class="underline decoration-2">Edit</button>
                    <button @click="$inertia.delete(route('transactions.destroy', row.id))" class="underline text-red-500 decoration-2">Hapus</button>
                </div>
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>