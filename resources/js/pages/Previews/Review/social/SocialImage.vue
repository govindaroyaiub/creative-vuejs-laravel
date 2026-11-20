<script setup lang="ts">
/** One image filling its box (cropped like the apps do), with a shimmer until loaded. */
import { ref } from 'vue'

defineProps<{ src: string; alt?: string; fit?: 'cover' | 'contain' }>()
const loaded = ref(false)
</script>

<template>
  <div class="relative h-full w-full overflow-hidden bg-zinc-200">
    <img
      :src="src"
      :alt="alt || ''"
      draggable="false"
      class="absolute inset-0 h-full w-full select-none"
      :class="fit === 'contain' ? 'object-contain' : 'object-cover'"
      @load="loaded = true"
      @error="loaded = true"
    />
    <div v-if="!loaded" class="p2-shimmer pointer-events-none absolute inset-0" />
    <div class="pointer-events-none absolute inset-0"><slot /></div>
  </div>
</template>
