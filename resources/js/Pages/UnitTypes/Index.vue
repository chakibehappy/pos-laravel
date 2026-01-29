<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    units: Array
});

// State untuk Form Toggle
const showForm = ref(false);

// Inisialisasi Form menggunakan Inertia useForm
const form = useForm({
    id: null,
    name: ''
});

// Fungsi buka form untuk Tambah
const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    showForm.value = true;
};

// Fungsi buka form untuk Edit
const openEdit = (item) => {
    form.clearErrors();
    form.id = item.id;
    form.name = item.name;
    showForm.value = true;
};

// Eksekusi Simpan (Tambah/Edit)
const submit = () => {
    form.post(route('unit-types.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

// Eksekusi Hapus
const deleteunit = (id) => {
    if (confirm('Hapus Satuan ini? Semua produk terkait mungkin akan kehilangan Satuannya.')) {
        form.delete(route('unit-types.destroy', id));
    }
};
</script>

<template>
    <Head title="Satuan Produk" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-black uppercase italic tracking-tighter">Satuan Produk</h1>
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Pengaturan Jenis Barang</p>
                </div>
                <button 
                    v-if="!showForm"
                    @click="openCreate" 
                    class="bg-yellow-400 text-black px-8 py-3 font-black uppercase border-4 border-black shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition-all"
                >
                    + Satuan Baru
                </button>
            </div>

            <div v-if="showForm" class="mb-10 p-6 border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                <h2 class="font-black uppercase mb-6 italic text-xl underline decoration-yellow-400">
                    {{ form.id ? 'Edit' : 'Tambahkan' }}
                </h2>
                
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-black uppercase text-gray-400">Nama Satuan</label>
                    <div class="flex flex-col md:flex-row gap-4">
                        <input 
                            v-model="form.name" 
                            type="text" 
                            placeholder="Contoh: Makanan, Minuman, Elektronik..." 
                            class="flex-1 border-4 border-black p-4 font-black uppercase focus:bg-yellow-50 outline-none transition-colors"
                            @keyup.enter="submit"
                        />
                        <div class="flex gap-2">
                            <button 
                                @click="submit" 
                                :disabled="form.processing"
                                class="bg-black text-white px-8 py-4 font-black uppercase border-2 border-black hover:bg-gray-800 shadow-[4px_4px_0px_0px_rgba(0,0,0,0.3)] active:shadow-none transition-all disabled:bg-gray-400"
                            >
                                {{ form.processing ? '...' : 'Simpan' }}
                            </button>
                            <button 
                                @click="showForm = false" 
                                class="border-4 border-black px-6 py-4 font-black uppercase hover:bg-gray-100 transition-colors"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                    <span v-if="form.errors.name" class="text-red-600 font-black text-[10px] uppercase italic">{{ form.errors.name }}</span>
                </div>
            </div>

            <div class="bg-white border-4 border-black shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black text-white border-b-4 border-black font-black uppercase text-xs italic">
                            <th class="p-4 w-20 text-center">ID</th>
                            <th class="p-4">Nama Satuan</th>
                            <th class="p-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-black">
                        <tr v-for="(cat, index) in units" :key="cat.id" class="hover:bg-yellow-50 transition-colors">
                            <td class="p-4 text-center font-mono text-xs text-gray-400">#{{ cat.id }}</td>
                            <td class="p-4 font-black uppercase tracking-tight text-lg">{{ cat.name }}</td>
                            <td class="p-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button 
                                        @click="openEdit(cat)"
                                        class="bg-blue-400 p-2 border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-0.5 hover:translate-y-0.5 transition-all"
                                    >
                                        ✏️
                                    </button>
                                    <button 
                                        @click="deleteunit(cat.id)"
                                        class="bg-red-500 p-2 border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-0.5 hover:translate-y-0.5 transition-all"
                                    >
                                        ❌
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="units.length === 0">
                            <td colspan="3" class="p-12 text-center">
                                <p class="text-gray-300 font-black italic uppercase text-2xl">Belum ada Satuan</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>