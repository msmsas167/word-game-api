<!-- src/App.vue -->
<template>
  <div class="flex flex-col items-center justify-center min-h-screen mb-10">
    <!-- The router will display the correct component here -->
    <RouterView />

    <!-- Fixed Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 w-full bg-white/80 backdrop-blur-sm shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
      <div class="max-w-md mx-auto flex justify-around items-center h-16">
        <RouterLink :to="{ name: 'Profile' }" class="nav-button">
          <Icon name="user" class="w-6 h-6" />
          <span class="text-xs font-medium">Profile</span>
        </RouterLink>
        <RouterLink :to="{ name: 'Home' }" class="nav-button">
          <Icon name="home" class="w-6 h-6" />
          <span class="text-xs font-medium">Home</span>
        </RouterLink>
        <RouterLink :to="{ name: 'Leaderboard' }" class="nav-button">
          <Icon name="leaderboard" class="w-6 h-6" />
          <span class="text-xs font-medium">Results</span>
        </RouterLink>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { RouterLink, RouterView } from 'vue-router';
import Icon from './components/icons/Icon.vue';
</script>

<style scoped>
.nav-button {
  @apply flex flex-col items-center gap-1 transition text-slate-500 hover:text-teal-600;
}
.router-link-exact-active {
  @apply text-teal-600; /* This class is automatically added by Vue Router to the active link */
}
</style>