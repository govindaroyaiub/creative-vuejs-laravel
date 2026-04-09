<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    percentage: number;
    label?: string;
    value?: string;
    segments?: number;
    color?: 'default' | 'accent' | 'success' | 'warning';
}

const props = withDefaults(defineProps<Props>(), {
    segments: 20,
    color: 'default'
});

// Calculate filled segments
const filledSegments = computed(() => {
    return Math.round((props.percentage / 100) * props.segments);
});

// Get color based on type
const segmentColor = computed(() => {
    const colors = {
        default: 'bg-black dark:bg-white',
        accent: 'bg-[#D71921]',
        success: 'bg-emerald-500',
        warning: 'bg-amber-500'
    };
    return colors[props.color] || colors.default;
});
</script>

<template>
    <div class="w-full">
        <!-- Label and Value Row -->
        <div v-if="label || value" class="flex items-center justify-between mb-2">
            <span class="text-xs font-mono uppercase tracking-[0.08em] text-[#666666] dark:text-[#999999]">
                {{ label }}
            </span>
            <span class="text-xs font-mono font-medium text-black dark:text-white tabular-nums">
                {{ value }}
            </span>
        </div>

        <!-- Segmented Bar -->
        <div class="flex gap-[2px] w-full">
            <div v-for="i in segments" :key="i" class="h-2 flex-1 transition-colors duration-300"
                :class="i <= filledSegments ? segmentColor : 'bg-[#E8E8E8] dark:bg-[#222222]'" />
        </div>
    </div>
</template>
