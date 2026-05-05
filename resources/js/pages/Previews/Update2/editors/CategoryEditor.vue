<script setup lang="ts">
import { computed, inject } from 'vue'
import { Trash2, Plus, ExternalLink, Image as ImageIcon, Film, Share2, Sparkles } from 'lucide-vue-next'
import type { PreviewTree } from '../usePreviewTree'

const props = defineProps<{ category: any }>()
defineEmits<{ (e: 'delete'): void }>()

const tree = inject<PreviewTree>('tree')!

const typeMeta = computed(() => {
  switch (props.category.type) {
    case 'banner': return { icon: ImageIcon, label: 'Banner project' }
    case 'video':  return { icon: Film,      label: 'Video project' }
    case 'social': return { icon: Share2,    label: 'Social project' }
    case 'gif':    return { icon: Sparkles,  label: 'GIF project' }
    default:       return { icon: ImageIcon, label: 'Project' }
  }
})

const onName = (e: Event) => {
  props.category.name = (e.target as HTMLInputElement).value
  tree.markDirty({ kind: 'category', id: props.category.id })
}

const addFeedback = () => {
  const fb = tree.addFeedback(props.category.id, `Round ${props.category.feedbacks.length + 1}`, '')
  if (fb) tree.markDirty({ kind: 'feedback', id: fb.id })
}
</script>

<template>
  <div class="space-y-6">
    <header class="flex items-start justify-between gap-4">
      <div class="min-w-0 flex-1">
        <div class="p2-label mb-1 inline-flex items-center gap-1.5">
          <component :is="typeMeta.icon" class="h-3 w-3" />
          {{ typeMeta.label }}
        </div>
        <input
          :value="category.name"
          class="w-full bg-transparent text-2xl font-semibold tracking-tight text-[var(--p2-text)] outline-none placeholder:text-[var(--p2-text-subtle)]"
          placeholder="Untitled project"
          @input="onName"
        />
      </div>
      <button
        type="button"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-rose-500/30 text-rose-500 transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:border-rose-500/50 hover:bg-rose-500/10"
        title="Delete project"
        aria-label="Delete project"
        @click="$emit('delete')"
      >
        <Trash2 class="h-4 w-4" />
      </button>
    </header>

    <!-- Type lock -->
    <div
      class="rounded-2xl border p-3 text-xs text-[var(--p2-text-muted)]"
      :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface-muted)' }"
    >
      Project type:
      <span class="p2-mono ml-1 font-semibold text-[var(--p2-text)]">{{ category.type }}</span>
      <span class="ml-2 text-[var(--p2-text-subtle)]">(can't change after creation)</span>
    </div>

    <!-- File transfer link -->
    <div
      v-if="category.file_transfer_slug"
      class="flex items-center justify-between gap-3 rounded-2xl border px-4 py-3"
      :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
    >
      <div>
        <p class="p2-label">File transfer</p>
        <div class="p2-mono mt-1 text-xs text-[var(--p2-text)]">{{ category.file_transfer_slug }}</div>
      </div>
      <a
        :href="`/file-transfers-view/${category.file_transfer_slug}`"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-[11px] font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)]"
        :style="{ borderColor: 'var(--p2-border)' }"
      >
        Open
        <ExternalLink class="h-3 w-3" />
      </a>
    </div>

    <!-- Feedbacks summary -->
    <section>
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold tracking-tight text-[var(--p2-text)]">Revision rounds</h3>
        <button
          type="button"
          class="inline-flex h-8 items-center gap-1 rounded-full px-3 text-[11px] font-semibold text-white transition-all duration-300 ease-[var(--p2-ease-expo)] hover:-translate-y-0.5"
          :style="{ background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }"
          @click="addFeedback"
        >
          <Plus class="h-3 w-3" />
          New round
        </button>
      </div>
      <ul v-if="category.feedbacks.length" class="space-y-1.5">
        <li
          v-for="f in category.feedbacks"
          :key="f.id"
          class="flex items-center justify-between rounded-xl border px-3 py-2 text-sm transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:border-[var(--p2-accent-muted)]"
          :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
        >
          <button
            type="button"
            class="min-w-0 flex-1 truncate text-left text-[var(--p2-text)] transition-colors duration-200 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)]"
            @click="tree.select({ kind: 'feedback', id: f.id }); tree.expandPathTo({ kind: 'feedback', id: f.id })"
          >
            {{ f.name || '(untitled)' }}
          </button>
          <span
            v-if="f.is_approved === 1"
            class="ml-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium text-emerald-600 ring-1 ring-emerald-500/30"
            style="background: rgba(16, 185, 129, 0.08);"
          >Approved</span>
        </li>
      </ul>
      <p
        v-else
        class="rounded-2xl border border-dashed px-4 py-6 text-center text-xs text-[var(--p2-text-muted)]"
        :style="{ borderColor: 'var(--p2-border)' }"
      >
        No revision rounds yet.
      </p>
    </section>
  </div>
</template>
