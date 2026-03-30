<script setup>
import { useForm } from '@inertiajs/vue3'
import '../../../css/login-style.css'

const backgroundUrl = '/images/login.jpg';

const form = useForm({
  email: '',
  password: '',
})

// Class Tailwind hanya untuk dekorasi warna & shadow saja
const inputStyle = "w-full pl-16 pr-4 py-4 bg-[#eff4ff] border-none rounded-2xl text-gray-700 focus:ring-2 focus:ring-yellow-400 outline-none transition-all";
const iconBoxStyle = "bg-[#ffc107] p-2 rounded-lg";
</script>

<template>
  <div class="page-wrapper" :style="{ backgroundImage: `url('${backgroundUrl}')` }">
    <div class="overlay"></div>

    <div class="content-container">
        <div class="logo-wrapper">
            <img src="/storage/img/maarlogo.png" alt="MAAR COMPANY" class="logo-img" />
            <span class="logo-text">MAAR COMPANY</span>
        </div>

        <div class="login-card">
          <h1 class="card-title">Dashboard Login</h1>

          <form @submit.prevent="form.post(route('login'))">
            <div class="input-group">
              <div class="input-wrapper">
                <div class="icon-container">
                  <div :class="iconBoxStyle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                      <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                  </div>
                </div>
                <input v-model="form.email" type="email" placeholder="Email" :class="inputStyle" />
              </div>
              <p v-if="form.errors.email" class="error-message">{{ form.errors.email }}</p>
            </div>

            <div class="input-group last-input">
              <div class="input-wrapper">
                <div class="icon-container">
                  <div :class="iconBoxStyle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
                <input v-model="form.password" type="password" placeholder="Kata Sandi" :class="inputStyle" />
              </div>
              <p v-if="form.errors.password" class="error-message">{{ form.errors.password }}</p>
            </div>

            <button type="submit" class="login-button" :disabled="form.processing">
              LOGIN
            </button>

            <div class="forgot-password">
              <a href="#">Lupa Password?</a>
            </div>
          </form>
        </div>

        <p class="footer-text">© 2024 MAAR COMPANY</p>
    </div>
  </div>
</template>
