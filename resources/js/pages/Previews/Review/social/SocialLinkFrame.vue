<script setup lang="ts">
/** A wide image as a promoted link post in a generic desktop feed. */
import { computed, inject } from 'vue'
import { MoreHorizontal, ThumbsUp, MessageSquare, Repeat2, Send, Globe } from 'lucide-vue-next'
import SafeZoneOverlay from '../video/SafeZoneOverlay.vue'
import SocialImage from './SocialImage.vue'
import { sizeOfSocial } from './useSocialShowcase'
import type { VideoBrand } from '../video/useVideoFormat'

const props = defineProps<{ image: any; safe: boolean }>()
const brand = inject<VideoBrand>('videoBrand')!
const s = computed(() => sizeOfSocial(props.image))
</script>

<template>
  <div class="w-full max-w-[540px] rounded-2xl bg-zinc-100 p-3 sm:p-4">
    <div class="overflow-hidden rounded-xl bg-white text-zinc-900 shadow-sm ring-1 ring-black/5">
      <div class="flex items-center gap-2.5 px-4 pt-3">
        <span class="grid h-10 w-10 place-items-center overflow-hidden rounded-md ring-1 ring-zinc-200">
          <img v-if="brand.logo" :src="brand.logo" alt="" class="h-full w-full object-contain p-1" />
        </span>
        <span class="leading-tight">
          <span class="block text-[13px] font-semibold">{{ brand.name }}</span>
          <span class="flex items-center gap-1 text-[11px] text-zinc-500">Promoted · <Globe class="h-3 w-3" /></span>
        </span>
        <MoreHorizontal class="ml-auto h-5 w-5 text-zinc-500" />
      </div>
      <p class="px-4 py-2.5 text-[13px] leading-snug">Our biggest sale of the season starts today. Up to 40% off, online and in stores.</p>
      <div class="relative w-full" :style="{ aspectRatio: `${s.w} / ${s.h}` }">
        <SocialImage :src="`/${image.path}`" :alt="image.name">
          <SafeZoneOverlay v-if="safe" format="feed" />
        </SocialImage>
      </div>
      <div class="flex items-center gap-3 bg-zinc-50 px-4 py-2.5">
        <span class="min-w-0 leading-tight">
          <span class="block truncate text-[13px] font-semibold">Up to 40% off the new collection</span>
          <span class="block text-[11px] text-zinc-500">example.com</span>
        </span>
        <span class="ml-auto shrink-0 rounded-full border border-sky-600 px-3 py-1 text-[12px] font-semibold text-sky-700">Learn more</span>
      </div>
      <div class="flex justify-around border-t border-zinc-100 py-2 text-[11px] font-medium text-zinc-500">
        <span class="flex items-center gap-1"><ThumbsUp class="h-4 w-4" /> Like</span>
        <span class="flex items-center gap-1"><MessageSquare class="h-4 w-4" /> Comment</span>
        <span class="flex items-center gap-1"><Repeat2 class="h-4 w-4" /> Repost</span>
        <span class="flex items-center gap-1"><Send class="h-4 w-4" /> Send</span>
      </div>
    </div>
  </div>
</template>
