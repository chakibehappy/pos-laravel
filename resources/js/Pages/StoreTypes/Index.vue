<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue'; 

const props = defineProps({
    types: Array,
});

const isEditing = ref(false); 

const form = useForm({
    id: null, 
    name: '',
});

const editType = (type) => {
    isEditing.value = true;
    form.id = type.id;   
    form.name = type.name;
};

const cancelEdit = () => {
    isEditing.value = false;
    form.reset(); 
};

const submit = () => {
   
    form.post(route('store-types.store'), {
        onSuccess: () => cancelEdit(),
    });
};

const deleteType = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus  Jenis Usaha ini?')) {
        form.delete(route('store-types.destroy', id));
    }
};
</script>

<template>
    <Head title=" Jenis Usaha  " />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="bg-white border-4 border-black p-6 h-fit  -[8px_8px_0px_0px_rgba(0,0,0,1)]">
                        <h3 class="text-xl font-black mb-4 uppercase italic">
                            {{ isEditing ? 'Edit  Jenis Usaha' : 'Tambah Jenis Usaha' }}
                        </h3>
                        <form @submit.prevent="submit">
                            <div>
                                <label class="block font-bold mb-2 uppercase text-sm">Jenis Usaha</label>
                                <input 
                                    v-model="form.name"
                                    type="text" 
                                    class="w-full border-4 border-black p-3 font-bold focus:ring-0 focus:border-yellow-400 uppercase"
                                    placeholder="Contoh: Konveksi,Konter, dll"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-red-600 mt-2 font-bold italic text-xs uppercase">{{ form.errors.name }}</div>
                            </div>

                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="mt-6 w-full border-4 border-black p-3 font-black uppercase transition-all hover:-translate-x-1 hover:-translate-y-1  -[4px_4px_0px_0px_rgba(0,0,0,1)] active: -none active:translate-x-0 active:translate-y-0 disabled:bg-gray-400"
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
                                Batal / Tambah Baru
                            </button>
                        </form>
                    </div>

                    <div class="md:col-span-2 bg-white border-4 border-black p-6  -[8px_8px_0px_0px_rgba(0,0,0,1)]">
                        <h3 class="text-xl font-black mb-4 uppercase italic text-left">Daftar  Jenis Usaha  </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-black text-white text-left uppercase text-sm italic">
                                        <th class="p-4 border-2 border-black">  Jenis Usaha</th>
                                        <th class="p-4 border-2 border-black text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="type in types" :key="type.id" class="border-b-4 border-black font-bold hover:bg-gray-50 transition-colors">
                                        <td class="p-4 border-2 border-black uppercase text-lg text-left">{{ type.name }}</td>
                                        <td class="p-4 border-2 border-black text-center">
                                            <div class="flex justify-center gap-4">
                                                <button 
                                                    @click="editType(type)"
                                                    class="font-black uppercase text-xs text-blue-600 decoration-2 hover:bg-blue-600 hover:text-white px-2 py-1 transition-all"
                                                >
                                                    ✏️
                                                </button>
                                                <button 
                                                    @click="deleteType(type.id)"
                                                    class="font-black uppercase text-xs text-red-500 decoration-2 hover:bg-red-500 hover:text-white px-2 py-1 transition-all"
                                                >
                                                    ❌
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="types.length === 0">
                                        <td colspan="2" class="p-10 text-center font-bold text-gray-500 uppercase italic">
                                            Belum ada data  Jenis Usaha  .
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