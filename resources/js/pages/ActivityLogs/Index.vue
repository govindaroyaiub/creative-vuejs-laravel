<template>

    <Head title="Activity Logs" />
    <AppLayout :breadcrumbs="[{ title: 'Activity Logs', href: '/activity-logs' }]">
        <div class="container mx-auto py-4 px-4">
            <div class="mb-4 flex justify-between items-center">
                <input v-model="search" @input="onSearchInput" type="text"
                    placeholder="Search logs..."
                    class="border rounded px-3 py-2 w-1/2 bg-white dark:bg-black text-black dark:text-white" />
            </div>
            <table class="min-w-full border bg-white dark:bg-black text-black dark:text-white text-center">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b text-center">ID</th>
                        <th class="py-2 px-4 border-b text-center">Log Name</th>
                        <th class="py-2 px-4 border-b text-center">User</th>
                        <th class="py-2 px-4 border-b text-center">Description</th>
                        <th class="py-2 px-4 border-b text-center">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(log, index) in logs.data" :key="log.id">
                        <td class="py-2 px-4 border-b text-center">{{ index + 1 }}</td>
                        <td class="py-2 px-4 border-b text-center">{{ log.log_name }}</td>
                        <td class="py-2 px-4 border-b text-center">
                            <span v-if="log.causer">{{ log.causer.name || log.causer.email || log.causer.id }}</span>
                            <span v-else>-</span>
                        </td>
                        <td class="py-2 px-4 border-b text-center">
                            {{ log.description }}
                            <hr class="border-t border-gray-200" />
                            {{ formatDate(log.created_at) }}
                        </td>
                        <td class="py-2 px-4 border-b text-center">
                            <button v-if="log.properties && (log.properties.attributes || log.properties.old)"
                                @click="toggleExpand(log.id)" class="text-blue-600 underline text-xs">
                                {{ expandedRows.includes(log.id) ? 'Hide' : 'Show' }} Details
                            </button>
                            <div v-if="expandedRows.includes(log.id)" class="mt-2 text-left">
                                <div v-if="log.properties && log.properties.attributes">
                                    <strong>Changed:</strong>
                                    <ul class="text-xs">
                                        <li v-for="(value, key) in log.properties.attributes" :key="key">
                                            {{ key }}: <span class="text-green-600">{{ value }}</span>
                                            <span v-if="log.properties.old && log.properties.old[key] !== undefined">
                                                (was <span class="text-red-600">{{ log.properties.old[key] }}</span>)
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                                <div v-else-if="log.properties && Object.keys(log.properties).length">
                                    <strong>Properties:</strong>
                                    <ul class="text-xs">
                                        <li v-for="(value, key) in log.properties" :key="key">
                                            {{ key }}: <span class="text-green-600">{{ value }}</span>
                                        </li>
                                    </ul>
                                </div>
                                <div v-else>
                                    <span class="text-xs text-gray-500">No extra details.</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="logs.data && logs.data.length === 0">
                        <td colspan="5" class="text-center py-4">No activity logs found.</td>
                    </tr>
                </tbody>
            </table>
            <div class="mt-4 flex justify-center items-center space-x-2">
                <button :disabled="!logs.prev_page_url" @click="changePage(logs.current_page - 1)"
                    class="px-3 py-1 border rounded bg-white dark:bg-black text-black dark:text-white">
                    Prev
                </button>
                <span>Page {{ logs.current_page }} of {{ logs.last_page }}</span>
                <button :disabled="!logs.next_page_url" @click="changePage(logs.current_page + 1)"
                    class="px-3 py-1 border rounded bg-white dark:bg-black text-black dark:text-white">
                    Next
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    logs: Object,
    search: String,
})

const logs = ref(props.logs)
const search = ref(props.search || '')
const expandedRows = ref([])

watch(() => props.logs, (newLogs) => {
    logs.value = newLogs
})

function changePage(page) {
    router.get('/activity-logs', { page, search: search.value }, { preserveState: true, replace: true })
}

function onSearchInput() {
    router.get('/activity-logs', { search: search.value }, { preserveState: true, replace: true })
}

function formatDate(dateStr) {
    const d = new Date(dateStr)
    return d.toLocaleString()
}

function toggleExpand(id) {
    if (expandedRows.value.includes(id)) {
        expandedRows.value = expandedRows.value.filter(rowId => rowId !== id)
    } else {
        expandedRows.value.push(id)
    }
}
</script>