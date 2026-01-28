<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue'; 

const props = defineProps({
    methods: Array,
});

const isEditing = ref(false); 
const editId = ref(null);    

const form = useForm({
    name: '',
});

const editMethod = (method) => {
    isEditing.value = true;
    editId.value = method.id;
    form.name = method.name;
};

const cancelEdit = () => {
    isEditing.value = false;
    editId.value = null;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('payment-methods.update', editId.value), {
            onSuccess: () => cancelEdit(),
        });
    } else {
        form.post(route('payment-methods.store'), {
            onSuccess: () => form.reset(),
        });
    }
};

const deleteMethod = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus metode ini?')) {
        form.delete(route('payment-methods.destroy', id));
    }
};
</script>

<template>
    <Head title="Metode Pembayaran" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="bg-white border-4 border-black p-6 h-fit shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                        <h3 class="text-xl font-black mb-4 uppercase italic">
                            {{ isEditing ? 'Edit Metode' : 'Tambah Metode' }}
                        </h3>
                        <form @submit.prevent="submit">
                            <div>
                                <label class="block font-bold mb-2 uppercase text-sm">Nama Metode</label>
                                <input 
                                    v-model="form.name"
                                    type="text" 
                                    class="w-full border-4 border-black p-3 font-bold focus:ring-0 focus:border-yellow-400 uppercase"
                                    placeholder="Contoh: QRIS, DEBIT, dll"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-red-600 mt-2 font-bold italic text-xs">{{ form.errors.name }}</div>
                            </div>

                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="mt-6 w-full border-4 border-black p-3 font-black uppercase transition-all hover:-translate-x-1 hover:-translate-y-1 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:shadow-none active:translate-x-0 active:translate-y-0"
                                :class="isEditing ? 'bg-blue-400 hover:bg-blue-500' : 'bg-yellow-400 hover:bg-yellow-500'"
                            >
                                {{ isEditing ? 'Update Data' : 'Simpan Data' }}
                            </button>

                            <button 
                                v-if="isEditing"
                                type="button"
                                @click="cancelEdit"
                                class="mt-4 w-full bg-gray-200 border-4 border-black p-2 font-black uppercase text-xs hover:bg-gray-300 transition-colors"
                            >
                                Batal
                            </button>
                        </form>
                    </div>

                    <div class="md:col-span-2 bg-white border-4 border-black p-6 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                        <h3 class="text-xl font-black mb-4 uppercase italic">Daftar Metode Pembayaran</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-black text-white text-left uppercase text-sm">
                                        <th class="p-4 border-2 border-black">Nama Metode</th>
                                        <th class="p-4 border-2 border-black text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="method in methods" :key="method.id" class="border-b-4 border-black font-bold hover:bg-gray-50">
                                        <td class="p-4 border-2 border-black uppercase text-lg">{{ method.name }}</td>
                                        <td class="p-4 border-2 border-black text-center">
                                            <div class="flex justify-center gap-2">
                                                <button 
                                                    @click="editMethod(method)"
                                                    class="bg-blue-400 border-2 border-black px-3 py-1 font-black uppercase text-xs hover:bg-blue-500 transition-colors"
                                                >
                                                    Edit
                                                </button>
                                                <button 
                                                    @click="deleteMethod(method.id)"
                                                    class="bg-red-500 text-white border-2 border-black px-3 py-1 font-black uppercase text-xs hover:bg-red-700 transition-colors"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="methods.length === 0">
                                        <td colspan="2" class="p-10 text-center font-bold text-gray-500 uppercase italic">
                                            Belum ada data metode pembayaran.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>