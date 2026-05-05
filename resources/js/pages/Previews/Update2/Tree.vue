<script setup lang="ts">
import { computed, inject } from 'vue'
import { Plus, Search } from 'lucide-vue-next'
import TreeNode from './TreeNode.vue'
import type { PreviewTree } from './usePreviewTree'

const tree = inject<PreviewTree>('tree')!

defineEmits<{
  (e: 'add-category'): void
}>()

/**
 * Categories are shown newest-first (most recently created at the top).
 * Children inside each category keep their natural ordering — banners /
 * videos / etc. retain their drag-managed `position` order.
 */
const sortedCategories = computed(() =>
  [...tree.preview.categories].sort(
    (a: any, b: any) =>
      new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime()
  )
)

const filtered = computed(() => {
  const q = tree.search.value.trim().toLowerCase()
  if (!q) return sortedCategories.value
  // simple recursive filter — keep any branch that has a match anywhere in it
  const matches = (text: string) => text.toLowerCase().includes(q)
  return sortedCategories.value
    .map((c: any) => {
      const cm = matches(c.name || '')
      const feedbacks = c.feedbacks
        .map((f: any) => {
          const fm = matches(f.name || '')
          const sets = f.feedback_sets
            .map((s: any) => {
              const sm = matches(s.name || '')
              const versions = s.versions
                .map((v: any) => {
                  const vm = matches(v.name || '')
                  const assets = c.type === 'banner'
                    ? v.banners
                    : c.type === 'video'
                      ? v.videos
                      : c.type === 'social'
                        ? v.socials
                        : v.gifs
                  const aFiltered = assets.filter((a: any) => matches(a.name || ''))
                  if (vm || aFiltered.length) return { ...v, _filteredAssets: vm ? assets : aFiltered }
                  return null
                })
                .filter(Boolean)
              if (sm || versions.length) return { ...s, versions: sm ? s.versions : versions }
              return null
            })
            .filter(Boolean)
          if (fm || sets.length) return { ...f, feedback_sets: fm ? f.feedback_sets : sets }
          return null
        })
        .filter(Boolean)
      if (cm || feedbacks.length) return { ...c, feedbacks: cm ? c.feedbacks : feedbacks }
      return null
    })
    .filter(Boolean)
})
</script>

<template>
  <aside
    class="hidden w-80 shrink-0 flex-col border-r lg:flex"
    :style="{ borderColor: 'var(--p2-hairline)', background: 'var(--p2-surface)' }"
  >
    <!-- Search + add -->
    <div
      class="flex items-center gap-2 border-b px-3 py-3"
      :style="{ borderColor: 'var(--p2-hairline)' }"
    >
      <div class="relative flex-1">
        <Search class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[var(--p2-text-subtle)]" />
        <input
          v-model="tree.search.value"
          type="text"
          placeholder="Search…"
          class="w-full rounded-full border py-1.5 pl-8 pr-3 text-sm text-[var(--p2-text)] placeholder:text-[var(--p2-text-subtle)] transition-colors duration-200 ease-[var(--p2-ease-expo)] focus:outline-none"
          :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
        />
      </div>
      <button
        type="button"
        class="grid h-8 w-8 place-items-center rounded-full text-white shadow-sm transition-all duration-300 ease-[var(--p2-ease-expo)] hover:-translate-y-0.5"
        :style="{ background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }"
        title="New project"
        aria-label="Add category"
        @click="$emit('add-category')"
      >
        <Plus class="h-4 w-4" />
      </button>
    </div>

    <!-- Tree -->
    <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
      <template v-if="filtered.length">
        <TreeNode
          v-for="cat in filtered"
          :key="cat.id"
          :node="cat"
          kind="category"
          :depth="0"
        />
      </template>
      <div
        v-else-if="tree.search.value"
        class="px-3 py-6 text-center text-xs text-[var(--p2-text-muted)]"
      >
        No matches.
      </div>
      <div
        v-else
        class="px-3 py-8 text-center text-xs text-[var(--p2-text-muted)]"
      >
        No projects yet.<br />
        Click <span class="font-semibold text-[var(--p2-accent)]">+</span> to create one.
      </div>
    </div>

    <!-- Footer summary -->
    <div
      class="border-t px-4 py-2"
      :style="{ borderColor: 'var(--p2-hairline)' }"
    >
      <span class="p2-mono text-[11px] text-[var(--p2-text-muted)]">
        {{ tree.preview.categories.length }}
        {{ tree.preview.categories.length === 1 ? 'project' : 'projects' }}
      </span>
    </div>
  </aside>
</template>
