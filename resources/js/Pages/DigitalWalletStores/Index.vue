<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({ 
    storeBalances: Object, // Diubah menjadi Object untuk mendukung pagination (.data, .links)
    stores: Array, 
    wallets: Array,
    filters: Object
});

const search = ref(props.filters.search || '');
const editMode = ref(false);

// Logic Pencarian ke Server
watch(search, debounce((value) => {
    router.get(
        route('digital-wallet-stores.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 500));

const form = useForm({
    id: null,
    store_id: '',
    digital_wallet_id: '',
    balance: 0
});

const submit = () => {
    form.post(route('digital-wallets.store'), {
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
    form.digital_wallet_id = data.digital_wallet_id;
    form.balance = data.balance;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const deleteData = (id) => {
    if (confirm('Hapus alokasi ini? Saldo akan dikembalikan secara otomatis ke gudang pusat.')) {
        router.delete(route('digital-wallet-stores.destroy', id));
    }
};

const formatIDR = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
</script>

<template>
    <Head title="Saldo Toko - Warehouse Distribution" />

    <AuthenticatedLayout>
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter text-black">Saldo Toko</h1>
                <p class="text-[10px] font-black uppercase text-gray-400 italic tracking-widest">Warehouse Digital Wallet Distribution</p>
            </div>

            <div class="w-full md:w-64">
                <div class="border-4 border-black p-1 bg-white flex items-center shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                    <span class="px-2">🔍</span>
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="CARI UNIT / WALLET..." 
                        class="border-none text-xs font-black uppercase outline-none focus:ring-0 w-full"
                    />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div class="border-4 border-black p-6 bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                <div class="flex justify-between items-center mb-6 border-b-4 border-black pb-2">
                    <h3 class="font-black uppercase italic decoration-yellow-400 decoration-4">
                        {{ editMode ? 'Edit Distribusi' : 'Alokasi Baru' }}
                    </h3>
                    <button v-if="editMode" @click="editMode = false; form.reset()" class="text-[10px] font-black uppercase text-red-600 underline">Batal</button>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div v-if="form.errors.balance" class="p-3 bg-red-100 border-2 border-red-600 text-red-600 text-[10px] font-black uppercase italic">
                        ⚠️ {{ form.errors.balance }}
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 tracking-tighter">Pilih Konter</label>
                        <select v-model="form.store_id" class="w-full border-4 border-black p-2 font-bold outline-none focus:bg-yellow-50 text-sm">
                            <option value="" disabled>-- Pilih Cabang --</option>
                            <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 tracking-tighter">Dompet Gudang (Sumber)</label>
                        <select v-model="form.digital_wallet_id" class="w-full border-4 border-black p-2 font-bold outline-none focus:bg-yellow-50 text-sm">
                            <option value="" disabled>-- Pilih Dompet --</option>
                            <option v-for="w in wallets" :key="w.id" :value="w.id">
                                {{ w.name }} (Sisa: {{ formatIDR(w.balance) }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 tracking-tighter">Nominal Distribusi (Rp)</label>
                        <input v-model="form.balance" type="number" class="w-full border-4 border-black p-2 font-black text-xl outline-none focus:bg-blue-50" />
                    </div>

                    <button :disabled="form.processing" class="w-full bg-black text-white py-4 font-black uppercase border-2 border-black hover:bg-yellow-400 hover:text-black transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:translate-y-1 disabled:opacity-50">
                        {{ form.processing ? 'MEMPROSES...' : (editMode ? 'UPDATE DATA' : 'TRANSFER SALDO') }}
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2">
                <div class="border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-black text-white border-b-4 border-black">
                                <tr>
                                    <th class="p-3 uppercase text-xs font-black italic w-12 text-center border-r border-gray-800">No</th>
                                    <th class="p-3 uppercase text-xs font-black italic">Toko</th>
                                    <th class="p-3 uppercase text-xs font-black italic">Platform</th>
                                    <th class="p-3 uppercase text-xs font-black italic text-right">Saldo</th>
                                    <th class="p-3 uppercase text-xs font-black italic text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-4 divide-black text-sm">
                                <tr v-for="(sb, index) in storeBalances.data" :key="sb.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="p-3 text-center border-r-4 border-black font-black italic bg-gray-50">
                                        {{ (storeBalances.current_page - 1) * storeBalances.per_page + index + 1 }}
                                    </td>
                                    <td class="p-3 font-black uppercase tracking-tight italic text-sm text-black">{{ sb.store?.name }}</td>
                                    <td class="p-3">
                                        <span class="bg-yellow-400 border-2 border-black px-2 py-0.5 font-bold uppercase text-[10px] italic shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
                                            {{ sb.wallet?.name }}
                                        </span>
                                    </td>
                                    <td class="p-3 font-black text-blue-600 text-right italic text-base bg-blue-50/20">
                                        {{ formatIDR(sb.balance) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex justify-center gap-4">
                                            <button @click="editData(sb)" title="Edit" class="text-lg hover:scale-125 transition-transform">✏️</button>
                                            <button @click="deleteData(sb.id)" title="Hapus" class="text-lg hover:scale-125 transition-transform">❌</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="storeBalances.data.length === 0">
                                    <td colspan="5" class="p-10 text-center font-black uppercase text-gray-300 italic tracking-widest">
                                        Data tidak ditemukan
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap justify-center gap-2">
                    <template v-for="(link, k) in storeBalances.links" :key="k">
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