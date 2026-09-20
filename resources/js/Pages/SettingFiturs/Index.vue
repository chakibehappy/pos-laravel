<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    admins: {
        type: Array,
        default: () => []
    },
    selectedAdminId: {
        type: [Number, String],
        default: null
    },
    initialModules: {
        type: [Array, Object],
        default: () => ({})
    }
});

// Ref untuk memantau admin yang sedang dipilih di dropdown
const selectedAdmin = ref(props.selectedAdminId || (props.admins[0]?.id ?? null));

// Pantau perubahan props.selectedAdminId dari backend agar dropdown & form selalu sinkron
watch(() => props.selectedAdminId, (newId) => {
    if (newId) {
        selectedAdmin.value = newId;
        form.user_id = newId;
    }
});

// Switch data ketika admin dipilih di dropdown
const changeAdmin = () => {
    router.get(
        route('setting-fiturs.index'), 
        { user_id: selectedAdmin.value }, 
        { preserveState: true, preserveScroll: true }
    );
};

// Structure awal modul beserta fiturnya
const modules = ref([
    {
        id: 'toko',
        title: 'TOKO',
        description: 'Pengaturan hak akses modul toko',
        icon: '🏪',
        iconBg: 'bg-[#FDC700]',
        isExpanded: true,
        subMenus: [
            {
                id: 'kas_toko',
                title: 'KAS',
                isExpanded: true,
                features: [
                    { id: 'edit_kas', name: 'Edit Kas', description: 'Admin dapat mengubah data kas', enabled: false },
                    { id: 'reset_kas', name: 'Reset Kas', description: 'Admin dapat mereset data kas', enabled: false },
                ]
            },
            {
                id: 'saldo_toko',
                title: 'SALDO',
                isExpanded: true,
                features: [
                    { id: 'edit_saldo', name: 'Edit Saldo', description: 'Admin dapat mengubah saldo', enabled: false },
                ]
            }
        ]
    },
    {
        id: 'master_produk',
        title: 'MASTER PRODUK',
        description: 'Pengaturan hak akses modul produk',
        icon: '📦',
        iconBg: 'bg-[#FDC700]',
        isExpanded: true,
        subMenus: [
            {
                id: 'produk',
                title: 'Produk',
                isExpanded: true,
                features: [
                    { id: 'edit_produk', name: 'Edit Produk', description: 'Admin dapat mengubah data produk', enabled: false },
                    { id: 'delete_produk', name: 'Hapus Produk', description: 'Admin dapat menghapus produk', enabled: false },
                ]
            }
        ]
    },
    {
        id: 'transaksi',
        title: 'TRANSAKSI',
        description: 'Pengaturan hak akses modul transaksi',
        icon: '🔄',
        iconBg: 'bg-[#FDC700]',
        isExpanded: false,
        subMenus: [
            {
                id: 'riwayat_transaksi',
                title: 'RIWAYAT TRANSAKSI',
                isExpanded: false,
                features: [
                    { id: 'detail_transaksi', name: 'Lihat Transaksi', description: 'Admin dapat melihat transaksi', enabled: false },
                    { id: 'add_transaksi', name: 'Buat Transaksi', description: 'Admin dapat membuat transaksi baru', enabled: false },
                    { id: 'edit_transaksi', name: 'Edit Transaksi', description: 'Admin dapat mengubah transaksi', enabled: false },
                    { id: 'delete_transaksi', name: 'Hapus Transaksi', description: 'Admin dapat menghapus transaksi', enabled: false },
                ]
            },
            {
                id: 'pengeluaran',
                title: 'PENGELUARAN',
                isExpanded: false,
                features: [
                    { id: 'add_pengeluaran', name: 'Buat Pengeluaran', description: 'Admin dapat membuat pengeluaran baru', enabled: false },
                    { id: 'edit_pengeluaran', name: 'Edit Pengeluaran', description: 'Admin dapat mengubah pengeluaran', enabled: false },
                    { id: 'delete_pengeluaran', name: 'Hapus Pengeluaran', description: 'Admin dapat menghapus pengeluaran', enabled: false },
                ]
            },
            {
                id: 'fee_topup',
                title: 'ATURAN BIAYA',
                isExpanded: false,
                features: [
                    { id: 'add_fee_topup', name: 'Buat Aturan Biaya', description: 'Admin dapat membuat aturan biaya baru', enabled: false },
                    { id: 'edit_fee_topup', name: 'Edit Aturan Biaya', description: 'Admin dapat mengubah aturan biaya', enabled: false },
                    { id: 'delete_fee_topup', name: 'Hapus Aturan Biaya', description: 'Admin dapat menghapus aturan biaya', enabled: false },
                ]
            },
            {
                id: 'fee_withdraw',
                title: 'ATURAN BIAYA PENARIKAN',
                isExpanded: false,
                features: [
                    { id: 'add_fee_withdraw', name: 'Buat Aturan Biaya Penarikan', description: 'Admin dapat membuat aturan biaya penarikan baru', enabled: false },
                    { id: 'edit_fee_withdraw', name: 'Edit Aturan Biaya Penarikan', description: 'Admin dapat mengubah aturan biaya penarikan', enabled: false },
                    { id: 'delete_fee_withdraw', name: 'Hapus Aturan Biaya Penarikan', description: 'Admin dapat menghapus aturan biaya penarikan', enabled: false },
                ]
            }
        ]
    },
]);

// Sinkronkan data `enabled` berdasarkan `initialModules` dari database
const syncPermissionsFromProps = () => {
    if (!props.initialModules || Object.keys(props.initialModules).length === 0) return;
    
    modules.value.forEach(mod => {
        mod.subMenus.forEach(sub => {
            sub.features.forEach(feature => {
                if (props.initialModules[feature.id] !== undefined) {
                    const val = props.initialModules[feature.id];
                    // Pengecekan eksplisit agar angka 0 atau "0" tidak dibaca sebagai true
                    feature.enabled = (val === 1 || val === "1" || val === true);
                }
            });
        });
    });
};

// Jalankan sinkronisasi saat pertama kali & saat props berubah
syncPermissionsFromProps();
watch(() => props.initialModules, () => {
    syncPermissionsFromProps();
}, { deep: true });

// Helper hitung total fitur
const getAllFeatures = (mod) => mod.subMenus.flatMap(sub => sub.features);

const totalFeatures = computed(() => {
    return modules.value.reduce((total, mod) => total + getAllFeatures(mod).length, 0);
});

const totalActiveFeatures = computed(() => {
    return modules.value.reduce((total, mod) => {
        return total + getAllFeatures(mod).filter(f => f.enabled).length;
    }, 0);
});

const totalLockedFeatures = computed(() => totalFeatures.value - totalActiveFeatures.value);
const totalModulesCount = computed(() => modules.value.length);

// Helper status per modul
const getActiveCountMod = (mod) => getAllFeatures(mod).filter(f => f.enabled).length;
const isModuleFullyActive = (mod) => {
    const feats = getAllFeatures(mod);
    return feats.length > 0 && feats.every(f => f.enabled);
};

// Helper status per sub-menu
const getActiveCountSub = (sub) => sub.features.filter(f => f.enabled).length;
const isSubMenuFullyActive = (sub) => sub.features.length > 0 && sub.features.every(f => f.enabled);

// Toggle sakelar per Modul
const toggleModule = (mod) => {
    const targetState = !isModuleFullyActive(mod);
    mod.subMenus.forEach(sub => {
        sub.features.forEach(f => f.enabled = targetState);
    });
};

// Toggle sakelar per Sub Menu
const toggleSubMenu = (sub) => {
    const targetState = !isSubMenuFullyActive(sub);
    sub.features.forEach(f => f.enabled = targetState);
};

// Expand / Collapse
const toggleExpandMod = (mod) => { mod.isExpanded = !mod.isExpanded; };
const toggleExpandSub = (sub) => { sub.isExpanded = !sub.isExpanded; };

// Simpan Pengaturan
const isSaving = ref(false);
const form = useForm({
    user_id: selectedAdmin.value,
    modules: {}
});

const saveSettings = () => {
    isSaving.value = true;
    
    // Ekstrak data permission ke format key-value object: { feature_id: 1 / 0 }
    const payloadModules = {};
    modules.value.forEach(mod => {
        mod.subMenus.forEach(sub => {
            sub.features.forEach(f => {
                payloadModules[f.id] = f.enabled ? 1 : 0;
            });
        });
    });

    form.user_id = selectedAdmin.value;
    form.modules = payloadModules;
    
    form.post(route('setting-fiturs.store'), {
        preserveScroll: true,
        onFinish: () => { isSaving.value = false; }
    });
};
</script>

<template>
    <Head title="Pengaturan Fitur" />

    <AuthenticatedLayout page-title="Pengaturan Fitur" page-subtitle="Atur fitur yang dapat digunakan oleh Admin.">
        
        <div class="p-8 max-w-[1400px] mx-auto font-sans text-gray-800">
            
            <!-- HEADER PAGE & TOMBOL SIMPAN -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Pengaturan Fitur</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Atur fitur yang dapat digunakan oleh Admin.</p>
                </div>

                <button 
                    @click="saveSettings" 
                    :disabled="isSaving"
                    class="bg-[#FDC700] hover:bg-yellow-500 active:bg-yellow-600 text-gray-900 font-bold text-xs tracking-wider uppercase px-5 py-3 rounded-xl shadow-md hover:shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 cursor-pointer"
                >
                    <span>{{ isSaving ? 'MENYIMPAN...' : 'SIMPAN PENGATURAN' }}</span>
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>

            <!-- CARD KONTROL AKSES GLOBAL -->
            <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm mb-6 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <div class="w-14 h-14 bg-[#FDC700] rounded-2xl flex items-center justify-center text-gray-900 text-2xl shadow-md flex-shrink-0">
                        ⚙️
                    </div>
                    <div>
                        <div class="flex items-center gap-8">
                            <div class="text-center px-2">
                                <div class="text-2xl font-black text-emerald-600 leading-none">{{ totalActiveFeatures }}</div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-1">FITUR AKTIF</div>
                            </div>
                            <div class="h-8 w-px bg-gray-200"></div>
                            <div class="text-center px-2">
                                <div class="text-2xl font-black text-red-500 leading-none">{{ totalLockedFeatures }}</div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-1">FITUR TERKUNCI</div>
                            </div>
                            <div class="h-8 w-px bg-gray-200"></div>
                            <div class="text-center px-2">
                                <div class="text-2xl font-black text-sky-600 leading-none">{{ totalModulesCount }}</div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-1">MODUL</div>
                            </div>
                        </div>
                    </div>
                </div>

                

                <!-- DROPDOWN DYNAMIC PILIH ADMIN -->
                <div class="flex flex-wrap items-center justify-between md:justify-end gap-8 w-full md:w-auto pt-4 md:pt-0 border-t md:border-t-0 border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="flex flex-col text-right">
                            <label for="admin-select" class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                                Pilih Akses Admin
                            </label>
                            <span class="text-xs text-gray-500">Filter hak akses fitur</span>
                        </div>
                        
                        <select 
                            id="admin-select"
                            v-model="selectedAdmin" 
                            @change="changeAdmin"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-[#FDC700] focus:border-[#FDC700] cursor-pointer"
                        >
                            <option v-if="admins.length === 0" value="" disabled>Tidak ada admin</option>
                            <option 
                                v-for="admin in admins" 
                                :key="admin.id" 
                                :value="admin.id"
                            >
                                {{ admin.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- DAFTAR MODUL FITUR -->
            <div class="space-y-4">
                <div 
                    v-for="mod in modules" 
                    :key="mod.id" 
                    class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden transition-all"
                >
                    <!-- HEADER MODUL UTAMA -->
                    <div class="p-5 flex items-center justify-between gap-4 select-none">
                        <div class="flex items-center gap-4 cursor-pointer flex-grow" @click="toggleExpandMod(mod)">
                            <div :class="[mod.iconBg, 'w-11 h-11 rounded-xl flex items-center justify-center text-gray-900 text-xl shadow-sm flex-shrink-0']">
                                <span>{{ mod.icon }}</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-gray-900 tracking-wide uppercase">{{ mod.title }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5">{{ mod.description }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2 cursor-pointer" @click="toggleExpandMod(mod)">
                                <span class="text-xs font-bold text-gray-700">
                                    {{ getActiveCountMod(mod) }} / {{ getAllFeatures(mod).length }} <span class="text-gray-400 font-normal">aktif</span>
                                </span>
                                <i :class="['fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200', mod.isExpanded ? 'rotate-180' : '']"></i>
                            </div>

                            <label class="relative inline-flex items-center cursor-pointer ml-2">
                                <input 
                                    type="checkbox" 
                                    :checked="isModuleFullyActive(mod)" 
                                    @change="toggleModule(mod)" 
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>
                    </div>

                    <!-- CONTAINER SUB-MENU -->
                    <div v-show="mod.isExpanded" class="border-t border-gray-100 bg-gray-50/50 p-4 space-y-3">
                        <div 
                            v-for="sub in mod.subMenus" 
                            :key="sub.id" 
                            class="bg-white rounded-xl border border-gray-200/80 overflow-hidden shadow-2xs"
                        >
                            <!-- HEADER SUB-MENU -->
                            <div class="p-3.5 px-5 bg-gray-100/50 flex items-center justify-between gap-4 select-none">
                                <div class="flex items-center gap-3 cursor-pointer flex-grow" @click="toggleExpandSub(sub)">
                                    <i :class="['fa-solid fa-chevron-right text-xs text-gray-400 transition-transform duration-200', sub.isExpanded ? 'rotate-90 text-amber-600' : '']"></i>
                                    <span class="text-xs font-extrabold text-gray-800 tracking-wider uppercase">{{ sub.title }}</span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="text-[11px] font-bold text-gray-500">
                                        {{ getActiveCountSub(sub) }} / {{ sub.features.length }} aktif
                                    </span>

                                    <!-- TOGGLE SUB-MENU -->
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            :checked="isSubMenuFullyActive(sub)" 
                                            @change="toggleSubMenu(sub)" 
                                            class="sr-only peer"
                                        >
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- DAFTAR FITUR SUB-MENU -->
                            <div v-show="sub.isExpanded" class="p-3 space-y-2 border-t border-gray-100 bg-white">
                                <div 
                                    v-for="feature in sub.features" 
                                    :key="feature.id"
                                    class="bg-gray-50/60 rounded-lg p-3 px-5 border border-gray-100 flex items-center justify-between hover:border-gray-200 transition-colors"
                                >
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 flex-grow pr-4">
                                        <div class="text-xs font-bold text-gray-800">{{ feature.name }}</div>
                                        <div class="text-xs text-gray-400">{{ feature.description }}</div>
                                    </div>

                                    <div class="flex items-center gap-4 flex-shrink-0">
                                        <span 
                                            :class="[
                                                'px-2.5 py-1 rounded-md text-[10px] font-black tracking-wider uppercase border',
                                                feature.enabled 
                                                    ? 'text-emerald-600 bg-emerald-50 border-emerald-200' 
                                                    : 'text-red-500 bg-red-50 border-red-200'
                                            ]"
                                        >
                                            {{ feature.enabled ? 'DIIZINKAN' : 'DIKUNCI' }}
                                        </span>

                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                v-model="feature.enabled" 
                                                class="sr-only peer"
                                            >
                                            <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER INFO BOX -->
            <div class="mt-6 bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4 flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-[#FDC700] text-gray-900 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fa-solid fa-lock text-sm"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-amber-900">Pengaturan Super Admin</h4>
                    <p class="text-xs text-amber-700/90 mt-0.5">
                        Pengaturan ini hanya dapat diubah oleh akun Super Admin. Menonaktifkan sebuah fitur akan membatasi akses Admin terhadap fitur tersebut.
                    </p>
                </div>
            </div>

        </div>

    </AuthenticatedLayout>
</template>