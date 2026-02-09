<script setup lang="ts">
interface Props {
    class?: string;
}

withDefaults(defineProps<Props>(), {
    class: '',
});
</script>

<template>
    <div :class="['overflow-y-auto', $props.class]">
        <slot />
    </div>
</template>
