<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3'; // Tambahkan Link untuk paginasi
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    rules: Object, // Ubah dari Array ke Object karena Laravel paginate mengembalikan object
    transTypes: Array,
    filters: Object // Tambahkan filters jika ingin sinkron dengan fitur search
});

const showForm = ref(false);

const form = useForm({
    id: null,
    topup_trans_type_id: '',
    min_limit: 0, 
    max_limit: 0,
    fee: 0,
    admin_fee: 0
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    form.min_limit = 0;
    form.max_limit = 0;
    form.fee = 0;
    form.admin_fee = 0;
    showForm.value = true;
};

const openEdit = (rule) => {
    form.clearErrors();
    form.id = rule.id;
    form.topup_trans_type_id = rule.topup_trans_type_id;
    form.min_limit = rule.min_limit;
    form.max_limit = rule.max_limit;
    form.fee = rule.fee;
    form.admin_fee = rule.admin_fee;
    showForm.value = true;
};

const submit = () => {
    if (form.id) {
        form.put(route('topup-fee-rules.update', form.id), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('topup-fee-rules.store'), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            }
        });
    }
};

const deleteRule = (id) => {
    if (confirm('Hapus aturan biaya ini?')) {
        form.delete(route('topup-fee-rules.destroy', id));
    }
};

const formatDisplay = (num) => {
    if (num === null || num === undefined || parseFloat(num) <= 0) {
        return '-';
    }
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-black uppercase italic">Aturan Biaya Admin</h1>
                <button @click="openCreate" class="bg-yellow-400 border-4 border-black px-6 py-2 font-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-x-1 hover:translate-y-1 transition-all text-sm">
                    TAMBAH ATURAN
                </button>
            </div>

            <div v-if="showForm" class="mb-8 p-6 border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="font-black text-xs uppercase">Tipe Transaksi</label>
                        <select v-model="form.topup_trans_type_id" class="border-2 border-black p-2 font-bold outline-none">
                            <option value="">Pilih Tipe</option>
                            <option v-for="t in transTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-black text-xs uppercase">Min Limit</label>
                        <input v-model="form.min_limit" type="number" class="border-2 border-black p-2 font-bold outline-none" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-black text-xs uppercase">Max Limit</label>
                        <input v-model="form.max_limit" type="number" class="border-2 border-black p-2 font-bold outline-none" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-black text-xs uppercase">Fee Profit</label>
                        <input v-model="form.fee" type="number" class="border-2 border-black p-2 font-bold outline-none" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-black text-xs uppercase">Fee Modal</label>
                        <input v-model="form.admin_fee" type="number" class="border-2 border-black p-2 font-bold outline-none" />
                    </div>
                    <div class="md:col-span-5 flex gap-2 mt-2">
                        <button type="submit" :disabled="form.processing" class="bg-black text-white px-6 py-2 font-black uppercase shadow-[4px_4px_0px_0px_rgba(255,255,255,0.2)]">
                            {{ form.id ? 'UPDATE' : 'SIMPAN' }}
                        </button>
                        <button @click="showForm = false" type="button" class="border-2 border-black px-6 py-2 font-black uppercase text-sm">BATAL</button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                <table class="w-full bg-white text-sm">
                    <thead class="bg-black text-white uppercase italic text-xs">
                        <tr>
                            <th class="p-4 text-left border-r border-gray-700">Tipe</th>
                            <th class="p-4 text-right border-r border-gray-700">Min</th>
                            <th class="p-4 text-right border-r border-gray-700">Max</th>
                            <th class="p-4 text-right border-r border-gray-700">Fee Profit</th>
                            <th class="p-4 text-right border-r border-gray-700">Fee Modal</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="font-bold">
                        <tr v-for="rule in rules.data" :key="rule.id" class="border-b-2 border-black hover:bg-yellow-50 transition-colors">
                            <td class="p-4 uppercase border-r-2 border-black">
                                {{ rule.trans_type_name }}
                            </td>
                            <td class="p-4 text-right border-r-2 border-black italic text-gray-400">
                                {{ formatDisplay(rule.min_limit) }}
                            </td>
                            <td class="p-4 text-right border-r-2 border-black italic text-gray-400">
                                {{ formatDisplay(rule.max_limit) }}
                            </td>
                            <td class="p-4 text-right text-red-600 border-r-2 border-black font-black">
                                {{ formatDisplay(rule.fee) }}
                            </td>
                            <td class="p-4 text-right text-blue-600 font-black border-r-2 border-black">
                                {{ formatDisplay(rule.admin_fee) }}
                            </td>
                            <td class="p-4 text-center flex justify-center gap-4">
                                <button @click="openEdit(rule)" class="hover:scale-125 transition-transform">✏️</button>
                                <button @click="deleteRule(rule.id)" class="hover:scale-125 transition-transform">❌</button>
                            </td>
                        </tr>
                        <tr v-if="rules.data.length === 0">
                            <td colspan="6" class="p-8 text-center uppercase italic opacity-50">Belum ada data aturan biaya</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-8 flex justify-center gap-2">
                <template v-for="(link, k) in rules.links" :key="k">
                    <Link 
                        v-if="link.url" 
                        :href="link.url" 
                        v-html="link.label"
                        class="px-4 py-2 border-2 border-black font-black uppercase transition-all shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:translate-x-[1px] hover:translate-y-[1px] hover:shadow-none"
                        :class="{'bg-yellow-400': link.active, 'bg-white': !link.active}"
                    />
                    <span v-else v-html="link.label" class="px-4 py-2 border-2 border-gray-300 text-gray-400 font-black uppercase italic"></span>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>