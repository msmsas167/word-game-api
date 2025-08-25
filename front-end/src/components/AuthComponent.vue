<template>
  <div class="w-full max-w-sm mx-auto bg-white rounded-2xl shadow-xl p-6 md:p-8">
    <header class="text-center mb-6">
      <h1 class="title-font text-4xl text-teal-600">Word Jumble</h1>
      <p class="text-slate-500 mt-2">{{ isLogin ? 'Log in to continue' : 'Create an account' }}</p>
    </header>
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <input
        v-if="!isLogin"
        type="text"
        v-model="form.name"
        placeholder="Name"
        required
        class="form-input"
      />
      <input type="email" v-model="form.email" placeholder="Email" required class="form-input" />
      <input
        type="password"
        v-model="form.password"
        placeholder="Password"
        required
        class="form-input"
      />
      <input
        v-if="!isLogin"
        type="password"
        v-model="form.password_confirmation"
        placeholder="Confirm Password"
        required
        class="form-input"
      />
      <p v-if="error" class="text-red-500 text-xs text-center">{{ error }}</p>
      <button
        type="submit"
        :disabled="isLoading"
        class="w-full py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 disabled:bg-slate-400"
      >
        {{ isLoading ? 'Loading...' : isLogin ? 'Log In' : 'Sign Up' }}
      </button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-500">
      {{ isLogin ? "Don't have an account?" : 'Already have an account?' }}
      <button @click="toggleForm" class="font-medium text-teal-600 hover:text-teal-500 ml-1">
        {{ isLogin ? 'Sign up' : 'Log in' }}
      </button>
    </p>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import apiClient from '../services/apiClient.js'

const router = useRouter()
const route = useRoute() // Used to access the current route's details (like query params)
const isLogin = ref(true)
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const error = ref('')
const isLoading = ref(false)

const toggleForm = () => {
  isLogin.value = !isLogin.value
  error.value = ''
  Object.keys(form).forEach((k) => (form[k] = ''))
}

const handleSubmit = async () => {
  error.value = ''
  isLoading.value = true
  try {
    const url = isLogin.value ? '/login' : '/register'
    const payload = isLogin.value ? { email: form.email, password: form.password } : { ...form }
    const response = await apiClient.post(url, payload)

    // Store the token and user data
    localStorage.setItem('authToken', response.data.access_token)
    localStorage.setItem('user', JSON.stringify(response.data.user))

    // Redirect to the intended page, or to the home page as a fallback
    const redirectPath = route.query.redirect || '/'
    router.push(redirectPath)
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'An unknown error occurred.'
    const validationErrors = err.response?.data?.errors
    error.value = validationErrors ? Object.values(validationErrors).flat().join(' ') : errorMsg
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.form-input {
  @apply w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500;
}
.title-font {
  font-family: 'Pacifico', cursive;
}
</style>
