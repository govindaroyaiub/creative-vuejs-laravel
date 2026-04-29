<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, provide, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Swal from 'sweetalert2'

import TopBar from './Update2/TopBar.vue'
import Tree from './Update2/Tree.vue'
import Inspector from './Update2/Inspector.vue'
import NewCategoryModal from './Update2/modals/NewCategoryModal.vue'
import { createPreviewTree, isDbId, type AssetType } from './Update2/usePreviewTree'

const props = defineProps<{
  preview: any
  preview_id: number
  preview_name: string
  preview_slug: string
  client_name: string
  bannerSizes: any[]
  videoSizes: any[]
}>()

const tree = createPreviewTree(props.preview)

provide('tree', tree)
provide('bannerSizes', props.bannerSizes)
provide('videoSizes', props.videoSizes)

const showSidebar = ref(true)

// ----- Save All ----------------------------------------------------------
const saveStartedAt = ref(0)
const elapsedSeconds = ref(0)
let elapsedTimer: number | null = null

/**
 * One UUID per save attempt. Sent as `idempotency_key` so the backend can
 * deduplicate if the same request arrives twice (browser retry, double
 * submit through some other path, etc.). Reset to null after a successful
 * or failed response so the next save gets a fresh key.
 */
const pendingIdempotencyKey = ref<string | null>(null)

const generateKey = () => {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
    return (crypto as Crypto).randomUUID()
  }
  // RFC4122-ish fallback
  return 'k-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10)
}

const saveAll = () => {
  // Hard re-entry guard. The button is disabled, but a stray programmatic
  // call (Cmd+S handler, retry, etc.) could land here while a save is
  // already in flight. Drop it.
  if (tree.isSaving.value) return

  const fd = new FormData()
  const p = tree.preview
  fd.append('preview_id', String(p.id))

  pendingIdempotencyKey.value ||= generateKey()
  fd.append('idempotency_key', pendingIdempotencyKey.value)

  p.categories.forEach((c: any, ci: number) => {
    if (isDbId(c.id)) fd.append(`categories[${ci}][id]`, String(c.id))
    fd.append(`categories[${ci}][name]`, c.name ?? '')
    fd.append(`categories[${ci}][type]`, c.type ?? 'banner')

    c.feedbacks.forEach((f: any, fi: number) => {
      const fp = `categories[${ci}][feedbacks][${fi}]`
      if (isDbId(f.id)) fd.append(`${fp}[id]`, String(f.id))
      fd.append(`${fp}[name]`, f.name ?? '')
      fd.append(`${fp}[description]`, f.description ?? '')

      f.feedback_sets.forEach((s: any, si: number) => {
        const sp = `${fp}[feedback_sets][${si}]`
        if (isDbId(s.id)) fd.append(`${sp}[id]`, String(s.id))
        fd.append(`${sp}[name]`, s.name ?? '')

        s.versions.forEach((v: any, vi: number) => {
          const vp = `${sp}[versions][${vi}]`
          if (isDbId(v.id)) fd.append(`${vp}[id]`, String(v.id))
          fd.append(`${vp}[name]`, v.name ?? '')

          if (c.type === 'banner') {
            v.banners.forEach((a: any, ai: number) => appendBanner(fd, `${vp}[banners][${ai}]`, a))
          } else if (c.type === 'video') {
            v.videos.forEach((a: any, ai: number) => appendVideo(fd, `${vp}[videos][${ai}]`, a))
          } else if (c.type === 'social') {
            v.socials.forEach((a: any, ai: number) => appendSocial(fd, `${vp}[socials][${ai}]`, a))
          } else if (c.type === 'gif') {
            v.gifs.forEach((a: any, ai: number) => appendGif(fd, `${vp}[gifs][${ai}]`, a))
          }
        })
      })
    })
  })

  tree.isSaving.value = true
  saveStartedAt.value = Date.now()
  elapsedSeconds.value = 0
  if (elapsedTimer) window.clearInterval(elapsedTimer)
  elapsedTimer = window.setInterval(() => {
    elapsedSeconds.value = Math.floor((Date.now() - saveStartedAt.value) / 1000)
  }, 1000)

  router.post(`/previews/${p.id}/bulk-edit`, fd, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      tree.clearDirty()
      pendingIdempotencyKey.value = null
      Swal.fire({
        icon: 'success',
        title: 'Saved',
        timer: 1200,
        showConfirmButton: false,
        toast: true,
        position: 'top-end',
        timerProgressBar: true,
      })
    },
    onError: (errs: any) => {
      // Validation/business errors: drop the key so the next attempt is
      // a fresh request. Network errors don't reach here — Inertia would
      // surface them through the global error handler — and would re-use
      // the same key on retry, which is exactly what we want.
      pendingIdempotencyKey.value = null
      console.error('Save failed', errs)
      Swal.fire({
        icon: 'error',
        title: 'Validation error',
        html: `<pre style="text-align:left">${JSON.stringify(errs, null, 2)}</pre>`,
        width: 600,
      })
    },
    onFinish: () => {
      tree.isSaving.value = false
      if (elapsedTimer) {
        window.clearInterval(elapsedTimer)
        elapsedTimer = null
      }
    },
  })
}

function appendBanner(fd: FormData, prefix: string, a: any) {
  if (isDbId(a.id)) fd.append(`${prefix}[id]`, String(a.id))
  fd.append(`${prefix}[name]`, a.name ?? '')
  fd.append(`${prefix}[size_id]`, String(a.size_id ?? ''))
  fd.append(`${prefix}[position]`, String(a.position ?? 0))
  if (a.file instanceof File) fd.append(`${prefix}[file]`, a.file)
}
function appendVideo(fd: FormData, prefix: string, a: any) {
  if (isDbId(a.id)) fd.append(`${prefix}[id]`, String(a.id))
  fd.append(`${prefix}[codec]`, a.codec ?? '')
  fd.append(`${prefix}[aspect_ratio]`, a.aspect_ratio ?? '')
  fd.append(`${prefix}[fps]`, String(a.fps ?? ''))
  fd.append(`${prefix}[size_id]`, String(a.size_id ?? ''))
  fd.append(`${prefix}[position]`, String(a.position ?? 0))
  if (a.file instanceof File) fd.append(`${prefix}[file]`, a.file)
  if (a.companion_banner instanceof File) fd.append(`${prefix}[companion_banner]`, a.companion_banner)
}
function appendSocial(fd: FormData, prefix: string, a: any) {
  if (isDbId(a.id)) fd.append(`${prefix}[id]`, String(a.id))
  fd.append(`${prefix}[name]`, a.name ?? '')
  fd.append(`${prefix}[position]`, String(a.position ?? 0))
  if (a.file instanceof File) fd.append(`${prefix}[file]`, a.file)
}
function appendGif(fd: FormData, prefix: string, a: any) {
  if (isDbId(a.id)) fd.append(`${prefix}[id]`, String(a.id))
  fd.append(`${prefix}[name]`, a.name ?? '')
  fd.append(`${prefix}[size_id]`, String(a.size_id ?? ''))
  fd.append(`${prefix}[position]`, String(a.position ?? 0))
  if (a.file instanceof File) fd.append(`${prefix}[file]`, a.file)
}

// ----- Add category modal ------------------------------------------------
const showNewCategory = ref(false)
const onAddCategory = () => {
  showNewCategory.value = true
}
const onCreateCategory = (value: { name: string; type: AssetType }) => {
  tree.addCategory(value.name, value.type)
}

const goToPreview = () => {
  window.open(`/previews/show/${props.preview_slug || tree.preview.slug}`, '_blank')
}

const goToPreview2 = () => {
  window.open(`/previews/show2/${props.preview_slug || tree.preview.slug}`, '_blank')
}

const goBack = () => {
  router.visit('/previews')
}

const totalBannersCount = computed(() => 0) // unused; placeholder
void totalBannersCount

// Warn before leaving if saving or has unsaved changes.
const onBeforeUnload = (e: BeforeUnloadEvent) => {
  if (tree.isSaving.value) {
    e.preventDefault()
    e.returnValue = 'A save is still in progress. Closing this tab may lose your changes.'
    return e.returnValue
  }
  if (tree.dirtyCount.value > 0 || tree.hasUnsavedNew.value > 0) {
    e.preventDefault()
    e.returnValue = 'You have unsaved changes. Are you sure you want to leave?'
    return e.returnValue
  }
}

onMounted(() => window.addEventListener('beforeunload', onBeforeUnload))
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', onBeforeUnload)
  if (elapsedTimer) window.clearInterval(elapsedTimer)
})
</script>

<template>
  <Head :title="`Edit · ${preview_name}`" />

  <AppLayout>
    <div class="flex h-[calc(100vh-4rem)] flex-col bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
      <TopBar
        :preview-name="preview_name"
        :client-name="client_name"
        :preview-slug="preview_slug || tree.preview.slug"
        :dirty-count="tree.dirtyCount.value + tree.hasUnsavedNew.value"
        :is-saving="tree.isSaving.value"
        :elapsed-seconds="elapsedSeconds"
        :sidebar-open="showSidebar"
        @save="saveAll"
        @preview="goToPreview"
        @preview2="goToPreview2"
        @back="goBack"
        @toggle-sidebar="showSidebar = !showSidebar"
      />

      <div class="flex min-h-0 flex-1">
        <Tree
          v-show="showSidebar"
          @add-category="onAddCategory"
        />

        <Inspector class="flex-1" />
      </div>

      <NewCategoryModal v-model:open="showNewCategory" @create="onCreateCategory" />
    </div>
  </AppLayout>
</template>
