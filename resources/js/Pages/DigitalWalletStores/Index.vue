<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({ 
    storeBalances: Array, 
    stores: Array, // Data ini sudah difilter 'Konter' dari Controller
    wallets: Array 
});

const form = useForm({
    id: null,
    store_id: '',
    digital_wallet_id: '',
    balance: 0
});

const editMode = ref(false);

const submit = () => {
    form.post(route('wallet-stores.store'), {
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
};

const deleteData = (id) => {
    if (confirm('Hapus alokasi ini? Saldo akan dikembalikan secara otomatis ke gudang pusat.')) {
        router.delete(route('wallet-stores.destroy', id));
    }
};

const formatIDR = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
</script>

<template>
    <AuthenticatedLayout>
        <div class="mb-8 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter">Saldo Toko</h1>
                <p class="text-[10px] font-black uppercase text-gray-400 italic tracking-widest">Warehouse Digital Wallet Distribution</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div class="border-4 border-black p-6 bg-white">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-black uppercase italic   decoration-yellow-400 decoration-4">
                        {{ editMode ? 'Edit Distribusi' : 'Alokasi Baru' }}
                    </h3>
                    <button v-if="editMode" @click="editMode = false; form.reset()" class="text-[10px] font-black uppercase text-red-600  ">Batal</button>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div v-if="form.errors.balance" class="p-3 bg-red-100 border-2 border-red-600 text-red-600 text-[10px] font-black uppercase italic">
                        {{ form.errors.balance }}
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

                    <button class="w-full bg-black text-white py-4 font-black uppercase border-2 border-black hover:bg-gray-800 transition-all">
                        {{ editMode ? 'Update Data' : 'Transfer Saldo' }}
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 border-4 border-black bg-white overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-black text-white border-b-4 border-black">
                        <tr>
                            <th class="p-3 uppercase text-xs font-black italic">#</th>
                            <th class="p-3 uppercase text-xs font-black italic">Toko</th>
                            <th class="p-3 uppercase text-xs font-black italic">Platform</th>
                            <th class="p-3 uppercase text-xs font-black italic text-right">Saldo</th>
                            <th class="p-3 uppercase text-xs font-black italic text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-4 divide-black text-sm">
                        <tr v-for="(sb, index) in storeBalances" :key="sb.id" class="hover:bg-gray-50 transition-colors">
                            <td class="p-3">
                                <span class="bg-black text-white px-2 py-1 text-[12px] font-black italic">#{{ index + 1 }}</span>
                            </td>
                            <td class="p-3 font-black uppercase tracking-tight italic text-sm">{{ sb.store?.name }}</td>
                            <td class="p-3">
                                <span class="bg-yellow-400 border-2 border-black px-2 py-0.5 font-bold uppercase text-[10px] italic">
                                    {{ sb.wallet?.name }}
                                </span>
                            </td>
                            <td class="p-3 font-black text-blue-600 text-right italic text-base">{{ formatIDR(sb.balance) }}</td>
                            <td class="p-3 text-center space-x-4">
                                <button @click="editData(sb)" class="font-black text-[10px] uppercase text-blue-600   hover:text-blue-800">✏️</button>
                                <button @click="deleteData(sb.id)" class="font-black text-[10px] uppercase text-red-600   hover:text-red-800">❌</button>
                            </td>
                        </tr>
                        <tr v-if="storeBalances.length === 0">
                            <td colspan="5" class="p-10 text-center font-black uppercase text-gray-300 italic tracking-widest">Belum Ada Distribusi</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>