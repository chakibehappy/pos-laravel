<script setup>
import { ref, watch } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue'; 

const props = defineProps({
    resource: Object, 
    posUsers: Array,
    stores: Array,
    storeTypes: Array,
    filters: Object,
});

const showForm = ref(false);
const errorMessage = ref('');


const selectedStore = ref(props.filters?.store_id || '');
const selectedStoreType = ref(props.filters?.store_type_id || '');

watch([selectedStore, selectedStoreType], ([newStore, newType]) => {
    router.get(route('pos-user-stores.index'), { 
        ...props.filters, 
        store_id: newStore,
        store_type_id: newType,
        page: 1 
    }, { 
        preserveState: true, 
        replace: true,
        preserveScroll: true 
    });
});

const form = useForm({
    id: null,
    pos_user_id: '',
    store_id: '',
});

const openCreate = () => {
    errorMessage.value = '';
    form.reset();
    form.id = null;
    showForm.value = true;
};

const openEdit = (row) => {
    errorMessage.value = '';
    form.clearErrors();
    form.id = row.id;
    form.pos_user_id = row.pos_user_id;
    form.store_id = row.store_id;
    showForm.value = true;
};

const submit = () => {
    errorMessage.value = '';

    if (!form.pos_user_id || !form.store_id) {
        errorMessage.value = "Pilih User dan Unit Toko terlebih dahulu!";
        return;
    }
    
    const action = form.id ? 'put' : 'post';
    const url = form.id ? route('pos-user-stores.update', form.id) : route('pos-user-stores.store');

    form[action](url, {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
        onError: (errors) => {
            errorMessage.value = errors.pos_user_id || errors.store_id || "Gagal menyimpan data.";
        }
    });
};

const destroy = (id) => {
    if (confirm('Cabut akses user ini dari toko?')) {
        router.delete(route('pos-user-stores.destroy', id));
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric'
});
</script>

<template>
    <Head title="Pegawai Toko" />

    <AuthenticatedLayout>
        <div class="p-8">
            
            <div v-if="showForm" class="mb-8 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">
                        {{ form.id ? '✏️ Edit Akses User' : '➕ Assign User Ke Toko' }}
                    </h2>
                    <button @click="showForm = false" class="text-gray-400 hover:text-red-500 transition-colors">✕</button>
                </div>

                <div class="p-6">
                    <div v-if="errorMessage" class="mb-6 p-4 bg-red-600 border border-red-700 rounded-lg flex items-center gap-3 shadow-md animate-pulse">
                        <span class="text-white text-lg">🚫</span>
                        <p class="text-xs font-black text-white uppercase tracking-widest">{{ errorMessage }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-1">
                            <SearchableSelect 
                                label="Unit Toko Tujuan"
                                v-model="form.store_id"
                                :options="stores"
                                placeholder="Cari atau pilih unit toko..."
                            />
                        </div>

                        <div class="flex flex-col gap-1">
                            <SearchableSelect 
                                label="Pilih Kasir / Pegawai"
                                v-model="form.pos_user_id"
                                :options="posUsers"
                                placeholder="Cari nama kasir atau pegawai..."
                            />
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3 border-t border-gray-100 pt-6">
                        <button @click="submit" :disabled="form.processing" 
                            class="bg-blue-600 text-white px-8 py-2.5 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-md active:scale-95 disabled:opacity-50">
                            {{ form.id ? 'Simpan Perubahan' : 'Proses Penugasan' }}
                        </button>
                        <button @click="showForm = false" 
                            class="bg-white border border-gray-300 text-gray-600 px-8 py-2.5 rounded-lg text-xs font-bold uppercase hover:bg-gray-50 transition-all">
                            Batal
                        </button>
                    </div>
                </div>
            </div>

            <DataTable 
                title="Pegawai Toko"
                :resource="resource" 
                :columns="[
                    { label: 'Unit Toko', key: 'store_name', sortable: true }, 
                    { label: 'Nama User', key: 'pos_user_name', sortable: true }, 
                    { label: 'Tanggal', key: 'created_at', sortable: true }, 
                    { label: 'Diberikan Oleh', key: 'creator_name', sortable: true }
                ]"
                route-name="pos-user-stores.index" 
                :initialSearch="filters?.search || ''"
                :filters="filters"
                :showAddButton="!showForm"
                @on-add="openCreate"
            >
                <template #extra-filters>
                    <select 
                        v-model="selectedStore"
                        class="border border-gray-300 rounded-lg p-2.5 text-sm font-medium bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none min-w-[200px] shadow-sm transition-all"
                    >
                        <option value="">-- SEMUA TOKO --</option>
                        <option v-for="s in stores" :key="s.id" :value="s.id">
                            {{ s.name.toUpperCase() }}
                        </option>
                    </select>

                    <select 
                        v-model="selectedStoreType"
                        class="border border-gray-300 rounded-lg p-2.5 text-sm font-medium bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none min-w-[200px] shadow-sm transition-all">
                        <option value="">-- JENIS USAHA  --</option>
                        <option v-for="st in storeTypes" :key="st.id" :value="st.id">
                            {{ st.name }}
                        </option>
                    </select>
                </template>

                <template #store_name="{ row }">
                    <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-[10px] font-black uppercase border border-blue-100 shadow-sm">
                        {{ row.pos_user?.role == 'admin' ? 'ALL STORES' : row.store?.name }}
                    </span>
                </template>

                <template #pos_user_name="{ row }">
                    <span class="font-bold text-gray-800 uppercase text-xs tracking-tight italic">{{ row.pos_user?.name }}</span>
                </template>

                <template #created_at="{ row }">
                    <span class="font-mono text-[11px] text-gray-400">{{ formatDate(row.created_at) }}</span>
                </template>

                <template #creator_name="{ row }">
                    <span class="text-[10px] font-bold uppercase text-gray-500 italic">
                        👤 {{ row.creator?.name || 'System' }}
                    </span>
                </template>

                <template #actions="{ row }">
                    <div class="flex flex-row gap-4 justify-end items-center mr-2">
                        <button @click="openEdit(row)" title="Edit" class="text-gray-300 hover:text-blue-600 transition-colors transform hover:scale-125">✏️</button>
                        <button @click="destroy(row.id)" title="Hapus" class="text-gray-300 hover:text-red-600 transition-colors transform hover:scale-125">❌</button>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>