<script setup>
import { ref, watch } from 'vue';
import { useForm, Head, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    data: Object, // Diubah menjadi Object untuk mendukung pagination (.data, .links)
    filters: Object
});

const search = ref(props.filters.search || '');
const editMode = ref(false);

// Logic Pencarian (Server-side) dengan Debounce 500ms
watch(search, debounce((value) => {
    router.get(
        route('withdrawal-source-types.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 500));

// Logic Form
const form = useForm({
    id: null,
    name: '',
});

const submit = () => {
    if (editMode.value) {
        form.put(route('withdrawal-source-types.update', form.id), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('withdrawal-source-types.store'), {
            onSuccess: () => resetForm(),
        });
    }
};

const editData = (item) => {
    editMode.value = true;
    form.id = item.id;
    form.name = item.name;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const deleteData = (id) => {
    if (confirm('Hapus sumber penarikan ini?')) {
        router.delete(route('withdrawal-source-types.destroy', id));
    }
};

const resetForm = () => {
    form.reset();
    editMode.value = false;
};
</script>

<template>
    <Head title="Master Sumber Penarikan" />

    <AuthenticatedLayout>
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h1 class="text-3xl font-black uppercase italic tracking-tighter text-black">Master Sumber Penarikan</h1>
                <p class="text-[10px] font-black uppercase text-gray-400 italic">Pengaturan Kategori Penarikan (withdrawal_source_type)</p>
            </div>

            <div class="w-full md:w-64">
                <div class="border-4 border-black p-1 bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] flex items-center">
                    <span class="px-2">🔍</span>
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="CARI KATEGORI..." 
                        class="w-full border-none text-[10px] font-black uppercase outline-none focus:ring-0"
                    />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div class="border-4 border-black p-6 bg-white shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] relative">
                <div v-if="form.processing" class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center font-black italic uppercase">Memproses...</div>

                <div class="flex justify-between items-center mb-6 border-b-4 border-black pb-2">
                    <h3 class="font-black uppercase italic text-black">
                        {{ editMode ? 'Edit Sumber' : 'Tambah Baru' }}
                    </h3>
                    <button v-if="editMode" @click="resetForm" class="text-[10px] font-black uppercase text-red-600 underline">Batal</button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase mb-1 text-black text-xs">Nama Sumber Dana</label>
                        <input 
                            v-model="form.name" 
                            type="text" 
                            placeholder="CONTOH: KAS TOKO / DANA" 
                            class="w-full border-4 border-black p-2 font-black outline-none focus:bg-yellow-50 text-sm text-black uppercase" 
                            required 
                        />
                        <p v-if="form.errors.name" class="text-[9px] text-red-600 font-bold uppercase italic mt-1">{{ form.errors.name }}</p>
                    </div>

                    <button 
                        :disabled="form.processing" 
                        class="w-full bg-black text-white py-4 font-black uppercase border-2 border-black hover:bg-blue-600 transition-all shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] active:translate-y-1 active:shadow-none disabled:bg-gray-400"
                    >
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
                                    <th class="p-4 uppercase text-[10px] font-black italic border-r border-gray-800 w-16 text-center">#</th>
                                    <th class="p-4 uppercase text-[10px] font-black italic">Nama Sumber Penarikan</th>
                                    <th class="p-4 uppercase text-[10px] font-black italic text-center w-32 border-l border-gray-800">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-4 divide-black text-sm text-black">
                                <tr v-for="(item, index) in data.data" :key="item.id" class="hover:bg-blue-50 transition-colors">
                                    <td class="p-4 border-r-4 border-black text-center font-black italic bg-gray-50">
                                        {{ (data.current_page - 1) * data.per_page + index + 1 }}
                                    </td>
                                    <td class="p-4">
                                        <div class="font-black uppercase tracking-widest text-sm italic">{{ item.name }}</div>
                                        <div class="text-[8px] font-bold text-gray-400 uppercase tracking-widest leading-none mt-1">ID_SOURCE: #WS-TYPE-{{ item.id }}</div>
                                    </td>
                                    <td class="p-4 text-center border-l-4 border-black">
                                        <div class="flex justify-center gap-4">
                                            <button @click="editData(item)" class="text-xl hover:scale-125 transition-transform" title="Edit">✏️</button>
                                            <button @click="deleteData(item.id)" class="text-xl hover:scale-125 transition-transform" title="Hapus">❌</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="data.data.length === 0">
                                    <td colspan="3" class="p-12 text-center font-black uppercase italic text-gray-300 tracking-widest">
                                        Data tidak ditemukan
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap justify-center gap-2">
                    <template v-for="(link, k) in data.links" :key="k">
                        <a 
                            v-if="link.url" 
                            :href="link.url" 
                            class="px-4 py-2 border-2 border-black font-black uppercase text-[10px] transition-all shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] hover:translate-x-[1px] hover:translate-y-[1px] hover:shadow-none"
                            :class="{'bg-yellow-400': link.active, 'bg-white': !link.active}"
                        >
                        {{ link.label }}
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>