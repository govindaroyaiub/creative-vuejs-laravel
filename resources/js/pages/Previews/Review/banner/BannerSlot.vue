<script setup lang="ts">
/**
 * One banner at its real pixel size in an ad slot.
 *  - `tools`: a bar above it with the size and a reload button, and the
 *    `footer` slot (comments / review) right under it.
 *  - `sizeOnly`: just the size chip above it (phone view, no room for more).
 *  - neither: just the "Advertisement" label, like a real site.
 * Highlights when its size is hovered in the size strip.
 * Banners are HTML5 (iframe); GIFs are an image, reloaded with a fresh query
 * so the animation restarts from the first frame.
 */
import { computed, inject, ref, type Ref } from 'vue'
import { RotateCw } from 'lucide-vue-next'
import { bannerSrc, bh, bw, sizeLabel, type BannerShowcaseState } from './useBannerSlots'

const props = withDefaults(defineProps<{ banner: any; label?: boolean; tools?: boolean; sizeOnly?: boolean }>(), {
  label: true,
  tools: false,
  sizeOnly: false,
})
const state = inject<BannerShowcaseState>('bannerShowcaseState')!
const kind = inject<Ref<'banner' | 'gif'>>('bannerKind', ref('banner'))

const loaded = ref(false)
const ownReplay = ref(0)
const lit = computed(() => state.hover === props.banner.id)
const reload = () => {
  loaded.value = false
  ownReplay.value++
}
</script>

<template>
  <div class="inline-flex flex-col" :style="{ width: `${bw(banner)}px` }">
    <!-- tools bar -->
    <div v-if="tools" class="mb-1.5 flex items-center gap-1.5">
      <span class="rounded-md bg-sky-50 px-2 py-0.5 font-mono text-[11px] font-semibold tabular-nums text-zinc-900 ring-1 ring-sky-200">
        {{ bw(banner) }}<span class="text-zinc-400">×</span>{{ bh(banner) }}
      </span>
      <button type="button" class="tool ml-auto" aria-label="Reload banner" title="Reload" @click="reload">
        <RotateCw class="h-3.5 w-3.5" />
      </button>
    </div>
    <span
      v-else-if="sizeOnly"
      class="mb-1.5 self-center rounded-md bg-sky-50 px-2 py-0.5 font-mono text-[11px] font-semibold tabular-nums text-zinc-900 ring-1 ring-sky-200"
    >
      {{ bw(banner) }}<span class="text-zinc-400">×</span>{{ bh(banner) }}
    </span>
    <span v-else-if="label" class="mb-1 self-center text-[9px] uppercase tracking-[0.2em] text-zinc-400">Advertisement</span>

    <div
      class="slot relative overflow-hidden bg-zinc-100"
      :class="lit ? 'slot-lit' : ''"
      :style="{ width: `${bw(banner)}px`, height: `${bh(banner)}px` }"
    >
      <img
        v-if="kind === 'gif'"
        :key="`${banner.id}-${state.replay}-${ownReplay}`"
        :src="`/${banner.path}?r=${state.replay}-${ownReplay}`"
        :width="bw(banner)"
        :height="bh(banner)"
        :alt="banner.name || sizeLabel(banner)"
        draggable="false"
        class="block h-full w-full"
        @load="loaded = true"
        @error="loaded = true"
      />
      <iframe
        v-else
        :key="`${banner.id}-${state.replay}-${ownReplay}`"
        :src="bannerSrc(banner)"
        :width="bw(banner)"
        :height="bh(banner)"
        frameborder="0"
        scrolling="no"
        sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox"
        referrerpolicy="no-referrer"
        class="block border-0"
        @load="loaded = true"
      />
      <div v-if="!loaded" class="p2-shimmer pointer-events-none absolute inset-0" />
      <span v-if="lit" class="pointer-events-none absolute left-1 top-1 rounded bg-sky-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">
        {{ sizeLabel(banner) }}
      </span>
    </div>

    <!-- comments / review, right under the banner -->
    <div v-if="tools && $slots.footer" class="footer-box mt-1.5 rounded-xl px-2">
      <slot name="footer" :banner="banner" />
    </div>
  </div>
</template>

<style scoped>
.slot {
  transition: box-shadow 200ms ease;
}
.slot-lit {
  box-shadow:
    0 0 0 3px rgb(14 165 233),
    0 0 0 9px rgb(14 165 233 / 0.25);
}
.tool {
  display: grid;
  place-items: center;
  height: 1.625rem;
  width: 1.625rem;
  border-radius: 9999px;
  border: 1px solid rgb(228 228 231);
  background: #fff;
  color: rgb(113 113 122);
  transition: color 150ms ease, border-color 150ms ease;
}
.tool:hover {
  color: rgb(14 165 233);
  border-color: rgb(186 230 253);
}
/* The review row uses the viewer's theme colours; give it the theme surface so it reads on the white page in dark mode too. */
.footer-box {
  background: var(--p2-surface);
  border: 1px solid var(--p2-border);
}
</style>
