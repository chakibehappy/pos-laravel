<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Data dikirim dari Controller melalui inertia('Import/PriviewProduct', ['data' => $previewData])
const props = defineProps({
    data: {
        type: Array,
        default: () => []
    }
});

// Masukkan data ke dalam reactive variable agar bisa diedit langsung di tabel
const items = ref([...props.data]);

// Fungsi menghapus baris jika user tidak ingin mengimport produk tertentu
const removeItem = (index) => {
    items.value.splice(index, 1);
};

// State loading saat proses simpan ke database
const processing = ref(false);

// Fungsi kirim data final (JSON) ke method store di controller
const submitFinalData = () => {
    if (items.value.length === 0) {
        alert("Tidak ada data untuk disimpan.");
        return;
    }

    if (!confirm(`Simpan ${items.value.length} produk ke database?`)) return;

    processing.value = true;
    
    router.post(route('products.import.store'), {
        products: items.value // Mengirim array of objects
    }, {
        onFinish: () => processing.value = false,
        onSuccess: () => {
            // Berhasil disimpan, controller akan redirect otomatis
        },
        onError: (errors) => {
            alert("Terjadi kesalahan saat menyimpan data.");
            console.error(errors);
        }
    });
};

const cancel = () => {
    if (confirm("Batalkan import? Data pratinjau akan hilang.")) {
        router.get(route('products.import.index'));
    }
};
</script>

<template>
    <Head title="Koreksi Data Import" />

    <AuthenticatedLayout>
        <div class="py-8 px-4 sm:px-6 lg:px-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto">
                
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                    <div>
                        <h2 class="text-xl font-black uppercase tracking-tight text-gray-800">Koreksi Data Import</h2>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">
                            Periksa kembali data sebelum masuk ke database. Klik pada kolom untuk mengedit.
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <button 
                            @click="cancel"
                            class="px-5 py-2.5 text-[10px] font-black uppercase tracking-widest text-gray-500 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition shadow-sm"
                        >
                            Batal
                        </button>
                        <button 
                            @click="submitFinalData"
                            :disabled="processing || items.length === 0"
                            class="px-5 py-2.5 text-[10px] font-black uppercase tracking-widest text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 shadow-xl transition active:scale-95"
                        >
                            <span v-if="processing" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            🚀 Konfirmasi & Simpan ({{ items.length }})
                        </button>
                    </div>
                </div>

                <div class="bg-white shadow-sm border border-gray-200 rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-[10px] uppercase tracking-[0.15em] font-black text-gray-400">
                                    <th class="px-6 py-5 min-w-[250px]">Nama Produk <span class="text-red-500">*</span></th>
                                    <th class="px-6 py-5 min-w-[150px]">SKU</th>
                                    <th class="px-6 py-5 min-w-[180px]">Kategori</th>
                                    <th class="px-6 py-5 min-w-[120px]">Satuan</th>
                                    <th class="px-6 py-5 min-w-[150px] text-right">Harga Beli</th>
                                    <th class="px-6 py-5 min-w-[150px] text-right">Harga Jual</th>
                                    <th class="px-6 py-5 text-center w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, index) in items" :key="index" class="hover:bg-blue-50/30 transition-colors group">
                                    <td class="px-4 py-3">
                                        <input 
                                            v-model="item.name" 
                                            class="w-full text-xs font-bold border-transparent focus:border-blue-500 focus:ring-0 rounded-md bg-transparent group-hover:bg-white transition-all" 
                                            type="text"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        <input 
                                            v-model="item.sku" 
                                            placeholder="Otomatis"
                                            class="w-full text-xs font-mono border-transparent focus:border-blue-500 focus:ring-0 rounded-md bg-transparent group-hover:bg-white transition-all" 
                                            type="text"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input 
                                            v-model="item.category_name" 
                                            class="w-full text-xs border-transparent focus:border-blue-500 focus:ring-0 rounded-md bg-transparent group-hover:bg-white transition-all uppercase" 
                                            type="text"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input 
                                            v-model="item.unit_name" 
                                            class="w-full text-xs border-transparent focus:border-blue-500 focus:ring-0 rounded-md bg-transparent group-hover:bg-white transition-all" 
                                            type="text"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[9px] font-bold text-gray-400">Rp</span>
                                            <input 
                                                v-model="item.buying_price" 
                                                class="w-full text-xs text-right border-transparent focus:border-blue-500 focus:ring-0 rounded-md bg-transparent group-hover:bg-white transition-all pl-7" 
                                                type="number"
                                            />
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[9px] font-bold text-gray-400">Rp</span>
                                            <input 
                                                v-model="item.selling_price" 
                                                class="w-full text-xs text-right border-transparent focus:border-blue-500 focus:ring-0 rounded-md bg-transparent group-hover:bg-white transition-all pl-7" 
                                                type="number"
                                            />
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button 
                                            @click="removeItem(index)" 
                                            class="p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition"
                                            title="Hapus Baris"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="items.length === 0" class="py-24 text-center">
                        <div class="w-16 h-16 bg-gray-50 text-gray-300 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
                            🗑️
                        </div>
                        <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Semua data telah dihapus</p>
                        <button @click="cancel" class="mt-4 text-[10px] font-black text-blue-600 uppercase hover:underline">Kembali ke Unggah</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Menghilangkan panah pada input number */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}

/* Custom shadow untuk fokus input */
input:focus {
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
}
</style>