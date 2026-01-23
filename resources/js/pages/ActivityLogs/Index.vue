<template>

    <Head title="Activity Logs" />
    <AppLayout :breadcrumbs="[{ title: 'Activity Logs', href: '/activity-logs' }]">
        <div
            class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-black dark:via-gray-950 dark:to-black">
            <div class="p-6 space-y-6">
                <!-- Search Section -->
                <div class="rounded-2xl flex w-full items-center gap-2">
                    <div class="relative w-full">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input v-model="search" @input="onSearchInput" type="text"
                            placeholder="Search by description, user, or log name..."
                            class="w-1/2 pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-neutral-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" />
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="confirmEmptyAll" v-if="logs?.total > 0"
                            class="px-3 py-2 bg-red-600 text-sm rounded-xl text-white hover:bg-red-700 transition whitespace-nowrap">
                            Empty Logs
                        </button>
                    </div>
                    <div
                        class="text-sm text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-neutral-800 px-3 py-4 rounded-2xl w-1/6 text-center">
                        Total: {{ logs?.total || 0 }} entries
                    </div>
                </div>

                <!-- Selection Controls -->
                <div v-if="logs?.data?.length > 0" class="flex items-center justify-between mt-3">
                    <div class="flex items-center gap-3">
                        <label class="inline-flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" v-model="selectAll" @change="toggleSelectAll"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded" />
                            <span>Select all on page</span>
                        </label>
                        <button v-if="selectedIds.length" @click="confirmDeleteSelected"
                            class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
                            Delete Selected ({{ selectedIds.length }})
                        </button>
                    </div>
                </div>

                <!-- Activity Cards -->
                <div v-if="logs?.data?.length > 0" class="space-y-4">
                    <div v-for="(log, index) in logs.data" :key="log.id"
                        class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 hover:shadow-md transition-all duration-200 overflow-hidden">
                        <div class="p-4">
                            <!-- Header Row -->
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                <div class="flex items-start space-x-4 flex-1">
                                    <!-- Checkbox + ID Badge -->
                                    <div class="flex-shrink-0 flex items-center space-x-3">
                                        <input type="checkbox" :value="log.id" v-model="selectedIds"
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded" />
                                        <div>
                                            <div
                                                class="w-10 h-10 bg-gradient-to-r from-blue-100 to-purple-100 dark:from-blue-900/50 dark:to-purple-900/50 rounded-xl flex items-center justify-center">
                                                <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                                    #{{ (logs.current_page - 1) * logs.per_page + index + 1 }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Main Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-2">
                                            <h3 class="font-semibold text-gray-900 dark:text-white text-lg">
                                                {{ log.description }}
                                            </h3>
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                                    {{ log.log_name }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- User and Date Info -->
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                    </path>
                                                </svg>
                                                <span>
                                                    <span v-if="log.causer"
                                                        class="font-medium text-gray-900 dark:text-white">
                                                        {{ log.causer.name || log.causer.email || `User
                                                        ${log.causer.id}` }}
                                                    </span>
                                                    <span v-else class="text-gray-400 italic">System</span>
                                                </span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="font-medium">{{ formatDate(log.created_at) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Details Button -->
                                <div class="flex-shrink-0">
                                    <button
                                        v-if="log.properties && (log.properties.attributes || log.properties.old || Object.keys(log.properties).length > 0)"
                                        @click="toggleExpand(log.id)"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-all duration-200">
                                        <svg class="w-4 h-4 mr-1 transition-transform duration-200"
                                            :class="{ 'rotate-180': expandedRows.includes(log.id) }" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                        {{ expandedRows.includes(log.id) ? 'Hide Details' : 'Show Details' }}
                                    </button>
                                    <span v-else class="text-xs text-gray-400 italic px-3 py-2">No details</span>
                                </div>
                            </div>

                            <!-- Expanded Details -->
                            <div v-if="expandedRows.includes(log.id)"
                                class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 animate-fadeIn">
                                <div v-if="log.properties && log.properties.attributes" class="space-y-3">
                                    <h4
                                        class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Changed Attributes
                                    </h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div v-for="(value, key) in log.properties.attributes" :key="key"
                                            class="bg-gray-50 dark:bg-neutral-800 rounded-lg p-3">
                                            <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{
                                                key }}</div>
                                            <div class="flex flex-col space-y-1">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-xs text-gray-500">New:</span>
                                                    <span
                                                        class="text-sm font-medium text-green-600 dark:text-green-400 font-mono bg-green-50 dark:bg-green-900/20 px-2 py-1 rounded">
                                                        {{ value }}
                                                    </span>
                                                </div>
                                                <div v-if="log.properties.old && log.properties.old[key] !== undefined"
                                                    class="flex items-center space-x-2">
                                                    <span class="text-xs text-gray-500">Old:</span>
                                                    <span
                                                        class="text-sm font-medium text-red-600 dark:text-red-400 font-mono bg-red-50 dark:bg-red-900/20 px-2 py-1 rounded">
                                                        {{ log.properties.old[key] }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else-if="log.properties && Object.keys(log.properties).length" class="space-y-3">
                                    <h4
                                        class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Properties
                                    </h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div v-for="(value, key) in log.properties" :key="key"
                                            class="bg-gray-50 dark:bg-neutral-800 rounded-lg p-3">
                                            <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{
                                                key }}</div>
                                            <div
                                                class="text-sm font-medium text-gray-900 dark:text-white font-mono bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded">
                                                {{ value }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else
                    class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-12 text-center">
                    <div
                        class="w-20 h-20 mx-auto bg-gray-100 dark:bg-neutral-800 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No activity logs found</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                        {{ search ? 'Try adjusting your search terms' : 'No activities have been logged yet' }}
                    </p>
                </div>

                <!-- Pagination - Responsive -->
                <div v-if="logs?.data?.length > 0"
                    class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">

                    <!-- Mobile/Tablet pagination (simplified) -->
                    <div class="lg:hidden">
                        <!-- Results Info -->
                        <div class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">
                            Showing {{ logs.from }} to {{ logs.to }} of {{ logs.total }} entries
                        </div>

                        <!-- Simple prev/next navigation -->
                        <div class="flex items-center justify-between gap-4">
                            <button @click="changePage(logs.current_page - 1)" :disabled="!logs.prev_page_url"
                                class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2"
                                :class="logs.prev_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Previous
                            </button>

                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                Page {{ logs.current_page }} of {{ logs.last_page }}
                            </span>

                            <button @click="changePage(logs.current_page + 1)" :disabled="!logs.next_page_url"
                                class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2"
                                :class="logs.next_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                Next
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Desktop pagination (full features) -->
                    <div class="hidden lg:flex items-center justify-between">
                        <!-- Results Info -->
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Showing {{ logs.from }} to {{ logs.to }} of {{ logs.total }} entries
                        </div>

                        <!-- Pagination Controls -->
                        <div class="flex items-center space-x-2">
                            <!-- Previous Button -->
                            <button @click="changePage(logs.current_page - 1)" :disabled="!logs.prev_page_url"
                                class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center"
                                :class="logs.prev_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Previous
                            </button>

                            <!-- Current Page Info -->
                            <div class="flex items-center space-x-1">
                                <span class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg">
                                    {{ logs.current_page }}
                                </span>
                                <span class="px-2 text-sm text-gray-500 dark:text-gray-400">of</span>
                                <span class="px-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ logs.last_page }}
                                </span>
                            </div>

                            <!-- Next Button -->
                            <button @click="changePage(logs.current_page + 1)" :disabled="!logs.next_page_url"
                                class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center"
                                :class="logs.next_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                Next
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue';
import Swal from 'sweetalert2'

const props = defineProps({
    logs: Object,
    search: String,
})

const logs = ref(props.logs)
const search = ref(props.search || '')
const expandedRows = ref([])

// Selection state
const selectedIds = ref([])
const selectAll = ref(false)

watch(() => props.logs, (newLogs) => {
    logs.value = newLogs
    // reset selection when page changes
    selectedIds.value = []
    selectAll.value = false
})

watch(selectedIds, () => {
    // toggle selectAll if all visible rows are selected
    if (!logs.value || !logs.value.data) return
    const pageIds = logs.value.data.map(l => l.id)
    selectAll.value = pageIds.length > 0 && pageIds.every(id => selectedIds.value.includes(id))
})

function changePage(page) {
    router.get('/activity-logs', { page, search: search.value }, { preserveState: true, replace: true })
}

function onSearchInput() {
    router.get('/activity-logs', { search: search.value }, { preserveState: true, replace: true })
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
        // select all on page
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...pageIds]))
    } else {
        // remove page ids from selection
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
            // reload the page to refresh logs
            router.get('/activity-logs', { search: search.value }, { preserveState: true, replace: true })
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
            router.get('/activity-logs', { search: search.value }, { preserveState: true, replace: true })
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
</script>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}
</style>