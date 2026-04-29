<script setup lang="ts">
import { computed, ref, inject } from 'vue'
import { RotateCw, Download } from 'lucide-vue-next'

const props = defineProps<{
  gif: any
}>()

const isPlanetNine = inject<boolean>('isPlanetNine', false)

const iframeEl = ref<HTMLIFrameElement | null>(null)

const width = computed(() => props.gif?.size?.width || 300)
const height = computed(() => props.gif?.size?.height || 250)

const wrapperStyle = computed(() => ({
  width: width.value + 'px',
  maxWidth: '100%',
}))

const frameStyle = computed(() => ({
  width: width.value + 'px',
  height: height.value + 'px',
}))

const reload = () => {
  if (iframeEl.value) iframeEl.value.src = iframeEl.value.src
}
</script>

<template>
  <div
    class="group flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white transition hover:border-zinc-300 hover:shadow-md hover:shadow-zinc-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700"
    :style="wrapperStyle"
  >
    <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-3 py-2 dark:border-zinc-800">
      <div class="flex items-center gap-2">
        <span class="rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
          GIF
        </span>
        <span class="font-mono text-[11px] font-semibold tabular-nums text-zinc-700 dark:text-zinc-200">
          {{ width }}<span class="text-zinc-400 dark:text-zinc-500">×</span>{{ height }}
        </span>
      </div>
      <span v-if="gif.file_size" class="font-mono text-[11px] tabular-nums text-zinc-500 dark:text-zinc-400">
        {{ gif.file_size }}
      </span>
    </div>

    <div class="relative bg-zinc-50 dark:bg-zinc-950">
      <div class="mx-auto overflow-hidden" :style="frameStyle">
        <iframe
          ref="iframeEl"
          :src="`/${gif.path}`"
          :width="width"
          :height="height"
          frameborder="0"
          scrolling="no"
          class="block border-0"
        />
      </div>

      <div class="pointer-events-none absolute right-2 top-2 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
        <button
          type="button"
          class="pointer-events-auto grid h-7 w-7 place-items-center rounded-md bg-white/90 text-zinc-700 shadow-sm ring-1 ring-zinc-200 backdrop-blur transition hover:bg-white hover:text-[var(--p2-accent)] dark:bg-zinc-900/90 dark:text-zinc-300 dark:ring-zinc-700 dark:hover:bg-zinc-900"
          aria-label="Reload gif"
          @click="reload"
        >
          <RotateCw class="h-3.5 w-3.5" />
        </button>
        <a
          v-if="isPlanetNine"
          :href="`/${gif.path}`"
          :download="gif.name"
          class="pointer-events-auto grid h-7 w-7 place-items-center rounded-md bg-white/90 text-zinc-700 shadow-sm ring-1 ring-zinc-200 backdrop-blur transition hover:bg-white hover:text-[var(--p2-accent)] dark:bg-zinc-900/90 dark:text-zinc-300 dark:ring-zinc-700 dark:hover:bg-zinc-900"
          aria-label="Download gif"
        >
          <Download class="h-3.5 w-3.5" />
        </a>
      </div>
    </div>
  </div>
</template>
