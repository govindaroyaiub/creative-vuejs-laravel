<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
dayjs.extend(relativeTime)

const props = defineProps({
    previews: Object,
})

const previews = ref(props.previews)
const search = ref('')
const debounceTimer = ref<number | null>(null)

const inProgressLimit = ref(6)
const completedLimit = ref(6)
const noFeedbackLimit = ref(6)

const groups = computed(() => {
    const inProgress: any[] = []
    const completed: any[] = []
    const noFeedback: any[] = []
    const list = (previews.value && previews.value.data) || []
    for (const p of list) {
        const total = Number(p.total_feedbacks || 0)
        const approved = Number(p.approved_feedbacks || 0)
        if (total === 0) {
            noFeedback.push(p)
        } else if (approved >= total) {
            completed.push(p)
        } else {
            inProgress.push(p)
        }
    }
    return { inProgress, completed, noFeedback }
})

watch(() => props.previews, (v) => { previews.value = v })

function onSearchInput() {
    if (debounceTimer.value) clearTimeout(debounceTimer.value)
    debounceTimer.value = window.setTimeout(() => {
        router.get('/preview-tracker', { search: search.value, page: 1 }, { preserveState: true, replace: true })
    }, 350)
}

function formatDateRelative(dateStr: string) {
    if (!dateStr) return ''
    return dayjs(dateStr).fromNow()
}

// statusFor removed — grouping is done in `groups` computed

function truncate(text: string | null, length = 140) {
    if (!text) return ''
    return text.length > length ? text.slice(0, length) + '…' : text
}

// pages helper removed — pagination now uses server-provided `previews.links`
</script>

<template>

    <Head title="Preview Tracker" />
    <AppLayout :breadcrumbs="[{ title: 'Preview Tracker', href: '/preview-tracker' }]">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <input v-model="search" @input="onSearchInput" type="text" placeholder="Search previews..."
                        class="px-3 py-2 rounded-2xl border w-72 dark:bg-neutral-800 bg-white" />


                </div>
                <div class="text-sm dark:text-white text-gray-800">Total: {{ previews?.total || 0 }}</div>
            </div>

            <!-- Grouped Sections: In Progress, Completed, No Feedback -->
            <div v-if="previews?.data?.length" class="space-y-8">
                <!-- In Progress -->
                <section>
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-lg font-semibold">In Progress</h3>
                        <div class="text-sm text-gray-500">Showing {{ Math.min(groups.inProgress.length,
                            inProgressLimit) }} of {{ groups.inProgress.length }}</div>
                    </div>
                    <div
                        class="h-0.5 w-full bg-gradient-to-r from-yellow-400 via-yellow-300 to-yellow-200 rounded-full mb-4">
                    </div>
                    <div v-if="groups.inProgress.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="p in groups.inProgress.slice(0, inProgressLimit)" :key="p.id"
                            class="bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700 shadow p-3 hover:shadow-md transition">
                            <div class="flex items-start justify-between">
                                <div>
                                    <a :href="`/previews/update/${p.id}`"
                                        class="text-lg font-semibold text-blue-600 dark:text-blue-300 hover:underline">{{
                                            p.name }}</a>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Created by: {{
                                        p.uploader?.name || 'System' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">{{ formatDateRelative(p.created_at) }}</div>
                                    <div class="mt-2">
                                        <span
                                            class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">In
                                            Progress</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                <div class="text-xs text-gray-500">Latest Summary:</div>
                                <div
                                    class="mt-3 text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-neutral-700 rounded-md p-2">
                                    {{
                                        p.latest_feedback_description
                                            ? (p.latest_feedback_description.length > 150
                                                ? p.latest_feedback_description.slice(0, 150) + '...'
                                                : p.latest_feedback_description)
                                            : 'No recent feedback summary'
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500">No previews in progress.</div>
                    <div v-if="groups.inProgress.length > inProgressLimit" class="mt-3 text-center">
                        <button @click="inProgressLimit += 10" class="px-4 py-2 rounded-xl border">Show more</button>
                    </div>
                </section>

                <!-- Completed -->
                <section>
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-lg font-semibold">Completed</h3>
                        <div class="text-sm text-gray-500">Showing {{ Math.min(groups.completed.length, completedLimit)
                        }} of {{ groups.completed.length }}</div>
                    </div>
                    <div
                        class="h-0.5 w-full bg-gradient-to-r from-green-400 via-green-300 to-green-200 rounded-full mb-4">
                    </div>
                    <div v-if="groups.completed.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="p in groups.completed.slice(0, completedLimit)" :key="p.id"
                            class="bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700 shadow p-4 hover:shadow-md transition">
                            <div class="flex items-start justify-between">
                                <div>
                                    <a :href="`/previews/update/${p.id}`"
                                        class="text-lg font-semibold text-blue-600 dark:text-blue-300 hover:underline">{{
                                            p.name }}</a>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Created by: {{
                                        p.uploader?.name || 'System' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">{{ formatDateRelative(p.created_at) }}</div>
                                    <div class="mt-2">
                                        <span
                                            class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Completed</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                <div class="text-xs text-gray-500">Latest Summary:</div>
                                <div
                                    class="mt-3 text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-neutral-700 rounded-md p-2">
                                    {{
                                        p.latest_feedback_description
                                            ? (p.latest_feedback_description.length > 150
                                                ? p.latest_feedback_description.slice(0, 150) + '...'
                                                : p.latest_feedback_description)
                                            : 'No recent feedback summary'
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500">No completed previews.</div>
                    <div v-if="groups.completed.length > completedLimit" class="mt-3 text-center">
                        <button @click="completedLimit += 10" class="px-4 py-2 rounded-xl border">Show more</button>
                    </div>
                </section>

                <!-- No Feedback -->
                <section>
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-lg font-semibold">No Feedback</h3>
                        <div class="text-sm text-gray-500">Showing {{ Math.min(groups.noFeedback.length,
                            noFeedbackLimit) }} of {{ groups.noFeedback.length }}</div>
                    </div>
                    <div class="h-0.5 w-full bg-gradient-to-r from-gray-400 via-gray-300 to-gray-200 rounded-full mb-4">
                    </div>
                    <div v-if="groups.noFeedback.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="p in groups.noFeedback.slice(0, noFeedbackLimit)" :key="p.id"
                            class="bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700 shadow p-4 hover:shadow-md transition">
                            <div class="flex items-start justify-between">
                                <div>
                                    <a :href="`/previews/update/${p.id}`"
                                        class="text-lg font-semibold text-blue-600 dark:text-blue-300 hover:underline">{{
                                            p.name }}</a>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Created by: {{
                                        p.uploader?.name || 'System' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">{{ formatDateRelative(p.created_at) }}</div>
                                    <div class="mt-2">
                                        <span
                                            class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">No
                                            Feedback</span>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="mt-3 text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-neutral-700 rounded-md p-2">
                                {{
                                    p.latest_feedback_description
                                        ? (p.latest_feedback_description.length > 150
                                            ? p.latest_feedback_description.slice(0, 150) + '...'
                                            : p.latest_feedback_description)
                                        : 'No recent feedback summary'
                                }}
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500">No previews without feedback.</div>
                    <div v-if="groups.noFeedback.length > noFeedbackLimit" class="mt-3 text-center">
                        <button @click="noFeedbackLimit += 10" class="px-4 py-2 rounded-xl border">Show more</button>
                    </div>
                </section>
            </div>

            <div v-else class="bg-white dark:bg-neutral-800 rounded-xl p-8 text-center">
                <div class="text-gray-600 dark:text-gray-400">No previews found.</div>
            </div>



        </div>
    </AppLayout>
</template>