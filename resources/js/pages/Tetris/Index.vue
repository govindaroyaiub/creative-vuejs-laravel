<template>
    <Head title="Tetris Game" />
    <AppLayout :breadcrumbs="[{ title: 'Tetris Game', href: '/play/tetris' }]">
        <div class="flex flex-col items-center justify-center w-full h-full bg-gray-50 dark:bg-black">
            <h1 class="text-2xl font-bold mb-4 text-black dark:text-white">Tetris Game</h1>
            <div class="mb-4 flex gap-4 justify-between w-full max-w-3xl">
                <button @click="startGame" class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700">New
                    Game</button>
                <span class="text-lg text-black dark:text-white ml-auto">Score: {{ score }}</span>
            </div>
            <div class="flex flex-row gap-8 w-full max-w-3xl">
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
                <div class="flex-1">
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
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

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
        submitScore()
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
    if (gameOver.value) return
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

function startGame() {
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
    startGame()
    boardRef.value && boardRef.value.focus()
})

onUnmounted(() => {
    if (intervalId.value) clearInterval(intervalId.value)
})
</script>