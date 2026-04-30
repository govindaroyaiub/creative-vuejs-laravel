<script setup lang="ts">
import { computed } from 'vue'
import { ImageIcon, Film, Share2, Sparkles, MessageSquare } from 'lucide-vue-next'
import BannerCard from './BannerCard.vue'
import VideoCard from './VideoCard.vue'
import SocialCard from './SocialCard.vue'
import GifCard from './GifCard.vue'

const props = defineProps<{
  feedbackSets: any[]
  activeCategory: any
  activeFeedback: any
  isLoading: boolean
}>()

defineEmits<{
  (e: 'open-notes'): void
}>()

const categoryType = computed(() => props.activeCategory?.type)

const hasNotes = computed(() => Boolean(props.activeFeedback?.description?.trim()))

const typeCopy = computed(() => {
  switch (categoryType.value) {
    case 'banner':
      return { icon: ImageIcon, label: 'Banner', plural: 'banners' }
    case 'video':
      return { icon: Film, label: 'Video', plural: 'videos' }
    case 'social':
      return { icon: Share2, label: 'Social', plural: 'social posts' }
    case 'gif':
      return { icon: Sparkles, label: 'GIF', plural: 'GIFs' }
    default:
      return { icon: ImageIcon, label: 'Asset', plural: 'assets' }
  }
})

const totalAssets = computed(() => {
  let n = 0
  for (const s of props.feedbackSets || []) {
    for (const v of s.versions || []) {
      n += (v.assets || []).length
    }
  }
  return n
})

const skeletonCount = computed(() => {
  switch (categoryType.value) {
    case 'video':
      return 2
    case 'social':
      return 4
    default:
      return 6
  }
})
</script>

<template>
  <section>
    <!-- Category header -->
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <div class="min-w-0">
        <div
          class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.12em]"
          :style="{ color: 'var(--p2-accent)' }"
        >
          <component :is="typeCopy.icon" class="h-3 w-3" />
          {{ typeCopy.label }}
        </div>
        <h1 class="mt-1 truncate text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
          {{ activeCategory?.name || 'Loading…' }}
        </h1>
        <p v-if="!isLoading && totalAssets" class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
          <span
            class="font-semibold"
            :style="{ color: 'var(--p2-accent)' }"
          >{{ totalAssets }}</span>
          {{ typeCopy.plural }} in this round
        </p>
      </div>

      <!-- Revision Notes -->
      <button
        v-if="!isLoading && hasNotes"
        type="button"
        class="notes-btn inline-flex items-center gap-2 rounded-lg border bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 shadow-sm transition dark:bg-zinc-900 dark:text-zinc-200"
        data-tour="notes"
        @click="$emit('open-notes')"
      >
        <MessageSquare class="h-4 w-4 transition" />
        <span>Revision Notes</span>
      </button>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="space-y-8">
      <div>
        <div class="mb-4 h-5 w-48 animate-pulse rounded bg-zinc-100 dark:bg-zinc-800" />
        <div
          :class="[
            categoryType === 'video' ? 'grid grid-cols-1 gap-6' :
            categoryType === 'social' ? 'grid grid-cols-1 gap-5 sm:grid-cols-2' :
            'flex flex-wrap gap-4',
          ]"
        >
          <div
            v-for="n in skeletonCount"
            :key="n"
            class="overflow-hidden rounded-2xl border border-zinc-100 bg-white dark:border-zinc-800 dark:bg-zinc-900"
          >
            <div class="h-9 border-b border-zinc-100 px-4 py-2 dark:border-zinc-800">
              <div class="h-3 w-24 animate-pulse rounded bg-zinc-100 dark:bg-zinc-800" />
            </div>
            <div
              class="animate-pulse bg-zinc-100 dark:bg-zinc-800"
              :style="{
                width: categoryType === 'video' ? '100%' : (200 + (n * 40)) + 'px',
                height: categoryType === 'video' ? '360px' : (160 + ((n % 3) * 40)) + 'px',
              }"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="!feedbackSets.length || !totalAssets"
      class="rounded-2xl border border-dashed bg-white px-6 py-16 text-center dark:bg-zinc-900"
      :style="{ borderColor: 'var(--p2-accent-muted)' }"
    >
      <component
        :is="typeCopy.icon"
        class="mx-auto h-10 w-10"
        :style="{ color: 'var(--p2-accent)' }"
      />
      <p class="mt-3 text-base font-medium text-zinc-700 dark:text-zinc-200">No {{ typeCopy.plural }} here yet</p>
      <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Your team is still working on this revision round.</p>
    </div>

    <!-- Versions (feedback sets) -->
    <div v-else class="space-y-10">
      <article
        v-for="(set, sIdx) in feedbackSets"
        :key="set.id"
        :data-tour="sIdx === 0 ? 'version-set' : undefined"
      >
        <header v-if="set.name" class="mb-4 flex items-center gap-3">
          <span
            class="grid h-8 w-8 place-items-center rounded-full text-xs font-semibold text-white shadow-sm"
            :style="{ background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }"
          >
            {{ String.fromCharCode(65 + sIdx) }}
          </span>
          <div>
            <div
              class="text-[11px] font-semibold uppercase tracking-[0.12em]"
              :style="{ color: 'var(--p2-accent)' }"
            >Version</div>
            <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ set.name }}</div>
          </div>
          <div class="ml-auto h-px flex-1 bg-gradient-to-r from-[var(--p2-accent-soft)] to-transparent" />
        </header>

        <div class="space-y-6">
          <section v-for="(version, vIdx) in set.versions" :key="version.id">
            <h3
              v-if="version.name"
              class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-300"
            >
              <span
                class="h-1.5 w-1.5 rounded-full"
                :style="{ background: 'var(--p2-accent)' }"
              />
              {{ version.name }}
            </h3>

            <!-- Banners -->
            <div
              v-if="categoryType === 'banner'"
              class="flex flex-wrap items-end gap-5"
              :data-tour="sIdx === 0 && vIdx === 0 ? 'assets' : undefined"
            >
              <BannerCard
                v-for="(banner, bIdx) in version.assets"
                :key="banner.id"
                :banner="banner"
                :index="bIdx"
              />
            </div>

            <!-- GIFs -->
            <div
              v-else-if="categoryType === 'gif'"
              class="flex flex-wrap items-end gap-5"
              :data-tour="sIdx === 0 && vIdx === 0 ? 'assets' : undefined"
            >
              <GifCard
                v-for="gif in version.assets"
                :key="gif.id"
                :gif="gif"
              />
            </div>

            <!-- Videos -->
            <div
              v-else-if="categoryType === 'video'"
              class="grid grid-cols-1 gap-6"
              :data-tour="sIdx === 0 && vIdx === 0 ? 'assets' : undefined"
            >
              <VideoCard
                v-for="video in version.assets"
                :key="video.id"
                :video="video"
              />
            </div>

            <!-- Socials -->
            <div
              v-else-if="categoryType === 'social'"
              class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3"
              :data-tour="sIdx === 0 && vIdx === 0 ? 'assets' : undefined"
            >
              <SocialCard
                v-for="social in version.assets"
                :key="social.id"
                :social="social"
              />
            </div>
          </section>
        </div>
      </article>
    </div>
  </section>
</template>

<style scoped>
.notes-btn {
  border-color: var(--p2-accent-muted);
}
.notes-btn svg {
  color: var(--p2-accent);
}
.notes-btn:hover {
  background: linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%);
  border-color: transparent;
  color: white;
}
.notes-btn:hover svg {
  color: white;
}
</style>
