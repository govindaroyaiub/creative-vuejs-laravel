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
            <div class="text-sm text-[#666666] dark:text-[#999999] text-center mb-3 font-mono tracking-wide">
                Showing {{ paginationData.from }} to {{ paginationData.to }} of {{ paginationData.total }} previews
            </div>

            <!-- Simple prev/next navigation -->
            <div class="flex items-center justify-between gap-4">
                <button @click="onPageChange(paginationData.prev_page_url)" :disabled="!paginationData.prev_page_url"
                    class="px-2 py-2 text-sm rounded-full transition-all duration-200 flex items-center gap-2 font-mono tracking-wide"
                    :class="paginationData.prev_page_url
                        ? 'text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white'
                        : 'text-[#CCCCCC] cursor-not-allowed border-2 border-[#CCCCCC]'">
                    <ChevronLeft class="w-4 h-4" :stroke-width="1.5" />
                    Previous
                </button>

                <span class="text-sm text-[#666666] dark:text-[#999999] font-mono tracking-wide">
                    Page {{ paginationData.current_page }} of {{ paginationData.last_page }}
                </span>

                <button @click="onPageChange(paginationData.next_page_url)" :disabled="!paginationData.next_page_url"
                    class="px-2 py-2 text-sm rounded-full transition-all duration-200 flex items-center gap-2 font-mono tracking-wide"
                    :class="paginationData.next_page_url
                        ? 'text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white'
                        : 'text-[#CCCCCC] cursor-not-allowed border-2 border-[#CCCCCC]'">
                    Next
                    <ChevronRight class="w-4 h-4" :stroke-width="1.5" />
                </button>
            </div>
        </div>

        <!-- Desktop pagination (full features) -->
        <div class="hidden lg:flex items-center justify-between">
            <!-- Results Info -->
            <div class="text-sm text-[#666666] dark:text-[#999999] font-mono tracking-wide">
                Showing {{ paginationData.from }} to {{ paginationData.to }} of {{ paginationData.total }} previews
            </div>

            <!-- Pagination Controls -->
            <div class="flex items-center space-x-2">
                <!-- Previous Button -->
                <button @click="onPageChange(paginationData.prev_page_url)" :disabled="!paginationData.prev_page_url"
                    class="px-2 py-2 text-sm rounded-full transition-all duration-200 flex items-center font-mono tracking-wide"
                    :class="paginationData.prev_page_url
                        ? 'text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white'
                        : 'text-[#CCCCCC] cursor-not-allowed border-2 border-[#CCCCCC]'">
                    <ChevronLeft class="w-4 h-4 mr-1" :stroke-width="1.5" />
                    Previous
                </button>

                <!-- Page Numbers -->
                <div class="flex items-center space-x-1">
                    <template v-for="link in paginationData.links.slice(1, -1)" :key="link.label">
                        <button v-if="link.url" @click="onPageChange(link.url)"
                            class="px-2 py-2 text-sm rounded-full transition-all duration-200 font-mono tabular-nums"
                            :class="link.active
                                ? 'bg-black text-white dark:bg-white dark:text-black border-2 border-black dark:border-white'
                                : 'text-[#666666] dark:text-[#999999] hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-[#E8E8E8] dark:border-[#222222]'"
                            v-html="link.label" />
                        <span v-else class="px-2 py-2 text-sm text-[#CCCCCC] cursor-not-allowed font-mono tabular-nums"
                            v-html="link.label" />
                    </template>
                </div>

                <!-- Next Button -->
                <button @click="onPageChange(paginationData.next_page_url)" :disabled="!paginationData.next_page_url"
                    class="px-2 py-2 text-sm rounded-full transition-all duration-200 flex items-center font-mono tracking-wide"
                    :class="paginationData.next_page_url
                        ? 'text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white'
                        : 'text-[#CCCCCC] cursor-not-allowed border-2 border-[#CCCCCC]'">
                    Next
                    <ChevronRight class="w-4 h-4 ml-1" :stroke-width="1.5" />
                </button>
            </div>
        </div>
    </div>
</template>
