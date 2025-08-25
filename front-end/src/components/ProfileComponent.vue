<template>
  <div class="w-full max-w-md mx-auto bg-white rounded-2xl shadow-xl p-6 md:p-8">
    <header class="text-center mb-6">
      <h1 class="title-font text-4xl text-teal-600">Your Profile</h1>
      <p class="text-slate-500 mt-2">Edit your information below.</p>
    </header>
    <form v-if="user" @submit.prevent="handleSaveProfile" class="space-y-6">
      <div>
        <label for="email" class="block text-sm font-medium text-slate-700">Email Address</label>
        <input
          type="email"
          id="email"
          :value="user.email"
          disabled
          class="form-input bg-slate-100 cursor-not-allowed"
        />
      </div>
      <div>
        <label for="name" class="block text-sm font-medium text-slate-700">Display Name</label>
        <input type="text" id="name" v-model="name" class="form-input" placeholder="Your Name" />
      </div>
      <p v-if="message" class="text-center text-sm text-green-600">{{ message }}</p>
      <button
        type="submit"
        :disabled="isLoading"
        class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 disabled:bg-slate-400"
      >
        <Icon name="save" /> {{ isLoading ? 'Saving...' : 'Save Changes' }}
      </button>
      <button
        @click="handleLogout"
        type="button"
        class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-600 bg-white hover:bg-red-50"
      >
        <Icon name="logout" /> Logout
      </button>
    </form>
  </div>
</template>
  
  <script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../services/apiClient.js'
import Icon from './icons/Icon.vue'

const router = useRouter()
const user = ref(null)
const name = ref('')
const message = ref('')
const isLoading = ref(false)

onMounted(() => {
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    user.value = JSON.parse(storedUser)
    name.value = user.value.name
  }
})

const handleSaveProfile = async () => {
  isLoading.value = true
  message.value = ''
  try {
    const authToken = localStorage.getItem('authToken')
    const response = await apiClient.put(
      '/user/profile',
      { name: name.value },
      {
        headers: { Authorization: `Bearer ${authToken}` },
      }
    )
    localStorage.setItem('user', JSON.stringify(response.data.user))
    user.value = response.data.user
    message.value = 'Profile saved successfully!'
  } catch (error) {
    message.value = `Error: ${error.response?.data?.message || 'Could not save profile.'}`
  } finally {
    isLoading.value = false
    setTimeout(() => (message.value = ''), 3000)
  }
}

const handleLogout = async () => {
  try {
    const authToken = localStorage.getItem('authToken')
    await apiClient.post('/logout', {}, { headers: { Authorization: `Bearer ${authToken}` } })
  } finally {
    localStorage.removeItem('authToken')
    localStorage.removeItem('user')
    router.push({ name: 'Home' })
  }
}
</script>
  
  <style scoped>
.form-input {
  @apply mt-1 w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-teal-500 focus:border-teal-500;
}
.title-font {
  font-family: 'Pacifico', cursive;
}
</style>
  