<script setup lang="ts">
/** DEMO ONLY: connected-TV ad break. 4:3 gets pillarboxed like a real TV. */
import { computed, inject } from 'vue'
import VideoPlayer from './VideoPlayer.vue'
import SafeZoneOverlay from './SafeZoneOverlay.vue'
import { sizeOfVideo, type VideoBrand } from './useVideoFormat'

const props = defineProps<{ video: any; safe: boolean }>()
const brand = inject<VideoBrand>('videoBrand')!
const s = computed(() => sizeOfVideo(props.video))
</script>

<template>
  <div class="mx-auto w-full max-w-[860px]">
    <div class="tv">
      <div class="relative overflow-hidden rounded-[6px] bg-black" style="aspect-ratio: 16 / 9">
        <VideoPlayer :src="`/${video.path}`" :fit="s.w / s.h > 1.6 ? 'cover' : 'contain'" ui-at="top">
          <template #default="{ remaining, playing, total }">
            <span class="absolute bottom-5 left-5 rounded bg-black/60 px-2.5 py-1 text-[11px] font-medium text-white">
              Ad · {{ playing ? remaining : total }}
            </span>
            <span class="absolute bottom-5 right-5 rounded bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-zinc-900">
              {{ brand.name }}
            </span>
            <SafeZoneOverlay v-if="safe" format="screen" />
          </template>
        </VideoPlayer>
      </div>
    </div>
    <div class="tv-stand" />
  </div>
</template>

<style scoped>
.tv {
  padding: 10px;
  border-radius: 12px;
  background: #0b0b0e;
  box-shadow:
    0 0 0 1px rgb(255 255 255 / 0.07) inset,
    0 30px 60px -24px rgb(0 0 0 / 0.55);
}
.tv-stand {
  margin: 0 auto;
  height: 22px;
  width: 30%;
  background: linear-gradient(#26262c, #121216);
  clip-path: polygon(12% 0, 88% 0, 100% 100%, 0 100%);
}
</style>
