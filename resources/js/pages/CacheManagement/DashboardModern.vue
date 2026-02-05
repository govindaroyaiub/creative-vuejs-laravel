<template>

    <Head title="Cache Management" />

    <!-- Modern Minimal Background -->
    <AppLayout :breadcrumbs="[{ title: 'Cache Management', href: '/cache-management' }]">
        <div class="min-h-screen bg-white dark:bg-neutral-900 animate-fadeIn">
            <!-- Clean Header -->
            <div class="container mx-auto px-4 max-w-5xl">
                <header
                    class="backdrop-blur-xl border-b border-slate-200/50 dark:border-neutral-700/50 sticky top-0 z-50">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                            <div class="flex items-center space-x-3 sm:space-x-4">
                                <div class="min-w-0 flex-1">
                                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white truncate">
                                        Cache
                                        Management</h1>
                                    <p class="text-slate-500 dark:text-gray-400 text-xs sm:text-sm hidden sm:block">
                                        System
                                        cleanup and monitoring</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end space-x-2 sm:space-x-3">
                                <!-- Live Server Time Card -->
                                <div
                                    class="bg-white dark:bg-neutral-800 rounded-lg sm:rounded-xl px-3 sm:px-6 py-1.5 sm:py-2 text-right flex-shrink-0">
                                    <p class="text-xs text-slate-500 dark:text-gray-400 hidden sm:block">Server Time
                                    </p>
                                    <p
                                        class="text-xs sm:text-sm font-mono font-semibold text-slate-900 dark:text-white">
                                        {{ currentTime || 'Loading...' }}
                                    </p>
                                    <p class="text-xs text-emerald-500 mt-0.5 sm:mt-1 hidden sm:block">
                                        🌍 {{ currentTimezone }}
                                    </p>
                                </div>

                                <!-- Refresh Button -->
                                <button @click="refreshAllData()" :disabled="isRefreshing"
                                    class="px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg sm:rounded-xl transition-all duration-200 flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                                    <svg :class="{ 'animate-spin': isRefreshing }" class="w-3 h-3 sm:w-4 sm:h-4"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                        </path>
                                    </svg>
                                    <span class="text-xs sm:text-sm">{{ isRefreshing ? 'Refreshing...' : 'Refresh'
                                    }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Main Content -->
                <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

                    <!-- Tab Navigation -->
                    <div class="mb-6 sm:mb-8">
                        <div
                            class="bg-white dark:bg-neutral-800/50 backdrop-blur-sm rounded-2xl shadow-lg border border-slate-200/60 dark:border-neutral-700/50 p-1.5 sm:p-2">
                            <nav class="flex gap-1 sm:gap-2">
                                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                                    'relative flex-1 px-4 sm:px-8 py-3 sm:py-4 rounded-xl font-semibold text-sm sm:text-base whitespace-nowrap transition-all duration-300 group overflow-hidden',
                                    activeTab === tab.id
                                        ? 'bg-blue-600 dark:bg-blue-700 text-white shadow-lg scale-105'
                                        : 'text-slate-600 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-neutral-700/50'
                                ]">
                                    <!-- Icon and Text -->
                                    <div class="relative flex items-center justify-center space-x-2 sm:space-x-3">
                                        <span :class="[
                                            'text-lg sm:text-xl transition-transform duration-300',
                                            activeTab === tab.id ? 'scale-110' : 'group-hover:scale-110'
                                        ]">{{ tab.icon }}</span>
                                        <span class="font-medium">{{ tab.name }}</span>
                                    </div>

                                    <!-- Active Indicator -->
                                    <div v-if="activeTab === tab.id"
                                        class="absolute bottom-0 left-1/2 -translate-x-1/2 w-1/2 h-1 bg-white/40 rounded-t-full">
                                    </div>
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="space-y-6 sm:space-y-8">

                        <!-- Overview Tab -->
                        <div v-show="activeTab === 'overview'" class="space-y-4 sm:space-y-6">

                            <!-- Quick Actions -->
                            <section
                                class="bg-white dark:bg-neutral-800 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-neutral-700 p-4 sm:p-6">
                                <h2
                                    class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white mb-3 sm:mb-4 flex items-center">
                                    <span class="text-lg sm:text-xl mr-2 sm:mr-3">⚡</span>
                                    Quick Actions
                                </h2>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 sm:gap-4">
                                    <button v-for="action in quickActions" :key="action.type"
                                        @click="handleQuickAction(action.type)" :disabled="isRunningCleanup"
                                        class="group relative p-3 sm:p-4 rounded-lg sm:rounded-xl transition-all duration-200 transform hover:scale-105 disabled:scale-100 disabled:opacity-50 min-h-[80px] sm:min-h-[100px] flex flex-col justify-center"
                                        :class="action.gradient">
                                        <div class="text-lg sm:text-2xl mb-1 sm:mb-2">{{ action.icon }}</div>
                                        <div class="text-xs sm:text-sm font-semibold text-white">{{ action.name }}</div>
                                        <div class="text-xs text-white/80 mt-0.5 sm:mt-1">{{ action.description }}</div>
                                    </button>
                                </div>
                            </section>

                            <div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-2 gap-4 sm:gap-6">
                                <div v-for="(stat, key) in currentStats" :key="key"
                                    class="bg-white dark:bg-neutral-800 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-neutral-700 p-4 sm:p-6 hover:shadow-lg transition-all duration-200">
                                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                                        <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                                            <div class="text-xl sm:text-2xl flex-shrink-0">{{ stat.icon }}</div>
                                            <div class="min-w-0 flex-1">
                                                <h3
                                                    class="font-semibold text-slate-900 dark:text-white text-sm sm:text-base truncate">
                                                    {{ stat.name }}</h3>
                                                <p class="text-xs sm:text-sm text-slate-500 dark:text-gray-400">{{
                                                    stat.files
                                                    }} files</p>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <div class="text-sm sm:text-lg font-bold text-slate-900 dark:text-white">{{
                                                formatBytes(stat.size) }}</div>
                                        </div>
                                    </div>

                                    <div class="w-full bg-slate-200 dark:bg-neutral-700 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-1000" :style="{
                                            width: `${getUsagePercentage(stat.size)}%`,
                                            background: getColorGradient(stat.color)
                                        }">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Cards Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6">
                                <!-- System Info & Disk Usage -->
                                <div
                                    class="bg-white dark:bg-neutral-800 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-neutral-700 p-4 sm:p-6">
                                    <h3
                                        class="font-semibold text-slate-900 dark:text-white mb-3 sm:mb-4 text-sm sm:text-base">
                                        System Overview</h3>

                                    <!-- System Info Section -->
                                    <div class="space-y-2 sm:space-y-3 mb-4">
                                        <div class="flex justify-between text-xs sm:text-sm">
                                            <span class="text-slate-500 dark:text-gray-400">PHP</span>
                                            <span
                                                class="font-medium text-slate-900 dark:text-white font-mono text-right">
                                                {{ systemInfo?.php_version || 'Loading...' }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-xs sm:text-sm">
                                            <span class="text-slate-500 dark:text-gray-400">Laravel</span>
                                            <span
                                                class="font-medium text-slate-900 dark:text-white font-mono text-right">
                                                {{ systemInfo?.laravel_version || 'Loading...' }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-xs sm:text-sm">
                                            <span class="text-slate-500 dark:text-gray-400">Timezone</span>
                                            <div class="text-right">
                                                <span
                                                    class="font-medium text-slate-900 dark:text-white font-mono text-xs">
                                                    {{ systemInfo?.timezone || 'Loading...' }}
                                                </span>
                                                <div v-if="systemInfo?.is_timezone_detected"
                                                    class="text-xs text-emerald-500">
                                                    🌍 Auto-detected
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Divider -->
                                    <div class="border-t border-slate-200 dark:border-neutral-700 my-4"></div>

                                    <!-- Disk Usage Section -->
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-slate-500 dark:text-gray-400 text-xs sm:text-sm">Disk
                                                Usage</span>
                                            <div class="text-right">
                                                <div class="text-lg font-bold text-slate-900 dark:text-white">
                                                    {{ systemInfo?.disk_usage?.used_percentage || 0 }}%
                                                </div>
                                            </div>
                                        </div>

                                        <div class="w-full bg-slate-200 dark:bg-neutral-700 rounded-full h-2">
                                            <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full transition-all duration-1000"
                                                :style="{ width: `${systemInfo?.disk_usage?.used_percentage || 0}%` }">
                                            </div>
                                        </div>

                                        <div class="flex justify-between text-xs text-slate-500 dark:text-gray-400">
                                            <span>Free: {{ systemInfo?.disk_usage?.free || 'N/A' }}</span>
                                            <span>Total: {{ systemInfo?.disk_usage?.total || 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Log Files -->
                                <div class="bg-white dark:bg-neutral-800 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-neutral-700 p-4 sm:p-6"
                                    :class="{ 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/10': systemInfo?.logs?.total?.needs_attention }">
                                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                                        <h3 class="font-semibold text-slate-900 dark:text-white text-sm sm:text-base flex items-center"
                                            :class="{ 'text-red-600 dark:text-red-400': systemInfo?.logs?.total?.needs_attention }">
                                            <span class="mr-2">📋</span>
                                            Log Files
                                            <span v-if="systemInfo?.logs?.total?.needs_attention"
                                                class="ml-2 text-red-500"
                                                title="Logs exceed 20MB - needs attention">⚠️</span>
                                        </h3>
                                        <button @click="blankLogFiles" :disabled="isBlankingLogs"
                                            class="px-3 py-1.5 bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-900/20 dark:text-orange-400 dark:hover:bg-orange-900/40 rounded-lg text-xs font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                                            <svg v-if="!isBlankingLogs" class="w-3 h-3 mr-1" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                            <div v-else
                                                class="w-3 h-3 mr-1 animate-spin rounded-full border-2 border-orange-600 border-t-transparent">
                                            </div>
                                            {{ isBlankingLogs ? 'Blanking...' : 'Blank Logs' }}
                                        </button>
                                    </div>

                                    <div class="space-y-3">
                                        <!-- Total Size -->
                                        <div class="flex justify-between items-center text-xs sm:text-sm p-3 rounded-lg"
                                            :class="systemInfo?.logs?.total?.needs_attention ? 'bg-red-100 dark:bg-red-900/20' : 'bg-slate-100 dark:bg-neutral-800'">
                                            <span class="font-medium"
                                                :class="systemInfo?.logs?.total?.needs_attention ? 'text-red-700 dark:text-red-400' : 'text-slate-700 dark:text-gray-300'">Total
                                                Size</span>
                                            <span class="font-bold"
                                                :class="systemInfo?.logs?.total?.needs_attention ? 'text-red-700 dark:text-red-400' : 'text-slate-900 dark:text-white'">
                                                {{ systemInfo?.logs?.total?.formatted_size || '0 B' }}
                                            </span>
                                        </div>

                                        <!-- Laravel Application Logs -->
                                        <div class="flex justify-between text-xs sm:text-sm">
                                            <span class="text-slate-500 dark:text-gray-400">Laravel App Logs</span>
                                            <div class="text-right">
                                                <span class="font-medium text-slate-900 dark:text-white font-mono">
                                                    {{ systemInfo?.logs?.laravel?.formatted_size || '0 B' }}
                                                </span>
                                                <div v-if="systemInfo?.logs?.laravel?.count"
                                                    class="text-xs text-gray-400">
                                                    {{ systemInfo.logs.laravel.count }} file(s)
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cache Management Logs -->
                                        <div class="flex justify-between text-xs sm:text-sm">
                                            <span class="text-slate-500 dark:text-gray-400">Cache Mgmt Logs</span>
                                            <div class="text-right">
                                                <span class="font-medium text-slate-900 dark:text-white font-mono">
                                                    {{ systemInfo?.logs?.cache_management?.formatted_size || '0 B' }}
                                                </span>
                                                <div v-if="systemInfo?.logs?.cache_management?.count"
                                                    class="text-xs text-gray-400">
                                                    {{ systemInfo.logs.cache_management.count }} file(s)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cleanup Logs Tab -->
                        <div v-show="activeTab === 'activity'" class="space-y-4 sm:space-y-6">
                            <!-- Recent Activity -->
                            <div
                                class="bg-white dark:bg-neutral-800 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-neutral-700 p-4 sm:p-6">
                                <h3
                                    class="font-semibold text-slate-900 dark:text-white mb-3 sm:mb-4 text-sm sm:text-base">
                                    Recent Cleanups</h3>

                                <div v-if="recentCleanups && recentCleanups.length"
                                    class="space-y-2 sm:space-y-3 overflow-y-auto">
                                    <div v-for="(cleanup, index) in recentCleanups" :key="index"
                                        class="flex flex-col sm:flex-row sm:justify-between sm:items-center p-3 bg-slate-50 dark:bg-neutral-700 rounded-lg space-y-2 sm:space-y-0">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold text-slate-900 dark:text-white text-sm">{{
                                                cleanup.total_files
                                                }} files</div>
                                            <div class="text-xs sm:text-sm text-slate-500 dark:text-gray-400">{{
                                                cleanup.human_time }}
                                            </div>
                                        </div>
                                        <div class="flex justify-between sm:text-right">
                                            <div class="font-semibold text-emerald-600 dark:text-emerald-400 text-sm">{{
                                                cleanup.total_size }}</div>
                                            <div
                                                class="text-xs text-slate-500 dark:text-gray-400 font-mono sm:ml-2 sm:mt-1">
                                                {{
                                                    cleanup.timestamp }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="text-center py-6 sm:py-8">
                                    <div class="text-3xl sm:text-4xl mb-2 sm:mb-3">🧹</div>
                                    <p class="text-slate-500 dark:text-gray-400 text-sm">No cleanup activity yet</p>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Logs Tab -->
                        <div v-show="activeTab === 'activity_logs'" class="space-y-4 sm:space-y-6">
                            <div
                                class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-slate-200 dark:border-neutral-700 p-6">
                                <div class="rounded-2xl flex w-full items-center gap-2 mb-4">
                                    <div class="relative w-full">
                                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <input v-model="activitySearch" @input="onActivitySearchInput" type="text"
                                            placeholder="Search"
                                            class="w-1/2 pl-10 pr-4 py-3 border border-slate-300 dark:border-neutral-600 rounded-xl bg-white dark:bg-neutral-700 text-slate-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="confirmEmptyAll" v-if="logs?.total > 0"
                                            class="px-3 py-2 bg-red-600 text-sm rounded-xl text-white hover:bg-red-700 transition whitespace-nowrap">Empty
                                            Logs</button>
                                    </div>
                                    <div
                                        class="text-sm text-slate-500 dark:text-gray-400 bg-slate-100 dark:bg-neutral-700 px-3 py-4 rounded-2xl whitespace-nowrap text-center">
                                        Total: {{ logs?.total || 0 }} entries</div>
                                </div>

                                <div v-if="logs?.data?.length > 0" class="flex items-center justify-between mt-3 mb-4">
                                    <div class="flex items-center gap-3">
                                        <label
                                            class="inline-flex items-center space-x-2 text-sm text-slate-700 dark:text-gray-300">
                                            <input type="checkbox" v-model="selectAll" @change="toggleSelectAll"
                                                class="w-4 h-4 text-blue-600 border-slate-300 rounded" />
                                            <span>Select all on page</span>
                                        </label>
                                        <button v-if="selectedIds.length" @click="confirmDeleteSelected"
                                            class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">Delete
                                            Selected ({{ selectedIds.length }})</button>
                                    </div>
                                </div>

                                <div v-if="logs?.data?.length > 0" class="space-y-4">
                                    <div v-for="(log, index) in logs.data" :key="log.id"
                                        class="bg-white dark:bg-neutral-700 rounded-2xl shadow-sm border border-slate-200 dark:border-neutral-600 hover:shadow-md transition-all duration-200 overflow-hidden">
                                        <div class="p-4">
                                            <div
                                                class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                                <div class="flex items-start space-x-4 flex-1">
                                                    <div class="flex-shrink-0 flex items-center space-x-3">
                                                        <input type="checkbox" :value="log.id" v-model="selectedIds"
                                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded" />
                                                        <div
                                                            class="px-2 py-1 bg-gradient-to-r from-blue-100 to-purple-100 dark:from-blue-900/50 dark:to-purple-900/50 rounded-lg">
                                                            <span
                                                                class="text-xs font-semibold text-blue-600 dark:text-blue-400">#{{
                                                                    log.id }}</span>
                                                        </div>
                                                        <div class="font-semibold text-slate-900 dark:text-white">{{
                                                            log.description }}</div>
                                                        <div class="text-xs text-slate-500 dark:text-gray-400">{{
                                                            log.log_name }} • {{ log.causer?.name || 'System' }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex-shrink-0">
                                                    <button @click="toggleExpand(log.id)"
                                                        class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm">Details</button>
                                                </div>
                                            </div>

                                            <div v-if="expandedRows.includes(log.id)"
                                                class="mt-4 pt-4 border-t border-slate-200 dark:border-neutral-600 animate-fadeIn">
                                                <div v-if="log.properties && log.properties.attributes"
                                                    class="space-y-3">
                                                    <div class="text-sm text-slate-700 dark:text-gray-300">Updated:
                                                        {{
                                                            formatDate(log.created_at) }}</div>
                                                    <pre
                                                        class="bg-slate-50 dark:bg-neutral-900 p-3 rounded text-xs overflow-auto">{{ JSON.stringify(log.properties.attributes, null, 2) }}</pre>
                                                </div>
                                                <div v-else-if="log.properties && Object.keys(log.properties).length"
                                                    class="space-y-3">
                                                    <pre
                                                        class="bg-slate-50 dark:bg-neutral-900 p-3 rounded text-xs overflow-auto">{{ JSON.stringify(log.properties, null, 2) }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else
                                    class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-slate-200 dark:border-neutral-700 p-12 text-center">
                                    <div
                                        class="w-20 h-20 mx-auto bg-slate-100 dark:bg-neutral-700 rounded-full flex items-center justify-center mb-6">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">No activity
                                        logs found</h3>
                                    <p class="text-slate-600 dark:text-gray-400 mb-6 max-w-md mx-auto">{{
                                        activitySearch
                                            ? 'Try adjusting your search terms' : 'No activities have been logged yet'
                                    }}
                                    </p>
                                </div>

                                <!-- Pagination (simple) -->
                                <div v-if="logs?.data?.length > 0" class="mt-4">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Showing {{ logs.from
                                        }} to
                                            {{ logs.to }} of {{ logs.total }} entries</div>
                                        <div class="flex items-center space-x-2">
                                            <button @click="changePage(logs.current_page - 1)"
                                                :disabled="!logs.prev_page_url"
                                                class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center"
                                                :class="logs.prev_page_url ? 'bg-white dark:bg-neutral-800' : 'text-gray-400 cursor-not-allowed'">Previous</button>
                                            <div
                                                class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg">
                                                Page {{ logs.current_page }} of {{ logs.last_page }}</div>
                                            <button @click="changePage(logs.current_page + 1)"
                                                :disabled="!logs.next_page_url"
                                                class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center"
                                                :class="logs.next_page_url ? 'bg-white dark:bg-neutral-800' : 'text-gray-400 cursor-not-allowed'">Next</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </AppLayout>

</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import timezoneDetector from '@/utils/timezone.js'

// Props
const props = defineProps({
    stats: Object,
    recentCleanups: Array,
    systemInfo: Object,
    lastCleanup: Object
})

// Reactive state
const currentStats = ref(props.stats || {})
const systemInfo = ref(props.systemInfo || {})
const recentCleanups = ref(props.recentCleanups || [])
const isRefreshing = ref(false)
const isRunningCleanup = ref(false)
const activeTab = ref('overview')
const isBlankingLogs = ref(false)

// Live clock variables
const currentTime = ref('')
const currentTimezone = ref('')
const clockInterval = ref(null)

// Tab configuration
const tabs = ref([
    { id: 'overview', name: 'Overview', icon: '📊' },
    { id: 'activity', name: 'Cleanup Logs', icon: '🕐' },
    { id: 'activity_logs', name: 'Activity Log', icon: '📋' }
])

// Quick actions configuration
const quickActions = ref([
    { type: 'all', name: 'Clean All', description: 'Complete cleanup', icon: '🧹', gradient: 'bg-emerald-600 dark:bg-emerald-700' },
    { type: 'storage', name: 'Storage', description: 'Temp files', icon: '📁', gradient: 'bg-amber-600 dark:bg-amber-700' },
    { type: 'logs', name: 'Logs', description: 'Old log files', icon: '📋', gradient: 'bg-red-600 dark:bg-red-700' },
    { type: 'temp', name: 'Temp Files', description: 'Upload temps', icon: '🗃️', gradient: 'bg-purple-600 dark:bg-purple-700' },
    { type: 'artisan', name: 'Artisan Clear', description: 'Clear & cache configs', icon: '⚡', gradient: 'bg-indigo-600 dark:bg-indigo-700' },
    { type: 'view-logs', name: 'View Logs', description: 'Browse log files', icon: '👀', gradient: 'bg-teal-600 dark:bg-teal-700' }
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

const getColorGradient = (color) => {
    const colors = {
        'blue': '#3b82f6',
        'green': '#10b981',
        'yellow': '#f59e0b',
        'purple': '#8b5cf6',
        'red': '#ef4444',
        'indigo': '#6366f1',
        'pink': '#ec4899',
        'teal': '#14b8a6',
        'orange': '#f97316',
        'emerald': '#10b981'
    }
    return colors[color] || colors['blue']
}

const refreshSystemInfo = async () => {
    try {
        const response = await fetch('/api/cache-management/system-info')

        if (response.url.includes('/login') || response.status === 401 || response.status === 419) {
            window.location.href = '/login'
            return
        }

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`)
        }

        const data = await response.json()
        systemInfo.value = data
    } catch (error) {
        console.error('Failed to refresh system info:', error)
    }
}

const refreshRecentCleanups = async () => {
    try {
        const response = await fetch('/api/cache-management/recent-cleanups')

        if (response.url.includes('/login') || response.status === 401 || response.status === 419) {
            window.location.href = '/login'
            return
        }

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`)
        }

        const data = await response.json()
        if (data.success) {
            recentCleanups.value = data.data
        }
    } catch (error) {
        console.error('Failed to refresh recent cleanups:', error)
    }
}

const refreshStats = async (showSuccessToast = false, setLoading = true) => {
    if (setLoading) isRefreshing.value = true
    try {
        const response = await fetch('/api/cache-management/stats')

        if (response.url.includes('/login') || response.status === 401 || response.status === 419) {
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
        if (showSuccessToast) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Failed to refresh stats',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            })
        }
    } finally {
        if (setLoading) isRefreshing.value = false
    }
}

const refreshAllData = async () => {
    isRefreshing.value = true
    try {
        // Refresh stats and recent cleanups
        await Promise.all([
            refreshStats(false, false), // Don't set loading individually
            refreshRecentCleanups()
        ])

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'All data refreshed successfully',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        })
    } catch (error) {
        console.error('Failed to refresh data:', error)
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'Failed to refresh data',
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
        const response = await fetch('/api/cache-management/server-time')
        if (response.ok) {
            const data = await response.json()

            // Set timezone from server
            currentTimezone.value = data.timezone

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
    currentTimezone.value = Intl.DateTimeFormat().resolvedOptions().timeZone
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

// Quick action handler
const handleQuickAction = (actionType) => {
    switch (actionType) {
        case 'artisan':
            runArtisanClear()
            break
        case 'view-logs':
            navigateToLogs()
            break
        default:
            runCleanup(actionType)
            break
    }
}

// Navigate to log viewer
const navigateToLogs = () => {
    router.get(route('logs.index'))
}

const runArtisanClear = async () => {
    isRunningCleanup.value = true

    Swal.fire({
        title: 'Running Artisan Commands',
        html: `<div class="text-lg">Clearing and rebuilding Laravel cache...</div>`,
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

        const response = await fetch('/cache-management/run-artisan-clear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })

        const data = await response.json()

        if (data.success) {
            Swal.fire({
                title: 'Artisan Commands Completed!',
                html: `
                    <div class="text-lg mb-4">✅ Laravel caches cleared and rebuilt</div>
                    <div class="text-sm text-slate-600">
                        <div>• Cache cleared</div>
                        <div>• Config cleared</div>
                        <div>• Route cleared</div>
                        <div>• View cleared</div>
                        <div>• Config cached</div>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Great!',
                confirmButtonColor: '#10b981'
            })

            await refreshStats()
        } else {
            throw new Error(data.message || 'Artisan commands failed')
        }
    } catch (error) {
        console.error('Artisan commands failed:', error)
        Swal.fire({
            title: 'Artisan Commands Failed',
            text: error.message || 'An error occurred while running artisan commands',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ef4444'
        })
    } finally {
        isRunningCleanup.value = false
    }
}

const runCleanup = async (type) => {
    isRunningCleanup.value = true

    console.log('Starting cleanup with type:', type)

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
        console.log('CSRF token:', csrfToken)

        const requestData = { type }
        console.log('Request data:', requestData)

        const response = await fetch('/cache-management/run-cleanup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(requestData)
        })

        console.log('Response status:', response.status)
        console.log('Response headers:', response.headers)
        console.log('Response URL:', response.url)

        // Only treat explicit login redirects or auth failures as a signal to go to login
        if (response.url.includes('/login') || response.status === 401 || response.status === 419) {
            console.error('Authentication failed - redirected to login')
            window.location.href = '/login'
            return
        }

        // Check if response is not ok
        if (!response.ok) {
            console.error('HTTP error:', response.status, response.statusText)
            throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }

        // Get response text first to debug
        const responseText = await response.text()
        console.log('Raw response:', responseText)

        // Try to parse as JSON
        let data
        try {
            data = JSON.parse(responseText)
        } catch (jsonError) {
            console.error('JSON parse error:', jsonError)
            console.error('Response was not valid JSON:', responseText.substring(0, 500))
            throw new Error('Server returned invalid JSON. Check console for details.')
        }

        console.log('Parsed response data:', data)

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

            // Refresh both stats and recent cleanups
            await Promise.all([
                refreshStats(),
                refreshRecentCleanups()
            ])
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

const blankLogFiles = async () => {
    isBlankingLogs.value = true

    Swal.fire({
        title: 'Blanking Log Files',
        html: `<div class="text-lg">Clearing log file contents...</div>`,
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

        const response = await fetch('/cache-management/blank-logs', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })

        const data = await response.json()

        if (data.success) {
            Swal.fire({
                title: 'Logs Blanked Successfully!',
                html: `
                    <div class="text-lg mb-4">✅ Log files have been blanked</div>
                    <div class="text-md">📋 Files processed: ${data.processed_files || 0}</div>
                    <div class="text-sm text-slate-500 mt-2">Files preserved but contents cleared</div>
                `,
                icon: 'success',
                confirmButtonText: 'Great!',
                confirmButtonColor: '#10b981'
            })

            await refreshStats()
            await refreshSystemInfo()
        } else {
            throw new Error(data.message || 'Log blanking failed')
        }
    } catch (error) {
        console.error('Log blanking failed:', error)
        Swal.fire({
            title: 'Log Blanking Failed',
            text: error.message || 'An error occurred while blanking logs',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ef4444'
        })
    } finally {
        isBlankingLogs.value = false
    }
}

// Activity Logs tab state & methods (adapted from ActivityLogs/Index.vue)
const logs = ref({ data: [] })
const activitySearch = ref('')
const expandedRows = ref([])
const selectedIds = ref([])
const selectAll = ref(false)

const loadActivityLogs = async (page = 1) => {
    try {
        const url = `/activity-logs?page=${page}&search=${encodeURIComponent(activitySearch.value)}`
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        })

        if (!response.ok) {
            logs.value = { data: [] }
            return
        }

        const payload = await response.json()

        // Inertia responses may nest props in several shapes. Try common locations:
        // - payload.props.logs
        // - payload.page.props.logs
        // - payload.logs (plain JSON)
        // - payload.data (paginated JSON)
        let logsData = null

        if (payload?.props?.logs) logsData = payload.props.logs
        else if (payload?.page?.props?.logs) logsData = payload.page.props.logs
        else if (payload?.logs) logsData = payload.logs
        else if (payload?.data) logsData = payload
        else logsData = payload

        // Ensure we always have a paginated shape (fallback to empty page)
        logs.value = logsData ?? { data: [] }
    } catch (e) {
        console.error('Failed to load activity logs:', e)
        logs.value = { data: [] }
    }
}

function changePage(page) {
    if (!page || page < 1) return
    loadActivityLogs(page)
}

function onActivitySearchInput() {
    loadActivityLogs(1)
}

function toggleExpand(id) {
    if (expandedRows.value.includes(id)) {
        expandedRows.value = expandedRows.value.filter(rowId => rowId !== id)
    } else {
        expandedRows.value.push(id)
    }
}

function toggleSelectAll() {
    if (!logs.value || !logs.value.data) return
    const pageIds = logs.value.data.map(l => l.id)
    if (selectAll.value) {
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...pageIds]))
    } else {
        selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id))
    }
}

function confirmDeleteSelected() {
    if (!selectedIds.value.length) return

    Swal.fire({
        title: `Delete ${selectedIds.value.length} log(s)?`,
        text: 'This action cannot be undone. Are you sure you want to proceed?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
    }).then(result => {
        if (result.isConfirmed) {
            deleteSelected()
        }
    })
}

function deleteSelected() {
    router.post('/activity-logs/bulk-delete', { ids: selectedIds.value }, {
        preserveState: false,
        onSuccess: () => {
            Swal.fire('Deleted', 'Selected logs have been deleted.', 'success')
            loadActivityLogs(1)
            selectedIds.value = []
            selectAll.value = false
        },
        onError: () => {
            Swal.fire('Error', 'Could not delete selected logs.', 'error')
        }
    })
}

function confirmEmptyAll() {
    Swal.fire({
        title: 'Delete ALL logs?',
        text: 'This will permanently delete all activity logs. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete all',
        cancelButtonText: 'Cancel',
    }).then(result => {
        if (result.isConfirmed) {
            emptyAll()
        }
    })
}

function emptyAll() {
    router.post('/activity-logs/empty', {}, {
        preserveState: false,
        onSuccess: () => {
            Swal.fire('Deleted', 'All activity logs have been deleted.', 'success')
            loadActivityLogs(1)
        },
        onError: () => {
            Swal.fire('Error', 'Could not delete logs.', 'error')
        }
    })
}

function formatDate(dateStr) {
    const date = new Date(dateStr)
    const now = new Date()
    const diffInHours = (now - date) / (1000 * 60 * 60)

    if (diffInHours < 1) {
        const minutes = Math.floor(diffInHours * 60)
        return `${minutes} min${minutes !== 1 ? 's' : ''} ago`
    } else if (diffInHours < 24) {
        const hours = Math.floor(diffInHours)
        return `${hours} hour${hours !== 1 ? 's' : ''} ago`
    } else if (diffInHours < 24 * 7) {
        const days = Math.floor(diffInHours / 24)
        return `${days} day${days !== 1 ? 's' : ''} ago`
    } else {
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        })
    }
}

// Load activity logs when tab becomes active
watch(activeTab, (val) => {
    if (val === 'activity_logs') {
        loadActivityLogs(1)
    }
})

// Auto-refresh stats every 30 seconds
onMounted(() => {
    // Initialize timezone detection
    timezoneDetector.init()

    // Start live clock
    startLiveClock()

    setInterval(() => {
        if (!isRunningCleanup.value && !isRefreshing.value) {
            refreshStats(false, false) // Auto refresh without loading state or toast
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

/* Shimmer animation for active tab */
@keyframes shimmer {
    0% {
        transform: translateX(-100%) skewX(-12deg);
    }

    100% {
        transform: translateX(200%) skewX(-12deg);
    }
}

.animate-shimmer {
    animation: shimmer 3s infinite;
}
</style>