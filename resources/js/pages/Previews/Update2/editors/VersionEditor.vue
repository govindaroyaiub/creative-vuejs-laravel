<script setup lang="ts">
import { computed, inject } from 'vue'
import { Trash2, Plus, GitBranch, Image as ImageIcon, Film, Share2, Sparkles, GripVertical } from 'lucide-vue-next'
import draggable from 'vuedraggable'
import type { PreviewTree, AssetType } from '../usePreviewTree'

const props = defineProps<{ version: any; category: any }>()
defineEmits<{ (e: 'delete'): void }>()

const tree = inject<PreviewTree>('tree')!

const assetType = computed<AssetType>(() => props.category.type)

const listKey = computed<'banners' | 'videos' | 'socials' | 'gifs'>(() => {
  const t = assetType.value
  if (t === 'banner') return 'banners'
  if (t === 'video') return 'videos'
  if (t === 'social') return 'socials'
  return 'gifs'
})

const assets = computed({
  get: () => props.version[listKey.value] as any[],
  set: (next: any[]) => {
    props.version[listKey.value] = next
  },
})

/**
 * Re-number positions to match the new order and mark each touched asset
 * dirty so Save All picks up the change.
 */
const onReorder = () => {
  assets.value.forEach((a: any, i: number) => {
    const newPos = i + 1
    if (a.position !== newPos) {
      a.position = newPos
      tree.markDirty({ kind: 'asset', id: a.id, assetType: assetType.value })
    }
  })
}

const typeMeta = computed(() => {
  switch (assetType.value) {
    case 'banner': return { icon: ImageIcon, label: 'Banners', singular: 'banner' }
    case 'video':  return { icon: Film,      label: 'Videos',  singular: 'video' }
    case 'social': return { icon: Share2,    label: 'Socials', singular: 'social' }
    case 'gif':    return { icon: Sparkles,  label: 'GIFs',    singular: 'gif' }
  }
})

const onName = (e: Event) => {
  props.version.name = (e.target as HTMLInputElement).value
  tree.markDirty({ kind: 'version', id: props.version.id })
}

const addAsset = () => {
  const t = assetType.value
  const partial: any = {}
  if (t === 'banner' || t === 'gif') partial.size_id = null
  if (t === 'video') {
    partial.size_id = null
    partial.codec = ''
    partial.aspect_ratio = ''
    partial.fps = ''
  }
  partial.name = ''
  const a = tree.addAsset(props.version.id, t, partial)
  if (a) tree.markDirty({ kind: 'asset', id: a.id, assetType: t })
}

const assetLabel = (a: any) => {
  if (assetType.value === 'banner' || assetType.value === 'gif') {
    if (a.size?.width && a.size?.height) return `${a.size.width}×${a.size.height}`
  }
  return a.name || `Asset #${a.id}`
}
</script>

<template>
  <div class="space-y-6">
    <header class="flex items-start justify-between gap-4">
      <div class="min-w-0 flex-1">
        <div class="mb-1 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">
          <GitBranch class="h-3 w-3" />
          Set
        </div>
        <input
          :value="version.name"
          class="w-full bg-transparent text-2xl font-semibold tracking-tight text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-zinc-100 dark:placeholder:text-zinc-600"
          placeholder="Set name (optional)"
          @input="onName"
        />
      </div>
      <button
        type="button"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-400 dark:hover:bg-rose-950/30"
        title="Delete set"
        @click="$emit('delete')"
      >
        <Trash2 class="h-4 w-4" />
      </button>
    </header>

    <section>
      <div class="mb-3 flex items-center justify-between">
        <h3 class="flex items-center gap-1.5 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
          <component :is="typeMeta.icon" class="h-3.5 w-3.5" />
          {{ typeMeta.label }}
          <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">({{ assets.length }})</span>
        </h3>
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-md border border-zinc-200 px-2.5 py-1.5 text-[11px] font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100"
          @click="addAsset"
        >
          <Plus class="h-3 w-3" /> Add {{ typeMeta.singular }}
        </button>
      </div>
      <p v-if="assets.length > 1" class="mb-2 text-[11px] text-zinc-500 dark:text-zinc-400">
        Drag <GripVertical class="inline h-3 w-3 -translate-y-px" /> to reorder.
      </p>
      <draggable
        v-if="assets.length"
        v-model="assets"
        item-key="id"
        handle=".drag-handle"
        ghost-class="drag-ghost"
        animation="180"
        class="space-y-1.5"
        @end="onReorder"
      >
        <template #item="{ element: a, index: i }">
          <div
            class="group flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-2 py-2 text-sm transition dark:border-zinc-800 dark:bg-zinc-900"
          >
            <span
              class="drag-handle grid h-7 w-5 shrink-0 cursor-grab place-items-center text-zinc-300 transition hover:text-zinc-600 active:cursor-grabbing dark:text-zinc-600 dark:hover:text-zinc-300"
              :title="'Drag to reorder'"
            >
              <GripVertical class="h-3.5 w-3.5" />
            </span>
            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-zinc-100 font-mono text-[11px] font-semibold tabular-nums text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
              {{ i + 1 }}
            </span>
            <button
              type="button"
              class="min-w-0 flex-1 truncate text-left font-mono text-zinc-800 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100"
              @click="tree.select({ kind: 'asset', id: a.id, assetType }); tree.expandPathTo({ kind: 'asset', id: a.id, assetType })"
            >
              {{ assetLabel(a) }}
            </button>
            <span v-if="a.file_size" class="ml-2 shrink-0 font-mono text-[11px] tabular-nums text-zinc-400 dark:text-zinc-500">
              {{ a.file_size }}
            </span>
          </div>
        </template>
      </draggable>
      <p v-else class="rounded-lg border border-dashed border-zinc-200 px-4 py-8 text-center text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
        No {{ typeMeta.label.toLowerCase() }} yet. Click <span class="font-semibold">Add {{ typeMeta.singular }}</span> to upload.
      </p>
    </section>
  </div>
</template>
