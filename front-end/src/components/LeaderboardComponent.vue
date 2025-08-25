<template>
  <div class="w-full max-w-md mx-auto">
    <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
      <header class="text-center mb-6">
        <h1 class="title-font text-4xl text-teal-600">Leaderboard</h1>
        <p class="text-slate-500 mt-2">Top 50 Players</p>
      </header>

      <!-- Loading State -->
      <div v-if="isLoading" class="text-center text-slate-500 animate-pulse">
        Loading leaderboard...
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center text-red-500">
        {{ error }}
      </div>

      <!-- Leaderboard Content -->
      <div v-else>
        <!-- Current Player's Rank -->
        <div v-if="currentUser" class="mb-6 p-4 bg-teal-50 border-2 border-teal-200 rounded-xl flex items-center justify-between">
          <div class="flex items-center gap-4">
            <span class="font-bold text-lg text-teal-600">#{{ currentUser.rank }}</span>
            <span class="font-semibold text-teal-800">{{ currentUser.name }} (You)</span>
          </div>
          <span class="font-bold text-lg text-teal-600">{{ currentUser.points }} pts</span>
        </div>

        <!-- Top Players List -->
        <div class="space-y-2">
          <div v-for="(player, index) in topPlayers" :key="player.id" class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
            <div class="flex items-center gap-3">
              <span class="font-bold text-slate-500 w-6 text-center">{{ index + 1 }}.</span>
              <span class="font-medium text-slate-700">{{ player.name }}</span>
            </div>
            <span class="font-semibold text-slate-600">{{ player.points }} pts</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import apiClient from '../services/apiClient.js';

const isLoading = ref(true);
const error = ref(null);
const topPlayers = ref([]);
const currentUser = ref(null);

onMounted(async () => {
  try {
    const authToken = localStorage.getItem('authToken');
    const response = await apiClient.get('/leaderboard', {
      headers: { Authorization: `Bearer ${authToken}` }
    });
    topPlayers.value = response.data.top_players;
    currentUser.value = response.data.current_user;
  } catch (err) {
    console.error("Failed to fetch leaderboard:", err);
    error.value = "Could not load the leaderboard. Please try again later.";
  } finally {
    isLoading.value = false;
  }
});
</script>

<style scoped>
.title-font {
  font-family: 'Pacifico', cursive;
}
</style>
