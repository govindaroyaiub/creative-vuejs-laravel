<script setup lang="ts">
import { inject } from 'vue'
import { Trash2, Plus, Layers } from 'lucide-vue-next'
import type { PreviewTree } from '../usePreviewTree'

const props = defineProps<{ set: any }>()
defineEmits<{ (e: 'delete'): void }>()

const tree = inject<PreviewTree>('tree')!

const onName = (e: Event) => {
  props.set.name = (e.target as HTMLInputElement).value
  tree.markDirty({ kind: 'set', id: props.set.id })
}

const addVersion = () => {
  const v = tree.addVersion(props.set.id, '')
  if (v) tree.markDirty({ kind: 'version', id: v.id })
}
</script>

<template>
  <div class="space-y-6">
    <header class="flex items-start justify-between gap-4">
      <div class="min-w-0 flex-1">
        <div class="mb-1 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">
          <Layers class="h-3 w-3" />
          Concept
        </div>
        <input
          :value="set.name"
          class="w-full bg-transparent text-2xl font-semibold tracking-tight text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-zinc-100 dark:placeholder:text-zinc-600"
          placeholder="Concept name (optional)"
          @input="onName"
        />
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
          A concept can hold multiple sets of assets — for example, "Holiday concept" with size variations inside it.
        </p>
      </div>
      <button
        type="button"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-400 dark:hover:bg-rose-950/30"
        title="Delete concept"
        @click="$emit('delete')"
      >
        <Trash2 class="h-4 w-4" />
      </button>
    </header>

    <section>
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Sets in this concept</h3>
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-md border border-zinc-200 px-2.5 py-1.5 text-[11px] font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100"
          @click="addVersion"
        >
          <Plus class="h-3 w-3" /> New set
        </button>
      </div>
      <ul v-if="set.versions.length" class="space-y-1.5">
        <li
          v-for="v in set.versions"
          :key="v.id"
          class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900"
        >
          <button
            type="button"
            class="min-w-0 flex-1 truncate text-left text-zinc-800 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100"
            @click="tree.select({ kind: 'version', id: v.id }); tree.expandPathTo({ kind: 'version', id: v.id })"
          >
            {{ v.name || 'Set' }}
          </button>
          <span class="text-[11px] text-zinc-400 dark:text-zinc-500">
            {{ (v.banners?.length || 0) + (v.videos?.length || 0) + (v.socials?.length || 0) + (v.gifs?.length || 0) }} assets
          </span>
        </li>
      </ul>
      <p v-else class="rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-center text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
        No sets yet.
      </p>
    </section>
  </div>
</template>
