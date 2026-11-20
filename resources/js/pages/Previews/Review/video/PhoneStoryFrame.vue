<script setup lang="ts">
/** DEMO ONLY: full-screen vertical video with generic story / reel UI. */
import { computed, inject } from 'vue'
import { Heart, MessageCircle, Send, MoreHorizontal, X, Music2 } from 'lucide-vue-next'
import PhoneShell from './PhoneShell.vue'
import StoryScreen from './StoryScreen.vue'
import VideoPlayer from './VideoPlayer.vue'
import SafeZoneOverlay from './SafeZoneOverlay.vue'
import { sizeOfVideo, type VideoBrand } from './useVideoFormat'

const props = defineProps<{ video: any; safe: boolean }>()
const brand = inject<VideoBrand>('videoBrand')!
const s = computed(() => sizeOfVideo(props.video))
</script>

<template>
  <PhoneShell>
    <StoryScreen :w="s.w" :h="s.h">
      <VideoPlayer :src="`/${video.path}`" ui-at="story">
        <template #default="{ progress }">
          <!-- top gradient + progress segments + account -->
          <div class="absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-black/55 to-transparent" />
          <div class="absolute inset-x-3 top-2.5 flex gap-1">
            <span class="h-[2px] flex-1 overflow-hidden rounded-full bg-white/35">
              <span class="block h-full bg-white" :style="{ width: `${progress}%` }" />
            </span>
            <span class="h-[2px] flex-1 rounded-full bg-white/35" />
            <span class="h-[2px] flex-1 rounded-full bg-white/35" />
          </div>
          <div class="absolute inset-x-3 top-5 flex items-center gap-2 text-white">
            <span class="grid h-7 w-7 shrink-0 place-items-center overflow-hidden rounded-full bg-white ring-2 ring-white/70">
              <img v-if="brand.logo" :src="brand.logo" alt="" class="h-full w-full object-contain p-0.5" />
            </span>
            <span class="min-w-0 leading-tight">
              <span class="block truncate text-[11px] font-semibold">{{ brand.handle }}</span>
              <span class="block text-[9px] opacity-80">Sponsored</span>
            </span>
            <MoreHorizontal class="ml-auto h-4 w-4" />
            <X class="h-4 w-4" />
          </div>

          <!-- right action column -->
          <div class="absolute bottom-24 right-2.5 flex flex-col items-center gap-3.5 text-white drop-shadow">
            <span class="flex flex-col items-center text-[9px] font-medium"><Heart class="h-6 w-6" />12.4k</span>
            <span class="flex flex-col items-center text-[9px] font-medium"><MessageCircle class="h-6 w-6" />318</span>
            <span class="flex flex-col items-center text-[9px] font-medium"><Send class="h-6 w-6" />Share</span>
          </div>

          <!-- caption + CTA -->
          <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-black/70 to-transparent" />
          <div class="absolute inset-x-3 bottom-4 text-white">
            <p class="pr-12 text-[11px] leading-snug">
              <span class="font-semibold">{{ brand.handle }}</span>
              {{ video.name || 'New trailer' }} · in cinemas soon
            </p>
            <p class="mt-1 flex items-center gap-1 text-[9px] opacity-85"><Music2 class="h-3 w-3" /> Original audio</p>
            <span class="mt-2 flex h-8 items-center justify-center rounded-lg bg-white text-[11px] font-semibold text-zinc-900">
              Learn more
            </span>
          </div>

          <SafeZoneOverlay v-if="safe" format="story" />
        </template>
      </VideoPlayer>
    </StoryScreen>
  </PhoneShell>
</template>
