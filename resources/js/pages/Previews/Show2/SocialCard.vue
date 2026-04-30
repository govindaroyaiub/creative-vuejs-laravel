<script setup lang="ts">
import { ref, computed, inject, watch, nextTick, onUnmounted } from 'vue'
import { Download, X, ZoomIn, ZoomOut, Loader2 } from 'lucide-vue-next'

defineProps<{
  social: any
}>()

const isPlanetNine = inject<boolean>('isPlanetNine', false)

const stageEl = ref<HTMLElement | null>(null)

const isOpen = ref(false)
const isZoomed = ref(false)
const isImgLoaded = ref(false)
const naturalW = ref(0)
const naturalH = ref(0)

const panX = ref(0)
const panY = ref(0)
const isDragging = ref(false)
let dragStart: { x: number; y: number; px: number; py: number } | null = null

const open = () => {
  isOpen.value = true
  isZoomed.value = false
  isImgLoaded.value = false
  panX.value = 0
  panY.value = 0
  document.body.style.overflow = 'hidden'
}

const close = () => {
  isOpen.value = false
  isZoomed.value = false
  panX.value = 0
  panY.value = 0
  document.body.style.overflow = ''
}

// Clamp the pan offsets so the image stays roughly within view.
// We allow a little overscroll so corners are reachable comfortably.
const OVERSCROLL = 60
const clampPan = () => {
  const stage = stageEl.value
  if (!stage || !naturalW.value || !naturalH.value) return
  const sw = stage.clientWidth
  const sh = stage.clientHeight
  const maxX = Math.max(0, (naturalW.value - sw) / 2 + OVERSCROLL)
  const maxY = Math.max(0, (naturalH.value - sh) / 2 + OVERSCROLL)
  panX.value = Math.max(-maxX, Math.min(maxX, panX.value))
  panY.value = Math.max(-maxY, Math.min(maxY, panY.value))
}

const toggleZoom = (e?: Event) => {
  e?.stopPropagation()
  isZoomed.value = !isZoomed.value
  if (!isZoomed.value) {
    panX.value = 0
    panY.value = 0
  } else {
    nextTick(clampPan)
  }
}

const onPointerDown = (e: PointerEvent) => {
  if (!isZoomed.value) return
  // left mouse / primary touch only
  if (e.button !== undefined && e.button !== 0) return
  isDragging.value = true
  dragStart = { x: e.clientX, y: e.clientY, px: panX.value, py: panY.value }
  ;(e.currentTarget as HTMLElement).setPointerCapture?.(e.pointerId)
  e.preventDefault()
}

const onPointerMove = (e: PointerEvent) => {
  if (!isDragging.value || !dragStart) return
  panX.value = dragStart.px + (e.clientX - dragStart.x)
  panY.value = dragStart.py + (e.clientY - dragStart.y)
  clampPan()
}

const onPointerUp = (e: PointerEvent) => {
  if (!isDragging.value) return
  isDragging.value = false
  dragStart = null
  ;(e.currentTarget as HTMLElement).releasePointerCapture?.(e.pointerId)
}

// Click on the image: only toggle zoom if the user did not just drag.
let pointerDownAt: { x: number; y: number } | null = null
const onImgPointerDown = (e: PointerEvent) => {
  pointerDownAt = { x: e.clientX, y: e.clientY }
  onPointerDown(e)
}
const onImgClick = (e: MouseEvent) => {
  e.stopPropagation()
  if (pointerDownAt) {
    const dx = e.clientX - pointerDownAt.x
    const dy = e.clientY - pointerDownAt.y
    pointerDownAt = null
    if (Math.hypot(dx, dy) > 4) return // it was a drag, not a click
  }
  toggleZoom()
}

const onImgLoad = (e: Event) => {
  const img = e.target as HTMLImageElement
  naturalW.value = img.naturalWidth
  naturalH.value = img.naturalHeight
  isImgLoaded.value = true
}

const imgStyle = computed(() => {
  if (isZoomed.value) {
    return {
      maxHeight: 'none',
      maxWidth: 'none',
      transform: `translate(${panX.value}px, ${panY.value}px)`,
      transition: isDragging.value ? 'none' : 'transform 0.28s cubic-bezier(0.32, 0.72, 0, 1)',
    }
  }
  return {
    maxHeight: 'calc(100vh - 140px)',
    maxWidth: 'calc(100vw - 48px)',
    transform: 'translate(0px, 0px)',
    transition:
      'max-height 0.28s cubic-bezier(0.32, 0.72, 0, 1), max-width 0.28s cubic-bezier(0.32, 0.72, 0, 1), transform 0.28s cubic-bezier(0.32, 0.72, 0, 1)',
  }
})

const onKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') close()
  else if (e.key === ' ' || e.key === 'Enter') {
    e.preventDefault()
    toggleZoom()
  }
}

const onResize = () => {
  if (isZoomed.value) clampPan()
}

watch(isOpen, (v) => {
  if (v) {
    document.addEventListener('keydown', onKeydown)
    window.addEventListener('resize', onResize)
  } else {
    document.removeEventListener('keydown', onKeydown)
    window.removeEventListener('resize', onResize)
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown)
  window.removeEventListener('resize', onResize)
  if (isOpen.value) document.body.style.overflow = ''
})
</script>

<template>
  <figure class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white transition hover:border-zinc-300 hover:shadow-lg hover:shadow-zinc-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
    <button
      type="button"
      class="block w-full"
      :aria-label="`Open ${social.name}`"
      @click="open"
    >
      <img
        :src="`/${social.path}`"
        :alt="social.name"
        class="block w-full object-cover transition-transform duration-500 group-hover:scale-[1.02]"
        loading="lazy"
      />

      <!-- Hover scrim -->
      <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-zinc-900/60 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />

      <!-- Hover label -->
      <div class="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 px-4 py-3 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
        <span class="truncate text-sm font-medium text-white">{{ social.name }}</span>
        <ZoomIn class="h-4 w-4 shrink-0 text-white/90" />
      </div>
    </button>

    <a
      v-if="isPlanetNine"
      :href="`/${social.path}`"
      :download="`${social.name}.jpg`"
      class="absolute right-3 top-3 grid h-8 w-8 place-items-center rounded-md bg-white/90 text-zinc-700 opacity-0 shadow-sm ring-1 ring-zinc-200 backdrop-blur transition hover:bg-white hover:text-[var(--p2-accent)] dark:bg-zinc-900/90 dark:text-zinc-300 dark:ring-zinc-700 group-hover:opacity-100"
      aria-label="Download"
      @click.stop
    >
      <Download class="h-3.5 w-3.5" />
    </a>
  </figure>

  <!-- Lightbox -->
  <Teleport to="body">
    <Transition name="lb-fade">
      <div
        v-if="isOpen"
        class="lightbox-root fixed inset-0 z-[100] flex flex-col bg-zinc-950/95 backdrop-blur-md"
        role="dialog"
        aria-modal="true"
        @click.self="close"
      >
        <!-- Top bar -->
        <div class="flex items-center justify-between gap-3 px-5 py-4">
          <div class="min-w-0 flex-1">
            <div class="truncate text-sm font-medium text-white">{{ social.name }}</div>
            <div v-if="naturalW && naturalH" class="mt-0.5 font-mono text-[11px] tabular-nums text-white/50">
              {{ naturalW }}<span class="text-white/30"> × </span>{{ naturalH }}
            </div>
          </div>
          <div class="flex items-center gap-1.5">
            <button
              type="button"
              class="grid h-9 w-9 place-items-center rounded-lg border border-white/10 bg-white/5 text-white/80 transition hover:bg-white/15 hover:text-white"
              :aria-label="isZoomed ? 'Zoom out' : 'Zoom in'"
              :title="isZoomed ? 'Zoom out (Space)' : 'Zoom in (Space)'"
              @click.stop="toggleZoom"
            >
              <ZoomOut v-if="isZoomed" class="h-4 w-4" />
              <ZoomIn v-else class="h-4 w-4" />
            </button>
            <a
              v-if="isPlanetNine"
              :href="`/${social.path}`"
              :download="`${social.name}.jpg`"
              class="grid h-9 w-9 place-items-center rounded-lg border border-white/10 bg-white/5 text-white/80 transition hover:bg-white/15 hover:text-white"
              aria-label="Download"
              title="Download"
              @click.stop
            >
              <Download class="h-4 w-4" />
            </a>
            <button
              type="button"
              class="grid h-9 w-9 place-items-center rounded-lg border border-white/10 bg-white/5 text-white/80 transition hover:bg-white/15 hover:text-white"
              aria-label="Close"
              title="Close (Esc)"
              @click="close"
            >
              <X class="h-5 w-5" />
            </button>
          </div>
        </div>

        <!-- Image stage -->
        <div
          ref="stageEl"
          class="relative flex flex-1 items-center justify-center overflow-hidden px-6 pb-6"
          @click.self="close"
        >
          <!-- Loading spinner -->
          <div
            v-if="!isImgLoaded"
            class="pointer-events-none absolute inset-0 flex items-center justify-center"
          >
            <Loader2
              class="h-6 w-6 animate-spin"
              :style="{ color: 'var(--p2-accent)' }"
            />
          </div>

          <Transition name="lb-zoom" appear>
            <img
              v-show="isImgLoaded"
              :src="`/${social.path}`"
              :alt="social.name"
              :class="[
                'select-none touch-none',
                isZoomed
                  ? isDragging
                    ? 'cursor-grabbing'
                    : 'cursor-grab'
                  : 'cursor-zoom-in',
              ]"
              :style="imgStyle"
              draggable="false"
              @pointerdown="onImgPointerDown"
              @pointermove="onPointerMove"
              @pointerup="onPointerUp"
              @pointercancel="onPointerUp"
              @click="onImgClick"
              @load="onImgLoad"
              @dragstart.prevent
            />
          </Transition>
        </div>

        <!-- Footer hint -->
        <div class="pointer-events-none flex justify-center pb-4">
          <div class="rounded-full bg-white/5 px-3 py-1 text-[11px] font-medium text-white/50">
            <template v-if="isZoomed">
              <span>Drag to pan</span>
              <span class="mx-2 text-white/20">·</span>
              <span>Click to zoom out</span>
              <span class="mx-2 text-white/20">·</span>
              <span>Esc to close</span>
            </template>
            <template v-else>
              <span>Click image to zoom</span>
              <span class="mx-2 text-white/20">·</span>
              <span>Esc to close</span>
            </template>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.lb-fade-enter-active,
.lb-fade-leave-active {
  transition: opacity 0.22s ease;
}
.lb-fade-enter-from,
.lb-fade-leave-to {
  opacity: 0;
}
.lb-zoom-enter-active {
  transition: all 0.32s cubic-bezier(0.32, 0.72, 0, 1);
}
.lb-zoom-enter-from {
  opacity: 0;
  transform: scale(0.94);
}
</style>
