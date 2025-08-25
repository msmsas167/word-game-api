import { createRouter, createWebHistory } from 'vue-router';

// Import all the components that will be used as pages
import HomeComponent from '../components/HomeComponent.vue';
import GameComponent from '../components/GameComponent.vue';
import AuthComponent from '../components/AuthComponent.vue';
import ProfileComponent from '../components/ProfileComponent.vue';
import LeaderboardComponent from '../components/LeaderboardComponent.vue';
import MatchmakingComponent from '../components/Matchmaking.vue';

// Define the routes for your application
const routes = [
  {
    path: '/',
    name: 'Home',
    component: HomeComponent,
  },
  {
    path: '/game',
    name: 'Game',
    component: GameComponent,
    // This route is public for offline play
  },
  {
    path: '/auth',
    name: 'Auth',
    component: AuthComponent,
  },
  {
    path: '/matchmaking',
    name: 'Matchmaking',
    component: MatchmakingComponent,
    meta: { requiresAuth: true }, // Matchmaking requires a user to be logged in
  },
  {
    path: '/profile',
    name: 'Profile',
    component: ProfileComponent,
    meta: { requiresAuth: true }, // This meta field marks the route as protected
  },
  {
    path: '/leaderboard',
    name: 'Leaderboard',
    component: LeaderboardComponent,
    meta: { requiresAuth: true }, // This meta field also marks the route as protected
  },
];

// Create the router instance
const router = createRouter({
  history: createWebHistory(),
  routes, // short for `routes: routes`
});

// --- Navigation Guard ---
// This function runs before each navigation action.
router.beforeEach((to, from, next) => {
  const isLoggedIn = !!localStorage.getItem('authToken');

  // Check if the route requires authentication
  if (to.matched.some(record => record.meta.requiresAuth)) {
    // If the user is not logged in, redirect them to the authentication page
    if (!isLoggedIn) {
      next({ name: 'Auth' });
    } else {
      // If the user is logged in, allow them to proceed
      next();
    }
  } else {
    // For public routes, always allow navigation
    next();
  }
});

export default router;
