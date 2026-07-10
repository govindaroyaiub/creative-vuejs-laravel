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
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div class="min-w-0">
        <div
          class="flex items-center gap-2"
          :style="{ color: 'var(--p2-accent)' }"
        >
          <component :is="typeCopy.icon" class="h-3 w-3" />
          <span class="p2-label" :style="{ color: 'var(--p2-accent)' }">{{ typeCopy.label }}</span>
        </div>
        <h1 class="mt-2 truncate text-3xl font-semibold leading-tight tracking-tight text-[var(--p2-text)] md:text-4xl">
          {{ activeCategory?.name || 'Loading…' }}
        </h1>
        <p v-if="!isLoading && totalAssets" class="mt-1 text-sm text-[var(--p2-text-muted)]">
          <span
            class="p2-mono font-semibold"
            :style="{ color: 'var(--p2-accent)' }"
          >{{ totalAssets }}</span>
          {{ typeCopy.plural }} in this round
        </p>
      </div>

      <!-- Revision Notes -->
      <button
        v-if="!isLoading && hasNotes"
        type="button"
        class="notes-btn inline-flex h-10 items-center gap-2 rounded-full border px-4 text-sm font-medium text-[var(--p2-text)] backdrop-blur-md transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-accent)]"
        :style="{ background: 'var(--p2-surface-muted)' }"
        data-tour="notes"
        @click="$emit('open-notes')"
      >
        <MessageSquare class="h-4 w-4" />
        <span>Revision Notes</span>
      </button>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="space-y-8">
      <div>
        <div
          class="mb-4 h-5 w-48 animate-pulse rounded"
          :style="{ background: 'var(--p2-hairline)' }"
        />
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
            class="overflow-hidden rounded-2xl border bg-[var(--p2-surface)]"
            :style="{ borderColor: 'var(--p2-border)' }"
          >
            <div class="h-9 border-b px-4 py-2" :style="{ borderColor: 'var(--p2-hairline)' }">
              <div
                class="h-3 w-24 animate-pulse rounded"
                :style="{ background: 'var(--p2-hairline)' }"
              />
            </div>
            <div
              class="animate-pulse"
              :style="{
                width: categoryType === 'video' ? '100%' : (200 + (n * 40)) + 'px',
                height: categoryType === 'video' ? '360px' : (160 + ((n % 3) * 40)) + 'px',
                background: 'var(--p2-hairline)',
              }"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="!feedbackSets.length || !totalAssets"
      class="rounded-3xl border border-dashed bg-[var(--p2-surface-muted)] px-6 py-16 text-center backdrop-blur-md"
      :style="{ borderColor: 'var(--p2-accent-muted)' }"
    >
      <component
        :is="typeCopy.icon"
        class="mx-auto h-10 w-10"
        :style="{ color: 'var(--p2-accent)' }"
      />
      <p class="mt-3 text-base font-medium text-[var(--p2-text)]">No {{ typeCopy.plural }} here yet</p>
      <p class="mt-1 text-sm text-[var(--p2-text-muted)]">Your team is still working on this revision round.</p>
    </div>

    <!-- Versions (feedback sets) -->
    <div v-else class="space-y-12">
      <article
        v-for="(set, sIdx) in feedbackSets"
        :key="set.id"
        :data-tour="sIdx === 0 ? 'version-set' : undefined"
      >
        <header v-if="set.name" class="mb-5 flex items-center gap-3">
          <span
            class="grid h-9 w-9 place-items-center rounded-full text-sm font-semibold text-white shadow-sm"
            :style="{ background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }"
          >
            {{ String.fromCharCode(65 + sIdx) }}
          </span>
          <div>
            <p class="p2-label" :style="{ color: 'var(--p2-accent)' }">Version</p>
            <p class="mt-0.5 text-base font-semibold tracking-tight text-[var(--p2-text)]">{{ set.name }}</p>
          </div>
          <div
            aria-hidden="true"
            class="ml-auto h-px flex-1"
            :style="{ background: 'var(--p2-border-strong)' }"
          />
        </header>

        <div class="space-y-6">
          <section v-for="(version, vIdx) in set.versions" :key="version.id">
            <header
              v-if="version.name"
              class="mb-3 flex items-center gap-3"
            >
              <span
                class="h-1.5 w-1.5 shrink-0 rounded-full"
                :style="{ background: 'var(--p2-accent)' }"
              />
              <h3 class="text-sm font-medium text-[var(--p2-text-muted)]">
                {{ version.name }}
              </h3>
              <div
                aria-hidden="true"
                class="ml-1 h-px flex-1"
                :style="{ background: 'var(--p2-border-strong)' }"
              />
            </header>

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
  transition: color 200ms var(--p2-ease-expo);
}
.notes-btn:hover {
  border-color: var(--p2-accent);
}
</style>
