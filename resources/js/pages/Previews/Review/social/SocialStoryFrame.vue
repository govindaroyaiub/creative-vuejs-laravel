<script setup lang="ts">
/** A story ad: full-screen image with generic story UI and a swipe-up CTA. */
import { computed, inject } from 'vue'
import { MoreHorizontal, X, ChevronUp } from 'lucide-vue-next'
import PhoneShell from '../video/PhoneShell.vue'
import StoryScreen from '../video/StoryScreen.vue'
import SafeZoneOverlay from '../video/SafeZoneOverlay.vue'
import SocialImage from './SocialImage.vue'
import { sizeOfSocial } from './useSocialShowcase'
import type { VideoBrand } from '../video/useVideoFormat'

const props = defineProps<{ image: any; safe: boolean }>()
const brand = inject<VideoBrand>('videoBrand')!
const s = computed(() => sizeOfSocial(props.image))
</script>

<template>
  <PhoneShell>
    <StoryScreen :w="s.w" :h="s.h">
      <SocialImage :src="`/${image.path}`" :alt="image.name">
        <div class="absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-black/45 to-transparent" />
        <div class="absolute inset-x-3 top-2.5 h-[2px] rounded-full bg-white/90" />
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
        <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-black/50 to-transparent" />
        <div class="absolute inset-x-0 bottom-4 flex flex-col items-center text-white">
          <ChevronUp class="h-4 w-4" />
          <span class="mt-0.5 rounded-full bg-white px-4 py-1.5 text-[11px] font-semibold text-zinc-900">Shop now</span>
        </div>
        <SafeZoneOverlay v-if="safe" format="story" />
      </SocialImage>
    </StoryScreen>
  </PhoneShell>
</template>
