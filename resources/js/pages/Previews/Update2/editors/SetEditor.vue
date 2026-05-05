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
        <div class="p2-label mb-1 inline-flex items-center gap-1.5">
          <Layers class="h-3 w-3" />
          Version
        </div>
        <input
          :value="set.name"
          class="w-full rounded-xl border px-3 py-2 text-2xl font-semibold tracking-tight text-[var(--p2-text)] outline-none transition-colors duration-200 ease-[var(--p2-ease-expo)] placeholder:text-[var(--p2-text-subtle)]"
          :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
          placeholder="Version name (optional)"
          @input="onName"
        />
        <p class="mt-1 text-xs text-[var(--p2-text-muted)]">
          A version can hold multiple sets of assets — for example, "Holiday version" with size variations inside it.
        </p>
      </div>
      <button
        type="button"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-rose-500/30 text-rose-500 transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:border-rose-500/50 hover:bg-rose-500/10"
        title="Delete version"
        @click="$emit('delete')"
      >
        <Trash2 class="h-4 w-4" />
      </button>
    </header>

    <section>
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold tracking-tight text-[var(--p2-text)]">Sets in this version</h3>
        <button
          type="button"
          class="inline-flex h-8 items-center gap-1 rounded-full px-3 text-[11px] font-semibold text-white transition-all duration-300 ease-[var(--p2-ease-expo)] hover:-translate-y-0.5"
          :style="{ background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }"
          @click="addVersion"
        >
          <Plus class="h-3 w-3" /> New set
        </button>
      </div>
      <ul v-if="set.versions.length" class="space-y-1.5">
        <li
          v-for="v in set.versions"
          :key="v.id"
          class="flex items-center justify-between rounded-xl border px-3 py-2 text-sm transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:border-[var(--p2-accent-muted)]"
          :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
        >
          <button
            type="button"
            class="min-w-0 flex-1 truncate text-left text-[var(--p2-text)] transition-colors duration-200 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)]"
            @click="tree.select({ kind: 'version', id: v.id }); tree.expandPathTo({ kind: 'version', id: v.id })"
          >
            {{ v.name || 'Set' }}
          </button>
          <span class="p2-mono text-[11px] text-[var(--p2-text-subtle)]">
            {{ (v.banners?.length || 0) + (v.videos?.length || 0) + (v.socials?.length || 0) + (v.gifs?.length || 0) }} assets
          </span>
        </li>
      </ul>
      <p
        v-else
        class="rounded-2xl border border-dashed px-4 py-6 text-center text-xs text-[var(--p2-text-muted)]"
        :style="{ borderColor: 'var(--p2-border)' }"
      >
        No sets yet.
      </p>
    </section>
  </div>
</template>
