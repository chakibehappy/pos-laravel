<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    resource: Object,
    stores: Array,
    wallets: Array,
    filters: Object,
});

const expandedStore = ref(null);
const isModalOpen = ref(false);

const toggleAccordion = (id) => {
    expandedStore.value = expandedStore.value === id ? null : id;
};

const form = useForm({
    id: null,
    store_id: '',
    digital_wallet_id: '',
    balance: 0,
});

const openEdit = (walletItem) => {
    const row = walletItem.raw;
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.digital_wallet_id = row.digital_wallet_id;
    form.balance = row.balance;
    isModalOpen.value = true;
};

const formatIDR = (num) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', currency: 'IDR', minimumFractionDigits: 0 
}).format(num);

const submit = () => {
    form.post(route('digital-wallet-stores.store'), {
        onSuccess: () => { isModalOpen.value = false; },
    });
};
</script>

<template>
    <Head title="Saldo Per Toko" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div v-if="isModalOpen" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="w-full max-w-md border-4 border-black bg-white shadow-[12px_12px_0px_0px_rgba(0,0,0,1)] p-8">
                    <h2 class="text-xl font-black uppercase italic mb-6">Update Saldo Wallet</h2>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block font-black text-xs uppercase mb-1">Nominal (Rp)</label>
                            <input v-model="form.balance" type="number" class="w-full border-4 border-black p-4 font-black text-2xl outline-none focus:bg-yellow-50" />
                        </div>
                        <div class="flex gap-4">
                            <button type="submit" class="flex-1 bg-black text-white py-3 font-black uppercase shadow-[4px_4px_0_#3b82f6]">Update</button>
                            <button type="button" @click="isModalOpen = false" class="px-6 border-4 border-black font-black uppercase">Batal</button>
                        </div>
                    </form>
                </div>
            </div>

            <DataTable 
                title="Ringkasan Saldo Toko"
                :resource="resource" 
                :columns="[
                    { label: 'Unit Toko', key: 'store_group' }, 
                    { label: 'Akumulasi Saldo', key: 'total_balance' }
                ]"
                routeName="digital-wallet-stores.index" 
                :initialSearch="filters?.search || ''"
                :showAddButton="false" 
            >
                <template #store_group="{ row }">
                    <div @click="toggleAccordion(row.id)" class="cursor-pointer group">
                        <div class="flex items-center gap-3 py-2">
                            <span class="font-black text-lg transition-transform" :class="expandedStore === row.id ? 'rotate-90 text-blue-600' : ''">▶</span>
                            <span class="font-black text-xl uppercase italic group-hover:underline decoration-yellow-400">{{ row.store_name }}</span>
                            <span class="bg-black text-white px-2 py-0.5 text-[10px] font-bold">{{ row.wallets.length }} WALLET</span>
                        </div>

                        <div v-if="expandedStore === row.id" class="mt-4 ml-8 space-y-3 mb-4 animate-in slide-in-from-top-2 duration-300">
                            <div v-for="w in row.wallets" :key="w.id" 
                                 class="flex items-center justify-between border-l-4 border-black bg-gray-50 p-4 hover:bg-yellow-50 transition-colors">
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase leading-none mb-1">Platform</p>
                                    <p class="font-black uppercase italic text-black">{{ w.wallet_name }}</p>
                                </div>
                                <div class="flex items-center gap-6">
                                    <div class="text-right">
                                        <p class="text-[10px] font-black text-gray-400 uppercase leading-none mb-1">Saldo</p>
                                        <p class="font-black text-blue-600 italic">{{ formatIDR(w.balance) }}</p>
                                    </div>
                                    <button @click.stop="openEdit(w)" class="bg-white border-2 border-black p-2 hover:bg-black hover:text-white transition-all shadow-[2px_2px_0_0_rgba(0,0,0,1)] active:shadow-none">
                                        ✏️
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template #total_balance="{ row }">
                    <span class="font-black text-lg italic tracking-tighter" :class="expandedStore === row.id ? 'text-gray-300' : 'text-black'">
                        {{ formatIDR(row.total_balance) }}
                    </span>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>