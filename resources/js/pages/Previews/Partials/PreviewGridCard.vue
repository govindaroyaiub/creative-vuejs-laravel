<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
dayjs.extend(relativeTime);
import PreviewActionButtons from './PreviewActionButtons.vue';

interface Props {
    preview: any;
    status: 'inProgress' | 'completed' | 'noFeedback';
}

const props = defineProps<Props>();

const statusConfig = computed(() => {
    const configs = {
        inProgress: {
            label: 'IN PROGRESS',
            borderColor: 'border-[#666666]',
            textColor: 'text-[#666666] dark:text-[#999999]',
        },
        completed: {
            label: 'COMPLETED',
            borderColor: 'border-black dark:border-white',
            textColor: 'text-black dark:text-white',
        },
        noFeedback: {
            label: 'NO FEEDBACK',
            borderColor: 'border-[#CCCCCC]',
            textColor: 'text-[#CCCCCC]',
        }
    };
    return configs[props.status];
});

const formatDateRelative = (dateStr: string) => {
    if (!dateStr) return '';
    return dayjs(dateStr).fromNow();
};

const latestFeedbackText = computed(() => {
    const description = props.preview.latest_feedback_description;
    if (!description) return 'NO RECENT FEEDBACK SUMMARY';
    return description.length > 150 ? description.slice(0, 150) + '...' : description;
});

const navigateToPreview = () => {
    router.visit(`/previews/update/${props.preview.id}`);
};
</script>

<template>
    <div @click="navigateToPreview"
        class="group bg-[#FFFFFF] dark:bg-black rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-3 hover:border-black dark:hover:border-white transition-all cursor-pointer">
        <!-- Header -->
        <div class="flex items-start justify-between mb-3">
            <div class="flex-1 min-w-0">
                <div class="text-lg font-semibold text-black dark:text-white truncate uppercase tracking-wide">
                    {{ preview.name }}
                </div>
                <div class="text-sm text-[#666666] dark:text-[#999999] uppercase tracking-wider font-mono">
                    CREATED BY: {{ preview.uploader?.name || 'SYSTEM' }}
                </div>
            </div>
            <div class="flex flex-col items-end gap-2 ml-3">
                <div class="text-xs text-[#666666] dark:text-[#999999] font-mono uppercase tracking-wider">
                    {{ formatDateRelative(preview.created_at) }}
                </div>
                <span
                    class="px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap border-2 font-mono tracking-wider"
                    :class="[statusConfig.borderColor, statusConfig.textColor]">
                    {{ statusConfig.label }}
                </span>
            </div>
        </div>

        <!-- Latest Feedback -->
        <div class="mb-3 text-sm" :class="status !== 'noFeedback' ? 'text-[#666666] dark:text-[#999999]' : ''">
            <div v-if="status !== 'noFeedback'"
                class="text-xs text-[#666666] dark:text-[#999999] mb-1 uppercase tracking-wider font-mono">
                LATEST SUMMARY:
            </div>
            <div
                class="text-sm text-black dark:text-black bg-white dark:bg-white rounded-lg border border-[#E8E8E8] dark:border-[#222222] p-2">
                {{ latestFeedbackText }}
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E8E8E8] dark:border-[#222222]">
            <PreviewActionButtons :preview="preview" size="md" :show-edit="false" :stop-propagation="true" />
        </div>
    </div>
</template>
