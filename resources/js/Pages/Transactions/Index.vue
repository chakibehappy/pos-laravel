<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ 
    transactions: Object, 
    stores: Array, 
    pos_users: Array, 
    products: Array, 
    paymentMethods: Array 
});

const columns = [
    { label: 'Tanggal', key: 'transaction_at' },
    { label: 'Toko', key: 'store_name' }, 
    { label: 'Kasir', key: 'cashier_name' },
    { label: 'Metode', key: 'payment_name' }, 
    { label: 'Total (Rp)', key: 'total' }
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
    payment_id: '', 
    transaction_at: new Date().toISOString().slice(0, 16),
    details: [], 
    subtotal: 0,
    tax: 0,
    total: 0
});

const deleteTransaction = (id) => {
    if (confirm('Hapus transaksi ini? Stok akan dikembalikan otomatis.')) {
        router.delete(route('transactions.destroy', id), {
            preserveScroll: true
        });
    }
};

const addItem = () => {
    const product = props.products.find(p => p.id === parseInt(selectedProductId.value));
    if (!product) {
        alert("Pilih produk terlebih dahulu!");
        return;
    }

    const qty = Number(qtyInput.value);
    const existingIndex = form.details.findIndex(d => d.product_id === product.id);
    const currentQtyInCart = existingIndex > -1 ? form.details[existingIndex].quantity : 0;

    // VALIDASI STOK (FRONTEND)
    if ((currentQtyInCart + qty) > product.stock) {
        alert(`Stok tidak cukup! Sisa stok ${product.name}: ${product.stock}`);
        return;
    }

    const price = Number(product.price || 0);
    
    if (existingIndex > -1) {
        form.details[existingIndex].quantity += qty;
        form.details[existingIndex].subtotal = form.details[existingIndex].quantity * price;
    } else {
        form.details.push({
            product_id: product.id,
            name: product.name,
            price: price,
            quantity: qty,
            subtotal: price * qty
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
    form.clearErrors();
    form.id = null;
    form.payment_id = ''; 
    form.details = [];
    form.transaction_at = new Date().toISOString().slice(0, 16);
    showForm.value = true;
};

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.pos_user_id = row.pos_user_id;
    form.payment_id = row.payment_id || ''; 
    form.transaction_at = row.transaction_at.replace(' ', 'T').slice(0, 16);
    
    form.details = row.details.map(d => {
        return {
            product_id: d.product_id,
            name: d.product ? d.product.name : 'Unknown Product',
            price: Number(d.selling_prices), 
            quantity: Number(d.quantity),
            subtotal: Number(d.subtotal)
        };
    });
    calculateAll();
    showForm.value = true;
};

const openDetail = (row) => {
    selectedTransaction.value = row;
    showDetail.value = true;
};

const submit = () => {
    if (form.details.length === 0) {
        alert("Tambahkan minimal satu produk!");
        return;
    }

    const url = form.id 
        ? route('transactions.update', form.id) 
        : route('transactions.store');

    form.transform((data) => ({
        ...data,
        _method: form.id ? 'PUT' : 'POST',
    })).post(url, {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
            alert("Berhasil disimpan!");
        },
        onError: (errors) => {
            // Menangkap pesan error dari throw Exception di Controller
            if (errors.message) {
                alert("ERROR: " + errors.message);
            }
        },
        preserveScroll: true
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <div v-if="showForm" class="mb-8 p-6 border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
            <h2 class="font-black uppercase mb-6 italic text-2xl underline decoration-yellow-400">
                {{ form.id ? 'Edit Transaksi' : 'Transaksi Baru' }}
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="flex flex-col gap-2">
                    <label class="font-black text-xs uppercase text-gray-400">Waktu</label>
                    <input v-model="form.transaction_at" type="datetime-local" class="border-2 border-black p-3 font-bold focus:bg-yellow-50 outline-none" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-black text-xs uppercase text-gray-400">Toko</label>
                    <select v-model="form.store_id" class="border-2 border-black p-3 font-bold bg-white outline-none">
                        <option value="" disabled>Pilih Toko</option>
                        <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-black text-xs uppercase text-gray-400">Staff</label>
                    <select v-model="form.pos_user_id" class="border-2 border-black p-3 font-bold bg-white outline-none">
                        <option value="" disabled>Pilih Staff</option>
                        <option v-for="u in pos_users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-black text-xs uppercase text-blue-600 italic">Metode Bayar</label>
                    <select v-model="form.payment_id" class="border-2 border-black p-3 font-bold bg-yellow-50 outline-none">
                        <option value="">Pilih Metode</option>
                        <option v-for="m in paymentMethods" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
            </div>

            <div class="mb-6 p-4 bg-gray-50 border-2 border-dashed border-black flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 flex flex-col gap-1">
                    <label class="font-black text-[10px] uppercase text-gray-400">Pilih Produk (Sisa Stok Tertera)</label>
                    <select v-model="selectedProductId" class="w-full border-2 border-black p-2 font-bold outline-none">
                        <option value="" disabled>Pilih Produk</option>
                        <option v-for="p in products" :key="p.id" :value="p.id" :disabled="p.stock <= 0">
                            {{ p.name }} — {{ Number(p.price).toLocaleString('id-ID') }} (Stok: {{ p.stock }})
                        </option>
                    </select>
                </div>
                <div class="w-24 flex flex-col gap-1">
                    <label class="font-black text-[10px] uppercase text-gray-400 text-center">Jumlah</label>
                    <input v-model.number="qtyInput" type="number" min="1" class="w-full border-2 border-black p-2 font-bold text-center outline-none" />
                </div>
                <button @click="addItem" type="button" class="bg-blue-500 text-white px-8 py-2 font-black border-2 border-black uppercase hover:bg-blue-600 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-1 active:translate-y-1 transition-all">Tambah</button>
            </div>

            <div class="overflow-x-auto mb-8">
                <table class="w-full border-collapse border-2 border-black">
                    <thead class="bg-black text-white italic uppercase text-xs">
                        <tr>
                            <th class="p-3 text-left">Produk</th>
                            <th class="p-3 text-right">Harga (Rp)</th>
                            <th class="p-3 text-center">Qty</th>
                            <th class="p-3 text-right">Subtotal (Rp)</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="font-bold">
                        <tr v-for="(item, index) in form.details" :key="index" class="border-b-2 border-black hover:bg-gray-50">
                            <td class="p-3 uppercase">{{ item.name }}</td>
                            <td class="p-3 text-right">{{ Number(item.price).toLocaleString('id-ID') }}</td>
                            <td class="p-3 text-center">{{ item.quantity }}</td>
                            <td class="p-3 text-right text-blue-600">{{ Number(item.subtotal).toLocaleString('id-ID') }}</td>
                            <td class="p-3 text-center">
                                <button @click="removeItem(index)" class="bg-red-100 text-red-600 px-3 py-1 border border-red-600 font-black uppercase text-[10px] hover:bg-red-600 hover:text-white transition-colors">❌</button>
                            </td>
                        </tr>
                        <tr v-if="form.details.length === 0">
                            <td colspan="5" class="p-8 text-center text-gray-300 italic uppercase">Keranjang Kosong</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col items-end gap-2 mb-8">
                <div class="text-right font-bold uppercase text-xs text-gray-400 space-y-1">
                    <div>Subtotal: Rp {{ Number(form.subtotal).toLocaleString('id-ID') }}</div>
                    <div class="text-red-500 font-black">Pajak (10%): Rp {{ Number(form.tax).toLocaleString('id-ID') }}</div>
                </div>
                <div class="text-3xl font-black bg-yellow-300 border-4 border-black p-4 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] italic">
                    TOTAL: Rp {{ Number(form.total).toLocaleString('id-ID') }}
                </div>
            </div>

            <div class="flex gap-x-4">
                <button @click.prevent="submit" :disabled="form.processing" class="bg-black text-white px-10 py-4 font-black uppercase hover:bg-gray-800 disabled:bg-gray-400 border-2 border-black shadow-[4px_4px_0px_0px_rgba(0,0,0,0.3)] active:shadow-none transition-all">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </button>
                <button @click="showForm = false" type="button" class="border-2 border-black px-10 py-4 font-black uppercase hover:bg-gray-100 transition-colors">Batal</button>
            </div>
        </div>

        <div v-if="showDetail" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border-4 border-black w-full max-w-4xl overflow-hidden shadow-[12px_12px_0px_0px_rgba(0,0,0,1)]">
                <div class="p-4 border-b-4 border-black flex justify-between items-center bg-yellow-400 font-black uppercase italic">
                    <span>Detail Transaksi</span>
                    <button @click="showDetail = false" class="text-2xl hover:scale-125 transition-transform">×</button>
                </div>
                <div class="p-6">
                    <table class="w-full border-2 border-black mb-6">
                        <thead class="bg-black text-white text-[10px] uppercase italic">
                            <tr>
                                <th class="p-2 text-left">Nama Produk</th>
                                <th class="p-2 text-right">Harga Jual (Rp)</th>
                                <th class="p-2 text-center">Qty</th>
                                <th class="p-2 text-right">Subtotal (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="font-bold text-sm">
                            <tr v-for="d in selectedTransaction.details" :key="d.id" class="border-b-2 border-black">
                                <td class="p-2 uppercase">{{ d.product?.name }}</td>
                                <td class="p-2 text-right text-gray-500">{{ Number(d.selling_prices).toLocaleString('id-ID') }}</td>
                                <td class="p-2 text-center">{{ d.quantity }}</td>
                                <td class="p-2 text-right text-blue-600 font-black">{{ Number(d.subtotal).toLocaleString('id-ID') }}</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="space-y-1 bg-gray-50 border-2 border-black p-4 font-bold uppercase text-sm mb-4">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>Rp {{ Number(selectedTransaction.subtotal).toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="flex justify-between text-red-600 font-black">
                            <span>Pajak (10%)</span>
                            <span>Rp {{ Number(selectedTransaction.tax).toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="flex justify-between items-center font-black text-xl bg-yellow-300 border-t-2 border-black pt-3 mt-3">
                            <span>Total Akhir</span>
                            <span>Rp {{ Number(selectedTransaction.total).toLocaleString('id-ID') }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex border-t-4 border-black font-black uppercase italic">
                    <button @click="() => { showDetail = false; openEdit(selectedTransaction); }" class="flex-1 p-4 bg-blue-500 text-white border-r-4 border-black hover:bg-blue-600">Edit Transaksi</button>
                    <button @click="showDetail = false" class="flex-1 p-4 bg-black text-white hover:bg-gray-800">Tutup</button>
                </div>
            </div>
        </div>

        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-5xl font-black uppercase italic tracking-tighter leading-none">Transaksi</h1>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mt-2">Log penjualan dan arus kas</p>
            </div>
            <button v-if="!showForm" @click="openCreate" class="bg-yellow-400 border-4 border-black px-10 py-4 font-black uppercase shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:translate-x-1 hover:translate-y-1 hover:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] transition-all active:translate-x-1.5 active:translate-y-1.5 active:shadow-none">
                Tambahkan
            </button>
        </div>

        <DataTable :resource="transactions" :columns="columns">
            <template #payment_name="{ value }">
                <span class="bg-blue-600 text-white border-2 border-black px-3 py-1 text-[9px] font-black uppercase italic shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">{{ value || 'Tunai' }}</span>
            </template>
            <template #total="{ value }">
                <span class="font-mono font-black text-xl text-black">{{ Number(value).toLocaleString('id-ID') }}</span>
            </template>
            <template #actions="{ row }">
                <div class="flex flex-row gap-x-5 justify-end items-center">
                    <button @click="openDetail(row)" title="Detail" class="hover:scale-150 transition-transform">🔎</button>
                    <button @click="openEdit(row)" title="Edit" class="hover:scale-150 transition-transform">✏️</button>
                    <button @click="deleteTransaction(row.id)" class="hover:scale-150 transition-transform" title="Hapus">❌</button>
                </div>
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>