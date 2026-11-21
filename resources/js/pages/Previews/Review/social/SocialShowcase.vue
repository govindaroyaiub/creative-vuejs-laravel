<script setup lang="ts">
/**
 * Replaces Show2's social grid on the review page. Toolbar (Clean · In
 * context · Safe zones, Carousel) is shared by every Set.
 * Feed images of a Set become one carousel post when Carousel is on.
 */
import { computed, inject, ref } from 'vue'
import { Square, Smartphone, ScanLine, GalleryHorizontal } from 'lucide-vue-next'
import { useViewFocus } from '../useViewFocus'
import SocialShowcaseCard from './SocialShowcaseCard.vue'
import { socialFormatOf, type SocialMode, type SocialShowcaseState } from './useSocialShowcase'

const props = defineProps<{ socials: any[] }>()
const state = inject<SocialShowcaseState>('socialShowcaseState')!

const feed = computed(() => props.socials.filter((s) => socialFormatOf(s) === 'feed'))
const boards = computed(() => props.socials.filter((s) => socialFormatOf(s) === 'storyboard'))
const others = computed(() => props.socials.filter((s) => !['feed', 'storyboard'].includes(socialFormatOf(s))))

/**
 * Cards to render: the storyboard (all its panels together), one carousel for
 * the feed images (if on), then the rest. Clean view shows every image alone.
 */
const groups = computed<any[][]>(() => {
  const clean = state.mode === 'clean'
  const asCarousel = state.carousel && !clean && feed.value.length > 1
  const boardGroups = boards.value.length ? (clean ? boards.value.map((s) => [s]) : [boards.value]) : []
  const feedGroups = asCarousel ? [feed.value] : feed.value.map((s) => [s])
  return [...boardGroups, ...feedGroups, ...others.value.map((s) => [s])]
})

const modes: { key: SocialMode; label: string; icon: any }[] = [
  { key: 'clean', label: 'Clean', icon: Square },
  { key: 'context', label: 'In context', icon: Smartphone },
  { key: 'safe', label: 'Safe zones', icon: ScanLine },
]

const root = ref<HTMLElement | null>(null)
const switchView = useViewFocus(root)
const pickMode = (m: SocialMode) => {
  if (m === state.mode) return
  switchView(() => (state.mode = m))
}
</script>

<template>
  <div ref="root" class="scroll-mt-4 space-y-6">
    <div class="flex flex-wrap items-center justify-center gap-2">
      <div class="seg" role="radiogroup" aria-label="Social view">
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
      <button
        v-if="feed.length > 1 && state.mode !== 'clean'"
        type="button"
        class="seg seg-btn"
        :class="state.carousel ? 'seg-on' : ''"
        :aria-pressed="state.carousel"
        title="Show this Set's feed images as one swipeable post"
        @click="state.carousel = !state.carousel"
      >
        <GalleryHorizontal class="h-3.5 w-3.5" /> Carousel
      </button>
      <span v-if="state.mode === 'safe'" class="w-full text-center text-[11px] text-[var(--p2-text-subtle)]">
        Red areas are covered by app UI. Keep text and logos out of them.
      </span>
    </div>

    <div class="flex flex-wrap items-start justify-center gap-6">
      <SocialShowcaseCard v-for="g in groups" :key="g.map((s) => s.id).join('-')" :images="g">
        <template #footer="{ social }">
          <slot name="footer" :social="social" />
        </template>
      </SocialShowcaseCard>
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
button.seg.seg-btn {
  height: 2.25rem;
}
</style>
