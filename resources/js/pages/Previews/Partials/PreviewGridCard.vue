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
            label: 'In Progress',
            bgColor: 'bg-yellow-100',
            textColor: 'text-yellow-800',
        },
        completed: {
            label: 'Completed',
            bgColor: 'bg-green-100',
            textColor: 'text-green-800',
        },
        noFeedback: {
            label: 'No Feedback',
            bgColor: 'bg-gray-100',
            textColor: 'text-gray-700',
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
    if (!description) return 'No recent feedback summary';
    return description.length > 150 ? description.slice(0, 150) + '...' : description;
});

const navigateToPreview = () => {
    router.visit(`/previews/update/${props.preview.id}`);
};
</script>

<template>
    <div @click="navigateToPreview"
        class="group bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700 shadow p-3 hover:shadow-md transition cursor-pointer">
        <!-- Header -->
        <div class="flex items-start justify-between mb-3">
            <div class="flex-1 min-w-0">
                <div class="text-lg font-semibold text-blue-600 dark:text-blue-300 truncate">
                    {{ preview.name }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Created by: {{ preview.uploader?.name || 'System' }}
                </div>
            </div>
            <div class="flex flex-col items-end gap-2 ml-3">
                <div class="text-xs text-gray-500">
                    {{ formatDateRelative(preview.created_at) }}
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap"
                    :class="[statusConfig.bgColor, statusConfig.textColor]">
                    {{ statusConfig.label }}
                </span>
            </div>
        </div>

        <!-- Latest Feedback -->
        <div class="mb-3 text-sm" :class="status !== 'noFeedback' ? 'text-gray-600 dark:text-gray-300' : ''">
            <div v-if="status !== 'noFeedback'" class="text-xs text-gray-500 mb-1">
                Latest Summary:
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-neutral-700 rounded-md p-2">
                {{ latestFeedbackText }}
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-neutral-700">
            <PreviewActionButtons :preview="preview" size="md" :show-edit="false" :stop-propagation="true" />
        </div>
    </div>
</template>
