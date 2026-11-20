<script setup lang="ts">
/**
 * DEMO ONLY: the row under each creative in the review prototype.
 * Comment count (open threads highlighted), approval status, and the way into
 * review mode. Rendered by Show2's AssetCanvas through the `creativeFooter`
 * injection, so Show2 itself is unchanged.
 */
import { computed, inject, watchEffect } from 'vue'
import { MessageSquare, Check, CircleAlert, Maximize2 } from 'lucide-vue-next'
import { assetKeyOf, type ReviewDemo } from './useReviewDemo'

const props = defineProps<{
  asset: any
  type: string
  context: any
}>()

const demo = inject<ReviewDemo>('reviewDemo')!

const assetKey = computed(() => assetKeyOf(props.type, props.asset))
const counts = computed(() => demo.countFor(assetKey.value))
const status = computed(() => demo.statusOf(assetKey.value))

// Lets the demo know which round is on screen (for progress + review nav).
watchEffect(() => demo.trackRound(props.context, props.type))

const open = () => demo.openReview(assetKey.value)
</script>

<template>
  <div
    class="mt-1.5 flex items-center gap-1 text-[11px]"
    :style="{ color: 'var(--p2-text-muted)' }"
  >
    <button
      type="button"
      class="cf-btn inline-flex h-7 items-center gap-1.5 rounded-full px-2.5"
      :class="counts.open ? 'cf-open' : ''"
      :aria-label="counts.total ? `${counts.total} comments, ${counts.open} open` : 'Add a comment'"
      @click="open"
    >
      <MessageSquare class="h-3.5 w-3.5" />
      <span v-if="counts.total" class="p2-mono tabular-nums">{{ counts.total }}</span>
      <span v-else>Comment</span>
      <span v-if="counts.open" class="h-1.5 w-1.5 rounded-full" :style="{ background: 'var(--p2-accent)' }" />
    </button>

    <span
      v-if="status === 'approved'"
      class="inline-flex h-7 items-center gap-1 rounded-full px-2.5 font-medium text-emerald-600 dark:text-emerald-400"
      style="background: rgb(16 185 129 / 0.1)"
    >
      <Check class="h-3.5 w-3.5" /> Approved
    </span>
    <span
      v-else-if="status === 'changes'"
      class="inline-flex h-7 items-center gap-1 rounded-full px-2.5 font-medium text-amber-600 dark:text-amber-400"
      style="background: rgb(245 158 11 / 0.12)"
    >
      <CircleAlert class="h-3.5 w-3.5" /> Changes
    </span>

    <button
      type="button"
      class="cf-btn ml-auto inline-flex h-7 items-center gap-1.5 rounded-full px-2.5"
      title="Open in review mode"
      @click="open"
    >
      <Maximize2 class="h-3.5 w-3.5" /> Review
    </button>
  </div>
</template>

<style scoped>
.cf-btn {
  transition: background 200ms var(--p2-ease-expo), color 200ms var(--p2-ease-expo);
}
.cf-btn:hover {
  background: var(--p2-accent-soft);
  color: var(--p2-text);
}
.cf-open {
  color: var(--p2-text);
}
</style>
