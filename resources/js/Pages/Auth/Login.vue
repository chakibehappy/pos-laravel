<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <form
      @submit.prevent="form.post('/login')"
      class="bg-white p-6 rounded shadow w-96"
    >
        <h1 class="text-xl font-bold mb-4">Dashboard Login</h1>

        <input
            v-model="form.email"
            type="email"
            placeholder="Email"
            class="w-full mb-3 border rounded p-2"
        />

        <input
            v-model="form.password"
            type="password"
            placeholder="Password"
            class="w-full mb-4 border rounded p-2"
        />

        <button
            class="w-full bg-blue-600 text-white py-2 rounded"
            :disabled="form.processing"
        >
            Login
        </button>

        <p v-if="form.errors.email" class="text-red-500 text-sm mt-2">
            {{ form.errors.email }}
        </p>
        
        <p v-if="form.errors.password" class="text-red-500 text-sm mt-2">
            {{ form.errors.password }}
        </p>
    </form>
  </div>
</template>
