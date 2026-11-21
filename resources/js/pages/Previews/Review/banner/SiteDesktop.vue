<script setup lang="ts">
/**
 * A generic news homepage at real desktop size (at least 1300px, wider when
 * there is room), scaled down to fit narrower screens, with
 * every banner of the Set in its natural slot at the same time.
 */
import { computed } from 'vue'
import { Search, Menu } from 'lucide-vue-next'
import BrowserChrome from '../video/BrowserChrome.vue'
import ScaledBox from './ScaledBox.vue'
import BannerSlot from './BannerSlot.vue'
import { desktopSlots, PAGE_W, PAGE_W_WITH_GUTTER } from './useBannerSlots'

const props = defineProps<{ banners: any[] }>()
const slots = computed(() => desktopSlots(props.banners))
/** Sizes that do not run on this desktop page, shown under it. */
const offPage = computed(() =>
  [
    { key: 'mobile', title: 'Mobile sizes · see them in the Mobile view', list: slots.value.mobileOnly },
    { key: 'other', title: 'Other sizes in this Set', list: slots.value.other },
  ].filter((g) => g.list.length),
)
/** Article text split into blocks, one banner after each block. */
const blocks = computed(() => slots.value.article.map((b, i) => ({ banner: b, lines: para(4, i + 2) })))
const para = (n: number, seed: number) => Array.from({ length: n }, (_, i) => [100, 97, 99, 92, 100, 84, 96, 71][(i + seed) % 8])
</script>

<template>
  <ScaledBox :width="slots.gutter ? PAGE_W_WITH_GUTTER : PAGE_W" fluid>
    <BrowserChrome url="news.example.com" radius="12px">
      <div class="bg-white text-zinc-900">
        <!-- masthead -->
        <div class="flex items-center gap-4 border-b border-zinc-200 px-10 py-4">
          <Menu class="h-5 w-5 text-zinc-500" />
          <span class="text-[28px] font-black tracking-tight">The Daily Example</span>
          <span class="ml-6 flex gap-6 text-[12px] font-semibold uppercase tracking-wider text-zinc-500">
            <span class="text-zinc-900">Home</span><span>News</span><span>Business</span><span>Culture</span><span>Sport</span><span>Travel</span>
          </span>
          <span class="ml-auto flex h-9 w-56 items-center gap-2 rounded-full bg-zinc-100 px-4 text-[12px] text-zinc-400">
            <Search class="h-4 w-4" /> Search
          </span>
        </div>

        <!-- top slot: billboard / leaderboard -->
        <div v-if="slots.top" class="flex justify-center border-b border-zinc-100 bg-zinc-50 py-5">
          <BannerSlot :banner="slots.top" tools>
            <template #footer="{ banner: fb }"><slot name="footer" :banner="fb" /></template>
          </BannerSlot>
        </div>

        <div class="grid gap-8 px-10 py-8" :class="slots.gutter ? 'grid-cols-[160px_1fr_300px]' : 'grid-cols-[1fr_300px]'">
          <!-- left gutter: skyscraper beside the content -->
          <aside v-if="slots.gutter" class="flex justify-center">
            <div class="self-start">
              <BannerSlot :banner="slots.gutter" tools>
                <template #footer="{ banner: fb }"><slot name="footer" :banner="fb" /></template>
              </BannerSlot>
            </div>
          </aside>

          <!-- article -->
          <article class="min-w-0">
            <span class="text-[11px] font-bold uppercase tracking-widest text-rose-600">Culture</span>
            <div class="mt-2 h-8 w-11/12 rounded bg-zinc-800/90" />
            <div class="mt-2.5 h-8 w-3/5 rounded bg-zinc-800/90" />
            <div class="mt-4 flex items-center gap-3">
              <span class="h-8 w-8 rounded-full bg-zinc-200" />
              <span class="h-2.5 w-48 rounded bg-zinc-200" />
            </div>
            <div class="hero mt-6 aspect-[16/8] w-full rounded-lg" />
            <div class="mt-7 space-y-3">
              <div v-for="(w, i) in para(5, 0)" :key="`a${i}`" class="h-3 rounded bg-zinc-200" :style="{ width: `${w}%` }" />
            </div>

            <template v-for="blk in blocks" :key="blk.banner.id">
              <div class="my-8 flex justify-center">
                <BannerSlot :banner="blk.banner" tools>
                  <template #footer="{ banner: fb }"><slot name="footer" :banner="fb" /></template>
                </BannerSlot>
              </div>
              <div class="space-y-3">
                <div v-for="(w, i) in blk.lines" :key="i" class="h-3 rounded bg-zinc-200" :style="{ width: `${w}%` }" />
              </div>
            </template>

            <div class="mt-7 space-y-3">
              <div v-for="(w, i) in para(7, 3)" :key="`b${i}`" class="h-3 rounded bg-zinc-200" :style="{ width: `${w}%` }" />
            </div>
            <div class="mt-8 grid grid-cols-3 gap-5">
              <div v-for="n in 3" :key="n">
                <div class="aspect-video rounded-md bg-zinc-200" />
                <div class="mt-2 h-3 w-11/12 rounded bg-zinc-300" />
                <div class="mt-1.5 h-3 w-2/3 rounded bg-zinc-300" />
              </div>
            </div>
          </article>

          <!-- right rail -->
          <aside class="space-y-8">
            <BannerSlot v-for="b in slots.rail" :key="b.id" :banner="b" tools>
              <template #footer="{ banner: fb }"><slot name="footer" :banner="fb" /></template>
            </BannerSlot>
            <div class="rounded-lg border border-zinc-200 p-4">
              <div class="mb-3 h-3.5 w-24 rounded bg-zinc-800/80" />
              <div v-for="n in 5" :key="n" class="flex gap-3 border-t border-zinc-100 py-3 first:border-0">
                <span class="text-[20px] font-black text-zinc-300">{{ n }}</span>
                <span class="flex-1 space-y-1.5 pt-1">
                  <span class="block h-2.5 w-full rounded bg-zinc-200" />
                  <span class="block h-2.5 w-3/4 rounded bg-zinc-200" />
                </span>
              </div>
            </div>
          </aside>
        </div>

        <!-- footer slot -->
        <div v-if="slots.footer" class="flex justify-center border-t border-zinc-100 bg-zinc-50 py-5">
          <BannerSlot :banner="slots.footer" tools>
            <template #footer="{ banner: fb }"><slot name="footer" :banner="fb" /></template>
          </BannerSlot>
        </div>
        <div class="flex gap-10 bg-zinc-900 px-10 py-8">
          <span class="text-[18px] font-black text-white">The Daily Example</span>
          <span v-for="n in 4" :key="n" class="mt-1 space-y-2">
            <span class="block h-2 w-24 rounded bg-white/20" />
            <span class="block h-2 w-16 rounded bg-white/10" />
          </span>
        </div>
      </div>
    </BrowserChrome>
  </ScaledBox>

  <!-- sizes that do not run on this desktop page -->
  <div v-for="grp in offPage" :key="grp.key" class="mt-8">
    <p class="mb-3 text-center text-[11px] font-medium uppercase tracking-[0.18em] text-[var(--p2-text-subtle)]">{{ grp.title }}</p>
    <div class="flex flex-wrap items-end justify-center gap-6 rounded-2xl bg-white p-5">
      <BannerSlot v-for="b in grp.list" :key="b.id" :banner="b" tools>
        <template #footer="{ banner: fb }"><slot name="footer" :banner="fb" /></template>
      </BannerSlot>
    </div>
  </div>
</template>

<style scoped>
.hero {
  background:
    radial-gradient(60% 80% at 30% 30%, rgb(255 255 255 / 0.5), transparent 60%),
    linear-gradient(135deg, #cbd5e1, #94a3b8);
}
</style>
