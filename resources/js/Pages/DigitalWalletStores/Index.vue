<script setup>
import { Link, router, useForm, Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    resource: Object, 
    filters: Object,
});

// State Accordion
const expandedStore = ref(null);
const toggleAccordion = (id) => {
    expandedStore.value = expandedStore.value === id ? null : id;
};

// Search System (Identik DataTable.vue)
const search = ref(props.filters?.search || '');
watch(search, debounce((value) => {
    router.get(
        route('digital-wallet-stores.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 500));

// Form Edit Inline
const activeEditId = ref(null);
const form = useForm({
    id: null,
    store_id: '',
    digital_wallet_id: '',
    balance: 0,
    action_type: 'add',
});

const openEdit = (walletItem) => {
    const row = walletItem.raw;
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id;
    form.digital_wallet_id = row.digital_wallet_id;
    form.balance = 0;
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
    form.post(route('digital-wallet-stores.store'), {
        preserveScroll: true,
        onSuccess: () => { 
            activeEditId.value = null;
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Monitoring Saldo Toko" />

    <AuthenticatedLayout>
        <div class="p-8 text-left">
            <div class="w-full flex flex-col">
                
                <div class="mb-4 flex justify-between items-end">
                    <h1 class="text-2xl font-black uppercase tracking-tighter text-black">Ringkasan Saldo Toko</h1>
                </div>

                <div class="mb-6">
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="CARI UNIT TOKO..." 
                        class="w-full md:w-1/3 border border-gray-300 rounded-lg p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white shadow-sm transition-all placeholder:text-gray-400 uppercase"
                    />
                </div>

                <div class="w-full bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-black font-black uppercase text-[10px] tracking-widest">
                                <th class="p-4 text-left text-gray-400">Unit Toko</th>
                                <th class="p-4 text-right text-gray-400">Akumulasi Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 italic">
                            <template v-for="row in resource.data" :key="row.id">
                                <tr @click="toggleAccordion(row.id)" class="hover:bg-gray-50 transition-colors duration-150 cursor-pointer">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3 font-black uppercase italic text-gray-800">
                                            <div class="w-5 h-5 flex items-center justify-center rounded border border-blue-600 bg-blue-50 text-[10px] text-blue-600 transition-transform" :class="expandedStore === row.id ? 'rotate-180' : ''">
                                                ▼
                                            </div>
                                            <span>{{ row.store_name }}</span>
                                            <span class="text-[9px] bg-gray-100 px-2 py-0.5 rounded text-gray-400 font-bold not-italic tracking-normal">{{ row.wallets.length }} WALLET</span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-right font-black text-sm text-gray-300">
                                        {{ formatIDR(row.total_balance) }}
                                    </td>
                                </tr>

                                <tr v-if="expandedStore === row.id" class="bg-gray-50/50">
                                    <td colspan="2" class="p-6">
                                        <div class="flex flex-col gap-4">
                                            <div v-for="w in row.wallets" :key="w.id" class="flex gap-4 items-stretch max-w-6xl">
                                                
                                                <div class="flex-1 bg-white border border-gray-200 rounded-xl p-4 shadow-sm flex items-center justify-between transition-all"
                                                     :class="activeEditId === w.id ? 'border-blue-500 ring-2 ring-blue-50' : ''">
                                                    <div class="flex flex-col">
                                                        <span class="text-[8px] font-black text-gray-300 uppercase leading-none mb-1 tracking-widest not-italic">Platform</span>
                                                        <span class="text-sm font-black text-gray-800 uppercase italic">{{ w.wallet_name }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-8">
                                                        <div class="text-right flex flex-col">
                                                            <span class="text-[8px] font-black text-gray-300 uppercase leading-none mb-1 tracking-widest not-italic text-right">Saldo Saat Ini</span>
                                                            <span class="text-sm font-black text-blue-600 italic">{{ formatIDR(w.balance) }}</span>
                                                        </div>
                                                        <button 
                                                            v-if="activeEditId !== w.id"
                                                            @click.stop="openEdit(w)" 
                                                            class="transition-transform active:scale-90 text-lg opacity-60 hover:opacity-100"
                                                        >
                                                            ✏️
                                                        </button>
                                                        <div v-else class="w-[28px]"></div>
                                                    </div>
                                                </div>

                                                <div v-if="activeEditId === w.id" 
                                                     class="w-1/2 bg-white border border-blue-500 rounded-xl p-5 shadow-lg animate-in slide-in-from-left-2 duration-200 relative">
                                                    
                                                    <button @click="cancelEdit" class="absolute top-2 right-3 text-gray-300 hover:text-red-500 transition-colors text-lg font-black not-italic">
                                                        ✕
                                                    </button>

                                                    <form @submit.prevent="submit" class="flex flex-col h-full gap-4 pt-1">
                                                        <div class="grid grid-cols-2 gap-3">
                                                            <div class="flex flex-col">
                                                                <label class="text-[8px] font-black text-blue-600 uppercase tracking-widest mb-1 not-italic">Aksi</label>
                                                                <select v-model="form.action_type" class="w-full border border-gray-200 rounded-lg p-2 text-xs font-black uppercase italic bg-gray-50 outline-none focus:ring-1 focus:ring-blue-500">
                                                                    <option value="add">Tambahkan</option>
                                                                    <option value="subtract">Kurangi</option>
                                                                    <option value="reset">Reset Saldo</option>
                                                                </select>
                                                            </div>
                                                            <div class="flex flex-col">
                                                                <label class="text-[8px] font-black text-blue-600 uppercase tracking-widest mb-1 not-italic">Nominal (Rp)</label>
                                                                <input 
                                                                    v-model="form.balance" 
                                                                    type="number" 
                                                                    :disabled="form.action_type === 'reset'"
                                                                    class="w-full border border-gray-200 rounded-lg p-2 font-black text-sm focus:ring-1 focus:ring-blue-500 outline-none italic disabled:bg-gray-100 disabled:text-gray-400"
                                                                    placeholder="0"
                                                                    autofocus
                                                                />
                                                            </div>
                                                        </div>
                                                        <button 
                                                            type="submit" 
                                                            class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-black uppercase text-[10px] hover:bg-blue-700 transition-all shadow-md shadow-blue-100 mt-auto"
                                                        >
                                                            Simpan Perubahan
                                                        </button>
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="resource.data.length === 0">
                                <td colspan="2" class="p-12 text-center text-gray-300 font-medium italic text-sm tracking-widest">
                                    --- DATA TIDAK DITEMUKAN ---
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="p-4 flex justify-between items-center border-t border-gray-100 bg-gray-50/50">
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                            Total: {{ resource.total || 0 }} Unit Toko
                        </span>
                        
                        <div class="flex items-center gap-1">
                            <template v-for="link in resource.links" :key="link.label">
                                <div v-if="!link.url" v-html="link.label" class="px-3 py-1 text-[10px] border border-gray-100 text-gray-300 rounded bg-white" />
                                <Link v-else 
                                    :href="link.url" 
                                    class="px-3 py-1 text-[10px] border rounded transition-all font-bold uppercase"
                                    :class="link.active ? 'bg-blue-600 border-blue-600 text-white shadow-md' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'"
                                >
                                    <span v-html="link.label"></span>
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>