<script setup lang="ts">
/**
 * Replaces Show2's banner row on the review page.
 *  - Clean: the banners centred, bottom-aligned (Show2's own cards).
 *  - Desktop: the whole Set at once on a news homepage, each size in its slot.
 *  - Mobile: the same Set in a scrollable mobile article on a phone.
 * "Replay all" (Mobile view only) restarts every banner together; on the
 * site each banner has its own reload. On the desktop site every
 * banner has its size / reload bar and its review row with it. Mobile is a
 * look-only view (sizes on the banners, no review rows): reviewing and
 * commenting happen in Clean or On a site.
 */
import { computed, inject, provide, ref, toRef } from 'vue'
import { Square, Monitor, Smartphone, RotateCcw } from 'lucide-vue-next'
import BannerCard from '../../Show2/BannerCard.vue'
import GifCard from '../../Show2/GifCard.vue'
import SiteDesktop from './SiteDesktop.vue'
import SiteMobile from './SiteMobile.vue'
import { useViewFocus } from '../useViewFocus'
import { viewsFor, type BannerShowcaseState, type BannerView } from './useBannerSlots'

/** GIF projects use the same views; only the creative is an image instead of HTML5. */
const props = withDefaults(defineProps<{ banners: any[]; kind?: 'banner' | 'gif' }>(), { kind: 'banner' })
provide('bannerKind', toRef(props, 'kind'))
const state = inject<BannerShowcaseState>('bannerShowcaseState')!

/**
 * A view with no banner to place is disabled for this Set. If the viewer's
 * choice is such a view, this Set falls back to one that has banners (the
 * shared choice itself is left alone for the other Sets).
 */
const root = ref<HTMLElement | null>(null)
const switchView = useViewFocus(root)
const pick = (v: BannerView) => {
  if (v === view.value) return
  switchView(() => (state.view = v))
}

const available = computed(() => ({ clean: true, ...viewsFor(props.banners) }))
const view = computed<BannerView>(() => {
  if (available.value[state.view]) return state.view
  if (available.value.desktop) return 'desktop'
  if (available.value.mobile) return 'mobile'
  return 'clean'
})
const why: Record<BannerView, string> = {
  clean: '',
  desktop: 'Only mobile sizes in this Set. See them in the Mobile view.',
  mobile: 'No size in this Set runs on phones (desktop sizes only).',
}

const views: { key: BannerView; label: string; icon: any }[] = [
  { key: 'clean', label: 'Clean', icon: Square },
  { key: 'desktop', label: 'On a site', icon: Monitor },
  { key: 'mobile', label: 'Mobile', icon: Smartphone },
]
</script>

<template>
  <div ref="root" class="scroll-mt-4 space-y-6">
    <div class="flex flex-wrap items-center justify-center gap-2">
      <div class="seg" role="radiogroup" aria-label="Banner view">
        <button
          v-for="v in views"
          :key="v.key"
          type="button"
          role="radio"
          :aria-checked="view === v.key"
          :disabled="!available[v.key]"
          :title="available[v.key] ? '' : why[v.key]"
          class="seg-btn"
          :class="view === v.key ? 'seg-on' : ''"
          @click="pick(v.key)"
        >
          <component :is="v.icon" class="h-3.5 w-3.5" /> {{ v.label }}
        </button>
      </div>
      <button v-if="view === 'mobile'" type="button" class="seg seg-btn" title="Restart every banner at the same moment" @click="state.replay++">
        <RotateCcw class="h-3.5 w-3.5" /> Replay all
      </button>
    </div>

    <!-- Clean: centred, bottom-aligned -->
    <div v-if="view === 'clean'" class="flex flex-wrap items-end justify-center gap-6">
      <div v-for="(b, i) in banners" :key="b.id" class="flex max-w-full flex-col">
        <GifCard v-if="kind === 'gif'" :gif="b" />
        <BannerCard v-else :banner="b" :index="i" />
        <slot name="footer" :banner="b" />
      </div>
    </div>

    <template v-else>
      <!-- Desktop: size, reload and the review row sit with each banner -->
      <SiteDesktop v-if="view === 'desktop'" :banners="banners">
        <template #footer="{ banner: fb }"><slot name="footer" :banner="fb" /></template>
      </SiteDesktop>
      <SiteMobile v-else :banners="banners" />
    </template>
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
.seg-btn:hover:not(:disabled) {
  color: var(--p2-text);
}
.seg-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.seg-on {
  background: var(--p2-accent-soft);
  color: var(--p2-text);
}
button.seg.seg-btn {
  height: 2.25rem;
}
.chip {
  border-color: var(--p2-border);
  background: var(--p2-surface);
  transition: border-color 200ms ease, box-shadow 200ms ease;
}
.chip-on {
  border-color: rgb(14 165 233);
  box-shadow: 0 0 0 3px rgb(14 165 233 / 0.2);
}
</style>
