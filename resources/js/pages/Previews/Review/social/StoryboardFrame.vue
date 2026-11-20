<script setup lang="ts">
/**
 * Storyboard: the Set's storyboard images as a numbered sheet, in order, with
 * the image name as the panel caption. "Play" steps through the panels like a
 * simple animatic. Clicking a panel selects it (the review row follows it).
 */
import { computed, inject, onBeforeUnmount, ref, watch } from 'vue'
import { Play, Pause, ChevronLeft, ChevronRight, LayoutGrid, Clapperboard } from 'lucide-vue-next'
import SocialImage from './SocialImage.vue'
import { sizeOfSocial } from './useSocialShowcase'
import type { VideoBrand } from '../video/useVideoFormat'

const props = defineProps<{ images: any[]; index: number }>()
const emit = defineEmits<{ (e: 'update:index', v: number): void }>()
const brand = inject<VideoBrand>('videoBrand')!

const first = computed(() => sizeOfSocial(props.images[0]))
const portrait = computed(() => first.value.w < first.value.h)
const num = (i: number) => String(i + 1).padStart(2, '0')
const caption = (img: any, i: number) => (img?.name && !/^social_|\.\w{3,4}$/.test(img.name) ? img.name : `Shot ${num(i)}`)

// ---- Play-through ---------------------------------------------------------
const view = ref<'sheet' | 'play'>('sheet')
const playing = ref(false)
const SECONDS = 2.5
const tick = ref(0) // 0..1 progress of the current panel
let timer: number | null = null

const stop = () => {
  playing.value = false
  if (timer) window.clearInterval(timer)
  timer = null
}
const start = () => {
  stop()
  playing.value = true
  const step = 50
  timer = window.setInterval(() => {
    tick.value += step / (SECONDS * 1000)
    if (tick.value >= 1) {
      tick.value = 0
      emit('update:index', (props.index + 1) % props.images.length)
    }
  }, step)
}
const go = (d: number) => {
  tick.value = 0
  emit('update:index', (props.index + d + props.images.length) % props.images.length)
}
const openPlay = (i?: number) => {
  if (i !== undefined) emit('update:index', i)
  tick.value = 0
  view.value = 'play'
  start()
}
watch(view, (v) => v === 'sheet' && stop())
onBeforeUnmount(stop)

const current = computed(() => props.images[props.index] ?? props.images[0])
</script>

<template>
  <div class="w-full">
    <!-- header strip -->
    <div class="mb-3 flex flex-wrap items-center gap-2">
      <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--p2-text-muted)]">
        <Clapperboard class="h-3.5 w-3.5" /> Storyboard · {{ images.length }} panels
      </span>
      <div class="ml-auto inline-flex gap-0.5 rounded-full border p-0.5" :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }">
        <button type="button" class="sb-tab" :class="view === 'sheet' ? 'sb-on' : ''" @click="view = 'sheet'">
          <LayoutGrid class="h-3.5 w-3.5" /> Sheet
        </button>
        <button type="button" class="sb-tab" :class="view === 'play' ? 'sb-on' : ''" @click="openPlay()">
          <Play class="h-3.5 w-3.5" /> Play
        </button>
      </div>
    </div>

    <!-- Sheet -->
    <div v-if="view === 'sheet'" class="sheet rounded-xl p-4 sm:p-5">
      <div class="mb-4 flex items-baseline justify-between border-b border-zinc-200 pb-2 text-zinc-900">
        <span class="text-[13px] font-bold tracking-tight">{{ brand.name }}</span>
        <span class="text-[10px] uppercase tracking-[0.2em] text-zinc-400">Storyboard</span>
      </div>
      <div class="grid gap-x-4 gap-y-5" :class="portrait ? 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'">
        <button
          v-for="(img, i) in images"
          :key="img.id"
          type="button"
          class="group text-left"
          @click="emit('update:index', i)"
          @dblclick="openPlay(i)"
        >
          <span class="mb-1 flex items-center justify-between text-zinc-900">
            <span class="text-[11px] font-bold tabular-nums">{{ num(i) }}</span>
            <span class="text-[9px] uppercase tracking-wider text-zinc-400 opacity-0 transition group-hover:opacity-100">Double-click to play</span>
          </span>
          <span
            class="relative block overflow-hidden rounded-md ring-1 transition"
            :class="i === index ? 'ring-2 ring-sky-500' : 'ring-zinc-300 group-hover:ring-zinc-500'"
            :style="{ aspectRatio: `${sizeOfSocial(img).w} / ${sizeOfSocial(img).h}` }"
          >
            <SocialImage :src="`/${img.path}`" :alt="caption(img, i)" />
          </span>
          <span class="mt-1.5 block text-[11px] leading-snug text-zinc-600">{{ caption(img, i) }}</span>
        </button>
      </div>
    </div>

    <!-- Play -->
    <div v-else class="overflow-hidden rounded-xl bg-zinc-950 text-white">
      <div class="flex gap-1 px-4 pt-3">
        <span v-for="(img, i) in images" :key="img.id" class="h-[3px] flex-1 overflow-hidden rounded-full bg-white/20">
          <span
            class="block h-full bg-white"
            :style="{ width: i < index ? '100%' : i === index ? `${tick * 100}%` : '0%' }"
          />
        </span>
      </div>
      <div class="flex justify-center px-4 py-4">
        <div
          class="relative overflow-hidden rounded-md"
          :style="{
            aspectRatio: `${sizeOfSocial(current).w} / ${sizeOfSocial(current).h}`,
            width: `min(100%, calc(55vh * ${sizeOfSocial(current).w} / ${sizeOfSocial(current).h}))`,
          }"
        >
          <Transition name="sb-fade" mode="out-in">
            <SocialImage :key="current.id" :src="`/${current.path}`" :alt="caption(current, index)" fit="contain" />
          </Transition>
          <span class="absolute left-2 top-2 rounded bg-black/60 px-2 py-0.5 text-[11px] font-bold tabular-nums">{{ num(index) }}</span>
        </div>
      </div>
      <div class="flex items-center gap-3 border-t border-white/10 px-4 py-3">
        <button type="button" class="sb-ctl" aria-label="Previous panel" @click="go(-1)"><ChevronLeft class="h-4 w-4" /></button>
        <button type="button" class="sb-ctl" :aria-label="playing ? 'Pause' : 'Play'" @click="playing ? stop() : start()">
          <Pause v-if="playing" class="h-4 w-4" />
          <Play v-else class="h-4 w-4" />
        </button>
        <button type="button" class="sb-ctl" aria-label="Next panel" @click="go(1)"><ChevronRight class="h-4 w-4" /></button>
        <p class="min-w-0 truncate text-[12px]">
          <span class="font-semibold tabular-nums">{{ num(index) }} / {{ num(images.length - 1) }}</span>
          <span class="ml-2 text-white/70">{{ caption(current, index) }}</span>
        </p>
        <span class="ml-auto text-[10px] text-white/40">{{ SECONDS }}s per panel</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.sheet {
  background: #fff;
  box-shadow:
    0 1px 0 rgb(0 0 0 / 0.04),
    0 12px 30px -18px rgb(0 0 0 / 0.35);
}
.sb-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  height: 1.75rem;
  padding: 0 0.7rem;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 500;
  color: var(--p2-text-muted);
}
.sb-on {
  background: var(--p2-accent-soft);
  color: var(--p2-text);
}
.sb-ctl {
  display: grid;
  place-items: center;
  height: 2rem;
  width: 2rem;
  border-radius: 9999px;
  background: rgb(255 255 255 / 0.1);
  transition: background 150ms ease;
}
.sb-ctl:hover {
  background: rgb(255 255 255 / 0.2);
}
.sb-fade-enter-active,
.sb-fade-leave-active {
  transition: opacity 0.25s ease;
}
.sb-fade-enter-from,
.sb-fade-leave-to {
  opacity: 0;
}
</style>
