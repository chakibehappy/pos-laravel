<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    transactions: Object, // Diubah menjadi Object karena paginate()
    stores: Array, 
    transTypes: Array,
    walletStores: Array, 
    wallets: Array, 
    filters: Object,
});

const editMode = ref(false);
const search = ref(props.filters.search || '');

/**
 * SEARCH LOGIC (Debounced)
 */
watch(search, debounce((value) => {
    router.get(
        route('topup-transactions.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 500));

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
 * MAPPING NAMA WALLET
 */
const walletNamesMapping = computed(() => {
    const map = {};
    if (props.wallets) {
        props.wallets.forEach(wallet => {
            map[wallet.id] = wallet.name;
        });
    }
    return map;
});

/**
 * FILTER SALDO BERDASARKAN TOKO
 */
const filteredWallets = computed(() => {
    if (!form.store_id) return [];
    return props.walletStores.filter(wallet => wallet.store_id === form.store_id);
});

watch(() => form.store_id, () => {
    if (!editMode.value) {
        form.digital_wallet_store_id = '';
    }
});

const submit = () => {
    if (editMode.value) {
        form.put(route('topup-transactions.update', form.id), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('topup-transactions.store'), {
            onSuccess: () => resetForm(),
            onError: (errors) => {
                if(errors.error) alert(errors.error); 
            }
        });
    }
};

const editData = (item) => {
    editMode.value = true;
    form.id = item.id;
    form.store_id = item.store_id;
    form.cust_account_number = item.cust_account_number;
    form.nominal_request = item.nominal_request;
    form.nominal_pay = item.nominal_pay;
    form.digital_wallet_store_id = item.digital_wallet_store_id;
    form.topup_trans_type_id = item.topup_trans_type_id;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const deleteData = (id) => {
    if (confirm('Hapus transaksi? Saldo akan dikembalikan otomatis ke toko.')) {
        form.delete(route('topup-transactions.destroy', id));
    }
};

const resetForm = () => {
    form.reset();
    editMode.value = false;
};

const formatIDR = (val) => new Intl.NumberFormat('id-ID').format(val);
</script>

<template>
    <Head title="Transaksi Topup" />

    <AuthenticatedLayout>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter text-black">Transaksi Topup</h1>
                <p class="text-[10px] font-black uppercase text-gray-400 italic">Arsip Penjualan & Pemotongan Saldo Otomatis</p>
            </div>
            
            <div class="w-full md:w-64">
                <input 
                    v-model="search"
                    type="text" 
                    placeholder="CARI TRANSAKSI..." 
                    class="w-full border-4 border-black p-2 font-black uppercase italic text-xs shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] outline-none focus:bg-yellow-400 transition-colors"
                />
            </div>
        </div>

        <div v-if="$page.props.errors.error" class="mb-4 border-4 border-black bg-red-500 p-4 text-white font-black uppercase italic animate-pulse">
            ⚠️ {{ $page.props.errors.error }}
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-8 items-start">
            
            <div class="xl:col-span-1 border-4 border-black p-6 bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                <div class="flex justify-between items-center mb-6 border-b-4 border-black pb-2">
                    <h3 class="font-black uppercase italic text-black text-xs">
                        {{ editMode ? 'Edit Transaksi' : 'Input Baru' }}
                    </h3>
                    <button v-if="editMode" @click="resetForm" class="text-[10px] font-black uppercase text-red-600 underline">Batal</button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 text-black">Pilih Toko</label>
                        <select v-model="form.store_id" class="w-full border-4 border-black p-2 font-bold outline-none text-xs" required>
                            <option value="" disabled>Pilih Toko</option>
                            <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 text-black">Layanan</label>
                        <select v-model="form.topup_trans_type_id" class="w-full border-4 border-black p-2 font-bold outline-none text-xs" required>
                            <option value="" disabled>Pilih Layanan</option>
                            <option v-for="t in transTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 text-black">No. Akun/Tujuan</label>
                        <input v-model="form.cust_account_number" type="text" placeholder="08xxxxxxx" 
                            class="w-full border-4 border-black p-2 font-bold outline-none text-xs" required />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-1 text-black">Nominal Topup</label>
                            <input v-model="form.nominal_request" type="number" 
                                class="w-full border-4 border-black p-2 font-bold outline-none text-xs" required />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-1 text-black">Harga Jual</label>
                            <input v-model="form.nominal_pay" type="number" 
                                class="w-full border-4 border-black p-2 font-bold outline-none text-xs bg-yellow-50" required />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 text-black">Saldo Sumber</label>
                        <select v-model="form.digital_wallet_store_id" class="w-full border-4 border-black p-2 font-bold outline-none text-xs" required>
                            <option value="" disabled>
                                {{ form.store_id ? 'Pilih Saldo' : 'Pilih Toko Dahulu' }}
                            </option>
                            <option v-for="w in filteredWallets" :key="w.id" :value="w.id">
                                {{ walletNamesMapping[w.digital_wallet_id] || 'Wallet' }} - [{{ formatIDR(w.balance) }}]
                            </option>
                        </select>
                    </div>

                    <button :disabled="form.processing" 
                        class="w-full bg-black text-white py-4 font-black uppercase border-2 border-black hover:bg-yellow-400 hover:text-black hover:shadow-none transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:translate-y-1 disabled:opacity-50">
                        {{ form.processing ? 'Memproses...' : (editMode ? 'Update' : 'Simpan') }}
                    </button>
                </form>
            </div>

            <div class="xl:col-span-3">
                <div class="border-4 border-black bg-white overflow-hidden shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-black text-white text-[10px] font-black uppercase italic">
                                <tr>
                                    <th class="p-3 border-r border-gray-800">Toko / Akun Pelanggan</th>
                                    <th class="p-3 border-r border-gray-800 text-center">Layanan</th>
                                    <th class="p-3 border-r border-gray-800 text-right">Potong Saldo</th>
                                    <th class="p-3 border-r border-gray-800 text-right">Bayar (Kas)</th>
                                    <th class="p-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-4 divide-black text-[11px] font-bold">
                                <tr v-for="item in transactions.data" :key="item.id" class="hover:bg-yellow-50 transition-colors text-black">
                                    <td class="p-3 border-r-4 border-black">
                                        <div class="font-black uppercase italic text-xs leading-tight">{{ item.store?.name }}</div>
                                        <div class="text-[9px] text-blue-600 font-black tracking-wider">{{ item.cust_account_number }}</div>
                                    </td>
                                    <td class="p-3 border-r-4 border-black text-center">
                                        <span class="bg-black text-white px-2 py-0.5 uppercase italic text-[9px] font-black">
                                            {{ item.trans_type?.name }}
                                        </span>
                                    </td>
                                    <td class="p-3 border-r-4 border-black text-right font-black italic text-red-600">
                                        - {{ formatIDR(item.nominal_request) }}
                                    </td>
                                    <td class="p-3 border-r-4 border-black text-right font-black text-green-600 bg-green-50/30">
                                        {{ formatIDR(item.nominal_pay) }}
                                    </td>
                                    <td class="p-3 text-center space-x-3">
                                        <button @click="editData(item)" class="text-blue-600 hover:scale-125 transition-transform uppercase font-black italic">Edit</button>
                                        <button @click="deleteData(item.id)" class="text-red-600 hover:scale-125 transition-transform uppercase font-black italic">Hapus</button>
                                    </td>
                                </tr>
                                <tr v-if="transactions.data.length === 0">
                                    <td colspan="5" class="p-10 text-center font-black uppercase italic text-gray-400">Data tidak ditemukan</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap justify-center gap-2">
                    <template v-for="(link, k) in transactions.links" :key="k">
                        <Link 
                            v-if="link.url" 
                            :href="link.url" 
                            v-html="link.label"
                            class="px-4 py-2 border-2 border-black font-black uppercase text-[10px] transition-all shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:translate-x-[1px] hover:translate-y-[1px] hover:shadow-none"
                            :class="{'bg-yellow-400': link.active, 'bg-white': !link.active}"
                        />
                        <span 
                            v-else 
                            v-html="link.label" 
                            class="px-4 py-2 border-2 border-gray-300 text-gray-400 font-black uppercase text-[10px] italic bg-gray-50"
                        ></span>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>