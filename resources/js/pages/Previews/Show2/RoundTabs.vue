<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue'
import { ChevronLeft, ChevronRight, CheckCircle2 } from 'lucide-vue-next'

const props = defineProps<{
  feedbacks: any[]
  activeFeedback: any
  isLoading: boolean
}>()

const emit = defineEmits<{
  (e: 'select', id: number): void
}>()

const scrollerEl = ref<HTMLElement | null>(null)

const activeIndex = computed(() =>
  props.feedbacks.findIndex((f) => f.id === props.activeFeedback?.id)
)

const goPrev = () => {
  const i = activeIndex.value
  if (i > 0) emit('select', props.feedbacks[i - 1].id)
}
const goNext = () => {
  const i = activeIndex.value
  if (i >= 0 && i < props.feedbacks.length - 1) emit('select', props.feedbacks[i + 1].id)
}

const scrollActiveIntoView = () => {
  const el = scrollerEl.value
  if (!el) return
  const active = el.querySelector('[data-active="true"]') as HTMLElement | null
  if (!active) return
  const elRect = el.getBoundingClientRect()
  const aRect = active.getBoundingClientRect()
  const offset = aRect.left - elRect.left - elRect.width / 2 + aRect.width / 2
  el.scrollBy({ left: offset, behavior: 'smooth' })
}

watch(
  () => props.activeFeedback?.id,
  () => nextTick(scrollActiveIntoView)
)
</script>

<template>
  <section class="mb-6">
    <div class="flex items-center justify-between gap-3 mb-3">
      <div>
        <h2
          class="text-xs font-semibold uppercase tracking-[0.12em]"
          :style="{ color: 'var(--p2-accent)' }"
        >
          Revision Round
        </h2>
        <p v-if="!isLoading && feedbacks.length" class="mt-0.5 text-sm text-zinc-700 dark:text-zinc-300">
          <span class="font-medium">{{ activeFeedback?.name || '—' }}</span>
          <span class="text-zinc-400 dark:text-zinc-500"> · {{ activeIndex + 1 }} of {{ feedbacks.length }}</span>
        </p>
      </div>

      <div v-if="!isLoading && feedbacks.length > 1" class="flex items-center gap-1.5">
        <button
          type="button"
          :disabled="activeIndex <= 0"
          class="grid h-8 w-8 place-items-center rounded-lg border border-zinc-200 bg-white text-zinc-600 transition hover:border-[var(--p2-accent)] hover:text-[var(--p2-accent)] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-zinc-200 disabled:hover:text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:disabled:hover:border-zinc-800 dark:disabled:hover:text-zinc-300"
          aria-label="Previous round"
          @click="goPrev"
        >
          <ChevronLeft class="h-4 w-4" />
        </button>
        <button
          type="button"
          :disabled="activeIndex >= feedbacks.length - 1"
          class="grid h-8 w-8 place-items-center rounded-lg border border-zinc-200 bg-white text-zinc-600 transition hover:border-[var(--p2-accent)] hover:text-[var(--p2-accent)] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-zinc-200 disabled:hover:text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:disabled:hover:border-zinc-800 dark:disabled:hover:text-zinc-300"
          aria-label="Next round"
          @click="goNext"
        >
          <ChevronRight class="h-4 w-4" />
        </button>
      </div>
    </div>

    <div v-if="isLoading" class="flex gap-2">
      <div v-for="n in 3" :key="n" class="h-10 w-32 animate-pulse rounded-full bg-zinc-100 dark:bg-zinc-800" />
    </div>

    <div
      v-else-if="feedbacks.length"
      ref="scrollerEl"
      class="-mx-1 flex snap-x gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
    >
      <button
        v-for="f in feedbacks"
        :key="f.id"
        type="button"
        :data-active="f.id === activeFeedback?.id"
        :class="[
          'group relative shrink-0 snap-start rounded-full border px-4 py-2 text-sm font-medium transition',
          f.id === activeFeedback?.id
            ? 'border-transparent text-white shadow-sm'
            : 'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-800',
        ]"
        :style="
          f.id === activeFeedback?.id
            ? { background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }
            : undefined
        "
        @click="$emit('select', f.id)"
      >
        <span class="inline-flex items-center gap-1.5">
          {{ f.name }}
          <CheckCircle2
            v-if="f.is_approved === 1"
            class="h-3.5 w-3.5"
            :class="f.id === activeFeedback?.id ? 'text-white/90' : 'text-emerald-500'"
          />
        </span>
      </button>
    </div>

    <div v-else class="rounded-xl border border-dashed border-zinc-200 bg-white px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
      No revision rounds yet.
    </div>
  </section>
</template>
