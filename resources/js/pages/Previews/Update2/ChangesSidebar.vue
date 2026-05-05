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
  return 'text-[var(--p2-text-muted)]'
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
      class="fixed top-0 right-0 z-40 flex h-full w-[24rem] flex-col border-l shadow-2xl"
      :style="{ borderColor: 'var(--p2-hairline)', background: 'var(--p2-surface)' }">
      <!-- Header -->
      <div class="flex items-center justify-between gap-2 border-b px-3 py-3"
        :style="{ borderColor: 'var(--p2-hairline)' }">
        <div class="flex items-center gap-2 min-w-0">
          <div>
            <p class="p2-label">Audit Log</p>
            <h2 class="mt-0.5 text-sm font-semibold tracking-tight text-[var(--p2-text)]">History</h2>
          </div>
          <span v-if="meta"
            class="p2-mono inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] tabular-nums text-[var(--p2-text-muted)]"
            :style="{ background: 'var(--p2-accent-soft)' }">
            {{ meta.total }}
          </span>
        </div>
        <div class="flex items-center gap-1">
          <button type="button" @click="refresh" :disabled="loading"
            class="grid h-8 w-8 place-items-center rounded-full text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)] disabled:opacity-50"
            title="Refresh">
            <RefreshCw class="w-4 h-4" :class="loading ? 'animate-spin' : ''" :stroke-width="1.5" />
          </button>
          <button type="button" @click="emit('close')"
            class="grid h-8 w-8 place-items-center rounded-full text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-text)]"
            aria-label="Close">
            <X class="w-4 h-4" :stroke-width="1.5" />
          </button>
        </div>
      </div>

      <!-- States -->
      <div v-if="loading && rows.length === 0" class="flex-1 flex items-center justify-center px-6 text-center">
        <div class="flex items-center gap-2 text-sm text-[var(--p2-text-muted)]">
          <Loader2 class="w-4 h-4 animate-spin" :style="{ color: 'var(--p2-accent)' }" />
          Loading…
        </div>
      </div>
      <div v-else-if="error" class="flex-1 flex items-center justify-center px-6 text-center">
        <div>
          <p class="text-sm text-rose-500">{{ error }}</p>
          <button type="button" @click="refresh"
            class="mt-2 inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-xs font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)]"
            :style="{ borderColor: 'var(--p2-border)' }">
            Try again
          </button>
        </div>
      </div>
      <div v-else-if="rows.length === 0" class="flex-1 flex items-center justify-center px-6 text-center">
        <div>
          <p class="text-sm font-medium text-[var(--p2-text)]">No history yet</p>
          <p class="mt-1 text-xs text-[var(--p2-text-muted)]">Saved changes will appear here.</p>
        </div>
      </div>

      <!-- List -->
      <div v-else class="flex-1 overflow-y-auto">
        <ul class="divide-y" :style="{ borderColor: 'var(--p2-hairline)' }">
          <li v-for="g in groups" :key="g.key" class="px-3 py-2 [&+&]:border-t" :style="{ borderColor: 'var(--p2-hairline)' }">
            <!-- Group header (or single row) -->
            <button v-if="g.rows.length > 1" type="button" @click="toggleGroup(g.key)"
              class="-mx-3 flex w-full items-start gap-2 rounded px-3 py-1 text-left transition-colors duration-200 ease-[var(--p2-ease-expo)] hover:bg-[var(--p2-accent-soft)]">
              <span
                class="p2-mono mt-0.5 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full text-[9px] font-bold text-[var(--p2-text-muted)]"
                :style="{ background: 'var(--p2-accent-soft)' }"
              >
                {{ g.rows.length }}
              </span>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-[var(--p2-text)]">
                  <span class="font-semibold">{{ g.causerName }}</span>&nbsp;<span class="text-[var(--p2-text-muted)]">saved {{ g.rows.length }} changes</span>
                </p>
                <p class="text-[11px] text-[var(--p2-text-subtle)]" :title="g.createdAt">
                  {{ relTime(g.createdAt) }}
                </p>
              </div>
              <ChevronDown v-if="expandedGroups.has(g.key)" class="mt-1 h-3.5 w-3.5 text-[var(--p2-text-subtle)]" :stroke-width="1.5" />
              <ChevronRight v-else class="mt-1 h-3.5 w-3.5 text-[var(--p2-text-subtle)]" :stroke-width="1.5" />
            </button>

            <div v-else class="flex items-start gap-2">
              <component :is="verbIcon(firstRow(g).description)" class="mt-0.5 w-3.5 h-3.5 flex-shrink-0"
                :class="verbColor(firstRow(g).description)" :stroke-width="2" />
              <div class="flex-1 min-w-0">
                <p class="text-sm text-[var(--p2-text)]">
                  <span class="font-semibold">{{ g.causerName }}</span>&nbsp;<span :class="verbColor(firstRow(g).description)">{{ verb(firstRow(g).description) }}</span>&nbsp;<span class="text-[var(--p2-text-muted)]">{{ subjectKind(firstRow(g)).toLowerCase() }}</span>&nbsp;<button type="button" @click.stop="onRowClick(firstRow(g))"
                    class="underline underline-offset-2 transition-colors duration-200 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)]">{{ subjectLabel(firstRow(g)) }}</button>
                </p>
                <p class="text-[11px] text-[var(--p2-text-subtle)]" :title="firstRow(g).created_at">
                  {{ relTime(firstRow(g).created_at) }}
                </p>
                <ul v-if="fieldDiffs(firstRow(g)).length" class="mt-1 space-y-0.5">
                  <li v-for="f in fieldDiffs(firstRow(g))" :key="f.name"
                    class="p2-mono text-[11px] text-[var(--p2-text-muted)]">
                    <span class="text-[var(--p2-text-subtle)]">{{ f.name }}:</span>
                    <span class="text-[var(--p2-text-subtle)] line-through">{{ formatValue(f.before) }}</span>
                    <span class="mx-1 text-[var(--p2-text-subtle)]">→</span>
                    <span class="text-[var(--p2-text)]">{{ formatValue(f.after) }}</span>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Expanded group rows -->
            <ul v-if="g.rows.length > 1 && expandedGroups.has(g.key)"
              class="mt-2 ml-7 space-y-1.5 border-l pl-3" :style="{ borderColor: 'var(--p2-border)' }">
              <li v-for="r in g.rows" :key="r.id" class="flex items-start gap-1.5">
                <component :is="verbIcon(r.description)" class="mt-0.5 w-3 h-3 flex-shrink-0"
                  :class="verbColor(r.description)" :stroke-width="2" />
                <div class="flex-1 min-w-0">
                  <p class="text-[12px] text-[var(--p2-text)]">
                    <span :class="verbColor(r.description)">{{ verb(r.description) }}</span>
                    <span class="text-[var(--p2-text-muted)]"> {{ subjectKind(r).toLowerCase() }}</span>
                    <button type="button" @click.stop="onRowClick(r)"
                      class="ml-1 underline underline-offset-2 transition-colors duration-200 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)]">{{ subjectLabel(r) }}</button>
                  </p>
                  <ul v-if="fieldDiffs(r).length" class="mt-0.5 space-y-0.5">
                    <li v-for="f in fieldDiffs(r)" :key="f.name"
                      class="p2-mono text-[11px] text-[var(--p2-text-muted)]">
                      <span class="text-[var(--p2-text-subtle)]">{{ f.name }}:</span>
                      <span class="text-[var(--p2-text-subtle)] line-through">{{ formatValue(f.before) }}</span>
                      <span class="mx-1 text-[var(--p2-text-subtle)]">→</span>
                      <span class="text-[var(--p2-text)]">{{ formatValue(f.after) }}</span>
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
            class="p2-mono inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-xs font-medium tracking-wide text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)] disabled:opacity-50"
            :style="{ borderColor: 'var(--p2-border)' }">
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
