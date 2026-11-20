<script setup lang="ts">
/**
 * DEMO ONLY: pre-roll in a generic video site on a laptop. The companion
 * banner sits in the right column, where a companion ad really runs.
 */
import { computed, inject } from 'vue'
import { ThumbsUp, Share2 } from 'lucide-vue-next'
import BrowserChrome from './BrowserChrome.vue'
import VideoPlayer from './VideoPlayer.vue'
import SafeZoneOverlay from './SafeZoneOverlay.vue'
import { sizeOfVideo, type VideoBrand } from './useVideoFormat'

const props = defineProps<{ video: any; safe: boolean }>()
const brand = inject<VideoBrand>('videoBrand')!
const s = computed(() => sizeOfVideo(props.video))
</script>

<template>
  <div class="mx-auto w-full max-w-[920px]">
    <div class="laptop-lid">
      <!-- inner radius = lid radius minus bezel, so the curves run parallel -->
      <BrowserChrome url="video.example.com/watch" radius="7px 7px 2px 2px">
        <div class="grid gap-4 p-4 sm:grid-cols-[1fr_200px]">
          <div class="min-w-0">
            <div class="relative w-full overflow-hidden rounded-lg bg-black" :style="{ aspectRatio: '16 / 9' }">
              <VideoPlayer :src="`/${video.path}`" :fit="s.w / s.h > 1.6 ? 'cover' : 'contain'">
                <template #default>
                  <span class="absolute left-2 top-2 rounded bg-amber-400 px-1.5 py-0.5 text-[9px] font-bold text-zinc-900">Ad</span>
                  <span class="absolute bottom-3 right-2 rounded bg-black/60 px-2 py-1 text-[10px] font-medium text-white">Visit site ›</span>
                  <SafeZoneOverlay v-if="safe" format="screen" />
                </template>
              </VideoPlayer>
            </div>
            <div class="mt-3 h-3 w-3/4 rounded bg-zinc-200" />
            <div class="mt-3 flex items-center gap-2">
              <span class="grid h-8 w-8 place-items-center overflow-hidden rounded-full ring-1 ring-zinc-200">
                <img v-if="brand.logo" :src="brand.logo" alt="" class="h-full w-full object-contain p-0.5" />
              </span>
              <span class="text-[11px] font-semibold">{{ brand.name }}</span>
              <span class="ml-2 rounded-full bg-zinc-900 px-3 py-1 text-[10px] font-semibold text-white">Subscribe</span>
              <span class="ml-auto flex gap-2 text-zinc-500"><ThumbsUp class="h-4 w-4" /><Share2 class="h-4 w-4" /></span>
            </div>
            <div class="mt-3 space-y-1.5 rounded-lg bg-zinc-100 p-3">
              <div class="h-2 w-full rounded bg-zinc-200" />
              <div class="h-2 w-5/6 rounded bg-zinc-200" />
            </div>
          </div>

          <aside class="hidden space-y-3 sm:block">
            <img
              v-if="video.companion_banner_path"
              :src="`/${video.companion_banner_path}`"
              alt="Companion banner"
              class="w-full rounded-md ring-1 ring-zinc-200"
            />
            <div v-for="n in 4" :key="n" class="flex gap-2">
              <span class="aspect-video w-20 shrink-0 rounded bg-zinc-200" />
              <span class="flex-1 space-y-1.5 pt-1">
                <span class="block h-2 w-full rounded bg-zinc-200" />
                <span class="block h-2 w-2/3 rounded bg-zinc-200" />
              </span>
            </div>
          </aside>
        </div>
      </BrowserChrome>
    </div>
    <div class="laptop-base" />
  </div>
</template>

<style scoped>
.laptop-lid {
  padding: 5px 5px 7px;
  border-radius: 12px 12px 4px 4px;
  background: linear-gradient(145deg, #2a2a30, #0d0d10);
  box-shadow: 0 0 0 1px rgb(255 255 255 / 0.06) inset;
}
.laptop-base {
  height: 14px;
  margin: 0 -4%;
  border-radius: 2px 2px 14px 14px;
  background: linear-gradient(#cfcfd4, #9a9aa2);
  box-shadow: 0 18px 30px -16px rgb(0 0 0 / 0.5);
}
</style>
