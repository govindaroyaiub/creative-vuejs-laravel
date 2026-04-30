<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import {
  X, ArrowRight, ArrowLeft, Sparkles, Sparkle,
  FolderKanban, History, Layers, MessageSquare, Package, Palette,
  Image as ImageIcon,
} from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
  previewName: string
  clientName: string
}>()

const emit = defineEmits<{
  (e: 'update:open', v: boolean): void
}>()

type TourStep = {
  mode: 'centered' | 'spotlight'
  target?: string
  icon: any
  eyebrow: string
  title: string
  body: string
}

// Full step list. Spotlight steps without a matching DOM target are filtered
// out at open time, so e.g. the file-transfer step is skipped when there's
// nothing to deliver.
const ALL_STEPS: TourStep[] = [
  {
    mode: 'centered',
    icon: Sparkle,
    eyebrow: 'Welcome',
    title: '',
    body: 'A quick walk-through, ~30 seconds. Skip anytime.',
  },
  {
    mode: 'spotlight',
    target: '[data-tour="projects"]',
    icon: FolderKanban,
    eyebrow: 'Creatives',
    title: 'Every creative, in one list.',
    body: 'The sidebar lists every creative — banners, videos, socials, GIFs. Pick one to load its assets.',
  },
  {
    mode: 'spotlight',
    target: '[data-tour="rounds"]',
    icon: History,
    eyebrow: 'Revision rounds',
    title: 'Compare rounds at a glance.',
    body: 'Each tab is a round of feedback. Use them to see how the work has evolved.',
  },
  {
    mode: 'spotlight',
    target: '[data-tour="version-set"]',
    icon: Layers,
    eyebrow: 'Versions & sets',
    title: 'Versions group related sets.',
    body: 'A version (like A or B) bundles one or more sets — each set is a self-contained run of creative.',
  },
  {
    mode: 'spotlight',
    target: '[data-tour="assets"]',
    icon: ImageIcon,
    eyebrow: 'Assets',
    title: 'The actual creative.',
    body: 'Banners, videos, GIFs, and social cuts preview here at their native size.',
  },
  {
    mode: 'spotlight',
    target: '[data-tour="notes"]',
    icon: MessageSquare,
    eyebrow: 'Revision notes',
    title: 'Read the brief.',
    body: 'Open Notes to see what the team is asking feedback on, and what changed in this round.',
  },
  {
    mode: 'spotlight',
    target: '[data-tour="theme"]',
    icon: Palette,
    eyebrow: 'Make it yours',
    title: 'Theme & dark mode.',
    body: 'Pick a palette or flip dark mode from the top bar. Changes only affect this preview.',
  },
  {
    mode: 'spotlight',
    target: '[data-tour="file-transfer"]',
    icon: Package,
    eyebrow: 'Deliverables',
    title: 'Grab approved files here.',
    body: 'When a round is approved, deliverables land in this dock — pinned to the bottom-right.',
  },
  {
    mode: 'centered',
    icon: Sparkles,
    eyebrow: 'Ready',
    title: 'You are all set.',
    body: 'Take your time. We will be here whenever you have feedback.',
  },
]

const visibleSteps = ref<TourStep[]>([])
const stepIdx = ref(0)
const targetRect = ref<DOMRect | null>(null)
const vw = ref(0)
const vh = ref(0)

const currentStep = computed(() => visibleSteps.value[stepIdx.value] ?? null)
const totalSteps = computed(() => visibleSteps.value.length)
const isCentered = computed(() => currentStep.value?.mode === 'centered')

const buildSteps = () => {
  visibleSteps.value = ALL_STEPS.filter((s) => {
    if (s.mode === 'centered' || !s.target) return true
    return !!document.querySelector(s.target)
  })
}

const PAD = 12

const measure = () => {
  if (typeof window === 'undefined') return
  vw.value = window.innerWidth
  vh.value = window.innerHeight

  const s = currentStep.value
  if (!s || s.mode !== 'spotlight' || !s.target) {
    targetRect.value = null
    return
  }
  const el = document.querySelector(s.target) as HTMLElement | null
  if (!el) {
    targetRect.value = null
    return
  }

  // Only scroll into view when the element is meaningfully out of view —
  // avoids twitchy re-scrolls when the rect is already fine.
  let rect = el.getBoundingClientRect()
  const fullyOff =
    rect.bottom < 0 || rect.top > vh.value ||
    rect.right < 0 || rect.left > vw.value
  if (fullyOff) {
    el.scrollIntoView({ block: 'center', inline: 'center', behavior: 'auto' })
    rect = el.getBoundingClientRect()
  }
  targetRect.value = rect
}

watch(() => props.open, (v) => {
  if (v) {
    stepIdx.value = 0
    buildSteps()
    nextTick(measure)
  }
})

watch(stepIdx, () => nextTick(measure))

const close = () => {
  emit('update:open', false)
  window.setTimeout(() => { stepIdx.value = 0 }, 320)
}

const next = () => {
  if (stepIdx.value < totalSteps.value - 1) stepIdx.value += 1
  else close()
}

const prev = () => {
  if (stepIdx.value > 0) stepIdx.value -= 1
}

const onKey = (e: KeyboardEvent) => {
  if (!props.open) return
  if (e.key === 'Escape') { e.preventDefault(); close() }
  else if (e.key === 'ArrowRight' || e.key === 'Enter') { e.preventDefault(); next() }
  else if (e.key === 'ArrowLeft') { e.preventDefault(); prev() }
}

const onResize = () => measure()

onMounted(() => {
  document.addEventListener('keydown', onKey)
  window.addEventListener('resize', onResize)
  document.addEventListener('scroll', onResize, true)
})
onUnmounted(() => {
  document.removeEventListener('keydown', onKey)
  window.removeEventListener('resize', onResize)
  document.removeEventListener('scroll', onResize, true)
})

// ---- Spotlight geometry ------------------------------------------------
const spotlight = computed(() => {
  const r = targetRect.value
  if (!r) return null
  return {
    x: r.left - PAD,
    y: r.top - PAD,
    w: r.width + PAD * 2,
    h: r.height + PAD * 2,
    cx: r.left + r.width / 2,
  }
})

// 4 mask pieces, each fully positioned with top/left/width/height so CSS
// can transition between cutouts smoothly.
const maskTop = computed(() => {
  const sp = spotlight.value; if (!sp) return null
  return {
    position: 'fixed' as const,
    left: '0px',
    top: '0px',
    width: '100%',
    height: `${Math.max(0, sp.y)}px`,
  }
})
const maskBottom = computed(() => {
  const sp = spotlight.value; if (!sp) return null
  return {
    position: 'fixed' as const,
    left: '0px',
    top: `${sp.y + sp.h}px`,
    width: '100%',
    height: `${Math.max(0, vh.value - (sp.y + sp.h))}px`,
  }
})
const maskLeft = computed(() => {
  const sp = spotlight.value; if (!sp) return null
  return {
    position: 'fixed' as const,
    left: '0px',
    top: `${sp.y}px`,
    width: `${Math.max(0, sp.x)}px`,
    height: `${sp.h}px`,
  }
})
const maskRight = computed(() => {
  const sp = spotlight.value; if (!sp) return null
  return {
    position: 'fixed' as const,
    left: `${sp.x + sp.w}px`,
    top: `${sp.y}px`,
    width: `${Math.max(0, vw.value - (sp.x + sp.w))}px`,
    height: `${sp.h}px`,
  }
})

const ring = computed(() => {
  const sp = spotlight.value; if (!sp) return null
  return {
    position: 'fixed' as const,
    left: `${sp.x}px`,
    top: `${sp.y}px`,
    width: `${sp.w}px`,
    height: `${sp.h}px`,
  }
})

// ---- Tooltip placement -------------------------------------------------
const TOOLTIP_W = 380
const TOOLTIP_GAP = 16
const TOOLTIP_EST_H = 220

// Decide where to put the tooltip relative to the spotlight. For tall narrow
// targets (sidebars) we prefer left/right since top/bottom won't fit.
const tooltipPlacement = computed<'top' | 'bottom' | 'left' | 'right'>(() => {
  const sp = spotlight.value
  if (!sp || !vh.value || !vw.value) return 'bottom'

  const bottomSpace = vh.value - (sp.y + sp.h) - TOOLTIP_GAP
  const topSpace = sp.y - TOOLTIP_GAP
  const rightSpace = vw.value - (sp.x + sp.w) - TOOLTIP_GAP
  const leftSpace = sp.x - TOOLTIP_GAP

  const tooltipW = Math.min(TOOLTIP_W, vw.value - 32)

  // If the target spans most of the viewport vertically, prefer horizontal
  // placement — that's the only way to keep the tooltip on-screen.
  const isTall = sp.h > vh.value * 0.6
  if (isTall) {
    if (rightSpace >= tooltipW + 16) return 'right'
    if (leftSpace >= tooltipW + 16) return 'left'
  }

  if (bottomSpace >= TOOLTIP_EST_H) return 'bottom'
  if (topSpace >= TOOLTIP_EST_H) return 'top'
  if (rightSpace >= tooltipW + 16) return 'right'
  if (leftSpace >= tooltipW + 16) return 'left'

  // Last resort — pick whichever vertical side has more room.
  return bottomSpace >= topSpace ? 'bottom' : 'top'
})

const tooltipStyle = computed(() => {
  const sp = spotlight.value
  if (!sp || !vw.value || !vh.value) return null
  const w = Math.min(TOOLTIP_W, vw.value - 32)
  const placement = tooltipPlacement.value

  // Horizontal placements: tooltip vertically centered on the target,
  // clamped so it stays inside the viewport.
  if (placement === 'left' || placement === 'right') {
    const halfH = TOOLTIP_EST_H / 2
    const cy = sp.y + sp.h / 2
    const top = Math.max(16 + halfH, Math.min(cy, vh.value - 16 - halfH))
    if (placement === 'left') {
      return {
        position: 'fixed' as const,
        left: `${sp.x - TOOLTIP_GAP}px`,
        top: `${top}px`,
        width: `${w}px`,
        transform: 'translate(-100%, -50%)',
      }
    }
    return {
      position: 'fixed' as const,
      left: `${sp.x + sp.w + TOOLTIP_GAP}px`,
      top: `${top}px`,
      width: `${w}px`,
      transform: 'translate(0, -50%)',
    }
  }

  // Vertical placements: tooltip horizontally centered on the target.
  const left = Math.max(16, Math.min(sp.cx - w / 2, vw.value - w - 16))
  const base = {
    position: 'fixed' as const,
    left: `${left}px`,
    width: `${w}px`,
  }
  if (placement === 'top') {
    return {
      ...base,
      top: `${sp.y - TOOLTIP_GAP}px`,
      transform: 'translateY(-100%)',
    }
  }
  return {
    ...base,
    top: `${sp.y + sp.h + TOOLTIP_GAP}px`,
    transform: 'translateY(0)',
  }
})

const greeting = computed(() => props.clientName?.trim() || 'there')
</script>

<template>
  <Transition name="tour-fade">
    <div
      v-if="open"
      class="fixed inset-0 z-[120]"
      role="dialog"
      aria-modal="true"
      aria-label="Welcome tour"
    >
      <!-- ====== Centered welcome / done ====== -->
      <template v-if="isCentered && currentStep">
        <div class="absolute inset-0 bg-zinc-950/75 backdrop-blur-xl" @click="close" />
        <div class="orbs absolute inset-0 overflow-hidden pointer-events-none">
          <div class="orb orb-1" />
          <div class="orb orb-2" />
          <div class="orb orb-3" />
        </div>

        <div class="absolute inset-0 flex items-center justify-center px-4">
          <Transition name="tour-card" mode="out-in">
            <div
              :key="stepIdx"
              class="relative w-full max-w-md overflow-hidden rounded-3xl border border-white/10 bg-zinc-950/85 p-7 shadow-[0_30px_120px_-20px_rgba(0,0,0,0.7)] backdrop-blur-2xl sm:p-9"
            >
              <button
                type="button"
                class="absolute right-3 top-3 grid h-9 w-9 place-items-center rounded-full text-zinc-400 transition hover:bg-white/10 hover:text-white"
                aria-label="Skip"
                @click="close"
              >
                <X class="h-4 w-4" />
              </button>

              <div class="text-center">
                <div class="mx-auto inline-flex items-center gap-1.5 rounded-full bg-white/[0.04] px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-zinc-300 ring-1 ring-white/10">
                  <component :is="currentStep.icon" class="h-3 w-3" :style="{ color: 'var(--p2-accent)' }" />
                  {{ currentStep.eyebrow }}
                </div>

                <!-- Welcome step (first centered) -->
                <template v-if="stepIdx === 0">
                  <h2 class="mt-7 text-balance text-4xl font-semibold leading-tight tracking-tight text-white sm:text-5xl">
                    Hello,
                    <span
                      class="bg-clip-text text-transparent"
                      :style="{
                        backgroundImage:
                          'linear-gradient(120deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)',
                      }"
                    >{{ greeting }}</span>.
                  </h2>
                  <p class="mx-auto mt-4 max-w-md text-balance text-sm leading-relaxed text-zinc-300 sm:text-base">
                    Welcome to your review for
                    <span class="font-medium text-white">"{{ previewName }}"</span>.
                    {{ currentStep.body }}
                  </p>
                </template>

                <!-- Done step (last centered) -->
                <template v-else>
                  <div class="relative mx-auto mt-5 grid h-16 w-16 place-items-center">
                    <span
                      class="absolute h-16 w-16 animate-ping rounded-full opacity-25"
                      :style="{ background: 'var(--p2-accent-2)' }"
                    />
                    <span
                      class="relative grid h-12 w-12 place-items-center rounded-full text-white"
                      :style="{
                        background:
                          'linear-gradient(135deg, var(--p2-accent-2), var(--p2-accent))',
                        boxShadow:
                          '0 18px 50px -10px color-mix(in srgb, var(--p2-accent-2) 55%, transparent)',
                      }"
                    >
                      <Sparkles class="h-6 w-6" />
                    </span>
                  </div>
                  <h2 class="mt-5 text-balance text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                    {{ currentStep.title }}
                  </h2>
                  <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-zinc-400">
                    {{ currentStep.body }}
                  </p>
                </template>
              </div>

              <div class="mt-7 flex items-center justify-between gap-3">
                <div class="flex items-center gap-1.5">
                  <span
                    v-for="i in totalSteps"
                    :key="i"
                    class="block h-1.5 rounded-full transition-all duration-300"
                    :class="i - 1 === stepIdx ? 'w-7' : 'w-1.5'"
                    :style="{
                      background: i - 1 === stepIdx
                        ? 'var(--p2-accent)'
                        : 'rgb(82 82 91 / 0.5)',
                    }"
                  />
                </div>
                <div class="flex items-center gap-2">
                  <button
                    v-if="stepIdx > 0"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-xs font-medium text-zinc-400 transition hover:text-white"
                    @click="prev"
                  >
                    <ArrowLeft class="h-3.5 w-3.5" /> Back
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-95"
                    :style="{
                      background:
                        'linear-gradient(135deg, var(--p2-accent), var(--p2-accent-2))',
                      boxShadow:
                        '0 12px 36px -12px color-mix(in srgb, var(--p2-accent) 60%, transparent)',
                    }"
                    @click="next"
                  >
                    {{ stepIdx === 0 ? 'Take the tour' : 'Got it' }}
                    <ArrowRight class="h-3.5 w-3.5" />
                  </button>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </template>

      <!-- ====== Spotlight (per-target) ====== -->
      <template v-else-if="spotlight && currentStep">
        <!-- 4-piece dim/blur mask with a transparent cutout -->
        <div class="mask-piece" :style="maskTop" @click="close" />
        <div class="mask-piece" :style="maskBottom" @click="close" />
        <div class="mask-piece" :style="maskLeft" @click="close" />
        <div class="mask-piece" :style="maskRight" @click="close" />

        <!-- Glowing ring around the spotlight -->
        <div class="ring-effect" :style="ring" />

        <!-- Tooltip -->
        <div
          class="tooltip-card"
          :class="`tooltip-${tooltipPlacement}`"
          :style="tooltipStyle"
        >
          <button
            type="button"
            class="absolute right-2.5 top-2.5 grid h-7 w-7 place-items-center rounded-full text-zinc-400 transition hover:bg-white/10 hover:text-white"
            aria-label="Skip tour"
            @click="close"
          >
            <X class="h-3.5 w-3.5" />
          </button>

          <Transition name="tour-content" mode="out-in">
            <div :key="stepIdx" class="pr-7">
              <div class="inline-flex items-center gap-1.5 rounded-full bg-white/[0.05] px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-zinc-300 ring-1 ring-white/10">
                <component :is="currentStep.icon" class="h-3 w-3" :style="{ color: 'var(--p2-accent)' }" />
                {{ currentStep.eyebrow }}
              </div>
              <h3 class="mt-2.5 text-balance text-lg font-semibold tracking-tight text-white">
                {{ currentStep.title }}
              </h3>
              <p class="mt-1.5 text-sm leading-relaxed text-zinc-300">
                {{ currentStep.body }}
              </p>
            </div>
          </Transition>

          <div class="mt-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-1.5">
              <span
                v-for="i in totalSteps"
                :key="i"
                class="block h-1 rounded-full transition-all duration-300"
                :class="i - 1 === stepIdx ? 'w-5' : 'w-1'"
                :style="{
                  background: i - 1 === stepIdx
                    ? 'var(--p2-accent)'
                    : 'rgb(82 82 91 / 0.5)',
                }"
              />
            </div>
            <div class="flex items-center gap-1.5">
              <button
                v-if="stepIdx > 0"
                type="button"
                class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-zinc-400 transition hover:text-white"
                @click="prev"
              >
                <ArrowLeft class="h-3 w-3" /> Back
              </button>
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition hover:opacity-95"
                :style="{
                  background:
                    'linear-gradient(135deg, var(--p2-accent), var(--p2-accent-2))',
                }"
                @click="next"
              >
                {{ stepIdx === totalSteps - 1 ? 'Done' : 'Next' }}
                <ArrowRight class="h-3 w-3" />
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>
  </Transition>
</template>

<style scoped>
/* Outer fade */
.tour-fade-enter-active, .tour-fade-leave-active {
  transition: opacity 0.32s cubic-bezier(0.4, 0, 0.2, 1);
}
.tour-fade-enter-from, .tour-fade-leave-to {
  opacity: 0;
}

/* Centered card transition */
.tour-card-enter-active {
  transition: all 0.45s cubic-bezier(0.32, 0.72, 0, 1);
}
.tour-card-leave-active {
  transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}
.tour-card-enter-from { opacity: 0; transform: translateY(14px) scale(0.97); }
.tour-card-leave-to   { opacity: 0; transform: translateY(-10px) scale(0.98); }

/* Tooltip content fade between steps (position itself is animated via CSS
   transitions on top/left/transform). */
.tour-content-enter-active, .tour-content-leave-active {
  transition: opacity 0.18s ease;
}
.tour-content-enter-from, .tour-content-leave-to {
  opacity: 0;
}

/* Mask pieces — animate top/left/width/height so the cutout slides between
   targets. */
.mask-piece {
  background: rgba(9, 9, 11, 0.72);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  transition:
    top 0.36s cubic-bezier(0.32, 0.72, 0, 1),
    left 0.36s cubic-bezier(0.32, 0.72, 0, 1),
    width 0.36s cubic-bezier(0.32, 0.72, 0, 1),
    height 0.36s cubic-bezier(0.32, 0.72, 0, 1);
  pointer-events: auto;
  cursor: pointer;
}

/* Spotlight outline */
.ring-effect {
  border-radius: 14px;
  border: 2px solid var(--p2-accent);
  box-shadow:
    0 0 0 4px color-mix(in srgb, var(--p2-accent) 22%, transparent),
    0 18px 50px -10px color-mix(in srgb, var(--p2-accent) 55%, transparent);
  pointer-events: none;
  transition:
    top 0.36s cubic-bezier(0.32, 0.72, 0, 1),
    left 0.36s cubic-bezier(0.32, 0.72, 0, 1),
    width 0.36s cubic-bezier(0.32, 0.72, 0, 1),
    height 0.36s cubic-bezier(0.32, 0.72, 0, 1);
  animation: ring-pulse 2.4s ease-in-out infinite;
}
@keyframes ring-pulse {
  0%, 100% { box-shadow:
    0 0 0 4px color-mix(in srgb, var(--p2-accent) 22%, transparent),
    0 18px 50px -10px color-mix(in srgb, var(--p2-accent) 55%, transparent); }
  50%      { box-shadow:
    0 0 0 8px color-mix(in srgb, var(--p2-accent) 12%, transparent),
    0 18px 50px -10px color-mix(in srgb, var(--p2-accent) 55%, transparent); }
}

/* Tooltip card */
.tooltip-card {
  background: rgba(9, 9, 11, 0.92);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 18px;
  padding: 18px 20px;
  box-shadow: 0 30px 80px -20px rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  pointer-events: auto;
  transition:
    top 0.36s cubic-bezier(0.32, 0.72, 0, 1),
    left 0.36s cubic-bezier(0.32, 0.72, 0, 1),
    width 0.36s cubic-bezier(0.32, 0.72, 0, 1),
    transform 0.36s cubic-bezier(0.32, 0.72, 0, 1);
}

/* Drifting accent orbs (centered mode only) */
.orbs { pointer-events: none; }
.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  opacity: 0.5;
  animation: drift 14s ease-in-out infinite;
}
.orb-1 {
  width: 32rem; height: 32rem;
  top: -10rem; left: -10rem;
  background: var(--p2-accent);
}
.orb-2 {
  width: 26rem; height: 26rem;
  bottom: -8rem; right: -6rem;
  background: var(--p2-accent-2);
  animation-delay: -3s;
  animation-duration: 18s;
}
.orb-3 {
  width: 18rem; height: 18rem;
  top: 30%; right: 18%;
  background: var(--p2-accent);
  opacity: 0.28;
  animation-delay: -7s;
  animation-duration: 22s;
}
@keyframes drift {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33%      { transform: translate(-8%, 12%) scale(1.08); }
  66%      { transform: translate(10%, -6%) scale(0.95); }
}

@media (prefers-reduced-motion: reduce) {
  .orb { animation: none; }
  .ring-effect { animation: none; }
  .mask-piece, .ring-effect, .tooltip-card { transition: none; }
}
</style>
