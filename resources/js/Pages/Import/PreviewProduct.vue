<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    // Ini harus sesuai dengan apa yang dikirim dari Controller
    importData: Object 
});

const form = useForm({});

// Sesuaikan kolom dengan DataTable.vue (label & key)
const columns = [
    { label: 'SKU', key: 'sku' },
    { label: 'Nama Produk', key: 'name' },
    { label: 'Kategori', key: 'category_raw' },
    { label: 'Satuan', key: 'unit_raw' },
    { label: 'Modal', key: 'buying_price' },
    { label: 'Jual', key: 'selling_price' },
];

const confirmImport = () => {
    if (confirm('Simpan data ini ke database?')) {
        form.post(route('products.import.store'));
    }
};
</script>

<template>
    <Head title="Preview Import" />

    <AuthenticatedLayout>
        <div class="p-8 max-w-7xl mx-auto">
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

            <DataTable 
                v-if="importData && importData.data"
                title="Data Terdeteksi"
                :resource="importData" 
                :columns="columns"
                :showAddButton="false"
            >
                <template #category_raw="{ value }">
                    <span class="text-[9px] font-black uppercase px-2 py-1 bg-blue-50 text-blue-600 rounded border border-blue-100">
                        {{ value }}
                    </span>
                </template>

                <template #buying_price="{ value }">
                    <span class="text-gray-400 text-[10px] mr-1">Rp</span>
                    <span class="font-medium">{{ Number(value).toLocaleString('id-ID') }}</span>
                </template>

                <template #selling_price="{ value }">
                    <span class="text-gray-400 text-[10px] mr-1">Rp</span>
                    <span class="font-black text-blue-700">{{ Number(value).toLocaleString('id-ID') }}</span>
                </template>
            </DataTable>

            <div v-else class="bg-white border-2 border-dashed border-gray-200 rounded-2xl p-20 text-center">
                <div class="text-4xl mb-4">⚠️</div>
                <p class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em]">Data tidak terbaca atau sesi berakhir</p>
                <Link :href="route('products.import.index')" class="mt-4 inline-block text-blue-600 font-bold text-[10px] uppercase underline">Coba Upload Ulang</Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>