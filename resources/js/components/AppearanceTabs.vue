<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';

interface Props {
    class?: string;
    compact?: boolean;
    iconOnly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
    compact: false,
    iconOnly: false,
});

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <div
        :class="['inline-flex gap-1 rounded-lg bg-neutral-100 dark:bg-neutral-800', props.compact ? 'p-0.5' : 'p-1', props.class]">
        <button v-for="{ value, Icon, label } in tabs" :key="value" @click="updateAppearance(value)" :class="[
            'flex items-center justify-center rounded-md transition-colors',
            props.iconOnly ? 'p-1.5' : props.compact ? 'px-2 py-1 text-xs' : 'px-3.5 py-1.5',
            appearance === value
                ? 'bg-white shadow-sm dark:bg-neutral-700 dark:text-neutral-100'
                : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
        ]" :title="label">
            <component :is="Icon"
                :class="props.iconOnly ? 'h-4 w-4' : props.compact ? 'h-3.5 w-3.5' : 'h-4 w-4 -ml-1'" />
            <span v-if="!props.iconOnly" :class="props.compact ? 'ml-1 text-xs' : 'ml-1.5 text-sm'">{{ label }}</span>
        </button>
    </div>
</template>
