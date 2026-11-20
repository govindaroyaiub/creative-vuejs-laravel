<script setup lang="ts">
/**
 * DEMO ONLY: full-screen review of one creative.
 *  - stage: the creative at real size (scaled down to fit)
 *  - timeline: the same creative in earlier rounds
 *  - compare: side by side with an earlier round or another creative,
 *    with "replay both" for banners/GIFs, synced play for video and a
 *    drag slider for images
 *  - right panel: approve / request changes, and the comment thread
 */
import { computed, inject, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import {
  X, ChevronLeft, ChevronRight, Columns2, RotateCcw, Play, Pause, Check, CircleAlert, SplitSquareHorizontal, ArrowLeftRight,
} from 'lucide-vue-next'
import AssetStage from './AssetStage.vue'
import CommentThread from './CommentThread.vue'
import { sizeLabel, type LineageEntry, type ReviewDemo } from './useReviewDemo'

const demo = inject<ReviewDemo>('reviewDemo')!
const type = computed(() => demo.view.type)

const isOpen = computed(() => demo.view.reviewing !== null)
const items = computed(() => demo.view.round)
const index = computed(() => items.value.findIndex((i) => i.assetKey === demo.view.reviewing))
const current = computed(() => items.value[index.value] ?? null)

/** Same creative across rounds, oldest first. Falls back to just this one. */
const lineage = computed<LineageEntry[]>(() => {
  if (!current.value) return []
  const l = demo.lineageOf(current.value.assetKey)
  return l.length ? l : [current.value]
})
const currentEntry = computed(() => lineage.value.find((e) => e.assetKey === current.value?.assetKey) ?? current.value)

/** Which round of this creative is on stage (defaults to the current one). */
const viewingKey = ref<string | null>(null)
const viewing = computed(() => lineage.value.find((e) => e.assetKey === viewingKey.value) ?? currentEntry.value)
const isViewingCurrent = computed(() => viewing.value?.assetKey === current.value?.assetKey)

// ---- Compare ------------------------------------------------------------
const comparing = ref(false)
const compareKey = ref<string | null>(null)
const sliderMode = ref(false)
const slider = ref(50)
const replayKey = ref(0)
const leftStage = ref<InstanceType<typeof AssetStage> | null>(null)
const rightStage = ref<InstanceType<typeof AssetStage> | null>(null)
const mainStage = ref<InstanceType<typeof AssetStage> | null>(null)

/**
 * Compare is round against round, "this vs this". Each side picks a round,
 * then any creative in it; the creative matching the one under review is
 * pre-selected and marked suggested. The two sides never share a round.
 */
const leftRoundId = ref<number | null>(null)
const leftKey = ref<string | null>(null)
const compareRoundId = ref<number | null>(null)

const allRounds = computed(() =>
  current.value ? demo.roundsOf(current.value.assetKey).filter((r) => r.entries.length) : [],
)

/** Rounds the right side may use (never the left side's round). */
const compareRounds = computed(() => {
  const exclude = comparing.value ? leftRoundId.value : viewing.value?.roundId
  return allRounds.value.filter((r) => r.roundId !== exclude)
})

const suggestedIn = (roundId: number | null) => lineage.value.find((e) => e.roundId === roundId) ?? null

const creativesIn = (roundId: number | null) => {
  const round = allRounds.value.find((r) => r.roundId === roundId)
  if (!round) return []
  const suggested = suggestedIn(round.roundId)?.assetKey
  return round.entries.map((e) => ({
    key: e.assetKey,
    label: `${sizeLabel(e.asset)} · ${placeLabel(e)}${e.assetKey === suggested ? '  (suggested)' : ''}`,
    entry: e,
  }))
}

const leftCreatives = computed(() => creativesIn(leftRoundId.value))
const compareCreatives = computed(() => creativesIn(compareRoundId.value))

const leftEntry = computed(() => leftCreatives.value.find((o) => o.key === leftKey.value)?.entry ?? viewing.value)
const compareEntry = computed(() => compareCreatives.value.find((o) => o.key === compareKey.value)?.entry ?? null)

const firstKeyIn = (roundId: number | null) =>
  (suggestedIn(roundId) ?? allRounds.value.find((r) => r.roundId === roundId)?.entries[0])?.assetKey ?? null

const pickRound = (roundId: number | null) => {
  compareRoundId.value = roundId
  compareKey.value = firstKeyIn(roundId)
}

/** Default right side: the round just before the left one, else the latest other one. */
const pickDefaultRight = () => {
  const order = allRounds.value.map((r) => r.roundId)
  const leftIdx = order.indexOf(leftRoundId.value as number)
  const others = compareRounds.value
  const before = others.filter((r) => order.indexOf(r.roundId) < leftIdx)
  const target = before[before.length - 1] ?? others[others.length - 1]
  pickRound(target?.roundId ?? null)
}

const pickLeftRound = (roundId: number | null, key?: string | null) => {
  leftRoundId.value = roundId
  leftKey.value = key ?? firstKeyIn(roundId)
  if (compareRoundId.value === roundId) pickDefaultRight()
}

const swapSides = () => {
  const [lr, lk, rr, rk] = [leftRoundId.value, leftKey.value, compareRoundId.value, compareKey.value]
  leftRoundId.value = rr
  leftKey.value = rk
  compareRoundId.value = lr
  compareKey.value = lk
}

/**
 * History chips. Outside compare they pick the round on stage. In compare they
 * mirror both sides (Left / Right tags) and clicking one moves the left side
 * there; clicking the right side's round swaps the sides.
 */
const chipSide = (e: LineageEntry): 'left' | 'right' | null => {
  if (!comparing.value) return null
  if (e.roundId === leftRoundId.value) return 'left'
  if (e.roundId === compareRoundId.value) return 'right'
  return null
}
const chipActive = (e: LineageEntry) => (comparing.value ? chipSide(e) === 'left' : e.assetKey === viewing.value?.assetKey)
const onChip = (e: LineageEntry) => {
  if (!comparing.value) {
    viewingKey.value = e.assetKey
    return
  }
  if (e.roundId === leftRoundId.value) return
  if (e.roundId === compareRoundId.value) swapSides()
  else pickLeftRound(e.roundId, e.assetKey)
}

/** Leaving compare keeps the left side on stage when it is this creative's history. */
const endCompare = () => {
  if (lineage.value.some((e) => e.assetKey === leftKey.value)) viewingKey.value = leftKey.value
  comparing.value = false
}

const startCompare = () => {
  if (!compareRounds.value.length || !viewing.value) return
  leftRoundId.value = viewing.value.roundId
  leftKey.value = viewing.value.assetKey
  comparing.value = true
  pickDefaultRight()
}

const replayBoth = () => {
  replayKey.value++
}
const videos = () =>
  [leftStage.value?.videoEl, rightStage.value?.videoEl].filter(Boolean) as HTMLVideoElement[]
const playBoth = () => videos().forEach((v) => { v.currentTime = 0; v.play().catch(() => {}) })
const pauseBoth = () => videos().forEach((v) => v.pause())

// ---- Labels -------------------------------------------------------------
function placeLabel(e: LineageEntry) {
  const parts = [] as string[]
  if (e.setName) parts.push(e.setName)
  if (e.versionName) parts.push(e.versionName)
  return parts.join(' · ') || `Set ${e.vIdx + 1}`
}

// ---- Navigation ---------------------------------------------------------
const go = (delta: number) => {
  const n = items.value.length
  if (!n) return
  const next = items.value[(index.value + delta + n) % n]
  if (next) demo.openReview(next.assetKey)
}

watch(() => demo.view.reviewing, () => {
  viewingKey.value = null
  comparing.value = false
  sliderMode.value = false
  slider.value = 50
})

const onKey = (e: KeyboardEvent) => {
  if (!isOpen.value) return
  const t = e.target as HTMLElement
  if (t && (t.tagName === 'TEXTAREA' || t.tagName === 'INPUT' || t.tagName === 'SELECT')) return
  if (e.key === 'Escape') demo.closeReview()
  else if (e.key === 'ArrowRight') go(1)
  else if (e.key === 'ArrowLeft') go(-1)
}

// Theme tokens: copy the live palette from the page root, so the overlay
// matches whatever theme the viewer picked.
const rootStyle = ref('')
watch(isOpen, async (open) => {
  if (open) {
    rootStyle.value = document.querySelector('.show2-root')?.getAttribute('style') ?? ''
    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', onKey)
    await nextTick()
  } else {
    document.body.style.overflow = ''
    window.removeEventListener('keydown', onKey)
  }
})
onBeforeUnmount(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', onKey)
})

const status = computed(() => (current.value ? demo.statusOf(current.value.assetKey) : null))
const setStatus = (s: 'approved' | 'changes') => {
  if (!current.value) return
  demo.setStatus(current.value.assetKey, status.value === s ? null : s)
}

const getTime = () => mainStage.value?.videoEl?.currentTime ?? leftStage.value?.videoEl?.currentTime ?? null
const seek = (t: number) => {
  const v = mainStage.value?.videoEl ?? leftStage.value?.videoEl
  if (v) {
    v.currentTime = t
    v.play().catch(() => {})
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="rv-fade">
      <div
        v-if="isOpen && current"
        class="show2-root fixed inset-0 z-[60] flex flex-col"
        :style="rootStyle"
        role="dialog"
        aria-modal="true"
        :aria-label="`Review ${sizeLabel(current.asset)}`"
      >
        <!-- Top bar -->
        <header class="flex shrink-0 items-center gap-3 border-b px-4 py-3" :style="{ borderColor: 'var(--p2-hairline)' }">
          <button type="button" class="rv-icon" aria-label="Close review (Esc)" title="Close (Esc)" @click="demo.closeReview()">
            <X class="h-4 w-4" />
          </button>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <span class="p2-mono rounded-md px-2 py-0.5 text-xs font-semibold" :style="{ background: 'var(--p2-accent-soft)', color: 'var(--p2-text)' }">
                {{ sizeLabel(current.asset) }}
              </span>
              <span class="truncate text-sm font-medium" :style="{ color: 'var(--p2-text)' }">{{ placeLabel(current) }}</span>
            </div>
            <p class="mt-0.5 text-xs" :style="{ color: 'var(--p2-text-muted)' }">{{ currentEntry?.roundName }}</p>
          </div>

          <div class="ml-auto flex items-center gap-2">
            <span class="rv-demo">Demo · not saved</span>
            <span class="p2-mono text-xs tabular-nums" :style="{ color: 'var(--p2-text-muted)' }">{{ index + 1 }} / {{ items.length }}</span>
            <button type="button" class="rv-icon" aria-label="Previous creative (←)" title="Previous (←)" @click="go(-1)">
              <ChevronLeft class="h-4 w-4" />
            </button>
            <button type="button" class="rv-icon" aria-label="Next creative (→)" title="Next (→)" @click="go(1)">
              <ChevronRight class="h-4 w-4" />
            </button>
          </div>
        </header>

        <div class="flex min-h-0 flex-1 flex-col lg:flex-row">
          <!-- Stage column -->
          <section class="flex min-h-[50vh] min-w-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 p-4 lg:p-6">
              <!-- Single -->
              <AssetStage
                v-if="!comparing || !compareEntry"
                ref="mainStage"
                :asset="viewing!.asset"
                :type="type"
                :replay-key="replayKey"
              />

              <!-- Compare: this vs this -->
              <div v-else class="flex h-full min-h-0 flex-col">
                <div class="mb-3 grid shrink-0 grid-cols-1 items-center gap-2 md:grid-cols-[1fr_auto_1fr]">
                  <div class="flex flex-wrap items-center justify-center gap-1.5">
                    <span class="rv-side">L</span>
                    <select
                      :value="leftRoundId"
                      class="rv-select rounded-full px-3 py-1.5 text-xs font-medium"
                      aria-label="Left side round"
                      @change="pickLeftRound(Number(($event.target as HTMLSelectElement).value))"
                    >
                      <option v-for="r in allRounds" :key="r.roundId" :value="r.roundId">{{ r.roundName }}</option>
                    </select>
                    <select v-model="leftKey" class="rv-select max-w-[15rem] rounded-full px-3 py-1.5 text-xs" aria-label="Left side creative">
                      <option v-for="o in leftCreatives" :key="o.key" :value="o.key">{{ o.label }}</option>
                    </select>
                  </div>

                  <button
                    type="button"
                    class="rv-vs mx-auto"
                    title="Swap sides"
                    aria-label="Swap sides"
                    @click="swapSides"
                  >
                    <span class="rv-vs-text">VS</span>
                    <ArrowLeftRight class="rv-vs-icon h-3.5 w-3.5" />
                  </button>

                  <div class="flex flex-wrap items-center justify-center gap-1.5">
                    <span class="rv-side">R</span>
                    <select
                      :value="compareRoundId"
                      class="rv-select rounded-full px-3 py-1.5 text-xs font-medium"
                      aria-label="Right side round"
                      @change="pickRound(Number(($event.target as HTMLSelectElement).value))"
                    >
                      <option v-for="r in compareRounds" :key="r.roundId" :value="r.roundId">{{ r.roundName }}</option>
                    </select>
                    <select v-model="compareKey" class="rv-select max-w-[15rem] rounded-full px-3 py-1.5 text-xs" aria-label="Right side creative">
                      <option v-for="o in compareCreatives" :key="o.key" :value="o.key">{{ o.label }}</option>
                    </select>
                  </div>
                </div>

                <!-- Slider (images) -->
                <div v-if="sliderMode" class="relative min-h-0 flex-1">
                  <div class="absolute inset-0">
                    <AssetStage :asset="compareEntry.asset" :type="type" />
                  </div>
                  <div class="absolute inset-0" :style="{ clipPath: `inset(0 ${100 - slider}% 0 0)` }">
                    <AssetStage :asset="leftEntry!.asset" :type="type" />
                  </div>
                  <div class="pointer-events-none absolute inset-y-0 w-0.5" :style="{ left: `${slider}%`, background: 'var(--p2-accent)' }" />
                  <span class="rv-tag left-3">{{ leftEntry!.roundName }}</span>
                  <span class="rv-tag right-3">{{ compareEntry.roundName }}</span>
                  <input
                    v-model.number="slider"
                    type="range"
                    min="0"
                    max="100"
                    class="absolute inset-x-0 bottom-2 mx-auto w-1/2 accent-current"
                    aria-label="Compare slider"
                  />
                </div>

                <!-- Side by side -->
                <div v-else class="grid min-h-0 flex-1 grid-cols-1 gap-4 md:grid-cols-2">
                  <div class="rv-pane min-h-0 rounded-2xl p-3">
                    <AssetStage ref="leftStage" :asset="leftEntry!.asset" :type="type" :replay-key="replayKey" />
                  </div>
                  <div class="rv-pane min-h-0 rounded-2xl p-3">
                    <AssetStage ref="rightStage" :asset="compareEntry.asset" :type="type" :replay-key="replayKey" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Timeline + compare controls -->
            <footer class="flex shrink-0 flex-wrap items-center gap-2 border-t px-4 py-3" :style="{ borderColor: 'var(--p2-hairline)' }">
              <span class="p2-label mr-1">History</span>
              <button
                v-for="e in lineage"
                :key="e.assetKey"
                type="button"
                class="rv-chip"
                :class="[chipActive(e) ? 'rv-chip-on' : '', chipSide(e) === 'right' ? 'rv-chip-right' : '']"
                :title="comparing ? (chipSide(e) === 'right' ? 'On the right. Click to swap sides' : 'Show on the left') : ''"
                @click="onChip(e)"
              >
                <span v-if="chipSide(e)" class="rv-side">{{ chipSide(e) === 'left' ? 'L' : 'R' }}</span>
                {{ e.roundName }}
                <span v-if="e.assetKey === current.assetKey" class="opacity-70">· current</span>
              </button>

              <div class="ml-auto flex flex-wrap items-center gap-2">
                <template v-if="comparing">
                  <button v-if="type === 'banner' || type === 'gif'" type="button" class="rv-chip" @click="replayBoth">
                    <RotateCcw class="h-3.5 w-3.5" /> Replay both
                  </button>
                  <template v-if="type === 'video'">
                    <button type="button" class="rv-chip" @click="playBoth"><Play class="h-3.5 w-3.5" /> Play both</button>
                    <button type="button" class="rv-chip" @click="pauseBoth"><Pause class="h-3.5 w-3.5" /> Pause</button>
                  </template>
                  <button v-if="type === 'social'" type="button" class="rv-chip" :class="sliderMode ? 'rv-chip-on' : ''" @click="sliderMode = !sliderMode">
                    <SplitSquareHorizontal class="h-3.5 w-3.5" /> Slider
                  </button>
                  <button type="button" class="rv-chip" @click="endCompare">Done</button>
                </template>
                <template v-else>
                  <button v-if="type === 'banner' || type === 'gif'" type="button" class="rv-chip" @click="replayBoth">
                    <RotateCcw class="h-3.5 w-3.5" /> Replay
                  </button>
                  <button
                    type="button"
                    class="rv-chip rv-chip-accent"
                    :disabled="!compareRounds.length"
                    :title="compareRounds.length ? 'Compare with a creative from another round' : 'Only one round so far. Compare unlocks once a new round is uploaded.'"
                    @click="startCompare"
                  >
                    <Columns2 class="h-3.5 w-3.5" /> Compare rounds
                  </button>
                </template>
              </div>
            </footer>
          </section>

          <!-- Feedback column -->
          <aside class="flex min-h-[40vh] w-full shrink-0 flex-col border-t lg:w-[380px] lg:border-l lg:border-t-0" :style="{ borderColor: 'var(--p2-hairline)' }">
            <div class="shrink-0 border-b p-4" :style="{ borderColor: 'var(--p2-hairline)' }">
              <p class="p2-label mb-2">Your decision · {{ currentEntry?.roundName }}</p>
              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  class="rv-decide"
                  :class="status === 'approved' ? 'rv-approved' : ''"
                  @click="setStatus('approved')"
                >
                  <Check class="h-4 w-4" /> Approve
                </button>
                <button
                  type="button"
                  class="rv-decide"
                  :class="status === 'changes' ? 'rv-changes' : ''"
                  @click="setStatus('changes')"
                >
                  <CircleAlert class="h-4 w-4" /> Request changes
                </button>
              </div>
              <p v-if="!isViewingCurrent" class="mt-2 text-[11px]" :style="{ color: 'var(--p2-text-subtle)' }">
                You are looking at {{ viewing?.roundName }}. Decisions apply to the current round.
              </p>
            </div>

            <CommentThread
              :entry="viewing!"
              :is-video="type === 'video'"
              :get-time="getTime"
              @seek="seek"
            />
          </aside>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.show2-root {
  background: var(--p2-bg);
}
.rv-icon {
  display: grid;
  place-items: center;
  height: 2.25rem;
  width: 2.25rem;
  border-radius: 9999px;
  border: 1px solid var(--p2-border);
  background: var(--p2-surface);
  color: var(--p2-text-muted);
  transition: color 200ms var(--p2-ease-expo), border-color 200ms var(--p2-ease-expo);
}
.rv-icon:hover {
  color: var(--p2-text);
  border-color: var(--p2-border-strong);
}
.rv-demo {
  border-radius: 9999px;
  padding: 0.2rem 0.6rem;
  font-size: 11px;
  font-weight: 500;
  background: rgb(245 158 11 / 0.12);
  color: rgb(217 119 6);
}
.rv-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  height: 2rem;
  padding: 0 0.75rem;
  border-radius: 9999px;
  border: 1px solid var(--p2-border);
  background: var(--p2-surface);
  font-size: 12px;
  color: var(--p2-text);
  transition: border-color 200ms var(--p2-ease-expo), background 200ms var(--p2-ease-expo);
}
.rv-chip:hover:not(:disabled) {
  border-color: var(--p2-border-strong);
}
.rv-chip:disabled {
  opacity: 0.4;
}
.rv-chip-on {
  border-color: var(--p2-accent);
  background: var(--p2-accent-soft);
}
.rv-chip-right {
  border-style: dashed;
  border-color: var(--p2-accent-muted);
}
.rv-side {
  display: inline-grid;
  place-items: center;
  height: 1.125rem;
  min-width: 1.125rem;
  border-radius: 9999px;
  background: var(--p2-accent);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
}
.rv-chip-accent {
  border-color: var(--p2-accent-muted);
  color: var(--p2-accent);
}
.rv-select {
  border: 1px solid var(--p2-border);
  background: var(--p2-surface);
  color: var(--p2-text);
}
.rv-pane {
  background: var(--p2-surface-muted);
  border: 1px solid var(--p2-border);
}
.rv-vs {
  position: relative;
  display: grid;
  place-items: center;
  height: 2.5rem;
  width: 2.5rem;
  border-radius: 9999px;
  border: 1px solid var(--p2-accent-muted);
  background: var(--p2-accent-soft);
  color: var(--p2-accent);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  transition: transform 200ms var(--p2-ease-expo);
}
.rv-vs:hover {
  transform: rotate(180deg);
}
.rv-vs-icon {
  position: absolute;
  opacity: 0;
  transition: opacity 150ms ease;
}
.rv-vs:hover .rv-vs-icon {
  opacity: 1;
}
.rv-vs:hover .rv-vs-text {
  opacity: 0;
}
.rv-tag {
  position: absolute;
  top: 0.75rem;
  border-radius: 9999px;
  padding: 0.15rem 0.6rem;
  font-size: 11px;
  background: var(--p2-surface);
  border: 1px solid var(--p2-border);
  color: var(--p2-text);
}
.rv-decide {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  height: 2.5rem;
  border-radius: 0.875rem;
  border: 1px solid var(--p2-border);
  background: var(--p2-surface);
  font-size: 13px;
  font-weight: 500;
  color: var(--p2-text);
  transition: all 200ms var(--p2-ease-expo);
}
.rv-decide:hover {
  border-color: var(--p2-border-strong);
}
.rv-approved {
  border-color: rgb(16 185 129);
  background: rgb(16 185 129 / 0.12);
  color: rgb(5 150 105);
}
.rv-changes {
  border-color: rgb(245 158 11);
  background: rgb(245 158 11 / 0.12);
  color: rgb(217 119 6);
}
.rv-fade-enter-active,
.rv-fade-leave-active {
  transition: opacity 200ms ease, transform 200ms var(--p2-ease-expo);
}
.rv-fade-enter-from,
.rv-fade-leave-to {
  opacity: 0;
  transform: scale(0.99);
}
</style>
