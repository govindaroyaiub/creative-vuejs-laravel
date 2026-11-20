<script setup lang="ts">
/**
 * DEMO ONLY: one video, shown clean or in the placement its size is made for.
 * The companion banner sits in the frame where one exists (laptop, article)
 * and under the card otherwise.
 */
import { computed, inject } from 'vue'
import { Download, Film, Aperture, Activity, FileVideo, Ratio } from 'lucide-vue-next'
import VideoPlayer from './VideoPlayer.vue'
import PhoneStoryFrame from './PhoneStoryFrame.vue'
import PhoneFeedFrame from './PhoneFeedFrame.vue'
import LaptopFrame from './LaptopFrame.vue'
import TvFrame from './TvFrame.vue'
import ArticleFrame from './ArticleFrame.vue'
import { FORMAT_LABEL, formatOf, isNarrowVideo, ratioLabel, sizeOfVideo, type ShowcaseState } from './useVideoFormat'

const props = defineProps<{ video: any }>()
const state = inject<ShowcaseState>('videoShowcaseState')!
const isPlanetNine = inject<boolean>('isPlanetNine', false)

const format = computed(() => formatOf(props.video))
const s = computed(() => sizeOfVideo(props.video))
const inContext = computed(() => state.mode !== 'clean' && format.value !== 'plain')
const safe = computed(() => state.mode === 'safe')

const frame = computed(() => {
  if (!inContext.value) return null
  switch (format.value) {
    case 'story':
      return PhoneStoryFrame
    case 'feed':
      return PhoneFeedFrame
    case 'article':
      return ArticleFrame
    case 'plain':
      return null
    default:
      return state.screen === 'tv' ? TvFrame : LaptopFrame
  }
})

const companionInFrame = computed(() => inContext.value && ((format.value === 'screen' && state.screen === 'laptop') || format.value === 'article'))

const isNarrow = computed(() => isNarrowVideo(props.video))

/** Clean view: real ratio, capped at 60vh like Show2's player. */
const cleanStyle = computed(() => ({
  aspectRatio: `${s.value.w} / ${s.value.h}`,
  width: `min(100%, calc(60vh * ${s.value.w} / ${s.value.h}))`,
}))

const meta = computed(() =>
  [
    { icon: Film, label: 'Resolution', value: `${s.value.w}×${s.value.h}` },
    { icon: Ratio, label: 'Aspect', value: props.video?.aspect_ratio || ratioLabel(props.video) },
    { icon: Aperture, label: 'Codec', value: props.video?.codec },
    { icon: Activity, label: 'FPS', value: props.video?.fps },
    { icon: FileVideo, label: 'Size', value: props.video?.file_size },
  ].filter((m) => m.value),
)
</script>

<template>
  <div
    class="flex max-w-full flex-col overflow-hidden rounded-3xl border bg-[var(--p2-surface)]"
    :class="isNarrow ? 'w-fit' : 'w-full'"
    :style="{ borderColor: 'var(--p2-border)' }"
  >
    <!-- header -->
    <div class="flex items-center gap-2 border-b px-4 py-2.5" :style="{ borderColor: 'var(--p2-hairline)' }">
      <span class="p2-mono rounded-md px-2 py-0.5 text-xs font-semibold" :style="{ background: 'var(--p2-accent-soft)', color: 'var(--p2-text)' }">
        {{ s.w }}×{{ s.h }}
      </span>
      <span class="truncate text-xs font-medium text-[var(--p2-text-muted)]">{{ FORMAT_LABEL[format] }} · {{ ratioLabel(video) }}</span>
    </div>

    <!-- stage -->
    <div
      class="flex justify-center"
      :class="inContext ? 'stage-bg px-5 py-7 sm:px-8' : 'bg-zinc-950'"
    >
      <component :is="frame" v-if="frame" :video="video" :safe="safe" />
      <div v-else class="relative" :style="cleanStyle">
        <VideoPlayer :src="`/${video.path}`" fit="contain" native />
      </div>
    </div>

    <!-- meta -->
    <div class="flex flex-wrap items-center gap-2 border-t px-4 py-3" :style="{ borderColor: 'var(--p2-hairline)' }">
      <span
        v-for="m in meta"
        :key="m.label"
        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-medium text-[var(--p2-text-muted)]"
        :style="{ background: 'var(--p2-surface-muted)', borderColor: 'var(--p2-border)' }"
      >
        <component :is="m.icon" class="h-3 w-3 text-[var(--p2-text-subtle)]" />
        <span class="p2-label text-[10px] tracking-[0.14em]">{{ m.label }}</span>
        <span class="p2-mono tabular-nums text-[var(--p2-text)]">{{ m.value }}</span>
      </span>
      <a
        v-if="isPlanetNine"
        :href="`/${video.path}`"
        download
        class="ml-auto inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-[11px] font-medium text-[var(--p2-text-muted)] transition-colors hover:text-[var(--p2-accent)]"
        :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface-muted)' }"
      >
        <Download class="h-3 w-3" /> Download
      </a>
    </div>

    <!-- companion (when the frame has no place for it) -->
    <div
      v-if="video.companion_banner_path && !companionInFrame"
      class="border-t px-4 py-4"
      :style="{ borderColor: 'var(--p2-hairline)', background: 'var(--p2-surface-muted)' }"
    >
      <p class="p2-label mb-2" :style="{ color: 'var(--p2-accent)' }">Companion banner</p>
      <img :src="`/${video.companion_banner_path}`" alt="Companion banner" class="mx-auto max-h-[280px] w-auto max-w-full rounded-2xl border" :style="{ borderColor: 'var(--p2-border)' }" />
    </div>
  </div>
</template>

<style scoped>
.stage-bg {
  background:
    radial-gradient(120% 80% at 50% 0%, var(--p2-accent-soft), transparent 60%),
    var(--p2-surface-muted);
}
</style>
