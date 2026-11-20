<script setup lang="ts">
/** A tall image as a promoted pin in a generic two-column board on a phone. */
import { computed, inject } from 'vue'
import { MoreHorizontal, Search, Home, PlusSquare, MessageCircle, User } from 'lucide-vue-next'
import PhoneShell from '../video/PhoneShell.vue'
import SafeZoneOverlay from '../video/SafeZoneOverlay.vue'
import SocialImage from './SocialImage.vue'
import { sizeOfSocial } from './useSocialShowcase'
import type { VideoBrand } from '../video/useVideoFormat'

const props = defineProps<{ image: any; safe: boolean }>()
const brand = inject<VideoBrand>('videoBrand')!
const s = computed(() => sizeOfSocial(props.image))
// Neighbouring tiles (grey placeholders of mixed heights).
const left = ['4 / 5', '1 / 1']
const right = ['3 / 4', '2 / 3', '1 / 1']
</script>

<template>
  <PhoneShell :width="290" status-dark>
    <div class="absolute inset-0 flex flex-col bg-white text-zinc-900">
      <div class="flex shrink-0 items-center gap-2 px-3 pb-2 pt-9">
        <span class="flex h-8 flex-1 items-center gap-1.5 rounded-full bg-zinc-100 px-3 text-[11px] text-zinc-400">
          <Search class="h-3.5 w-3.5" /> Search ideas
        </span>
      </div>
      <div class="grid min-h-0 flex-1 grid-cols-2 gap-2 overflow-hidden px-2">
        <div class="space-y-2">
          <div>
            <div class="relative w-full overflow-hidden rounded-xl" :style="{ aspectRatio: `${s.w} / ${s.h}` }">
              <SocialImage :src="`/${image.path}`" :alt="image.name">
                <SafeZoneOverlay v-if="safe" format="feed" />
              </SocialImage>
            </div>
            <div class="mt-1 flex items-center gap-1 px-0.5">
              <span class="grid h-4 w-4 place-items-center overflow-hidden rounded-full ring-1 ring-zinc-200">
                <img v-if="brand.logo" :src="brand.logo" alt="" class="h-full w-full object-contain" />
              </span>
              <span class="min-w-0 leading-tight">
                <span class="block text-[9px] text-zinc-500">Promoted by</span>
                <span class="block truncate text-[10px] font-semibold">{{ brand.name }}</span>
              </span>
              <MoreHorizontal class="ml-auto h-3.5 w-3.5 text-zinc-500" />
            </div>
          </div>
          <div v-for="(r, i) in left" :key="`l${i}`" class="rounded-xl bg-zinc-200" :style="{ aspectRatio: r }" />
        </div>
        <div class="space-y-2">
          <div v-for="(r, i) in right" :key="`r${i}`" class="rounded-xl bg-zinc-200" :style="{ aspectRatio: r }" />
        </div>
      </div>
      <div class="flex shrink-0 items-center justify-around border-t border-zinc-100 pb-4 pt-2">
        <Home class="h-5 w-5" /><Search class="h-5 w-5" /><PlusSquare class="h-5 w-5" /><MessageCircle class="h-5 w-5" /><User class="h-5 w-5" />
      </div>
    </div>
  </PhoneShell>
</template>
