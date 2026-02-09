<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    withdrawals: Object, 
    stores: Array,
    storeTypes: Array,
    filters: Object
});

const showForm = ref(false);

const columns = [
    { label: 'Pelanggan & Unit', key: 'customer' },
    { label: 'Sumber Dana', key: 'source' },
    { label: 'Nominal Tarik', key: 'withdrawal_count' },
    { label: 'Fee Admin', key: 'admin_fee' }
];

const page = usePage();
const errorMessage = computed(() => page.props.errors.error);

const storesWithTypeOne = computed(() => {
    return props.stores.filter(s => s.store_type_id == 1);
});

const form = useForm({
    id: null,
    store_id: '',
    customer_name: '',
    withdrawal_source_id: '',
    withdrawal_count: 0,
    admin_fee: 0,
});

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.customer_name = row.customer_name;
    form.withdrawal_source_id = row.withdrawal_source_id;
    form.withdrawal_count = row.withdrawal_count;
    form.admin_fee = row.admin_fee;
    showForm.value = true;
};

const submit = () => {
    if (form.id) {
        // Ganti form.put menjadi form.patch
        form.patch(route('cash-withdrawals.update', form.id), {
            onSuccess: () => { 
                showForm.value = false; 
                form.reset(); 
            },
        });
    } else {
        form.post(route('cash-withdrawals.store'), {
            onSuccess: () => { 
                showForm.value = false; 
                form.reset(); 
            },
        });
    }
};

const destroy = (id) => {
    if (confirm('Batalkan transaksi ini? Saldo kas toko akan dikembalikan.')) {
        form.delete(route('cash-withdrawals.destroy', id));
    }
};

const formatIDR = (num) => new Intl.NumberFormat('id-ID').format(num);

const getSourceName = (id) => {
    const sources = { 1: 'DANA', 2: 'OVO', 3: 'GOPAY', 4: 'LINKAJA', 5: 'SHOPEEPAY', 6: 'LAINNYA' };
    return sources[id] || 'UNKNOWN';
};
</script>

<template>
    <Head title="Transaksi Tarik Tunai" />

    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="errorMessage" class="mb-6 bg-red-50 border border-red-200 p-4 rounded-lg flex items-center gap-3">
                <span class="text-xl">⚠️</span>
                <span class="text-sm font-bold text-red-600 uppercase italic">{{ errorMessage }}</span>
            </div>

            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showForm = false"></div>
                
                <div class="relative w-full max-w-4xl bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden animate-in zoom-in-95 duration-200">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="text-sm font-black text-gray-700 uppercase tracking-tight italic">
                            ✏️ Edit / Koreksi Transaksi
                        </h3>
                        <button @click="showForm = false" class="text-[10px] font-bold text-gray-400 hover:text-red-500 uppercase tracking-widest transition-colors">Tutup [X]</button>
                    </div>

                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Unit Toko</label>
                                <select v-model="form.store_id" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                    <option value="" disabled>-- Pilih Toko --</option>
                                    <option v-for="s in storesWithTypeOne" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Nama Pelanggan</label>
                                <input v-model="form.customer_name" type="text" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none bg-white" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Sumber Dana</label>
                                <select v-model="form.withdrawal_source_id" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                    <option value="" disabled>-- Pilih Sumber --</option>
                                    <option value="1">DANA</option>
                                    <option value="2">OVO</option>
                                    <option value="3">GOPAY</option>
                                    <option value="4">LINKAJA</option>
                                    <option value="5">SHOPEEPAY</option>
                                    <option value="6">LAINNYA</option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-red-400 uppercase tracking-widest ml-1">Jumlah Tarik</label>
                                <input v-model="form.withdrawal_count" type="number" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-black focus:ring-2 focus:ring-red-500 outline-none bg-white" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-black text-green-400 uppercase tracking-widest ml-1">Fee Admin</label>
                                <input v-model="form.admin_fee" type="number" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm font-black focus:ring-2 focus:ring-green-500 outline-none bg-white" />
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
                title="Transaksi Tarik Tunai"
                :resource="withdrawals" 
                :columns="columns"
                :showAddButton="false" 
                route-name="cash-withdrawals.index" 
                :initial-search="filters?.search || ''"
            >
                <template #customer="{ row }">
                    <div class="flex flex-col">
                        <span class="text-xs font-black uppercase italic tracking-tight text-blue-600">{{ row.customer_name }}</span>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ row.store?.name }}</span>
                    </div>
                </template>

                <template #source="{ row }">
                    <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-[9px] font-black uppercase">
                        {{ getSourceName(row.withdrawal_source_id) }}
                    </span>
                </template>

                <template #withdrawal_count="{ value }">
                    <span class="text-red-600 font-black italic">Rp {{ formatIDR(value) }}</span>
                </template>

                <template #admin_fee="{ value }">
                    <span class="text-green-600 font-bold">Rp {{ formatIDR(value) }}</span>
                </template>

                
            </DataTable>

        </div>
    </AuthenticatedLayout>
</template>