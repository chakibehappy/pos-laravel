<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3'; // Tambahkan usePage
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    withdrawals: Array,
    stores: Array,
    storeTypes: Array
});

// Ambil error dari session (Flash Messages)
const page = usePage();
const errorMessage = computed(() => page.props.errors.error);

const storesWithTypeOne = computed(() => {
    return props.stores.filter(s => s.store_type_id == 1);
});

const form = useForm({
    id: null,
    store_id: '',
    customer_name: '',
    withdrawal_source_id: '',
    withdrawal_count: 0,
    admin_fee: 0,
});

const editMode = ref(false);

const submit = () => {
    if (editMode.value) {
        form.patch(route('cash-withdrawals.update', form.id), {
            onSuccess: () => {
                form.reset();
                editMode.value = false;
            }
        });
    } else {
        form.post(route('cash-withdrawals.store'), {
            onSuccess: () => form.reset(),
            // Jika gagal karena saldo kurang, form tidak akan reset sehingga user bisa koreksi
        });
    }
};

const editData = (data) => {
    editMode.value = true;
    form.id = data.id;
    form.store_id = data.store_id;
    form.customer_name = data.customer_name;
    form.withdrawal_source_id = data.withdrawal_source_id;
    form.withdrawal_count = data.withdrawal_count;
    form.admin_fee = data.admin_fee;
};

const deleteData = (id) => {
    if (confirm('Batalkan transaksi ini? Saldo kas toko akan dikembalikan.')) {
        router.delete(route('cash-withdrawals.destroy', id));
    }
};

const formatIDR = (num) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);

const getSourceName = (id) => {
    const sources = { 1: 'DANA', 2: 'OVO', 3: 'GOPAY', 4: 'LINKAJA', 5: 'SHOPEEPAY', 6: 'LAINNYA' };
    return sources[id] || 'UNKNOWN';
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter">Transaksi Tarik Tunai</h1>
                <p class="text-[10px] font-black uppercase text-gray-400 italic">Manajemen Saldo Kas Fisik</p>
            </div>

            <transition name="fade">
                <div v-if="errorMessage" class="bg-red-500 text-white border-4 border-black p-3 font-black uppercase italic text-xs animate-bounce">
                    ⚠️ {{ errorMessage }}
                </div>
            </transition>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div class="border-4 border-black p-6 bg-white relative">
                <div v-if="form.processing" class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center font-black italic uppercase">Memproses...</div>

                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-black uppercase italic decoration-blue-500 decoration-4 text-sm">
                        {{ editMode ? 'Mode Koreksi Transaksi' : 'Input Tarik Tunai' }}
                    </h3>
                    <button v-if="editMode" @click="editMode = false; form.reset()" class="text-[10px] font-black uppercase text-red-600 ">Batal</button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div v-if="form.errors.store_id" class="text-[9px] text-red-600 font-bold uppercase italic italic">Toko wajib dipilih</div>
                    
                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1">Unit Toko</label>
                        <select v-model="form.store_id" class="w-full border-4 border-black p-2 font-bold outline-none text-sm focus:bg-yellow-50">
                            <option value="" disabled>-- Pilih Toko --</option>
                            <option v-for="s in storesWithTypeOne" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1">Nama Pelanggan</label>
                        <input v-model="form.customer_name" type="text" class="w-full border-4 border-black p-2 font-bold outline-none text-sm" placeholder="Input nama..." />
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1">Sumber Dana</label>
                        <select v-model="form.withdrawal_source_id" class="w-full border-4 border-black p-2 font-bold outline-none text-sm">
                            <option value="" disabled>-- Pilih Sumber --</option>
                            <option value="1">DANA</option>
                            <option value="2">OVO</option>
                            <option value="3">GOPAY</option>
                            <option value="4">LINKAJA</option>
                            <option value="5">SHOPEEPAY</option>
                            <option value="6">LAINNYA</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-1 text-red-500">Jumlah Tarik</label>
                            <input v-model="form.withdrawal_count" type="number" class="w-full border-4 border-black p-2 font-black outline-none" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase mb-1 text-green-600">Fee Admin</label>
                            <input v-model="form.admin_fee" type="number" class="w-full border-4 border-black p-2 font-black outline-none" />
                        </div>
                    </div>

                    <button :disabled="form.processing" class="w-full bg-black text-white py-4 font-black uppercase border-2 border-black hover:bg-blue-600 transition-all active:translate-y-1 disabled:bg-gray-400">
                        {{ editMode ? 'Simpan Perubahan' : 'Simpan Transaksi' }}
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 border-4 border-black bg-white overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-black text-white">
                        <tr>
                            <th class="p-3 uppercase font-black border-r border-gray-800 italic">#</th>
                            <th class="p-3 uppercase font-black italic text-[10px]">Pelanggan & Unit</th>
                            <th class="p-3 uppercase font-black italic text-right text-[10px]">Nominal</th>
                            <th class="p-3 uppercase font-black italic text-center text-[10px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-4 divide-black">
                        <tr v-for="(w, index) in withdrawals" :key="w.id" class="hover:bg-blue-50">
                            <td class="p-3 border-r-4 border-black text-center font-black">#{{ index + 1 }}</td>
                            <td class="p-3">
                                <div class="font-black uppercase text-blue-600 tracking-tighter italic">{{ w.customer_name }}</div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase italic">{{ w.store?.name }} | {{ getSourceName(w.withdrawal_source_id) }}</div>
                            </td>
                            <td class="p-3 text-right">
                                <div class="font-black text-red-600">{{ formatIDR(w.withdrawal_count) }}</div>
                                <div class="text-[9px] font-bold text-green-600">Fee: {{ formatIDR(w.admin_fee) }}</div>
                            </td>
                            <td class="p-3 text-center space-x-3">
                                <button @click="editData(w)" class="text-[18px] active:scale-90 transition-transform">✏️</button>
                                <button @click="deleteData(w.id)" class="text-[18px] active:scale-90 transition-transform">❌</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.5s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>