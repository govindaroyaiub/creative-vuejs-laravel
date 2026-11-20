<script setup lang="ts">
/**
 * One social creative (or a carousel of them) in its frame. Clean view is
 * Show2's own SocialCard (with its zoom lightbox), so nothing is lost.
 * The `footer` slot gets the image currently on screen.
 */
import { computed, inject, ref } from 'vue'
import { Download } from 'lucide-vue-next'
import SocialFeedFrame from './SocialFeedFrame.vue'
import SocialStoryFrame from './SocialStoryFrame.vue'
import SocialLinkFrame from './SocialLinkFrame.vue'
import SocialPinFrame from './SocialPinFrame.vue'
import StoryboardFrame from './StoryboardFrame.vue'
import SocialCard from '../../Show2/SocialCard.vue'
import { SOCIAL_LABEL, ratioOf, sizeOfSocial, socialFormatOf, type SocialShowcaseState } from './useSocialShowcase'

const props = defineProps<{ images: any[] }>()
const state = inject<SocialShowcaseState>('socialShowcaseState')!
const isPlanetNine = inject<boolean>('isPlanetNine', false)

const index = ref(0)
const current = computed(() => props.images[Math.min(index.value, props.images.length - 1)])
const format = computed(() => socialFormatOf(props.images[0]))
const s = computed(() => sizeOfSocial(current.value))
const isCarousel = computed(() => props.images.length > 1)
const inContext = computed(() => state.mode !== 'clean' && format.value !== 'plain')
const safe = computed(() => state.mode === 'safe')
const label = computed(() =>
  format.value === 'storyboard'
    ? `Storyboard · ${props.images.length} panel${props.images.length === 1 ? '' : 's'}`
    : isCarousel.value
      ? `Carousel · ${props.images.length} images`
      : SOCIAL_LABEL[format.value],
)
</script>

<template>
  <!-- Clean: Show2's own card (it has its border and zoom lightbox) -->
  <div v-if="!inContext" class="flex w-[320px] max-w-full flex-col">
    <SocialCard :social="current" />
    <slot name="footer" :social="current" />
  </div>

  <div
    v-else
    class="flex max-w-full flex-col overflow-hidden rounded-3xl border bg-[var(--p2-surface)]"
    :class="format === 'storyboard' ? 'w-full' : format === 'link' ? 'w-full max-w-[640px]' : 'w-fit'"
    :style="{ borderColor: 'var(--p2-border)' }"
  >
    <div class="flex items-center gap-2 border-b px-4 py-2.5" :style="{ borderColor: 'var(--p2-hairline)' }">
      <span class="p2-mono rounded-md px-2 py-0.5 text-xs font-semibold" :style="{ background: 'var(--p2-accent-soft)', color: 'var(--p2-text)' }">
        {{ s.w }}×{{ s.h }}
      </span>
      <span class="truncate text-xs font-medium text-[var(--p2-text-muted)]">{{ label }} · {{ ratioOf(current) }}</span>
      <a
        v-if="isPlanetNine"
        :href="`/${current.path}`"
        download
        class="ml-auto grid h-7 w-7 shrink-0 place-items-center rounded-full border text-[var(--p2-text-muted)] transition-colors hover:text-[var(--p2-accent)]"
        :style="{ borderColor: 'var(--p2-border)' }"
        title="Download this image"
      >
        <Download class="h-3.5 w-3.5" />
      </a>
    </div>

    <div class="stage-bg flex justify-center px-5 py-7 sm:px-8">
      <StoryboardFrame v-if="format === 'storyboard'" v-model:index="index" :images="images" />
      <SocialFeedFrame v-else-if="format === 'feed'" v-model:index="index" :images="images" :safe="safe" />
      <SocialStoryFrame v-else-if="format === 'story'" :image="current" :safe="safe" />
      <SocialLinkFrame v-else-if="format === 'link'" :image="current" :safe="safe" />
      <SocialPinFrame v-else :image="current" :safe="safe" />
    </div>

    <slot name="footer" :social="current" />
  </div>
</template>

<style scoped>
.stage-bg {
  background:
    radial-gradient(120% 80% at 50% 0%, var(--p2-accent-soft), transparent 60%),
    var(--p2-surface-muted);
}
</style>
