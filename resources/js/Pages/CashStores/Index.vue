<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue'; 

const props = defineProps({
    cashBalances: Object, 
    filters: Object,
    storeTypes: Array,
    paymentMethods: Array, // Diperlukan untuk tipe non-konter (fitur lengkap)
    transactionBalances: Object, // Diperlukan untuk tipe non-konter (fitur lengkap)
});

// --- LOGIKA INTERNAL ---
const expandedStore = ref(null);
const toggleAccordion = (id) => {
    expandedStore.value = expandedStore.value === id ? null : id;
};

const activeEditId = ref(null);
const activeMethodId = ref(null); 

const form = useForm({
    id: null,
    store_id: '',
    cash: 0, // Untuk tipe konter (standar)
    action_type: 'add', 
    cash_amounts: {}, // Untuk tipe non-konter (lengkap)
    initial_cash: 0, // Untuk tipe non-konter (lengkap)
    target_method_id: null, // Untuk tipe non-konter (lengkap)
});

// MENGECEK APAKAH KAS TOKO WAJIB DIKUNCI (True jika kas > 0)
const isStoreLocked = (cashValue) => {
    const cashNum = Number(cashValue);
    return !isNaN(cashNum) && cashNum > 0;
};

// Menghitung rincian saldo dinamis per metode pembayaran (Spesifik Non-Konter)
const getMethodBalance = (storeId, method) => {
    const storeRow = props.cashBalances.data.find(row => row.store_id === storeId);
    const globalCash = storeRow ? parseFloat(storeRow.cash || 0) : 0;

    const isTunai = method.name.toLowerCase() === 'tunai';
    const storeTransactions = props.transactionBalances?.[storeId] || {};
    
    if (isTunai) {
        let nonTunaiTotal = 0;
        props.paymentMethods?.forEach(m => {
            if (m.name.toLowerCase() !== 'tunai') {
                nonTunaiTotal += parseFloat(storeTransactions[m.id] || 0);
            }
        });
        return Math.max(0, globalCash - nonTunaiTotal);
    } else {
        return parseFloat(storeTransactions[method.id] || 0);
    }
};

// Buka Modal Edit Standar (Konter)
const openEditStandard = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.cash = 0; 
    form.action_type = 'add';
    activeEditId.value = row.id;
};

// Buka Modal Edit Rincian Kartu (Non-Konter)
const openEditNonKonter = (row, methodId) => {
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.action_type = 'add';
    form.target_method_id = methodId;
    
    const amounts = {};
    props.paymentMethods?.forEach(method => {
        amounts[method.id] = 0;
    });
    form.cash_amounts = amounts;
    
    activeEditId.value = row.id;
    activeMethodId.value = methodId; 
};

// Fungsi Kas Awal (Non-Konter)
const handleAddModal = (row) => {
    if (isStoreLocked(row.cash)) {
        alert('Kas toko masih berjalan! Silakan tekan tombol Reset Global terlebih dahulu.');
        return;
    }

    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.action_type = 'set_initial'; 
    
    const tunaiMethod = props.paymentMethods?.find(m => m.name.toLowerCase() === 'tunai');
    form.target_method_id = tunaiMethod ? tunaiMethod.id : null;
    
    submit();
};

// Fungsi Tombol Reset Global (Non-Konter)
const triggerGlobalReset = (row) => {
    if (confirm(`Apakah Anda yakin ingin mereset seluruh kas pada toko ${row.store?.name || ''} menjadi 0?`)) {
        form.clearErrors();
        form.id = row.id;
        form.store_id = row.store_id;
        form.action_type = 'reset'; 
        
        form.initial_cash = 0;
        form.cash_amounts = {};
        form.target_method_id = null;
        
        submit();
    }
};

const cancelEdit = () => {
    activeEditId.value = null;
    activeMethodId.value = null;
    form.reset();
};

const formatIDR = (num) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', currency: 'IDR', minimumFractionDigits: 0 
}).format(num ?? 0);

// Pengecekan apakah baris toko saat ini bertipe konter
const isKonterType = (row) => {
    const typeObj = props.storeTypes?.find(t => t.id === row.store?.store_type_id);
    return typeObj?.name?.toLowerCase().includes('konter') ?? false;
};

const submit = () => {
    form.post(route('cash-stores.store'), {
        preserveScroll: true,
        onSuccess: () => { 
            activeEditId.value = null;
            activeMethodId.value = null;
            form.reset();
        },
    });
};

const columns = [
    { key: 'store_name', label: 'Unit Toko', sortable: true },
    { key: 'cash', label: 'Total Kas', sortable: true },
];
</script>

<template>
    <Head title="Kas Toko" />

    <AuthenticatedLayout page-title="Kas Toko" page-subtitle="Maar Company">
        <div class="p-8 text-left text-black">
            <DataTable
                title="Kas Toko"
                :resource="cashBalances"
                :columns="columns"
                route-name="cash-stores.index"
                :filters="filters"
                placeholder="CARI UNIT TOKO..."
            >
                <template #extra-filters>
                    <div class="relative w-full md:w-64">
                        <select 
                            @change="(e) => $inertia.get(route('cash-stores.index'), { ...filters, type: e.target.value, search: filters.search }, { preserveState: true })"
                            :value="filters.type || ''"
                            class="w-full border border-gray-300 rounded-lg p-2 text-sm font-black focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white shadow-sm transition-all uppercase italic appearance-none cursor-pointer"
                        >
                            <option value="">-- SEMUA KATEGORI --</option>
                            <option v-for="type in storeTypes" :key="type.id" :value="type.id">
                                {{ type.name }}
                            </option>
                        </select>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[10px]">▼</div>
                    </div>
                </template>

                <template #store_name="{ row }">
                    <div @click="toggleAccordion(row.id)" class="flex items-center gap-3 font-black uppercase italic text-gray-800 cursor-pointer group select-none">
                        <div class="w-5 h-5 flex items-center justify-center rounded border border-blue-600 bg-blue-50 text-[10px] text-blue-600 transition-transform duration-200" 
                            :class="expandedStore === row.id ? 'rotate-180 bg-blue-600 text-white' : ''">
                            ▼
                        </div>
                        <span>{{ row.store?.name }}</span>
                    </div>
                </template>

                <template #cash="{ row }">
                    <div class="text-right font-black text-sm text-gray-700">
                        <span class="bg-gray-100 px-2 py-1 rounded border border-gray-200 shadow-sm">
                            {{ formatIDR(row.cash) }}
                        </span>
                    </div>

                    <div v-if="expandedStore === row.id" class="mt-4 text-left italic">
                        
                        <!-- ==================== TAMPILAN JIKA BUKAN KONTER (RINCIAN FITUR LENGKAP) ==================== -->
                        <div v-if="!isKonterType(row)" class="flex flex-col gap-4 bg-gray-50/50 p-4 rounded-lg border border-dashed border-gray-200">
                            
                            <div class="bg-blue-50/40 border border-blue-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
                                <div class="flex flex-col justify-center">
                                    <span class="text-[9px] font-black text-blue-600 uppercase tracking-widest not-italic mb-0.5">KAS GLOBAL (AKUMULASI)</span>
                                    <span class="text-base font-black text-blue-900 uppercase italic">{{ formatIDR(row.cash) }}</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="bg-white border border-gray-200 rounded-xl p-2 flex items-center gap-3 shadow-sm h-10"
                                         :class="isStoreLocked(row.cash) ? 'opacity-50 bg-gray-100 pointer-events-none select-none' : ''">
                                        <label class="text-[9px] font-black text-blue-600 uppercase tracking-wider not-italic whitespace-nowrap pl-1">Kas Awal :</label>
                                        <input 
                                            v-model="form.initial_cash" 
                                            type="number" 
                                            :disabled="isStoreLocked(row.cash)"
                                            class="w-28 border border-gray-200 rounded-lg p-1 font-black text-xs italic focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-right h-7" 
                                            :class="isStoreLocked(row.cash) ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-gray-50 text-black'"
                                            placeholder="0" 
                                        />
                                        <button 
                                            type="button"
                                            :disabled="isStoreLocked(row.cash)"
                                            @click.stop="handleAddModal(row)"
                                            class="text-xs font-black px-4 rounded-lg shadow-md transition-colors duration-150 border-none uppercase tracking-wider whitespace-nowrap h-7 flex items-center justify-center"
                                            :class="isStoreLocked(row.cash) ? 'bg-gray-300 text-gray-400 cursor-not-allowed shadow-none' : 'bg-green-600 hover:bg-green-700 text-white'"
                                        >
                                            SUBMIT
                                        </button>
                                    </div>
                                    <button 
                                        type="button"
                                        @click.stop="triggerGlobalReset(row)"
                                        class="bg-red-600 hover:bg-red-700 text-white text-xs font-black uppercase not-italic px-4 rounded-lg shadow-md transition-colors duration-150 tracking-wider h-7 flex items-center justify-center whitespace-nowrap"
                                    >
                                        RESET GLOBAL
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 px-1 mt-2">
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-wider not-italic">Rincian Per Metode Pembayaran</span>
                                <div class="h-[1px] bg-gray-200 flex-1"></div>
                            </div>

                            <div class="flex flex-col gap-4">
                                <div v-for="method in paymentMethods" :key="method.id" class="flex gap-4 items-stretch">
                                    <div class="flex-1 p-2 flex items-center justify-between"
                                         :class="(activeEditId === row.id && activeMethodId === method.id) ? 'ring-2 ring-blue-50 rounded-xl' : ''">
                                        <div class="flex flex-col">
                                            <span class="text-[8px] font-black text-gray-300 uppercase mb-1 tracking-widest not-italic">Metode Pembayaran</span>
                                            <span class="text-sm font-black text-gray-800 uppercase italic">{{ method.name }}</span>
                                        </div>
                                        <div class="flex items-center gap-8">
                                            <div class="text-right flex flex-col">
                                                <span class="text-[8px] font-black text-gray-300 uppercase mb-1 tracking-widest not-italic">Saldo Terhitung</span>
                                                <span class="text-sm font-black text-blue-600 italic">
                                                    {{ formatIDR(getMethodBalance(row.store_id, method)) }}
                                                </span>
                                            </div>
                                            <button 
                                                v-if="!(activeEditId === row.id && activeMethodId === method.id) && ['developer', 'owner'].includes($page.props.auth.role)" 
                                                @click.stop="openEditNonKonter(row, method.id)" 
                                                class="text-lg opacity-60 hover:opacity-100 transition-opacity"
                                            >
                                                ✏️
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="activeEditId === row.id && activeMethodId === method.id" class="w-1/2 bg-white border border-blue-500 rounded-xl p-5 shadow-lg relative">
                                        <button type="button" @click="cancelEdit" class="absolute top-2 right-3 text-gray-300 hover:text-red-500 font-black transition-colors">✕</button>
                                        <form @submit.prevent="submit" class="flex flex-col gap-4">
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="flex flex-col">
                                                    <label class="text-[8px] font-black text-blue-600 uppercase tracking-widest mb-1 not-italic">Aksi Mutasi</label>
                                                    <select v-model="form.action_type" class="w-full border border-gray-200 rounded-lg p-2 text-xs font-black uppercase italic bg-gray-50 outline-none">
                                                        <option value="add">Tambahkan (+)</option>
                                                        <option value="subtract">Kurangi (-)</option>
                                                        <option value="reset_local">Reset ke 0</option>
                                                    </select>
                                                </div>
                                                <div class="flex flex-col">
                                                    <label class="text-[8px] font-black text-blue-600 uppercase tracking-widest mb-1 not-italic">Nominal {{ method.name }} (Rp)</label>
                                                    <input 
                                                        v-model="form.cash_amounts[method.id]" 
                                                        type="number" 
                                                        :disabled="form.action_type === 'reset_local'"
                                                        class="w-full border border-gray-200 rounded-lg p-2 font-black text-sm italic focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" 
                                                        :class="form.action_type === 'reset_local' ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-black'"
                                                        placeholder="0" 
                                                    />
                                                </div>
                                            </div>
                                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-black uppercase text-[10px] transition-colors">Update Via {{ method.name }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ==================== TAMPILAN JIKA ADALAH KONTER (KAS TUNAI STANDAR) ==================== -->
                        <div v-else class="flex flex-col gap-4 bg-gray-50/50 p-4 rounded-lg border border-dashed border-gray-200">
                            <div class="flex gap-4 items-stretch">
                                <div class="flex-1 bg-white border border-gray-200 rounded-xl p-4 shadow-sm flex items-center justify-between"
                                     :class="activeEditId === row.id ? 'border-blue-500 ring-2 ring-blue-50' : ''">
                                    <div class="flex flex-col">
                                        <span class="text-[8px] font-black text-gray-300 uppercase mb-1 tracking-widest not-italic">Status</span>
                                        <span class="text-sm font-black text-gray-800 uppercase italic">Kas Tunai Aktif</span>
                                    </div>
                                    <div class="flex items-center gap-8">
                                        <div class="text-right flex flex-col">
                                            <span class="text-[8px] font-black text-gray-300 uppercase mb-1 tracking-widest not-italic">Saldo Sekarang</span>
                                            <span class="text-sm font-black text-blue-600 italic">{{ formatIDR(row.cash) }}</span>
                                        </div>
                                        <button v-if="activeEditId !== row.id" @click.stop="openEditStandard(row)" class="text-lg opacity-60 hover:opacity-100">✏️</button>
                                    </div>
                                </div>

                                <div v-if="activeEditId === row.id" class="w-1/2 bg-white border border-blue-500 rounded-xl p-5 shadow-lg relative">
                                    <button type="button" @click="cancelEdit" class="absolute top-2 right-3 text-gray-300 hover:text-red-500 font-black">✕</button>
                                    <form @submit.prevent="submit" class="flex flex-col gap-4">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="flex flex-col">
                                                <label class="text-[8px] font-black text-blue-600 uppercase tracking-widest mb-1 not-italic">Aksi</label>
                                                <select v-model="form.action_type" class="w-full border border-gray-200 rounded-lg p-2 text-xs font-black uppercase italic bg-gray-50 outline-none">
                                                    <option value="add">Tambahkan (+)</option>
                                                    <option value="subtract">Kurangi (-)</option>
                                                    <option value="reset">Reset Ke 0</option>
                                                </select>
                                            </div>
                                            <div class="flex flex-col">
                                                <label class="text-[8px] font-black text-blue-600 uppercase tracking-widest mb-1 not-italic">Nominal (Rp)</label>
                                                <input v-model="form.cash" type="number" :disabled="form.action_type === 'reset'" class="w-full border border-gray-200 rounded-lg p-2 font-black text-sm italic" placeholder="0" />
                                            </div>
                                        </div>
                                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-black uppercase text-[10px]">Update Kas</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </template>

                <template #actions="{ row }">
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
input[type=number] {
    -moz-appearance: textfield;
}
</style>