<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({ 
    cashBalances: Array, 
    stores: Array,
    storeTypes: Array 
});

// State Global untuk Filter Jenis Usaha
const selectedTypeFilter = ref('all');

// 1. Logic Filter untuk COMBO BOX (Pilihan Toko di Form)
const filteredStoresForSelect = computed(() => {
    if (selectedTypeFilter.value === 'all') {
        return props.stores;
    }
    return props.stores.filter(s => s.store_type_id == selectedTypeFilter.value);
});

// 2. Logic Filter untuk TABEL (Data yang muncul di Dashboard)
const filteredTableData = computed(() => {
    if (selectedTypeFilter.value === 'all') {
        return props.cashBalances;
    }
    return props.cashBalances.filter(cb => cb.store?.store_type_id == selectedTypeFilter.value);
});

// Logic Form
const form = useForm({
    id: null,
    store_id: '',
    cash: 0
});

const editMode = ref(false);

const submit = () => {
    form.post(route('cash-stores.store'), {
        onSuccess: () => {
            form.reset();
            editMode.value = false;
        }
    });
};

const editData = (data) => {
    editMode.value = true;
    form.id = data.id;
    form.store_id = data.store_id;
    form.cash = data.cash;
    // Otomatis arahkan filter ke jenis usaha toko yang diedit
    selectedTypeFilter.value = data.store?.store_type_id || 'all';
};

const formatIDR = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
</script>

<template>
    <AuthenticatedLayout>
        <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter">Monitoring Kas Unit</h1>
                <p class="text-[10px] font-black uppercase text-gray-400 italic">Pencatatan & Filter Saldo Tunai Real-time</p>
            </div>

            <div class="border-4 border-black p-2 bg-yellow-300 flex items-center gap-3">
                <span class="text-[10px] font-black uppercase pl-2 text-black">Kategori Bisnis:</span>
                <select v-model="selectedTypeFilter" class="border-2 border-black p-1 text-xs font-black uppercase outline-none bg-white">
                    <option value="all">SEMUA JENIS USAHA</option>
                    <option v-for="type in storeTypes" :key="type.id" :value="type.id">
                        {{ type.name }}
                    </option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div class="border-4 border-black p-6 bg-white">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-black uppercase italic underline decoration-green-400 decoration-4">
                        {{ editMode ? 'Edit Saldo' : 'Input Baru' }}
                    </h3>
                    <button v-if="editMode" @click="editMode = false; form.reset()" class="text-[10px] font-black uppercase text-red-600 underline font-bold">Batal</button>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 tracking-tighter text-gray-400">Pilih Toko (Tersaring)</label>
                        <select v-model="form.store_id" class="w-full border-4 border-black p-2 font-bold outline-none focus:bg-green-50 text-sm">
                            <option value="" disabled>-- Pilih Unit --</option>
                            <option v-for="s in filteredStoresForSelect" :key="s.id" :value="s.id">
                                {{ s.name }}
                            </option>
                        </select>
                        <p v-if="filteredStoresForSelect.length === 0" class="text-[9px] text-red-500 font-bold mt-1 uppercase italic">! Tidak ada toko di kategori ini</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 tracking-tighter text-black font-bold">Nominal Kas (Tunai)</label>
                        <input v-model="form.cash" type="number" class="w-full border-4 border-black p-2 font-black text-xl outline-none focus:bg-green-50" />
                    </div>

                    <button class="w-full bg-black text-white py-4 font-black uppercase border-2 border-black hover:bg-gray-800 transition-all active:translate-y-1">
                        {{ editMode ? 'Update Data' : 'Simpan Data' }}
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 border-4 border-black bg-white overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-black text-white">
                        <tr>
                            <th class="p-3 uppercase text-xs font-black italic border-r border-gray-800">#</th>
                            <th class="p-3 uppercase text-xs font-black italic">Nama Unit / Toko</th>
                            <th class="p-3 uppercase text-xs font-black italic text-right">Saldo Kas</th>
                            <th class="p-3 uppercase text-xs font-black italic text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-4 divide-black text-sm font-medium">
                        <tr v-for="(cb, index) in filteredTableData" :key="cb.id" class="hover:bg-yellow-50 transition-colors">
                            <td class="p-3 border-r-4 border-black w-12 text-center">
                                <span class="bg-black text-white px-2 py-1 text-[12px] font-black italic">#{{ index + 1 }}</span>
                            </td>
                            <td class="p-3">
                                <div class="font-black uppercase tracking-tight italic text-sm">{{ cb.store?.name }}</div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Type ID: {{ cb.store?.store_type_id }}</div>
                            </td>
                            <td class="p-3 font-black text-green-600 text-right italic text-base">
                                {{ formatIDR(cb.cash) }}
                            </td>
                            <td class="p-3 text-center space-x-2">
                                <button @click="editData(cb)" title="Edit" class="text-[20px] transition-transform active:scale-90">✏️</button>
                                <button @click="router.delete(route('cash-stores.destroy', cb.id))" title="Hapus" class="text-[20px] transition-transform active:scale-90 font-bold">❌</button>
                            </td>
                        </tr>
                        <tr v-if="filteredTableData.length === 0">
                            <td colspan="4" class="p-10 text-center font-black uppercase text-red-400 italic tracking-widest bg-gray-50">
                                ⚠️ Tidak ada data kas untuk kategori ini
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>