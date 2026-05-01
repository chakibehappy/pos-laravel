<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';


const props = defineProps({
    importData: Object 
});

const isTable1Open = ref(true);
const isTable2Open = ref(true);
const isTable3Open = ref(false);

// State untuk menyimpan index data yang akan diabaikan (disilang)
const excludedSimilar = ref([]);

const toggleExclude = (index) => {
    if (excludedSimilar.value.includes(index)) {
        excludedSimilar.value = excludedSimilar.value.filter(i => i !== index);
    } else {
        excludedSimilar.value.push(index);
    }
};

const expandedSimilarRows = ref([]);
const toggleSimilarRow = (index) => {
    if (expandedSimilarRows.value.includes(index)) {
        expandedSimilarRows.value = expandedSimilarRows.value.filter(i => i !== index);
    } else {
        expandedSimilarRows.value.push(index);
    }
};

const form = useForm({
    excluded_indices: [],
});

const confirmImport = () => {
    if (confirm('Simpan data ini ke database? (Data baru akan dibuat, data mirip akan diperbarui kecuali yang Anda abaikan)')) {
        
        // Isi form dengan daftar index yang disilang
        form.excluded_indices = excludedSimilar.value;

        form.post(route('products.import.store'), {
            preserveScroll: true,
            onSuccess: () => {
                // Berhasil
            }
        });
    }
};
</script>

<template>
    <Head title="Preview Import" />

    <AuthenticatedLayout>
        <div class="p-8 max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-10">
                <div>
                    <h2 class="text-xl font-black uppercase tracking-tight text-gray-800">Pratinjau Impor</h2>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Periksa baris data di bawah sebelum konfirmasi</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('products.import.index')" class="text-[10px] font-bold uppercase px-4 py-2 bg-white border border-gray-200 rounded hover:bg-gray-50 shadow-sm text-gray-500">
                        ⬅️ Kembali
                    </Link>
                    <button 
                        @click="confirmImport" 
                        :disabled="form.processing" 
                        class="text-[10px] font-black uppercase px-6 py-2 bg-gray-900 text-white rounded hover:bg-black shadow-xl disabled:opacity-20 active:scale-95 transition-all"
                    >
                        {{ form.processing ? 'Menyimpan...' : '🚀 Konfirmasi Simpan' }}
                    </button>
                </div>
            </div>

            <div v-if="importData" class="space-y-8">
                
                <!-- CARD 1: POTENSI DUPLIKAT -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <button @click="isTable1Open = !isTable1Open" class="w-full flex items-center justify-between p-5 hover:bg-gray-50 transition-colors border-none outline-none">
                        <div class="flex items-center gap-4">
                            <div class="bg-amber-50 p-2 rounded-lg text-xl">⚠️</div>
                            <div class="text-left">
                                <h3 class="text-xs font-black uppercase text-gray-700 tracking-wider">{{ importData.meta.total_similar }} Potensi Duplikat</h3>
                                <p class="text-[9px] font-bold text-gray-400 uppercase">Klik baris untuk detail. Gunakan kotak silang untuk mengabaikan data.</p>
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 transition-transform duration-300" :class="{ 'rotate-180': isTable1Open }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    
                    <div v-show="isTable1Open" class="border-t border-gray-100">
                        <div class="grid grid-cols-12 gap-4 px-6 py-3 bg-gray-50 border-b border-gray-100 text-[9px] font-black uppercase text-gray-400 tracking-widest">
                            <div class="col-span-3">Nama Produk</div>
                            <div class="col-span-1">SKU</div>
                            <div class="col-span-2 text-center">Kategori</div>
                            <div class="col-span-1 text-center">Satuan</div>
                            <div class="col-span-2 text-right">Harga Modal</div>
                            <div class="col-span-2 text-right">Harga Jual</div>
                            <div class="col-span-1 text-center">Abaikan</div>
                        </div>

                        <div v-for="(item, index) in importData.similar" :key="index" class="border-b border-gray-100 last:border-0">
                            <!-- Baris Import (Klik dimana saja kecuali tombol silang) -->
                            <div 
                                @click="toggleSimilarRow(index)"
                                class="grid grid-cols-12 gap-4 px-6 py-4 items-center bg-white relative hover:bg-gray-50 group cursor-pointer transition-all duration-300"
                                :class="{'opacity-40 grayscale-[0.5]': excludedSimilar.includes(index)}"
                            >
                                <div class="absolute left-0 top-0 bottom-0 w-1" :class="excludedSimilar.includes(index) ? 'bg-red-500' : 'bg-emerald-400'"></div>
                                
                                <div class="col-span-3 flex items-center gap-3">
                                    <div class="text-gray-300 group-hover:text-emerald-500 transition-transform" :class="{'rotate-90': expandedSimilarRows.includes(index)}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[6px] font-black text-emerald-600 uppercase tracking-tighter mb-0.5">Data Import</span>
                                        <span class="text-[11px] font-black text-gray-800 uppercase leading-none">{{ item.excel.name }}</span>
                                    </div>
                                </div>

                                <div class="col-span-1 text-[10px] font-bold text-gray-600">{{ item.excel.sku || '' }}</div>
                                <div class="col-span-2 text-center text-[9px] font-black text-blue-600 uppercase">{{ item.excel.category_raw }}</div>
                                <div class="col-span-1 text-center text-[10px] font-bold text-gray-600 uppercase">{{ item.excel.unit_raw }}</div>
                                <div class="col-span-2 text-right text-[10px] font-bold text-gray-500">Rp {{ Number(item.excel.buying_price).toLocaleString('id-ID') }}</div>
                                <div class="col-span-2 text-right text-[11px] font-black text-blue-700">Rp {{ Number(item.excel.selling_price).toLocaleString('id-ID') }}</div>
                                
                                <div class="col-span-1 flex justify-center">
                                    <button 
                                        @click.stop="toggleExclude(index)" 
                                        class="w-6 h-6 border-2 rounded flex items-center justify-center transition-all active:scale-90"
                                        :class="excludedSimilar.includes(index) ? 'border-red-500 bg-red-50' : 'border-gray-200 bg-white hover:border-gray-400'"
                                    >
                                        <svg v-if="excludedSimilar.includes(index)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Detail Sistem -->
                            <div v-show="expandedSimilarRows.includes(index)" class="bg-gray-100 border-t border-gray-200">
                                <div v-for="(dbItem, dbIndex) in item.databases" :key="dbIndex" class="grid grid-cols-12 gap-4 px-6 py-3 items-center relative border-b border-gray-200 last:border-0 pl-14 text-gray-500">
                                    <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-red-300"></div>
                                    <div class="col-span-3 flex flex-col">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <span class="text-[6px] font-black text-red-600 uppercase">Sistem #{{ dbIndex + 1 }}</span>
                                            <span class="text-[6px] font-black text-white bg-red-500 px-1 rounded">{{ dbItem.similarity }}% Mirip</span>
                                        </div>
                                        <span class="text-[11px] font-bold uppercase leading-none text-gray-700">{{ dbItem.name }}</span>
                                    </div>
                                    <div class="col-span-1 text-[10px] font-semibold">{{ dbItem.sku || '' }}</div>
                                    <div class="col-span-2 text-center text-[9px] uppercase font-semibold">{{ dbItem.category_name || '-' }}</div>
                                    <div class="col-span-1 text-center text-[10px] uppercase font-semibold">{{ dbItem.unit_name || '-' }}</div>
                                    <div class="col-span-2 text-right text-[10px] font-semibold">Rp {{ Number(dbItem.buying_price).toLocaleString('id-ID') }}</div>
                                    <div class="col-span-2 text-right text-[11px] font-bold text-red-600">Rp {{ Number(dbItem.selling_price).toLocaleString('id-ID') }}</div>
                                    <div class="col-span-1"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: DATA BARU -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <button @click="isTable2Open = !isTable2Open" class="w-full flex items-center justify-between p-5 hover:bg-gray-50 transition-colors border-none outline-none">
                        <div class="flex items-center gap-4">
                            <div class="bg-green-50 p-2 rounded-lg text-xl">📦</div>
                            <div class="text-left">
                                <h3 class="text-xs font-black uppercase text-gray-700 tracking-wider">{{ importData.meta.total_new }} Data Baru</h3>
                                <p class="text-[9px] font-bold text-gray-400 uppercase">Data ini tidak ditemukan di sistem dan akan dibuat baru</p>
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 transition-transform duration-300" :class="{ 'rotate-180': isTable2Open }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div v-show="isTable2Open" class="border-t border-gray-100">
                        <div class="grid grid-cols-11 gap-4 px-6 py-3 bg-gray-50 border-b border-gray-100 text-[9px] font-black uppercase text-gray-400 tracking-widest">
                            <div class="col-span-3">Nama Produk</div>
                            <div class="col-span-1">SKU</div>
                            <div class="col-span-2 text-center">Kategori</div>
                            <div class="col-span-1 text-center">Satuan</div>
                            <div class="col-span-2 text-right">Harga Modal</div>
                            <div class="col-span-2 text-right">Harga Jual</div>
                        </div>
                        <div v-for="(item, index) in importData.new_data" :key="index" class="grid grid-cols-11 gap-4 px-6 py-4 items-center border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors">
                            <div class="col-span-3 text-[11px] font-black text-gray-800 uppercase">{{ item.name }}</div>
                            <div class="col-span-1 text-[10px] font-bold text-gray-600">{{ item.sku || '' }}</div>
                            <div class="col-span-2 text-center text-[9px] font-black text-gray-600 uppercase">{{ item.category_raw }}</div>
                            <div class="col-span-1 text-center text-[10px] font-bold text-gray-600 uppercase">{{ item.unit_raw }}</div>
                            <div class="col-span-2 text-right text-[10px] font-bold text-gray-500">Rp {{ Number(item.buying_price).toLocaleString('id-ID') }}</div>
                            <div class="col-span-2 text-right text-[11px] font-black text-emerald-600">Rp {{ Number(item.selling_price).toLocaleString('id-ID') }}</div>
                        </div>
                    </div>
                </div>

                <!-- CARD 3: DATA IDENTIK -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <button @click="isTable3Open = !isTable3Open" class="w-full flex items-center justify-between p-5 hover:bg-gray-50 transition-colors border-none outline-none">
                        <div class="flex items-center gap-4">
                            <div class="bg-gray-100 p-2 rounded-lg text-xl">📑</div>
                            <div class="text-left">
                                <h3 class="text-xs font-black uppercase text-gray-700 tracking-wider">{{ importData.meta.total_identical }} Data Identik</h3>
                                <p class="text-[9px] font-bold text-gray-400 uppercase">Data sudah ada di sistem dengan informasi yang sama persis</p>
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 transition-transform duration-300" :class="{ 'rotate-180': isTable3Open }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div v-show="isTable3Open" class="border-t border-gray-100">
                        <div class="grid grid-cols-11 gap-4 px-6 py-3 bg-gray-50 border-b border-gray-100 text-[9px] font-black uppercase text-gray-400 tracking-widest">
                            <div class="col-span-3">Nama Produk</div>
                            <div class="col-span-1">SKU</div>
                            <div class="col-span-2 text-center">Kategori</div>
                            <div class="col-span-1 text-center">Satuan</div>
                            <div class="col-span-2 text-right">Harga Modal</div>
                            <div class="col-span-2 text-right">Harga Jual</div>
                        </div>
                        <div v-for="(item, index) in importData.identical" :key="index" class="grid grid-cols-11 gap-4 px-6 py-4 items-center border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors">
                            <div class="col-span-3 text-[11px] font-bold text-gray-700 uppercase">{{ item.name }}</div>
                            <div class="col-span-1 text-[10px] font-medium text-gray-600">{{ item.sku || '' }}</div>
                            <div class="col-span-2 text-center text-[9px] text-gray-600 uppercase">{{ item.category_raw }}</div>
                            <div class="col-span-1 text-center text-[10px] text-gray-600 uppercase">{{ item.unit_raw }}</div>
                            <div class="col-span-2 text-right text-[10px] text-gray-500">Rp {{ Number(item.buying_price).toLocaleString('id-ID') }}</div>
                            <div class="col-span-2 text-right text-[11px] font-black text-gray-700">Rp {{ Number(item.selling_price).toLocaleString('id-ID') }}</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>