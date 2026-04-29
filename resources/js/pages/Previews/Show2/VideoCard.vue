<script setup lang="ts">
import { computed, inject } from 'vue'
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
  <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white transition hover:border-zinc-300 hover:shadow-lg hover:shadow-zinc-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
    <!-- Player -->
    <div class="bg-zinc-950">
      <video
        :src="`/${video.path}`"
        controls
        muted
        controlsList="nodownload noremoteplayback"
        disablePictureInPicture
        class="mx-auto block max-h-[60vh] w-auto max-w-full"
      />
    </div>

    <!-- Meta strip -->
    <div class="flex flex-wrap items-center gap-2 border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
      <span
        v-for="m in meta"
        :key="m.label"
        class="inline-flex items-center gap-1.5 rounded-full bg-zinc-50 px-2.5 py-1 text-[11px] font-medium text-zinc-600 ring-1 ring-zinc-200/70 dark:bg-zinc-800/60 dark:text-zinc-300 dark:ring-zinc-700/70"
      >
        <component :is="m.icon" class="h-3 w-3 text-zinc-400 dark:text-zinc-500" />
        <span class="text-zinc-400 dark:text-zinc-500">{{ m.label }}</span>
        <span class="font-mono tabular-nums text-zinc-800 dark:text-zinc-100">{{ m.value }}</span>
      </span>

      <a
        v-if="isPlanetNine"
        :href="`/${video.path}`"
        download
        class="ml-auto inline-flex items-center gap-1.5 rounded-md border border-zinc-200 px-2.5 py-1.5 text-[11px] font-medium text-zinc-700 transition hover:border-[var(--p2-accent)] hover:text-[var(--p2-accent)] dark:border-zinc-700 dark:text-zinc-300"
      >
        <Download class="h-3 w-3" />
        Download
      </a>
    </div>

    <!-- Companion banner -->
    <div
      v-if="video.companion_banner_path"
      class="border-t border-zinc-100 bg-zinc-50 px-4 py-4 dark:border-zinc-800 dark:bg-zinc-950/40"
    >
      <div
        class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em]"
        :style="{ color: 'var(--p2-accent)' }"
      >
        Companion banner
      </div>
      <div class="flex flex-col items-center gap-3">
        <img
          :src="`/${video.companion_banner_path}`"
          alt="Companion banner"
          class="max-h-[280px] w-auto max-w-full rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
        />
        <a
          v-if="isPlanetNine"
          :href="`/${video.companion_banner_path}`"
          download
          class="inline-flex items-center gap-1.5 text-xs font-medium text-zinc-600 transition hover:text-[var(--p2-accent)] dark:text-zinc-300"
        >
          <Download class="h-3 w-3" />
          Download companion
        </a>
      </div>
    </div>
  </div>
</template>
