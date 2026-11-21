<script setup lang="ts">
/** DEMO ONLY: a generic browser window. Page content goes in the slot. */
import { Lock } from 'lucide-vue-next'

withDefaults(defineProps<{ url: string; radius?: string }>(), { radius: '12px' })
</script>

<template>
  <div class="overflow-hidden border border-black/10 bg-white text-zinc-900" :style="{ borderRadius: radius }">
    <div class="flex items-center gap-3 border-b border-zinc-200 bg-zinc-100 px-3 py-2">
      <span class="flex gap-1.5">
        <span class="h-2.5 w-2.5 rounded-full bg-[#ff5f57]" />
        <span class="h-2.5 w-2.5 rounded-full bg-[#febc2e]" />
        <span class="h-2.5 w-2.5 rounded-full bg-[#28c840]" />
      </span>
      <span class="mx-auto flex w-full max-w-sm items-center justify-center gap-1.5 rounded-md bg-white px-3 py-1 text-[10px] text-zinc-500 ring-1 ring-zinc-200">
        <Lock class="h-2.5 w-2.5" /> {{ url }}
      </span>
      <span class="w-10" />
    </div>
    <slot />
  </div>
</template>
