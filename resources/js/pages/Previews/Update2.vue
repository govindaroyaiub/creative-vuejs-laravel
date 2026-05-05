<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, provide, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
// import AppLayout from '@/layouts/AppLayout.vue'
import Swal from 'sweetalert2'

import TopBar from './Update2/TopBar.vue'
import Tree from './Update2/Tree.vue'
import Inspector from './Update2/Inspector.vue'
import NewCategoryModal from './Update2/modals/NewCategoryModal.vue'
import ChangesSidebar from './Update2/ChangesSidebar.vue'
import { createPreviewTree, isDbId, type AssetType } from './Update2/usePreviewTree'

type Palette = {
  id: number
  name: string
  primary: string
  secondary: string
  tertiary: string
  quaternary: string
  quinary: string
  senary: string
  septenary: string
}

const props = defineProps<{
  preview: any
  preview_id: number
  preview_name: string
  preview_slug: string
  client_name: string
  bannerSizes: any[]
  videoSizes: any[]
  palette?: Palette | null
}>()

// Mirror the preview's color palette into the editor's --p2-accent
// token system so the Save button, "+ New X" CTAs, selected tree
// nodes, etc. all match the live preview's hue. Falls back to the
// Planet Nine launch-blue gradient when no palette is set.
const DEFAULT_PALETTE_ACCENT = '#3B82F6'
const DEFAULT_PALETTE_ACCENT_2 = '#6366F1'
const DEFAULT_PALETTE_ACCENT_3 = '#1D4ED8'

const accent = computed(() => props.palette?.primary || DEFAULT_PALETTE_ACCENT)
// Tertiary in this color system tends to be a deeper / accent-paired
// hue — same role as Show2's `--p2-accent-2`. Fall back to the second
// gradient stop if the palette doesn't supply one.
const accent2 = computed(() => props.palette?.tertiary || DEFAULT_PALETTE_ACCENT_2)
const accent3 = computed(() => props.palette?.senary || DEFAULT_PALETTE_ACCENT_3)
const accentSoft  = computed(() => `${accent.value}1a`)   // ~10% alpha
const accentMuted = computed(() => `${accent.value}38`)   // ~22% alpha
const accentGlow  = computed(() => `${accent.value}66`)   // ~40% alpha
const accent2Soft = computed(() => `${accent2.value}14`)  //  ~8% alpha
const accent2Glow = computed(() => `${accent2.value}59`)  // ~35% alpha

const themeStyle = computed(() => ({
  '--p2-accent':        accent.value,
  '--p2-accent-2':      accent2.value,
  '--p2-accent-3':      accent3.value,
  '--p2-accent-soft':   accentSoft.value,
  '--p2-accent-muted':  accentMuted.value,
  '--p2-accent-glow':   accentGlow.value,
  '--p2-accent-2-soft': accent2Soft.value,
  '--p2-accent-2-glow': accent2Glow.value,
} as any))

const tree = createPreviewTree(props.preview)

provide('tree', tree)
provide('bannerSizes', props.bannerSizes)
provide('videoSizes', props.videoSizes)

// Pick the active category (or first one as fallback). Used both on initial
// mount and after Save All — the server flips is_active on create, so after
// a save we want to land on whatever it just marked active.
const selectActiveCategory = () => {
  const cat =
    tree.preview.categories?.find((c: any) => c.is_active) ||
    tree.preview.categories?.[0]
  if (cat) {
    tree.select({ kind: 'category', id: cat.id })
    tree.expandPathTo({ kind: 'category', id: cat.id })
  }
}
selectActiveCategory()

const showSidebar = ref(true)
const showChanges = ref(false)

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
    preserveState: true,
    onSuccess: (page: any) => {
      // Swap local temp IDs for the server's real DB IDs so `hasUnsavedNew`
      // drops to 0. `clearDirty()` alone leaves new records counted as
      // unsaved and the TopBar badge sticks.
      const fresh = page?.props?.preview
      if (fresh) tree.rehydrate(fresh)
      else tree.clearDirty()
      // If rehydrate dropped the selection (its target no longer exists, e.g.
      // a temp-id category that just got a real DB id), land back on the
      // active category. Server marks newly created categories is_active=true.
      if (!tree.selection.value) selectActiveCategory()
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
        // Use `text:` (not `html:`) so the JSON-stringified error
        // payload — which echoes back the user's own input — can't be
        // interpreted as HTML and turn into a self-XSS the moment the
        // user pastes anything containing `<script>` into a field.
        text: JSON.stringify(errs, null, 2),
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

const goToEditInfo = () => {
  router.visit(`/previews-edit/${props.preview_id || tree.preview.id}`)
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
    <div class="update2-root relative flex h-[calc(100vh-4rem)] flex-col" :style="themeStyle">
      <!-- Decorative ambient color wash. Light mode is asset-first
           (very subtle); dark mode opens up into a cinematic Planet
           Nine backdrop with a starfield + aurora glow — same identity
           as the public Show2 viewer. -->
      <div aria-hidden="true" class="update2-ambient" />
      <div aria-hidden="true" class="update2-stars" />

      <TopBar
        :preview-name="preview_name"
        :client-name="client_name"
        :preview-slug="preview_slug || tree.preview.slug"
        :dirty-count="tree.unsavedCount.value"
        :is-saving="tree.isSaving.value"
        :elapsed-seconds="elapsedSeconds"
        :sidebar-open="showSidebar"
        @save="saveAll"
        @preview="goToPreview"
        @preview2="goToPreview2"
        @edit-info="goToEditInfo"
        @back="goBack"
        @toggle-sidebar="showSidebar = !showSidebar"
        @view-changes="showChanges = true"
      />

      <ChangesSidebar :open="showChanges" @close="showChanges = false" />

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

<style>
/* Planet Nine — calm editor variant. Same token system as the public
   Show2 viewer, but with a fixed launch-blue accent and no starfield /
   aurora bloom. Editors are work surfaces; the cinematic chrome is
   reserved for the public-facing pages. */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

.update2-root {
  /* --p2-accent / --p2-accent-2 / --p2-accent-soft / --p2-accent-muted /
     --p2-accent-glow / --p2-accent-2-soft / --p2-accent-2-glow are set
     dynamically via `:style="themeStyle"` from the loaded color palette.
     This block only owns the surface + motion tokens, which never vary
     per preview. */

  --p2-bg:            #fafafa;
  --p2-surface:       #ffffff;
  --p2-surface-muted: rgba(255, 255, 255, 0.85);
  --p2-text:          #18181b;
  --p2-text-muted:    #71717a;
  --p2-text-subtle:   #a1a1aa;
  --p2-border:        rgba(15, 15, 20, 0.08);
  --p2-border-strong: rgba(15, 15, 20, 0.16);
  --p2-hairline:      rgba(15, 15, 20, 0.06);

  --p2-ease-expo:   cubic-bezier(0.16, 1, 0.3, 1);
  --p2-ease-cinema: cubic-bezier(0.22, 1, 0.36, 1);

  background-color: var(--p2-bg);
  color: var(--p2-text);
  font-family: 'Inter', 'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif;
  font-feature-settings: 'cv11', 'ss01', 'tnum';
}

.dark .update2-root {
  --p2-bg:            #0B0B10;
  --p2-surface:       #1E1E23;
  --p2-surface-muted: rgba(30, 30, 35, 0.45);
  --p2-text:          #F8FAFC;
  --p2-text-muted:    #94A3B8;
  --p2-text-subtle:   #71717a;
  --p2-border:        rgba(255, 255, 255, 0.10);
  --p2-border-strong: rgba(255, 255, 255, 0.22);
  --p2-hairline:      rgba(255, 255, 255, 0.06);
}

/* Reusable Planet Nine primitives, scoped to the editor root. */
.update2-root .p2-label {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: var(--p2-text-muted);
}
.update2-root .p2-mono {
  font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;
}
.update2-root .p2-glass {
  background: var(--p2-surface-muted);
  border: 1px solid var(--p2-border);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}
.update2-root .p2-hairline { border-color: var(--p2-hairline); }

/* Focus rings — accent-colored, 2px ring on every interactive surface. */
.update2-root :focus-visible {
  outline: none;
  box-shadow: 0 0 0 2px var(--p2-bg), 0 0 0 4px var(--p2-accent);
  border-radius: inherit;
}

/* ---------- Ambient backdrop (mirrors Show2) ---------- */

/* Light-mode ambient: kept extremely soft so it doesn't compete with
   the editor surface. */
.update2-ambient {
  pointer-events: none;
  position: absolute;
  inset: 0;
  z-index: 0;
  background:
    radial-gradient(55% 45% at 0% 0%, var(--p2-accent-soft) 0%, transparent 70%),
    radial-gradient(40% 40% at 100% 0%, var(--p2-accent-2-soft) 0%, transparent 75%);
  opacity: 0.5;
}

/* Dark-mode ambient: full Planet Nine aurora bloom. */
.dark .update2-ambient {
  background:
    radial-gradient(70% 55% at 5% 0%, var(--p2-accent-glow) 0%, transparent 65%),
    radial-gradient(60% 55% at 100% 25%, var(--p2-accent-2-glow) 0%, transparent 70%),
    radial-gradient(60% 60% at 100% 100%, var(--p2-accent-glow) 0%, transparent 75%);
  opacity: 0.55;
}

/* Three-depth starfield. Hidden in light mode (would distract from
   the editor); visible in dark, with subtle parallax twinkle. */
.update2-stars {
  pointer-events: none;
  position: absolute;
  inset: 0;
  z-index: 0;
  opacity: 0;
  transition: opacity 600ms var(--p2-ease-cinema);
  background-image:
    radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.85), transparent 50%),
    radial-gradient(1px 1px at 60% 70%, rgba(255,255,255,0.7),  transparent 50%),
    radial-gradient(1.5px 1.5px at 80% 20%, rgba(255,255,255,0.6), transparent 50%),
    radial-gradient(1px 1px at 35% 85%, rgba(255,255,255,0.5), transparent 50%),
    radial-gradient(1px 1px at 90% 50%, rgba(255,255,255,0.65), transparent 50%);
  background-size: 1200px 800px;
}
.dark .update2-stars { opacity: 0.55; animation: u2-twinkle 6s ease-in-out infinite; }
@keyframes u2-twinkle {
  0%, 100% { opacity: 0.4; }
  50%      { opacity: 0.65; }
}

/* All editor children sit above the ambient + stars layers. */
.update2-root > :not(.update2-ambient):not(.update2-stars) {
  position: relative;
  z-index: 1;
}

@media (prefers-reduced-motion: reduce) {
  .update2-root *,
  .update2-root *::before,
  .update2-root *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
  .update2-stars { animation: none; }
}
</style>
