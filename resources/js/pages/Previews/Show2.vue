<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, provide, readonly } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'

import PreviewTopBar from './Show2/PreviewTopBar.vue'
import { useMediaQuery } from '@vueuse/core'
import ProjectSidebar from './Show2/ProjectSidebar.vue'
import RoundTabs from './Show2/RoundTabs.vue'
import AssetCanvas from './Show2/AssetCanvas.vue'
import NotesSheet from './Show2/NotesSheet.vue'
import FileTransferDock from './Show2/FileTransferDock.vue'
import PaletteSwitcher from './Show2/PaletteSwitcher.vue'
import IntroOverlay from './Show2/IntroOverlay.vue'
import IntroAssistant from './Show2/IntroAssistant.vue'

// Responsive scaling rules for BannerCard / GifCard at narrow viewports.
// The cards bind `banner-area-{W}-{H}` on their outer wrapper and the
// stylesheet shrinks + transform-scales matching widths. See file header.
import '../../../css/preview-banner-responsive.css'

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
  client: any
  headerLogo: any
  palette: Palette | null
  allColors: Palette[]
  authUserClientName: string
  previewId: number | string
  isAuthenticated: boolean
}>()

const DEFAULT_PALETTE = {
  primary:    '#6366f1',
  secondary:  '#fafafa',
  tertiary:   '#0f172a',
  quaternary: '#e5e7eb',
  quinary:    '#a1a1aa',
  senary:     '#525252',
  septenary:  '#1f2937',
}

// Mirror the prop into a local ref so theme changes apply instantly without
// a page reload — `onThemeChange` updates this and the CSS vars recompute.
const currentPalette = ref<Palette | null>(props.palette)
const accent = computed(() => currentPalette.value?.primary || DEFAULT_PALETTE.primary)
const accent2 = computed(() => currentPalette.value?.tertiary || DEFAULT_PALETTE.tertiary)
const accent3 = computed(() => currentPalette.value?.senary || DEFAULT_PALETTE.senary)
const accentSoft = computed(() => `${accent.value}1a`)   // ~10% alpha (light)
const accentMuted = computed(() => `${accent.value}33`)  // ~20% alpha
const accent2Soft = computed(() => `${accent2.value}14`) // ~8%  alpha (light)
const accentGlow = computed(() => `${accent.value}66`)   // ~40% alpha (dark)
const accent2Glow = computed(() => `${accent2.value}59`) // ~35% alpha (dark)

provide('accent', accent)
provide('isPlanetNine', props.authUserClientName === 'Planet Nine')

// --- Show2-scoped dark mode ---------------------------------------------
// Persists in its own cookie so toggling on the public preview page does
// not change the logged-in admin's app-wide appearance.
const SHOW2_THEME_COOKIE = 'show2_appearance'
const readCookie = (name: string): string | null => {
  if (typeof document === 'undefined') return null
  const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&') + '=([^;]*)'))
  return m && m[1] !== undefined ? decodeURIComponent(m[1]) : null
}
const writeCookie = (name: string, value: string, days = 365) => {
  document.cookie = `${name}=${value};path=/;max-age=${days * 24 * 60 * 60};SameSite=Lax`
}

const initialIsDark = readCookie(SHOW2_THEME_COOKIE) === 'dark'
let priorRootDark = false
if (typeof document !== 'undefined') {
  priorRootDark = document.documentElement.classList.contains('dark')
  document.documentElement.classList.toggle('dark', initialIsDark)
}
const isDark = ref(initialIsDark)
const toggleDark = () => {
  const next = !isDark.value
  isDark.value = next
  document.documentElement.classList.toggle('dark', next)
  writeCookie(SHOW2_THEME_COOKIE, next ? 'dark' : 'light')
}

provide('show2Theme', { isDark: readonly(isDark), toggleDark })

const currentYear = new Date().getFullYear()

const categories = ref<any[]>([])
const feedbacks = ref<any[]>([])
const feedbackSets = ref<any[]>([])
const activeCategory = ref<any>(null)
const activeFeedback = ref<any>(null)
const fileTransfer = ref<any>(null)
const viewers = ref<string[]>([])
const isInitialLoading = ref(true)
const isAssetsLoading = ref(false)
const isNotesOpen = ref(false)
const isPaletteOpen = ref(false)
/**
 * On a short screen the always-visible sidebar column gives the page a second
 * scroll region — the category list scrolls independently of the content, which
 * is disorienting and hides the selected project below its own fold. Above a
 * certain height there is room for the whole list and the column is fine.
 *
 * Gated on category count too: the second scrollbar only appears when the list
 * is long enough to overflow, so a preview with a handful of projects keeps its
 * column even on a laptop.
 */
const hasRoomForSidebarColumn = useMediaQuery('(min-height: 820px)')
const preferDrawerNav = computed(
  () => !hasRoomForSidebarColumn.value && categories.value.length > 6,
)

const isSidebarOpen = ref(false) // mobile drawer
const isIntroOpen = ref(false)
const guestName = ref('')

/**
 * The viewer can collapse the column away to give the creatives the full width.
 *
 * Remembered across previews rather than per preview: it is a statement about
 * how someone likes to review work, not about one job. Restored in `onMounted`
 * so it survives a reload, and written through `setSidebarCollapsed` so the two
 * never drift apart.
 */
const SIDEBAR_COLLAPSED_KEY = 'show2-sidebar-collapsed'
const isSidebarCollapsed = ref(false)

const setSidebarCollapsed = (value: boolean) => {
  isSidebarCollapsed.value = value
  try {
    localStorage.setItem(SIDEBAR_COLLAPSED_KEY, value ? '1' : '0')
  } catch {
    /* private mode, ignore */
  }
}

// True once the user has scrolled past the topbar. Used to fade the
// header logo out and slide a copy into the top of the sticky sidebar
// — the logo "transfers" between zones as you scroll. Threshold is
// generous so the transfer feels intentional, not a flicker.
const isScrolled = ref(false)
provide('show2Scrolled', readonly(isScrolled))

const introSeenKey = computed(() => `show2-intro-seen-${props.preview?.id ?? 'x'}`)
const onIntroOpenChange = (v: boolean) => {
  isIntroOpen.value = v
  if (!v) {
    try { localStorage.setItem(introSeenKey.value, '1') } catch { /* private mode, ignore */ }
  }
}

const initGuestName = () => {
  let name = localStorage.getItem('guest_name')
  if (!name) {
    name = 'Guest-' + Math.floor(Math.random() * 10000)
    localStorage.setItem('guest_name', name)
  }
  guestName.value = name
}

const trackViewer = () => {
  axios.post('/track-viewer', {
    page_id: props.preview.id,
    guest_name: guestName.value,
  })
}

const fetchViewers = () => {
  axios.get(`/get-viewers/${props.preview.id}`).then((res) => {
    viewers.value = res.data || []
  })
}

const applyAjaxPayload = (data: any) => {
  categories.value = data.categories || []
  feedbacks.value = data.feedbacks || []
  feedbackSets.value = (data.feedbackSets || []).map((s: any) => ({ ...s, versions: [] }))
  activeCategory.value = data.activeCategory || null
  activeFeedback.value = data.activeFeedback || null
  fileTransfer.value = data.fileTransfer || null
}

// Race-condition guard. Each async select operation bumps + captures the
// epoch; after every await we re-check it. If the user fired another
// select while we were waiting, the stale handler bails out before
// overwriting fresh state with old data or clearing the loading flag too
// early (which was making the empty state flash on rapid switches).
let activeEpoch = 0

const loadVersionsAndAssets = async (epoch: number) => {
  if (!activeCategory.value) return
  const type = activeCategory.value.type
  await Promise.all(
    feedbackSets.value.map(async (set) => {
      const versionsRes = await axios.get(`/preview/renderVersions/${set.id}`)
      if (epoch !== activeEpoch) return
      const versions = (versionsRes.data.versions || []).map((v: any) => ({ ...v, assets: [] }))
      set.versions = versions

      await Promise.all(
        versions.map(async (version: any) => {
          try {
            if (type === 'banner') {
              const r = await axios.get(`/preview/renderBanners/${version.id}`)
              if (epoch !== activeEpoch) return
              version.assets = r.data.banners || []
            } else if (type === 'video') {
              const r = await axios.get(`/preview/renderVideos/${version.id}`)
              if (epoch !== activeEpoch) return
              version.assets = r.data.videos || []
            } else if (type === 'social') {
              const r = await axios.get(`/preview/renderSocials/${version.id}`)
              if (epoch !== activeEpoch) return
              version.assets = r.data.socials || []
            } else if (type === 'gif') {
              const r = await axios.get(`/preview/renderGifs/${version.id}`)
              if (epoch !== activeEpoch) return
              version.assets = r.data.gifs || []
            }
          } catch {
            version.assets = []
          }
        })
      )
    })
  )
}

const initialLoad = async () => {
  const epoch = ++activeEpoch
  isInitialLoading.value = true
  try {
    const res = await axios.get(`/preview/renderCategories/${props.previewId}`)
    if (epoch !== activeEpoch) return
    applyAjaxPayload(res.data)
    await loadVersionsAndAssets(epoch)
  } catch (e) {
    console.error('Failed to load categories', e)
  } finally {
    if (epoch === activeEpoch) {
      isInitialLoading.value = false
    }
  }
}

const onCategorySelect = async (categoryId: number) => {
  if (activeCategory.value?.id === categoryId) return
  const epoch = ++activeEpoch
  isAssetsLoading.value = true
  isSidebarOpen.value = false
  try {
    const res = await axios.post(`/preview/updateActiveCategory/${categoryId}`)
    if (epoch !== activeEpoch) return
    applyAjaxPayload(res.data)
    await loadVersionsAndAssets(epoch)
  } finally {
    if (epoch === activeEpoch) {
      isAssetsLoading.value = false
    }
  }
}

const onFeedbackSelect = async (feedbackId: number) => {
  if (activeFeedback.value?.id === feedbackId) return
  const epoch = ++activeEpoch
  isAssetsLoading.value = true
  try {
    const res = await axios.post(`/preview/updateActiveFeedback/${feedbackId}`)
    if (epoch !== activeEpoch) return
    applyAjaxPayload(res.data)
    await loadVersionsAndAssets(epoch)
  } finally {
    if (epoch === activeEpoch) {
      isAssetsLoading.value = false
    }
  }
}

const onThemeChange = (colorId: number) => {
  // Apply locally for instant feedback; the request below just persists.
  const next = props.allColors.find((p) => p.id === colorId)
  if (next) currentPalette.value = next
  isPaletteOpen.value = false
  axios.post(`/preview/${props.previewId}/change/theme/${colorId}`).catch((err) => {
    console.error('Failed to persist theme change', err)
  })
}

const onLogout = (e: Event) => {
  e.preventDefault()
  router.post('/preview/logout', { preview_id: props.preview.id })
}

let viewerInterval: number | null = null
let trackingInterval: number | null = null

// Scroll watch — drives the logo transfer between topbar and sidebar.
const onScroll = () => { isScrolled.value = window.scrollY > 32 }

onMounted(async () => {
  initGuestName()
  // Read here rather than in the ref's initialiser: this component also renders
  // server-side, where there is no localStorage.
  try {
    isSidebarCollapsed.value = localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === '1'
  } catch { /* private mode, ignore */ }
  await initialLoad()
  trackingInterval = window.setInterval(trackViewer, 8000)
  if (props.authUserClientName === 'Planet Nine') {
    fetchViewers()
    viewerInterval = window.setInterval(fetchViewers, 10000)
  }
  // First-visit intro. Wait a beat so the page paints first, then drift in.
  try {
    if (!localStorage.getItem(introSeenKey.value)) {
      window.setTimeout(() => { isIntroOpen.value = true }, 350)
    }
  } catch { /* private mode, skip */ }
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
})

onUnmounted(() => {
  if (viewerInterval) clearInterval(viewerInterval)
  if (trackingInterval) clearInterval(trackingInterval)
  window.removeEventListener('scroll', onScroll)
  // Restore the admin's global appearance so leaving Show2 doesn't keep
  // the page-scoped dark mode applied across the rest of the app.
  if (typeof document !== 'undefined') {
    document.documentElement.classList.toggle('dark', priorRootDark)
  }
})

const themeStyle = computed(() => ({
  '--p2-accent': accent.value,
  '--p2-accent-2': accent2.value,
  '--p2-accent-3': accent3.value,
  '--p2-accent-soft': accentSoft.value,
  '--p2-accent-muted': accentMuted.value,
  '--p2-accent-2-soft': accent2Soft.value,
  '--p2-accent-glow': accentGlow.value,
  '--p2-accent-2-glow': accent2Glow.value,
} as any))
</script>

<template>
  <Head :title="`Creative · ${preview.name}`" />

  <!-- `flex flex-col` exists for the footer. `sticky bottom-0` can only pull an
       element up when it would fall below the fold — it cannot push one down, so
       on a short page (a preview with no assets yet) the footer used to sit
       directly under the content, stranded mid-screen with empty space beneath
       it. As a flex column the content row takes the leftover height and lands
       the footer on the bottom edge instead. The two ambient layers are
       `position: fixed`, so they stay out of this. -->
  <div
    class="show2-root flex min-h-screen flex-col text-zinc-900 antialiased dark:text-zinc-100"
    :style="themeStyle"
  >
    <!-- Decorative ambient color wash. Light mode is asset-first
         (very subtle); dark mode opens up into a cinematic Planet Nine
         backdrop with a starfield + aurora glow. -->
    <div aria-hidden="true" class="show2-ambient" />
    <div aria-hidden="true" class="show2-stars" />

    <PreviewTopBar
      :preview="preview"
      :client="client"
      :header-logo="headerLogo"
      :viewers="viewers"
      :is-authenticated="isAuthenticated"
      :auth-user-client-name="authUserClientName"
      :force-drawer-nav="preferDrawerNav"
      :active-category="activeCategory"
      @open-sidebar="isSidebarOpen = true"
      @open-palette="isPaletteOpen = true"
      @logout="onLogout"
    />

    <!-- `lg:pl-20` when collapsed leaves room for the fixed rail. Without it the
         rail floats over the creatives on a ~1024-1280px desktop, where the
         centred container has no margin to spare. Still a big win on width: 80px
         instead of the column's 288px plus a 24px gutter. -->
    <div
      class="mx-auto flex w-full max-w-[2000px] flex-1 gap-6 px-4 pb-24 pt-6 lg:px-8"
      :class="isSidebarCollapsed ? 'lg:pl-20' : ''"
    >
      <ProjectSidebar
        :categories="categories"
        :active-category="activeCategory"
        :is-loading="isInitialLoading"
        :is-mobile-open="isSidebarOpen"
        :force-drawer-nav="preferDrawerNav"
        :collapsed="isSidebarCollapsed"
        :preview="preview"
        :client="client"
        :header-logo="headerLogo"
        @select="onCategorySelect"
        @close="isSidebarOpen = false"
        @collapse="setSidebarCollapsed(true)"
        @expand="setSidebarCollapsed(false)"
      />

      <main class="min-w-0 flex-1">
        <RoundTabs
          :feedbacks="feedbacks"
          :active-feedback="activeFeedback"
          :is-loading="isInitialLoading"
          data-tour="rounds"
          @select="onFeedbackSelect"
        />

        <AssetCanvas
          :feedback-sets="feedbackSets"
          :active-category="activeCategory"
          :active-feedback="activeFeedback"
          :is-loading="isInitialLoading || isAssetsLoading"
          @open-notes="isNotesOpen = true"
        />
      </main>
    </div>

    <!--
      Planet Nine credit. Gated on `show_footer`, the flag the edit page already
      exposes as "Show Footer?" — it drives the same footer on the older Show
      viewer but was never implemented here, so toggling it did nothing on this
      page and the preview carried no Planet Nine branding at all once a client
      was picked in the header-logo select.

      Sticky rather than in-flow so the credit stays visible while scrolling.
      Two floating glass pills rather than a full-width bar: the page is an
      asset viewer, so the chrome stays out of the way and lets the work run
      edge to edge behind it.

      The <footer> itself is only a transparent positioner now, which is why it
      is `pointer-events-none` — full-width and invisible, it would otherwise
      swallow clicks on whatever sits under it. Each pill takes pointer events
      back for itself.

      z-20 sits under the top bar (z-30) and under the floating file-transfer
      dock and help button (z-40), which remain clickable above it. The pill is
      centred and the dock is bottom-right, so they no longer collide and the
      old `pr-28` nudge is gone.
    -->
    <footer
      v-if="preview?.show_footer"
      class="show2-sticky pointer-events-none sticky bottom-0 z-20 flex justify-center px-4 pb-2"
    >
      <!-- One pill, two halves: the credit and a hairline, then the mark. The
           glass and the rounding live on the wrapper so the two read as a
           single object rather than two things that happen to be adjacent. -->
      <div
        class="p2-glass show2-credit pointer-events-auto flex items-center gap-2.5 rounded-full py-1.5 pl-3.5 pr-2 text-xs shadow-sm"
        :style="{ color: 'var(--p2-text-muted)' }"
      >
        <span>&copy; {{ currentYear }} All rights reserved.</span>

        <span aria-hidden="true" class="h-3.5 w-px shrink-0" :style="{ background: 'var(--p2-hairline)' }" />

        <!-- The mark itself is the link. `planetnine.png` is Planet Nine's own
             attribution, not the preview's branding, so it is the static asset
             rather than the header/client logo the top bar and sidebar show.
             The negative vertical margin lets the hit area fill the pill's
             full height without the pill growing around it. -->
        <a
          href="https://www.planetnine.com"
          target="_blank"
          rel="noopener noreferrer"
          title="Planet Nine"
          class="-my-1.5 flex items-center rounded-full px-2 py-1.5"
        >
          <img src="/logos/planetnine.png" alt="Planet Nine" class="h-4 w-auto" />
        </a>
      </div>
    </footer>

    <NotesSheet
      v-model:open="isNotesOpen"
      :feedback="activeFeedback"
    />


    <PaletteSwitcher
      v-model:open="isPaletteOpen"
      :all-colors="allColors"
      :current-id="currentPalette?.id"
      @select="onThemeChange"
    />

    <IntroOverlay
      :open="isIntroOpen"
      :preview-name="preview?.name || ''"
      :client-name="client?.name || ''"
      @update:open="onIntroOpenChange"
    />

    <!-- Floating bottom-right row: file transfer dock left of the help fab -->
    <div class="fixed bottom-5 right-5 z-40 flex flex-col items-end gap-3">
      <FileTransferDock
        v-if="fileTransfer"
        :file-transfer="fileTransfer"
        data-tour="file-transfer"
      />
      <IntroAssistant
        v-show="!isIntroOpen"
        @start-tour="isIntroOpen = true"
      />
    </div>
  </div>
</template>

<style>
/* Planet Nine cinematic typeface stack — Inter for everything,
   JetBrains Mono for tags / timestamps / numerics. Loaded once
   per page via @import to avoid the global CSS pulling in fonts
   the rest of the app does not need. */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

.show2-root {
  /* No `position` here on purpose — modals/sheets that re-use this
     class for token cascade (NotesSheet, PaletteSwitcher) need to
     keep their own `fixed`/`absolute` positioning. The page root
     creates a stacking context indirectly: every direct child gets
     `position: relative` via the descendant rule below, which is
     enough to layer above the fixed-position ambient + stars. */
  /* ---------- Surface tokens (asset-first, low distraction) ---------- */
  --p2-bg: #fafafa;
  --p2-surface: #ffffff;
  --p2-surface-muted: rgba(255, 255, 255, 0.85);
  --p2-text: #18181b;
  --p2-text-muted: #71717a;
  --p2-text-subtle: #a1a1aa;
  --p2-border: rgba(15, 15, 20, 0.08);
  --p2-border-strong: rgba(15, 15, 20, 0.16);
  --p2-hairline: rgba(15, 15, 20, 0.06);
  /* ---------- Motion tokens (Planet Nine cinema easings) ---------- */
  --p2-ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
  --p2-ease-cinema: cubic-bezier(0.22, 1, 0.36, 1);

  background-color: var(--p2-bg);
  color: var(--p2-text);
  font-family: 'Inter', 'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif;
  font-feature-settings: 'cv11', 'ss01', 'tnum';
}

.dark .show2-root {
  /* Cinematic space-tech surface in dark mode. Background lifts
     a touch from the website's #0B0B10 because Show2 mounts cards
     directly on top — pure black makes glass cards feel detached. */
  --p2-bg: #0B0B10;
  --p2-surface: #1E1E23;
  --p2-surface-muted: rgba(30, 30, 35, 0.45);
  --p2-text: #F8FAFC;
  --p2-text-muted: #94A3B8;
  --p2-text-subtle: #71717a;
  --p2-border: rgba(255, 255, 255, 0.10);
  --p2-border-strong: rgba(255, 255, 255, 0.22);
  --p2-hairline: rgba(255, 255, 255, 0.06);
}

/* Light-mode ambient: kept extremely soft. The accent washes hint
   at the active palette without competing with banner / video /
   gif assets in the canvas. */
.show2-ambient {
  pointer-events: none;
  position: fixed;
  inset: 0;
  z-index: 0;
  background:
    radial-gradient(55% 45% at 0% 0%, var(--p2-accent-soft) 0%, transparent 70%),
    radial-gradient(40% 40% at 100% 0%, var(--p2-accent-2-soft) 0%, transparent 75%);
  opacity: 0.5;
}

/* Dark-mode ambient: full Planet Nine aurora bloom. */
.dark .show2-ambient {
  background:
    radial-gradient(70% 55% at 5% 0%, var(--p2-accent-glow) 0%, transparent 65%),
    radial-gradient(60% 55% at 100% 25%, var(--p2-accent-2-glow) 0%, transparent 70%),
    radial-gradient(60% 60% at 100% 100%, var(--p2-accent-glow) 0%, transparent 75%);
  opacity: 0.55;
}

/* Three-depth starfield. Hidden in light mode (would distract
   from assets); visible in dark, with subtle parallax twinkle. */
.show2-stars {
  pointer-events: none;
  position: fixed;
  inset: 0;
  z-index: 0;
  opacity: 0;
  transition: opacity 600ms var(--p2-ease-cinema);
  background-image:
    radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.85), transparent 50%),
    radial-gradient(1px 1px at 60% 70%, rgba(255,255,255,0.7), transparent 50%),
    radial-gradient(1.5px 1.5px at 80% 20%, rgba(255,255,255,0.6), transparent 50%),
    radial-gradient(1px 1px at 35% 85%, rgba(255,255,255,0.5), transparent 50%),
    radial-gradient(1px 1px at 90% 50%, rgba(255,255,255,0.65), transparent 50%);
  background-size: 1200px 800px;
}
.dark .show2-stars { opacity: 0.55; animation: p2-twinkle 6s ease-in-out infinite; }
@keyframes p2-twinkle {
  0%, 100% { opacity: 0.4; }
  50%      { opacity: 0.65; }
}

.show2-root > :not(.show2-ambient):not(.show2-stars):not(.fixed):not(.show2-sticky) {
  position: relative;
  z-index: 1;
}

/* Opt-out for children that must keep their own `position`.
   This rule forces `relative` on every child to layer them above the fixed
   ambient/stars, which silently defeated the footer's `sticky bottom-0`.
   Only elements marked `.show2-sticky` are exempt — deliberately narrow: the
   top bar also carries `sticky top-0`, but the design scrolls it away (its
   logo transfers into the sidebar via `isScrolled`), so it must keep the
   `relative` it has always had. */
.show2-root > .show2-sticky {
  z-index: 20;
}

/* ---------- Reusable Planet Nine primitives ---------- */

/* Label-overline: 11–12px uppercase tracked label. Used for
   section labels ("Revision Round", "Surface · 01"). */
.p2-label {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: var(--p2-text-muted);
}

.p2-mono { font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace; }

/* Glass surface — used for cards over the ambient backdrop. */
.p2-glass {
  background: var(--p2-surface-muted);
  border: 1px solid var(--p2-border);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

/* Hairline divider — 1px, almost invisible, just enough to give
   structure without weight. */
.p2-hairline { border-color: var(--p2-hairline); }

/* Pill primary CTA — white-on-light, accent-on-dark, used for
   "Plan a 15-min intro"-style calls-to-action and submit buttons. */
.p2-pill-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.625rem;
  height: 2.5rem;
  padding: 0 1.25rem;
  border-radius: 9999px;
  background: var(--p2-text);
  color: var(--p2-bg);
  font-size: 0.875rem;
  font-weight: 500;
  transition: transform 200ms var(--p2-ease-expo), background 200ms var(--p2-ease-expo);
}
.p2-pill-primary:hover { transform: translateY(-1px); }

/* Pill ghost CTA — outlined, glass-blurred. For secondary actions. */
.p2-pill-ghost {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  height: 2.5rem;
  padding: 0 1rem;
  border-radius: 9999px;
  border: 1px solid var(--p2-border);
  background: var(--p2-surface-muted);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  color: var(--p2-text);
  font-size: 0.875rem;
  font-weight: 500;
  transition: border-color 200ms var(--p2-ease-expo), background 200ms var(--p2-ease-expo);
}
.p2-pill-ghost:hover { border-color: var(--p2-border-strong); }

/* Footer credit pill. Hover follows `.p2-pill-ghost`: the border firms up and
   the surface gains a little weight, rather than the fill changing colour — the
   pill floats over the creative work, so it should never pull the eye off it. */
.show2-credit {
  transition:
    border-color 200ms var(--p2-ease-expo),
    background 200ms var(--p2-ease-expo);
}
.show2-credit:hover {
  border-color: var(--p2-border-strong);
  background: var(--p2-surface);
}

/* The mark is a dark slate wordmark on transparency, so on the dark theme's
   glass it sinks into the background. Inverting it lifts it back out. */
.dark .show2-credit img {
  filter: invert(1);
}

/* Focus rings — 2px accent ring on every interactive surface. */
.show2-root :focus-visible {
  outline: none;
  box-shadow: 0 0 0 2px var(--p2-bg), 0 0 0 4px var(--p2-accent);
  border-radius: inherit;
}

/* Reduced motion — kill all but fades, in line with the website. */
@media (prefers-reduced-motion: reduce) {
  .show2-root *,
  .show2-root *::before,
  .show2-root *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
  .show2-stars { animation: none; }
}
</style>
