<script setup lang="ts">
import { ref } from 'vue'
import { Download, ChevronDown, ChevronUp, ArrowRight, Package } from 'lucide-vue-next'

defineProps<{
  fileTransfer: any
}>()

const isMinimized = ref(false)
</script>

<template>
  <div class="fixed bottom-5 left-5 z-40 max-w-xs">
    <Transition name="dock">
      <div
        v-if="!isMinimized"
        class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white p-3 pr-2 shadow-xl shadow-zinc-900/10 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black/40"
      >
        <span
          class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-white"
          :style="{ background: 'var(--p2-accent)' }"
        >
          <Package class="h-5 w-5" />
        </span>
        <div class="min-w-0 flex-1">
          <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Files ready</div>
          <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">Download package available</div>
        </div>
        <a
          :href="`/file-transfers-view/${fileTransfer.slug}`"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex shrink-0 items-center gap-1 rounded-lg px-3 py-2 text-xs font-semibold text-white transition hover:opacity-90"
          :style="{ background: 'var(--p2-accent)' }"
        >
          Get
          <ArrowRight class="h-3 w-3" />
        </a>
        <button
          type="button"
          class="ml-1 grid h-7 w-7 shrink-0 place-items-center rounded-md text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
          aria-label="Minimize"
          @click="isMinimized = true"
        >
          <ChevronDown class="h-4 w-4" />
        </button>
      </div>
      <button
        v-else
        type="button"
        class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-3 py-2 shadow-lg shadow-zinc-900/10 transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black/40 dark:hover:border-zinc-700"
        aria-label="Show file transfer"
        @click="isMinimized = false"
      >
        <Download class="h-4 w-4" :style="{ color: 'var(--p2-accent)' }" />
        <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">Files ready</span>
        <ChevronUp class="h-3.5 w-3.5 text-zinc-400 dark:text-zinc-500" />
      </button>
    </Transition>
  </div>
</template>

<style scoped>
.dock-enter-active,
.dock-leave-active {
  transition: all 0.25s cubic-bezier(0.32, 0.72, 0, 1);
}
.dock-enter-from,
.dock-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
