<template>
  <div class="w-full max-w-md mx-auto">
    <!-- Loading State -->
    <div v-if="isLoading" class="text-center p-10 bg-white rounded-2xl shadow-xl">
      <p class="text-slate-500 animate-pulse">Loading New Game...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center p-10 bg-white rounded-2xl shadow-xl">
      <p class="text-red-500 mb-4">{{ error }}</p>
      <button @click="initGame" class="bg-teal-500 text-white font-bold py-2 px-6 rounded-lg">
        Try Again
      </button>
    </div>

    <!-- Main Game Content -->
    <div v-else class="bg-slate-100">
      <div v-if="state.foundWords.length > 0" class="mb-4 p-4 bg-white rounded-2xl shadow-xl">
        <h3 class="text-sm font-bold text-slate-800 mb-3 text-center">Words Found</h3>
        <div class="flex flex-wrap justify-center gap-2">
          <span v-for="word in state.foundWords" :key="word" class="found-word-badge">
            {{ word }}
          </span>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8">
        <header class="text-center mb-4 relative">
          <h1 class="title-font text-4xl text-teal-600">Word Jumble</h1>
          <p class="text-slate-500 mt-2 font-semibold capitalize">Theme: {{ gameConfig.theme }}</p>
        </header>

        <div class="mb-6 space-y-2 text-center">
          <p v-for="len in sortedGoalKeys" :key="len" class="text-slate-600 font-medium">
            <span class="font-bold">{{ len }}-Letter Words:</span>
            {{ state.goals[len].found }} of {{ state.goals[len].needed }}
          </p>
        </div>

        <div class="mb-6 space-y-2 text-center">
          <div
            v-if="gameMode === 'online'"
            class="bg-slate-100 text-slate-700 font-bold text-lg px-4 py-2 rounded-lg"
          >
            {{ formattedTime }}
          </div>
        </div>

        <div class="mb-6">
          <div class="current-word-display">
            <span v-if="state.currentWord">{{ state.currentWord }}</span>
            <span v-else class="text-slate-400">...</span>
          </div>
          <div
            class="h-6 text-center mt-2 font-medium transition-opacity duration-300"
            :class="[message.text ? 'opacity-100' : 'opacity-0', messageColorClass]"
          >
            {{ message.text }}
          </div>
        </div>

        <div class="flex flex-wrap justify-center gap-3 mb-6">
          <button
            v-for="letter in gameConfig.availableLetters"
            :key="letter"
            @click="handleLetterClick(letter)"
            :disabled="isGameOver"
            class="letter-card disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ letter }}
          </button>
        </div>

        <div class="flex justify-center items-center gap-3">
          <button
            @click="handleSubmit"
            :disabled="isGameOver"
            class="action-button bg-teal-500 hover:bg-teal-600 text-white w-full disabled:bg-slate-400 disabled:cursor-not-allowed"
          >
            Submit
          </button>
          <button
            @click="state.currentWord = ''"
            :disabled="isGameOver"
            class="action-button bg-slate-200 hover:bg-slate-300 text-slate-700 w-1/2 disabled:opacity-50"
          >
            Clear
          </button>
          <button
            @click="shuffleLetters"
            :disabled="isGameOver"
            class="action-button bg-slate-200 hover:bg-slate-300 text-slate-700 flex-shrink-0 p-3 disabled:opacity-50"
          >
            <Icon name="shuffle" class="w-6 h-6" />
          </button>
        </div>

        <Modal :show="showWinModal" title="You Won!">
          <p class="text-slate-600 mb-6 text-lg">Congratulations! You found all the words.</p>
          <button
            @click="initGame"
            class="w-full bg-teal-500 hover:bg-teal-600 text-white font-bold py-3 px-4 rounded-lg shadow-md"
          >
            Play Again
          </button>
        </Modal>

        <Modal :show="isGameOver && gameMode === 'online'" :title="gameOverTitle">
          <p class="text-slate-600 mb-6 text-lg">{{ gameOverMessage }}</p>
          <button
            @click="$router.push({ name: 'Home' })"
            class="w-full bg-teal-500 hover:bg-teal-600 text-white font-bold py-3 px-4 rounded-lg shadow-md"
          >
            Back to Home
          </button>
        </Modal>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiClient from '../services/apiClient.js'
import echo from '../services/echo.js'
import Icon from './icons/Icon.vue'
import Modal from './Modal.vue'

const route = useRoute()
const router = useRouter()

const isLoading = ref(true)
const error = ref(null)
const gameMode = ref('offline')
const onlineGameData = ref(null)
const isGameOver = ref(false)
const winner = ref(null)
const timer = ref(90)
let timerInterval = null

const gameConfig = reactive({ availableLetters: [], wordsToFind: {}, theme: '' })
const state = reactive({ currentWord: '', foundWords: [], goals: {} })
const message = reactive({ text: '', color: '' })
const showWinModal = ref(false)

let channel = null

const setupGameData = (data) => {
  gameConfig.availableLetters = data.availableLetters
  gameConfig.theme = data.theme
  gameConfig.wordsToFind = data.wordsToFind

  const newGoals = {}
  Object.keys(data.wordsToFind).forEach((len) => {
    newGoals[len] = { needed: data.wordsToFind[len].length, found: 0 }
  })
  state.goals = newGoals
  state.currentWord = ''
  state.foundWords = []
}

const startTimer = (duration) => {
  timer.value = duration
  timerInterval = setInterval(() => {
    if (timer.value > 0) {
      timer.value--
    } else {
      clearInterval(timerInterval)
      isGameOver.value = true
    }
  }, 1000)
}

const initGame = async () => {
  isLoading.value = true
  error.value = null
  showWinModal.value = false
  isGameOver.value = false
  winner.value = null
  clearInterval(timerInterval)

  try {
    if (gameMode.value === 'online') {
      const gameData = onlineGameData.value

      // --- THE FIX: Use the correct data source for online games ---
      const words = gameData.words_for_game // This is the array of strings for the match
      const allLetters = words.join('')
      const uniqueLetters = [...new Set(allLetters.split(''))]

      const wordsToFind = words.reduce((acc, word) => {
        const len = word.length
        if (!acc[len]) acc[len] = []
        acc[len].push(word)
        return acc
      }, {})

      const constructedData = {
        availableLetters: uniqueLetters,
        theme: gameData.theme.name,
        wordsToFind: wordsToFind,
      }
      setupGameData(constructedData)
      startTimer(gameData.duration_seconds)
    } else {
      const response = await apiClient.get('/game/start')
      setupGameData(response.data)
    }
  } catch (err) {
    console.error('Failed to set up game:', err)
    error.value = 'Could not load a new game. Please try again.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  if (window.history.state.onlineGameData) {
    gameMode.value = 'online'
    onlineGameData.value = window.history.state.onlineGameData

    channel = echo.private(`game.${onlineGameData.value.id}`)

    channel
      .listen('WordFound', (event) => {
        if (!state.foundWords.includes(event.word)) {
          state.foundWords.push(event.word)
          state.foundWords.sort()
          const len = event.word.length
          if (state.goals[len]) state.goals[len].found++
        }
      })
      .listen('GameFinished', (event) => {
        isGameOver.value = true
        winner.value = event.game.players.find((p) => p.id === event.game.winner_id)
      })
  }
  initGame()
})

onUnmounted(() => {
  if (channel) {
    echo.leave(`game.${onlineGameData.value.id}`)
  }
  clearInterval(timerInterval)
})

const handleSubmit = async () => {
  const word = state.currentWord.toUpperCase()
  if (!word || isGameOver.value) return

  if (gameMode.value === 'online') {
    try {
      const authToken = localStorage.getItem('authToken')
      await apiClient.post(
        `/game/${onlineGameData.value.id}/submit`,
        { word },
        {
          headers: { Authorization: `Bearer ${authToken}` },
        }
      )
      showUserMessage('Submitted!', 'green')
    } catch (err) {
      showUserMessage(err.response?.data?.message || 'Invalid word', 'red')
    }
  } else {
    const len = word.length
    if (gameConfig.wordsToFind[len]?.includes(word)) {
      if (state.foundWords.includes(word)) {
        showUserMessage('Already found!', 'blue')
      } else {
        state.foundWords.push(word)
        state.foundWords.sort()
        state.goals[len].found++
        showUserMessage('Correct!', 'green')
        const totalNeeded = Object.values(state.goals).reduce((sum, goal) => sum + goal.needed, 0)
        if (state.foundWords.length === totalNeeded) {
          isGameOver.value = true
          showWinModal.value = true
        }
      }
    } else {
      showUserMessage('Not in the list!', 'red')
    }
  }
  state.currentWord = ''
}

const formattedTime = computed(() => {
  const minutes = Math.floor(timer.value / 60)
  const seconds = timer.value % 60
  return `${minutes}:${seconds.toString().padStart(2, '0')}`
})

const gameOverTitle = computed(() => (winner.value ? 'Game Over' : "Time's Up!"))
const gameOverMessage = computed(() => {
  const currentUser = JSON.parse(localStorage.getItem('user'))
  if (!winner.value) return 'The game is a draw!'
  return winner.value.id === currentUser.id
    ? 'Congratulations, you won!'
    : `${winner.value.name} has won the game!`
})

const sortedGoalKeys = computed(() => Object.keys(state.goals).sort((a, b) => a - b))
const messageColorClass = computed(() => ({
  'text-green-600': message.color === 'green',
  'text-red-600': message.color === 'red',
  'text-blue-600': message.color === 'blue',
}))
const showUserMessage = (text, color) => {
  message.text = text
  message.color = color
  setTimeout(() => {
    message.text = ''
    message.color = ''
  }, 3000)
}
const handleLetterClick = (letter) => {
  if (state.currentWord.length < 10) state.currentWord += letter
}
const shuffleLetters = () => {
  gameConfig.availableLetters.sort(() => Math.random() - 0.5)
}
</script>

<style scoped>
.title-font {
  font-family: 'Pacifico', cursive;
}
.letter-card {
  @apply w-14 h-14 rounded-lg text-2xl font-bold text-slate-800 transition-all duration-200;
  background-color: #e0e5ec;
  box-shadow: 5px 5px 10px #a3b1c6, -5px -5px 10px #ffffff;
}
.letter-card:active {
  box-shadow: inset 5px 5px 10px #a3b1c6, inset -5px -5px 10px #ffffff;
  transform: translateY(1px);
}
.found-word-badge {
  @apply h-8 inline-flex items-center justify-center px-4 bg-teal-100 text-teal-800 font-bold text-sm rounded-full;
}
.current-word-display {
  @apply w-full text-center bg-slate-100 rounded-lg h-16 flex items-center justify-center text-3xl font-bold tracking-widest uppercase text-slate-700 select-none;
}
.action-button {
  @apply py-3 px-4 rounded-lg shadow-md transition-transform duration-150 ease-in-out transform hover:scale-105 font-bold;
}
</style>
