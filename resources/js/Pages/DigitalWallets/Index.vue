<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({ wallets: Array, total_balance: Number });

const showModal = ref(false);
const modalType = ref('form'); // 'form' untuk edit/tambah, 'balance' untuk update saldo
const selectedWallet = ref(null);

const form = useForm({
    id: null,
    name: '',
    balance: 0,
    amount: 0, // khusus update saldo
    type: 'add' // khusus update saldo
});

const openCreateModal = () => {
    form.reset();
    form.id = null;
    modalType.value = 'form';
    showModal.value = true;
};

const openEditModal = (wallet) => {
    form.id = wallet.id;
    form.name = wallet.name;
    form.balance = wallet.balance;
    modalType.value = 'form';
    showModal.value = true;
};

const openBalanceModal = (wallet) => {
    selectedWallet.value = wallet;
    form.reset('amount');
    modalType.value = 'balance';
    showModal.value = true;
};

const submitForm = () => {
    if (modalType.value === 'form') {
        form.post(route('digital-wallets.store'), { onSuccess: () => showModal.value = false });
    } else {
        form.post(route('wallets.update-balance', selectedWallet.value.id), { onSuccess: () => showModal.value = false });
    }
};

const deleteWallet = (id) => {
    if (confirm('Hapus wallet ini?')) router.delete(route('digital-wallets.destroy', id));
};

const formatIDR = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
</script>

<template>
    <AuthenticatedLayout>
        <div class="mb-8 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter">Saldo Gudang</h1>
                <button @click="openCreateModal" class="mt-2 bg-black text-white px-4 py-1 text-xs font-black uppercase hover:bg-gray-800 transition-all border-2 border-black">
                    + Tambah Wallet
                </button>
            </div>
            <div class="bg-yellow-400 border-4 border-black p-4 text-right">
                <p class="text-[10px] font-black uppercase">Total Saldo</p>
                <p class="text-2xl font-black italic ">{{ formatIDR(total_balance) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <div v-for="(w, index) in wallets" :key="w.id" class="border-4 border-black bg-white p-6">
        <div class="flex justify-between items-start mb-4">
            <span class="bg-black text-white px-2 py-1 text-[12px] font-black italic">
                #{{ index + 1 }}
            </span>
            <div class="space-x-2">
                <button @click="openEditModal(w)" class="text-blue-600 font-black text-[20px] uppercase ">✏️</button>
                <button @click="deleteWallet(w.id)" class="text-red-600 font-black text-[20px] uppercase ">❌</button>
            </div>
        </div>
        
            <h2 class="text-2xl font-black uppercase italic tracking-tight mb-4">{{ w.name }}</h2>
            <p class="text-3xl font-black text-blue-600 mb-6 italic">{{ formatIDR(w.balance) }}</p>
            
            <button @click="openBalanceModal(w)" class="w-full bg-yellow-400 border-2 border-black py-2 font-black uppercase text-xs hover:bg-yellow-500 transition-all">
                Update Saldo
            </button>
        </div>
    </div>

        <div v-if="showModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-white border-8 border-black p-8 w-full max-w-md">
                <h3 class="text-2xl font-black uppercase italic mb-6">
                    {{ modalType === 'form' ? (form.id ? 'Edit Wallet' : 'Tambah Wallet') : 'Update Saldo' }}
                </h3>
                
                <div class="space-y-4">
                    <template v-if="modalType === 'form'">
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-1">Nama Wallet (Platform)</label>
                            <input v-model="form.name" type="text" class="w-full border-4 border-black p-3 font-black uppercase outline-none focus:bg-yellow-50" placeholder="Contoh: Bukalapak" />
                        </div>
                        <div v-if="!form.id">
                            <label class="block text-[10px] font-black uppercase mb-1">Saldo Awal (Rp)</label>
                            <input v-model="form.balance" type="number" class="w-full border-4 border-black p-3 font-black text-xl outline-none" />
                        </div>
                    </template>

                    <template v-else>
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-1 text-gray-400 italic">Type Pengisian:</label>
                            <select v-model="form.type" class="w-full border-4 border-black p-3 font-black uppercase outline-none">
                                <option value="add">Tambah Saldo</option>
                                <option value="set">Set Total Baru</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-1">Nominal (Rp)</label>
                            <input v-model="form.amount" type="number" class="w-full border-4 border-black p-3 font-black text-xl outline-none" />
                        </div>
                    </template>

                    <div class="flex gap-4 pt-4">
                        <button @click="submitForm" class="flex-1 bg-black text-white py-4 font-black uppercase border-2 border-black hover:bg-gray-800 transition-all">
                            Simpan
                        </button>
                        <button @click="showModal = false" class="flex-1 border-4 border-black py-4 font-black uppercase hover:bg-gray-100 transition-all">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>