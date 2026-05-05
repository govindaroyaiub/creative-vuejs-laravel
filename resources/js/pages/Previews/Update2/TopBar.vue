<script setup lang="ts">
import { computed } from 'vue'
import { ArrowLeft, Save, ExternalLink, PanelLeft, Loader2, Copy, Check, Pencil, History } from 'lucide-vue-next'
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
  (e: 'edit-info'): void
  (e: 'back'): void
  (e: 'toggle-sidebar'): void
  (e: 'view-changes'): void
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
  <header
    class="flex shrink-0 items-center gap-3 border-b px-4 py-3"
    :style="{ borderColor: 'var(--p2-hairline)', background: 'var(--p2-surface)' }"
  >
    <button
      type="button"
      class="grid h-9 w-9 place-items-center rounded-full border text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-text)]"
      :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
      title="Back to previews"
      aria-label="Back"
      @click="$emit('back')"
    >
      <ArrowLeft class="h-4 w-4" />
    </button>

    <button
      type="button"
      class="grid h-9 w-9 place-items-center rounded-full border transition-colors duration-300 ease-[var(--p2-ease-expo)]"
      :style="sidebarOpen
        ? { borderColor: 'var(--p2-accent-muted)', background: 'var(--p2-accent-soft)', color: 'var(--p2-accent)' }
        : { borderColor: 'var(--p2-border)', background: 'var(--p2-surface)', color: 'var(--p2-text-muted)' }"
      title="Toggle sidebar"
      aria-label="Toggle sidebar"
      @click="$emit('toggle-sidebar')"
    >
      <PanelLeft class="h-4 w-4" />
    </button>

    <div class="hidden h-7 w-px sm:block" :style="{ background: 'var(--p2-border)' }" />

    <div class="min-w-0 flex-1">
      <p class="p2-label">Editing</p>
      <h1 class="mt-0.5 truncate text-sm font-semibold tracking-tight text-[var(--p2-text)] sm:text-base">
        {{ previewName }}
      </h1>
      <div class="mt-0.5 flex items-center gap-2 text-xs text-[var(--p2-text-muted)]">
        <span class="truncate">{{ clientName }}</span>
        <span class="hidden h-1 w-1 rounded-full sm:inline-block" :style="{ background: 'var(--p2-border-strong)' }" />
        <button
          type="button"
          class="p2-mono hidden items-center gap-1 truncate text-[11px] tracking-tight transition-colors duration-200 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-text)] sm:inline-flex"
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
          class="hidden items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium text-amber-700 ring-1 ring-amber-500/30 sm:inline-flex"
          style="background: rgba(245, 158, 11, 0.10);"
        >
          <span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
          {{ dirtyLabel }}
        </span>
      </Transition>

      <!-- Persistent "History" button — always visible, opens the audit
           log panel for this preview. -->
      <button
        type="button"
        @click="$emit('view-changes')"
        title="View saved-changes history"
        class="inline-flex h-9 items-center gap-1.5 rounded-full border px-3.5 text-xs font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)]"
        :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
      >
        <History class="h-3.5 w-3.5" />
        <span class="hidden md:inline">History</span>
      </button>

      <button
        type="button"
        class="hidden h-9 items-center gap-1.5 rounded-full border px-3.5 text-xs font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)] md:inline-flex"
        :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
        title="Edit preview info (name, client, palette, team)"
        @click="$emit('edit-info')"
      >
        <Pencil class="h-3.5 w-3.5" />
        Edit Info
      </button>

      <button
        type="button"
        class="hidden h-9 items-center gap-1.5 rounded-full border px-3.5 text-xs font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)] md:inline-flex"
        :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
        title="Open preview in new tab"
        @click="$emit('preview')"
      >
        <ExternalLink class="h-3.5 w-3.5" />
        Preview
      </button>

      <button
        type="button"
        class="hidden h-9 items-center gap-1.5 rounded-full border px-3.5 text-xs font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)] md:inline-flex"
        :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
        title="Open new preview UI"
        @click="$emit('preview2')"
      >
        <ExternalLink class="h-3.5 w-3.5" />
        Preview v2
      </button>

      <button
        type="button"
        :disabled="isSaving"
        class="inline-flex h-9 items-center gap-1.5 rounded-full px-4 text-xs font-semibold text-white shadow-sm transition-all duration-300 ease-[var(--p2-ease-expo)] hover:-translate-y-0.5 disabled:cursor-wait disabled:opacity-70 disabled:hover:translate-y-0"
        :style="{ background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }"
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
