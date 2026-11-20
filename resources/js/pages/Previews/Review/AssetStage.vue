<script setup lang="ts">
/**
 * DEMO ONLY: one creative, as large as the box allows (never upscaled).
 * Banners and GIFs keep their exact pixel size and are scaled down with a
 * transform when the box is smaller, so the creative renders exactly as built.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { sizeOf } from './useReviewDemo'

const props = defineProps<{
  asset: any
  type: string
  /** Bump to restart banners / GIFs (iframes reload). */
  replayKey?: number
}>()

const box = ref<HTMLElement | null>(null)
const videoEl = ref<HTMLVideoElement | null>(null)
const boxW = ref(0)
const boxH = ref(0)
const loaded = ref(false)

let ro: ResizeObserver | null = null
onMounted(() => {
  if (!box.value) return
  ro = new ResizeObserver(([e]) => {
    if (!e) return
    boxW.value = e.contentRect.width
    boxH.value = e.contentRect.height
  })
  ro.observe(box.value)
})
onBeforeUnmount(() => ro?.disconnect())

const natural = computed(() => sizeOf(props.asset) ?? (props.type === 'video' ? { w: 1920, h: 1080 } : { w: 1080, h: 1080 }))

const scale = computed(() => {
  if (!boxW.value || !boxH.value) return 1
  return Math.min(1, boxW.value / natural.value.w, boxH.value / natural.value.h)
})

const outerStyle = computed(() => ({
  width: `${natural.value.w * scale.value}px`,
  height: `${natural.value.h * scale.value}px`,
}))
const innerStyle = computed(() => ({
  width: `${natural.value.w}px`,
  height: `${natural.value.h}px`,
  transform: `scale(${scale.value})`,
  transformOrigin: 'top left',
}))

const src = computed(() =>
  props.type === 'banner' ? `/${props.asset.path}/index.html`.replace(/\/+index\.html$/, '/index.html') : `/${props.asset.path}`,
)

defineExpose({ videoEl })
</script>

<template>
  <div ref="box" class="flex h-full w-full items-center justify-center overflow-hidden">
    <div class="relative shrink-0 overflow-hidden" :style="outerStyle">
      <div :style="innerStyle">
        <iframe
          v-if="type === 'banner' || type === 'gif'"
          :key="`${asset.id}-${replayKey ?? 0}`"
          :src="src"
          :width="natural.w"
          :height="natural.h"
          frameborder="0"
          scrolling="no"
          sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox"
          referrerpolicy="no-referrer"
          class="block border-0"
          @load="loaded = true"
        />
        <video
          v-else-if="type === 'video'"
          ref="videoEl"
          :src="src"
          controls
          muted
          preload="metadata"
          controlsList="nodownload noremoteplayback"
          disablePictureInPicture
          class="block h-full w-full bg-black"
          @loadeddata="loaded = true"
        />
        <img
          v-else
          :src="src"
          :alt="asset.name"
          class="block h-full w-full object-contain"
          draggable="false"
          @load="loaded = true"
        />
      </div>
      <div v-if="!loaded" class="p2-shimmer pointer-events-none absolute inset-0" />
    </div>
  </div>
</template>
