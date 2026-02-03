<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, usePage, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    withdrawals: Object, // Ubah ke Object karena menggunakan paginate()
    stores: Array,
    storeTypes: Array,
    filters: Object
});

// Search State
const search = ref(props.filters.search || '');

/**
 * SEARCH LOGIC (Server-side)
 */
watch(search, debounce((value) => {
    router.get(
        route('cash-withdrawals.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 500));

// Flash Messages
const page = usePage();
const errorMessage = computed(() => page.props.errors.error);

// Filter Toko khusus Type 1 (sesuai logic Anda)
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

const editMode = ref(false);

const submit = () => {
    if (editMode.value) {
        // Gunakan PUT atau PATCH sesuai route resource Laravel
        form.put(route('cash-withdrawals.update', form.id), {
            onSuccess: () => {
                form.reset();
                editMode.value = false;
            }
        });
    } else {
        form.post(route('cash-withdrawals.store'), {
            onSuccess: () => form.reset(),
        });
    }
};

const editData = (data) => {
    editMode.value = true;
    form.id = data.id;
    form.store_id = data.store_id;
    form.customer_name = data.customer_name;
    form.withdrawal_source_id = data.withdrawal_source_id;
    form.withdrawal_count = data.withdrawal_count;
    form.admin_fee = data.admin_fee;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const deleteData = (id) => {
    if (confirm('Batalkan transaksi ini? Saldo kas toko akan dikembalikan.')) {
        router.delete(route('cash-withdrawals.destroy', id));
    }
};

const formatIDR = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);

const getSourceName = (id) => {
    const sources = { 1: 'DANA', 2: 'OVO', 3: 'GOPAY', 4: 'LINKAJA', 5: 'SHOPEEPAY', 6: 'LAINNYA' };
    return sources[id] || 'UNKNOWN';
};
</script>

<template>
    <Head title="Transaksi Tarik Tunai" />

    <AuthenticatedLayout>
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter text-black">Transaksi Tarik Tunai</h1>
                <p class="text-[10px] font-black uppercase text-gray-400 italic">Manajemen Saldo Kas Fisik</p>
            </div>

            <div class="w-full md:w-64">
                <input 
                    v-model="search"
                    type="text" 
                    placeholder="CARI PELANGGAN / TOKO..." 
                    class="w-full border-4 border-black p-2 font-black uppercase italic text-xs shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] outline-none focus:bg-blue-50 transition-colors"
                />
            </div>
        </div>

        <transition name="fade">
            <div v-if="errorMessage" class="mb-6 bg-red-500 text-white border-4 border-black p-4 font-black uppercase italic text-xs animate-pulse shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                ⚠️ {{ errorMessage }}
            </div>
        </transition>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div class="border-4 border-black p-6 bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] relative">
                <div v-if="form.processing" class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center font-black italic uppercase">Memproses...</div>

                <div class="flex justify-between items-center mb-6 border-b-4 border-black pb-2">
                    <h3 class="font-black uppercase italic text-xs">
                        {{ editMode ? 'Mode Koreksi Transaksi' : 'Input Tarik Tunai' }}
                    </h3>
                    <button v-if="editMode" @click="editMode = false; form.reset()" class="text-[10px] font-black uppercase text-red-600 underline">Batal</button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 text-black">Unit Toko</label>
                        <select v-model="form.store_id" class="w-full border-4 border-black p-2 font-bold outline-none text-sm focus:bg-yellow-50" :disabled="editMode">
                            <option value="" disabled>-- Pilih Toko --</option>
                            <option v-for="s in storesWithTypeOne" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <p v-if="form.errors.store_id" class="text-[9px] text-red-600 font-bold uppercase italic mt-1">{{ form.errors.store_id }}</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 text-black">Nama Pelanggan</label>
                        <input v-model="form.customer_name" type="text" class="w-full border-4 border-black p-2 font-bold outline-none text-sm focus:bg-blue-50" placeholder="Input nama..." />
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 text-black">Sumber Dana</label>
                        <select v-model="form.withdrawal_source_id" class="w-full border-4 border-black p-2 font-bold outline-none text-sm" :disabled="editMode">
                            <option value="" disabled>-- Pilih Sumber --</option>
                            <option value="1">DANA</option>
                            <option value="2">OVO</option>
                            <option value="3">GOPAY</option>
                            <option value="4">LINKAJA</option>
                            <option value="5">SHOPEEPAY</option>
                            <option value="6">LAINNYA</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-1 text-red-600">Jumlah Tarik</label>
                            <input v-model="form.withdrawal_count" type="number" class="w-full border-4 border-black p-2 font-black outline-none" :disabled="editMode" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-1 text-green-600">Fee Admin</label>
                            <input v-model="form.admin_fee" type="number" class="w-full border-4 border-black p-2 font-black outline-none" :disabled="editMode" />
                        </div>
                    </div>

                    <button :disabled="form.processing" class="w-full bg-black text-white py-4 font-black uppercase border-2 border-black hover:bg-blue-600 hover:shadow-none transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:translate-y-1 disabled:bg-gray-400">
                        {{ form.processing ? 'MEMPROSES...' : (editMode ? 'SIMPAN PERUBAHAN' : 'SIMPAN TRANSAKSI') }}
                    </button>
                    <p v-if="editMode" class="text-[8px] font-bold text-center uppercase text-gray-400">Catatan: Hanya nama pelanggan yang dapat dikoreksi setelah simpan.</p>
                </form>
            </div>

            <div class="lg:col-span-2">
                <div class="border-4 border-black bg-white overflow-hidden shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-black text-white">
                                <tr>
                                    <th class="p-3 uppercase font-black border-r border-gray-800 italic w-10 text-center">#</th>
                                    <th class="p-3 uppercase font-black italic text-[10px]">Pelanggan & Unit</th>
                                    <th class="p-3 uppercase font-black italic text-right text-[10px]">Nominal</th>
                                    <th class="p-3 uppercase font-black italic text-center text-[10px]">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-4 divide-black">
                                <tr v-for="(w, index) in withdrawals.data" :key="w.id" class="hover:bg-blue-50 transition-colors">
                                    <td class="p-3 border-r-4 border-black text-center font-black italic bg-gray-50">
                                        {{ (withdrawals.current_page - 1) * withdrawals.per_page + index + 1 }}
                                    </td>
                                    <td class="p-3">
                                        <div class="font-black uppercase text-blue-600 tracking-tighter italic text-sm">{{ w.customer_name }}</div>
                                        <div class="text-[9px] font-bold text-gray-400 uppercase italic">{{ w.store?.name }} | {{ getSourceName(w.withdrawal_source_id) }}</div>
                                    </td>
                                    <td class="p-3 text-right bg-red-50/30">
                                        <div class="font-black text-red-600 text-sm">{{ formatIDR(w.withdrawal_count) }}</div>
                                        <div class="text-[9px] font-bold text-green-600 uppercase">Fee: {{ formatIDR(w.admin_fee) }}</div>
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex justify-center gap-3">
                                            <button @click="editData(w)" title="Koreksi Nama" class="text-[18px] hover:scale-125 transition-transform">✏️</button>
                                            <button @click="deleteData(w.id)" title="Batalkan Transaksi" class="text-[18px] hover:scale-125 transition-transform">❌</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="withdrawals.data.length === 0">
                                    <td colspan="4" class="p-10 text-center font-black uppercase text-gray-400 italic bg-gray-50">
                                        Data transaksi tidak ditemukan
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap justify-center gap-2">
                    <template v-for="(link, k) in withdrawals.links" :key="k">
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

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.5s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>