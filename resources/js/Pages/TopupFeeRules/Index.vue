<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    rules: Array,
    transTypes: Array
});

const showForm = ref(false);

// Mengubah default nilai ke null agar input tidak menampilkan 0 atau -1 saat baru dibuka
const form = useForm({
    id: null,
    topup_trans_type_id: '',
    min_limit: null, 
    max_limit: null,
    fee: null
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    // Memastikan nilai benar-benar kosong saat buka form tambah
    form.min_limit = null;
    form.max_limit = null;
    form.fee = null;
    form.id = null;
    showForm.value = true;
};

const openEdit = (rule) => {
    form.clearErrors();
    form.id = rule.id;
    form.topup_trans_type_id = rule.topup_trans_type_id;
    form.min_limit = rule.min_limit;
    form.max_limit = rule.max_limit;
    form.fee = rule.fee;
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

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num);
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-black uppercase italic">Aturan Biaya Admin</h1>
                <button @click="openCreate" class="bg-yellow-400 border-4 border-black px-6 py-2 font-black shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] hover:translate-x-1 hover:translate-y-1 transition-all">
                    TAMBAH ATURAN
                </button>
            </div>

            <div v-if="showForm" class="mb-8 p-6 border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="font-black text-xs uppercase">Tipe Transaksi</label>
                        <select v-model="form.topup_trans_type_id" class="border-2 border-black p-2 font-bold outline-none" :class="{'border-red-500': form.errors.topup_trans_type_id}">
                            <option value="">Pilih Tipe</option>
                            <option v-for="t in transTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <span v-if="form.errors.topup_trans_type_id" class="text-red-600 text-[10px] font-bold uppercase">{{ form.errors.topup_trans_type_id }}</span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="font-black text-xs uppercase">Min Limit</label>
                        <input v-model="form.min_limit" type="number" placeholder="Masukkan Min Limit" class="border-2 border-black p-2 font-bold outline-none" :class="{'border-red-500': form.errors.min_limit}" />
                        <span v-if="form.errors.min_limit" class="text-red-600 text-[10px] font-bold uppercase">{{ form.errors.min_limit }}</span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="font-black text-xs uppercase">Max Limit</label>
                        <input v-model="form.max_limit" type="number" placeholder="Masukkan Max Limit" class="border-2 border-black p-2 font-bold outline-none" :class="{'border-red-500': form.errors.max_limit}" />
                        <span v-if="form.errors.max_limit" class="text-red-600 text-[10px] font-bold uppercase">{{ form.errors.max_limit }}</span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="font-black text-xs uppercase">Biaya (Fee)</label>
                        <input v-model="form.fee" type="number" placeholder="Masukkan Fee" class="border-2 border-black p-2 font-bold outline-none" :class="{'border-red-500': form.errors.fee}" />
                        <span v-if="form.errors.fee" class="text-red-600 text-[10px] font-bold uppercase">{{ form.errors.fee }}</span>
                    </div>

                    <div class="md:col-span-4 flex gap-2 mt-2">
                        <button type="submit" :disabled="form.processing" class="bg-black text-white px-6 py-2 font-black uppercase shadow-[4px_4px_0px_0px_rgba(255,255,255,0.2)] disabled:opacity-50">
                            {{ form.id ? 'UPDATE' : 'SIMPAN' }}
                        </button>
                        <button @click="showForm = false" type="button" class="border-2 border-black px-6 py-2 font-black uppercase text-sm">BATAL</button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                <table class="w-full bg-white text-sm">
                    <thead class="bg-black text-white uppercase italic">
                        <tr>
                            <th class="p-4 text-left border-r border-gray-700">Tipe</th>
                            <th class="p-4 text-right border-r border-gray-700">Min</th>
                            <th class="p-4 text-right border-r border-gray-700">Max</th>
                            <th class="p-4 text-right border-r border-gray-700">Fee</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="font-bold">
                        <tr v-for="rule in rules" :key="rule.id" class="border-b-2 border-black hover:bg-yellow-50 transition-colors">
                            <td class="p-4 uppercase border-r-2 border-black">
                                {{ rule.trans_type ? rule.trans_type.name : 'N/A' }}
                            </td>
                            
                            <td class="p-4 text-right border-r-2 border-black">
                                {{ rule.min_limit <= 0 ? '-' : 'Rp ' + formatNumber(rule.min_limit) }}
                            </td>
                            
                            <td class="p-4 text-right border-r-2 border-black">
                                {{ rule.max_limit <= 0 ? '-' : 'Rp ' + formatNumber(rule.max_limit) }}
                            </td>

                            <td class="p-4 text-right text-blue-600 font-black border-r-2 border-black">
                                Rp {{ formatNumber(rule.fee) }}
                            </td>
                            
                            <td class="p-4 text-center flex justify-center gap-4">
                                <button @click="openEdit(rule)" class="hover:scale-125 transition-transform" title="Edit">✏️</button>
                                <button @click="deleteRule(rule.id)" class="hover:scale-125 transition-transform" title="Hapus">❌</button>
                            </td>
                        </tr>
                        <tr v-if="rules.length === 0">
                            <td colspan="5" class="p-8 text-center uppercase italic opacity-50">Belum ada aturan biaya</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>