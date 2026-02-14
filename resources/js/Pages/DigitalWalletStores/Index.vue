<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    resource: Object, 
    filters: Object,
});

// --- LOGIKA ACCORDION ---
const expandedStore = ref(null);
const toggleAccordion = (id) => {
    expandedStore.value = expandedStore.value === id ? null : id;
};

// --- LOGIKA FORM EDIT ---
const activeEditId = ref(null);
const form = useForm({
    id: null,
    store_id: '',
    digital_wallet_id: '',
    balance: 0,
    action_type: 'add',
});

const openEdit = (walletItem) => {
    form.clearErrors();
    form.id = walletItem.id;
    form.store_id = walletItem.store_id;
    form.digital_wallet_id = walletItem.digital_wallet_id;
    form.balance = 0; 
    form.action_type = 'add';
    activeEditId.value = walletItem.id;
};

const cancelEdit = () => {
    activeEditId.value = null;
    form.reset();
};

const formatIDR = (num) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', currency: 'IDR', minimumFractionDigits: 0 
}).format(num);

const submit = () => {
    if (form.action_type === 'reset') form.balance = 0;
    
    form.post(route('digital-wallet-store.store'), {
        preserveScroll: true,
        onSuccess: () => { 
            activeEditId.value = null;
            form.reset();
        },
    });
};

// Konfigurasi Kolom untuk DataTable (Icon Sorting otomatis diatur di sini)
const columns = [
    { key: 'store_name', label: 'Unit Toko', sortable: true },
    { key: 'total_balance', label: 'Akumulasi Saldo', sortable: true },
];
</script>

<template>
    <Head title="Monitoring Saldo Toko" />

    <AuthenticatedLayout>
        <div class="p-8 text-left">
            <DataTable
                title="Saldo Toko"
                subtitle="Akumulasi Saldo E-Wallet Per Unit"
                :resource="resource"
                :columns="columns"
                route-name="digital-wallet-store.index"
                :filters="filters"
                placeholder="CARI UNIT TOKO..."
            >
                <template #store_name="{ row }">
                    <div @click="toggleAccordion(row.id)" class="flex items-center gap-3 font-black uppercase italic text-gray-800 cursor-pointer group">
                        <div class="w-5 h-5 flex items-center justify-center rounded border border-blue-600 bg-blue-50 text-[10px] text-blue-600 transition-transform" 
                            :class="expandedStore === row.id ? 'rotate-180 bg-blue-600 text-white' : ''">
                            ▼
                        </div>
                        <span>{{ row.store_name }}</span>
                        <span class="text-[9px] bg-gray-100 px-2 py-0.5 rounded text-gray-400 font-bold not-italic tracking-normal uppercase">
                            {{ row.wallets.length }} WALLET
                        </span>
                    </div>
                </template>

                <template #total_balance="{ row }">
                    <div class="text-right font-black text-sm text-gray-700">
                        <span class="bg-gray-100 px-2 py-1 rounded border border-gray-200">
                            {{ formatIDR(row.total_balance) }}
                        </span>
                    </div>

                    <div v-if="expandedStore === row.id" class="mt-6 text-left italic">
                        <div class="flex flex-col gap-4 bg-gray-50/50 p-4 rounded-lg border border-dashed border-gray-200">
                            <div v-for="w in row.wallets" :key="w.id" class="flex gap-4 items-stretch">
                                
                                <div class="flex-1 bg-white border border-gray-200 rounded-xl p-4 shadow-sm flex items-center justify-between transition-all"
                                     :class="activeEditId === w.id ? 'border-blue-500 ring-2 ring-blue-50' : ''">
                                    <div class="flex flex-col">
                                        <span class="text-[8px] font-black text-gray-300 uppercase mb-1 tracking-widest not-italic">Platform</span>
                                        <span class="text-sm font-black text-gray-800 uppercase italic">{{ w.wallet_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-8">
                                        <div class="text-right flex flex-col">
                                            <span class="text-[8px] font-black text-gray-300 uppercase mb-1 tracking-widest not-italic">Saldo Saat Ini</span>
                                            <span class="text-sm font-black text-blue-600 italic">{{ formatIDR(w.balance) }}</span>
                                        </div>
                                        <button v-if="activeEditId !== w.id" @click.stop="openEdit(w)" class="text-lg opacity-60 hover:opacity-100 transition-transform active:scale-90">✏️</button>
                                        <div v-else class="w-[28px]"></div>
                                    </div>
                                </div>

                                <div v-if="activeEditId === w.id" class="w-1/2 bg-white border border-blue-500 rounded-xl p-5 shadow-lg relative animate-in slide-in-from-left-2 duration-200">
                                    <button @click="cancelEdit" class="absolute top-2 right-3 text-gray-300 hover:text-red-500 font-black not-italic">✕</button>
                                    <form @submit.prevent="submit" class="flex flex-col gap-4 pt-1">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="flex flex-col">
                                                <label class="text-[8px] font-black text-blue-600 uppercase tracking-widest mb-1 not-italic">Aksi</label>
                                                <select v-model="form.action_type" class="w-full border border-gray-200 rounded-lg p-2 text-xs font-black uppercase italic bg-gray-50 outline-none focus:ring-1 focus:ring-blue-500">
                                                    <option value="add">Tambahkan (+)</option>
                                                    <option value="subtract">Kurangi (-)</option>
                                                    <option value="reset">Reset Saldo</option>
                                                </select>
                                            </div>
                                            <div class="flex flex-col">
                                                <label class="text-[8px] font-black text-blue-600 uppercase tracking-widest mb-1 not-italic">Nominal (Rp)</label>
                                                <input v-model="form.balance" type="number" :disabled="form.action_type === 'reset'" class="no-spinner w-full border border-gray-200 rounded-lg p-2 font-black text-sm italic focus:ring-1 focus:ring-blue-500 outline-none" placeholder="0" />
                                            </div>
                                        </div>
                                        <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-black uppercase text-[10px] hover:bg-blue-700 transition-all shadow-md shadow-blue-100 active:scale-[0.98]">Update Saldo</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-spinner::-webkit-inner-spin-button,
.no-spinner::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.no-spinner {
    -moz-appearance: textfield;
}
</style>