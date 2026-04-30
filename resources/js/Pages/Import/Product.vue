<script setup>
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    categories: Array,
    unitTypes: Array,
});

const form = useForm({
    file: null,
});

const filePreview = ref(null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    form.file = file;
    if (file) {
        filePreview.value = file.name;
    }
};

const submit = () => {
    if (!form.file) return alert('Silakan pilih file Excel terlebih dahulu');

    form.post(route('products.import.preview'), { // Mengarah ke rute preview
        forceFormData: true,
        preserveScroll: true,
        // onSuccess tidak reset form agar user tidak bingung jika ingin kembali
    });
};
</script>

<template>
    <Head title="Import Produk" />

    <AuthenticatedLayout>
        <div class="p-8 max-w-5xl mx-auto">
            <div class="flex justify-between items-center mb-10">
                <div>
                    <h2 class="text-xl font-black uppercase tracking-tight text-gray-800">Pusat Impor Produk</h2>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Unggah file untuk pengisian data massal</p>
                </div>
                <Link :href="route('products.index')" class="text-[10px] font-bold uppercase px-4 py-2 bg-white border border-gray-200 rounded hover:bg-gray-50 transition-all shadow-sm text-gray-500">
                    ⬅️ Kembali
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="space-y-6">
                    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                        <h3 class="text-[10px] font-black uppercase text-gray-700 mb-4 tracking-widest border-b pb-2">Struktur Kolom</h3>
                        <ul class="space-y-2">
                            <li v-for="col in ['nama_produk', 'sku', 'id_kategori', 'id_satuan', 'harga_modal', 'harga_jual', 'stok_awal']" 
                                :key="col" class="text-[10px] font-bold text-gray-500 uppercase flex items-center gap-2">
                                <span class="w-1 h-1 bg-blue-500 rounded-full"></span> {{ col }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="md:col-span-2 space-y-4">
                    <div class="relative border-2 border-dashed border-gray-200 rounded-2xl p-12 bg-white hover:border-blue-400 transition-colors flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-4">
                            <span class="text-2xl">📁</span>
                        </div>

                        <div v-if="!filePreview">
                            <h4 class="text-sm font-black uppercase text-gray-700">Pilih File Excel</h4>
                            <p class="text-[10px] font-bold text-gray-400 uppercase mt-1 tracking-widest">Klik atau seret file (.xlsx) ke sini</p>
                        </div>
                        <div v-else class="bg-blue-600 px-4 py-2 rounded text-white animate-in zoom-in-95">
                            <p class="text-xs font-black uppercase">{{ filePreview }}</p>
                        </div>

                        <input 
                            type="file" 
                            @change="handleFileChange" 
                            class="absolute inset-0 opacity-0 cursor-pointer" 
                            accept=".xlsx, .xls, .csv"
                        />
                    </div>

                    <button 
                        @click="submit"
                        :disabled="form.processing || !form.file"
                        class="w-full bg-gray-900 text-white py-4 rounded-xl text-xs font-black uppercase tracking-[0.2em] shadow-xl disabled:opacity-20 active:scale-95 transition-all"
                    >
                        <span v-if="form.processing">Menganalisis File...</span>
                        <span v-else>👁️ Pratinjau Data</span>
                    </button>
                    
                    <p v-if="form.errors.file" class="text-red-500 text-[10px] font-black uppercase text-center italic">{{ form.errors.file }}</p>
                </div>
            </div>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h4 class="text-[9px] font-black text-gray-400 uppercase mb-3 tracking-widest text-center italic">Daftar ID Kategori</h4>
                    <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto pr-2 custom-scrollbar">
                        <div v-for="c in categories" :key="c.id" class="text-[9px] font-bold text-gray-600 bg-white p-2 rounded border border-gray-100 flex justify-between">
                            <span>{{ c.name.toUpperCase() }}</span>
                            <span class="text-blue-600">ID: {{ c.id }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h4 class="text-[9px] font-black text-gray-400 uppercase mb-3 tracking-widest text-center italic">Daftar ID Satuan</h4>
                    <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto pr-2 custom-scrollbar">
                        <div v-for="u in unitTypes" :key="u.id" class="text-[9px] font-bold text-gray-600 bg-white p-2 rounded border border-gray-100 flex justify-between">
                            <span>{{ u.name.toUpperCase() }}</span>
                            <span class="text-green-600">ID: {{ u.id }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>