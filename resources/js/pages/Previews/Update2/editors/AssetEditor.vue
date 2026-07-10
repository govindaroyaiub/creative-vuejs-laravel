<script setup lang="ts">
import { computed, inject, onBeforeUnmount, ref, watch } from 'vue'
import {
  Trash2, Upload, Image as ImageIcon, Film, Share2, Sparkles, Download, FileArchive, Eye,
} from 'lucide-vue-next'
import vSelect from 'vue-select'
import 'vue-select/dist/vue-select.css'
import type { PreviewTree, AssetType } from '../usePreviewTree'

const props = defineProps<{
  asset: any
  assetType: AssetType | undefined
  category: any
}>()
defineEmits<{ (e: 'delete'): void }>()

const tree = inject<PreviewTree>('tree')!
const bannerSizes = inject<any[]>('bannerSizes', [])
const videoSizes = inject<any[]>('videoSizes', [])

const sizes = computed<any[]>(() =>
  props.assetType === 'video' ? videoSizes : bannerSizes
)

// ----- Sibling list / position label -----------------------------------
const sibling = computed(() => {
  if (!props.assetType) return { list: [] as any[], version: null as any }
  for (const c of tree.preview.categories) {
    for (const f of c.feedbacks) {
      for (const s of f.feedback_sets) {
        for (const v of s.versions) {
          const list = v[`${props.assetType}s` as 'banners' | 'videos' | 'socials' | 'gifs'] as any[]
          if (list.some((a: any) => a.id === props.asset.id)) {
            return { list, version: v }
          }
        }
      }
    }
  }
  return { list: [] as any[], version: null as any }
})

const positionLabel = computed(() => {
  const list = sibling.value.list
  const idx = list.findIndex((a: any) => a.id === props.asset.id)
  if (idx < 0) return ''
  return `Position ${idx + 1} of ${list.length}`
})

const goToVersion = () => {
  const v = sibling.value.version
  if (!v) return
  tree.select({ kind: 'version', id: v.id })
  tree.expandPathTo({ kind: 'version', id: v.id })
}

// ----- Per-type metadata -----------------------------------------------
const typeMeta = computed(() => {
  switch (props.assetType) {
    case 'banner': return { icon: ImageIcon, label: 'Banner', accept: '.zip,application/zip', hint: 'Upload a .zip containing an HTML5 banner with index.html.' }
    case 'video':  return { icon: Film,      label: 'Video',  accept: 'video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/x-matroska,.mp4,.mov,.avi,.wmv,.mkv', hint: 'MP4, MOV, AVI, WMV, or MKV.' }
    case 'social': return { icon: Share2,    label: 'Social', accept: 'image/jpeg,image/png,image/webp,image/svg+xml,.jpg,.jpeg,.png,.webp,.svg', hint: 'JPEG, PNG, WebP, or SVG.' }
    case 'gif':    return { icon: Sparkles,  label: 'GIF',    accept: '.gif,image/gif', hint: 'Upload an animated .gif file.' }
    default:       return { icon: ImageIcon, label: 'Asset',  accept: '*', hint: '' }
  }
})

const markDirty = () => {
  tree.markDirty({
    kind: 'asset',
    id: props.asset.id,
    ...(props.assetType ? { assetType: props.assetType } : {}),
  })
}

// ----- File handling ---------------------------------------------------
const fileInput = ref<HTMLInputElement | null>(null)
const dragActive = ref(false)
const newFileName = computed(() => (props.asset.file as File | undefined)?.name || '')
const newFileSize = computed(() => (props.asset.file as File | undefined)?.size || 0)

// Object-URLs for live previews. We track them as refs and revoke on
// change/unmount so we don't leak.
const newFileUrl = ref<string | null>(null)
const companionFileUrl = ref<string | null>(null)

watch(
  () => props.asset.file,
  (f) => {
    if (newFileUrl.value) URL.revokeObjectURL(newFileUrl.value)
    newFileUrl.value = f instanceof File ? URL.createObjectURL(f) : null
  },
  { immediate: true }
)
watch(
  () => props.asset.companion_banner,
  (f) => {
    if (companionFileUrl.value) URL.revokeObjectURL(companionFileUrl.value)
    companionFileUrl.value = f instanceof File ? URL.createObjectURL(f) : null
  },
  { immediate: true }
)
onBeforeUnmount(() => {
  if (newFileUrl.value) URL.revokeObjectURL(newFileUrl.value)
  if (companionFileUrl.value) URL.revokeObjectURL(companionFileUrl.value)
})

const onPickFile = () => fileInput.value?.click()
const onFile = (e: Event) => {
  const f = (e.target as HTMLInputElement).files?.[0]
  if (!f) return
  acceptFile(f)
}
const onDrop = (e: DragEvent) => {
  dragActive.value = false
  const f = e.dataTransfer?.files?.[0]
  if (!f) return
  acceptFile(f)
}
const acceptFile = (f: File) => {
  props.asset.file = f
  if (!props.asset.name) props.asset.name = f.name.replace(/\.[^.]+$/, '')
  markDirty()
}

// companion banner (video only)
const companionInput = ref<HTMLInputElement | null>(null)
const companionName = computed(() => (props.asset.companion_banner as File | undefined)?.name || '')
const onCompanion = (e: Event) => {
  const f = (e.target as HTMLInputElement).files?.[0]
  if (!f) return
  props.asset.companion_banner = f
  markDirty()
}

const onField = (field: string, e: Event) => {
  const t = e.target as HTMLInputElement
  ;(props.asset as any)[field] = t.value
  markDirty()
}

const existingPath = computed<string | null>(() => props.asset?.path || null)

// ----- Searchable size select ------------------------------------------
const sizeOptions = computed(() =>
  sizes.value.map((s: any) => ({
    id: s.id,
    label: `${s.width} × ${s.height}${s.name ? ` — ${s.name}` : ''}`,
  }))
)
const onSizeChange = (val: number | null) => {
  props.asset.size_id = val ?? null
  // Keep the nested `size` object in sync so the preview iframe + header
  // re-render at the newly picked dimensions instead of the stale ones.
  if (val) {
    const sz = sizes.value.find((x: any) => x.id === val)
    if (sz) props.asset.size = { id: sz.id, width: Number(sz.width), height: Number(sz.height) }
  } else {
    props.asset.size = null
  }
  markDirty()
}

// ----- Preview helpers --------------------------------------------------
const formatBytes = (n: number) => {
  if (!n) return ''
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  if (n < 1024 * 1024 * 1024) return `${(n / 1024 / 1024).toFixed(1)} MB`
  return `${(n / 1024 / 1024 / 1024).toFixed(2)} GB`
}

const isZipType = computed(() => props.assetType === 'banner')

// Preview is shown when:
// - a new file is picked (live blob URL preview, except for zips), OR
// - the asset is already saved (use the server path).
const hasNewPreview = computed(() => {
  if (!newFileUrl.value) return false
  if (isZipType.value) return false // can't render zip without unzipping
  return true
})
const hasExistingPreview = computed(() => Boolean(existingPath.value && !newFileUrl.value))
const showPreview = computed(() => hasNewPreview.value || hasExistingPreview.value || (newFileUrl.value && isZipType.value))

// Resolve dimensions from `asset.size` first; if it's missing (older row,
// or a freshly-picked size hasn't been mirrored yet) fall back to looking
// up the size_id against the injected sizes list.
const resolvedSize = computed(() => {
  if (props.asset?.size?.width && props.asset?.size?.height) return props.asset.size
  if (props.asset?.size_id) {
    const sz = sizes.value.find((x: any) => x.id === props.asset.size_id)
    if (sz) return { width: Number(sz.width), height: Number(sz.height) }
  }
  return null
})
const bannerWidth = computed(() => resolvedSize.value?.width || 300)
const bannerHeight = computed(() => resolvedSize.value?.height || 250)
</script>

<template>
  <div class="space-y-6">
    <header class="flex items-start justify-between gap-4">
      <div class="min-w-0 flex-1">
        <div class="p2-label mb-1 inline-flex items-center gap-1.5">
          <component :is="typeMeta.icon" class="h-3 w-3" />
          {{ typeMeta.label }} asset
        </div>
        <h2 class="truncate text-2xl font-semibold tracking-tight text-[var(--p2-text)]">
          <template v-if="(assetType === 'banner' || assetType === 'gif') && asset.size?.width">
            <span class="p2-mono">{{ asset.size.width }}<span class="text-[var(--p2-text-subtle)]">×</span>{{ asset.size.height }}</span>
          </template>
          <template v-else-if="asset.name">{{ asset.name }}</template>
          <template v-else>New {{ typeMeta.label.toLowerCase() }}</template>
        </h2>
        <button
          v-if="positionLabel"
          type="button"
          class="mt-2 inline-flex items-center gap-1.5 rounded-full px-3 py-0.5 text-[11px] font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-accent)]"
          :style="{ background: 'var(--p2-accent-soft)' }"
          title="Open the parent set to drag-reorder"
          @click="goToVersion"
        >
          {{ positionLabel }}
          <span class="text-[var(--p2-text-subtle)]">·</span>
          <span class="underline-offset-2 hover:underline">Reorder</span>
        </button>
      </div>
      <button
        type="button"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-rose-500/30 text-rose-500 transition-colors duration-300 ease-p2-expo hover:border-rose-500/50 hover:bg-rose-500/10"
        title="Delete asset"
        @click="$emit('delete')"
      >
        <Trash2 class="h-4 w-4" />
      </button>
    </header>

    <!-- File drop zone -->
    <section>
      <label class="p2-label mb-2 block">
        Source file
      </label>
      <div
        class="group relative flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed px-4 py-8 text-center transition-all duration-300 ease-p2-expo"
        :style="dragActive
          ? { borderColor: 'var(--p2-accent)', background: 'var(--p2-accent-soft)' }
          : { borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
        @dragover.prevent="dragActive = true"
        @dragleave.prevent="dragActive = false"
        @drop.prevent="onDrop"
      >
        <input ref="fileInput" type="file" class="hidden" :accept="typeMeta.accept" @change="onFile" />
        <Upload class="h-6 w-6 text-[var(--p2-text-subtle)]" />
        <div class="text-sm font-medium text-[var(--p2-text)]">
          <template v-if="newFileName">Replace: <span class="p2-mono">{{ newFileName }}</span></template>
          <template v-else-if="existingPath">Replace existing file</template>
          <template v-else>Drop a file here</template>
        </div>
        <button
          type="button"
          class="text-xs font-medium underline underline-offset-2 transition-colors duration-200 ease-p2-expo"
          :style="{ color: 'var(--p2-accent)' }"
          @click="onPickFile"
        >
          or browse
        </button>
        <p class="text-[11px] text-[var(--p2-text-subtle)]">{{ typeMeta.hint }}</p>
      </div>

      <a
        v-if="existingPath && !newFileName"
        :href="`/${existingPath}`"
        target="_blank"
        rel="noopener noreferrer"
        class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-accent)]"
      >
        <Download class="h-3 w-3" />
        Current: {{ existingPath.split('/').pop() }}
      </a>
    </section>

    <!-- Preview -->
    <section v-if="showPreview">
      <label class="p2-label mb-2 flex items-center gap-1.5">
        <Eye class="h-3 w-3" />
        Preview
        <span
          v-if="hasNewPreview || (newFileUrl && isZipType)"
          class="ml-1 rounded-full px-2 py-0.5 text-[9px] font-bold tracking-wider text-amber-600"
          style="background: rgba(245, 158, 11, 0.12); letter-spacing: 0.08em;"
        >
          NEW
        </span>
      </label>

      <div
        class="overflow-hidden rounded-2xl border"
        :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface-muted)' }"
      >
        <!-- Social: image -->
        <template v-if="assetType === 'social'">
          <img
            :src="newFileUrl || `/${existingPath}`"
            :alt="asset.name || 'preview'"
            class="mx-auto block max-h-[420px] w-auto"
          />
        </template>

        <!-- Video: native player -->
        <template v-else-if="assetType === 'video'">
          <video
            :src="newFileUrl || `/${existingPath}`"
            controls
            muted
            class="mx-auto block max-h-[420px] w-auto bg-black"
          />
        </template>

        <!-- Banner: live iframe at native size -->
        <template v-else-if="assetType === 'banner' && existingPath && !newFileUrl">
          <div class="flex justify-center overflow-auto p-3">
            <iframe
              :src="`/${existingPath}/index.html`"
              :width="bannerWidth"
              :height="bannerHeight"
              frameborder="0"
              scrolling="no"
              sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox"
              referrerpolicy="no-referrer"
              class="border-0 bg-white"
            />
          </div>
        </template>

        <!-- Gif: <img> preview at the picked banner-size, works for both
             freshly-picked files (blob URL) and saved files (server path). -->
        <template v-else-if="assetType === 'gif' && (newFileUrl || existingPath)">
          <div class="flex justify-center overflow-auto p-3">
            <img
              :src="newFileUrl || `/${existingPath}`"
              :alt="asset.name || 'gif preview'"
              :width="bannerWidth"
              :height="bannerHeight"
              class="block bg-white"
            />
          </div>
        </template>

        <!-- New zip pick (banner/gif): can't preview without unzipping, just show metadata -->
        <template v-else-if="newFileUrl && isZipType">
          <div class="flex items-center gap-3 px-4 py-5">
            <span
              class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl text-amber-600"
              style="background: rgba(245, 158, 11, 0.12);"
            >
              <FileArchive class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
              <div class="p2-mono truncate text-sm font-medium text-[var(--p2-text)]">
                {{ newFileName }}
              </div>
              <div class="p2-mono mt-0.5 text-[11px] tabular-nums text-[var(--p2-text-muted)]">
                {{ formatBytes(newFileSize) }}
                <span class="ml-2 text-[var(--p2-text-subtle)]">·</span>
                <span class="ml-1">Zip will be extracted on save</span>
              </div>
            </div>
          </div>
        </template>
      </div>

      <p
        v-if="hasNewPreview && (assetType === 'video' || assetType === 'social')"
        class="p2-mono mt-1.5 text-[11px] tabular-nums text-[var(--p2-text-subtle)]"
      >
        {{ newFileName }} · {{ formatBytes(newFileSize) }}
      </p>
    </section>

    <!-- Type-specific fields -->
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <!-- Name (banner / social / gif) -->
      <div v-if="assetType !== 'video'" class="sm:col-span-2">
        <label class="p2-label mb-1.5 block">Name</label>
        <input
          :value="asset.name"
          type="text"
          placeholder="Asset name"
          class="w-full rounded-xl border px-3 py-2 text-sm text-[var(--p2-text)] placeholder:text-[var(--p2-text-subtle)] focus:outline-none"
          :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
          @input="(e) => onField('name', e)"
        />
      </div>

      <!-- Size (banner / video / gif) — searchable -->
      <div v-if="assetType !== 'social'" class="sm:col-span-2">
        <label class="p2-label mb-1.5 block">Size</label>
        <v-select
          :model-value="asset.size_id || null"
          :options="sizeOptions"
          :reduce="(o: any) => o.id"
          label="label"
          placeholder="Search and select a size…"
          class="p2-vs"
          :clearable="false"
          @update:model-value="onSizeChange"
        />
      </div>

      <!-- Video metadata -->
      <template v-if="assetType === 'video'">
        <div>
          <label class="p2-label mb-1.5 block">Codec</label>
          <input
            :value="asset.codec"
            type="text"
            placeholder="e.g. h264"
            class="w-full rounded-xl border px-3 py-2 text-sm text-[var(--p2-text)] placeholder:text-[var(--p2-text-subtle)] focus:outline-none"
            :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
            @input="(e) => onField('codec', e)"
          />
        </div>
        <div>
          <label class="p2-label mb-1.5 block">FPS</label>
          <input
            :value="asset.fps"
            type="text"
            placeholder="30"
            class="w-full rounded-xl border px-3 py-2 text-sm text-[var(--p2-text)] placeholder:text-[var(--p2-text-subtle)] focus:outline-none"
            :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
            @input="(e) => onField('fps', e)"
          />
        </div>
        <div class="sm:col-span-2">
          <label class="p2-label mb-1.5 block">Aspect ratio</label>
          <input
            :value="asset.aspect_ratio"
            type="text"
            placeholder="16:9"
            class="w-full rounded-xl border px-3 py-2 text-sm text-[var(--p2-text)] placeholder:text-[var(--p2-text-subtle)] focus:outline-none"
            :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
            @input="(e) => onField('aspect_ratio', e)"
          />
        </div>

        <!-- Companion banner -->
        <div class="sm:col-span-2">
          <label class="p2-label mb-1.5 block">
            Companion banner (optional)
          </label>
          <div
            class="rounded-2xl border p-3"
            :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
          >
            <div class="flex items-center gap-3">
              <input ref="companionInput" type="file" accept="image/*" class="hidden" @change="onCompanion" />
              <button
                type="button"
                class="inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-xs font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-accent)]"
                :style="{ borderColor: 'var(--p2-border)' }"
                @click="companionInput?.click()"
              >
                <Upload class="h-3 w-3" />
                {{ companionName ? 'Replace' : 'Choose image' }}
              </button>
              <span v-if="companionName" class="p2-mono truncate text-[11px] text-[var(--p2-text-muted)]">
                {{ companionName }}
              </span>
              <a
                v-else-if="asset.companion_banner_path"
                :href="`/${asset.companion_banner_path}`"
                target="_blank"
                rel="noopener noreferrer"
                class="truncate text-[11px] text-[var(--p2-text-muted)] underline-offset-2 hover:underline"
              >
                Current: {{ asset.companion_banner_path.split('/').pop() }}
              </a>
            </div>

            <!-- Companion preview -->
            <div v-if="companionFileUrl || asset.companion_banner_path" class="mt-3">
              <img
                :src="companionFileUrl || `/${asset.companion_banner_path}`"
                alt="companion banner"
                class="mx-auto block max-h-48 w-auto rounded-xl border object-contain"
                :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface-muted)' }"
              />
            </div>
          </div>
        </div>
      </template>
    </section>

    <!-- Footer hint -->
    <p class="text-[11px] text-[var(--p2-text-subtle)]">
      Changes apply on Save All. Adding new files uploads them with the next save.
    </p>
  </div>
</template>

<style>
/* vue-select theming — light & dark, matches the rest of Update2.
   Unscoped so it reaches the library's internal markup. */
.p2-vs.vs--single .vs__selected,
.p2-vs .vs__search,
.p2-vs .vs__search:focus {
  color: rgb(39 39 42);
  font-size: 0.875rem;
}
.p2-vs .vs__dropdown-toggle {
  background-color: white;
  border-color: rgb(228 228 231);
  border-radius: 0.5rem;
  padding: 2px 4px;
  min-height: 38px;
}
.p2-vs.vs--open .vs__dropdown-toggle {
  border-color: rgb(161 161 170);
  box-shadow: 0 0 0 1px rgb(161 161 170);
}
.p2-vs .vs__dropdown-menu {
  background-color: white;
  border-color: rgb(228 228 231);
  border-radius: 0.5rem;
  box-shadow: 0 10px 30px -8px rgba(0, 0, 0, 0.15);
  margin-top: 4px;
}
.p2-vs .vs__dropdown-option {
  color: rgb(63 63 70);
  padding: 8px 12px;
  font-size: 0.875rem;
}
.p2-vs .vs__dropdown-option--highlight {
  background-color: rgb(244 244 245);
  color: rgb(24 24 27);
}
.p2-vs .vs__dropdown-option--selected {
  font-weight: 600;
}
.p2-vs .vs__no-options {
  color: rgb(161 161 170);
  font-size: 0.8125rem;
}

.dark .p2-vs.vs--single .vs__selected,
.dark .p2-vs .vs__search,
.dark .p2-vs .vs__search:focus {
  color: rgb(228 228 231);
}
.dark .p2-vs .vs__dropdown-toggle {
  background-color: rgba(9, 9, 11, 0.3);
  border-color: rgb(39 39 42);
}
.dark .p2-vs.vs--open .vs__dropdown-toggle {
  border-color: rgb(82 82 91);
  box-shadow: 0 0 0 1px rgb(82 82 91);
}
.dark .p2-vs .vs__dropdown-menu {
  background-color: rgb(24 24 27);
  border-color: rgb(39 39 42);
  box-shadow: 0 10px 30px -8px rgba(0, 0, 0, 0.6);
}
.dark .p2-vs .vs__dropdown-option {
  color: rgb(212 212 216);
}
.dark .p2-vs .vs__dropdown-option--highlight {
  background-color: rgb(39 39 42);
  color: white;
}
.dark .p2-vs .vs__open-indicator,
.dark .p2-vs .vs__clear {
  fill: rgb(161 161 170);
}
</style>
