<template>
  <div class="w-full max-w-md mx-auto bg-white rounded-2xl shadow-xl p-10 text-center">
    <div v-if="error" class="text-red-500">
      <h2 class="text-2xl font-bold mb-4">Error</h2>
      <p>{{ error }}</p>
      <button @click="$router.push({ name: 'Home' })" class="mt-6 bg-slate-200 text-slate-700 font-bold py-2 px-6 rounded-lg">
        Back to Home
      </button>
    </div>
    <div v-else>
      <h2 class="title-font text-4xl text-teal-600 mb-4 animate-pulse">
        {{ status }}
      </h2>
      <p class="text-slate-500">
        Finding a worthy opponent for you...
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../services/apiClient.js';
import echo from '../services/echo.js';

const router = useRouter();
const status = ref('Finding Match...');
const error = ref(null);
const gameId = ref(null);

let channel = null;

const navigateToGame = (gameData) => {
  router.push({ 
    name: 'Game', 
    state: { onlineGameData: gameData } 
  });
};

onMounted(async () => {
  try {
    const authToken = localStorage.getItem('authToken');
    const response = await apiClient.post('/matchmaking/join', {}, {
      headers: { Authorization: `Bearer ${authToken}` }
    });

    const game = response.data.game;
    gameId.value = game.id;
    status.value = response.data.message;

    if (game.status === 'active') {
        console.log("Player 2: Match found immediately via HTTP. Navigating...");
        navigateToGame(game);
        return;
    }

    console.log(`Player 1: Waiting for match, joining presence channel: game.${game.id}`);
    channel = echo.join(`game.${game.id}`);
    
    channel
      .here((users) => {
        console.log('Successfully joined channel. Users already here:', users);
      })
      .joining((user) => {
        console.log('A new player has joined:', user);
        status.value = 'Match Found!';
      })
      // --- Listen for the explicit event name ---
      .listen('.match.started', (event) => {
        console.log('SUCCESS! ".match.started" event received!', event);
        navigateToGame(event.game);
      })
      // For debugging, listen to all events on the channel
      .listenToAll((eventName, data) => {
          console.log('DEBUG: Received event:', eventName, 'with data:', data);
      })
      .error((error) => {
        console.error('Channel subscription error:', error);
        status.value = 'Error connecting to server.';
      });

  } catch (err) {
    console.error("Matchmaking failed:", err);
    error.value = err.response?.data?.message || "Could not connect to matchmaking.";
  }
});

onUnmounted(() => {
  if (channel) {
    echo.leave(`game.${gameId.value}`);
    console.log(`Left channel: game.${gameId.value}`);
  }
});
</script>

<style scoped>
.title-font {
  font-family: 'Pacifico', cursive;
}
</style>
