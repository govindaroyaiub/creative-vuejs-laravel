<template>

    <Head title="Tetris Game" />
    <AppLayout :breadcrumbs="[{ title: 'Tetris Game', href: '/play/tetris' }]">
        <!-- Introductory Guide Overlay -->
        <div v-if="showIntro" @click.self="closeIntro"
            class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 backdrop-blur-sm">
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-2xl mx-4 transform transition-all animate-fade-in">
                <h2 class="text-3xl font-bold mb-6 text-center text-black dark:text-white">How to Play Tetris</h2>
                <p class="text-gray-600 dark:text-gray-300 mb-8 text-center">Use your keyboard to control the falling
                    blocks!</p>

                <div class="grid grid-cols-2 gap-6 mb-8">
                    <!-- Left Arrow -->
                    <div
                        class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl transition-all hover:scale-105">
                        <div class="keyboard-key animate-pulse-subtle">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-black dark:text-white">Move Left</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">← Arrow Key</div>
                        </div>
                    </div>

                    <!-- Right Arrow -->
                    <div
                        class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl transition-all hover:scale-105">
                        <div class="keyboard-key animate-pulse-subtle" style="animation-delay: 0.1s;">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-black dark:text-white">Move Right</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">→ Arrow Key</div>
                        </div>
                    </div>

                    <!-- Up Arrow -->
                    <div
                        class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl transition-all hover:scale-105">
                        <div class="keyboard-key animate-pulse-subtle" style="animation-delay: 0.2s;">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 15l7-7 7 7" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-black dark:text-white">Rotate</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">↑ Arrow Key</div>
                        </div>
                    </div>

                    <!-- Down Arrow -->
                    <div
                        class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl transition-all hover:scale-105">
                        <div class="keyboard-key animate-pulse-subtle" style="animation-delay: 0.3s;">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-black dark:text-white">Move Down Faster</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">↓ Arrow Key</div>
                        </div>
                    </div>

                    <!-- Spacebar -->
                    <div
                        class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl transition-all hover:scale-105 col-span-2">
                        <div class="keyboard-key-wide animate-pulse-subtle" style="animation-delay: 0.4s;">
                            <span class="text-xs font-bold">SPACE</span>
                        </div>
                        <div>
                            <div class="font-semibold text-black dark:text-white">Instant Drop</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Drop block to bottom instantly</div>
                        </div>
                    </div>
                </div>

                <button @click="closeIntro"
                    class="w-full py-3 px-6 bg-black dark:bg-white text-white dark:text-black rounded-xl font-bold text-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95">
                    Got it! Let's Play 🎮
                </button>
            </div>
        </div>

        <div class="flex flex-col items-center justify-center w-full h-full bg-white dark:bg-black font-mono">
            <div class="flex flex-row gap-8 w-full max-w-3xl">
                <div class="flex flex-col gap-4">
                    <div class="flex gap-4 justify-between">
                        <button @click="startGame" :disabled="isPlaying"
                            class="px-4 py-2 bg-black text-white rounded-xl border-2 hover:bg-white hover:text-black disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-black disabled:hover:text-white">
                            New Game
                        </button>
                        <button @click="resetScore"
                            :disabled="!isSuperAdmin"
                            class="px-4 py-2 bg-red-600 text-white rounded-xl border-2 hover:bg-white hover:text-red-600 hover:border-red-600 transition-all">
                            Reset
                        </button>
                    </div>
                    <div ref="boardRef" class="relative" tabindex="0" @keydown="handleKey" style="outline: none;">
                        <div v-for="y in rows" :key="y" class="flex">
                            <div v-for="x in cols" :key="x" class="w-6 h-6 border border-gray-300 dark:border-gray-700"
                                :style="getCellStyle(x - 1, y - 1)"></div>
                        </div>
                        <div v-if="gameOver"
                            class="absolute inset-0 bg-black bg-opacity-70 flex items-center justify-center">
                            <span class="text-2xl text-white font-bold">Game Over</span>
                        </div>
                    </div>
                </div>
                <div class="flex-1 flex flex-col gap-4">
                    <div class="text-lg text-black dark:text-white font-bold">Score: {{ score }}</div>
                    <h2 class="text-xl font-bold mb-2 text-black dark:text-white">Top 10 High Scores</h2>
                    <table
                        class="min-w-full table-auto bg-white dark:bg-black text-black dark:text-white text-center rounded-xl shadow">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b text-center">Rank</th>
                                <th class="py-2 px-4 border-b text-center">Name</th>
                                <th class="py-2 px-4 border-b text-center">Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in topScores" :key="item.id">
                                <td class="py-2 px-4 border-b text-center">{{ idx + 1 }}</td>
                                <td class="py-2 px-4 border-b text-center">{{ item.user?.name ?? '-' }}</td>
                                <td class="py-2 px-4 border-b text-center">{{ item.score }}</td>
                            </tr>
                            <tr v-if="topScores.length === 0">
                                <td colspan="3" class="text-center py-4">No scores yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
const authUser = computed(() => (page.props.auth as any)?.user);
const isSuperAdmin = computed(() => authUser.value?.role === 'super_admin');

const props = defineProps({
    topScores: Array
})

const rows = 20
const cols = 10

const board = ref([])
const score = ref(0)
const gameOver = ref(false)
const intervalId = ref(null)
const boardRef = ref(null)
const topScores = ref(props.topScores)
const hasStarted = ref(false)
const isPlaying = computed(() => hasStarted.value && !gameOver.value)
const showIntro = ref(true)

const tetrominoes = [
    { color: '#00f0f0', shape: [[[1, 1, 1, 1]], [[1], [1], [1], [1]]] },
    { color: '#0000f0', shape: [[[1, 0, 0], [1, 1, 1]], [[1, 1], [1, 0], [1, 0]], [[1, 1, 1], [0, 0, 1]], [[0, 1], [0, 1], [1, 1]]] },
    { color: '#f0a000', shape: [[[0, 0, 1], [1, 1, 1]], [[1, 0], [1, 0], [1, 1]], [[1, 1, 1], [1, 0, 0]], [[1, 1], [0, 1], [0, 1]]] },
    { color: '#f0f000', shape: [[[1, 1], [1, 1]]] },
    { color: '#00f000', shape: [[[0, 1, 1], [1, 1, 0]], [[1, 0], [1, 1], [0, 1]]] },
    { color: '#a000f0', shape: [[[0, 1, 0], [1, 1, 1]], [[1, 0], [1, 1], [1, 0]], [[1, 1, 1], [0, 1, 0]], [[0, 1], [1, 1], [0, 1]]] },
    { color: '#f00000', shape: [[[1, 1, 0], [0, 1, 1]], [[0, 1], [1, 1], [1, 0]]] },
]

const current = ref({
    x: 3,
    y: 0,
    type: 0,
    rotation: 0,
})

function getRandomTetromino() {
    const type = Math.floor(Math.random() * tetrominoes.length)
    return {
        x: Math.floor(cols / 2) - 2,
        y: 0,
        type,
        rotation: 0,
    }
}

function resetBoard() {
    board.value = Array.from({ length: rows }, () =>
        Array(cols).fill(null)
    )
}

function drawTetromino(tetro, val = true) {
    const shape = tetrominoes[tetro.type].shape[tetro.rotation]
    for (let y = 0; y < shape.length; y++) {
        for (let x = 0; x < shape[y].length; x++) {
            if (shape[y][x]) {
                const bx = tetro.x + x
                const by = tetro.y + y
                if (by >= 0 && by < rows && bx >= 0 && bx < cols) {
                    board.value[by][bx] = val ? tetrominoes[tetro.type].color : null
                }
            }
        }
    }
}

function canMove(tetro, dx, dy, dr) {
    const shape = tetrominoes[tetro.type].shape[(tetro.rotation + dr + tetrominoes[tetro.type].shape.length) % tetrominoes[tetro.type].shape.length]
    for (let y = 0; y < shape.length; y++) {
        for (let x = 0; x < shape[y].length; x++) {
            if (shape[y][x]) {
                const bx = tetro.x + dx + x
                const by = tetro.y + dy + y
                if (
                    bx < 0 ||
                    bx >= cols ||
                    by >= rows ||
                    (by >= 0 && board.value[by] && board.value[by][bx])
                ) {
                    return false
                }
            }
        }
    }
    return true
}

function mergeTetromino(tetro) {
    const shape = tetrominoes[tetro.type].shape[tetro.rotation]
    for (let y = 0; y < shape.length; y++) {
        for (let x = 0; x < shape[y].length; x++) {
            if (shape[y][x]) {
                const bx = tetro.x + x
                const by = tetro.y + y
                if (by >= 0 && by < rows && bx >= 0 && bx < cols) {
                    board.value[by][bx] = tetrominoes[tetro.type].color
                }
            }
        }
    }
}

function clearLines() {
    let lines = 0
    for (let y = rows - 1; y >= 0; y--) {
        if (board.value[y] && board.value[y].every(cell => cell)) {
            board.value.splice(y, 1)
            board.value.unshift(Array(cols).fill(null))
            lines++
            y++
        }
    }
    if (lines > 0) score.value += lines * 100
}

function submitScore() {
    axios.post('/tetris/submit/score', {
        score: score.value
    }).then(res => {
        if (res.data.topScores) {
            topScores.value = res.data.topScores
        }
    })
}

function spawnTetromino() {
    current.value = getRandomTetromino()
    if (!canMove(current.value, 0, 0, 0)) {
        gameOver.value = true
        clearInterval(intervalId.value)
        if (hasStarted.value) submitScore()
    }
}

function move(dx, dy, dr = 0) {
    if (gameOver.value) return
    drawTetromino(current.value, false)
    const next = { ...current.value }
    next.x += dx
    next.y += dy
    next.rotation = (next.rotation + dr + tetrominoes[next.type].shape.length) % tetrominoes[next.type].shape.length
    if (canMove(next, 0, 0, 0)) {
        current.value = next
    }
    drawTetromino(current.value, true)
}

function drop() {
    if (gameOver.value) return
    drawTetromino(current.value, false)
    while (canMove(current.value, 0, 1, 0)) {
        current.value.y++
    }
    drawTetromino(current.value, true)
    mergeTetromino(current.value)
    clearLines()
    spawnTetromino()
}

function tick() {
    if (gameOver.value) return
    drawTetromino(current.value, false)
    if (canMove(current.value, 0, 1, 0)) {
        current.value.y++
        drawTetromino(current.value, true)
    } else {
        drawTetromino(current.value, true)
        mergeTetromino(current.value)
        clearLines()
        spawnTetromino()
    }
}

function handleKey(e) {
    if (!hasStarted.value || gameOver.value) return
    if (['ArrowLeft', 'ArrowRight', 'ArrowDown', 'ArrowUp', ' '].includes(e.key)) {
        e.preventDefault()
    }
    switch (e.key) {
        case 'ArrowLeft':
            move(-1, 0)
            break
        case 'ArrowRight':
            move(1, 0)
            break
        case 'ArrowDown':
            move(0, 1)
            break
        case 'ArrowUp':
            move(0, 0, 1)
            break
        case ' ':
            drop()
            break
    }
}

function getCellStyle(x, y) {
    if (!board.value[y] || typeof board.value[y][x] === 'undefined') {
        return { background: 'transparent' }
    }
    const color = board.value[y][x]
    return {
        background: color || 'transparent',
        transition: 'background 0.1s',
    }
}

function closeIntro() {
    showIntro.value = false
    // Auto-focus the game board after closing intro
    setTimeout(() => {
        boardRef.value && boardRef.value.focus()
    }, 100)
}

function resetScore() {
    if (confirm('Are you sure you want to reset your score? This will reset your current game and score to 0.')) {
        score.value = 0
        gameOver.value = true
        hasStarted.value = false
        if (intervalId.value) clearInterval(intervalId.value)
        resetBoard()
    }
}

function startGame() {
    if (isPlaying.value) return
    hasStarted.value = true
    resetBoard()
    score.value = 0
    gameOver.value = false
    spawnTetromino()
    drawTetromino(current.value, true)
    if (intervalId.value) clearInterval(intervalId.value)
    intervalId.value = setInterval(tick, 500)
    setTimeout(() => {
        boardRef.value && boardRef.value.focus()
    }, 100)
}

onMounted(() => {
    resetBoard()
})

onUnmounted(() => {
    if (intervalId.value) clearInterval(intervalId.value)
})
</script>

<style scoped>
.keyboard-key {
    @apply w-12 h-12 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700;
    @apply rounded-lg shadow-md flex items-center justify-center;
    @apply border-2 border-gray-400 dark:border-gray-500;
    @apply text-black dark:text-white;
}

.keyboard-key-wide {
    @apply w-48 h-12 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700;
    @apply rounded-lg shadow-md flex items-center justify-center;
    @apply border-2 border-gray-400 dark:border-gray-500;
    @apply text-black dark:text-white;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes pulseSubtle {

    0%,
    100% {
        transform: scale(1);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    50% {
        transform: scale(1.05);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }
}

.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
}

.animate-pulse-subtle {
    animation: pulseSubtle 2s ease-in-out infinite;
}
</style>