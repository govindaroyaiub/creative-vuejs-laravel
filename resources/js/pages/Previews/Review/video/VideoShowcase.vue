<script setup lang="ts">
/**
 * DEMO ONLY: replaces Show2's video grid inside the review prototype.
 * Toolbar (Clean · In context · Safe zones, Laptop / TV) is shared by every
 * Set. Phones (story, feed) sit side by side; wide placements take a row.
 * The `footer` slot carries the review row (comments / status) per video.
 */
import { computed, inject, ref } from 'vue'
import { Monitor, Tv, ScanLine, Smartphone, Square } from 'lucide-vue-next'
import { useViewFocus } from '../useViewFocus'
import VideoShowcaseCard from './VideoShowcaseCard.vue'
import { formatOf, isNarrowVideo, type ShowcaseMode, type ShowcaseState } from './useVideoFormat'

const props = defineProps<{ videos: any[] }>()
const state = inject<ShowcaseState>('videoShowcaseState')!

const narrow = computed(() => props.videos.filter((v) => isNarrowVideo(v)))
const wide = computed(() => props.videos.filter((v) => !isNarrowVideo(v)))
const hasLandscape = computed(() => props.videos.some((v) => formatOf(v) === 'screen'))

const modes: { key: ShowcaseMode; label: string; icon: any }[] = [
  { key: 'clean', label: 'Clean', icon: Square },
  { key: 'context', label: 'In context', icon: Smartphone },
  { key: 'safe', label: 'Safe zones', icon: ScanLine },
]

const root = ref<HTMLElement | null>(null)
const switchView = useViewFocus(root)
const pickMode = (m: ShowcaseMode) => {
  if (m === state.mode) return
  switchView(() => (state.mode = m))
}
</script>

<template>
  <div ref="root" class="scroll-mt-4 space-y-6">
    <!-- toolbar -->
    <div class="flex flex-wrap items-center justify-center gap-2">
      <div class="seg" role="radiogroup" aria-label="Video view">
        <button
          v-for="m in modes"
          :key="m.key"
          type="button"
          role="radio"
          :aria-checked="state.mode === m.key"
          class="seg-btn"
          :class="state.mode === m.key ? 'seg-on' : ''"
          @click="pickMode(m.key)"
        >
          <component :is="m.icon" class="h-3.5 w-3.5" /> {{ m.label }}
        </button>
      </div>
      <div v-if="hasLandscape && state.mode !== 'clean'" class="seg" role="radiogroup" aria-label="Wide screen">
        <button type="button" role="radio" :aria-checked="state.screen === 'laptop'" class="seg-btn" :class="state.screen === 'laptop' ? 'seg-on' : ''" @click="state.screen = 'laptop'">
          <Monitor class="h-3.5 w-3.5" /> Laptop
        </button>
        <button type="button" role="radio" :aria-checked="state.screen === 'tv'" class="seg-btn" :class="state.screen === 'tv' ? 'seg-on' : ''" @click="state.screen = 'tv'">
          <Tv class="h-3.5 w-3.5" /> TV
        </button>
      </div>
      <span v-if="state.mode === 'safe'" class="w-full text-center text-[11px] text-[var(--p2-text-subtle)]">
        Red areas are covered by app UI. Keep text and logos out of them.
      </span>
    </div>

    <!-- phones, side by side -->
    <div v-if="narrow.length" class="flex flex-wrap items-start justify-center gap-6">
      <div v-for="v in narrow" :key="v.id" class="flex max-w-full flex-col">
        <VideoShowcaseCard :video="v" />
        <slot name="footer" :video="v" />
      </div>
    </div>

    <!-- wide placements, one per row -->
    <div v-for="v in wide" :key="v.id" class="flex flex-col">
      <VideoShowcaseCard :video="v" />
      <slot name="footer" :video="v" />
    </div>
  </div>
</template>

<style scoped>
.seg {
  display: inline-flex;
  gap: 2px;
  padding: 3px;
  border-radius: 9999px;
  border: 1px solid var(--p2-border);
  background: var(--p2-surface);
}
.seg-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  height: 1.875rem;
  padding: 0 0.75rem;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 500;
  color: var(--p2-text-muted);
  transition: background 200ms var(--p2-ease-expo), color 200ms var(--p2-ease-expo);
}
.seg-btn:hover {
  color: var(--p2-text);
}
.seg-on {
  background: var(--p2-accent-soft);
  color: var(--p2-text);
}
</style>
