<script setup lang="ts">
import { ref } from 'vue'
import { Download, ChevronDown, ChevronUp, ArrowRight, Package } from 'lucide-vue-next'

defineProps<{
  fileTransfer: any
}>()

const isMinimized = ref(false)
</script>

<template>
  <div class="max-w-xs">
    <Transition name="dock" mode="out-in">
      <div v-if="!isMinimized"
        class="flex items-center gap-3 rounded-2xl border p-3 pr-2 shadow-2xl backdrop-blur-md"
        :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface-muted)' }">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-white"
          :style="{ background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }">
          <Package class="h-5 w-5" />
        </span>
        <div class="min-w-0 flex-1">
          <div class="text-sm font-semibold text-[var(--p2-text)]">Files ready</div>
          <div class="truncate text-xs text-[var(--p2-text-muted)]">Download package available</div>
        </div>
        <a :href="`/file-transfers-view/${fileTransfer.slug}`" target="_blank" rel="noopener noreferrer"
          class="inline-flex h-9 shrink-0 items-center gap-1 rounded-full px-4 text-xs font-semibold text-white transition-all duration-300 ease-[var(--p2-ease-expo)] hover:-translate-y-0.5"
          :style="{ background: 'var(--p2-accent)' }">
          Get
          <ArrowRight class="h-3 w-3" />
        </a>
        <button type="button"
          class="ml-1 grid h-7 w-7 shrink-0 place-items-center rounded-full text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-text)]"
          aria-label="Minimize" @click="isMinimized = true">
          <ChevronDown class="h-4 w-4" />
        </button>
      </div>
      <button v-else type="button"
        class="inline-flex h-10 items-center gap-2 rounded-full border px-4 shadow-xl backdrop-blur-md transition-colors duration-300 ease-[var(--p2-ease-expo)]"
        :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface-muted)' }"
        aria-label="Show file transfer" @click="isMinimized = false">
        <Download class="h-4 w-4" :style="{ color: 'var(--p2-accent)' }" />
        <span class="text-xs font-semibold text-[var(--p2-text)]">Files ready</span>
        <ChevronUp class="h-3.5 w-3.5 text-[var(--p2-text-muted)]" />
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
