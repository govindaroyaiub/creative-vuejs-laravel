<script setup lang="ts">
/**
 * DEMO ONLY: /previews/review/{slug}
 *
 * The Show2 viewer, unchanged, plus a prototype layer for managers to try:
 *  - a comment / status row under every creative
 *  - review mode (big stage, round history, compare, approve, comments)
 *  - a floating demo bar: switch between client and team view, see round
 *    progress, reset the demo data
 *
 * Everything the prototype records stays in this browser (localStorage).
 * To remove: delete this file, the Review/ folder, PreviewReviewDemoController
 * and its route.
 */
import { computed, provide, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { FlaskConical, RotateCcw, Users, ChevronDown } from 'lucide-vue-next'
import Show2 from './Show2.vue'
import CreativeFooter from './Review/CreativeFooter.vue'
import ReviewMode from './Review/ReviewMode.vue'
import { createReviewDemo } from './Review/useReviewDemo'
import VideoShowcase from './Review/video/VideoShowcase.vue'
import { createShowcaseState } from './Review/video/useVideoFormat'
import SocialShowcase from './Review/social/SocialShowcase.vue'
import { createSocialShowcaseState } from './Review/social/useSocialShowcase'
import BannerShowcase from './Review/banner/BannerShowcase.vue'
import { createBannerShowcaseState } from './Review/banner/useBannerSlots'

const props = defineProps<{
  preview: any
  client: any
  headerLogo: any
  palette: any
  allColors: any[]
  authUserClientName: string
  previewId: number | string
  isAuthenticated: boolean
  initialPayload?: any
  history: Record<string, any[]>
}>()

const page = usePage<any>()
const teamName = computed(() => page.props?.auth?.user?.name || 'Planet Nine')

const demo = createReviewDemo({
  previewId: props.previewId,
  history: props.history,
  categories: props.initialPayload?.categories ?? [],
  teamName: teamName.value,
})

provide('reviewDemo', demo)
provide('creativeFooter', CreativeFooter)
// No Revision Notes button here: feedback lives on each creative. (The intro
// tour drops its notes step on its own, since the button is not on the page.)
provide('hideRevisionNotes', true)

// Video showcase: in-context frames (phone, laptop, TV, article), safe zones.
provide('videoShowcase', VideoShowcase)
provide('videoShowcaseState', createShowcaseState())
// Social showcase: feed (carousel), story, link ad, pin, profile grid.
provide('socialShowcase', SocialShowcase)
provide('socialShowcaseState', createSocialShowcaseState())
// Banner showcase: centred clean view, desktop site takeover, mobile article.
provide('bannerShowcase', BannerShowcase)
provide('bannerShowcaseState', createBannerShowcaseState())
const brandName = props.client?.name || 'Brand'
provide('videoBrand', {
  name: brandName,
  handle: brandName.toLowerCase().replace(/[^a-z0-9]+/g, ''),
  logo: props.client?.logo ? `/logos/${props.client.logo}` : null,
})

const show2Props = computed(() => {
  const { history: _history, ...rest } = props
  return rest
})

const barOpen = ref(true)
const progress = demo.roundProgress
const progressPct = computed(() =>
  progress.value.total ? Math.round((progress.value.approved / progress.value.total) * 100) : 0,
)

const resetDemo = () => {
  if (window.confirm('Reset the demo comments and approvals for this preview?')) demo.reset()
}
</script>

<template>
  <Show2 v-bind="show2Props" />
  <ReviewMode />

  <!-- Floating demo bar (left, so it does not collide with the help/file dock) -->
  <div class="fixed bottom-5 left-5 z-50 max-w-[calc(100vw-2.5rem)]">
    <div
      class="demo-bar rounded-2xl border shadow-lg"
      :class="barOpen ? 'w-80' : ''"
    >
      <button
        type="button"
        class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-xs font-semibold"
        :aria-expanded="barOpen"
        @click="barOpen = !barOpen"
      >
        <FlaskConical class="h-4 w-4 text-amber-500" />
        <span>Review prototype</span>
        <span class="demo-pill">Demo · not saved</span>
        <ChevronDown class="ml-auto h-4 w-4 transition-transform" :class="barOpen ? '' : '-rotate-90'" />
      </button>

      <div v-if="barOpen" class="space-y-3 border-t px-4 pb-4 pt-3 text-xs demo-hair">
        <!-- Who am I -->
        <div>
          <p class="mb-1.5 flex items-center gap-1.5 font-medium demo-muted"><Users class="h-3.5 w-3.5" /> Viewing as</p>
          <div class="grid grid-cols-2 gap-1 rounded-xl p-1 demo-track">
            <button
              type="button"
              class="rounded-lg py-1.5 font-medium"
              :class="demo.state.viewer === 'client' ? 'demo-seg-on' : 'demo-muted'"
              @click="demo.state.viewer = 'client'"
            >Client (guest)</button>
            <button
              type="button"
              class="rounded-lg py-1.5 font-medium"
              :class="demo.state.viewer === 'team' ? 'demo-seg-on' : 'demo-muted'"
              @click="demo.state.viewer = 'team'"
            >Team</button>
          </div>
          <input
            v-if="demo.state.viewer === 'client'"
            v-model="demo.state.guestName"
            class="demo-input mt-1.5 w-full rounded-lg px-2.5 py-1.5"
            placeholder="Guest name"
            aria-label="Guest name"
          />
          <p v-else class="mt-1.5 demo-muted">Commenting as {{ teamName }}</p>
        </div>

        <!-- Round progress -->
        <div v-if="progress.total">
          <div class="mb-1.5 flex items-center justify-between">
            <span class="font-medium demo-muted">{{ demo.view.roundName || 'This round' }}</span>
            <span class="p2-mono tabular-nums">{{ progress.approved }}/{{ progress.total }} approved</span>
          </div>
          <div class="h-1.5 overflow-hidden rounded-full demo-track">
            <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: `${progressPct}%` }" />
          </div>
          <p v-if="progress.changes" class="mt-1.5 text-amber-600 dark:text-amber-400">
            {{ progress.changes }} need{{ progress.changes === 1 ? 's' : '' }} changes
          </p>
        </div>

        <button type="button" class="inline-flex items-center gap-1.5 demo-muted hover:underline" @click="resetDemo">
          <RotateCcw class="h-3.5 w-3.5" /> Reset demo data
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.demo-bar {
  background: rgba(255, 255, 255, 0.92);
  border-color: rgba(15, 15, 20, 0.1);
  color: #18181b;
  backdrop-filter: blur(12px);
}
.demo-hair { border-color: rgba(15, 15, 20, 0.08); }
.demo-muted { color: #71717a; }
.demo-track { background: rgba(15, 15, 20, 0.06); }
.demo-seg-on { background: #fff; color: #18181b; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08); }
.demo-input { border: 1px solid rgba(15, 15, 20, 0.12); background: #fff; color: #18181b; }
.demo-pill {
  border-radius: 9999px;
  padding: 0.1rem 0.5rem;
  font-size: 10px;
  font-weight: 500;
  background: rgb(245 158 11 / 0.14);
  color: rgb(217 119 6);
}
:global(.dark) .demo-bar { background: rgba(30, 30, 35, 0.92); border-color: rgba(255, 255, 255, 0.1); color: #f8fafc; }
:global(.dark) .demo-hair { border-color: rgba(255, 255, 255, 0.08); }
:global(.dark) .demo-muted { color: #94a3b8; }
:global(.dark) .demo-track { background: rgba(255, 255, 255, 0.08); }
:global(.dark) .demo-seg-on { background: #2a2a31; color: #f8fafc; }
:global(.dark) .demo-input { border-color: rgba(255, 255, 255, 0.12); background: #1e1e23; color: #f8fafc; }
</style>
