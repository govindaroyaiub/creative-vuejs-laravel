<script setup lang="ts">
import { computed, inject, ref } from 'vue'
import {
  Trash2, GitBranch, Image as ImageIcon, Film, Share2, Sparkles,
  GripVertical, Upload, X,
} from 'lucide-vue-next'
import draggable from 'vuedraggable'
import vSelect from 'vue-select'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import type { PreviewTree, AssetType } from '../usePreviewTree'
import { isDbId } from '../usePreviewTree'

const props = defineProps<{ version: any; category: any }>()
defineEmits<{ (e: 'delete'): void }>()

const tree = inject<PreviewTree>('tree')!
const bannerSizes = inject<any[]>('bannerSizes', [])

// Filename → size auto-detect.
// Two passes so we don't false-match years/IDs around `_` / `-` separators
// (e.g. "April2026 - 468x60" must resolve to 468×60, not 2026×468):
//   1. Strict pass: only `x` / `X` / `×` between the numbers
//   2. Permissive pass: `_` and `-` allowed too
// Each pass walks every match and picks the first that exists in bannerSizes.
const SIZE_RE_STRICT = /(\d{2,4})\s*[xX×]\s*(\d{2,4})/g
const SIZE_RE_LOOSE  = /(\d{2,4})\s*[xX×_-]\s*(\d{2,4})/g

const findKnownSize = (filename: string, re: RegExp) => {
  for (const m of filename.matchAll(re)) {
    const w = parseInt(m[1]!, 10)
    const h = parseInt(m[2]!, 10)
    const found = bannerSizes.find((s: any) => Number(s.width) === w && Number(s.height) === h)
    if (found) return { id: found.id, width: Number(found.width), height: Number(found.height) }
  }
  return null
}

const detectSize = (filename: string): { id: number; width: number; height: number } | null => {
  return findKnownSize(filename, SIZE_RE_STRICT) || findKnownSize(filename, SIZE_RE_LOOSE)
}

const sizeOptions = computed(() =>
  bannerSizes.map((s: any) => ({
    id: s.id,
    label: `${s.width} × ${s.height}${s.name ? ` — ${s.name}` : ''}`,
  }))
)

const sizeChip = (a: any): string | null => {
  if (a.size?.width && a.size?.height) return `${a.size.width}×${a.size.height}`
  if (a.size_id) {
    const s = bannerSizes.find((x: any) => x.id === a.size_id)
    if (s) return `${s.width}×${s.height}`
  }
  return null
}

const onInlineSize = (a: any, val: number | null) => {
  a.size_id = val ?? null
  if (val) {
    const sz = bannerSizes.find((x: any) => x.id === val)
    if (sz) a.size = { id: sz.id, width: sz.width, height: sz.height }
  } else {
    a.size = null
  }
  tree.markDirty({ kind: 'asset', id: a.id, assetType: assetType.value })
}

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

const typeMeta = computed(() => {
  switch (assetType.value) {
    case 'banner':
      return {
        icon: ImageIcon, label: 'Banners', singular: 'banner',
        accept: '.zip,application/zip',
        hint: 'Each .zip should contain an HTML5 banner with index.html.',
      }
    case 'video':
      return {
        icon: Film, label: 'Videos', singular: 'video',
        accept: 'video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/x-matroska,.mp4,.mov,.avi,.wmv,.mkv',
        hint: 'MP4, MOV, AVI, WMV, MKV.',
      }
    case 'social':
      return {
        icon: Share2, label: 'Social posts', singular: 'image',
        accept: 'image/jpeg,image/png,image/webp,image/svg+xml,.jpg,.jpeg,.png,.webp,.svg',
        hint: 'JPEG, PNG, WebP, SVG.',
      }
    case 'gif':
      return {
        icon: Sparkles, label: 'GIFs', singular: 'gif',
        accept: '.gif,image/gif',
        hint: 'Animated .gif files.',
      }
  }
})

const onName = (e: Event) => {
  props.version.name = (e.target as HTMLInputElement).value
  tree.markDirty({ kind: 'version', id: props.version.id })
}

// ----- Reorder -----------------------------------------------------------
const onReorder = () => {
  assets.value.forEach((a: any, i: number) => {
    const newPos = i + 1
    if (a.position !== newPos) {
      a.position = newPos
      tree.markDirty({ kind: 'asset', id: a.id, assetType: assetType.value })
    }
  })
}

// ----- Multi-file upload -------------------------------------------------
const dragActive = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

const triggerPick = () => fileInput.value?.click()

const addFiles = (filesIn: FileList | File[]) => {
  const files = Array.from(filesIn)
  if (!files.length) return
  const t = assetType.value

  // Build partial asset objects up-front so we can sort them before pushing
  // them into the tree.
  const partials = files.map((f) => {
    const partial: any = {
      name: f.name.replace(/\.[^.]+$/, ''),
      file: f,
    }
    if (t === 'banner' || t === 'gif') {
      const detected = detectSize(f.name)
      partial.size_id = detected?.id ?? null
      if (detected) {
        partial.size = { id: detected.id, width: detected.width, height: detected.height }
      }
    }
    if (t === 'video') {
      partial.size_id = null
      partial.codec = ''
      partial.aspect_ratio = ''
      partial.fps = ''
    }
    return partial
  })

  // Auto-sort banners (and gifs, same dimension story) by detected size:
  // smallest width first, with height as a tiebreaker. Files with no
  // detectable size sink to the bottom so the user notices and picks one.
  if (t === 'banner' || t === 'gif') {
    partials.sort((a, b) => {
      const aw = a.size?.width ?? Number.POSITIVE_INFINITY
      const bw = b.size?.width ?? Number.POSITIVE_INFINITY
      if (aw !== bw) return aw - bw
      const ah = a.size?.height ?? Number.POSITIVE_INFINITY
      const bh = b.size?.height ?? Number.POSITIVE_INFINITY
      return ah - bh
    })
  }

  partials.forEach((partial) => {
    const a = tree.addAsset(props.version.id, t, partial, { select: false })
    if (a) tree.markDirty({ kind: 'asset', id: a.id, assetType: t })
  })

  // Renumber positions to match the new list order
  assets.value.forEach((a: any, i: number) => {
    a.position = i + 1
  })
}

const onDrop = (e: DragEvent) => {
  dragActive.value = false
  const files = e.dataTransfer?.files
  if (!files || !files.length) return
  addFiles(files)
}

const onPick = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files) return
  addFiles(target.files)
  // reset value so picking the same file again still triggers change
  target.value = ''
}

// ----- Remove (per-row) --------------------------------------------------
const removeAsset = (a: any) => {
  const t = assetType.value
  // Unsaved (locally added) → just drop it from the tree
  if (!isDbId(a.id)) {
    tree.removeNode({ kind: 'asset', id: a.id, assetType: t })
    return
  }
  // Saved → confirm, then hit the delete endpoint, then drop locally
  Swal.fire({
    icon: 'warning',
    title: 'Delete this asset?',
    text: 'This cannot be undone.',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    confirmButtonColor: '#e11d48',
  }).then((r) => {
    if (!r.isConfirmed) return
    router.delete(`/previews/${t}/delete/${a.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        tree.removeNode({ kind: 'asset', id: a.id, assetType: t })
        Swal.fire({ icon: 'success', title: 'Deleted', toast: true, position: 'top-end', timer: 900, showConfirmButton: false })
      },
      onError: (errs) => console.error('Delete failed', errs),
    })
  })
}

// ----- Display helpers ---------------------------------------------------
const assetLabel = (a: any) => a.name || `Asset #${a.id}`

const isBannerOrGif = computed(() => assetType.value === 'banner' || assetType.value === 'gif')
</script>

<template>
  <div class="space-y-6">
    <header class="flex items-start justify-between gap-4">
      <div class="min-w-0 flex-1">
        <div class="mb-1 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">
          <GitBranch class="h-3 w-3" />
          Set
        </div>
        <input :value="version.name"
          class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-2xl font-semibold tracking-tight text-zinc-900 outline-none transition placeholder:text-zinc-400 hover:border-zinc-300 focus:border-zinc-400 focus:ring-1 focus:ring-zinc-400 dark:border-zinc-800 dark:bg-zinc-950/30 dark:text-zinc-100 dark:placeholder:text-zinc-600 dark:hover:border-zinc-700 dark:focus:border-zinc-600 dark:focus:ring-zinc-600"
          placeholder="Set name (optional)" @input="onName" />
      </div>
      <button type="button"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-400 dark:hover:bg-rose-950/30"
        title="Delete set" @click="$emit('delete')">
        <Trash2 class="h-4 w-4" />
      </button>
    </header>

    <!-- Section title with count -->
    <div class="flex items-center gap-1.5 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
      <component :is="typeMeta!.icon" class="h-3.5 w-3.5" />
      {{ typeMeta!.label }}
      <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">({{ assets.length }})</span>
    </div>

    <!-- Multi-file drop zone -->
    <section>
      <div :class="[
        'group relative flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed text-center transition',
        dragActive
          ? 'border-zinc-400 bg-zinc-50 dark:border-zinc-500 dark:bg-zinc-900'
          : 'border-zinc-200 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900',
        assets.length ? 'px-4 py-5' : 'px-4 py-12',
      ]" @click="triggerPick" @dragover.prevent="dragActive = true" @dragleave.prevent="dragActive = false"
        @drop.prevent="onDrop">
        <input ref="fileInput" type="file" multiple :accept="typeMeta!.accept" class="hidden" @change="onPick" />
        <Upload class="h-5 w-5 text-zinc-400 dark:text-zinc-500" />
        <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
          Drop {{ typeMeta!.singular }}{{ typeMeta!.singular.endsWith('s') ? 'es' : 's' }} here, or
          <span class="underline decoration-zinc-300 underline-offset-2 dark:decoration-zinc-700">click to browse</span>
        </div>
        <p class="text-[11px] text-zinc-400 dark:text-zinc-500">
          {{ typeMeta!.hint }} · Multiple files supported.
        </p>
      </div>
    </section>

    <!-- Draggable asset list -->
    <section v-if="assets.length">
      <p v-if="assets.length > 1" class="mb-2 text-[11px] text-zinc-500 dark:text-zinc-400">
        Drag
        <GripVertical class="inline h-3 w-3 -translate-y-px" /> to reorder. Click an item to edit details.
      </p>
      <draggable v-model="assets" item-key="id" handle=".drag-handle" ghost-class="drag-ghost" animation="180"
        class="space-y-1.5" @end="onReorder">
        <template #item="{ element: a, index: i }">
          <div
            class="group flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-2 py-2 text-sm transition dark:border-zinc-800 dark:bg-zinc-900"
            :class="isBannerOrGif && !a.size_id ? 'border-amber-300 dark:border-amber-700/60' : ''">
            <span
              class="drag-handle grid h-7 w-5 shrink-0 cursor-grab place-items-center text-zinc-300 transition hover:text-zinc-600 active:cursor-grabbing dark:text-zinc-600 dark:hover:text-zinc-300"
              title="Drag to reorder">
              <GripVertical class="h-3.5 w-3.5" />
            </span>
            <span
              class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-zinc-100 font-mono text-[11px] font-semibold tabular-nums text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
              {{ i + 1 }}
            </span>
            <button type="button"
              class="min-w-0 flex-1 truncate text-left font-mono text-zinc-800 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100"
              @click="tree.select({ kind: 'asset', id: a.id, assetType }); tree.expandPathTo({ kind: 'asset', id: a.id, assetType })">
              {{ assetLabel(a) }}
            </button>

            <!-- Size pill (banners + gifs, when size is set) -->
            <span v-if="isBannerOrGif && sizeChip(a)"
              class="shrink-0 rounded-md bg-zinc-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold tabular-nums text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
              {{ sizeChip(a) }}
            </span>

            <!-- Inline size picker (banners + gifs, when size is missing) -->
            <div v-else-if="isBannerOrGif" class="size-picker-wrap shrink-0" @click.stop>
              <v-select :model-value="null" :options="sizeOptions" :reduce="(o: any) => o.id" label="label"
                placeholder="Pick size" :clearable="false" :append-to-body="true" class="p2-vs p2-vs-sm"
                @update:model-value="(val: any) => onInlineSize(a, val)" />
            </div>

            <span v-if="!isDbId(a.id)"
              class="shrink-0 rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold tracking-wider text-amber-700 dark:bg-amber-950/40 dark:text-amber-300"
              title="Unsaved — will be uploaded on Save All">
              NEW
            </span>
            <span v-else-if="a.file_size"
              class="shrink-0 font-mono text-[11px] tabular-nums text-zinc-400 dark:text-zinc-500">
              {{ a.file_size }}
            </span>

            <button
              type="button"
              class="grid h-7 w-7 shrink-0 place-items-center rounded-md text-zinc-400 transition hover:bg-rose-50 hover:text-rose-600 dark:text-zinc-500 dark:hover:bg-rose-950/30 dark:hover:text-rose-400"
              :title="isDbId(a.id) ? 'Delete asset' : 'Remove'"
              :aria-label="isDbId(a.id) ? 'Delete asset' : 'Remove asset'"
              @click.stop="removeAsset(a)"
            >
              <X class="h-3.5 w-3.5" />
            </button>
          </div>
        </template>
      </draggable>
    </section>
  </div>
</template>

<style scoped>
.drag-ghost {
  opacity: 0.4;
}

.size-picker-wrap {
  width: 9.5rem;
}
</style>

<style>
/* Compact variant for the inline size picker — sits inside an asset row
   so we trim the height and font size. The base .p2-vs theming is defined
   in AssetEditor.vue's unscoped style and applies here too. */
.p2-vs-sm .vs__dropdown-toggle {
  min-height: 30px;
  padding: 0 4px;
}

.p2-vs-sm .vs__selected,
.p2-vs-sm .vs__search,
.p2-vs-sm .vs__search:focus {
  font-size: 0.75rem;
  margin: 0 0 0 4px;
  padding: 0;
}

.p2-vs-sm .vs__actions {
  padding: 0 4px 0 0;
}

.p2-vs-sm .vs__open-indicator {
  transform: scale(0.85);
}
</style>
