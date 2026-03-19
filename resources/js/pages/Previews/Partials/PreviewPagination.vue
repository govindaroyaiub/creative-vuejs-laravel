<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface Props {
    paginationData: any;
    onPageChange: (url: string) => void;
}

defineProps<Props>();
</script>

<template>
    <div v-if="paginationData.links && paginationData.links.length" class="mt-6 p-4">
        <!-- Mobile/Tablet pagination (simplified) -->
        <div class="lg:hidden">
            <!-- Results Info -->
            <div class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">
                Showing {{ paginationData.from }} to {{ paginationData.to }} of {{ paginationData.total }} previews
            </div>

            <!-- Simple prev/next navigation -->
            <div class="flex items-center justify-between gap-4">
                <button @click="onPageChange(paginationData.prev_page_url)" :disabled="!paginationData.prev_page_url"
                    class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2" :class="paginationData.prev_page_url
                        ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                        : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                    <ChevronLeft class="w-4 h-4" />
                    Previous
                </button>

                <span class="text-sm text-gray-600 dark:text-gray-400">
                    Page {{ paginationData.current_page }} of {{ paginationData.last_page }}
                </span>

                <button @click="onPageChange(paginationData.next_page_url)" :disabled="!paginationData.next_page_url"
                    class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2" :class="paginationData.next_page_url
                        ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                        : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                    Next
                    <ChevronRight class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Desktop pagination (full features) -->
        <div class="hidden lg:flex items-center justify-between">
            <!-- Results Info -->
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Showing {{ paginationData.from }} to {{ paginationData.to }} of {{ paginationData.total }} previews
            </div>

            <!-- Pagination Controls -->
            <div class="flex items-center space-x-2">
                <!-- Previous Button -->
                <button @click="onPageChange(paginationData.prev_page_url)" :disabled="!paginationData.prev_page_url"
                    class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center" :class="paginationData.prev_page_url
                        ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                        : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                    <ChevronLeft class="w-4 h-4 mr-1" />
                    Previous
                </button>

                <!-- Page Numbers -->
                <div class="flex items-center space-x-1">
                    <template v-for="link in paginationData.links.slice(1, -1)" :key="link.label">
                        <button v-if="link.url" @click="onPageChange(link.url)"
                            class="px-3 py-2 text-sm rounded-lg transition-all duration-200"
                            :class="link.active
                                ? 'bg-blue-600 text-white'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'" v-html="link.label" />
                        <span v-else class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed" v-html="link.label" />
                    </template>
                </div>

                <!-- Next Button -->
                <button @click="onPageChange(paginationData.next_page_url)" :disabled="!paginationData.next_page_url"
                    class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center" :class="paginationData.next_page_url
                        ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                        : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                    Next
                    <ChevronRight class="w-4 h-4 ml-1" />
                </button>
            </div>
        </div>
    </div>
</template>
