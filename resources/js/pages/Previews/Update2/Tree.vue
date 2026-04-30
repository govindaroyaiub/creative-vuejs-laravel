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
  <aside class="hidden w-80 shrink-0 flex-col border-r border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 lg:flex">
    <!-- Search + add -->
    <div class="flex items-center gap-2 border-b border-zinc-100 px-3 py-3 dark:border-zinc-800">
      <div class="relative flex-1">
        <Search class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400" />
        <input
          v-model="tree.search.value"
          type="text"
          placeholder="Search…"
          class="w-full rounded-lg border border-zinc-200 bg-white py-1.5 pl-8 pr-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-400 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-200 dark:placeholder:text-zinc-500"
        />
      </div>
      <button
        type="button"
        class="grid h-8 w-8 place-items-center rounded-lg bg-indigo-600 text-white shadow-sm transition hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400"
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
        class="px-3 py-6 text-center text-xs text-zinc-500 dark:text-zinc-400"
      >
        No matches.
      </div>
      <div
        v-else
        class="px-3 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400"
      >
        No projects yet.<br />
        Click <span class="font-semibold">+</span> to create one.
      </div>
    </div>

    <!-- Footer summary -->
    <div class="border-t border-zinc-100 px-4 py-2 text-[11px] text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
      {{ tree.preview.categories.length }}
      {{ tree.preview.categories.length === 1 ? 'project' : 'projects' }}
    </div>
  </aside>
</template>
