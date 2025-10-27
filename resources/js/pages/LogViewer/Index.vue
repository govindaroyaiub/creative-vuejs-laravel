<template>

    <Head title="System logs" />
    <AppLayout :breadcrumbs="[{ title: 'Cache Management', href: '/cache-management' },{ title: 'Logs', href: '/logs' }]">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        📋 Log Viewer
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Monitor application logs in real-time
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Live</span>
                    </div>
                    <button @click="toggleAutoRefresh" :class="[
                        'px-3 py-1 rounded-lg text-sm font-medium transition-all duration-200',
                        autoRefresh
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                            : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                    ]">
                        {{ autoRefresh ? 'Auto-refresh ON' : 'Auto-refresh OFF' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Controls Panel -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                            <!-- File Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    📁 Log File
                                </label>
                                <select v-model="selectedFile" @change="loadLogData"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                                    <option v-for="file in logFiles" :key="file.name" :value="file.name">
                                        {{ file.icon }} {{ file.display_name }} ({{ file.formatted_size }})
                                    </option>
                                </select>
                            </div>

                            <!-- Lines to Show -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    📄 Lines to Show
                                </label>
                                <select v-model="linesToShow" @change="loadLogData"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                                    <option value="50">Last 50 lines</option>
                                    <option value="100">Last 100 lines</option>
                                    <option value="200">Last 200 lines</option>
                                    <option value="500">Last 500 lines</option>
                                    <option value="1000">Last 1000 lines</option>
                                </select>
                            </div>

                            <!-- Level Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    🏷️ Log Level
                                </label>
                                <select v-model="levelFilter" @change="loadLogData"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                                    <option value="all">All Levels</option>
                                    <option value="emergency">🚨 Emergency</option>
                                    <option value="alert">🔴 Alert</option>
                                    <option value="critical">💥 Critical</option>
                                    <option value="error">❌ Error</option>
                                    <option value="warning">⚠️ Warning</option>
                                    <option value="notice">📢 Notice</option>
                                    <option value="info">ℹ️ Info</option>
                                    <option value="debug">🐛 Debug</option>
                                </select>
                            </div>

                            <!-- Search -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    🔍 Search
                                </label>
                                <div class="relative">
                                    <input v-model="searchTerm" @keyup.enter="loadLogData" placeholder="Search logs..."
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 pl-10">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4 flex flex-wrap gap-3">
                            <button @click="loadLogData" :disabled="loading"
                                class="flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg transition-colors">
                                <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span>{{ loading ? 'Loading...' : '🔄 Refresh' }}</span>
                            </button>

                            <button @click="downloadLog"
                                class="flex items-center space-x-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                                <span>📥 Download</span>
                            </button>

                            <button @click="clearLogFile"
                                class="flex items-center space-x-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                                <span>🗑️ Clear Log</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Log Info Panel -->
                <div v-if="logData.file_info" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ logData.total_lines?.toLocaleString() || 0 }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Total Lines</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ logData.filtered_lines?.toLocaleString() || 0 }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Filtered Lines</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ logData.file_info?.formatted_size || '0 B' }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">File Size</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                    {{ logData.file_info?.modified || 'Unknown' }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Last Modified</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Log Content -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Log Content
                            </h3>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Showing {{ logData.showing_last || 0 }} most recent entries
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div v-if="logData.error"
                            class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
                            <div class="text-red-800 dark:text-red-400">
                                ❌ {{ logData.error }}
                            </div>
                        </div>

                        <!-- No Content -->
                        <div v-else-if="!logData.content || logData.content.length === 0" class="text-center py-12">
                            <div class="text-6xl mb-4">📭</div>
                            <div class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No log entries found
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                {{ logData.has_search ? 'No entries match your search criteria' : 'The log file is emptyor has no recent entries' }}
                            </div>
                        </div>

                        <!-- Log Entries -->
                        <div v-else class="space-y-2">
                            <div v-for="(entry, index) in logData.content" :key="index" :class="[
                                'border rounded-lg p-4 transition-all duration-200 hover:shadow-md',
                                getLogLevelClasses(entry.level)
                            ]">
                                <!-- Log Header -->
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-lg">{{ getLogLevelIcon(entry.level) }}</span>
                                        <div class="font-mono text-sm">
                                            <span class="text-gray-500 dark:text-gray-400">#{{ entry.line_number
                                                }}</span>
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ entry.formatted_time || entry.timestamp }}
                                        </div>
                                        <span :class="[
                                            'px-2 py-1 rounded-full text-xs font-medium uppercase tracking-wide',
                                            getLogLevelBadgeClasses(entry.level)
                                        ]">
                                            {{ entry.level }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Log Message -->
                                <div
                                    class="font-mono text-sm whitespace-pre-wrap break-words text-gray-800 dark:text-gray-200">
                                    {{ entry.message }}
                                </div>

                                <!-- Log Context (JSON data) -->
                                <div v-if="entry.context && typeof entry.context === 'object'" class="mt-3">
                                    <button @click="toggleContext(index)"
                                        class="text-xs text-blue-600 dark:text-blue-400 hover:underline mb-2">
                                        {{ expandedContexts.includes(index) ? '🔽' : '▶️' }}
                                        Show {{ expandedContexts.includes(index) ? 'Less' : 'More' }} Details
                                    </button>
                                    <div v-if="expandedContexts.includes(index)"
                                        class="bg-gray-50 dark:bg-gray-900 rounded p-3 text-xs font-mono overflow-x-auto">
                                        <pre>{{ JSON.stringify(entry.context, null, 2) }}</pre>
                                    </div>
                                </div>

                                <!-- Raw Log Line -->
                                <div class="mt-2">
                                    <button @click="toggleRaw(index)"
                                        class="text-xs text-gray-500 dark:text-gray-400 hover:underline">
                                        {{ expandedRaw.includes(index) ? '🔽' : '▶️' }}
                                        {{ expandedRaw.includes(index) ? 'Hide' : 'Show' }} Raw
                                    </button>
                                    <div v-if="expandedRaw.includes(index)"
                                        class="bg-gray-100 dark:bg-gray-700 rounded p-2 text-xs font-mono overflow-x-auto mt-1">
                                        {{ entry.raw }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, reactive } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

// Props
const props = defineProps({
    logFiles: Array,
    selectedFile: String,
    logData: Object,
    refreshInterval: Number
})

// Reactive data
const loading = ref(false)
const autoRefresh = ref(false)
const refreshTimer = ref<number | null>(null)
const selectedFile = ref(props.selectedFile || 'laravel.log')
const linesToShow = ref(100)
const levelFilter = ref('all')
const searchTerm = ref('')
const logData = reactive(props.logData || {})
const logFiles = ref(props.logFiles || [])
const expandedContexts = ref<number[]>([])
const expandedRaw = ref<number[]>([])

// Methods
const loadLogData = async () => {
    loading.value = true

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

        const params = new URLSearchParams({
            file: selectedFile.value,
            lines: linesToShow.value.toString(),
            level: levelFilter.value
        })

        if (searchTerm.value) {
            params.append('search', searchTerm.value)
        }

        const response = await fetch(`${route('logs.data')}?${params}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })

        if (response.ok) {
            const data = await response.json()
            Object.assign(logData, data)
        }
    } catch (error) {
        console.error('Error loading log data:', error)
    } finally {
        loading.value = false
    }
}

const downloadLog = () => {
    const url = route('logs.download') + '?file=' + encodeURIComponent(selectedFile.value)
    window.open(url, '_blank')
}

const clearLogFile = async () => {
    if (!confirm(`Are you sure you want to clear the ${selectedFile.value} log file? This action cannot be undone.`)) {
        return
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

        const response = await fetch(route('logs.clear'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                file: selectedFile.value
            })
        })

        const data = await response.json()

        if (data.success) {
            await loadLogData()
        }
    } catch (error) {
        console.error('Error clearing log:', error)
    }
}

const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value

    if (autoRefresh.value) {
        startAutoRefresh()
    } else {
        stopAutoRefresh()
    }
}

const startAutoRefresh = () => {
    refreshTimer.value = setInterval(() => {
        loadLogData()
    }, (props.refreshInterval || 30) * 1000)
}

const stopAutoRefresh = () => {
    if (refreshTimer.value) {
        clearInterval(refreshTimer.value)
        refreshTimer.value = null
    }
}

const toggleContext = (index: number) => {
    if (expandedContexts.value.includes(index)) {
        expandedContexts.value = expandedContexts.value.filter(i => i !== index)
    } else {
        expandedContexts.value.push(index)
    }
}

const toggleRaw = (index: number) => {
    if (expandedRaw.value.includes(index)) {
        expandedRaw.value = expandedRaw.value.filter(i => i !== index)
    } else {
        expandedRaw.value.push(index)
    }
}

const getLogLevelIcon = (level: string) => {
    const icons: Record<string, string> = {
        emergency: '🚨',
        alert: '🔴',
        critical: '💥',
        error: '❌',
        warning: '⚠️',
        notice: '📢',
        info: 'ℹ️',
        debug: '🐛'
    }
    return icons[level] || 'ℹ️'
}

const getLogLevelClasses = (level: string) => {
    const classes: Record<string, string> = {
        emergency: 'border-red-500 bg-red-50 dark:bg-red-900/20 dark:border-red-400',
        alert: 'border-red-400 bg-red-50 dark:bg-red-900/20 dark:border-red-400',
        critical: 'border-red-400 bg-red-50 dark:bg-red-900/20 dark:border-red-400',
        error: 'border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-400',
        warning: 'border-yellow-300 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-400',
        notice: 'border-blue-300 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-400',
        info: 'border-gray-300 bg-gray-50 dark:bg-gray-700 dark:border-gray-600',
        debug: 'border-purple-300 bg-purple-50 dark:bg-purple-900/20 dark:border-purple-400'
    }
    return classes[level] || classes.info
}

const getLogLevelBadgeClasses = (level: string) => {
    const classes: Record<string, string> = {
        emergency: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        alert: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        critical: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        error: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        notice: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        info: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
        debug: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'
    }
    return classes[level] || classes.info
}

// Lifecycle
onMounted(() => {
    // Load initial data if not provided
    if (!logData.content) {
        loadLogData()
    }
})

onUnmounted(() => {
    stopAutoRefresh()
})
</script>