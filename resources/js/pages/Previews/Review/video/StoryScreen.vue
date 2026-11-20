<script setup lang="ts">
/**
 * Inside a real-shaped phone (9:19.5): the 9:16 story under the status bar,
 * rounded like the apps draw it, and the reply bar in the space below.
 * A story taller than the space is cropped to fit.
 */
import { Heart, Send } from 'lucide-vue-next'

defineProps<{ w: number; h: number }>()
</script>

<template>
  <div class="absolute inset-0 flex flex-col bg-black">
    <div class="h-7 shrink-0" />
    <div class="relative min-h-0 w-full overflow-hidden rounded-[14px]" :style="{ aspectRatio: `${w} / ${h}` }">
      <slot />
    </div>
    <div class="flex min-h-0 flex-1 items-center gap-2.5 px-3 text-white">
      <span class="flex h-8 flex-1 items-center rounded-full border border-white/40 px-3 text-[10px] text-white/70">Send message</span>
      <Heart class="h-5 w-5 shrink-0" />
      <Send class="h-5 w-5 shrink-0" />
    </div>
  </div>
</template>
