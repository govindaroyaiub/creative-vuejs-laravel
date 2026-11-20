<script setup lang="ts">
/**
 * A sponsored post in a generic phone feed. Several images = a carousel:
 * swipe with the arrows or dots; the post takes the first image's shape,
 * like the apps do.
 */
import { computed, inject } from 'vue'
import { Heart, MessageCircle, Send, Bookmark, MoreHorizontal, ChevronRight, ChevronLeft, Home, Search, PlusSquare, User } from 'lucide-vue-next'
import PhoneShell from '../video/PhoneShell.vue'
import SafeZoneOverlay from '../video/SafeZoneOverlay.vue'
import SocialImage from './SocialImage.vue'
import { sizeOfSocial } from './useSocialShowcase'
import type { VideoBrand } from '../video/useVideoFormat'

const props = defineProps<{ images: any[]; safe: boolean; index: number }>()
const emit = defineEmits<{ (e: 'update:index', v: number): void }>()
const brand = inject<VideoBrand>('videoBrand')!

const first = computed(() => sizeOfSocial(props.images[0]))
const many = computed(() => props.images.length > 1)
const go = (d: number) => emit('update:index', Math.min(props.images.length - 1, Math.max(0, props.index + d)))
</script>

<template>
  <PhoneShell :width="290" status-dark>
    <div class="absolute inset-0 flex flex-col bg-white text-zinc-900">
      <div class="flex shrink-0 items-end justify-between px-3.5 pb-2 pt-9">
        <span class="text-[15px] font-bold tracking-tight">Feed</span>
        <span class="flex gap-3"><Heart class="h-[18px] w-[18px]" /><Send class="h-[18px] w-[18px]" /></span>
      </div>

      <div class="min-h-0 flex-1 overflow-hidden">
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

        <!-- media (carousel track) -->
        <div class="group relative w-full overflow-hidden" :style="{ aspectRatio: `${first.w} / ${first.h}` }">
          <div class="flex h-full transition-transform duration-300 ease-out" :style="{ transform: `translateX(-${index * 100}%)` }">
            <div v-for="img in images" :key="img.id" class="h-full w-full shrink-0">
              <SocialImage :src="`/${img.path}`" :alt="img.name">
                <SafeZoneOverlay v-if="safe" format="feed" />
              </SocialImage>
            </div>
          </div>
          <template v-if="many">
            <span class="absolute right-2 top-2 rounded-full bg-black/60 px-2 py-0.5 text-[10px] font-medium text-white">
              {{ index + 1 }}/{{ images.length }}
            </span>
            <button
              v-if="index > 0"
              type="button"
              class="absolute left-1.5 top-1/2 grid h-6 w-6 -translate-y-1/2 place-items-center rounded-full bg-white/85 text-zinc-900 shadow"
              aria-label="Previous image"
              @click="go(-1)"
            >
              <ChevronLeft class="h-4 w-4" />
            </button>
            <button
              v-if="index < images.length - 1"
              type="button"
              class="absolute right-1.5 top-1/2 grid h-6 w-6 -translate-y-1/2 place-items-center rounded-full bg-white/85 text-zinc-900 shadow"
              aria-label="Next image"
              @click="go(1)"
            >
              <ChevronRight class="h-4 w-4" />
            </button>
          </template>
        </div>

        <div class="flex items-center justify-between border-b border-zinc-100 bg-zinc-50 px-3 py-2 text-[11px] font-semibold">
          Shop now <ChevronRight class="h-3.5 w-3.5" />
        </div>

        <div class="relative flex items-center gap-3 px-3 pt-2">
          <Heart class="h-5 w-5" /><MessageCircle class="h-5 w-5" /><Send class="h-5 w-5" />
          <span v-if="many" class="absolute left-1/2 flex -translate-x-1/2 gap-1">
            <button
              v-for="(img, i) in images"
              :key="img.id"
              type="button"
              class="h-1.5 w-1.5 rounded-full"
              :class="i === index ? 'bg-sky-500' : 'bg-zinc-300'"
              :aria-label="`Image ${i + 1}`"
              @click="emit('update:index', i)"
            />
          </span>
          <Bookmark class="ml-auto h-5 w-5" />
        </div>
        <p class="px-3 pt-1 text-[11px] font-semibold">2,431 likes</p>
        <p class="px-3 text-[11px] leading-snug">
          <span class="font-semibold">{{ brand.handle }}</span> The new season is here. Tap to shop the collection.
        </p>
        <p class="px-3 pt-0.5 text-[10px] text-zinc-400">View all 124 comments</p>
      </div>

      <div class="flex shrink-0 items-center justify-around border-t border-zinc-100 pb-4 pt-2">
        <Home class="h-5 w-5" /><Search class="h-5 w-5" /><PlusSquare class="h-5 w-5" /><User class="h-5 w-5" />
      </div>
    </div>
  </PhoneShell>
</template>
