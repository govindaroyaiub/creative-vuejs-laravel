<script setup lang="ts">
import { computed } from 'vue'
import { ArrowLeft, Save, ExternalLink, PanelLeft, Loader2, Copy, Check } from 'lucide-vue-next'
import { ref } from 'vue'

const props = defineProps<{
  previewName: string
  clientName: string
  previewSlug: string
  dirtyCount: number
  isSaving: boolean
  elapsedSeconds: number
  sidebarOpen: boolean
}>()

const elapsedLabel = computed(() => {
  const s = props.elapsedSeconds
  if (s <= 0) return ''
  if (s < 60) return `${s}s`
  const m = Math.floor(s / 60)
  const r = s % 60
  return `${m}m ${r}s`
})

defineEmits<{
  (e: 'save'): void
  (e: 'preview'): void
  (e: 'preview2'): void
  (e: 'back'): void
  (e: 'toggle-sidebar'): void
}>()

const copied = ref(false)
const copy = async () => {
  try {
    await navigator.clipboard.writeText(props.previewSlug)
    copied.value = true
    setTimeout(() => (copied.value = false), 1400)
  } catch {
    /* ignore */
  }
}

const dirtyLabel = computed(() => {
  if (!props.dirtyCount) return ''
  return props.dirtyCount === 1 ? '1 unsaved change' : `${props.dirtyCount} unsaved changes`
})
</script>

<template>
  <header class="flex shrink-0 items-center gap-3 border-b border-zinc-200 bg-white px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900">
    <button
      type="button"
      class="grid h-9 w-9 place-items-center rounded-lg border border-zinc-200 text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:text-zinc-100"
      title="Back to previews"
      aria-label="Back"
      @click="$emit('back')"
    >
      <ArrowLeft class="h-4 w-4" />
    </button>

    <button
      type="button"
      :class="[
        'grid h-9 w-9 place-items-center rounded-lg border transition',
        sidebarOpen
          ? 'border-zinc-300 bg-zinc-100 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100'
          : 'border-zinc-200 text-zinc-600 hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:text-zinc-100',
      ]"
      title="Toggle sidebar"
      aria-label="Toggle sidebar"
      @click="$emit('toggle-sidebar')"
    >
      <PanelLeft class="h-4 w-4" />
    </button>

    <div class="hidden h-7 w-px bg-zinc-200 dark:bg-zinc-800 sm:block" />

    <div class="min-w-0 flex-1">
      <h1 class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100 sm:text-base">
        {{ previewName }}
      </h1>
      <div class="mt-0.5 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
        <span class="truncate">{{ clientName }}</span>
        <span class="hidden h-1 w-1 rounded-full bg-zinc-300 dark:bg-zinc-700 sm:inline-block" />
        <button
          type="button"
          class="hidden items-center gap-1 truncate font-mono text-[11px] tracking-tight transition hover:text-zinc-700 dark:hover:text-zinc-200 sm:inline-flex"
          :title="`Copy slug: ${previewSlug}`"
          @click="copy"
        >
          <span class="truncate">{{ previewSlug }}</span>
          <Check v-if="copied" class="h-3 w-3 text-emerald-500" />
          <Copy v-else class="h-3 w-3" />
        </button>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <Transition name="fade">
        <span
          v-if="dirtyCount"
          class="hidden items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-medium text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900/50 sm:inline-flex"
        >
          <span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
          {{ dirtyLabel }}
        </span>
      </Transition>

      <button
        type="button"
        class="hidden items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:text-zinc-100 md:inline-flex"
        title="Open preview in new tab"
        @click="$emit('preview')"
      >
        <ExternalLink class="h-3.5 w-3.5" />
        Preview
      </button>

      <button
        type="button"
        class="hidden items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:text-zinc-100 md:inline-flex"
        title="Open new preview UI"
        @click="$emit('preview2')"
      >
        <ExternalLink class="h-3.5 w-3.5" />
        Preview v2
      </button>

      <button
        type="button"
        :disabled="isSaving"
        class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-900 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-zinc-800 disabled:cursor-wait disabled:opacity-70 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white"
        @click="$emit('save')"
      >
        <Loader2 v-if="isSaving" class="h-3.5 w-3.5 animate-spin" />
        <Save v-else class="h-3.5 w-3.5" />
        {{ isSaving ? `Saving… ${elapsedLabel}` : 'Save All' }}
      </button>
    </div>
  </header>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.18s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
