<script setup lang="ts">
import { computed, inject, ref } from 'vue'
import { ChevronRight, MousePointer2 } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'

import CategoryEditor from './editors/CategoryEditor.vue'
import FeedbackEditor from './editors/FeedbackEditor.vue'
import SetEditor from './editors/SetEditor.vue'
import VersionEditor from './editors/VersionEditor.vue'
import AssetEditor from './editors/AssetEditor.vue'
import ConfirmDelete from './modals/ConfirmDelete.vue'

import type { PreviewTree } from './usePreviewTree'
import { isDbId } from './usePreviewTree'

const tree = inject<PreviewTree>('tree')!

const sel = computed(() => tree.selection.value)
const path = computed(() => sel.value ? tree.findPath(sel.value) : {})

const breadcrumb = computed(() => {
  const p: any = path.value
  const items: { label: string; kind?: string; id?: any }[] = []
  if (p.category) items.push({ label: p.category.name || '(untitled)', kind: 'category', id: p.category.id })
  if (p.feedback) items.push({ label: p.feedback.name || '(untitled)', kind: 'feedback', id: p.feedback.id })
  if (p.set) items.push({ label: p.set.name || 'Version', kind: 'set', id: p.set.id })
  if (p.version) items.push({ label: p.version.name || 'Set', kind: 'version', id: p.version.id })
  if (p.asset) items.push({ label: p.asset.name || `Asset #${p.asset.id}`, kind: 'asset' })
  return items
})

const onCrumbClick = (item: { kind?: string; id?: any }, type?: string) => {
  if (!item.kind || item.kind === 'asset') return
  const newSel: any = { kind: item.kind, id: item.id }
  if (type) newSel.assetType = type
  tree.select(newSel)
}

// ----- Delete handling --------------------------------------------------
const showConfirm = ref(false)
const isDeleting = ref(false)
const confirmText = ref('Are you sure?')

const requestDelete = () => {
  if (!sel.value) return
  const p: any = path.value
  let label = ''
  switch (sel.value.kind) {
    case 'category': label = `project "${p.category.name}"`; break
    case 'feedback': label = `revision round "${p.feedback.name}"`; break
    case 'set':      label = `version "${p.set.name || '(untitled)'}"`; break
    case 'version':  label = `set "${p.version.name || '(untitled)'}"`; break
    case 'asset':    label = `${sel.value.assetType} asset`; break
  }
  confirmText.value = `Delete this ${label}? This cannot be undone.`
  showConfirm.value = true
}

const performDelete = async () => {
  if (!sel.value) return
  isDeleting.value = true
  const s = sel.value

  // Unsaved → just remove locally
  if (!isDbId(s.id)) {
    tree.removeNode(s)
    isDeleting.value = false
    showConfirm.value = false
    return
  }

  // Saved → call the server endpoint
  const buildUrl = (): string => {
    if (s.kind === 'asset') return `/previews/${s.assetType}/delete/${s.id}`
    if (s.kind === 'category') return `/previews/category/delete/${s.id}`
    if (s.kind === 'feedback') return `/previews/feedback/delete/${s.id}`
    if (s.kind === 'set')      return `/previews/feedbackSet/delete/${s.id}`
    if (s.kind === 'version')  return `/previews/version/delete/${s.id}`
    return ''
  }
  const url = buildUrl()
  if (!url) {
    isDeleting.value = false
    return
  }

  router.delete(url, {
    preserveScroll: true,
    onSuccess: () => {
      tree.removeNode(s)
      showConfirm.value = false
    },
    onError: (errs) => {
      console.error('Delete failed', errs)
    },
    onFinish: () => {
      isDeleting.value = false
    },
  })
}
</script>

<template>
  <main class="min-w-0 flex-1 overflow-y-auto">
    <div v-if="!sel" class="grid h-full place-items-center px-6 text-center">
      <div class="max-w-sm">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800/60 dark:text-zinc-500">
          <MousePointer2 class="h-6 w-6" />
        </div>
        <h2 class="mt-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">Nothing selected</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
          Pick a category, revision, version, set, or asset on the left to start editing.
        </p>
      </div>
    </div>

    <div v-else class="mx-auto flex min-h-full max-w-3xl flex-col px-6 py-5">
      <!-- Breadcrumb -->
      <nav class="mb-4 flex flex-wrap items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
        <template v-for="(c, idx) in breadcrumb" :key="idx">
          <ChevronRight v-if="idx > 0" class="h-3 w-3 shrink-0 text-zinc-300 dark:text-zinc-600" />
          <button
            type="button"
            :disabled="c.kind === 'asset'"
            class="truncate transition hover:text-zinc-900 disabled:cursor-default disabled:hover:text-current dark:hover:text-zinc-200"
            :class="idx === breadcrumb.length - 1 ? 'font-semibold text-zinc-800 dark:text-zinc-200' : ''"
            @click="onCrumbClick(c)"
          >{{ c.label }}</button>
        </template>
      </nav>

      <!-- Editor router -->
      <div class="flex-1">
        <CategoryEditor v-if="sel.kind === 'category' && path.category" :category="path.category" @delete="requestDelete" />
        <FeedbackEditor v-else-if="sel.kind === 'feedback' && path.feedback" :feedback="path.feedback" :category="path.category" @delete="requestDelete" />
        <SetEditor v-else-if="sel.kind === 'set' && path.set" :set="path.set" @delete="requestDelete" />
        <VersionEditor v-else-if="sel.kind === 'version' && path.version" :version="path.version" :category="path.category" @delete="requestDelete" />
        <AssetEditor v-else-if="sel.kind === 'asset' && path.asset" :asset="path.asset" :asset-type="sel.assetType" :category="path.category" @delete="requestDelete" />
      </div>
    </div>

    <ConfirmDelete
      v-model:open="showConfirm"
      :message="confirmText"
      :is-loading="isDeleting"
      @confirm="performDelete"
    />
  </main>
</template>
