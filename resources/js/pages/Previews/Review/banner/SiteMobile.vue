<script setup lang="ts">
/**
 * The same Set on a phone: a scrollable mobile article (real 390px viewport,
 * scaled into the phone) with the banners in the text and a strip stuck to the
 * bottom. Desktop-only sizes are listed, not forced in.
 */
import { computed, ref } from 'vue'
import { Menu, Search, MonitorOff, ChevronDown, ChevronUp } from 'lucide-vue-next'
import PhoneShell from '../video/PhoneShell.vue'
import ScaledBox from './ScaledBox.vue'
import BannerSlot from './BannerSlot.vue'
import { mobileSlots, sizeLabel } from './useBannerSlots'

const props = defineProps<{ banners: any[] }>()
const slots = computed(() => mobileSlots(props.banners))

/** Sticky (anchor) ad: the tab on top slides it out of view and back, like real ones. */
const stickyHidden = ref(false)
const lines = (n: number, seed: number) => Array.from({ length: n }, (_, i) => [100, 96, 99, 88, 100, 74][(i + seed) % 6])
</script>

<template>
  <div class="flex flex-col items-center gap-4">
    <!-- 402px = a real 390px screen + bezel, so banners show at true size;
         smaller when the window is too short to fit the whole phone. -->
    <PhoneShell css-width="min(402px, calc((100vh - 9rem) * 9 / 19.5))" status-dark>
      <div class="absolute inset-0 overflow-y-auto overscroll-contain bg-white pt-7 text-zinc-900 [scrollbar-width:none]">
        <ScaledBox :width="390">
          <div class="flex items-center gap-3 border-b border-zinc-200 bg-white px-4 py-3">
            <Menu class="h-5 w-5" />
            <span class="text-[19px] font-black tracking-tight">The Daily Example</span>
            <Search class="ml-auto h-5 w-5" />
          </div>
          <div class="px-4 pb-24 pt-4">
            <span class="text-[11px] font-bold uppercase tracking-widest text-rose-600">Culture</span>
            <div class="mt-2 h-6 w-full rounded bg-zinc-800/90" />
            <div class="mt-2 h-6 w-2/3 rounded bg-zinc-800/90" />
            <div class="hero mt-4 aspect-[16/10] w-full rounded-lg" />
            <div class="mt-5 space-y-2.5">
              <div v-for="(w, i) in lines(5, 0)" :key="`a${i}`" class="h-2.5 rounded bg-zinc-200" :style="{ width: `${w}%` }" />
            </div>
            <template v-for="(b, i) in slots.inline" :key="b.id">
              <div class="my-6 flex justify-center"><BannerSlot :banner="b" size-only /></div>
              <div class="space-y-2.5">
                <div v-for="(w, j) in lines(6, i + 1)" :key="`p${i}-${j}`" class="h-2.5 rounded bg-zinc-200" :style="{ width: `${w}%` }" />
              </div>
            </template>
          </div>
        </ScaledBox>
      </div>
      <div
        v-if="slots.sticky"
        class="sticky-ad absolute inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 py-1.5"
        :class="stickyHidden ? 'translate-y-full' : ''"
      >
        <!-- tab: stays visible when the ad is hidden, so it can come back -->
        <button
          type="button"
          class="absolute -top-6 right-3 flex h-6 items-center rounded-t-md border border-b-0 border-zinc-200 bg-white px-2 text-zinc-600 shadow-sm"
          :aria-label="stickyHidden ? 'Show sticky ad' : 'Hide sticky ad'"
          :title="stickyHidden ? 'Show ad' : 'Hide ad'"
          :aria-expanded="!stickyHidden"
          @click="stickyHidden = !stickyHidden"
        >
          <ChevronUp v-if="stickyHidden" class="h-3.5 w-3.5" />
          <ChevronDown v-else class="h-3.5 w-3.5" />
        </button>
        <ScaledBox :width="390">
          <div class="flex justify-center"><BannerSlot :banner="slots.sticky" size-only /></div>
        </ScaledBox>
      </div>
    </PhoneShell>

    <p v-if="slots.desktopOnly.length" class="flex items-center gap-1.5 text-[11px] text-[var(--p2-text-subtle)]">
      <MonitorOff class="h-3.5 w-3.5" />
      Desktop only, not shown on mobile: {{ slots.desktopOnly.map(sizeLabel).join(', ') }}
    </p>
    <p class="text-[11px] text-[var(--p2-text-subtle)]">Scroll inside the phone to read the article.</p>
  </div>
</template>

<style scoped>
.sticky-ad {
  transition: transform 300ms cubic-bezier(0.22, 1, 0.36, 1);
}
.hero {
  background:
    radial-gradient(60% 80% at 30% 30%, rgb(255 255 255 / 0.5), transparent 60%),
    linear-gradient(135deg, #cbd5e1, #94a3b8);
}
</style>
