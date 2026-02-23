<script setup>
import { ref } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    resource: Object,
    columns: Array,
    filters: Object,
    stores: Array,
    posUsers: Array,
});

const isModalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    store_id: '',
    pos_user_id: '',
    amount: '',
    description: '',
    transaction_at: new Date().toISOString().split('T')[0],
    image: null,
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors(); // Bersihkan error sebelumnya
    form.transaction_at = new Date().toISOString().split('T')[0];
    isModalOpen.value = true;
};

const openEditModal = (row) => {
    isEditing.value = true;
    form.clearErrors(); // Bersihkan error sebelumnya
    form.id = row.id;
    form.store_id = row.store_id;
    form.pos_user_id = row.pos_user_id;
    form.amount = row.amount;
    form.description = row.description;
    form.transaction_at = row.transaction_at ? row.transaction_at.split('T')[0] : '';
    form.image = null; 
    isModalOpen.value = true;
};

const submit = () => {
    form.post(route('expenses.store'), {
        onSuccess: () => {
            isModalOpen.value = false;
            form.reset();
        },
    });
};

const deleteExpense = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        router.delete(route('expenses.destroy', id), {
            preserveScroll: true,
        });
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};
</script>

<template>
    <Head title="Transaksi Keluar" />

    <AuthenticatedLayout>
        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <DataTable 
                    title="Riwayat Pengeluaran"
                    :resource="resource"
                    :columns="columns"
                    :filters="filters"
                    route-name="expenses.index"
                    show-add-button
                    @on-add="openAddModal"
                >
                    <template #transaction_at="{ value }">
                        <span class="text-gray-600">{{ new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) }}</span>
                    </template>

                    <template #image="{ value }">
                        <div v-if="value" class="flex items-center justify-start">
                            <a :href="'/storage/' + value" target="_blank" class="block group">
                                <img 
                                    :src="'/storage/' + value" 
                                    alt="Nota" 
                                    class="h-10 w-10 object-cover rounded border border-gray-200 group-hover:opacity-75 transition-opacity cursor-zoom-in"
                                />
                            </a>
                        </div>
                        <span v-else class="text-[10px] text-gray-400 italic">No Image</span>
                    </template>

                    <template #amount="{ value }">
                        <span class="font-semibold text-red-600">
                            {{ formatCurrency(value) }}
                        </span>
                    </template>

                    <template #store_name="{ row }">
                        <span class="text-sm text-gray-700 font-medium px-2.5 py-0.5 rounded-full bg-blue-50 border border-blue-100">
                            {{ row.store?.name || 'N/A' }}
                        </span>
                    </template>

                    <template #user_name="{ row }">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-900">{{ row.pos_user?.name || 'Sistem' }}</span>
                            <span class="text-[11px] text-gray-500 italic">Oleh Staf</span>
                        </div>
                    </template>

                    <template #created_by_name="{ row }">
                        <div class="flex flex-col">
                            <span class="text-xs font-medium text-gray-700">{{ row.creator?.name || 'Sistem' }}</span>
                            <span class="text-[10px] text-gray-400 italic">Administrator</span>
                        </div>
                    </template>

                    <template #actions="{ row }">
                        <div class="flex justify-end gap-4">
                            <button @click="openEditModal(row)" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm transition-colors">✏️</button>
                            <button @click="deleteExpense(row.id)" class="text-red-600 hover:text-red-900 font-medium text-sm transition-colors">❌</button>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <Transition 
            enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" 
            leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0"
        >
            <div v-if="isModalOpen" class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] z-50 flex items-center justify-center p-4">
                <Transition 
                    enter-active-class="ease-out duration-300" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" 
                    leave-active-class="ease-in duration-200" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95"
                >
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-visible border border-gray-100">
                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ isEditing ? 'Edit Pengeluaran' : 'Tambah Pengeluaran Baru' }}
                            </h3>
                            <button @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <form @submit.prevent="submit" class="p-6 space-y-5">
                            <div class="space-y-4">
                                <div>
                                    <SearchableSelect 
                                        v-model="form.store_id"
                                        :options="stores"
                                        label="Lokasi Toko / Cabang"
                                        placeholder="Cari dan pilih toko..."
                                    />
                                    <p v-if="form.errors.store_id" class="mt-1 text-xs text-red-600">{{ form.errors.store_id }}</p>
                                </div>

                                <div>
                                    <SearchableSelect 
                                        v-model="form.pos_user_id"
                                        :options="posUsers"
                                        label="PIC / Karyawan"
                                        placeholder="Cari nama personil..."
                                    />
                                    <p v-if="form.errors.pos_user_id" class="mt-1 text-xs text-red-600">{{ form.errors.pos_user_id }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Nominal (Rp)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500 text-sm">Rp</div>
                                        <input v-model="form.amount" type="number" :class="{'border-red-500': form.errors.amount}" class="w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="0">
                                    </div>
                                    <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tanggal</label>
                                    <input v-model="form.transaction_at" type="date" :class="{'border-red-500': form.errors.transaction_at}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <p v-if="form.errors.transaction_at" class="mt-1 text-xs text-red-600">{{ form.errors.transaction_at }}</p>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Detail Keperluan</label>
                                <textarea v-model="form.description" rows="2" :class="{'border-red-500': form.errors.description}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Contoh: Belanja ATK Kantor..."></textarea>
                                <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Lampiran Foto Nota</label>
                                <input type="file" @input="form.image = $event.target.files[0]" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer">
                                <p v-if="form.errors.image" class="mt-1 text-xs text-red-600">{{ form.errors.image }}</p>
                            </div>

                            <div class="flex justify-end gap-3 pt-4">
                                <button type="button" @click="isModalOpen = false" class="px-5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                    Batal
                                </button>
                                <button type="submit" :disabled="form.processing" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 inline-flex items-center transition-all shadow-md active:scale-95">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan Transaksi' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>