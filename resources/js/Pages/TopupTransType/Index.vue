<script setup>
import { ref, watch } from 'vue';
import { useForm, Head, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    data: Object, // Diubah menjadi Object untuk mendukung pagination (.data, .links)
    filters: Object
});

// State untuk Search
const search = ref(props.filters.search || '');
const editMode = ref(false);

// Logic Pencarian ke Server (Debounce 500ms)
watch(search, debounce((value) => {
    router.get(
        route('topup-trans-types.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 500));

// Logic Form
const form = useForm({
    id: null,
    name: '',
    type: 'digital', // Default value
});

const submit = () => {
    if (editMode.value) {
        form.put(route('topup-trans-types.update', form.id), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('topup-trans-types.store'), {
            onSuccess: () => resetForm(),
        });
    }
};

const editData = (item) => {
    editMode.value = true;
    form.id = item.id;
    form.name = item.name;
    form.type = item.type;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const deleteData = (id) => {
    if (confirm('Hapus tipe transaksi ini? Data yang sudah terhubung mungkin akan terdampak.')) {
        router.delete(route('topup-trans-types.destroy', id));
    }
};

const resetForm = () => {
    form.reset();
    editMode.value = false;
};
</script>

<template>
    <Head title="Master Tipe Transaksi" />

    <AuthenticatedLayout>
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter text-black">Master Tipe Transaksi</h1>
                <p class="text-[10px] font-black uppercase text-gray-400 italic">Pengaturan Layanan (Digital & Bill)</p>
            </div>

            <div class="w-full md:w-64">
                <div class="border-4 border-black p-1 bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] flex items-center">
                    <span class="px-2">🔍</span>
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="CARI LAYANAN..." 
                        class="w-full border-none text-[10px] font-black uppercase outline-none focus:ring-0"
                    />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div class="border-4 border-black p-6 bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] relative">
                <div class="flex justify-between items-center mb-6 border-b-4 border-black pb-2">
                    <h3 class="font-black uppercase italic text-black">
                        {{ editMode ? 'Edit Layanan' : 'Tambah Baru' }}
                    </h3>
                    <button v-if="editMode" @click="resetForm" class="text-[10px] font-black uppercase text-red-600 underline">Batal</button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 text-black">Nama Layanan</label>
                        <input v-model="form.name" type="text" placeholder="Contoh: Transfer Dana" 
                            class="w-full border-4 border-black p-2 font-black outline-none focus:bg-yellow-50 text-sm text-black uppercase" required />
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 text-black">Kategori (Type)</label>
                        <select v-model="form.type" class="w-full border-4 border-black p-2 font-black outline-none focus:bg-yellow-50 text-sm text-black uppercase">
                            <option value="digital">DIGITAL (E-Wallet/Pulsa)</option>
                            <option value="bill">BILL (Tagihan/Listrik)</option>
                        </select>
                    </div>

                    <button :disabled="form.processing" 
                        class="w-full bg-black text-white py-4 font-black uppercase border-2 border-black hover:bg-yellow-400 hover:text-black transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:translate-y-1 active:shadow-none disabled:bg-gray-400">
                        {{ editMode ? 'Update Data' : 'Simpan Master' }}
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2">
                <div class="border-4 border-black bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-black text-white">
                                <tr>
                                    <th class="p-3 uppercase text-[10px] font-black italic border-r border-gray-800 w-12 text-center">#</th>
                                    <th class="p-3 uppercase text-[10px] font-black italic">Nama Layanan</th>
                                    <th class="p-3 uppercase text-[10px] font-black italic text-center border-l border-gray-800 w-32">Type</th>
                                    <th class="p-3 uppercase text-[10px] font-black italic text-center border-l border-gray-800 w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-4 divide-black text-sm">
                                <tr v-for="(item, index) in data.data" :key="item.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="p-3 border-r-4 border-black text-center font-black italic text-black bg-gray-50">
                                        {{ (data.current_page - 1) * data.per_page + index + 1 }}
                                    </td>
                                    <td class="p-3">
                                        <div class="font-black uppercase tracking-tight text-sm text-black italic">{{ item.name }}</div>
                                        <div class="text-[8px] font-bold text-gray-400 uppercase tracking-widest leading-none">REF: #TYPE-{{ item.id }}</div>
                                    </td>
                                    <td class="p-3 text-center border-l-4 border-black">
                                        <span :class="item.type === 'digital' ? 'bg-blue-100 text-blue-600 border-blue-600' : 'bg-purple-100 text-purple-600 border-purple-600'" 
                                            class="px-3 py-1 text-[9px] font-black uppercase italic border-2 shadow-[2px_2px_0px_0px_currentColor]">
                                            {{ item.type }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center border-l-4 border-black">
                                        <div class="flex justify-center gap-4">
                                            <button @click="editData(item)" class="text-xl hover:scale-125 transition-transform">✏️</button>
                                            <button @click="deleteData(item.id)" class="text-xl hover:scale-125 transition-transform">❌</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="data.data.length === 0">
                                    <td colspan="4" class="p-10 text-center font-black uppercase italic text-gray-300 tracking-widest leading-loose">
                                        Data layanan tidak ditemukan
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap justify-center gap-2">
                    <template v-for="(link, k) in data.links" :key="k">
                        <Link 
                            v-if="link.url" 
                            :href="link.url" 
                            v-html="link.label"
                            class="px-4 py-2 border-2 border-black font-black uppercase text-[10px] transition-all shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-0.5 hover:translate-y-0.5"
                            :class="{'bg-yellow-400 text-black': link.active, 'bg-white text-black': !link.active}"
                        />
                        <span 
                            v-else 
                            v-html="link.label" 
                            class="px-4 py-2 border-2 border-gray-200 text-gray-400 font-black uppercase text-[10px] italic bg-gray-50"
                        ></span>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>