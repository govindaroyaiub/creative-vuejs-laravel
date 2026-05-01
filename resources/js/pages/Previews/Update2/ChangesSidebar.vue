<script setup lang="ts">
import { computed, inject, ref, watch } from 'vue'
import { X, Dot, Pencil, Trash2, RefreshCw, Loader2, ChevronRight, ChevronDown } from 'lucide-vue-next'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
dayjs.extend(relativeTime)
import type { PreviewTree } from './usePreviewTree'

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const tree = inject<PreviewTree>('tree')!

interface ActivityRow {
  id: number
  log_name: string | null
  description: string  // 'created' | 'updated' | 'deleted' (Spatie defaults)
  subject_type: string | null
  subject_id: number | null
  causer_id: number | null
  causer?: { id: number; name: string } | null
  properties?: { attributes?: Record<string, any>; old?: Record<string, any> } | null
  batch_uuid: string | null
  created_at: string
}

interface PageMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const rows = ref<ActivityRow[]>([])
const meta = ref<PageMeta | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)
const page = ref(1)

const previewId = computed<number | null>(() => {
  const id = (tree.preview as any)?.id
  return typeof id === 'number' ? id : null
})

async function fetchPage(p: number, append = false) {
  if (!previewId.value) return
  loading.value = true
  error.value = null
  try {
    const res = await fetch(`/previews/${previewId.value}/activity?page=${p}&per_page=25`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json()
    const next: ActivityRow[] = data.data || []
    rows.value = append ? rows.value.concat(next) : next
    meta.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total,
    }
  } catch (e: any) {
    error.value = e?.message || 'Failed to load history'
  } finally {
    loading.value = false
  }
}

function refresh() {
  page.value = 1
  fetchPage(1, false)
}

function loadMore() {
  if (!meta.value || meta.value.current_page >= meta.value.last_page) return
  page.value = meta.value.current_page + 1
  fetchPage(page.value, true)
}

// Fetch when the sidebar opens; reset state when it closes.
watch(() => props.open, (isOpen) => {
  if (isOpen) refresh()
})

// ---------------------------------------------------------------------
// Display helpers
// ---------------------------------------------------------------------

const SUBJECT_LABELS: Record<string, string> = {
  'App\\Models\\newPreview': 'Preview',
  'App\\Models\\newCategory': 'Category',
  'App\\Models\\newFeedback': 'Feedback',
  'App\\Models\\newFeedbackSet': 'Set',
  'App\\Models\\newVersion': 'Version',
  'App\\Models\\newBanner': 'Banner',
  'App\\Models\\newVideo': 'Video',
  'App\\Models\\newSocial': 'Social',
  'App\\Models\\newGif': 'Gif',
}

const subjectKind = (row: ActivityRow): string =>
  (row.subject_type && SUBJECT_LABELS[row.subject_type]) || 'Item'

const subjectLabel = (row: ActivityRow): string => {
  const attrs = row.properties?.attributes || row.properties?.old || {}
  return (attrs as any).name || `${subjectKind(row)} #${row.subject_id ?? '?'}`
}

const verb = (description: string): string => {
  const d = (description || '').toLowerCase()
  if (d === 'created') return 'created'
  if (d === 'updated') return 'updated'
  if (d === 'deleted') return 'deleted'
  return d || 'changed'
}

const verbColor = (description: string): string => {
  const d = (description || '').toLowerCase()
  if (d === 'created') return 'text-emerald-700 dark:text-emerald-400'
  if (d === 'updated') return 'text-amber-700 dark:text-amber-400'
  if (d === 'deleted') return 'text-rose-700 dark:text-rose-400'
  return 'text-zinc-600 dark:text-zinc-400'
}

const verbIcon = (description: string) => {
  const d = (description || '').toLowerCase()
  if (d === 'created') return Dot
  if (d === 'deleted') return Trash2
  return Pencil
}

const formatValue = (v: any): string => {
  if (v == null || v === '') return '—'
  if (typeof v === 'object') return JSON.stringify(v)
  const s = String(v)
  return s.length > 60 ? s.slice(0, 60) + '…' : s
}

// Field-level diff inferred from Spatie's properties.{old,attributes}.
// Only attributes that actually changed are returned.
function fieldDiffs(row: ActivityRow): Array<{ name: string; before: any; after: any }> {
  if ((row.description || '').toLowerCase() !== 'updated') return []
  const oldAttrs = row.properties?.old || {}
  const newAttrs = row.properties?.attributes || {}
  const out: Array<{ name: string; before: any; after: any }> = []
  for (const k of Object.keys(newAttrs)) {
    if (k === 'updated_at' || k === 'created_at') continue
    if ((oldAttrs as any)[k] !== (newAttrs as any)[k]) {
      out.push({ name: k, before: (oldAttrs as any)[k], after: (newAttrs as any)[k] })
    }
  }
  return out
}

// Group consecutive rows that share a batch_uuid into one collapsible event.
// Order is preserved; rows without a batch are shown standalone.
interface Group {
  key: string
  batchUuid: string | null
  rows: ActivityRow[]
  causerName: string
  createdAt: string
}

const groups = computed<Group[]>(() => {
  const out: Group[] = []
  let current: Group | null = null
  for (const r of rows.value) {
    const causer = r.causer?.name || 'System'
    if (r.batch_uuid && current && current.batchUuid === r.batch_uuid) {
      current.rows.push(r)
      continue
    }
    current = {
      key: r.batch_uuid ? `b-${r.batch_uuid}` : `r-${r.id}`,
      batchUuid: r.batch_uuid,
      rows: [r],
      causerName: causer,
      createdAt: r.created_at,
    }
    out.push(current)
  }
  return out
})

const expandedGroups = ref<Set<string>>(new Set())
function toggleGroup(key: string) {
  const next = new Set(expandedGroups.value)
  next.has(key) ? next.delete(key) : next.add(key)
  expandedGroups.value = next
}

// A group always has at least one row (we push the first row when creating
// the group), so this is safe — but TS can't narrow that on indexed access.
const firstRow = (g: Group): ActivityRow => g.rows[0]!

const KIND_TO_NODE_KIND: Record<string, 'category' | 'feedback' | 'set' | 'version' | 'asset'> = {
  Category: 'category',
  Feedback: 'feedback',
  Set: 'set',
  Version: 'version',
  Banner: 'asset',
  Video: 'asset',
  Social: 'asset',
  Gif: 'asset',
}

const KIND_TO_ASSET_TYPE: Record<string, 'banner' | 'video' | 'social' | 'gif'> = {
  Banner: 'banner',
  Video: 'video',
  Social: 'social',
  Gif: 'gif',
}

// Best-effort jump to the still-existing tree node when a row is clicked.
function onRowClick(row: ActivityRow) {
  if (!row.subject_id) return
  if ((row.description || '').toLowerCase() === 'deleted') return
  const kindLabel = subjectKind(row)
  const nodeKind = KIND_TO_NODE_KIND[kindLabel]
  if (!nodeKind) return
  const sel: any = { kind: nodeKind, id: row.subject_id }
  if (nodeKind === 'asset') sel.assetType = KIND_TO_ASSET_TYPE[kindLabel]
  // Verify the node is still in the live tree before selecting.
  if (tree.findPath(sel).category) {
    tree.select(sel)
    tree.expandPathTo(sel)
  }
}

const relTime = (iso: string) => {
  try { return dayjs(iso).fromNow() } catch { return iso }
}
</script>

<template>
  <Transition name="slide">
    <aside v-if="open"
      class="fixed top-0 right-0 z-40 flex h-full w-[24rem] flex-col border-l border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-950">
      <!-- Header -->
      <div class="flex items-center justify-between gap-2 border-b border-zinc-200 px-3 py-2 dark:border-zinc-800">
        <div class="flex items-center gap-2 min-w-0">
          <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">History</h2>
          <span v-if="meta"
            class="inline-flex items-center px-1.5 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-[10px] font-mono tabular-nums text-zinc-600 dark:text-zinc-400">
            {{ meta.total }}
          </span>
        </div>
        <div class="flex items-center gap-1">
          <button type="button" @click="refresh" :disabled="loading"
            class="p-1.5 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded transition-colors disabled:opacity-50"
            title="Refresh">
            <RefreshCw class="w-4 h-4" :class="loading ? 'animate-spin' : ''" :stroke-width="1.5" />
          </button>
          <button type="button" @click="emit('close')"
            class="p-1.5 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded transition-colors"
            aria-label="Close">
            <X class="w-4 h-4" :stroke-width="1.5" />
          </button>
        </div>
      </div>

      <!-- States -->
      <div v-if="loading && rows.length === 0" class="flex-1 flex items-center justify-center px-6 text-center">
        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-500">
          <Loader2 class="w-4 h-4 animate-spin" />
          Loading…
        </div>
      </div>
      <div v-else-if="error" class="flex-1 flex items-center justify-center px-6 text-center">
        <div>
          <p class="text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
          <button type="button" @click="refresh"
            class="mt-2 px-3 py-1 text-xs rounded-full border border-zinc-300 dark:border-zinc-700 hover:border-zinc-500 dark:hover:border-zinc-500">
            Try again
          </button>
        </div>
      </div>
      <div v-else-if="rows.length === 0" class="flex-1 flex items-center justify-center px-6 text-center">
        <div>
          <p class="text-sm text-zinc-700 dark:text-zinc-300">No history yet</p>
          <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">Saved changes will appear here.</p>
        </div>
      </div>

      <!-- List -->
      <div v-else class="flex-1 overflow-y-auto">
        <ul class="divide-y divide-zinc-100 dark:divide-zinc-900">
          <li v-for="g in groups" :key="g.key" class="px-3 py-2">
            <!-- Group header (or single row) -->
            <button v-if="g.rows.length > 1" type="button" @click="toggleGroup(g.key)"
              class="w-full text-left flex items-start gap-2 hover:bg-zinc-50 dark:hover:bg-zinc-900 -mx-3 px-3 py-1 rounded transition-colors">
              <span class="mt-0.5 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-[9px] font-mono font-bold text-zinc-600 dark:text-zinc-400">
                {{ g.rows.length }}
              </span>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-zinc-900 dark:text-zinc-100">
                  <span class="font-semibold">{{ g.causerName }}</span>&nbsp;<span class="text-zinc-500">saved {{ g.rows.length }} changes</span>
                </p>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-500" :title="g.createdAt">
                  {{ relTime(g.createdAt) }}
                </p>
              </div>
              <ChevronDown v-if="expandedGroups.has(g.key)" class="mt-1 h-3.5 w-3.5 text-zinc-400" :stroke-width="1.5" />
              <ChevronRight v-else class="mt-1 h-3.5 w-3.5 text-zinc-400" :stroke-width="1.5" />
            </button>

            <div v-else class="flex items-start gap-2">
              <component :is="verbIcon(firstRow(g).description)" class="mt-0.5 w-3.5 h-3.5 flex-shrink-0"
                :class="verbColor(firstRow(g).description)" :stroke-width="2" />
              <div class="flex-1 min-w-0">
                <p class="text-sm text-zinc-900 dark:text-zinc-100">
                  <span class="font-semibold">{{ g.causerName }}</span>&nbsp;<span :class="verbColor(firstRow(g).description)">{{ verb(firstRow(g).description) }}</span>&nbsp;<span class="text-zinc-500">{{ subjectKind(firstRow(g)).toLowerCase() }}</span>&nbsp;<button type="button" @click.stop="onRowClick(firstRow(g))"
                    class="underline decoration-zinc-300 hover:decoration-zinc-500 underline-offset-2">{{ subjectLabel(firstRow(g)) }}</button>
                </p>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-500" :title="firstRow(g).created_at">
                  {{ relTime(firstRow(g).created_at) }}
                </p>
                <ul v-if="fieldDiffs(firstRow(g)).length" class="mt-1 space-y-0.5">
                  <li v-for="f in fieldDiffs(firstRow(g))" :key="f.name"
                    class="text-[11px] font-mono text-zinc-600 dark:text-zinc-400">
                    <span class="text-zinc-500">{{ f.name }}:</span>
                    <span class="text-zinc-500 line-through">{{ formatValue(f.before) }}</span>
                    <span class="mx-1 text-zinc-400">→</span>
                    <span class="text-zinc-900 dark:text-zinc-100">{{ formatValue(f.after) }}</span>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Expanded group rows -->
            <ul v-if="g.rows.length > 1 && expandedGroups.has(g.key)"
              class="mt-2 ml-7 space-y-1.5 border-l border-zinc-200 dark:border-zinc-800 pl-3">
              <li v-for="r in g.rows" :key="r.id" class="flex items-start gap-1.5">
                <component :is="verbIcon(r.description)" class="mt-0.5 w-3 h-3 flex-shrink-0"
                  :class="verbColor(r.description)" :stroke-width="2" />
                <div class="flex-1 min-w-0">
                  <p class="text-[12px] text-zinc-900 dark:text-zinc-100">
                    <span :class="verbColor(r.description)">{{ verb(r.description) }}</span>
                    <span class="text-zinc-500"> {{ subjectKind(r).toLowerCase() }}</span>
                    <button type="button" @click.stop="onRowClick(r)"
                      class="ml-1 underline decoration-zinc-300 hover:decoration-zinc-500 underline-offset-2">{{ subjectLabel(r) }}</button>
                  </p>
                  <ul v-if="fieldDiffs(r).length" class="mt-0.5 space-y-0.5">
                    <li v-for="f in fieldDiffs(r)" :key="f.name"
                      class="text-[11px] font-mono text-zinc-600 dark:text-zinc-400">
                      <span class="text-zinc-500">{{ f.name }}:</span>
                      <span class="text-zinc-500 line-through">{{ formatValue(f.before) }}</span>
                      <span class="mx-1 text-zinc-400">→</span>
                      <span class="text-zinc-900 dark:text-zinc-100">{{ formatValue(f.after) }}</span>
                    </li>
                  </ul>
                </div>
              </li>
            </ul>
          </li>
        </ul>

        <!-- Load more -->
        <div v-if="meta && meta.current_page < meta.last_page" class="p-3 text-center">
          <button type="button" @click="loadMore" :disabled="loading"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-zinc-300 dark:border-zinc-700 text-xs font-mono tracking-wide text-zinc-700 dark:text-zinc-300 hover:border-zinc-500 dark:hover:border-zinc-500 transition-colors disabled:opacity-50">
            <Loader2 v-if="loading" class="w-3.5 h-3.5 animate-spin" />
            Load more
          </button>
        </div>
      </div>
    </aside>
  </Transition>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: transform 200ms ease, opacity 200ms ease;
}

.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
  opacity: 0;
}
</style>
