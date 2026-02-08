<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    transactions: Object, 
    stores: Array, 
    transTypes: Array,
    walletStores: Array, 
    wallets: Array, 
    filters: Object,
});

const showForm = ref(false);

const columns = [
    { label: 'Toko / Pelanggan', key: 'store' }, 
    { label: 'Layanan', key: 'trans_type' },
    { label: 'Potong Saldo', key: 'nominal_request' },
    { label: 'Bayar (Kas)', key: 'nominal_pay' }
];

const form = useForm({
    id: null,
    store_id: '',
    cust_account_number: '',
    nominal_request: 0,
    nominal_pay: 0,
    digital_wallet_store_id: '',
    topup_trans_type_id: '',
});

/**
 * MAPPING & FILTER LOGIC
 */
const walletNamesMapping = computed(() => {
    const map = {};
    props.wallets?.forEach(wallet => {
        map[wallet.id] = wallet.name;
    });
    return map;
});

const filteredWallets = computed(() => {
    if (!form.store_id) return [];
    return props.walletStores.filter(wallet => wallet.store_id === form.store_id);
});

// Reset wallet pilihan jika toko berubah (hanya jika tidak sedang edit)
watch(() => form.store_id, (newVal, oldVal) => {
    if (oldVal && form.id === null) {
        form.digital_wallet_store_id = '';
    }
});

/**
 * ACTIONS
 */
const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.cust_account_number = row.cust_account_number;
    form.nominal_request = row.nominal_request;
    form.nominal_pay = row.nominal_pay;
    form.digital_wallet_store_id = row.digital_wallet_store_id;
    form.topup_trans_type_id = row.topup_trans_type_id;
    showForm.value = true;
};

const submit = () => {
    if (form.id) {
        form.patch(route('topup-transactions.update', form.id), {
            onSuccess: () => { showForm.value = false; form.reset(); },
        });
    } else {
        form.post(route('topup-transactions.store'), {
            onSuccess: () => { showForm.value = false; form.reset(); },
            onError: (err) => { if(err.error) alert(err.error) }
        });
    }
};

const destroy = (id) => {
    if (confirm('Hapus transaksi? Saldo akan dikembalikan otomatis ke toko.')) {
        form.delete(route('topup-transactions.destroy', id));
    }
};

const formatIDR = (val) => new Intl.NumberFormat('id-ID').format(val);
</script>

<template>
    <Head title="Transaksi Topup" />

    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showForm = false"></div>
                
                <div class="relative w-full max-w-5xl bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden animate-in zoom-in-95 duration-200">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-sm font-black text-gray-700 uppercase tracking-tight italic">
                            ✏️ Edit Transaksi Topup
                        </h3>
                        <button @click="showForm = false" class="text-[10px] font-bold text-gray-400 hover:text-red-500 uppercase tracking-widest transition-colors">Tutup [X]</button>
                    </div>

                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Pilih Toko</label>
                                <select v-model="form.store_id" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                    <option value="" disabled>Pilih Toko</option>
                                    <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Layanan</label>
                                <select v-model="form.topup_trans_type_id" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                    <option value="" disabled>Pilih Layanan</option>
                                    <option v-for="t in transTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">No. Akun/Tujuan</label>
                                <input v-model="form.cust_account_number" type="text" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none bg-white" />
                            </div>

                            <div class="flex flex-col gap-1 lg:col-span-1">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Saldo Sumber</label>
                                <select v-model="form.digital_wallet_store_id" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                    <option value="" disabled>{{ form.store_id ? 'Pilih Saldo' : 'Pilih Toko Dahulu' }}</option>
                                    <option v-for="w in filteredWallets" :key="w.id" :value="w.id">
                                        {{ walletNamesMapping[w.digital_wallet_id] }} - [{{ formatIDR(w.balance) }}]
                                    </option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-red-500 uppercase tracking-widest ml-1">Nominal Topup</label>
                                <input v-model="form.nominal_request" type="number" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-bold focus:ring-2 focus:ring-red-500 outline-none bg-white" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-green-600 uppercase tracking-widest ml-1">Harga Jual (Kas Masuk)</label>
                                <input v-model="form.nominal_pay" type="number" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-bold focus:ring-2 focus:ring-green-500 outline-none bg-blue-50" />
                            </div>
                        </div>

                        <div class="mt-10 flex justify-end gap-3 border-t border-gray-100 pt-6">
                            <button @click="showForm = false" class="bg-white text-gray-500 border border-gray-200 px-8 py-2.5 rounded-lg font-black text-xs uppercase tracking-widest hover:bg-gray-50 transition-all">
                                Batalkan
                            </button>
                            <button @click="submit" :disabled="form.processing" class="bg-black text-white px-8 py-2.5 rounded-lg font-black text-xs uppercase tracking-widest hover:bg-gray-800 transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px]">
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <DataTable 
                title="Transaksi Topup"
                :resource="transactions" 
                :columns="columns"
                :showAddButton="false"
                route-name="topup-transactions.index" 
                :initial-search="filters?.search || ''"
            >
                <template #store="{ row }">
                    <div class="flex flex-col">
                        <span class="text-xs font-black uppercase tracking-tight text-gray-900">{{ row.store?.name }}</span>
                        <span class="text-[10px] font-bold text-blue-600 tracking-wider">{{ row.cust_account_number }}</span>
                    </div>
                </template>

                <template #trans_type="{ row }">
                    <span class="px-2 py-1 rounded bg-gray-100 text-gray-800 text-[10px] font-black uppercase">
                        {{ row.trans_type?.name }}
                    </span>
                </template>

                <template #nominal_request="{ value }">
                    <span class="text-red-600 font-bold italic">- {{ formatIDR(value) }}</span>
                </template>

                <template #nominal_pay="{ value }">
                    <span class="text-green-600 font-bold">{{ formatIDR(value) }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex flex-row gap-4 justify-end items-center">
                        <button @click="openEdit(row)" class="text-lg hover:scale-125 transition-transform" title="Edit Data">✏️</button>
                        <button @click="destroy(row.id)" class="text-lg hover:scale-125 transition-transform" title="Hapus Data">❌</button>
                    </div>
                </template>
            </DataTable>

        </div>
    </AuthenticatedLayout>
</template>