<template>

    <Head title="Cache Management" />

    <!-- Modern Minimal Background -->
    <div
        class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-indigo-900">

        <!-- Clean Header -->
        <header
            class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-slate-200/50 dark:border-slate-700/50 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                    <div class="flex items-center space-x-3 sm:space-x-4">
                        <button @click="goBack"
                            class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition-all duration-200 flex-shrink-0">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-slate-600 dark:text-slate-300" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </button>

                        <div class="min-w-0 flex-1">
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white truncate">Cache
                                Management</h1>
                            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm hidden sm:block">System
                                cleanup and monitoring</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end space-x-2 sm:space-x-3">
                        <!-- Live Server Time Card -->
                        <div
                            class="bg-slate-100 dark:bg-slate-800 rounded-lg sm:rounded-xl px-3 sm:px-6 py-1.5 sm:py-2 text-right flex-shrink-0">
                            <p class="text-xs text-slate-500 dark:text-slate-400 hidden sm:block">Server Time</p>
                            <p class="text-xs sm:text-sm font-mono font-semibold text-slate-900 dark:text-white">
                                {{ currentTime || 'Loading...' }}
                            </p>
                            <p class="text-xs text-emerald-500 mt-0.5 sm:mt-1 hidden sm:block">
                                🌍 {{ detectedTimezone }}
                            </p>
                        </div>

                        <!-- Refresh Button -->
                        <button @click="refreshStats(true)" :disabled="isRefreshing"
                            class="px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-400 text-white rounded-lg sm:rounded-xl transition-all duration-200 flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                            <svg :class="{ 'animate-spin': isRefreshing }" class="w-3 h-3 sm:w-4 sm:h-4" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            <span class="text-xs sm:text-sm">{{ isRefreshing ? 'Refreshing...' : 'Refresh' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

            <!-- Tab Navigation -->
            <div class="mb-6 sm:mb-8">
                <nav class="flex overflow-x-auto scrollbar-hide bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                    <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                        'px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg font-medium text-sm whitespace-nowrap transition-all duration-200 flex-shrink-0',
                        activeTab === tab.id
                            ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm'
                            : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'
                    ]">
                        <span class="mr-2">{{ tab.icon }}</span>
                        {{ tab.name }}
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="space-y-6 sm:space-y-8">

                <!-- Overview Tab -->
                <div v-show="activeTab === 'overview'" class="space-y-4 sm:space-y-6">

                    <!-- Quick Actions -->
                    <section
                        class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                        <h2
                            class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white mb-3 sm:mb-4 flex items-center">
                            <span class="text-lg sm:text-xl mr-2 sm:mr-3">⚡</span>
                            Quick Actions
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                            <button v-for="action in quickActions" :key="action.type" @click="runCleanup(action.type)"
                                :disabled="isRunningCleanup"
                                class="group relative p-3 sm:p-4 bg-gradient-to-br rounded-lg sm:rounded-xl transition-all duration-200 transform hover:scale-105 disabled:scale-100 disabled:opacity-50 min-h-[80px] sm:min-h-[100px] flex flex-col justify-center"
                                :class="action.gradient">
                                <div class="text-lg sm:text-2xl mb-1 sm:mb-2">{{ action.icon }}</div>
                                <div class="text-xs sm:text-sm font-semibold text-white">{{ action.name }}</div>
                                <div class="text-xs text-white/80 mt-0.5 sm:mt-1">{{ action.description }}</div>
                            </button>
                        </div>
                    </section>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
                        <div v-for="(stat, key) in currentStats" :key="key"
                            class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6 hover:shadow-lg transition-all duration-200">
                            <div class="flex items-center justify-between mb-3 sm:mb-4">
                                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                    <div class="text-xl sm:text-2xl flex-shrink-0">{{ stat.icon }}</div>
                                    <div class="min-w-0 flex-1">
                                        <h3
                                            class="font-semibold text-slate-900 dark:text-white text-sm sm:text-base truncate">
                                            {{ stat.name }}</h3>
                                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">{{ stat.files
                                            }} files</p>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm sm:text-lg font-bold text-slate-900 dark:text-white">{{
                                        formatBytes(stat.size) }}</div>
                                </div>
                            </div>

                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                <div :class="`bg-gradient-to-r from-${stat.color}-400 to-${stat.color}-600`"
                                    class="h-2 rounded-full transition-all duration-1000"
                                    :style="{ width: `${getUsagePercentage(stat.size)}%` }">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Cards Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                        <!-- Scheduler Status -->
                        <div
                            class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                            <div class="flex items-center justify-between mb-3 sm:mb-4">
                                <h3 class="font-semibold text-slate-900 dark:text-white text-sm sm:text-base">Auto
                                    Scheduler</h3>
                                <div class="flex items-center space-x-2">
                                    <div :class="getSchedulerStatusColor()" class="w-2 h-2 rounded-full"></div>
                                    <span :class="getSchedulerStatusTextColor()" class="text-xs font-medium">
                                        {{ getSchedulerStatusText() }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-2 sm:space-y-3">
                                <div class="flex justify-between text-xs sm:text-sm">
                                    <span class="text-slate-500 dark:text-slate-400">Next Run</span>
                                    <span class="font-medium text-slate-900 dark:text-white text-right">
                                        {{ schedulerStatus?.next_run?.human || 'Calculating...' }}
                                    </span>
                                </div>

                                <div class="flex justify-between text-xs sm:text-sm">
                                    <span class="text-slate-500 dark:text-slate-400">Schedule</span>
                                    <span class="font-medium text-slate-900 dark:text-white font-mono text-right">
                                        {{ schedulerStatus?.schedule_time || '04:30' }} daily
                                    </span>
                                </div>

                                <div class="flex justify-between text-xs sm:text-sm">
                                    <span class="text-slate-500 dark:text-slate-400">Success Rate</span>
                                    <span class="font-medium text-emerald-600 dark:text-emerald-400">
                                        {{ schedulerStatus?.success_rate || 100 }}%
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- System Info -->
                        <div
                            class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                            <h3 class="font-semibold text-slate-900 dark:text-white mb-3 sm:mb-4 text-sm sm:text-base">
                                System Info</h3>
                            <div class="space-y-2 sm:space-y-3">
                                <div class="flex justify-between text-xs sm:text-sm">
                                    <span class="text-slate-500 dark:text-slate-400">PHP</span>
                                    <span class="font-medium text-slate-900 dark:text-white font-mono text-right">
                                        {{ systemInfo?.php_version || 'Loading...' }}
                                    </span>
                                </div>

                                <div class="flex justify-between text-xs sm:text-sm">
                                    <span class="text-slate-500 dark:text-slate-400">Laravel</span>
                                    <span class="font-medium text-slate-900 dark:text-white font-mono text-right">
                                        {{ systemInfo?.laravel_version || 'Loading...' }}
                                    </span>
                                </div>

                                <div class="flex justify-between text-xs sm:text-sm">
                                    <span class="text-slate-500 dark:text-slate-400">Timezone</span>
                                    <div class="text-right">
                                        <span class="font-medium text-slate-900 dark:text-white font-mono text-xs">
                                            {{ systemInfo?.timezone || 'Loading...' }}
                                        </span>
                                        <div v-if="systemInfo?.is_timezone_detected" class="text-xs text-emerald-500">
                                            🌍 Auto-detected
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Disk Usage -->
                        <div
                            class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                            <h3 class="font-semibold text-slate-900 dark:text-white mb-3 sm:mb-4 text-sm sm:text-base">
                                Disk Usage</h3>
                            <div class="space-y-3 sm:space-y-4">
                                <div class="text-center">
                                    <div class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                                        {{ systemInfo?.disk_usage?.used_percentage || 0 }}%
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">Used</div>
                                </div>

                                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-1000"
                                        :style="{ width: `${systemInfo?.disk_usage?.used_percentage || 0}%` }">
                                    </div>
                                </div>

                                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                                    <span>Free: {{ systemInfo?.disk_usage?.free || 'N/A' }}</span>
                                    <span>Total: {{ systemInfo?.disk_usage?.total || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Tab -->
                <div v-show="activeTab === 'activity'" class="space-y-4 sm:space-y-6">
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Recent Activity -->
                        <div
                            class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                            <h3 class="font-semibold text-slate-900 dark:text-white mb-3 sm:mb-4 text-sm sm:text-base">
                                Recent Cleanups</h3>

                            <div v-if="recentCleanups && recentCleanups.length"
                                class="space-y-2 sm:space-y-3 max-h-80 overflow-y-auto">
                                <div v-for="(cleanup, index) in recentCleanups" :key="index"
                                    class="flex flex-col sm:flex-row sm:justify-between sm:items-center p-3 bg-slate-50 dark:bg-slate-800 rounded-lg space-y-2 sm:space-y-0">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-slate-900 dark:text-white text-sm">{{
                                            cleanup.total_files
                                            }} files</div>
                                        <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">{{
                                            cleanup.human_time }}
                                        </div>
                                    </div>
                                    <div class="flex justify-between sm:text-right">
                                        <div class="font-semibold text-emerald-600 dark:text-emerald-400 text-sm">{{
                                            cleanup.total_size }}</div>
                                        <div
                                            class="text-xs text-slate-500 dark:text-slate-400 font-mono sm:ml-2 sm:mt-1">
                                            {{
                                                cleanup.timestamp }}</div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-6 sm:py-8">
                                <div class="text-3xl sm:text-4xl mb-2 sm:mb-3">🧹</div>
                                <p class="text-slate-500 dark:text-slate-400 text-sm">No cleanup activity yet</p>
                            </div>
                        </div>

                        <!-- Combined Scheduler Activity Timeline -->
                        <div v-if="hasSchedulerActivity"
                            class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                            <h3
                                class="font-semibold text-slate-900 dark:text-white mb-3 sm:mb-4 flex items-center text-sm sm:text-base">
                                <span class="text-blue-500 mr-2">📅</span>
                                Scheduler Activity Timeline
                            </h3>

                            <div class="space-y-2 sm:space-y-3 max-h-80 overflow-y-auto">
                                <div v-for="(activity, index) in getSchedulerActivities()" :key="`activity-${index}`"
                                    :class="[
                                        'flex flex-col sm:flex-row sm:justify-between sm:items-start p-3 sm:p-4 rounded-lg border space-y-2 sm:space-y-0',
                                        activity.type === 'success'
                                            ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800'
                                            : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
                                    ]">
                                    <div class="flex items-start space-x-2 sm:space-x-3 flex-1 min-w-0">
                                        <div :class="[
                                            'text-base sm:text-lg mt-0.5 flex-shrink-0',
                                            activity.type === 'success' ? 'text-emerald-500' : 'text-red-500'
                                        ]">
                                            {{ activity.type === 'success' ? '✅' : '❌' }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div v-if="activity.type === 'success'"
                                                class="font-semibold text-emerald-700 dark:text-emerald-300 text-sm">
                                                Cleaned {{ activity.files_cleaned }} files
                                            </div>
                                            <div v-else class="font-medium text-sm text-red-700 dark:text-red-300">
                                                {{ activity.message }}
                                            </div>
                                            <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                                                {{ activity.human }}
                                            </div>
                                            <div class="text-xs text-slate-400 dark:text-slate-500 font-mono mt-0.5">
                                                {{ activity.formatted }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-between sm:block sm:ml-3 sm:text-right">
                                        <div v-if="activity.type === 'success'"
                                            class="text-emerald-600 dark:text-emerald-400 font-semibold text-sm">
                                            {{ activity.space_freed }}
                                        </div>
                                        <div v-else
                                            class="text-red-500 dark:text-red-400 text-xs font-medium bg-red-100 dark:bg-red-900/30 px-2 py-1 rounded">
                                            Error
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Show message when no scheduler activity -->
                        <div v-else
                            class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                            <h3
                                class="font-semibold text-slate-900 dark:text-white mb-3 sm:mb-4 flex items-center text-sm sm:text-base">
                                <span class="text-blue-500 mr-2">📅</span>
                                Scheduler Activity Timeline
                            </h3>
                            <div class="text-center py-6 sm:py-8">
                                <div class="text-3xl sm:text-4xl mb-2 sm:mb-3">⏰</div>
                                <p class="text-slate-500 dark:text-slate-400 text-sm">No scheduler activity yet</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div v-show="activeTab === 'settings'" class="space-y-4 sm:space-y-6">
                    <div class="max-w-full lg:max-w-2xl">
                        <div
                            class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700 p-4 sm:p-6">
                            <h3 class="font-semibold text-slate-900 dark:text-white mb-4 sm:mb-6 text-sm sm:text-base">
                                Scheduler Settings</h3>

                            <div class="space-y-4 sm:space-y-6">
                                <!-- Enable/Disable Toggle -->
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 sm:p-4 bg-slate-50 dark:bg-slate-800 rounded-lg sm:rounded-xl space-y-3 sm:space-y-0">
                                    <div class="flex-1">
                                        <div class="font-medium text-slate-900 dark:text-white text-sm sm:text-base">
                                            Automatic Cleanup</div>
                                        <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Enable
                                            daily automated
                                            cache cleanup</div>
                                    </div>
                                    <label
                                        class="relative inline-flex items-center cursor-pointer self-start sm:self-center">
                                        <input type="checkbox" v-model="schedulerEnabled" class="sr-only"
                                            @change="updateSchedulerSettings">
                                        <div :class="schedulerEnabled ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-600'"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                                            <span :class="schedulerEnabled ? 'translate-x-6' : 'translate-x-1'"
                                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform">
                                            </span>
                                        </div>
                                    </label>
                                </div>

                                <!-- Time Setting -->
                                <div class="p-3 sm:p-4 bg-slate-50 dark:bg-slate-800 rounded-lg sm:rounded-xl">
                                    <label class="block">
                                        <div
                                            class="font-medium text-slate-900 dark:text-white mb-2 text-sm sm:text-base">
                                            Cleanup Time</div>
                                        <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mb-3">Daily
                                            execution
                                            time (24-hour format)</div>
                                        <input type="time" v-model="schedulerTime" @change="updateSchedulerSettings"
                                            :disabled="!schedulerEnabled"
                                            class="w-full px-3 sm:px-4 py-2.5 sm:py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg sm:rounded-xl text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50 text-sm sm:text-base">
                                    </label>
                                </div>

                                <!-- Current Status -->
                                <div class="p-3 sm:p-4 bg-slate-50 dark:bg-slate-800 rounded-lg sm:rounded-xl">
                                    <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mb-2">Current
                                        Schedule</div>
                                    <div class="font-bold text-lg sm:text-xl text-slate-900 dark:text-white font-mono">
                                        {{ schedulerEnabled ? `${schedulerTime} daily` : 'Disabled' }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">{{
                                        schedulerStatus?.timezone || 'UTC' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { router, Head, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import timezoneDetector from '@/utils/timezone.js'

// Props
const props = defineProps({
    stats: Object,
    recentCleanups: Array,
    systemInfo: Object,
    lastCleanup: Object,
    schedulerStatus: Object
})

// Reactive state
const currentStats = ref(props.stats || {})
const schedulerStatus = ref(props.schedulerStatus || {})
const isRefreshing = ref(false)
const isRunningCleanup = ref(false)
const schedulerEnabled = ref(true)
const schedulerTime = ref('04:30')
const activeTab = ref('overview')

// Live clock variables
const currentTime = ref('')
const detectedTimezone = ref('')
const clockInterval = ref(null)

// Tab configuration
const tabs = ref([
    { id: 'overview', name: 'Overview', icon: '📊' },
    { id: 'activity', name: 'Activity', icon: '🕐' },
    { id: 'settings', name: 'Settings', icon: '⚙️' }
])

// Quick actions configuration
const quickActions = ref([
    { type: 'all', name: 'Clean All', description: 'Complete cleanup', icon: '🧹', gradient: 'from-emerald-500 to-emerald-600' },
    { type: 'laravel', name: 'Laravel Cache', description: 'App & framework', icon: '🗂️', gradient: 'from-blue-500 to-blue-600' },
    { type: 'storage', name: 'Storage', description: 'Temp files', icon: '📁', gradient: 'from-amber-500 to-amber-600' },
    { type: 'logs', name: 'Logs', description: 'Old log files', icon: '📋', gradient: 'from-red-500 to-red-600' },
    { type: 'temp', name: 'Temp Files', description: 'Upload temps', icon: '🗃️', gradient: 'from-purple-500 to-purple-600' }
])

// Methods
const formatBytes = (bytes) => {
    if (!bytes) return '0 B'
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    return `${Math.round(bytes / Math.pow(1024, i) * 100) / 100} ${sizes[i]}`
}

const getUsagePercentage = (size) => {
    const stats = currentStats.value || {}
    const maxSize = Math.max(...Object.values(stats).map(s => s?.size || 0))
    return maxSize > 0 ? (size / maxSize) * 100 : 0
}

const getSchedulerStatusColor = () => {
    const status = schedulerStatus.value?.status
    switch (status) {
        case 'running_successfully': return 'bg-emerald-500'
        case 'active': return 'bg-blue-500'
        case 'attention_needed': return 'bg-amber-500'
        case 'error': return 'bg-red-500'
        case 'disabled': return 'bg-slate-400'
        default: return 'bg-slate-400'
    }
}

const getSchedulerStatusTextColor = () => {
    const status = schedulerStatus.value?.status
    switch (status) {
        case 'running_successfully': return 'text-emerald-600 dark:text-emerald-400'
        case 'active': return 'text-blue-600 dark:text-blue-400'
        case 'attention_needed': return 'text-amber-600 dark:text-amber-400'
        case 'error': return 'text-red-600 dark:text-red-400'
        case 'disabled': return 'text-slate-500 dark:text-slate-400'
        default: return 'text-slate-500 dark:text-slate-400'
    }
}

const getSchedulerStatusText = () => {
    const status = schedulerStatus.value?.status
    switch (status) {
        case 'running_successfully': return 'Running'
        case 'active': return 'Active'
        case 'attention_needed': return 'Attention'
        case 'error': return 'Error'
        case 'disabled': return 'Disabled'
        default: return 'Unknown'
    }
}

// Computed property to check if there's any scheduler activity
const hasSchedulerActivity = computed(() => {
    return (schedulerStatus.value?.recent_auto_runs && schedulerStatus.value.recent_auto_runs.length > 0) ||
        (schedulerStatus.value?.recent_errors && schedulerStatus.value.recent_errors.length > 0)
})

// Combine and sort scheduler activities (successes and errors) by timestamp
const getSchedulerActivities = () => {
    const activities = []

    // Add successful runs
    if (schedulerStatus.value?.recent_auto_runs) {
        schedulerStatus.value.recent_auto_runs.forEach(run => {
            activities.push({
                ...run,
                type: 'success',
                timestamp: run.timestamp,
                sortTime: new Date(run.timestamp).getTime()
            })
        })
    }

    // Add errors
    if (schedulerStatus.value?.recent_errors) {
        schedulerStatus.value.recent_errors.forEach(error => {
            activities.push({
                ...error,
                type: 'error',
                timestamp: error.timestamp,
                sortTime: new Date(error.timestamp).getTime()
            })
        })
    }

    // Sort by timestamp (newest first)
    return activities.sort((a, b) => b.sortTime - a.sortTime)
}

const refreshStats = async (showSuccessToast = false) => {
    isRefreshing.value = true
    try {
        const response = await fetch('/cache-management/stats')

        if (response.redirected || response.url.includes('/login')) {
            window.location.href = '/login'
            return
        }

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`)
        }

        const data = await response.json()
        currentStats.value = data

        if (showSuccessToast) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Stats refreshed successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            })
        }
    } catch (error) {
        console.error('Failed to refresh stats:', error)
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'Failed to refresh stats',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        })
    } finally {
        isRefreshing.value = false
    }
}

// Live Clock Functions
const startLiveClock = () => {
    // Get initial server time and timezone
    updateServerTime()

    // Set up interval to update every second
    clockInterval.value = setInterval(updateServerTime, 1000)
}

const updateServerTime = async () => {
    try {
        const response = await fetch('/cache-management/server-time')
        if (response.ok) {
            const data = await response.json()

            // Set timezone from server
            detectedTimezone.value = data.timezone

            // Create Date object from server timestamp and format it
            const serverTime = new Date(data.timestamp * 1000)
            currentTime.value = serverTime.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            })
        } else {
            // Fallback to browser time if server request fails
            updateTime()
        }
    } catch (error) {
        console.warn('Failed to fetch server time, using browser time:', error)
        // Fallback to browser time
        updateTime()
    }
}

const updateTime = () => {
    // Fallback function for browser time
    detectedTimezone.value = Intl.DateTimeFormat().resolvedOptions().timeZone
    const now = new Date()
    currentTime.value = now.toLocaleTimeString('en-US', {
        hour12: true,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    })
}

const stopLiveClock = () => {
    if (clockInterval.value) {
        clearInterval(clockInterval.value)
        clockInterval.value = null
    }
}

const runCleanup = async (type) => {
    isRunningCleanup.value = true

    Swal.fire({
        title: 'Running Cache Cleanup',
        html: `<div class="text-lg">Cleaning ${type} cache...</div>`,
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading()
        }
    })

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

        const response = await fetch('/cache-management/run-cleanup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ type })
        })

        const data = await response.json()

        if (data.success) {
            Swal.fire({
                title: 'Cleanup Completed!',
                html: `
                    <div class="text-lg mb-4">✅ Successfully cleaned ${data.summary?.total_files || 0} files</div>
                    <div class="text-md">💾 Space freed: ${data.summary?.total_size_formatted || '0 B'}</div>
                `,
                icon: 'success',
                confirmButtonText: 'Great!',
                confirmButtonColor: '#10b981'
            })

            await refreshStats()
        } else {
            throw new Error(data.message || 'Cleanup failed')
        }
    } catch (error) {
        console.error('Cleanup failed:', error)
        Swal.fire({
            title: 'Cleanup Failed',
            text: error.message || 'An error occurred during cleanup',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ef4444'
        })
    } finally {
        isRunningCleanup.value = false
    }
}

const updateSchedulerSettings = async () => {
    try {
        // Use Inertia's form handling for proper CSRF token management
        const form = useForm({
            enabled: schedulerEnabled.value,
            time: schedulerTime.value
        })

        form.post('/cache-management/scheduler-settings', {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Settings updated successfully',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                })
            },
            onError: (errors) => {
                console.error('Update failed:', errors)
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Failed to update settings',
                    text: Object.values(errors)[0] || 'An error occurred',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                })
            }
        })
    } catch (error) {
        console.error('Error updating scheduler settings:', error)
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'Network error',
            text: 'Unable to connect to server',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        })
    }
}

const goBack = () => {
    router.visit('/')
}

const initializeSchedulerSettings = () => {
    if (schedulerStatus.value) {
        schedulerEnabled.value = schedulerStatus.value.is_configured || false
        schedulerTime.value = schedulerStatus.value.schedule_time || '04:30'
    }
}

// Auto-refresh stats every 30 seconds
onMounted(() => {
    // Initialize timezone detection
    timezoneDetector.init()

    // Initialize scheduler settings
    initializeSchedulerSettings()

    // Start live clock
    startLiveClock()

    setInterval(() => {
        if (!isRunningCleanup.value && !isRefreshing.value) {
            refreshStats()
        }
    }, 30000)
})

// Cleanup on unmount
onUnmounted(() => {
    stopLiveClock()
})
</script>

<style scoped>
/* Smooth transitions for tabs */
.tab-content {
    transition: opacity 0.2s ease-in-out;
}

/* Hide scrollbar for tab navigation on mobile */
.scrollbar-hide {
    -ms-overflow-style: none;
    /* Internet Explorer 10+ */
    scrollbar-width: none;
    /* Firefox */
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
    /* Safari and Chrome */
}

/* Custom scrollbar for vertical scrolling areas */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.5);
}

/* Touch-friendly button sizing */
@media (max-width: 640px) {
    button {
        min-height: 44px;
        /* iOS recommended touch target size */
    }

    /* Ensure text doesn't get too small on mobile */
    .text-xs {
        font-size: 0.75rem;
    }
}

/* Prevent horizontal overflow on small screens */
.min-w-0 {
    min-width: 0;
}

/* Responsive text truncation */
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>