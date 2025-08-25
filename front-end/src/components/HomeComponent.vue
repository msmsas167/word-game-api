<template>
  <div>
    <div class="flex flex-col items-center justify-center text-center">
      <h1 class="title-font text-6xl md:text-8xl text-teal-600">Word Jumble</h1>
      <p class="text-slate-500 mt-4 mb-12">The fun and challenging word puzzle game.</p>
      <button
        @click="showGameModeModal = true"
        class="bg-teal-500 text-white font-bold py-4 px-10 rounded-full shadow-lg hover:bg-teal-600 transition-transform transform hover:scale-105"
      >
        Start Game
      </button>
    </div>

    <Modal :show="showGameModeModal" title="Select Mode" @close="showGameModeModal = false">
      <div class="space-y-4 mt-6">
        <button
          @click="startOfflineGame"
          class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-3 px-4 rounded-lg"
        >
          Offline Game
        </button>
        <button
          @click="startOnlineGame"
          class="w-full bg-teal-500 hover:bg-teal-600 text-white font-bold py-3 px-4 rounded-lg"
        >
          Online Game
        </button>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Modal from './Modal.vue'

const router = useRouter()
const showGameModeModal = ref(false)

const startOfflineGame = () => {
  showGameModeModal.value = false
  router.push({ name: 'Game' })
}

const startOnlineGame = () => {
  showGameModeModal.value = false
  const isLoggedIn = !!localStorage.getItem('authToken')
  if (isLoggedIn) {
    router.push({ name: 'Matchmaking' });
  } else {
    // If not logged in, go to Auth and redirect to matchmaking after
    router.push({ name: 'Auth', query: { redirect: '/matchmaking' } });
  }
}
</script>

<style scoped>
.title-font {
  font-family: 'Pacifico', cursive;
}
</style>
