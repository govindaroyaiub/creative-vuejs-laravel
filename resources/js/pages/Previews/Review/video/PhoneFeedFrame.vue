<script setup lang="ts">
/** DEMO ONLY: the video as a sponsored post in a generic social feed. */
import { computed, inject } from 'vue'
import { Heart, MessageCircle, Send, Bookmark, MoreHorizontal, ChevronRight, Home, Search, PlusSquare, User } from 'lucide-vue-next'
import PhoneShell from './PhoneShell.vue'
import VideoPlayer from './VideoPlayer.vue'
import SafeZoneOverlay from './SafeZoneOverlay.vue'
import { sizeOfVideo, type VideoBrand } from './useVideoFormat'

const props = defineProps<{ video: any; safe: boolean }>()
const brand = inject<VideoBrand>('videoBrand')!
const s = computed(() => sizeOfVideo(props.video))
</script>

<template>
  <PhoneShell :width="290" status-dark>
    <div class="absolute inset-0 flex flex-col bg-white text-zinc-900">
      <!-- app header -->
      <div class="flex shrink-0 items-end justify-between px-3.5 pb-2 pt-9">
        <span class="text-[15px] font-bold tracking-tight">Feed</span>
        <span class="flex gap-3"><Heart class="h-[18px] w-[18px]" /><Send class="h-[18px] w-[18px]" /></span>
      </div>

      <div class="min-h-0 flex-1 overflow-hidden">
        <!-- post header -->
        <div class="flex items-center gap-2 px-3 py-2">
          <span class="grid h-7 w-7 place-items-center overflow-hidden rounded-full ring-1 ring-zinc-200">
            <img v-if="brand.logo" :src="brand.logo" alt="" class="h-full w-full object-contain p-0.5" />
          </span>
          <span class="leading-tight">
            <span class="block text-[11px] font-semibold">{{ brand.handle }}</span>
            <span class="block text-[9px] text-zinc-500">Sponsored</span>
          </span>
          <MoreHorizontal class="ml-auto h-4 w-4" />
        </div>

        <!-- media -->
        <div class="relative w-full" :style="{ aspectRatio: `${s.w} / ${s.h}` }">
          <VideoPlayer :src="`/${video.path}`">
            <SafeZoneOverlay v-if="safe" format="feed" />
          </VideoPlayer>
        </div>

        <!-- CTA bar -->
        <div class="flex items-center justify-between border-b border-zinc-100 bg-zinc-50 px-3 py-2 text-[11px] font-semibold">
          Learn more <ChevronRight class="h-3.5 w-3.5" />
        </div>

        <!-- actions + caption -->
        <div class="flex items-center gap-3 px-3 pt-2">
          <Heart class="h-5 w-5" /><MessageCircle class="h-5 w-5" /><Send class="h-5 w-5" />
          <Bookmark class="ml-auto h-5 w-5" />
        </div>
        <p class="px-3 pt-1 text-[11px] font-semibold">1,284 likes</p>
        <p class="px-3 text-[11px] leading-snug">
          <span class="font-semibold">{{ brand.handle }}</span> {{ video.name || 'New trailer' }} · in cinemas soon
        </p>
        <p class="px-3 pt-0.5 text-[10px] text-zinc-400">View all 86 comments</p>
      </div>

      <!-- tab bar -->
      <div class="flex shrink-0 items-center justify-around border-t border-zinc-100 pb-4 pt-2">
        <Home class="h-5 w-5" /><Search class="h-5 w-5" /><PlusSquare class="h-5 w-5" /><User class="h-5 w-5" />
      </div>
    </div>
  </PhoneShell>
</template>
