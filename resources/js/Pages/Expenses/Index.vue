<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    resource: Object,
    columns: Array,
    filters: Object,
    stores: Array,
    posUsers: Array,
    expenseTypes: Array, // Tambahkan props baru ini
});

const page = usePage();
const authUser = page.props.auth.user;

const isModalOpen = ref(false);
const isEditing = ref(false);

// Menggabungkan opsi "GLOBAL" ke dalam daftar toko untuk dropdown
const storeOptions = computed(() => {
    return [
        { id: null, name: 'GLOBAL' },
        ...props.stores
    ];
});

const form = useForm({
    id: null,
    store_id: null,
    expense_type_id: '', // Tambahkan field tipe pengeluaran
    pos_user_id: '',
    amount: '',
    description: '',
    transaction_at: new Date().toISOString().split('T')[0],
    image: null,
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    
    form.store_id = null; // Default ke Global

    const matchedUser = props.posUsers.find(u => u.username === authUser.email);
    if (matchedUser) {
        form.pos_user_id = matchedUser.id;
    }
    
    form.transaction_at = new Date().toISOString().split('T')[0];
    isModalOpen.value = true;
};

const openEditModal = (row) => {
    isEditing.value = true;
    form.clearErrors();
    form.id = row.id;
    form.store_id = row.store_id || null;
    form.expense_type_id = row.expense_type_id; // Isi data tipe saat edit
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

                    <template #store_name="{ row }">
                        <span v-if="row.store" class="text-xs text-blue-700 font-medium px-2.5 py-0.5 rounded-full bg-blue-50 border border-blue-100">
                            {{ row.store.name }}
                        </span>
                        <span v-else class="text-xs text-gray-600 font-medium px-2.5 py-0.5 rounded-full bg-gray-100 border border-gray-200">
                            GLOBAL
                        </span>
                    </template>

                    <template #expense_type_name="{ row }">
                        <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-tighter">
                            {{ row.expense_type?.name || '-' }}
                        </span>
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

                    <template #user_name="{ row }">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-900">{{ row.pos_user?.name || 'Sistem' }}</span>
                            <span v-if="row.pos_user?.role" class="text-[10px] uppercase tracking-wider text-indigo-500 font-bold">
                                {{ row.pos_user.role }}
                            </span>
                        </div>
                    </template>

                    <template #actions="{ row }">
                        <div class="flex justify-end gap-2">
                            <button @click="openEditModal(row)" 
                                class="w-8 h-8 flex items-center justify-center bg-indigo-50 text-base rounded-lg hover:bg-indigo-100 transition-colors shadow-sm" 
                                title="Edit">
                                ✏️
                            </button>
                            <button @click="deleteExpense(row.id)" 
                                class="w-8 h-8 flex items-center justify-center bg-red-50 text-xs rounded-lg hover:bg-red-100 transition-colors shadow-sm" 
                                title="Hapus">
                                ❌
                            </button>
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
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100">
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
                                        v-model="form.expense_type_id"
                                        :options="expenseTypes"
                                        label="Jenis Pengeluaran"
                                        placeholder="Pilih kategori (Produk, Gaji, dll)..."
                                    />
                                    <p v-if="form.errors.expense_type_id" class="mt-1 text-xs text-red-600">{{ form.errors.expense_type_id }}</p>
                                </div>

                                <div>
                                    <SearchableSelect 
                                        v-model="form.store_id"
                                        :options="storeOptions"
                                        label="Lokasi Toko / Cabang"
                                        placeholder="Cari toko..."
                                        allow-clear
                                    />
                                    <p class="mt-1 text-[10px] text-gray-500 italic">* Pilih GLOBAL jika pengeluaran tidak spesifik ke satu cabang.</p>
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
                                    <input 
                                        v-model="form.amount" 
                                        type="number" 
                                        :class="{'border-red-500': form.errors.amount}" 
                                        class="w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                                        placeholder="0"
                                    >
                                    <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                                </div>

                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tanggal</label>
                                    <input 
                                        v-model="form.transaction_at" 
                                        type="date" 
                                        :class="{'border-red-500': form.errors.transaction_at}" 
                                        class="w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    >
                                    <p v-if="form.errors.transaction_at" class="mt-1 text-xs text-red-600">{{ form.errors.transaction_at }}</p>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Detail Keperluan</label>
                                <textarea 
                                    v-model="form.description" 
                                    rows="2" 
                                    :class="{'border-red-500': form.errors.description}" 
                                    class="w-full px-4 py-2 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                                    placeholder="Contoh: Belanja ATK Kantor..."
                                ></textarea>
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