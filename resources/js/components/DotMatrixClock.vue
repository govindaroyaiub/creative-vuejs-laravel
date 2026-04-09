<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    time: string;
    date: string;
    city: string;
    country: string;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md'
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return {
                time: 'text-2xl sm:text-3xl',
                date: 'text-[9px]',
                city: 'text-[11px]',
                country: 'text-[9px]',
                padding: 'p-2 md:p-2.5',
                mb: 'mb-1.5'
            };
        case 'lg':
            return {
                time: 'text-5xl sm:text-6xl md:text-7xl',
                date: 'text-sm',
                city: 'text-lg',
                country: 'text-xs',
                padding: 'p-4 md:p-5',
                mb: 'mb-3'
            };
        default: // md
            return {
                time: 'text-3xl sm:text-4xl',
                date: 'text-[10px]',
                city: 'text-xs',
                country: 'text-[9px]',
                padding: 'p-2.5 md:p-3',
                mb: 'mb-2'
            };
    }
});
</script>

<template>
    <div :class="[
        'flex flex-col border border-[#E8E8E8] dark:border-[#222222] rounded-md',
        'transition-all duration-200 hover:border-[#CCCCCC] dark:hover:border-[#333333]',
        'bg-white dark:bg-[#111111] relative overflow-hidden',
        sizeClasses.padding
    ]">

        <!-- Subtle dot pattern background -->
        <div class="absolute inset-0 opacity-[0.015] dark:opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(circle, currentColor 0.5px, transparent 0.5px);
                    background-size: 4px 4px;"></div>

        <!-- Location Header -->
        <div :class="['relative z-10', sizeClasses.mb]">
            <div
                :class="['font-medium text-[#1A1A1A] dark:text-[#E8E8E8] uppercase tracking-[0.08em] font-mono mb-0.5', sizeClasses.city]">
                {{ city }}
            </div>
            <div
                :class="['text-[#666666] dark:text-[#999999] uppercase tracking-[0.1em] font-mono', sizeClasses.country]">
                {{ country }}
            </div>
        </div>

        <!-- Dot Matrix Time Display -->
        <div :class="['relative z-10', sizeClasses.mb]">
            <div :class="[
                'font-mono tracking-[-0.02em] tabular-nums dot-matrix-text',
                'text-black dark:text-white',
                sizeClasses.time
            ]" style="font-family: 'Doto', 'Space Mono', monospace; font-variation-settings: 'DOTS' 20;">
                {{ time }}
            </div>
        </div>

        <!-- Date Display -->
        <div class="relative z-10">
            <div
                :class="['text-[#666666] dark:text-[#999999] uppercase tracking-[0.08em] font-mono', sizeClasses.date]">
                {{ date }}
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Enhanced dot matrix effect using text-shadow */
.dot-matrix-text {
    position: relative;
    font-weight: 400;
    /* Add subtle dot grid effect with multiple text shadows */
    text-shadow:
        0.5px 0.5px 0 currentColor,
        -0.5px -0.5px 0 rgba(0, 0, 0, 0.1);
    letter-spacing: 0.05em;
}

/* Additional dot pattern overlay for dark mode */
@media (prefers-color-scheme: dark) {
    .dot-matrix-text {
        text-shadow:
            0.5px 0.5px 0 currentColor,
            -0.5px -0.5px 0 rgba(255, 255, 255, 0.1);
    }
}

/* Ensure Doto font renders with optimal settings */
@supports (font-variation-settings: normal) {
    .dot-matrix-text {
        font-variation-settings: 'DOTS' 20, 'ROND' 0;
    }
}
</style>
