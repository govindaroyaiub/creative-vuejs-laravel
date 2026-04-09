<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    value: string | number;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    label?: string;
    unit?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md'
});

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'text-lg',
        sm: 'text-2xl',
        md: 'text-3xl sm:text-4xl',
        lg: 'text-4xl sm:text-5xl',
        xl: 'text-5xl sm:text-6xl md:text-7xl'
    };
    return sizes[props.size];
});

const unitSize = computed(() => {
    const sizes = {
        xs: 'text-[9px]',
        sm: 'text-[10px]',
        md: 'text-xs',
        lg: 'text-sm',
        xl: 'text-base'
    };
    return sizes[props.size];
});
</script>

<template>
    <div class="flex flex-col">
        <div v-if="label" class="mb-1">
            <span class="text-xs font-mono uppercase tracking-[0.08em] text-[#666666] dark:text-[#999999]">
                {{ label }}
            </span>
        </div>

        <div class="flex items-baseline gap-1">
            <div :class="[
                'font-mono tracking-tight tabular-nums dot-matrix-number',
                'text-black dark:text-white',
                sizeClasses
            ]" style="font-family: 'Doto', 'Space Mono', monospace; font-variation-settings: 'DOTS' 20;">
                {{ value }}
            </div>
            <span v-if="unit" :class="[
                'font-mono uppercase tracking-wider text-[#666666] dark:text-[#999999]',
                unitSize
            ]">
                {{ unit }}
            </span>
        </div>
    </div>
</template>

<style scoped>
.dot-matrix-number {
    font-weight: 400;
    letter-spacing: -0.02em;
}

/* Ensure Doto font renders with optimal settings */
@supports (font-variation-settings: normal) {
    .dot-matrix-number {
        font-variation-settings: 'DOTS' 20, 'ROND' 0;
    }
}
</style>
