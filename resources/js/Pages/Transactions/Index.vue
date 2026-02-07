<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, Head, usePage } from '@inertiajs/vue3'; 
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({ 
    transactions: Object,
    stores: Array, 
    pos_users: Array, 
    products: Array, 
    topup_trans_types: Array,
    store_products: Array, 
    paymentMethods: Array,
    digital_wallet_stores: Array, 
    withdrawal_source_type: Array, 
    filters: Object 
});

const page = usePage();

const columns = [
    { label: 'Tanggal', key: 'transaction_at' },
    { label: 'Toko', key: 'store_name' }, 
    { label: 'Kasir', key: 'cashier_name' },
    { label: 'Metode', key: 'payment_name' }, 
    { label: 'Total (Rp)', key: 'total' }
];

const showForm = ref(false); 
const isEditMode = ref(false);
const errorMessage = ref('');
const productOptions = ref([]); 

const withdrawalTypeOptions = computed(() => {
    return (props.withdrawal_source_type || []).map(item => ({
        id: item.id,
        name: item.name
    }));
});

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

const singleEntry = ref({
    type: '', 
    product_id: '', 
    quantity: 1, 
    account_number: '',
    nominal: 0, 
    price: 0, 
    customer_name: '', 
    withdrawal_amount: 0, 
    admin_fee: 0,
    digital_wallet_store_id: '',
    withdrawal_source_id: '' 
});

// --- LOGIKA FILTER & REAKTIVITAS ---

watch(() => form.store_id, () => {
    form.details = [];
    singleEntry.value.digital_wallet_store_id = '';
    calculateAll();
    refreshProductList();
});

const filteredWallets = computed(() => {
    if (!props.digital_wallet_stores || !form.store_id) return [];
    return props.digital_wallet_stores
        .filter(w => String(w.store_id) === String(form.store_id))
        .map(w => ({
            id: w.id,
            name: w.wallet?.name || w.name || 'Dompet Toko',
            balance: w.balance || 0
        }));
});

const refreshProductList = () => {
    productOptions.value = []; 
    if (singleEntry.value.type === 'produk') {
        const stockLookup = {};
        (props.store_products || []).forEach(sp => {
            if (String(sp.store_id) === String(form.store_id)) stockLookup[sp.product_id] = sp.stock;
        });
        productOptions.value = props.products
            .filter(p => (stockLookup[p.id] || 0) > 0)
            .map(p => ({
                id: p.id, 
                name: `${p.name} (Stok: ${stockLookup[p.id]})`, 
                raw_name: p.name, 
                price: p.price, 
                stock: stockLookup[p.id]
            }));
    } else if (singleEntry.value.type === 'topup') {
        productOptions.value = props.topup_trans_types.map(t => ({ 
            id: t.id, name: t.name, raw_name: t.name, price: 0, stock: 999 
        }));
    }
};

watch(() => singleEntry.value.type, (newType) => {
    if(!newType) return;
    Object.assign(singleEntry.value, { 
        product_id: '', account_number: '', nominal: 0, price: 0, 
        customer_name: '', withdrawal_amount: 0, admin_fee: 0, 
        digital_wallet_store_id: '', withdrawal_source_id: '' 
    });
    singleEntry.value.type = newType;
    refreshProductList();
});

const calculateAll = () => {
    form.subtotal = form.details.reduce((acc, item) => acc + Number(item.subtotal), 0);
    form.total = form.subtotal + Number(form.tax);
};

const addToBatch = () => {
    errorMessage.value = '';
    if (!form.store_id) { errorMessage.value = "Pilih toko terlebih dahulu!"; return; }

    if (singleEntry.value.type === 'produk') {
        const p = productOptions.value.find(x => x.id == singleEntry.value.product_id);
        if (!p) { errorMessage.value = "Pilih produk!"; return; }
        form.details.push({
            type: 'produk', product_id: p.id, name: p.raw_name, price: p.price,
            quantity: singleEntry.value.quantity, subtotal: p.price * singleEntry.value.quantity, meta: null
        });
    } else if (singleEntry.value.type === 'topup') {
        const s = productOptions.value.find(x => x.id == singleEntry.value.product_id);
        const w = filteredWallets.value.find(x => String(x.id) === String(singleEntry.value.digital_wallet_store_id));
        if (!s || !singleEntry.value.account_number || !w) { errorMessage.value = "Lengkapi data Top Up & Pilih Sumber Saldo!"; return; }
        
        form.details.push({
            type: 'topup', 
            product_id: s.id, 
            name: `${s.raw_name} (${singleEntry.value.account_number})`,
            price: singleEntry.value.price, 
            quantity: 1, 
            subtotal: singleEntry.value.price,
            meta: { 
                target: singleEntry.value.account_number, 
                nominal_topup: singleEntry.value.nominal, 
                digital_wallet_store_id: w.id, 
                wallet_name: w.name 
            }
        });
    } else if (singleEntry.value.type === 'tarik_tunai') {
        const wType = withdrawalTypeOptions.value.find(x => x.id == singleEntry.value.withdrawal_source_id);

        if (!singleEntry.value.customer_name || !singleEntry.value.withdrawal_amount || !wType) {
            errorMessage.value = "Lengkapi data Tarik Tunai!";
            return;
        }

        // PERUBAHAN DISINI: subtotal diset 0 agar tidak menambah Grand Total
        // Namun nominal & fee tetap masuk ke meta untuk dikirim ke Controller
        form.details.push({
            type: 'tarik_tunai', 
            product_id: null, 
            name: `TARIK TUNAI [${wType.name}]`,
            price: Number(singleEntry.value.withdrawal_amount) + Number(singleEntry.value.admin_fee), 
            quantity: 1, 
            subtotal: 0, 
            meta: { 
                customer_name: singleEntry.value.customer_name, 
                amount: singleEntry.value.withdrawal_amount, 
                fee: singleEntry.value.admin_fee,           
                withdrawal_source_id: wType.id, 
                store_id: form.store_id 
            }
        });
    }
    calculateAll();
    singleEntry.value.product_id = '';
    singleEntry.value.customer_name = '';
    singleEntry.value.withdrawal_amount = 0;
    singleEntry.value.admin_fee = 0;
};

const submit = () => {
    errorMessage.value = '';
    if (form.details.length === 0) { errorMessage.value = "Keranjang masih kosong!"; return; }
    if (!form.payment_id) { errorMessage.value = "Pilih metode pembayaran!"; return; }

    const url = isEditMode.value ? route('transactions.update', form.id) : route('transactions.store');
    form[isEditMode.value ? 'put' : 'post'](url, {
        onSuccess: () => { 
            showForm.value = false; 
            form.reset(); 
            form.details = [];
        },
        onError: (err) => { 
            errorMessage.value = err.message || Object.values(err)[0] || "Gagal menyimpan transaksi."; 
        }
    });
};

const openCreate = () => {
    isEditMode.value = false;
    form.reset();
    form.details = [];
    
    const adminEmail = page.props.auth.user.email;
    const matchedUser = props.pos_users.find(u => u.username === adminEmail);
    if (matchedUser) {
        form.pos_user_id = matchedUser.id;
    }

    showForm.value = true;
};

const formatDate = (date) => new Date(date).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <Head title="Master Transaksi" />
    <AuthenticatedLayout>
        <div class="p-8">
            <div v-if="showForm" class="mb-8 p-6 bg-white rounded-xl border border-gray-200 shadow-md relative">
                <button @click="showForm = false" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 font-bold">✕</button>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <SearchableSelect label="Toko" v-model="form.store_id" :options="stores" />
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Waktu Transaksi</label>
                        <input v-model="form.transaction_at" type="datetime-local" class="border border-gray-300 rounded-lg p-2 text-sm h-[38px] outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>
                    <SearchableSelect label="Kasir (Nota)" v-model="form.pos_user_id" :options="pos_users" />
                    <SearchableSelect label="Metode Bayar" v-model="form.payment_id" :options="paymentMethods" />
                </div>

                <div class="p-4 bg-gray-50 rounded-lg grid grid-cols-1 md:grid-cols-12 gap-4 items-end mb-6 border border-dashed border-gray-300">
                    <div class="md:col-span-2 flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase">Jenis Transaksi</label>
                        <select v-model="singleEntry.type" class="border border-gray-300 rounded-lg p-2 text-xs h-[38px] bg-white outline-none">
                            <option value="">PILIH...</option>
                            <option value="produk">📦 PRODUK</option>
                            <option value="topup">📱 TOP UP</option>
                            <option value="tarik_tunai">💸 TARIK TUNAI</option>
                        </select>
                    </div>
                    
                    <template v-if="singleEntry.type === 'produk'">
                        <div class="md:col-span-6">
                            <SearchableSelect label="Pilih Produk" v-model="singleEntry.product_id" :options="productOptions" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase">Qty</label>
                            <input v-model.number="singleEntry.quantity" type="number" min="1" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm outline-none" />
                        </div>
                    </template>

                    <template v-if="singleEntry.type === 'topup'">
                        <div class="md:col-span-2"><SearchableSelect label="Layanan" v-model="singleEntry.product_id" :options="productOptions" /></div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase">No. Tujuan</label>
                            <input v-model="singleEntry.account_number" type="text" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm outline-none" placeholder="08xx..." />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase">Nominal</label>
                            <input v-model.number="singleEntry.nominal" type="number" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm outline-none" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold text-blue-600 uppercase">Sumber Saldo</label>
                            <select v-model="singleEntry.digital_wallet_store_id" class="border-2 border-blue-200 rounded-lg p-2 text-xs h-[38px] w-full bg-white outline-none">
                                <option value="">PILIH DOMPET...</option>
                                <option v-for="wallet in filteredWallets" :key="wallet.id" :value="wallet.id">{{ wallet.name }}</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase">Harga Jual</label>
                            <input v-model.number="singleEntry.price" type="number" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm outline-none" />
                        </div>
                    </template>

                    <template v-if="singleEntry.type === 'tarik_tunai'">
                        <div class="md:col-span-3">
                            <label class="text-[10px] font-bold text-gray-400 uppercase">Nama Pelanggan</label>
                            <input v-model="singleEntry.customer_name" type="text" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm outline-none" placeholder="Masukkan Nama..." />
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-[10px] font-bold text-gray-400 uppercase"></label>
                            <SearchableSelect label="Jenis Tarik" v-model="singleEntry.withdrawal_source_id" :options="withdrawalTypeOptions" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase">Nominal Tarik</label>
                            <input v-model.number="singleEntry.withdrawal_amount" type="number" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm outline-none" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase">Biaya Admin</label>
                            <input v-model.number="singleEntry.admin_fee" type="number" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm outline-none" />
                        </div>
                    </template>

                    <div v-if="singleEntry.type" class="md:col-span-2">
                        <button @click="addToBatch" class="w-full bg-blue-600 text-white rounded-lg h-[38px] font-bold text-xs uppercase hover:bg-blue-700 transition-colors shadow-sm active:scale-95">+ Tambah</button>
                    </div>
                </div>

                <div v-if="form.details.length > 0" class="border rounded-lg overflow-hidden mb-6 shadow-sm bg-white">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-100 uppercase font-bold text-gray-500 border-b">
                            <tr>
                                <th class="p-3 text-left w-20">Jenis</th>
                                <th class="p-3 text-left">Item / Detail</th>
                                <th class="p-3 text-right">Harga</th>
                                <th class="p-3 text-center">Qty</th>
                                <th class="p-3 text-right">Total</th>
                                <th class="p-3 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in form.details" :key="idx" class="border-b last:border-0 hover:bg-gray-50 transition-colors">
                                <td class="p-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full bg-gray-200 text-[9px] uppercase font-bold text-gray-600">
                                        {{ item.type.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="p-3 font-bold">
                                    {{ item.name }}
                                    <div v-if="item.meta?.wallet_name" class="text-[9px] text-blue-500 uppercase flex items-center gap-1 mt-0.5 font-medium">
                                        💳 Dompet: {{ item.meta.wallet_name }}
                                    </div>
                                    <div v-if="item.type === 'tarik_tunai'" class="text-[9px] text-orange-500 uppercase flex flex-col gap-0.5 mt-0.5 font-medium">
                                        <span>👤 Pelanggan: {{ item.meta.customer_name }}</span>
                                        <span class="bg-orange-100 px-1 py-0.5 rounded w-fit">💸 Transfer Saldo + Admin: Rp {{ Number(item.price).toLocaleString('id-ID') }}</span>
                                    </div>
                                </td>
                                <td class="p-3 text-right">{{ Number(item.price).toLocaleString('id-ID') }}</td>
                                <td class="p-3 text-center">{{ item.quantity }}</td>
                                <td class="p-3 text-right font-bold text-gray-800">{{ Number(item.subtotal).toLocaleString('id-ID') }}</td>
                                <td class="p-3 text-center">
                                    <button @click="form.details.splice(idx,1); calculateAll()" class="text-red-400 hover:text-red-600 font-bold transition-colors">✕</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center pt-6 border-t border-gray-100">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Grand Total Belanja</span>
                        <div class="text-3xl font-black italic text-gray-900">Rp {{ form.total.toLocaleString('id-ID') }}</div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span v-if="errorMessage" class="text-red-500 font-bold text-xs uppercase animate-pulse">{{ errorMessage }}</span>
                        <button @click="submit" :disabled="form.processing" class="px-10 py-3 bg-black text-white rounded-xl font-bold uppercase hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-50 shadow-lg">
                            {{ form.processing ? 'Sedang Menyimpan...' : 'Simpan Transaksi' }}
                        </button>
                    </div>
                </div>
            </div>

            <DataTable 
                title="Riwayat Transaksi" 
                :resource="transactions" 
                :columns="columns" 
                :showAddButton="true" 
                routeName="transactions.index" 
                @on-add="openCreate"
            >
                <template #transaction_at="{ value }"> 
                    <span class="text-gray-500 font-medium">{{ formatDate(value) }}</span> 
                </template>
                <template #total="{ value }"> 
                    <span class="font-bold text-blue-600">Rp {{ Number(value).toLocaleString('id-ID') }}</span> 
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>