<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue'; // Pastikan path sesuai

const props = defineProps({
    cashBalances: Object, 
    filters: Object,
    storeTypes: Array,
});

// --- LOGIKA INTERNAL (TIDAK DIUBAH) ---
const expandedStore = ref(null);
const toggleAccordion = (id) => {
    expandedStore.value = expandedStore.value === id ? null : id;
};

const activeEditId = ref(null);
const form = useForm({
    id: null,
    store_id: '',
    cash: 0,
    action_type: 'add', 
});

const openEdit = (row) => {
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.cash = 0; 
    form.action_type = 'add';
    activeEditId.value = row.id;
};

const cancelEdit = () => {
    activeEditId.value = null;
    form.reset();
};

const formatIDR = (num) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', currency: 'IDR', minimumFractionDigits: 0 
}).format(num);

const submit = () => {
    form.post(route('cash-stores.store'), {
        preserveScroll: true,
        onSuccess: () => { 
            activeEditId.value = null;
            form.reset();
        },
    });
};

// Konfigurasi Kolom untuk DataTable
const columns = [
    { key: 'store_name', label: 'Unit Toko', sortable: true },
    { key: 'cash', label: 'Total Kas Tunai', sortable: true },
];
</script>

<template>
    <Head title="Kas Toko" />

    <AuthenticatedLayout>
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
                    <div @click="toggleAccordion(row.id)" class="flex items-center gap-3 font-black uppercase italic text-gray-800 cursor-pointer group">
                        <div class="w-5 h-5 flex items-center justify-center rounded border border-blue-600 bg-blue-50 text-[10px] text-blue-600 transition-transform" 
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
                         <div class="flex flex-col gap-4 bg-gray-50/50 p-4 rounded-lg border border-dashed border-gray-200">
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
                                        <button v-if="activeEditId !== row.id" @click.stop="openEdit(row)" class="text-lg opacity-60 hover:opacity-100">✏️</button>
                                    </div>
                                </div>

                                <div v-if="activeEditId === row.id" class="w-1/2 bg-white border border-blue-500 rounded-xl p-5 shadow-lg relative">
                                    <button @click="cancelEdit" class="absolute top-2 right-3 text-gray-300 hover:text-red-500 font-black">✕</button>
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