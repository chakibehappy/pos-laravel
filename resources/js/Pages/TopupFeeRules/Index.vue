<script setup>
import { ref, computed } from 'vue'; // Tambahkan computed
import { useForm, Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    data: Object,
    transTypes: Array,
    walletTargets: Array, 
    filters: Object
});

const showForm = ref(false);
const isEditMode = ref(false);
const errorMessage = ref('');

const form = useForm({
    id: null,
    rules: [] 
});

const singleEntry = ref({
    topup_trans_type_id: '',
    wallet_target_id: '',
    min_limit: 0,
    max_limit: 0,
    fee: 0,
    admin_fee: 0
});

// Logic untuk mengecek apakah form edit valid
const isEditInvalid = computed(() => {
    return isEditMode.value && (!singleEntry.value.topup_trans_type_id || !singleEntry.value.wallet_target_id);
});

const getName = (list, id) => list.find(i => i.id === id)?.name || '';

const openCreate = () => {
    isEditMode.value = false;
    errorMessage.value = '';
    form.reset();
    form.rules = [];
    resetSingleEntry();
    showForm.value = true;
};

const openEdit = (row) => {
    isEditMode.value = true;
    errorMessage.value = '';
    form.clearErrors();
    form.id = row.id;
    
    singleEntry.value = {
        topup_trans_type_id: row.topup_trans_type_id,
        wallet_target_id: row.wallet_target_id,
        min_limit: row.min_limit,
        max_limit: row.max_limit,
        fee: row.fee,
        admin_fee: row.admin_fee
    };
    
    showForm.value = true;
};

const resetSingleEntry = () => {
    singleEntry.value = {
        topup_trans_type_id: '',
        wallet_target_id: '',
        min_limit: 0,
        max_limit: 0,
        fee: 0,
        admin_fee: 0
    };
};

const addToBatch = () => {
    errorMessage.value = ''; 

    if (!singleEntry.value.topup_trans_type_id || !singleEntry.value.wallet_target_id) {
        errorMessage.value = "Silakan pilih Tipe Transaksi dan Wallet Target!";
        return;
    }

    const isInQueue = form.rules.some(item => 
        item.topup_trans_type_id === singleEntry.value.topup_trans_type_id && 
        item.wallet_target_id === singleEntry.value.wallet_target_id
    );

    if (isInQueue) {
        errorMessage.value = "Item ini sudah ada di daftar antrian di bawah.";
        return;
    }

    const isInDatabase = props.data.data.some(item => 
        item.topup_trans_type_id === singleEntry.value.topup_trans_type_id && 
        item.wallet_target_id === singleEntry.value.wallet_target_id
    );

    if (isInDatabase) {
        const type = getName(props.transTypes, singleEntry.value.topup_trans_type_id);
        const target = getName(props.walletTargets, singleEntry.value.wallet_target_id);
        errorMessage.value = `Data untuk "${type}" dengan target "${target}" sudah ada di database.`;
        return;
    }

    form.rules.push({ ...singleEntry.value });
    const lastMax = singleEntry.value.max_limit;
    resetSingleEntry();
    singleEntry.value.min_limit = lastMax;
};

const removeFromBatch = (index) => {
    form.rules.splice(index, 1);
};

const submit = () => {
    // Validasi tambahan sebelum submit mode edit
    if (isEditMode.value && isEditInvalid.value) {
        alert("Tipe Transaksi dan Wallet Target tidak boleh kosong!");
        return;
    }

    let confirmMessage = isEditMode.value 
        ? "Apakah anda yakin untuk mengubah data ini?" 
        : `Simpan ${form.rules.length} data dalam antrian ini?`;

    if (isEditMode.value) form.rules = [{ ...singleEntry.value }];
    if (!isEditMode.value && form.rules.length === 0) return;

    if (confirm(confirmMessage)) {
        form.post(route('topup-fee-rules.store'), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
        });
    }
};

const destroy = (row) => {
    const typeName = row.topup_trans_type?.name || 'Unknown';
    if (confirm(`Hapus aturan biaya untuk tipe "${typeName}"?`)) {
        router.delete(route('topup-fee-rules.destroy', row.id));
    }
};

const formatCurrency = (value) => new Intl.NumberFormat('id-ID').format(value);
const displayLimit = (value) => value <= 0 ? '-' : 'Rp ' + formatCurrency(value);
</script>

<template>
    <Head title="Topup Fee Rules" />

    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="showForm" :class="isEditMode ? 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm' : 'mb-8 p-6 bg-white rounded-xl border border-gray-200 shadow-md relative animate-in fade-in zoom-in duration-200'">
                
                <button @click="showForm = false" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-red-500 hover:text-white transition-all z-10 shadow-sm">✕</button>

                <div :class="isEditMode ? 'bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-visible border border-gray-100 relative' : 'w-full'">
                    
                    <div v-if="isEditMode" class="p-6 border-b border-gray-100 bg-gray-50 rounded-t-2xl">
                        <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Edit Rule Data</h2>
                    </div>

                    <div class="px-4 pt-4" v-if="errorMessage">
                        <div class="p-3 bg-red-50 border-l-4 border-red-500 rounded-r-lg flex items-center gap-3">
                            <span class="text-red-500 font-bold">⚠️</span>
                            <p class="text-[11px] font-black text-red-700 uppercase tracking-tight">{{ errorMessage }}</p>
                        </div>
                    </div>

                    <div :class="isEditMode ? 'p-6 grid grid-cols-2 gap-4' : 'grid grid-cols-1 md:grid-cols-7 gap-3 bg-gray-50 p-4 rounded-lg border border-gray-200 overflow-visible'">
                        
                        <SearchableSelect 
                            label="Tipe Transaksi"
                            v-model="singleEntry.topup_trans_type_id"
                            :options="transTypes"
                            placeholder="Cari Tipe..."
                        />

                        <SearchableSelect 
                            label="Wallet Target"
                            v-model="singleEntry.wallet_target_id"
                            :options="walletTargets"
                            placeholder="Cari Target..."
                        />

                        <div class="flex flex-col gap-1" :class="isEditMode ? '' : 'text-xs'">
                            <label class="font-bold text-gray-400 uppercase">Min Limit</label>
                            <input v-model="singleEntry.min_limit" type="number" class="border border-gray-300 rounded-lg p-2 outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div class="flex flex-col gap-1" :class="isEditMode ? '' : 'text-xs'">
                            <label class="font-bold text-gray-400 uppercase">Max Limit</label>
                            <input v-model="singleEntry.max_limit" type="number" class="border border-gray-300 rounded-lg p-2 font-bold outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div class="flex flex-col gap-1" :class="isEditMode ? '' : 'text-xs'">
                            <label class="font-bold text-green-600 uppercase tracking-tighter">Profit (Fee)</label>
                            <input v-model="singleEntry.fee" type="number" class="border border-green-100 bg-green-50 rounded-lg p-2 text-green-700 font-black outline-none" />
                        </div>
                        <div class="flex flex-col gap-1" :class="isEditMode ? '' : 'text-xs'">
                            <label class="font-bold text-blue-600 uppercase italic tracking-tighter">Provider Fee</label>
                            <input v-model="singleEntry.admin_fee" type="number" class="border border-blue-100 bg-blue-50 rounded-lg p-2 text-blue-700 font-black outline-none" />
                        </div>

                        <div v-if="!isEditMode" class="flex flex-col gap-1 text-xs">
                            <label class="font-bold text-gray-400 uppercase tracking-tighter text-center">Simpan</label>
                            <button @click="addToBatch" class="bg-black text-white rounded-lg py-2 font-bold hover:bg-blue-600 transition-all shadow-sm">
                                + Antrian
                            </button>
                        </div>
                    </div>

                    <div v-if="isEditInvalid" class="px-6 pb-2 animate-pulse">
                        <div class="p-2 bg-amber-50 border border-amber-200 rounded-lg flex items-center gap-2">
                            <span class="text-amber-600">💡</span>
                            <p class="text-[10px] font-bold text-amber-700 uppercase tracking-wide">
                                Data Tipe Transaksi & Wallet Target harus diisi agar perubahan dapat disimpan!
                            </p>
                        </div>
                    </div>

                    <div v-if="isEditMode" class="p-6 bg-gray-50 border-t border-gray-100 flex gap-3 rounded-b-2xl">
                        <button 
                            @click="submit" 
                            :disabled="form.processing || isEditInvalid" 
                            class="flex-1 py-3 rounded-xl font-black uppercase tracking-widest transition-all shadow-lg text-white"
                            :class="isEditInvalid ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                        <button @click="showForm = false" class="px-6 py-3 bg-white border border-gray-300 rounded-xl font-bold text-gray-600 hover:bg-gray-100">Batal</button>
                    </div>
                </div>
            </div>

            <div v-if="showForm && !isEditMode && form.rules.length > 0" class="mb-8 border-2 border-dashed border-blue-200 rounded-xl overflow-hidden shadow-sm bg-white animate-in fade-in slide-in-from-top-4">
                <div class="bg-blue-50 px-4 py-2 border-b border-blue-100 flex justify-between items-center">
                    <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest">Daftar Antrian Baru</span>
                    <span class="text-[10px] bg-blue-600 text-white px-2 py-0.5 rounded-full">{{ form.rules.length }} Data</span>
                </div>
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-gray-50 border-b border-gray-100 text-[10px] uppercase text-gray-400 font-black">
                        <tr>
                            <th class="p-3">Transaksi / Target</th>
                            <th class="p-3 text-center">Range Limit</th>
                            <th class="p-3 text-center text-green-600">Profit</th>
                            <th class="p-3 text-center text-blue-600">Provider</th>
                            <th class="p-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(tr, idx) in form.rules" :key="idx" class="hover:bg-blue-50/30 transition-colors">
                            <td class="p-3 font-bold uppercase italic text-gray-700">
                                {{ getName(transTypes, tr.topup_trans_type_id) }} / <span class="text-blue-600">{{ getName(walletTargets, tr.wallet_target_id) }}</span>
                            </td>
                            <td class="p-3 text-center font-bold text-gray-700">{{ displayLimit(tr.min_limit) }} - {{ displayLimit(tr.max_limit) }}</td>
                            <td class="p-3 font-black text-green-600 text-center">Rp {{ formatCurrency(tr.fee) }}</td>
                            <td class="p-3 font-black text-blue-600 text-center">Rp {{ formatCurrency(tr.admin_fee) }}</td>
                            <td class="p-3 text-right">
                                <button @click="removeFromBatch(idx)" class="w-6 h-6 rounded-full bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition-all text-[10px]">✕</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="p-4 bg-white border-t flex justify-end">
                    <button @click="submit" :disabled="form.processing" class="bg-blue-600 text-white px-8 py-2.5 rounded-lg font-black uppercase tracking-tighter hover:bg-blue-700 shadow-sm">
                        {{ form.processing ? 'Memproses...' : 'Simpan Semua Antrian' }}
                    </button>
                </div>
            </div>

            <DataTable 
                title="Topup Fee Rules"
                :resource="data" 
                :columns="[
                    { label: 'Tipe', key: 'type_name' }, 
                    { label: 'Target', key: 'target_name' }, 
                    { label: 'Min Limit', key: 'min_limit' },
                    { label: 'Max Limit', key: 'max_limit' },
                    { label: 'Profit', key: 'fee' },
                    { label: 'Provider', key: 'admin_fee' },
                    { label: 'Dibuat Oleh', key: 'creator' }
                ]"
                routeName="topup-fee-rules.index" 
                :initialSearch="filters?.search || ''"
                :showAddButton="!showForm"
                @on-add="openCreate"
            >
                <template #type_name="{ row }">
                    <span class="font-bold text-gray-800 uppercase text-[10px] italic tracking-tight">{{ row.topup_trans_type?.name }}</span>
                </template>
                <template #target_name="{ row }">
                    <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-[10px] font-black uppercase border border-blue-100 shadow-sm">{{ row.wallet_target?.name }}</span>
                </template>
                <template #min_limit="{ value }"><span class="text-gray-400 font-medium text-[11px]">{{ displayLimit(value) }}</span></template>
                <template #max_limit="{ value }"><span class="text-gray-900 font-black text-[11px]">{{ displayLimit(value) }}</span></template>
                <template #fee="{ value }"><span class="text-green-600 font-black text-[11px]">Rp {{ formatCurrency(value) }}</span></template>
                <template #admin_fee="{ value }"><span class="text-blue-600 font-black text-[11px] italic">Rp {{ formatCurrency(value) }}</span></template>
                <template #creator="{ row }"><span class="text-[10px] font-black text-gray-700 uppercase italic">{{ row.creator?.name || 'SYSTEM' }}</span></template>
                <template #actions="{ row }">
                    <div class="flex gap-4 justify-end items-center">
                        <button @click="openEdit(row)" class="text-gray-300 hover:text-blue-600 transition-colors transform hover:scale-125">✏️</button>
                        <button @click="destroy(row)" class="text-gray-300 hover:text-red-600 transition-colors transform hover:scale-125">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>