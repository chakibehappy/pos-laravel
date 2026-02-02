<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({ 
    cashBalances: Object, // Diubah menjadi Object karena paginate()
    stores: Array,
    storeTypes: Array,
    filters: Object
});

// State Global
const selectedTypeFilter = ref('all');
const search = ref(props.filters.search || '');
const editMode = ref(false);

/**
 * SEARCH LOGIC (Server-side)
 * Pencarian dikirim ke backend agar paginasi tetap akurat
 */
watch(search, debounce((value) => {
    router.get(
        route('cash-stores.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 500));

/**
 * 1. Logic Filter untuk COMBO BOX (Pilihan Toko di Form)
 * Tetap menggunakan client-side filter agar user nyaman saat input
 */
const filteredStoresForSelect = computed(() => {
    if (selectedTypeFilter.value === 'all') {
        return props.stores;
    }
    return props.stores.filter(s => s.store_type_id == selectedTypeFilter.value);
});

/**
 * 2. Logic Filter untuk TABEL (Data dari Paginasi)
 * Filter lokal tambahan jika user ingin menyaring data yang sudah tampil di halaman tersebut
 */
const filteredTableData = computed(() => {
    let data = props.cashBalances.data;
    if (selectedTypeFilter.value === 'all') {
        return data;
    }
    return data.filter(cb => cb.store?.store_type_id == selectedTypeFilter.value);
});

// Logic Form
const form = useForm({
    id: null,
    store_id: '',
    cash: 0
});

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
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const formatIDR = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
</script>

<template>
    <Head title="Monitoring Kas Unit" />

    <AuthenticatedLayout>
        <div class="mb-8 flex flex-col xl:flex-row xl:items-end justify-between gap-6">
            <div>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter text-black">Monitoring Kas Unit</h1>
                <p class="text-[10px] font-black uppercase text-gray-400 italic">Pencatatan & Filter Saldo Tunai Real-time</p>
            </div>

            <div class="flex flex-col md:flex-row gap-4">
                <div class="border-4 border-black p-1 bg-white flex items-center shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                    <span class="px-2">🔍</span>
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="CARI TOKO..." 
                        class="border-none text-xs font-black uppercase outline-none focus:ring-0 w-full md:w-40"
                    />
                </div>

                <div class="border-4 border-black p-2 bg-yellow-300 flex items-center gap-3 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                    <span class="text-[10px] font-black uppercase pl-2 text-black text-nowrap">Kategori:</span>
                    <select v-model="selectedTypeFilter" class="border-2 border-black p-1 text-xs font-black uppercase outline-none bg-white min-w-[150px]">
                        <option value="all">SEMUA JENIS USAHA</option>
                        <option v-for="type in storeTypes" :key="type.id" :value="type.id">
                            {{ type.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div class="border-4 border-black p-6 bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                <div class="flex justify-between items-center mb-6 border-b-4 border-black pb-2">
                    <h3 class="font-black uppercase italic text-xs">
                        {{ editMode ? 'Edit Saldo Kas' : 'Input Kas Baru' }}
                    </h3>
                    <button v-if="editMode" @click="editMode = false; form.reset()" class="text-[10px] font-black uppercase text-red-600 underline">Batal</button>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 tracking-tighter text-gray-400 font-bold">Pilih Toko (Tersaring)</label>
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
                        <input v-model="form.cash" type="number" class="w-full border-4 border-black p-2 font-black text-xl outline-none focus:bg-yellow-50" />
                    </div>

                    <button :disabled="form.processing" class="w-full bg-black text-white py-4 font-black uppercase border-2 border-black hover:bg-yellow-400 hover:text-black transition-all active:translate-y-1 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:shadow-none disabled:opacity-50">
                        {{ form.processing ? 'MEMPROSES...' : (editMode ? 'UPDATE DATA' : 'SIMPAN DATA') }}
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2">
                <div class="border-4 border-black bg-white overflow-hidden shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-black text-white">
                                <tr>
                                    <th class="p-3 uppercase text-[10px] font-black italic border-r border-gray-800 w-12 text-center">No</th>
                                    <th class="p-3 uppercase text-[10px] font-black italic">Nama Unit / Toko</th>
                                    <th class="p-3 uppercase text-[10px] font-black italic text-right">Saldo Kas</th>
                                    <th class="p-3 uppercase text-[10px] font-black italic text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-4 divide-black text-sm font-medium">
                                <tr v-for="(cb, index) in filteredTableData" :key="cb.id" class="hover:bg-yellow-50 transition-colors">
                                    <td class="p-3 border-r-4 border-black text-center font-black italic">
                                        {{ (cashBalances.current_page - 1) * cashBalances.per_page + index + 1 }}
                                    </td>
                                    <td class="p-3">
                                        <div class="font-black uppercase tracking-tight italic text-sm text-black">{{ cb.store?.name }}</div>
                                        <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Kategori ID: {{ cb.store?.store_type_id }}</div>
                                    </td>
                                    <td class="p-3 font-black text-green-600 text-right italic text-base bg-green-50/20">
                                        {{ formatIDR(cb.cash) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex justify-center gap-3">
                                            <button @click="editData(cb)" class="text-blue-600 font-black uppercase italic text-[10px] border-2 border-black px-2 py-1 hover:bg-black hover:text-white transition-colors">Edit</button>
                                            <button @click="router.delete(route('cash-stores.destroy', cb.id))" class="text-red-600 font-black uppercase italic text-[10px] border-2 border-black px-2 py-1 hover:bg-black hover:text-white transition-colors">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredTableData.length === 0">
                                    <td colspan="4" class="p-10 text-center font-black uppercase text-red-400 italic bg-gray-50">
                                        ⚠️ Data tidak ditemukan
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap justify-center gap-2">
                    <template v-for="(link, k) in cashBalances.links" :key="k">
                        <a 
                            v-if="link.url" 
                            :href="link.url" 
                            class="px-4 py-2 border-2 border-black font-black uppercase text-[10px] transition-all shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:translate-x-[1px] hover:translate-y-[1px] hover:shadow-none"
                            :class="{'bg-yellow-400': link.active, 'bg-white': !link.active}"
                        >
                        {{ link.label }}
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>