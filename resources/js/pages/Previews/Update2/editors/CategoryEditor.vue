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
        <div class="mb-1 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">
          <component :is="typeMeta.icon" class="h-3 w-3" />
          {{ typeMeta.label }}
        </div>
        <input
          :value="category.name"
          class="w-full bg-transparent text-2xl font-semibold tracking-tight text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-zinc-100 dark:placeholder:text-zinc-600"
          placeholder="Untitled project"
          @input="onName"
        />
      </div>
      <button
        type="button"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-400 dark:hover:bg-rose-950/30"
        title="Delete project"
        aria-label="Delete project"
        @click="$emit('delete')"
      >
        <Trash2 class="h-4 w-4" />
      </button>
    </header>

    <!-- Type lock -->
    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
      Project type:
      <span class="ml-1 font-mono font-semibold text-zinc-700 dark:text-zinc-200">{{ category.type }}</span>
      <span class="ml-2 text-zinc-400 dark:text-zinc-500">(can't change after creation)</span>
    </div>

    <!-- File transfer link -->
    <div
      v-if="category.file_transfer_slug"
      class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900"
    >
      <div>
        <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">File transfer</div>
        <div class="mt-0.5 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ category.file_transfer_slug }}</div>
      </div>
      <a
        :href="`/file-transfers-view/${category.file_transfer_slug}`"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center gap-1 rounded-md border border-zinc-200 px-2.5 py-1.5 text-[11px] font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100"
      >
        Open
        <ExternalLink class="h-3 w-3" />
      </a>
    </div>

    <!-- Feedbacks summary -->
    <section>
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Revision rounds</h3>
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-md border border-zinc-200 px-2.5 py-1.5 text-[11px] font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100"
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
          class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900"
        >
          <button
            type="button"
            class="min-w-0 flex-1 truncate text-left text-zinc-800 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100"
            @click="tree.select({ kind: 'feedback', id: f.id }); tree.expandPathTo({ kind: 'feedback', id: f.id })"
          >
            {{ f.name || '(untitled)' }}
          </button>
          <span
            v-if="f.is_approved === 1"
            class="ml-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50"
          >Approved</span>
        </li>
      </ul>
      <p v-else class="rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-center text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
        No revision rounds yet.
      </p>
    </section>
  </div>
</template>
