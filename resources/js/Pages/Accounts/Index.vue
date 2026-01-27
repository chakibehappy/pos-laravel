<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Props dari AccountController
const props = defineProps({
    accounts: Object,
    errors: Object,
});

const form = useForm({
    id: null,
    company_name: '',
    status: true,
});

const submit = () => {
    form.post(route('accounts.store'), {
        onSuccess: () => resetForm(),
    });
};

const edit = (account) => {
    form.clearErrors();
    form.id = account.id;
    form.company_name = account.company_name;
    form.status = !!account.status;
};

const destroy = (id) => {
    if (confirm('Are you sure?')) {
        form.delete(route('accounts.destroy', id));
    }
};

const resetForm = () => {
    form.reset();
    form.id = null;
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6">
            <h1 class="text-2xl font-black uppercase mb-6 italic tracking-tighter">Manage Accounts</h1>

            <div class="mb-8 p-6 border-2 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                <h2 class="font-bold uppercase mb-4 text-sm">{{ form.id ? 'Update Account' : 'Register New Company' }}</h2>
                
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block font-black text-xs uppercase mb-1">Company Name</label>
                            <input v-model="form.company_name" type="text" 
                                class="w-full border-2 border-black p-2 font-bold focus:bg-yellow-50 outline-none" 
                                placeholder="e.g. MAARS CORP">
                            <div v-if="errors.company_name" class="text-red-500 text-xs font-bold mt-1 uppercase">{{ errors.company_name }}</div>
                        </div>

                        <div>
                            <label class="block font-black text-xs uppercase mb-1">Status</label>
                            <select v-model="form.status" 
                                class="w-full border-2 border-black p-2 font-bold bg-white focus:bg-yellow-50 outline-none">
                                <option :value="true">ACTIVE</option>
                                <option :value="false">INACTIVE</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-x-3">
                        <button type="submit" 
                            class="bg-black text-white px-8 py-2 font-bold uppercase border-2 border-black hover:bg-gray-800 transition-all">
                            {{ form.id ? 'Update' : 'Save Account' }}
                        </button>
                        <button v-if="form.id" @click="resetForm" type="button" 
                            class="border-2 border-black px-8 py-2 font-bold uppercase hover:bg-gray-100">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <div class="border-2 border-black bg-white shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-black text-white">
                        <tr>
                            <th class="p-3 uppercase font-black text-sm">Company Name</th>
                            <th class="p-3 uppercase font-black text-sm text-center">Status</th>
                            <th class="p-3 uppercase font-black text-sm text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="account in accounts.data" :key="account.id" class="border-b-2 border-black hover:bg-gray-50">
                            <td class="p-3 font-bold uppercase">{{ account.company_name }}</td>
                            <td class="p-3 text-center">
                                <span :class="account.status ? 'bg-green-400' : 'bg-red-400'" 
                                    class="border-2 border-black px-2 py-1 text-[10px] font-black uppercase">
                                    {{ account.status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-black uppercase text-xs">
                                <button @click="edit(account)" class="underline mr-4 hover:text-blue-600">Edit</button>
                                <button @click="destroy(account.id)" class="underline text-red-500 hover:text-red-700">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>