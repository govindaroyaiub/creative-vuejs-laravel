<script setup lang="ts">
/**
 * The Update page's collapsed sidebar, same concept as Show2's rail: hiding
 * the tree does not cost you navigation. A slim floating pill on the left
 * edge with restore at the top, one numbered button per project (same order
 * as the tree, newest first; the name on hover), and + to add a project.
 * Clicking a number selects that project in the editor.
 */
import { computed, inject } from 'vue'
import { PanelLeftOpen, Plus } from 'lucide-vue-next'
import type { PreviewTree } from './usePreviewTree'

defineProps<{ open: boolean }>()
defineEmits<{
  (e: 'expand'): void
  (e: 'add-category'): void
}>()

const tree = inject<PreviewTree>('tree')!

const sorted = computed(() =>
  [...tree.preview.categories].sort((a: any, b: any) => {
    const byDate = new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime()
    return byDate !== 0 ? byDate : Number(b.id) - Number(a.id)
  }),
)

/** The project the current selection belongs to (any depth). */
const activeId = computed(() => {
  const sel = tree.selection.value
  return sel ? tree.findPath(sel).category?.id ?? null : null
})

const pick = (id: any) => {
  tree.select({ kind: 'category', id })
  tree.expandPathTo({ kind: 'category', id })
}
</script>

<template>
  <div class="pointer-events-none fixed left-4 top-1/2 z-30 hidden -translate-y-1/2 lg:block">
    <Transition name="u2-rail">
      <nav
        v-if="open"
        class="p2-glass pointer-events-auto flex max-h-[70vh] flex-col items-center gap-1 overflow-y-auto rounded-full p-2 shadow-lg"
        aria-label="Projects"
        data-rail
      >
        <button
          type="button"
          class="rail-item grid h-8 w-8 shrink-0 place-items-center rounded-full text-[var(--p2-text-muted)]"
          title="Show sidebar"
          aria-label="Show sidebar"
          @click="$emit('expand')"
        >
          <PanelLeftOpen class="h-4 w-4" />
        </button>

        <span v-if="sorted.length" aria-hidden="true" class="my-0.5 h-px w-4 shrink-0" :style="{ background: 'var(--p2-hairline)' }" />

        <button
          v-for="(cat, i) in sorted"
          :key="cat.id"
          type="button"
          class="rail-item grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-semibold"
          :style="
            activeId === cat.id
              ? { background: 'var(--p2-accent-soft)', color: 'var(--p2-accent)' }
              : { color: 'var(--p2-text-muted)' }
          "
          :title="cat.name"
          :aria-label="cat.name"
          :aria-current="activeId === cat.id ? 'true' : undefined"
          :data-active="activeId === cat.id"
          @click="pick(cat.id)"
        >
          {{ i + 1 }}
        </button>

        <span aria-hidden="true" class="my-0.5 h-px w-4 shrink-0" :style="{ background: 'var(--p2-hairline)' }" />

        <button
          type="button"
          class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-white shadow-sm"
          :style="{ background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }"
          title="New project"
          aria-label="Add category"
          @click="$emit('add-category')"
        >
          <Plus class="h-4 w-4" />
        </button>
      </nav>
    </Transition>
  </div>
</template>

<style scoped>
.rail-item {
  transition: background 200ms var(--p2-ease-expo), color 200ms var(--p2-ease-expo);
}
/* Hover only on inactive items; the active one keeps its accent fill. */
.rail-item:not([data-active='true']):hover {
  background: var(--p2-surface-muted);
  color: var(--p2-text);
}
/* Slides in from its edge a beat after the column has left, like Show2. */
.u2-rail-enter-active {
  transition:
    opacity 220ms ease 120ms,
    transform 320ms var(--p2-ease-cinema) 120ms;
}
.u2-rail-leave-active {
  transition:
    opacity 160ms ease,
    transform 220ms var(--p2-ease-cinema);
}
.u2-rail-enter-from,
.u2-rail-leave-to {
  opacity: 0;
  transform: translateX(-1.5rem);
}
[data-rail] {
  scrollbar-width: none;
}
[data-rail]::-webkit-scrollbar {
  display: none;
}
@media (prefers-reduced-motion: reduce) {
  .u2-rail-enter-active,
  .u2-rail-leave-active {
    transition: opacity 120ms ease;
  }
  .u2-rail-enter-from,
  .u2-rail-leave-to {
    transform: none;
  }
}
</style>
