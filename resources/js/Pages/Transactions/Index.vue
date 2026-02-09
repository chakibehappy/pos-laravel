<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, Head, usePage, router } from '@inertiajs/vue3'; 
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

// IMPORT VIEW DETAIL
import TransactionDetailView from '@/Pages/TransactionsDetails/Index.vue';

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

// STATE DETAIL MODAL
const isDetailModalOpen = ref(false);
const selectedTransactionId = ref(null);
const detailRef = ref(null);

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

// --- LOGIKA ---

watch(() => form.store_id, (newStore, oldStore) => {
    if (oldStore && !isEditMode.value) {
        form.details = [];
    }
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
            name: `${w.wallet?.name || w.name || 'Dompet Toko'} (Sisa: Rp ${Number(w.balance || 0).toLocaleString('id-ID')})`,
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
            .map(p => ({
                id: p.id, 
                name: `${p.name} (Stok: ${stockLookup[p.id] || 0})`, 
                raw_name: p.name, 
                price: p.price, 
                stock: stockLookup[p.id] || 0
            }))
            .filter(p => isEditMode.value ? true : p.stock > 0);

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
        if (!isEditMode.value && singleEntry.value.quantity > p.stock) {
            errorMessage.value = `Stok tidak mencukupi! (Sisa: ${p.stock})`;
            return;
        }
        form.details.push({
            type: 'produk', product_id: p.id, name: p.raw_name, note: '-', price: p.price,
            quantity: singleEntry.value.quantity, subtotal: p.price * singleEntry.value.quantity, meta: null
        });
    } else if (singleEntry.value.type === 'topup') {
        const s = productOptions.value.find(x => x.id == singleEntry.value.product_id);
        const w = filteredWallets.value.find(x => String(x.id) === String(singleEntry.value.digital_wallet_store_id));
        if (!s || !singleEntry.value.account_number || !w) { errorMessage.value = "Lengkapi data Top Up & Pilih Sumber Saldo!"; return; }
        
        if (!isEditMode.value && singleEntry.value.nominal > w.balance) {
            errorMessage.value = `Saldo dompet tidak mencukupi! (Sisa: Rp ${Number(w.balance).toLocaleString('id-ID')})`;
            return;
        }

        form.details.push({
            type: 'topup', 
            product_id: null,
            name: s.raw_name,
            // Nomor tujuan dimasukkan langsung ke kolom keterangan
            note: singleEntry.value.account_number,
            price: singleEntry.value.price, 
            quantity: 1, 
            subtotal: singleEntry.value.price,
            meta: { 
                target: singleEntry.value.account_number, 
                nominal_topup: singleEntry.value.nominal, 
                digital_wallet_store_id: w.id,
                topup_trans_type_id: s.id 
            }
        });
    } else if (singleEntry.value.type === 'tarik_tunai') {
        const wType = withdrawalTypeOptions.value.find(x => x.id == singleEntry.value.withdrawal_source_id);
        if (!singleEntry.value.customer_name || !singleEntry.value.withdrawal_amount || !wType) {
            errorMessage.value = "Lengkapi data Tarik Tunai!"; return;
        }
        const combinedPrice = Number(singleEntry.value.withdrawal_amount) + Number(singleEntry.value.admin_fee);
        form.details.push({
            type: 'tarik_tunai', product_id: null, name: `TARIK TUNAI [${wType.name}]`,
            note: singleEntry.value.customer_name,
            price: combinedPrice, quantity: 1, subtotal: combinedPrice, 
            meta: { customer_name: singleEntry.value.customer_name, amount: singleEntry.value.withdrawal_amount, fee: singleEntry.value.admin_fee, withdrawal_source_id: wType.id }
        });
    }
    
    calculateAll();

    const savedType = singleEntry.value.type;
    Object.assign(singleEntry.value, { 
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
    singleEntry.value.type = savedType;
};

const closeForm = () => {
    showForm.value = false;
    isEditMode.value = false;
    errorMessage.value = '';
    form.reset();
    form.details = [];
    Object.assign(singleEntry.value, { 
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
};

const submit = () => {
    errorMessage.value = '';
    if (form.details.length === 0) { errorMessage.value = "Keranjang masih kosong!"; return; }
    if (!form.payment_id) { errorMessage.value = "Pilih metode pembayaran!"; return; }

    const options = {
        onSuccess: () => {
            closeForm();
        },
        preserveScroll: true,
        onError: (errors) => {
            errorMessage.value = errors.message || "Terjadi kesalahan saat menyimpan.";
        }
    };

    if (isEditMode.value) {
        form.post(route('transactions.update', form.id), options);
    } else {
        form.post(route('transactions.store'), options);
    }
};

const openCreate = () => {
    isEditMode.value = false;
    form.reset();
    form.id = null; 
    form.details = [];
    form.transaction_at = new Date().toISOString().slice(0, 16);
    const matchedUser = props.pos_users.find(u => u.username === page.props.auth.user.email);
    if (matchedUser) form.pos_user_id = matchedUser.id;
    showForm.value = true;
};

const openEdit = (row) => {
    isEditMode.value = true;
    form.id = row.id;
    form.store_id = row.store_id;
    form.pos_user_id = row.pos_user_id;
    form.payment_id = row.payment_id;
    form.transaction_at = row.transaction_at.replace(' ', 'T').slice(0, 16);
    form.tax = row.tax || 0;
    
    form.details = row.details.map(d => ({
        type: d.topup_transaction_id ? 'topup' : (d.cash_withdrawal_id ? 'tarik_tunai' : 'produk'),
        product_id: d.product_id,
        name: d.product?.name || (d.topup_transaction_id ? 'TOPUP' : (d.cash_withdrawal_id ? 'TARIK TUNAI' : 'PRODUK')),
        note: d.topup_transaction?.cust_account_number ? d.topup_transaction.cust_account_number : (d.cash_withdrawal?.customer_name ? d.cash_withdrawal.customer_name : '-'),
        price: d.selling_prices,
        quantity: d.quantity,
        subtotal: d.subtotal,
        meta: d.topup_transaction_id ? { 
            target: d.topup_transaction?.cust_account_number || '', 
            nominal_topup: d.topup_transaction?.nominal_request || 0,
            digital_wallet_store_id: d.topup_transaction?.digital_wallet_store_id,
            topup_trans_type_id: d.topup_transaction?.topup_trans_type_id
        } : (d.cash_withdrawal_id ? { 
            customer_name: d.cash_withdrawal?.customer_name || '',
            amount: d.cash_withdrawal?.withdrawal_count || 0,
            fee: d.cash_withdrawal?.admin_fee || 0,
            withdrawal_source_id: d.cash_withdrawal?.withdrawal_source_id
        } : null)
    }));

    calculateAll();
    showForm.value = true;
};

const handleOpenDetail = (id) => {
    selectedTransactionId.value = id;
    isDetailModalOpen.value = true;
    setTimeout(() => { detailRef.value?.fetchDetails(); }, 50);
};

const confirmDelete = (row) => {
    if (confirm(`Apakah Anda yakin ingin membatalkan transaksi #${row.id}?`)) {
        router.delete(route('transactions.destroy', row.id));
    }
};

const formatDate = (date) => new Date(date).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <Head title="Master Transaksi" />
    <AuthenticatedLayout>
        <div class="p-8">
            <div v-if="showForm" :class="isEditMode ? 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4' : ''">
                <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-md relative" :class="isEditMode ? 'w-full max-w-6xl max-h-[90vh] overflow-y-auto' : 'mb-8'">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-black uppercase tracking-tighter text-gray-800">
                            {{ isEditMode ? 'Edit Transaksi #' + form.id : 'Input Transaksi Baru' }}
                        </h2>
                        <button @click="closeForm" class="text-gray-400 hover:text-red-500 font-bold">✕ Close</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <SearchableSelect label="Toko" v-model="form.store_id" :options="stores" :disabled="isEditMode" />
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase">Waktu Transaksi</label>
                            <input v-model="form.transaction_at" type="datetime-local" class="border border-gray-300 rounded-lg p-2 text-sm h-[38px] outline-none" />
                        </div>
                        <SearchableSelect label="Kasir (Nota)" v-model="form.pos_user_id" :options="pos_users" />
                        <SearchableSelect label="Metode Bayar" v-model="form.payment_id" :options="paymentMethods" />
                    </div>

                    <div class="p-4 bg-gray-50 rounded-lg grid grid-cols-1 md:grid-cols-12 gap-4 items-end mb-6 border border-dashed border-gray-300">
                        <div class="md:col-span-2 flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase">Jenis</label>
                            <select v-model="singleEntry.type" class="border border-gray-300 rounded-lg p-2 text-xs h-[38px] bg-white outline-none">
                                <option value="">PILIH...</option>
                                <option value="produk">📦 PRODUK</option>
                                <option value="topup">📱 TOP UP</option>
                                <option value="tarik_tunai">💸 TARIK TUNAI</option>
                            </select>
                        </div>
                        
                        <template v-if="singleEntry.type === 'produk'">
                            <div class="md:col-span-6"><SearchableSelect label="Pilih Produk" v-model="singleEntry.product_id" :options="productOptions" /></div>
                            <div class="md:col-span-2">
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Qty</label>
                                <input v-model.number="singleEntry.quantity" type="number" min="1" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm" />
                            </div>
                        </template>

                        <template v-if="singleEntry.type === 'topup'">
                            <div class="md:col-span-2"><SearchableSelect label="Layanan" v-model="singleEntry.product_id" :options="productOptions" /></div>
                            <div class="md:col-span-2"><label class="text-[10px] font-bold text-gray-400 uppercase">No. Tujuan</label><input v-model="singleEntry.account_number" type="text" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm" /></div>
                            <div class="md:col-span-2"><label class="text-[10px] font-bold text-gray-400 uppercase">Nominal</label><input v-model.number="singleEntry.nominal" type="number" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm" /></div>
                            <div class="md:col-span-2">
                                <label class="text-[10px] font-bold text-blue-600 uppercase">Sumber Saldo</label>
                                <select v-model="singleEntry.digital_wallet_store_id" class="border-2 border-blue-200 rounded-lg p-2 text-xs h-[38px] w-full bg-white">
                                    <option value="">PILIH DOMPET...</option>
                                    <option v-for="wallet in filteredWallets" :key="wallet.id" :value="wallet.id">{{ wallet.name }}</option>
                                </select>
                            </div>
                            <div class="md:col-span-2"><label class="text-[10px] font-bold text-gray-400 uppercase">Harga Jual</label><input v-model.number="singleEntry.price" type="number" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm" /></div>
                        </template>

                        <template v-if="singleEntry.type === 'tarik_tunai'">
                            <div class="md:col-span-3"><label class="text-[10px] font-bold text-gray-400 uppercase">Nama Pelanggan</label><input v-model="singleEntry.customer_name" type="text" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm" /></div>
                            <div class="md:col-span-3"><SearchableSelect label="Jenis Tarik" v-model="singleEntry.withdrawal_source_id" :options="withdrawalTypeOptions" /></div>
                            <div class="md:col-span-2"><label class="text-[10px] font-bold text-gray-400 uppercase">Nominal</label><input v-model.number="singleEntry.withdrawal_amount" type="number" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm" /></div>
                            <div class="md:col-span-2"><label class="text-[10px] font-bold text-gray-400 uppercase">Biaya Admin</label><input v-model.number="singleEntry.admin_fee" type="number" class="w-full border border-gray-300 p-2 rounded-lg h-[38px] text-sm" /></div>
                        </template>

                        <div v-if="singleEntry.type" class="md:col-span-2">
                            <button @click="addToBatch" class="w-full bg-blue-600 text-white rounded-lg h-[38px] font-bold text-xs uppercase hover:bg-blue-700 shadow-sm">+ Tambah</button>
                        </div>
                    </div>

                    <div v-if="form.details.length > 0" class="border rounded-lg overflow-hidden mb-6 bg-white shadow-sm">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-100 uppercase font-bold text-gray-500 border-b">
                                <tr>
                                    <th class="p-3 text-left w-20">Jenis</th>
                                    <th class="p-3 text-left">Item / Detail</th>
                                    <th class="p-3 text-left">Keterangan</th>
                                    <th class="p-3 text-right">Harga</th>
                                    <th class="p-3 text-center">Qty</th>
                                    <th class="p-3 text-right">Total</th>
                                    <th class="p-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, idx) in form.details" :key="idx" class="border-b last:border-0 hover:bg-gray-50">
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded-full bg-gray-200 text-[9px] uppercase font-bold text-gray-600">{{ item.type.replace('_', ' ') }}</span>
                                    </td>
                                    <td class="p-3 font-bold uppercase">{{ item.name }}</td>
                                    <td class="p-3 text-gray-500 italic">{{ item.note }}</td>
                                    <td class="p-3 text-right">{{ Number(item.price).toLocaleString('id-ID') }}</td>
                                    <td class="p-3 text-center">{{ item.quantity }}</td>
                                    <td class="p-3 text-right font-bold text-gray-800">{{ Number(item.subtotal).toLocaleString('id-ID') }}</td>
                                    <td class="p-3 text-center">
                                        <button @click="form.details.splice(idx,1); calculateAll()" class="text-red-400 hover:text-red-600 font-bold">✕</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-between items-center pt-6 border-t border-gray-100">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Grand Total</span>
                            <div class="text-3xl font-black italic text-gray-900">Rp {{ form.total.toLocaleString('id-ID') }}</div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span v-if="errorMessage" class="text-red-500 font-bold text-xs uppercase">{{ errorMessage }}</span>
                            <button @click="submit" :disabled="form.processing" class="px-10 py-3 bg-black text-white rounded-xl font-bold uppercase hover:bg-gray-800 shadow-lg transition-all active:scale-95">
                                {{ form.processing ? 'Menyimpan...' : (isEditMode ? 'Update Transaksi' : 'Simpan Transaksi') }}
                            </button>
                        </div>
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
                    <span class="font-black text-gray-900 italic">Rp {{ Number(value).toLocaleString('id-ID') }}</span> 
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center gap-2 justify-end">
                        <button @click="handleOpenDetail(row.id)" class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg font-black text-[10px] uppercase hover:bg-blue-600 hover:text-white transition-all shadow-sm border border-blue-100">🔎</button>
                        <button @click="openEdit(row)" class="px-3 py-1 bg-amber-50 text-amber-600 rounded-lg font-black text-[10px] uppercase hover:bg-amber-500 hover:text-white transition-all shadow-sm border border-amber-100">✏️</button>
                        <button @click="confirmDelete(row)" class="px-3 py-1 bg-red-50 text-red-600 rounded-lg font-black text-[10px] uppercase hover:bg-red-500 hover:text-white transition-all shadow-sm border border-red-100">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>

        <TransactionDetailView 
            ref="detailRef"
            :show="isDetailModalOpen" 
            :transactionId="selectedTransactionId" 
            @close="isDetailModalOpen = false" 
        />
    </AuthenticatedLayout>
</template>