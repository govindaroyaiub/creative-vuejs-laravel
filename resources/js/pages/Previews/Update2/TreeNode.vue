<script setup lang="ts">
import { computed, inject } from 'vue'
import {
  ChevronRight, ChevronDown,
  Image as ImageIcon, Film, Share2, Sparkles,
  Folder, MessageSquare, Layers, GitBranch,
  CheckCircle2,
} from 'lucide-vue-next'
import type { NodeKind, PreviewTree, AssetType } from './usePreviewTree'
import { isDbId } from './usePreviewTree'

const props = defineProps<{
  node: any
  kind: NodeKind
  depth: number
  /** Asset type if kind === 'asset' */
  assetType?: AssetType
  /** parent category type — propagated to determine version's asset list */
  categoryType?: AssetType
}>()

const tree = inject<PreviewTree>('tree')!

const key = computed(() =>
  props.kind === 'asset'
    ? `asset:${props.assetType}:${props.node.id}`
    : `${props.kind}:${props.node.id}`
)

const isSelected = computed(() => {
  const s = tree.selection.value
  if (!s) return false
  if (s.kind !== props.kind) return false
  if (s.id !== props.node.id) return false
  if (s.kind === 'asset' && s.assetType !== props.assetType) return false
  return true
})

const isOpen = computed(() => tree.isExpanded(key.value))
const isUnsaved = computed(() => !isDbId(props.node.id))

// ----- Determine children + child kind -----
const childList = computed(() => {
  if (props.kind === 'category') return props.node.feedbacks || []
  if (props.kind === 'feedback') return props.node.feedback_sets || []
  if (props.kind === 'set') return props.node.versions || []
  if (props.kind === 'version') {
    const t = props.categoryType
    if (t === 'banner') return props.node.banners || []
    if (t === 'video') return props.node.videos || []
    if (t === 'social') return props.node.socials || []
    if (t === 'gif') return props.node.gifs || []
  }
  return []
})

const childKind = computed<NodeKind | null>(() => {
  if (props.kind === 'category') return 'feedback'
  if (props.kind === 'feedback') return 'set'
  if (props.kind === 'set') return 'version'
  if (props.kind === 'version') return 'asset'
  return null
})

// ----- Display details -----
const icon = computed(() => {
  if (props.kind === 'category' || props.kind === 'asset') {
    const t = props.kind === 'category' ? props.node.type : props.assetType
    if (t === 'banner') return ImageIcon
    if (t === 'video') return Film
    if (t === 'social') return Share2
    if (t === 'gif') return Sparkles
  }
  if (props.kind === 'feedback') return MessageSquare
  if (props.kind === 'set') return Layers
  if (props.kind === 'version') return GitBranch
  return Folder
})

const label = computed(() => {
  if (props.kind === 'asset') {
    if (props.assetType === 'banner' || props.assetType === 'gif') {
      const w = props.node?.size?.width
      const h = props.node?.size?.height
      if (w && h) return `${w}×${h}`
    }
    if (props.assetType === 'video') {
      const w = props.node?.size?.width
      const h = props.node?.size?.height
      if (w && h) return `${w}×${h}`
      if (props.node?.codec) return props.node.codec
    }
    return props.node.name || `Asset #${props.node.id}`
  }
  if (props.kind === 'set' && !props.node.name) return 'Concept'
  if (props.kind === 'version' && !props.node.name) return 'Set'
  return props.node.name || '(untitled)'
})

const childCount = computed(() => childList.value.length)

// ----- Interactions -----
const onSelect = (e: MouseEvent) => {
  e.stopPropagation()
  tree.select({
    kind: props.kind,
    id: props.node.id,
    ...(props.kind === 'asset' ? { assetType: props.assetType } : {}),
  })
  if (props.kind !== 'asset' && childCount.value > 0 && !isOpen.value) {
    tree.toggleExpand(key.value)
  }
}

const onToggle = (e: MouseEvent) => {
  e.stopPropagation()
  tree.toggleExpand(key.value)
}

const childAssetType = computed<AssetType | undefined>(() =>
  props.kind === 'category' ? (props.node.type as AssetType) : props.categoryType
)
</script>

<template>
  <div>
    <button
      type="button"
      :class="[
        'group relative flex w-full items-center gap-1.5 rounded-md py-1.5 pr-2 text-left text-[13px] transition',
        isSelected
          ? 'bg-zinc-900 text-white shadow-sm dark:bg-zinc-100 dark:text-zinc-900'
          : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800/70',
      ]"
      :style="{ paddingLeft: depth * 16 + 8 + 'px' }"
      @click="onSelect"
    >
      <!-- Expand chevron (or spacer for assets) -->
      <span
        v-if="kind !== 'asset'"
        class="grid h-4 w-4 shrink-0 place-items-center"
        @click.stop="onToggle"
      >
        <ChevronDown v-if="isOpen" class="h-3 w-3" />
        <ChevronRight v-else class="h-3 w-3" />
      </span>
      <span v-else class="h-4 w-4 shrink-0" />

      <!-- Type icon -->
      <component :is="icon" class="h-3.5 w-3.5 shrink-0 opacity-80" />

      <!-- Label -->
      <span class="min-w-0 flex-1 truncate font-medium">{{ label }}</span>

      <!-- Status badges -->
      <CheckCircle2
        v-if="kind === 'feedback' && node.is_approved === 1"
        class="h-3 w-3 shrink-0 text-emerald-500"
      />
      <span
        v-if="isUnsaved"
        class="h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"
        title="Unsaved"
      />
      <span
        v-if="!isSelected && kind !== 'asset' && childCount > 0"
        class="shrink-0 rounded-md bg-zinc-100 px-1 text-[10px] font-mono tabular-nums text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
      >
        {{ childCount }}
      </span>
    </button>

    <!-- Recursive children -->
    <div v-if="isOpen && childKind && childList.length">
      <TreeNode
        v-for="child in childList"
        :key="child.id"
        :node="child"
        :kind="childKind"
        :depth="depth + 1"
        v-bind="{
          ...(childKind === 'asset' && childAssetType ? { assetType: childAssetType } : {}),
          ...(childAssetType ? { categoryType: childAssetType } : {}),
        }"
      />
    </div>
  </div>
</template>
