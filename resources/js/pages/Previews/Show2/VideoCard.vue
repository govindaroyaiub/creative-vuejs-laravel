<script setup lang="ts">
import { computed, inject, ref } from 'vue'
import { Download, Film, Aperture, Activity, FileVideo, Ratio } from 'lucide-vue-next'

const props = defineProps<{
  video: any
}>()

const isPlanetNine = inject<boolean>('isPlanetNine', false)

const resolution = computed(() => {
  const w = props.video?.size?.width
  const h = props.video?.size?.height
  return w && h ? `${w}×${h}` : null
})

const isReady = ref(false)

// Reserve the player's box before the file arrives: real aspect ratio, capped
// at 60vh tall like the player itself. Without a size we fall back to 16:9.
const stageStyle = computed(() => {
  const w = Number(props.video?.size?.width) || 16
  const h = Number(props.video?.size?.height) || 9
  return {
    aspectRatio: `${w} / ${h}`,
    width: `min(100%, calc(60vh * ${w} / ${h}))`,
  }
})

const meta = computed(() =>
  [
    { icon: Film, label: 'Resolution', value: resolution.value },
    { icon: Ratio, label: 'Aspect', value: props.video?.aspect_ratio },
    { icon: Aperture, label: 'Codec', value: props.video?.codec },
    { icon: Activity, label: 'FPS', value: props.video?.fps },
    { icon: FileVideo, label: 'Size', value: props.video?.file_size },
  ].filter((m) => m.value)
)
</script>

<template>
  <div
    class="overflow-hidden rounded-3xl border bg-[var(--p2-surface)] transition-all duration-300 ease-p2-expo hover:-translate-y-0.5"
    :style="{ borderColor: 'var(--p2-border)' }"
  >
    <!-- Player -->
    <div class="bg-zinc-950">
      <div class="relative mx-auto" :style="stageStyle">
        <video
          :src="`/${video.path}`"
          controls
          muted
          preload="metadata"
          controlsList="nodownload noremoteplayback"
          disablePictureInPicture
          class="block h-full w-full"
          @loadeddata="isReady = true"
          @error="isReady = true"
        />
        <div v-if="!isReady" class="p2-shimmer pointer-events-none absolute inset-0" />
      </div>
    </div>

    <!-- Meta strip -->
    <div
      class="flex flex-wrap items-center gap-2 border-t px-4 py-3"
      :style="{ borderColor: 'var(--p2-hairline)' }"
    >
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
        class="ml-auto inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-[11px] font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-accent)]"
        :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface-muted)' }"
      >
        <Download class="h-3 w-3" />
        Download
      </a>
    </div>

    <!-- Companion banner -->
    <div
      v-if="video.companion_banner_path"
      class="border-t px-4 py-4"
      :style="{ borderColor: 'var(--p2-hairline)', background: 'var(--p2-surface-muted)' }"
    >
      <p class="p2-label mb-2" :style="{ color: 'var(--p2-accent)' }">Companion banner</p>
      <div class="flex flex-col items-center gap-3">
        <img
          :src="`/${video.companion_banner_path}`"
          alt="Companion banner"
          class="max-h-[280px] w-auto max-w-full rounded-2xl border bg-[var(--p2-surface)]"
          :style="{ borderColor: 'var(--p2-border)' }"
        />
        <a
          v-if="isPlanetNine"
          :href="`/${video.companion_banner_path}`"
          download
          class="inline-flex items-center gap-1.5 text-xs font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-accent)]"
        >
          <Download class="h-3 w-3" />
          Download companion
        </a>
      </div>
    </div>
  </div>
</template>
