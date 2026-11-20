<script setup lang="ts">
import { computed, inject, nextTick, ref, type Component } from 'vue'
import { ImageIcon, Film, Share2, Sparkles, MessageSquare, ChevronDown, ChevronsDownUp, ChevronsUpDown } from 'lucide-vue-next'
import BannerCard from './BannerCard.vue'
import VideoCard from './VideoCard.vue'
import SocialCard from './SocialCard.vue'
import GifCard from './GifCard.vue'

const props = defineProps<{
  feedbackSets: any[]
  activeCategory: any
  activeFeedback: any
  /** No data yet at all (only when the page was rendered without a payload). */
  isLoading: boolean
  /** A category / round switch is in flight; the current content stays up, dimmed. */
  isSwitching?: boolean
}>()

defineEmits<{
  (e: 'open-notes'): void
}>()

/**
 * Optional row under each creative, provided by a host page (the review-mode
 * prototype provides one for comments / approval). Show2 provides none, so
 * nothing renders and the layout is unchanged.
 */
const creativeFooter = inject<Component | null>('creativeFooter', null)
/**
 * Optional replacement for the video grid (the review prototype's in-context
 * showcase). Show2 provides none and keeps its own grid.
 */
const videoShowcase = inject<Component | null>('videoShowcase', null)
/** Same for social images (review page's in-context frames). */
const socialShowcase = inject<Component | null>('socialShowcase', null)
const footerContext = (set: any, version: any, sIdx: number, vIdx: number) => ({
  category: props.activeCategory,
  feedback: props.activeFeedback,
  feedbackSets: props.feedbackSets,
  set,
  version,
  sIdx,
  vIdx,
})

const categoryType = computed(() => props.activeCategory?.type)

const hasNotes = computed(() => Boolean(props.activeFeedback?.description?.trim()))

const typeCopy = computed(() => {
  switch (categoryType.value) {
    case 'banner':
      return { icon: ImageIcon, label: 'Banner', singular: 'banner', plural: 'banners' }
    case 'video':
      return { icon: Film, label: 'Video', singular: 'video', plural: 'videos' }
    case 'social':
      return { icon: Share2, label: 'Social', singular: 'social post', plural: 'social posts' }
    case 'gif':
      return { icon: Sparkles, label: 'GIF', singular: 'GIF', plural: 'GIFs' }
    default:
      return { icon: ImageIcon, label: 'Asset', singular: 'asset', plural: 'assets' }
  }
})

const countLabel = (n: number) => `${n} ${n === 1 ? typeCopy.value.singular : typeCopy.value.plural}`

const totalAssets = computed(() => {
  let n = 0
  for (const s of props.feedbackSets || []) {
    for (const v of s.versions || []) {
      n += (v.assets || []).length
    }
  }
  return n
})

// ---------------------------------------------------------------------------
// Collapsible Sets
//
// A round can hold many Sets, which made the page very long. Each Set can now
// be folded to its header. The choice is remembered per viewer in this
// browser (guests have no account to store it against), keyed by the Set's
// id, so it survives reloads and coming back days later.
//
// Nothing stored yet for a Set -> the newest Set in each Version is open and
// the older ones are folded, so the page opens short with the latest work in
// view.
// ---------------------------------------------------------------------------
const COLLAPSE_KEY = 'show2-set-collapse'

const readCollapse = (): Record<string, boolean> => {
  try {
    const raw = localStorage.getItem(COLLAPSE_KEY)
    const parsed = raw ? JSON.parse(raw) : {}
    return parsed && typeof parsed === 'object' ? parsed : {}
  } catch {
    return {}
  }
}

const collapseState = ref<Record<string, boolean>>(typeof window === 'undefined' ? {} : readCollapse())

const persistCollapse = () => {
  try {
    localStorage.setItem(COLLAPSE_KEY, JSON.stringify(collapseState.value))
  } catch {
    /* private mode / storage full: state still works for this visit */
  }
}

const hasName = (row: any): boolean => Boolean(String(row?.name ?? '').trim())

/**
 * Folding only makes sense when a Version holds more than one Set. A lone Set
 * folded away would leave the round looking empty, so it is always open and
 * gets no toggle.
 */
const canFold = (set: any): boolean => (set.versions || []).length > 1

const isCollapsed = (set: any, version: any): boolean => {
  if (!canFold(set)) return false
  const stored = collapseState.value[version.id]
  if (stored !== undefined) return stored
  const versions = set.versions || []
  return versions.length > 1 && versions[versions.length - 1]?.id !== version.id
}

const setCollapsed = (versionIds: number[], value: boolean) => {
  const next = { ...collapseState.value }
  for (const id of versionIds) next[id] = value
  collapseState.value = next
  persistCollapse()
}

/**
 * Opening a Set brings it to the top of the screen, so its creatives are right
 * there instead of somewhere below the fold. Only on open: closing leaves the
 * scroll alone, and "Expand all" does not jump anywhere.
 */
const toggleVersion = async (set: any, version: any) => {
  const opening = isCollapsed(set, version)
  setCollapsed([version.id], !opening)
  if (!opening) return
  await nextTick()
  const el = document.getElementById(`set-${version.id}`)
  if (!el) return
  const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  el.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' })
}

const allVersionPairs = computed(() =>
  (props.feedbackSets || [])
    .filter(canFold)
    .flatMap((set) => (set.versions || []).map((version: any) => ({ set, version }))),
)
const anyExpanded = computed(() => allVersionPairs.value.some(({ set, version }) => !isCollapsed(set, version)))
const toggleAll = () =>
  setCollapsed(
    allVersionPairs.value.map(({ version }) => version.id),
    anyExpanded.value,
  )

/** Sizes in a folded Set, so the header still says what is inside. */
const sizeSummary = (version: any): string => {
  const seen: string[] = []
  for (const a of version.assets || []) {
    const w = a?.size?.width ?? a?.width
    const h = a?.size?.height ?? a?.height
    if (!w || !h) continue
    const key = `${w}×${h}`
    if (!seen.includes(key)) seen.push(key)
  }
  if (seen.length > 4) return `${seen.slice(0, 4).join(' · ')} +${seen.length - 4}`
  return seen.join(' · ')
}
</script>

<template>
  <section :aria-busy="isLoading || isSwitching">
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

      <div class="flex items-center gap-2">
        <!-- Expand / collapse every Set at once -->
        <button
          v-if="!isLoading && allVersionPairs.length"
          type="button"
          class="set-all-btn inline-flex h-10 items-center gap-2 rounded-full border px-4 text-sm font-medium text-[var(--p2-text)] backdrop-blur-md transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-accent)]"
          :style="{ background: 'var(--p2-surface-muted)' }"
          @click="toggleAll"
        >
          <component :is="anyExpanded ? ChevronsDownUp : ChevronsUpDown" class="h-4 w-4" />
          <span>{{ anyExpanded ? 'Collapse all' : 'Expand all' }}</span>
        </button>

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
    </div>

    <!-- Switching: thin progress line. The current cards stay in place
         (dimmed) instead of being swapped for invented placeholders, and the
         new ones arrive already sized from the payload. -->
    <div
      v-if="isSwitching"
      class="switch-bar mb-4 h-0.5 overflow-hidden rounded-full"
      :style="{ background: 'var(--p2-hairline)' }"
      aria-hidden="true"
    >
      <div class="switch-bar-fill h-full w-1/3 rounded-full" :style="{ background: 'var(--p2-accent)' }" />
    </div>

    <!-- No data yet: headers only. The real layout depends on counts and
         sizes we do not have, so no fake asset boxes are drawn. -->
    <div v-if="isLoading" class="space-y-6">
      <div v-for="n in 3" :key="n" class="flex items-center gap-3">
        <div class="p2-shimmer h-9 w-9 rounded-full" />
        <div class="p2-shimmer h-4 w-40 rounded" />
        <div class="h-px flex-1" :style="{ background: 'var(--p2-hairline)' }" />
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
    <div
      v-else
      class="space-y-12 transition-opacity duration-200"
      :class="isSwitching ? 'pointer-events-none opacity-50' : ''"
    >
      <article
        v-for="(set, sIdx) in feedbackSets"
        :key="set.id"
        :data-tour="sIdx === 0 ? 'version-set' : undefined"
      >
        <!-- Only a named Version gets a header; no invented "Version A". -->
        <header v-if="hasName(set)" class="mb-5 flex items-center gap-3">
          <span
            class="grid h-9 w-9 place-items-center rounded-full text-sm font-semibold text-white shadow-sm"
            :style="{ background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }"
          >
            {{ String.fromCharCode(65 + sIdx) }}
          </span>
          <div>
            <p class="p2-label" :style="{ color: 'var(--p2-accent)' }">Version</p>
            <p class="mt-0.5 text-base font-semibold tracking-tight text-[var(--p2-text)]">
              {{ set.name }}
            </p>
          </div>
          <div
            aria-hidden="true"
            class="ml-auto h-px flex-1"
            :style="{ background: 'var(--p2-border-strong)' }"
          />
        </header>

        <div class="space-y-3">
          <section
            v-for="(version, vIdx) in set.versions"
            :id="`set-${version.id}`"
            :key="version.id"
            class="scroll-mt-4"
          >
            <!-- Lone Set: no toggle. Its name, if it has one, as a plain label. -->
            <h3
              v-if="!canFold(set) && hasName(version)"
              class="flex items-center gap-3 py-2"
            >
              <span class="h-1.5 w-1.5 shrink-0 rounded-full" :style="{ background: 'var(--p2-accent)' }" />
              <span class="text-sm font-medium text-[var(--p2-text)]">{{ version.name }}</span>
              <span
                aria-hidden="true"
                class="ml-1 h-px min-w-6 flex-1"
                :style="{ background: 'var(--p2-border-strong)' }"
              />
            </h3>

            <!-- Two or more Sets: each gets a toggle row (the whole row is the
                 button). A Set saved without a name shows just its count and
                 sizes, never an invented name. -->
            <h3 v-else-if="canFold(set)">
              <button
                type="button"
                class="set-toggle group/set flex w-full items-center gap-3 rounded-lg py-2 text-left"
                :aria-expanded="!isCollapsed(set, version)"
                :aria-controls="`set-panel-${version.id}`"
                @click="toggleVersion(set, version)"
              >
                <ChevronDown
                  class="h-4 w-4 shrink-0 text-[var(--p2-text-muted)] transition-transform duration-300 ease-p2-expo group-hover/set:text-[var(--p2-accent)]"
                  :class="isCollapsed(set, version) ? '-rotate-90' : ''"
                />
                <span v-if="hasName(version)" class="text-sm font-medium text-[var(--p2-text)]">
                  {{ version.name }}
                </span>
                <span v-else class="sr-only">Set {{ vIdx + 1 }}</span>
                <span
                  class="p2-mono text-[11px] tabular-nums"
                  :class="hasName(version) ? 'text-[var(--p2-text-muted)]' : 'text-[var(--p2-text)]'"
                >
                  {{ countLabel((version.assets || []).length) }}
                </span>
                <span
                  v-if="(isCollapsed(set, version) || !hasName(version)) && sizeSummary(version)"
                  class="p2-mono hidden truncate text-[11px] tabular-nums text-[var(--p2-text-subtle)] sm:inline"
                >
                  {{ sizeSummary(version) }}
                </span>
                <span
                  aria-hidden="true"
                  class="ml-1 h-px min-w-6 flex-1"
                  :style="{ background: 'var(--p2-border-strong)' }"
                />
              </button>
            </h3>

            <!-- Folded Sets are not rendered at all, so their iframes,
                 images and videos never load until opened. -->
            <Transition name="set-panel">
              <div
                v-if="!isCollapsed(set, version)"
                :id="`set-panel-${version.id}`"
                class="pb-6 pt-2"
              >
                <!-- Banners -->
                <div
                  v-if="categoryType === 'banner'"
                  class="flex flex-wrap items-end gap-5"
                  :data-tour="sIdx === 0 && vIdx === 0 ? 'assets' : undefined"
                >
                  <div
                    v-for="(banner, bIdx) in version.assets"
                    :key="banner.id"
                    class="flex max-w-full flex-col"
                  >
                    <BannerCard :banner="banner" :index="bIdx" />
                    <component
                      :is="creativeFooter"
                      v-if="creativeFooter"
                      :asset="banner"
                      :type="categoryType"
                      :context="footerContext(set, version, sIdx, vIdx)"
                    />
                  </div>
                </div>

                <!-- GIFs -->
                <div
                  v-else-if="categoryType === 'gif'"
                  class="flex flex-wrap items-end gap-5"
                  :data-tour="sIdx === 0 && vIdx === 0 ? 'assets' : undefined"
                >
                  <div
                    v-for="gif in version.assets"
                    :key="gif.id"
                    class="flex max-w-full flex-col"
                  >
                    <GifCard :gif="gif" />
                    <component
                      :is="creativeFooter"
                      v-if="creativeFooter"
                      :asset="gif"
                      :type="categoryType"
                      :context="footerContext(set, version, sIdx, vIdx)"
                    />
                  </div>
                </div>

                <!-- Videos: host page's showcase (review prototype) -->
                <component
                  :is="videoShowcase"
                  v-else-if="categoryType === 'video' && videoShowcase"
                  :videos="version.assets"
                  :data-tour="sIdx === 0 && vIdx === 0 ? 'assets' : undefined"
                >
                  <template #footer="{ video }">
                    <component
                      :is="creativeFooter"
                      v-if="creativeFooter"
                      :asset="video"
                      :type="categoryType"
                      :context="footerContext(set, version, sIdx, vIdx)"
                    />
                  </template>
                </component>

                <!-- Videos -->
                <div
                  v-else-if="categoryType === 'video'"
                  class="grid grid-cols-1 gap-6"
                  :data-tour="sIdx === 0 && vIdx === 0 ? 'assets' : undefined"
                >
                  <div
                    v-for="video in version.assets"
                    :key="video.id"
                    class="flex max-w-full flex-col"
                  >
                    <VideoCard :video="video" />
                    <component
                      :is="creativeFooter"
                      v-if="creativeFooter"
                      :asset="video"
                      :type="categoryType"
                      :context="footerContext(set, version, sIdx, vIdx)"
                    />
                  </div>
                </div>

                <!-- Socials: host page's showcase (review page) -->
                <component
                  :is="socialShowcase"
                  v-else-if="categoryType === 'social' && socialShowcase"
                  :socials="version.assets"
                  :data-tour="sIdx === 0 && vIdx === 0 ? 'assets' : undefined"
                >
                  <template #footer="{ social }">
                    <component
                      :is="creativeFooter"
                      v-if="creativeFooter"
                      :asset="social"
                      :type="categoryType"
                      :context="footerContext(set, version, sIdx, vIdx)"
                    />
                  </template>
                </component>

                <!-- Socials -->
                <div
                  v-else-if="categoryType === 'social'"
                  class="grid grid-cols-1 items-start gap-5 sm:grid-cols-2 xl:grid-cols-3"
                  :data-tour="sIdx === 0 && vIdx === 0 ? 'assets' : undefined"
                >
                  <div
                    v-for="social in version.assets"
                    :key="social.id"
                    class="flex max-w-full flex-col"
                  >
                    <SocialCard :social="social" />
                    <component
                      :is="creativeFooter"
                      v-if="creativeFooter"
                      :asset="social"
                      :type="categoryType"
                      :context="footerContext(set, version, sIdx, vIdx)"
                    />
                  </div>
                </div>
              </div>
            </Transition>
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
.set-all-btn {
  border-color: var(--p2-border);
}
.set-all-btn:hover {
  border-color: var(--p2-border-strong);
}

.set-panel-enter-active,
.set-panel-leave-active {
  transition: opacity 200ms var(--p2-ease-expo), transform 200ms var(--p2-ease-expo);
}
.set-panel-enter-from,
.set-panel-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.switch-bar-fill {
  animation: switch-slide 1s var(--p2-ease-cinema) infinite;
}
@keyframes switch-slide {
  from { transform: translateX(-100%); }
  to   { transform: translateX(300%); }
}
</style>
