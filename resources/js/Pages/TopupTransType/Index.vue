<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    data: Array
});

// Logic Form
const form = useForm({
    id: null,
    name: '',
    type: 'digital', // Default value sesuai data Anda
});

const editMode = ref(false);

const submit = () => {
    if (editMode.value) {
        form.patch(route('topup-trans-types.update', form.id), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('topup-trans-types.store'), {
            onSuccess: () => resetForm(),
        });
    }
};

const editData = (item) => {
    editMode.value = true;
    form.id = item.id;
    form.name = item.name;
    form.type = item.type;
};

const deleteData = (id) => {
    if (confirm('Hapus tipe transaksi ini? Data yang sudah terhubung mungkin akan terdampak.')) {
        form.delete(route('topup-trans-types.destroy', id));
    }
};

const resetForm = () => {
    form.reset();
    editMode.value = false;
};
</script>

<template>
    <Head title="Master Tipe Transaksi" />

    <AuthenticatedLayout>
        <div class="mb-8">
            <h1 class="text-3xl font-black uppercase italic tracking-tighter text-black">Master Tipe Transaksi</h1>
            <p class="text-[10px] font-black uppercase text-gray-400 italic">Pengaturan Layanan (Digital & Bill)</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div class="border-4 border-black p-6 bg-white">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-black uppercase italic underline decoration-yellow-400 decoration-4 text-black">
                        {{ editMode ? 'Edit Layanan' : 'Tambah Layanan Baru' }}
                    </h3>
                    <button v-if="editMode" @click="resetForm" class="text-[10px] font-black uppercase text-red-600 underline decoration-2">Batal</button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 text-black">Nama Layanan</label>
                        <input v-model="form.name" type="text" placeholder="Contoh: Transfer Dana" 
                            class="w-full border-4 border-black p-2 font-bold outline-none focus:bg-yellow-50 text-sm text-black" required />
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 text-black">Kategori (Type)</label>
                        <select v-model="form.type" class="w-full border-4 border-black p-2 font-bold outline-none focus:bg-yellow-50 text-sm text-black">
                            <option value="digital">DIGITAL (E-Wallet/Pulsa)</option>
                            <option value="bill">BILL (Tagihan/Listrik)</option>
                        </select>
                    </div>

                    <button :disabled="form.processing" 
                        class="w-full bg-black text-white py-4 font-black uppercase border-2 border-black hover:bg-yellow-500 hover:text-black transition-all active:translate-y-1 disabled:bg-gray-400">
                        {{ editMode ? 'Update Data' : 'Simpan Master' }}
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 border-4 border-black bg-white overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-black text-white">
                            <tr>
                                <th class="p-3 uppercase text-[10px] font-black italic border-r border-gray-800">#</th>
                                <th class="p-3 uppercase text-[10px] font-black italic">Nama Layanan</th>
                                <th class="p-3 uppercase text-[10px] font-black italic text-center border-l border-gray-800">Type</th>
                                <th class="p-3 uppercase text-[10px] font-black italic text-center border-l border-gray-800">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-4 divide-black text-sm">
                            <tr v-for="(item, index) in data" :key="item.id" class="hover:bg-gray-50 transition-colors">
                                <td class="p-3 border-r-4 border-black w-12 text-center font-black italic text-black">
                                    {{ index + 1 }}
                                </td>
                                <td class="p-3">
                                    <div class="font-black uppercase tracking-tight text-sm text-black">{{ item.name }}</div>
                                    <div class="text-[8px] font-bold text-gray-400 uppercase tracking-widest leading-none">ID: #00{{ item.id }}</div>
                                </td>
                                <td class="p-3 text-center border-l-4 border-black">
                                    <span :class="item.type === 'digital' ? 'bg-blue-100 text-blue-600 border-blue-600' : 'bg-purple-100 text-purple-600 border-purple-600'" 
                                        class="px-3 py-1 text-[9px] font-black uppercase italic border-2">
                                        {{ item.type }}
                                    </span>
                                </td>
                                <td class="p-3 text-center border-l-4 border-black space-x-4">
                                    <button @click="editData(item)" 
                                        class="text-[20px] font-black uppercase  text-blue-600  decoration-2">
                                        ✏️
                                    </button>
                                    <button @click="deleteData(item.id)" 
                                        class="text-[20px] font-black uppercase  text-red-600 decoration-2">
                                        ❌
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>