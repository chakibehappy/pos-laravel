<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    modelValue: [String, Number],
    options: Array,
    label: String,
    placeholder: String
});

const emit = defineEmits(['update:modelValue']);

const search = ref('');
const isOpen = ref(false);
const container = ref(null);

// Fungsi untuk mencari nama berdasarkan ID
const getSelectedName = (id) => {
    const selected = props.options.find(opt => opt.id === id);
    return selected ? selected.name : '';
};

// SINKRONISASI: Jika modelValue (ID) berubah dari luar (misal saat klik Edit)
watch(() => props.modelValue, (newId) => {
    const targetName = getSelectedName(newId);
    // Hanya update jika teks saat ini berbeda, agar tidak mengganggu ketikan user
    if (search.value !== targetName && !isOpen.value) {
        search.value = targetName;
    }
}, { immediate: true });

// Logika Pencarian
const filteredOptions = computed(() => {
    const query = search.value.toLowerCase().trim();
    if (!query) return props.options; // Tampilkan semua jika input kosong
    
    return props.options.filter(opt => 
        opt.name.toLowerCase().includes(query)
    );
});

// Handler saat user mengetik atau menghapus
const onInput = () => {
    isOpen.value = true;
    // Jika user menghapus semua teks, kosongkan value di parent
    if (search.value === '') {
        emit('update:modelValue', '');
    }
};

const selectOption = (opt) => {
    search.value = opt.name;
    emit('update:modelValue', opt.id);
    isOpen.value = false;
};

// Logika menutup dropdown saat klik di luar
const handleClickOutside = (e) => {
    if (container.value && !container.value.contains(e.target)) {
        isOpen.value = false;
        // Kembalikan teks ke nama asli jika user tidak memilih apapun dari list
        search.value = getSelectedName(props.modelValue);
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div class="flex flex-col gap-1 relative" ref="container">
        <label v-if="label" class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
            {{ label }}
        </label>
        
        <div class="relative group">
            <input 
                type="text" 
                v-model="search" 
                @focus="isOpen = true"
                @input="onInput"
                :placeholder="placeholder"
                class="w-full border border-gray-300 rounded-lg p-2 text-sm bg-white outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm"
            />
            
            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400 text-[10px]">
                {{ isOpen ? '▲' : '▼' }}
            </div>
        </div>
        
        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div v-if="isOpen" class="absolute z-[100] top-full left-0 w-full bg-white border border-gray-200 rounded-lg shadow-xl max-h-56 overflow-y-auto mt-1 py-1">
                <div 
                    v-for="opt in filteredOptions" :key="opt.id"
                    @click="selectOption(opt)"
                    class="px-4 py-2 text-sm hover:bg-blue-600 hover:text-white cursor-pointer border-b border-gray-50 last:border-0 transition-colors flex items-center justify-between"
                    :class="{'bg-blue-50 text-blue-700': opt.id === modelValue}"
                >
                    <span>{{ opt.name }}</span>
                    <span v-if="opt.id === modelValue" class="text-blue-600 font-bold">✓</span>
                </div>
                
                <div v-if="filteredOptions.length === 0" class="px-4 py-3 text-xs text-gray-400 italic text-center font-medium">
                    Data tidak ditemukan
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
::-webkit-scrollbar {
    width: 4px;
}
::-webkit-scrollbar-track {
    background: #f1f1f1;
}
::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #888;
}
</style>