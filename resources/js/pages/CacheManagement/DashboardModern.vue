<template>

    <Head title="Server Management" />

    <!-- Modern Minimal Background -->
    <div class="min-h-screen bg-white dark:bg-black animate-fadeIn font-mono">
            <!-- Clean Header -->
            <div class="container mx-auto px-4 max-w-8xl">
                <header class="backdrop-blur-xl sticky top-0 z-50 bg-white dark:bg-black">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                            <div class="flex items-center space-x-3 sm:space-x-4">
                                <div class="min-w-0 flex-1">
                                    <h1 class="text-xl sm:text-2xl md:text-3xl font-light text-black dark:text-white uppercase tracking-[0.08em]"
                                        style="font-family: 'Space Grotesk', sans-serif;">
                                        Server Management
                                    </h1>
                                    <p
                                        class="text-[#666666] dark:text-[#999999] text-[10px] sm:text-xs hidden sm:block font-mono uppercase tracking-[0.08em] mt-1">
                                        System cleanup and monitoring
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end space-x-2 sm:space-x-3">
                                <!-- Live Server Time Card -->
                                <div
                                    class="bg-white dark:bg-[#111111] rounded-lg px-3 sm:px-4 py-2 sm:py-2.5 text-right flex-shrink-0 border border-[#E8E8E8] dark:border-[#222222]">
                                    <p
                                        class="text-[10px] text-[#666666] dark:text-[#999999] hidden sm:block font-mono uppercase tracking-[0.08em] mb-1">
                                        Server Time
                                    </p>
                                    <div class="text-xs sm:text-sm font-mono tabular-nums text-black dark:text-white"
                                        style="font-family: 'Doto', 'Space Mono', monospace; font-variation-settings: 'DOTS' 20;">
                                        {{ currentTime || 'Loading...' }}
                                    </div>
                                    <p
                                        class="text-[9px] text-[#999999] dark:text-[#1f1d1d] mt-1 hidden sm:block font-mono uppercase tracking-wider">
                                        {{ currentTimezone }}
                                    </p>
                                </div>
                            </div>

                            <!-- Refresh Button -->
                            <button @click="refreshAllData()" :disabled="isRefreshing"
                                class="px-3 sm:px-4 py-2 bg-black dark:bg-white disabled:bg-[#CCCCCC] dark:disabled:bg-[#333333] text-white dark:text-black rounded-full border border-black dark:border-white transition-all duration-200 hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white flex items-center gap-1.5 flex-shrink-0 text-[10px] font-mono uppercase tracking-[0.08em]">
                                <svg :class="{ 'animate-spin': isRefreshing }" class="w-3.5 h-3.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                                <span>{{ isRefreshing ? 'Refreshing...' : 'Refresh' }}</span>
                            </button>
                        </div>
                    </div>
                </header>

                <!-- Main Content -->
                <main class="max-w-7xl mx-auto px-4 sm:px-6 py-4 sm:py-6">

                    <!-- Tab Navigation -->
                    <div class="mb-6">
                        <div
                            class="bg-white dark:bg-[#111111] rounded-lg p-1.5 border border-[#E8E8E8] dark:border-[#222222]">
                            <nav class="flex gap-1">
                                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                                    'relative flex-1 px-4 sm:px-6 py-2.5 sm:py-3 rounded-md font-mono uppercase tracking-[0.08em] text-[10px] sm:text-xs whitespace-nowrap transition-all duration-200 group overflow-hidden',
                                    activeTab === tab.id
                                        ? 'bg-black dark:bg-white text-white dark:text-black'
                                        : 'text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white'
                                ]">
                                    <!-- Icon and Text -->
                                    <div class="relative flex items-center justify-center gap-2">
                                        <span :class="[
                                            'text-sm sm:text-base transition-transform duration-300',
                                            activeTab === tab.id ? 'scale-110' : 'group-hover:scale-110'
                                        ]">{{ tab.icon }}</span>
                                        <span>{{ tab.name }}</span>
                                    </div>
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="space-y-6">

                        <!-- Overview Tab -->
                        <div v-show="activeTab === 'overview'" class="space-y-4 sm:space-y-5">

                            <!-- Quick Actions -->
                            <section
                                class="bg-white dark:bg-[#111111] rounded-lg border border-[#E8E8E8] dark:border-[#222222] p-4 sm:p-5">
                                <h2
                                    class="text-xs font-mono uppercase tracking-[0.08em] text-[#666666] dark:text-[#999999] mb-4 flex items-center gap-2">
                                    <span class="text-base">⚡</span>
                                    Quick Actions
                                </h2>

                                <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-3 gap-3">
                                    <button v-for="action in quickActions" :key="action.type"
                                        @click="handleQuickAction(action.type)" :disabled="isRunningCleanup"
                                        class="group relative p-3 sm:p-4 rounded-lg transition-all duration-200 transform hover:scale-[1.02] disabled:scale-100 disabled:opacity-50 min-h-[90px] sm:min-h-[100px] flex flex-col justify-center"
                                        :class="action.gradient">
                                        <div class="text-lg sm:text-xl mb-1.5">{{ action.icon }}</div>
                                        <div class="text-xs sm:text-sm font-mono font-medium uppercase tracking-wide">{{
                                            action.name }}</div>
                                        <div
                                            class="text-[10px] mt-1 font-mono text-[#666666] dark:text-[#999999] uppercase tracking-wider">
                                            {{ action.description }}</div>
                                    </button>
                                </div>
                            </section>

                            <!-- Cache Stats Grid - Nothing Design -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="(stat, key) in currentStats" :key="key"
                                    class="bg-white dark:bg-[#111111] rounded-lg border border-[#E8E8E8] dark:border-[#222222] p-4 sm:p-5 hover:border-[#CCCCCC] dark:hover:border-[#333333] transition-colors duration-200">

                                    <!-- Icon + Label Row -->
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl">{{ stat.icon }}</span>
                                            <div>
                                                <h3
                                                    class="text-xs font-mono uppercase tracking-[0.08em] text-[#666666] dark:text-[#999999]">
                                                    {{ stat.name }}
                                                </h3>
                                                <p
                                                    class="text-[10px] font-mono text-[#999999] dark:text-[#666666] mt-0.5">
                                                    {{ stat.files }} FILES
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dot Matrix Size Display -->
                                    <div class="mb-3">
                                        <DotMatrixNumber :value="formatBytes(stat.size)" size="sm" />
                                    </div>

                                    <!-- Segmented Progress Bar -->
                                    <SegmentedProgressBar :percentage="getUsagePercentage(stat.size)" :segments="16" />
                                </div>
                            </div>

                            <!-- Status Cards Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6">
                                <!-- System Info & Disk Usage -->
                                <div
                                    class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4 sm:p-6">
                                    <h3
                                        class="font-semibold text-black dark:text-white mb-3 sm:mb-4 text-sm sm:text-base uppercase tracking-wide">
                                        System Overview</h3>

                                    <!-- System Info Section -->
                                    <div class="space-y-2 sm:space-y-3 mb-4">
                                        <div class="flex justify-between text-xs sm:text-sm">
                                            <span
                                                class="text-[#666666] dark:text-[#999999] font-mono uppercase tracking-wider">PHP</span>
                                            <span class="font-medium text-black dark:text-white font-mono text-right">
                                                {{ systemInfo?.php_version || 'Loading...' }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-xs sm:text-sm">
                                            <span
                                                class="text-[#666666] dark:text-[#999999] font-mono uppercase tracking-wider">Laravel</span>
                                            <span class="font-medium text-black dark:text-white font-mono text-right">
                                                {{ systemInfo?.laravel_version || 'Loading...' }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-xs sm:text-sm">
                                            <span
                                                class="text-[#666666] dark:text-[#999999] font-mono uppercase tracking-wider">Timezone</span>
                                            <div class="text-right">
                                                <span class="font-medium text-black dark:text-white font-mono text-xs">
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
                                    <div class="border-t-2 border-[#E8E8E8] dark:border-[#222222] my-4"></div>

                                    <!-- Disk Usage Section -->
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-baseline">
                                            <span
                                                class="text-xs font-mono uppercase tracking-[0.08em] text-[#666666] dark:text-[#999999]">
                                                Disk Usage
                                            </span>
                                            <DotMatrixNumber :value="systemInfo?.disk_usage?.used_percentage || 0"
                                                unit="%" size="sm" />
                                        </div>

                                        <!-- Segmented Progress Bar -->
                                        <SegmentedProgressBar :percentage="systemInfo?.disk_usage?.used_percentage || 0"
                                            :segments="20"
                                            :color="(systemInfo?.disk_usage?.used_percentage || 0) > 80 ? 'accent' : 'default'" />

                                        <div
                                            class="flex justify-between text-[10px] font-mono text-[#666666] dark:text-[#999999] uppercase tracking-wider">
                                            <span>Free: {{ systemInfo?.disk_usage?.free || 'N/A' }}</span>
                                            <span>Total: {{ systemInfo?.disk_usage?.total || 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Log Files -->
                                <div class="bg-white dark:bg-[#111111] rounded-lg border border-[#E8E8E8] dark:border-[#222222] p-4 sm:p-5"
                                    :class="{ 'border-[#D71921]/30 dark:border-[#D71921]/30 bg-red-50/30 dark:bg-red-900/5': systemInfo?.logs?.total?.needs_attention }">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-xs font-mono uppercase tracking-[0.08em] flex items-center gap-2"
                                            :class="systemInfo?.logs?.total?.needs_attention ? 'text-[#D71921]' : 'text-[#666666] dark:text-[#999999]'">
                                            <span class="text-base">📋</span>
                                            Log Files
                                            <span v-if="systemInfo?.logs?.total?.needs_attention"
                                                class="text-[#D71921] text-xs"
                                                title="Logs exceed 20MB - needs attention">⚠️</span>
                                        </h3>
                                        <div class="flex items-center gap-2">
                                            <a href="/logs"
                                                class="px-3 py-1.5 bg-white dark:bg-black border border-black dark:border-white text-black dark:text-white rounded-full text-[10px] font-mono uppercase tracking-[0.08em] transition-colors hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                    </path>
                                                </svg>
                                                View
                                            </a>
                                            <button @click="blankLogFiles" :disabled="isBlankingLogs"
                                                class="px-3 py-1.5 bg-white dark:bg-black border border-[#D71921] text-[#D71921] rounded-full text-[10px] font-mono uppercase tracking-[0.08em] transition-colors hover:bg-[#D71921] hover:text-white disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1">
                                                <svg v-if="!isBlankingLogs" class="w-3 h-3" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                                <div v-else
                                                    class="w-3 h-3 animate-spin rounded-full border-2 border-[#D71921] border-t-transparent">
                                                </div>
                                                {{ isBlankingLogs ? 'Blanking...' : 'Blank' }}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <!-- Total Size -->
                                        <div class="flex justify-between items-center text-xs sm:text-sm p-3 rounded-lg"
                                            :class="systemInfo?.logs?.total?.needs_attention ? 'bg-[#D71921]/10 dark:bg-[#D71921]/10' : 'bg-[#F5F5F5] dark:bg-black'">
                                            <span class="font-mono uppercase tracking-[0.08em]"
                                                :class="systemInfo?.logs?.total?.needs_attention ? 'text-[#D71921]' : 'text-[#666666] dark:text-[#999999]'">Total
                                                Size</span>
                                            <span class="font-mono font-medium tabular-nums"
                                                :class="systemInfo?.logs?.total?.needs_attention ? 'text-[#D71921]' : 'text-black dark:text-white'">
                                                {{ systemInfo?.logs?.total?.formatted_size || '0 B' }}
                                            </span>
                                        </div>

                                        <!-- Laravel Application Logs -->
                                        <div class="flex justify-between text-xs sm:text-sm">
                                            <span
                                                class="text-[#666666] dark:text-[#999999] font-mono uppercase tracking-[0.08em]">Laravel
                                                App</span>
                                            <div class="text-right">
                                                <span class="font-mono text-black dark:text-white tabular-nums">
                                                    {{ systemInfo?.logs?.laravel?.formatted_size || '0 B' }}
                                                </span>
                                                <div v-if="systemInfo?.logs?.laravel?.count"
                                                    class="text-[10px] text-[#999999] dark:text-[#666666] font-mono uppercase tracking-wider">
                                                    {{ systemInfo.logs.laravel.count }} file(s)
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cache Management Logs -->
                                        <div class="flex justify-between text-xs sm:text-sm">
                                            <span
                                                class="text-[#666666] dark:text-[#999999] font-mono uppercase tracking-[0.08em]">Cache
                                                Mgmt</span>
                                            <div class="text-right">
                                                <span class="font-mono text-black dark:text-white tabular-nums">
                                                    {{ systemInfo?.logs?.cache_management?.formatted_size || '0 B' }}
                                                </span>
                                                <div v-if="systemInfo?.logs?.cache_management?.count"
                                                    class="text-[10px] text-[#999999] dark:text-[#666666] font-mono uppercase tracking-wider">
                                                    {{ systemInfo.logs.cache_management.count }} file(s)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cleanup Logs Tab -->
                        <div v-show="activeTab === 'activity'" class="space-y-4">
                            <!-- Recent Activity -->
                            <div
                                class="bg-white dark:bg-[#111111] rounded-lg border border-[#E8E8E8] dark:border-[#222222] p-4 sm:p-5">
                                <h3
                                    class="text-xs font-mono uppercase tracking-[0.08em] text-[#666666] dark:text-[#999999] mb-4">
                                    Recent Cleanups
                                </h3>

                                <div v-if="recentCleanups && recentCleanups.length" class="space-y-3 overflow-y-auto">
                                    <div v-for="(cleanup, index) in recentCleanups" :key="index"
                                        class="flex flex-col sm:flex-row sm:justify-between sm:items-center p-3 bg-[#F5F5F5] dark:bg-black rounded-lg space-y-2 sm:space-y-0 border border-[#E8E8E8] dark:border-[#222222]">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-mono text-black dark:text-white">
                                                {{ cleanup.total_files }} files
                                            </div>
                                            <div
                                                class="text-[10px] font-mono text-[#666666] dark:text-[#999999] uppercase tracking-wider">
                                                {{ cleanup.human_time }}
                                            </div>
                                        </div>
                                        <div class="flex justify-between sm:flex-col sm:text-right sm:items-end gap-2">
                                            <div
                                                class="font-mono font-medium text-emerald-600 dark:text-emerald-400 text-sm">
                                                {{ cleanup.total_size }}
                                            </div>
                                            <div
                                                class="text-[10px] text-[#999999] dark:text-[#666666] font-mono tabular-nums uppercase tracking-wider">
                                                {{ cleanup.timestamp }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="text-center py-8 sm:py-10">
                                    <div class="text-3xl sm:text-4xl mb-3">🧹</div>
                                    <p
                                        class="text-xs font-mono uppercase tracking-[0.08em] text-[#999999] dark:text-[#666666]">
                                        No cleanup activity yet</p>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Logs Tab -->
                        <div v-show="activeTab === 'activity_logs'" class="space-y-4">
                            <div
                                class="bg-white dark:bg-[#111111] rounded-lg border border-[#E8E8E8] dark:border-[#222222] p-4 sm:p-5">
                                <div class="rounded-lg flex w-full items-center gap-2 mb-4">
                                    <div class="relative w-full">
                                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-[#999999] dark:text-[#666666] w-4 h-4"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <input v-model="activitySearch" @input="onActivitySearchInput" type="text"
                                            placeholder="Search logs..."
                                            class="w-1/2 pl-10 pr-4 py-2.5 border border-[#E8E8E8] dark:border-[#222222] rounded-lg bg-white dark:bg-black text-black dark:text-white placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white transition-colors duration-200 font-mono text-xs uppercase tracking-[0.08em]" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="confirmEmptyAll" v-if="logs?.total > 0"
                                            class="px-3 py-2.5 bg-white dark:bg-black border border-[#D71921] text-[#D71921] text-[10px] font-mono uppercase tracking-[0.08em] rounded-full hover:bg-[#D71921] hover:text-white transition-colors whitespace-nowrap">
                                            Empty Logs
                                        </button>
                                    </div>
                                    <div
                                        class="text-[10px] font-mono uppercase tracking-[0.08em] text-[#666666] dark:text-[#999999] bg-[#F5F5F5] dark:bg-black px-3 py-2.5 rounded-lg border border-[#E8E8E8] dark:border-[#222222] whitespace-nowrap text-center">
                                        Total: {{ logs?.total || 0 }}
                                    </div>
                                </div>

                                <div v-if="logs?.data?.length > 0" class="flex items-center justify-between mt-3 mb-4">
                                    <div class="flex items-center gap-3">
                                        <label
                                            class="inline-flex items-center space-x-2 text-[10px] font-mono uppercase tracking-[0.08em] text-[#666666] dark:text-[#999999]">
                                            <input type="checkbox" v-model="selectAll" @change="toggleSelectAll"
                                                class="w-4 h-4 text-black border-[#CCCCCC] dark:border-[#333333] rounded" />
                                            <span>Select all</span>
                                        </label>
                                        <button v-if="selectedIds.length" @click="confirmDeleteSelected"
                                            class="px-3 py-2 bg-white dark:bg-black border border-[#D71921] text-[#D71921] rounded-full text-[10px] font-mono uppercase tracking-[0.08em] hover:bg-[#D71921] hover:text-white transition-colors">
                                            Delete ({{ selectedIds.length }})
                                        </button>
                                    </div>
                                </div>

                                <div v-if="logs?.data?.length > 0" class="space-y-3">
                                    <div v-for="(log, index) in logs.data" :key="log.id"
                                        class="bg-white dark:bg-[#111111] rounded-lg border border-[#E8E8E8] dark:border-[#222222] hover:border-[#CCCCCC] dark:hover:border-[#333333] transition-colors duration-200 overflow-hidden">
                                        <div class="p-4">
                                            <div
                                                class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                                <div class="flex items-start space-x-4 flex-1">
                                                    <div class="flex-shrink-0 flex items-center space-x-3">
                                                        <input type="checkbox" :value="log.id" v-model="selectedIds"
                                                            class="w-4 h-4 text-black border-[#CCCCCC] dark:border-[#333333] rounded" />
                                                        <div
                                                            class="px-2 py-1 bg-[#F5F5F5] dark:bg-black border border-[#E8E8E8] dark:border-[#222222] rounded">
                                                            <span
                                                                class="text-[10px] font-mono font-medium text-black dark:text-white tabular-nums">
                                                                #{{ log.id }}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <div class="font-mono text-sm text-black dark:text-white">
                                                                {{ log.description }}
                                                            </div>
                                                            <div
                                                                class="text-[10px] text-[#666666] dark:text-[#999999] font-mono uppercase tracking-[0.08em] mt-1">
                                                                {{ log.log_name }} • {{ log.causer?.name || 'System' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex-shrink-0">
                                                    <button @click="toggleExpand(log.id)"
                                                        class="px-3 py-2 bg-black dark:bg-white text-white dark:text-black border border-black dark:border-white rounded-full text-[10px] font-mono uppercase tracking-[0.08em] hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white transition-colors">
                                                        Details
                                                    </button>
                                                </div>
                                            </div>

                                            <div v-if="expandedRows.includes(log.id)"
                                                class="mt-4 pt-4 border-t border-[#E8E8E8] dark:border-[#222222] animate-fadeIn">
                                                <div v-if="log.properties && log.properties.attributes"
                                                    class="space-y-3">
                                                    <div class="text-xs font-mono text-[#666666] dark:text-[#999999]">
                                                        Updated: {{ formatDate(log.created_at) }}
                                                    </div>
                                                    <pre
                                                        class="bg-[#F5F5F5] dark:bg-black p-3 rounded text-[10px] font-mono overflow-auto border border-[#E8E8E8] dark:border-[#222222]">{{ JSON.stringify(log.properties.attributes, null, 2) }}</pre>
                                                </div>
                                                <div v-else-if="log.properties && Object.keys(log.properties).length"
                                                    class="space-y-3">
                                                    <pre
                                                        class="bg-[#F5F5F5] dark:bg-black p-3 rounded text-[10px] font-mono overflow-auto border border-[#E8E8E8] dark:border-[#222222]">{{ JSON.stringify(log.properties, null, 2) }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else
                                    class="bg-white dark:bg-[#111111] rounded-lg border border-[#E8E8E8] dark:border-[#222222] p-12 text-center">
                                    <div
                                        class="w-16 h-16 mx-auto bg-[#F5F5F5] dark:bg-black rounded-full flex items-center justify-center mb-4 border border-[#E8E8E8] dark:border-[#222222]">
                                        <svg class="w-8 h-8 text-[#999999] dark:text-[#666666]" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3
                                        class="text-sm font-mono uppercase tracking-[0.08em] text-black dark:text-white mb-2">
                                        No activity logs found
                                    </h3>
                                    <p
                                        class="text-xs font-mono text-[#666666] dark:text-[#999999] uppercase tracking-[0.08em]">
                                        {{ activitySearch ? 'Try adjusting your search' : 'No activities logged yet' }}
                                    </p>
                                </div>

                                <!-- Pagination -->
                                <div v-if="logs?.data?.length > 0" class="mt-4">
                                    <div class="flex items-center justify-between">
                                        <div
                                            class="text-[10px] font-mono text-[#666666] dark:text-[#999999] uppercase tracking-[0.08em]">
                                            Showing {{ logs.from }} to {{ logs.to }} of {{ logs.total }}
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button @click="changePage(logs.current_page - 1)"
                                                :disabled="!logs.prev_page_url"
                                                class="px-3 py-2 text-[10px] rounded-full border transition-colors duration-200 flex items-center font-mono uppercase tracking-[0.08em]"
                                                :class="logs.prev_page_url ? 'bg-white dark:bg-black text-black dark:text-white border-black dark:border-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black' : 'text-[#CCCCCC] cursor-not-allowed border-[#CCCCCC] dark:border-[#333333]'">
                                                Prev
                                            </button>
                                            <div
                                                class="px-4 py-2 text-[10px] font-mono uppercase tracking-[0.08em] bg-black dark:bg-white text-white dark:text-black rounded-full border border-black dark:border-white">
                                                {{ logs.current_page }} / {{ logs.last_page }}
                                            </div>
                                            <button @click="changePage(logs.current_page + 1)"
                                                :disabled="!logs.next_page_url"
                                                class="px-3 py-2 text-[10px] rounded-full border transition-colors duration-200 flex items-center font-mono uppercase tracking-[0.08em]"
                                                :class="logs.next_page_url ? 'bg-white dark:bg-black text-black dark:text-white border-black dark:border-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black' : 'text-[#CCCCCC] cursor-not-allowed border-[#CCCCCC] dark:border-[#333333]'">
                                                Next
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Explorer Tab -->
                        <div v-show="activeTab === 'explorer'" class="space-y-4 animate-fadeIn">
                            <!-- Toolbar: segmented root control + breadcrumb + summary -->
                            <div class="rounded-2xl border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A] shadow-sm">
                                <div class="flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between sm:p-4">
                                    <!-- Segmented root control -->
                                    <div class="inline-flex rounded-full border border-[#E8E8E8] dark:border-[#222222] bg-[#F5F5F5] dark:bg-black p-0.5">
                                        <button v-for="r in explorerRoots" :key="(r as any).id" @click="switchRoot((r as any).id)"
                                            :class="['px-3.5 py-1.5 text-[11px] font-mono uppercase tracking-wider rounded-full transition-all duration-200', currentRoot === (r as any).id ? 'bg-black text-white dark:bg-white dark:text-black shadow-sm' : 'text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white']">
                                            {{ (r as any).label }}
                                        </button>
                                    </div>
                                    <!-- Summary -->
                                    <div class="flex items-center gap-2 text-[10px] font-mono uppercase tracking-wider text-[#999999]">
                                        <span class="rounded-full border border-[#E8E8E8] dark:border-[#222222] px-2.5 py-1">{{ dirCount }} folders</span>
                                        <span class="rounded-full border border-[#E8E8E8] dark:border-[#222222] px-2.5 py-1">{{ fileCount }} files</span>
                                        <span class="rounded-full border border-[#E8E8E8] dark:border-[#222222] px-2.5 py-1 tabular-nums">{{ formatBytes(totalSize) }}</span>
                                    </div>
                                </div>
                                <!-- Breadcrumb -->
                                <div class="flex flex-wrap items-center gap-1.5 border-t border-[#E8E8E8] dark:border-[#222222] px-4 py-2.5 text-[11px] font-mono">
                                    <button @click="goRoot"
                                        :class="['rounded-md px-1.5 py-0.5 transition-colors', atRoot ? 'text-black dark:text-white font-semibold' : 'text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white hover:bg-[#F5F5F5] dark:hover:bg-black']">
                                        {{ currentRoot }}
                                    </button>
                                    <template v-for="(seg, i) in pathSegments" :key="i">
                                        <span class="text-[#CCCCCC] dark:text-[#444444]">/</span>
                                        <button @click="goToSegment(i)"
                                            :class="['rounded-md px-1.5 py-0.5 transition-colors', i === pathSegments.length - 1 ? 'text-black dark:text-white font-semibold' : 'text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white hover:bg-[#F5F5F5] dark:hover:bg-black']">
                                            {{ seg }}
                                        </button>
                                    </template>
                                </div>
                                <!-- Search -->
                                <div class="flex items-center gap-2 border-t border-[#E8E8E8] dark:border-[#222222] px-3 py-2.5 sm:px-4">
                                    <div class="relative flex-1">
                                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[#999999]">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="M21 21l-4.3-4.3" /></svg>
                                        </span>
                                        <input v-model="search" @keyup.enter="runRecursiveSearch" type="text"
                                            placeholder="Filter this folder — press Enter to search the whole root"
                                            class="w-full rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-[#F9F9F9] dark:bg-black py-2 pl-9 pr-3 font-mono text-[12px] text-black dark:text-white placeholder-[#AAAAAA] dark:placeholder-[#555555] outline-none transition-colors focus:border-black dark:focus:border-white" />
                                    </div>
                                    <button @click="runRecursiveSearch" :disabled="!search.trim() || searchLoading"
                                        class="shrink-0 rounded-lg border border-[#E8E8E8] dark:border-[#222222] px-3 py-2 text-[11px] font-mono uppercase tracking-wider text-black dark:text-white transition-colors hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black disabled:cursor-not-allowed disabled:opacity-40">
                                        Search all
                                    </button>
                                    <button v-if="search || searchMode" @click="clearSearch" title="Clear"
                                        class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[#E8E8E8] dark:border-[#222222] text-[#999999] transition-colors hover:text-black dark:hover:text-white">✕</button>
                                </div>
                            </div>

                            <!-- Search results banner -->
                            <div v-if="searchMode" class="flex items-center justify-between rounded-xl border border-[#E8E8E8] dark:border-[#222222] bg-[#F9F9F9] dark:bg-black px-4 py-2 text-[11px] font-mono">
                                <span class="text-[#666666] dark:text-[#999999]">
                                    Results for “<span class="text-black dark:text-white">{{ search }}</span>” in {{ currentRoot }}
                                    · {{ displayEntries.length }}<span v-if="searchCapped">+ (showing first 500)</span>
                                </span>
                                <button @click="clearSearch" class="text-[#666666] dark:text-[#999999] transition-colors hover:text-black hover:underline dark:hover:text-white">Back to browsing</button>
                            </div>

                            <!-- Listing -->
                            <div class="overflow-hidden rounded-2xl border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A] shadow-sm">
                                <!-- Sticky header -->
                                <div class="sticky top-0 z-10 grid grid-cols-[1fr_auto_auto] items-center gap-4 border-b border-[#E8E8E8] dark:border-[#222222] bg-[#FAFAFA]/90 dark:bg-black/80 px-4 py-2.5 text-[10px] font-mono uppercase tracking-[0.12em] text-[#999999] backdrop-blur">
                                    <span>Name</span>
                                    <span class="w-24 text-right">Size</span>
                                    <span class="w-32 text-right pr-1">Modified · Actions</span>
                                </div>

                                <!-- Scrollable body -->
                                <div class="max-h-[58vh] overflow-y-auto">
                                    <!-- Loading skeleton -->
                                    <div v-if="explorerLoading || searchLoading" class="divide-y divide-[#E8E8E8] dark:divide-[#222222]">
                                        <div v-for="n in 8" :key="n" class="flex items-center gap-3 px-4 py-3">
                                            <div class="h-9 w-9 shrink-0 animate-pulse rounded-lg bg-[#F0F0F0] dark:bg-[#161616]"></div>
                                            <div class="h-3 flex-1 animate-pulse rounded bg-[#F0F0F0] dark:bg-[#161616]" :style="{ maxWidth: (40 + (n * 7) % 45) + '%' }"></div>
                                        </div>
                                    </div>

                                    <!-- Error -->
                                    <div v-else-if="explorerError" class="flex flex-col items-center gap-2 px-4 py-16 text-center">
                                        <span class="text-2xl">⚠️</span>
                                        <p class="text-xs font-mono text-red-500">{{ explorerError }}</p>
                                    </div>

                                    <!-- Empty -->
                                    <div v-else-if="!displayEntries.length" class="flex flex-col items-center gap-2 px-4 py-16 text-center">
                                        <span class="text-3xl opacity-40">{{ searchMode || search.trim() ? '🔍' : '🗂️' }}</span>
                                        <p class="text-xs font-mono uppercase tracking-wider text-[#999999]">
                                            {{ searchMode ? 'No matches in this root' : (search.trim() ? 'No matches in this folder' : 'This folder is empty') }}
                                        </p>
                                    </div>

                                    <!-- Rows -->
                                    <div v-else class="divide-y divide-[#E8E8E8] dark:divide-[#222222]">
                                        <!-- Up row -->
                                        <button v-if="!atRoot && !searchMode && !search.trim()" @click="goUp"
                                            class="group flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors hover:bg-[#F7F7F7] dark:hover:bg-[#111111]">
                                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[#E8E8E8] dark:border-[#222222] text-[#999999]">↩</span>
                                            <span class="font-mono text-[13px] text-[#666666] dark:text-[#999999]">..</span>
                                        </button>

                                        <div v-for="e in displayEntries" :key="e.path"
                                            class="group grid grid-cols-[1fr_auto_auto] items-center gap-4 px-4 py-2.5 transition-colors hover:bg-[#F7F7F7] dark:hover:bg-[#111111]">
                                            <!-- Name + icon/thumb -->
                                            <button :disabled="e.type !== 'dir'" @click="openEntry(e)"
                                                class="flex min-w-0 items-center gap-3 text-left"
                                                :class="e.type === 'dir' ? 'cursor-pointer' : 'cursor-default'">
                                                <span class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-lg border transition-colors"
                                                    :class="e.type === 'dir'
                                                        ? 'border-[#E8E8E8] dark:border-[#222222] bg-[#F5F5F5] dark:bg-black group-hover:border-black dark:group-hover:border-white'
                                                        : 'border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A]'">
                                                    <img v-if="e.type === 'file' && isImage(e.ext)" :src="downloadUrl(e)" loading="lazy" alt=""
                                                        class="h-full w-full object-cover" />
                                                    <span v-else class="text-base">{{ e.type === 'dir' ? '📁' : '📄' }}</span>
                                                </span>
                                                <span class="flex min-w-0 flex-col">
                                                    <span class="truncate font-mono text-[13px] text-black dark:text-white"
                                                        :class="e.type === 'dir' ? 'group-hover:underline' : ''">{{ e.name }}</span>
                                                    <span class="truncate font-mono text-[10px] text-[#AAAAAA] dark:text-[#555555]" :title="fullPath(e)">{{ fullPath(e) }}</span>
                                                </span>
                                            </button>

                                            <!-- Size -->
                                            <span class="w-24 text-right font-mono text-[11px] tabular-nums text-[#666666] dark:text-[#999999]">
                                                {{ e.type === 'dir' ? '—' : formatBytes(e.size) }}
                                            </span>

                                            <!-- Modified + actions (actions reveal on hover) -->
                                            <div class="flex w-32 items-center justify-end gap-2">
                                                <span class="font-mono text-[10px] tabular-nums text-[#AAAAAA] dark:text-[#666666] transition-opacity group-hover:opacity-0 group-hover:hidden">
                                                    {{ e.modified }}
                                                </span>
                                                <div class="hidden items-center gap-1.5 group-hover:flex">
                                                    <button v-if="e.type === 'file'" @click.stop="downloadEntry(e)" title="Download"
                                                        class="grid h-7 w-7 place-items-center rounded-md border border-[#E8E8E8] dark:border-[#222222] text-[#666666] dark:text-[#999999] transition-colors hover:border-black hover:bg-black hover:text-white dark:hover:border-white dark:hover:bg-white dark:hover:text-black">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" /></svg>
                                                    </button>
                                                    <button v-if="canDelete" @click.stop="deleteEntry(e)" title="Delete"
                                                        class="grid h-7 w-7 place-items-center rounded-md border border-red-200 dark:border-red-500/30 text-red-500 transition-colors hover:border-red-500 hover:bg-red-500 hover:text-white">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7" /></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-if="!canDelete" class="text-center text-[10px] font-mono uppercase tracking-wider text-[#BBBBBB] dark:text-[#555555]">Delete is restricted to super admins</p>
                        </div>

                        <!-- Database Tab (read-only) -->
                        <div v-show="activeTab === 'database'" class="animate-fadeIn">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[260px_1fr] lg:items-start">
                                <!-- Table list -->
                                <aside class="flex flex-col overflow-hidden rounded-2xl border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A] shadow-sm">
                                    <div class="border-b border-[#E8E8E8] dark:border-[#222222] p-3">
                                        <div class="relative">
                                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[#999999]">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="M21 21l-4.3-4.3" /></svg>
                                            </span>
                                            <input v-model="dbTableFilter" type="text" placeholder="Find table…"
                                                class="w-full rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-[#F9F9F9] dark:bg-black py-2 pl-9 pr-3 font-mono text-[12px] text-black dark:text-white placeholder-[#AAAAAA] dark:placeholder-[#555555] outline-none transition-colors focus:border-black dark:focus:border-white" />
                                        </div>
                                    </div>
                                    <div class="max-h-[62vh] overflow-y-auto p-2">
                                        <!-- Skeleton -->
                                        <div v-if="dbTablesLoading" class="space-y-1.5 p-1">
                                            <div v-for="n in 10" :key="n" class="h-8 animate-pulse rounded-lg bg-[#F0F0F0] dark:bg-[#161616]"></div>
                                        </div>
                                        <template v-else>
                                            <button v-for="t in filteredDbTables" :key="t.name" @click="openTable(t.name)"
                                                :class="['group mb-0.5 flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left transition-colors',
                                                    dbCurrentTable === t.name ? 'bg-black text-white dark:bg-white dark:text-black' : 'text-[#444444] dark:text-[#BBBBBB] hover:bg-[#F5F5F5] dark:hover:bg-[#111111]']">
                                                <span class="truncate font-mono text-[12px]">{{ t.name }}</span>
                                                <span :class="['shrink-0 rounded-full px-1.5 py-0.5 font-mono text-[9px] tabular-nums',
                                                    dbCurrentTable === t.name ? 'bg-white/20 dark:bg-black/20' : 'bg-[#F0F0F0] dark:bg-[#161616] text-[#999999]']">
                                                    {{ t.rows === null ? '—' : t.rows }}
                                                </span>
                                            </button>
                                            <p v-if="!filteredDbTables.length" class="px-3 py-6 text-center font-mono text-[11px] text-[#999999]">No tables</p>
                                        </template>
                                    </div>
                                </aside>

                                <!-- Table viewer -->
                                <section class="flex min-w-0 flex-col rounded-2xl border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A] shadow-sm lg:h-[70vh]">
                                    <!-- Header: table name + search + per-page -->
                                    <div class="flex flex-col gap-3 border-b border-[#E8E8E8] dark:border-[#222222] p-3 sm:flex-row sm:items-center sm:justify-between sm:p-4">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="text-base">🗄️</span>
                                            <span v-if="dbCurrentTable" class="truncate font-mono text-sm text-black dark:text-white" :title="(dbName ? dbName + '.' : '') + dbCurrentTable">
                                                <span class="text-[#AAAAAA] dark:text-[#666666]">{{ dbName }}.</span><span class="font-semibold">{{ dbCurrentTable }}</span>
                                            </span>
                                            <span v-else class="truncate font-mono text-sm font-semibold text-black dark:text-white">Select a table</span>
                                            <span v-if="dbCurrentTable" class="shrink-0 rounded-full border border-[#E8E8E8] dark:border-[#222222] px-2 py-0.5 font-mono text-[10px] tabular-nums text-[#999999]">{{ dbMeta.total }} rows</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="relative flex-1 sm:w-56 sm:flex-none">
                                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[#999999]">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="M21 21l-4.3-4.3" /></svg>
                                                </span>
                                                <input v-model="dbSearch" @keyup.enter="dbRunSearch" type="text" placeholder="Search rows…" :disabled="!dbCurrentTable"
                                                    class="w-full rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-[#F9F9F9] dark:bg-black py-2 pl-9 pr-8 font-mono text-[12px] text-black dark:text-white placeholder-[#AAAAAA] dark:placeholder-[#555555] outline-none transition-colors focus:border-black dark:focus:border-white disabled:opacity-40" />
                                                <button v-if="dbSearch" @click="dbClearSearch" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#999999] transition-colors hover:text-black dark:hover:text-white">✕</button>
                                            </div>
                                            <select :value="dbMeta.perPage" @change="dbSetPerPage(Number(($event.target as HTMLSelectElement).value))" :disabled="!dbCurrentTable"
                                                class="rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-[#F9F9F9] dark:bg-black px-2 py-2 font-mono text-[11px] text-black dark:text-white outline-none transition-colors focus:border-black dark:focus:border-white disabled:opacity-40">
                                                <option :value="25">25</option>
                                                <option :value="50">50</option>
                                                <option :value="100">100</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Grid -->
                                    <div class="relative flex-1 min-h-0 overflow-auto">
                                        <!-- Loading -->
                                        <div v-if="dbLoading" class="space-y-2 p-4">
                                            <div v-for="n in 8" :key="n" class="h-6 animate-pulse rounded bg-[#F0F0F0] dark:bg-[#161616]"></div>
                                        </div>
                                        <!-- Error -->
                                        <div v-else-if="dbError" class="flex flex-col items-center gap-2 px-4 py-16 text-center">
                                            <span class="text-2xl">⚠️</span>
                                            <p class="font-mono text-xs text-red-500">{{ dbError }}</p>
                                        </div>
                                        <!-- Empty -->
                                        <div v-else-if="!dbRows.length" class="flex flex-col items-center gap-2 px-4 py-16 text-center">
                                            <span class="text-3xl opacity-40">{{ dbSearch.trim() ? '🔍' : '🫙' }}</span>
                                            <p class="font-mono text-xs uppercase tracking-wider text-[#999999]">{{ dbSearch.trim() ? 'No matching rows' : 'No rows' }}</p>
                                        </div>
                                        <!-- Table -->
                                        <table v-else class="w-full border-collapse">
                                            <thead class="sticky top-0 z-10">
                                                <tr class="bg-[#FAFAFA]/95 dark:bg-black/90 backdrop-blur">
                                                    <th v-for="(col, ci) in dbColumns" :key="col" @click="dbSortBy(col)"
                                                        class="cursor-pointer whitespace-nowrap border-b border-[#E8E8E8] dark:border-[#222222] px-3 py-2.5 text-left font-mono text-[10px] uppercase tracking-[0.08em] text-[#666666] dark:text-[#999999] transition-colors hover:text-black dark:hover:text-white"
                                                        :class="ci === 0 ? 'sticky left-0 z-20 bg-[#FAFAFA] dark:bg-black border-r border-[#E8E8E8] dark:border-[#222222]' : ''">
                                                        <span class="inline-flex items-center gap-1">
                                                            {{ col }}
                                                            <span v-if="dbSort === col" class="text-[9px]">{{ dbDir === 'asc' ? '▲' : '▼' }}</span>
                                                        </span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(row, ri) in dbRows" :key="ri"
                                                    class="group transition-colors odd:bg-white even:bg-[#FCFCFC] hover:bg-[#F5F5F5] dark:odd:bg-[#0A0A0A] dark:even:bg-[#0D0D0D] dark:hover:bg-[#141414]">
                                                    <td v-for="(col, ci) in dbColumns" :key="col" @click="dbToggleCell(ri, col)"
                                                        class="cursor-pointer border-b border-[#F0F0F0] dark:border-[#161616] px-3 py-2 font-mono text-[12px] tabular-nums align-top"
                                                        :class="[
                                                            dbIsNull(row[col]) ? 'text-[#CCCCCC] dark:text-[#444444] italic' : 'text-[#333333] dark:text-[#DDDDDD]',
                                                            dbCellExpanded(ri, col)
                                                                ? 'whitespace-pre-wrap break-all bg-[#F5F5F5] dark:bg-[#141414] min-w-[280px]'
                                                                : 'max-w-[320px] truncate',
                                                            ci === 0 ? 'sticky left-0 z-10 border-r border-[#E8E8E8] dark:border-[#222222] group-hover:bg-[#F5F5F5] dark:group-hover:bg-[#141414]' : '',
                                                            ci === 0 && !dbCellExpanded(ri, col) ? (ri % 2 === 0 ? 'bg-white dark:bg-[#0A0A0A]' : 'bg-[#FCFCFC] dark:bg-[#0D0D0D]') : ''
                                                        ]"
                                                        :title="dbCellExpanded(ri, col) ? 'Click to collapse' : dbCell(row[col])">
                                                        {{ dbCell(row[col]) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination footer (only when more than one page) -->
                                    <div v-if="dbCurrentTable && !dbLoading && dbRows.length && dbMeta.lastPage > 1" class="flex flex-col gap-2 border-t border-[#E8E8E8] dark:border-[#222222] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                        <span class="font-mono text-[11px] text-[#999999]">
                                            {{ dbMeta.from }}–{{ dbMeta.to }} of {{ dbMeta.total }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <button @click="dbGoTo(1)" :disabled="dbMeta.page <= 1"
                                                class="rounded-md border border-[#E8E8E8] dark:border-[#222222] px-2 py-1 font-mono text-[11px] text-[#666666] dark:text-[#999999] transition-colors hover:border-black hover:text-black dark:hover:border-white dark:hover:text-white disabled:cursor-not-allowed disabled:opacity-30">«</button>
                                            <button @click="dbGoTo(dbMeta.page - 1)" :disabled="dbMeta.page <= 1"
                                                class="rounded-md border border-[#E8E8E8] dark:border-[#222222] px-2 py-1 font-mono text-[11px] text-[#666666] dark:text-[#999999] transition-colors hover:border-black hover:text-black dark:hover:border-white dark:hover:text-white disabled:cursor-not-allowed disabled:opacity-30">‹</button>
                                            <span class="px-2 font-mono text-[11px] tabular-nums text-black dark:text-white">{{ dbMeta.page }} / {{ dbMeta.lastPage }}</span>
                                            <button @click="dbGoTo(dbMeta.page + 1)" :disabled="dbMeta.page >= dbMeta.lastPage"
                                                class="rounded-md border border-[#E8E8E8] dark:border-[#222222] px-2 py-1 font-mono text-[11px] text-[#666666] dark:text-[#999999] transition-colors hover:border-black hover:text-black dark:hover:border-white dark:hover:text-white disabled:cursor-not-allowed disabled:opacity-30">›</button>
                                            <button @click="dbGoTo(dbMeta.lastPage)" :disabled="dbMeta.page >= dbMeta.lastPage"
                                                class="rounded-md border border-[#E8E8E8] dark:border-[#222222] px-2 py-1 font-mono text-[11px] text-[#666666] dark:text-[#999999] transition-colors hover:border-black hover:text-black dark:hover:border-white dark:hover:text-white disabled:cursor-not-allowed disabled:opacity-30">»</button>
                                        </div>
                                    </div>
                                    <p class="border-t border-[#E8E8E8] dark:border-[#222222] px-4 py-2 text-center font-mono text-[10px] uppercase tracking-wider text-[#BBBBBB] dark:text-[#555555]">Read-only · secrets masked · click a cell to expand</p>
                                </section>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>

</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DotMatrixNumber from '@/components/DotMatrixNumber.vue';
import SegmentedProgressBar from '@/components/SegmentedProgressBar.vue';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import timezoneDetector from '@/utils/timezone.js'

// Persistent layout — keeps AppLayout (sidebar/backdrop) mounted across
// Inertia navigations instead of rebuilding it on every page change.
defineOptions({
    layout: (h: any, page: any) =>
        h(AppLayout, { breadcrumbs: [{ title: 'Server Management', href: '/cache-management' }] }, () => page),
});

// Props
const props = defineProps({
    stats: Object,
    recentCleanups: Array,
    systemInfo: Object,
    lastCleanup: Object,
    explorerRoots: Array,
    canDelete: Boolean,
})

// Reactive state
const currentStats = ref(props.stats || {})
const systemInfo = ref(props.systemInfo || {})
const recentCleanups = ref(props.recentCleanups || [])
const isRefreshing = ref(false)
const isRunningCleanup = ref(false)
const activeTab = ref('overview')
const isBlankingLogs = ref(false)
const isDarkMode = ref(false)
let themeObserver: MutationObserver | null = null

// Live clock variables
const currentTime = ref('')
const currentTimezone = ref('')
const clockInterval = ref(null)

// Tab configuration
const tabs = ref([
    { id: 'overview', name: 'Overview', icon: '📊' },
    { id: 'activity', name: 'Cleanup Logs', icon: '🕐' },
    { id: 'activity_logs', name: 'Activity Log', icon: '📋' },
    { id: 'explorer', name: 'Explorer', icon: '📁' },
    { id: 'database', name: 'Database', icon: '🗄️' }
])

// ─── File explorer tab ──────────────────────────────────────────────────────────
const explorerRoots = ref(props.explorerRoots || [])
const canDelete = ref(!!props.canDelete)
const currentRoot = ref((props.explorerRoots?.[0] as any)?.id || 'uploads')
const currentPath = ref('')
const explorerEntries = ref<any[]>([])
const explorerLoading = ref(false)
const explorerError = ref('')
const explorerLoaded = ref(false)

const pathSegments = computed(() => (currentPath.value ? currentPath.value.split('/') : []))
const atRoot = computed(() => currentPath.value === '')
const currentRootLabel = computed(() => (explorerRoots.value.find((r: any) => r.id === currentRoot.value) as any)?.label || currentRoot.value)
// Full path shown under every entry: root label + its relative path.
const fullPath = (e: any) => `${currentRootLabel.value}/${e.path}`

// Search: live filter of the current folder + recursive root-wide search.
const search = ref('')
const searchMode = ref(false)
const searchResults = ref<any[]>([])
const searchLoading = ref(false)
const searchCapped = ref(false)

const displayEntries = computed(() => {
    if (searchMode.value) return searchResults.value
    const q = search.value.trim().toLowerCase()
    if (!q) return explorerEntries.value
    return explorerEntries.value.filter((e) => e.name.toLowerCase().includes(q))
})

async function runRecursiveSearch() {
    const q = search.value.trim()
    if (!q) return
    searchLoading.value = true
    explorerError.value = ''
    try {
        const { data } = await axios.get('/cache-management/explorer/search', {
            params: { root: currentRoot.value, q },
        })
        searchResults.value = data.entries
        searchCapped.value = data.capped
        searchMode.value = true
    } catch (e: any) {
        explorerError.value = e?.response?.data?.error || 'Search failed'
    } finally {
        searchLoading.value = false
    }
}
function clearSearch() {
    search.value = ''
    searchMode.value = false
    searchResults.value = []
    searchCapped.value = false
}
const fileCount = computed(() => displayEntries.value.filter((e) => e.type === 'file').length)
const dirCount = computed(() => displayEntries.value.filter((e) => e.type === 'dir').length)
const totalSize = computed(() => displayEntries.value.reduce((t, e) => t + (e.type === 'file' ? (e.size || 0) : 0), 0))
function downloadUrl(e: any) {
    return `/cache-management/explorer/download?root=${encodeURIComponent(currentRoot.value)}&path=${encodeURIComponent(e.path)}`
}
function goUp() {
    if (atRoot.value) return
    clearSearch()
    currentPath.value = pathSegments.value.slice(0, -1).join('/')
    loadExplorer()
}

async function loadExplorer() {
    explorerLoading.value = true
    explorerError.value = ''
    try {
        const { data } = await axios.get('/cache-management/explorer', {
            params: { root: currentRoot.value, path: currentPath.value },
        })
        explorerEntries.value = data.entries
        currentPath.value = data.path
        explorerLoaded.value = true
    } catch (e: any) {
        explorerError.value = e?.response?.data?.error || 'Could not load folder'
        explorerEntries.value = []
    } finally {
        explorerLoading.value = false
    }
}
function switchRoot(id: string) { clearSearch(); currentRoot.value = id; currentPath.value = ''; loadExplorer() }
function openEntry(entry: any) { if (entry.type === 'dir') { clearSearch(); currentPath.value = entry.path; loadExplorer() } }
function goToSegment(i: number) { clearSearch(); currentPath.value = pathSegments.value.slice(0, i + 1).join('/'); loadExplorer() }
function goRoot() { clearSearch(); currentPath.value = ''; loadExplorer() }
function downloadEntry(entry: any) {
    window.open(downloadUrl(entry), '_blank')
}
async function deleteEntry(entry: any) {
    const res = await Swal.fire({
        title: `Delete ${entry.type === 'dir' ? 'folder' : 'file'}?`,
        text: entry.name + (entry.type === 'dir' ? ' and everything inside it' : ''),
        icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc2626',
    })
    if (!res.isConfirmed) return
    try {
        await axios.delete('/cache-management/explorer', { data: { root: currentRoot.value, path: entry.path } })
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Deleted', timer: 1200, showConfirmButton: false })
        searchMode.value ? runRecursiveSearch() : loadExplorer()
    } catch (e: any) {
        Swal.fire({ icon: 'error', title: 'Delete failed', text: e?.response?.data?.error || 'Could not delete' })
    }
}
const isImage = (ext: string) => ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg'].includes(ext)

// Lazy-load the folder listing the first time the Explorer tab is opened.
watch(activeTab, (t) => { if (t === 'explorer' && !explorerLoaded.value) loadExplorer() })

// ─── Database browser tab (read-only) ─────────────────────────────────────────
const dbTables = ref<any[]>([])
const dbName = ref('')
const dbTablesLoaded = ref(false)
const dbTablesLoading = ref(false)
const dbTableFilter = ref('')          // filters the table list (client-side)
const dbCurrentTable = ref<string>('')

const dbColumns = ref<string[]>([])
const dbRows = ref<any[]>([])
const dbMeta = ref<any>({ page: 1, perPage: 25, total: 0, lastPage: 1, from: 0, to: 0 })
const dbLoading = ref(false)
const dbError = ref('')

const dbSearch = ref('')               // row search (server-side)
const dbSort = ref('')
const dbDir = ref<'asc' | 'desc'>('asc')

const filteredDbTables = computed(() => {
    const q = dbTableFilter.value.trim().toLowerCase()
    if (!q) return dbTables.value
    return dbTables.value.filter((t) => t.name.toLowerCase().includes(q))
})

async function loadDbTables() {
    dbTablesLoading.value = true
    try {
        const { data } = await axios.get('/cache-management/database/tables')
        dbTables.value = data.tables
        dbName.value = data.database || ''
        dbTablesLoaded.value = true
        if (!dbCurrentTable.value && data.tables.length) openTable(data.tables[0].name)
    } catch (e: any) {
        dbError.value = e?.response?.data?.error || 'Could not load tables'
    } finally {
        dbTablesLoading.value = false
    }
}

async function loadDbRows() {
    if (!dbCurrentTable.value) return
    dbLoading.value = true
    dbError.value = ''
    dbExpanded.value = ''
    try {
        const { data } = await axios.get('/cache-management/database/browse', {
            params: {
                table: dbCurrentTable.value,
                page: dbMeta.value.page,
                perPage: dbMeta.value.perPage,
                q: dbSearch.value.trim() || undefined,
                sort: dbSort.value || undefined,
                dir: dbDir.value,
            },
        })
        dbColumns.value = data.columns
        dbRows.value = data.rows
        dbMeta.value = data.meta
    } catch (e: any) {
        dbError.value = e?.response?.data?.error || 'Could not load rows'
        dbRows.value = []
        dbColumns.value = []
    } finally {
        dbLoading.value = false
    }
}

function openTable(name: string) {
    if (name === dbCurrentTable.value) return
    dbCurrentTable.value = name
    dbSearch.value = ''
    dbSort.value = ''
    dbDir.value = 'asc'
    dbMeta.value = { ...dbMeta.value, page: 1 }
    loadDbRows()
}
function dbRunSearch() { dbMeta.value.page = 1; loadDbRows() }
function dbClearSearch() { if (!dbSearch.value) return; dbSearch.value = ''; dbMeta.value.page = 1; loadDbRows() }
function dbSortBy(col: string) {
    if (dbSort.value === col) {
        dbDir.value = dbDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        dbSort.value = col; dbDir.value = 'asc'
    }
    dbMeta.value.page = 1
    loadDbRows()
}
function dbGoTo(page: number) {
    if (page < 1 || page > dbMeta.value.lastPage || page === dbMeta.value.page) return
    dbMeta.value.page = page
    loadDbRows()
}
function dbSetPerPage(n: number) { dbMeta.value.perPage = n; dbMeta.value.page = 1; loadDbRows() }

function dbCell(val: any): string {
    if (val === null || val === undefined) return '∅'
    if (typeof val === 'object') return JSON.stringify(val)
    return String(val)
}
const dbIsNull = (val: any) => val === null || val === undefined

// Click a cell to reveal its full value (long paths/text otherwise truncate to "…").
const dbExpanded = ref('')
const dbCellKey = (ri: number, col: string) => `${ri}:${col}`
const dbCellExpanded = (ri: number, col: string) => dbExpanded.value === dbCellKey(ri, col)
function dbToggleCell(ri: number, col: string) {
    const k = dbCellKey(ri, col)
    dbExpanded.value = dbExpanded.value === k ? '' : k
}

// Lazy-load tables the first time the Database tab is opened.
watch(activeTab, (t) => { if (t === 'database' && !dbTablesLoaded.value) loadDbTables() })

// Quick actions configuration
const quickActions = ref([
    { type: 'all', name: 'Clean All', description: 'Complete cleanup', icon: '🧹', gradient: 'border-2 bg-white border-[#333333] text-black dark:bg-[#111111] dark:border-white dark:text-white' },
    { type: 'storage', name: 'Storage', description: 'Temp files', icon: '📁', gradient: 'border-2 bg-white border-[#333333] text-black dark:bg-[#111111] dark:border-white dark:text-white' },
    { type: 'logs', name: 'Logs', description: 'Old log files', icon: '📋', gradient: 'border-2 bg-white border-[#333333] text-black dark:bg-[#111111] dark:border-white dark:text-white' },
    { type: 'temp', name: 'Temp Files', description: 'Upload temps', icon: '🗃️', gradient: 'border-2 bg-white border-[#333333] text-black dark:bg-[#111111] dark:border-white dark:text-white' },
    { type: 'artisan', name: 'Artisan Clear', description: 'Clear & cache configs', icon: '⚡', gradient: 'border-2 bg-white border-[#333333] text-black dark:bg-[#111111] dark:border-white dark:text-white' },
    { type: 'view-logs', name: 'View Logs', description: 'Browse log files', icon: '👀', gradient: 'border-2 bg-white border-[#333333] text-black dark:bg-[#111111] dark:border-white dark:text-white' }
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
    const colors = isDarkMode.value
        ? {
            blue: '#FFFFFF',
            green: '#FFFFFF',
            yellow: '#E8E8E8',
            purple: '#E8E8E8',
            red: '#D71921',
            indigo: '#FFFFFF',
            pink: '#E8E8E8',
            teal: '#E8E8E8',
            orange: '#CCCCCC',
            emerald: '#FFFFFF'
        }
        : {
            blue: '#000000',
            green: '#000000',
            yellow: '#666666',
            purple: '#666666',
            red: '#D71921',
            indigo: '#000000',
            pink: '#666666',
            teal: '#666666',
            orange: '#999999',
            emerald: '#000000'
        }

    return colors[color] || colors.blue
}

const syncThemeState = () => {
    isDarkMode.value = document.documentElement.classList.contains('dark')
}

const initThemeObserver = () => {
    syncThemeState()

    themeObserver = new MutationObserver(() => {
        syncThemeState()
    })

    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    })
}

const stopThemeObserver = () => {
    if (themeObserver) {
        themeObserver.disconnect()
        themeObserver = null
    }
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
        if (response.url.includes('/login') || response.status === 401 || response.status === 419) {
            // If redirected to login or unauthorized, use browser time
            updateTime()
            return
        }

        if (!response.ok) {
            updateTime()
            return
        }

        // Ensure we have JSON before attempting to parse (avoid HTML error pages)
        const contentType = response.headers.get('content-type') || ''
        if (!contentType.includes('application/json')) {
            updateTime()
            return
        }

        const data = await response.json()

        // Set timezone from server if present
        if (data?.timezone) {
            currentTimezone.value = data.timezone
        }

        // Create Date object from server timestamp and format it
        const ts = data?.timestamp || (Date.now() / 1000)
        const serverTime = new Date(ts * 1000)
        currentTime.value = serverTime.toLocaleTimeString('en-US', {
            hour12: true,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        })
    } catch (error) {
        // Avoid noisy JSON parse errors flooding the console when server returns HTML
        if (!(error instanceof SyntaxError)) {
            console.warn('Failed to fetch server time, using browser time:', error)
        }
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
                confirmButtonColor: '#000000'
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
            confirmButtonColor: '#D71921'
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
                confirmButtonColor: '#000000'
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
            confirmButtonColor: '#D71921'
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
                confirmButtonColor: '#000000'
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
            confirmButtonColor: '#D71921'
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
    initThemeObserver()

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
    stopThemeObserver()
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