<script setup lang="ts">
/** DEMO ONLY: in-page video at its exact pixel size inside a generic article. */
import { computed } from 'vue'
import BrowserChrome from './BrowserChrome.vue'
import VideoPlayer from './VideoPlayer.vue'
import SafeZoneOverlay from './SafeZoneOverlay.vue'
import { sizeOfVideo } from './useVideoFormat'

const props = defineProps<{ video: any; safe: boolean }>()
const s = computed(() => sizeOfVideo(props.video))
</script>

<template>
  <div class="mx-auto w-full max-w-[920px]">
    <BrowserChrome url="news.example.com/culture/weekend-releases">
      <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3">
        <span class="text-lg font-extrabold tracking-tight">The Daily Example</span>
        <span class="hidden gap-4 text-[10px] font-medium uppercase tracking-wider text-zinc-500 sm:flex">
          <span>News</span><span>Culture</span><span>Film</span><span>Sport</span>
        </span>
      </div>
      <div class="grid gap-6 p-5 md:grid-cols-[1fr_300px]">
        <article class="min-w-0">
          <div class="h-5 w-11/12 rounded bg-zinc-800/85" />
          <div class="mt-2 h-5 w-2/3 rounded bg-zinc-800/85" />
          <div class="mt-3 h-2 w-40 rounded bg-zinc-200" />
          <div class="mt-5 space-y-2">
            <div v-for="n in 4" :key="n" class="h-2 rounded bg-zinc-200" :style="{ width: `${[100, 96, 99, 70][n - 1]}%` }" />
          </div>

          <!-- the ad, at its real size -->
          <div class="my-6 flex flex-col items-center">
            <span class="mb-1 text-[9px] uppercase tracking-[0.2em] text-zinc-400">Advertisement</span>
            <div class="relative max-w-full" :style="{ width: `${s.w}px`, aspectRatio: `${s.w} / ${s.h}` }">
              <VideoPlayer :src="`/${video.path}`">
                <SafeZoneOverlay v-if="safe" format="article" />
              </VideoPlayer>
            </div>
          </div>

          <div class="space-y-2">
            <div v-for="n in 5" :key="n" class="h-2 rounded bg-zinc-200" :style="{ width: `${[98, 100, 94, 100, 60][n - 1]}%` }" />
          </div>
        </article>

        <aside class="hidden space-y-4 md:block">
          <div v-if="video.companion_banner_path" class="flex flex-col items-center">
            <span class="mb-1 text-[9px] uppercase tracking-[0.2em] text-zinc-400">Advertisement</span>
            <img :src="`/${video.companion_banner_path}`" alt="Companion banner" class="w-[300px] max-w-full ring-1 ring-zinc-200" />
          </div>
          <div class="rounded-lg bg-zinc-100 p-3">
            <div class="mb-2 h-2.5 w-24 rounded bg-zinc-300" />
            <div v-for="n in 4" :key="n" class="mt-2 h-2 rounded bg-zinc-200" />
          </div>
        </aside>
      </div>
    </BrowserChrome>
  </div>
</template>
