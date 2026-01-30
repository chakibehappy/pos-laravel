<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    stocks: Object,
    stores: Array,
    products: Array,
    flash: Object
});

const showForm = ref(false);
const search = ref('');

// Fitur Pencarian Real-time (Sync dengan Controller)
watch(search, debounce((v) => {
    router.get(route('store-products.index'), { search: v }, { preserveState: true });
}, 500));

const form = useForm({
    id: null,
    store_id: '',
    product_id: '',
    stock: 0
});

// Menangani Tambah/Update Stok
const submit = () => {
    form.post(route('store-products.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

// Fungsi Edit (Mengisi form dengan data yang ada)
const openEdit = (row) => {
    form.id = row.id;
    form.store_id = row.store_id;
    form.product_id = row.product_id;
    form.stock = row.stock;
    showForm.value = true;
};

// Fungsi Tarik Stok (Delete)
const deleteStock = (id) => {
    if (confirm('Apakah Anda yakin ingin menarik kembali stok ini ke gudang pusat?')) {
        router.delete(route('store-products.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Manajemen Stok Cabang" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-6">
                <div>
                    <h1 class="text-6xl font-black uppercase italic tracking-tighter leading-none">
                        Stok <span class="text-yellow-400 drop- -[4px_4px_0_#000]">Cabang</span>
                    </h1>
                    <p class="font-bold uppercase mt-2 text-gray-500 italic   decoration-black decoration-2">Inventory Distribution System</p>
                </div>
                
                <button @click="showForm = true; form.reset(); form.id = null" class="bg-black text-white px-10 py-4 font-black text-xl border-4 border-black  -[8px_8px_0px_0px_rgba(0,0,0,1)] hover: -none hover:translate-x-2 hover:translate-y-2 transition-all uppercase">
                    + Alokasi Stok
                </button>
            </div>

            <div v-if="flash.message" class="mb-6 p-4 bg-green-400 border-4 border-black font-black uppercase  -[4px_4px_0_#000] italic">
                ✅ {{ flash.message }}
            </div>

            <div class="mb-8">
                <input v-model="search" type="text" placeholder="Cari cabang, produk, atau SKU..." 
                    class="w-full md:w-1/3 border-4 border-black p-4 font-black uppercase focus: -[6px_6px_0_#000] outline-none transition-all placeholder:text-gray-400">
            </div>

            <div class="bg-white border-4 border-black  -[12px_12px_0px_0px_rgba(0,0,0,1)]">
                <DataTable 
                    :resource="stocks" 
                    :columns="[
                        { label: 'Cabang', key: 'store_name' },
                        { label: 'Produk', key: 'product_name' },
                        { label: 'SKU', key: 'product_sku' },
                        { label: 'Jumlah Stok', key: 'stock' },
                    ]"
                >
                    <template #stock="{ row }">
                        <div class="flex items-center gap-2">
                            <span class="bg-yellow-300 border-2 border-black px-4 py-1 font-black text-xl  -[3px_3px_0_#000]">
                                {{ row.stock }}
                            </span>
                            <span class="text-xs font-black uppercase italic text-gray-500">
                                {{ row.product?.unit_type?.name || 'Unit' }}
                            </span>
                        </div>
                    </template>

                    <template #actions="{ row }">
                        <div class="flex gap-4 justify-end">
                            <button @click="openEdit(row)" class="font-black text-xs uppercase   decoration-2 hover:text-blue-600">
                                Edit
                            </button>
                            <button @click="deleteStock(row.id)" class="text-red-500 font-black text-xs uppercase   decoration-2 hover:bg-black hover:text-white px-1">
                                Tarik Stok
                            </button>
                        </div>
                    </template>
                </DataTable>
            </div>

            <div v-if="showForm" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white border-[8px] border-black p-10 w-full max-w-2xl  -[20px_20px_0px_0px_rgba(255,215,0,1)] relative">
                    <button @click="showForm = false" class="absolute top-4 right-4 font-black text-2xl hover:scale-125 transition-transform">✕</button>
                    
                    <h2 class="text-4xl font-black uppercase italic mb-8   decoration-yellow-400 decoration-8">
                        {{ form.id ? 'Update Alokasi' : 'Kirim Stok Baru' }}
                    </h2>
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block font-black uppercase mb-2 italic">Cabang Tujuan</label>
                            <select v-model="form.store_id" :disabled="form.id" class="w-full border-4 border-black p-4 font-black uppercase outline-none bg-gray-50 focus:bg-white disabled:opacity-50">
                                <option value="">-- PILIH UNIT CABANG --</option>
                                <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-black uppercase mb-2 italic">Produk (Sisa Gudang)</label>
                            <select v-model="form.product_id" :disabled="form.id" class="w-full border-4 border-black p-4 font-black uppercase outline-none bg-gray-50 focus:bg-white disabled:opacity-50">
                                <option value="">-- PILIH PRODUK --</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">
                                    {{ p.name }} — (Tersedia: {{ p.stock }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-black uppercase mb-2 italic">Jumlah Stok di Cabang</label>
                            <input v-model="form.stock" type="number" min="0" class="w-full border-4 border-black p-4 font-black text-4xl outline-none  -[inner_4px_4px_0_#ccc]" placeholder="0">
                            
                            <div v-if="form.errors.message" class="mt-4 p-3 bg-red-500 text-white border-4 border-black font-black uppercase text-xs italic animate-bounce">
                                ⚠️ {{ form.errors.message }}
                            </div>
                        </div>

                        <div class="flex gap-4 pt-6">
                            <button type="submit" :disabled="form.processing" class="flex-1 bg-black text-white p-5 font-black uppercase text-xl border-4 border-black  -[6px_6px_0_#000] active: -none active:translate-x-1 active:translate-y-1 transition-all">
                                {{ form.id ? 'SIMPAN PERUBAHAN' : 'KONFIRMASI PENGIRIMAN' }}
                            </button>
                            <button type="button" @click="showForm = false" class="bg-white text-black px-8 py-5 font-black uppercase border-4 border-black hover:bg-gray-100 transition-all text-xl">
                                BATAL
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>