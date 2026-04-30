<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, provide, readonly } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'

import PreviewTopBar from './Show2/PreviewTopBar.vue'
import ProjectSidebar from './Show2/ProjectSidebar.vue'
import RoundTabs from './Show2/RoundTabs.vue'
import AssetCanvas from './Show2/AssetCanvas.vue'
import NotesSheet from './Show2/NotesSheet.vue'
import FileTransferDock from './Show2/FileTransferDock.vue'
import PaletteSwitcher from './Show2/PaletteSwitcher.vue'
import IntroOverlay from './Show2/IntroOverlay.vue'
import IntroAssistant from './Show2/IntroAssistant.vue'

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
const isSidebarOpen = ref(false) // mobile drawer
const isIntroOpen = ref(false)
const guestName = ref('')

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
    const res = await axios.get(`/preview/updateActiveCategory/${categoryId}`)
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
    const res = await axios.get(`/preview/updateActiveFeedback/${feedbackId}`)
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
  axios.get(`/preview/${props.previewId}/change/theme/${colorId}`).catch((err) => {
    console.error('Failed to persist theme change', err)
  })
}

const onLogout = (e: Event) => {
  e.preventDefault()
  router.post('/preview/logout', { preview_id: props.preview.id })
}

let viewerInterval: number | null = null
let trackingInterval: number | null = null

onMounted(async () => {
  initGuestName()
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
})

onUnmounted(() => {
  if (viewerInterval) clearInterval(viewerInterval)
  if (trackingInterval) clearInterval(trackingInterval)
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

  <div class="show2-root min-h-screen text-zinc-900 antialiased dark:text-zinc-100" :style="themeStyle">
    <!-- Decorative ambient color wash -->
    <div aria-hidden="true" class="show2-ambient" />

    <PreviewTopBar
      :preview="preview"
      :client="client"
      :header-logo="headerLogo"
      :viewers="viewers"
      :is-authenticated="isAuthenticated"
      :auth-user-client-name="authUserClientName"
      @open-sidebar="isSidebarOpen = true"
      @open-palette="isPaletteOpen = true"
      @logout="onLogout"
    />

    <div class="mx-auto flex w-full max-w-[1800px] gap-6 px-4 pb-24 pt-6 lg:px-8">
      <ProjectSidebar
        :categories="categories"
        :active-category="activeCategory"
        :is-loading="isInitialLoading"
        :is-mobile-open="isSidebarOpen"
        @select="onCategorySelect"
        @close="isSidebarOpen = false"
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
.show2-root {
  position: relative;
  background-color: #fafafa;
  font-family: 'Inter', 'Montserrat', system-ui, -apple-system, sans-serif;
  font-feature-settings: 'cv11', 'ss01', 'tnum';
}
.dark .show2-root {
  background-color: #0a0a0d;
}
.show2-ambient {
  pointer-events: none;
  position: fixed;
  inset: 0;
  z-index: 0;
  background:
    radial-gradient(60% 50% at 0% 0%, var(--p2-accent-soft) 0%, transparent 60%),
    radial-gradient(45% 45% at 100% 0%, var(--p2-accent-2-soft) 0%, transparent 65%),
    radial-gradient(40% 40% at 100% 100%, var(--p2-accent-soft) 0%, transparent 70%);
}
.dark .show2-ambient {
  background:
    radial-gradient(70% 55% at 5% 0%, var(--p2-accent-glow) 0%, transparent 65%),
    radial-gradient(60% 55% at 100% 25%, var(--p2-accent-2-glow) 0%, transparent 70%),
    radial-gradient(60% 60% at 100% 100%, var(--p2-accent-glow) 0%, transparent 75%);
  opacity: 0.55;
}
.show2-root > :not(.show2-ambient):not(.fixed) {
  position: relative;
  z-index: 1;
}
</style>
