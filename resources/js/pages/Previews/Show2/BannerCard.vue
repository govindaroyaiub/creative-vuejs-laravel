<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, inject } from 'vue'
import { RotateCw, Download, Play } from 'lucide-vue-next'

const props = defineProps<{
  banner: any
  index: number
}>()

const isPlanetNine = inject<boolean>('isPlanetNine', false)

const containerEl = ref<HTMLElement | null>(null)
const iframeEl = ref<HTMLIFrameElement | null>(null)
const isLoaded = ref(false)
const isLoading = ref(false)
const hasError = ref(false)

const width = computed(() => props.banner?.size?.width || 300)
const height = computed(() => props.banner?.size?.height || 250)
const src = computed(() => `/${props.banner.path}/index.html`)
const fileSize = computed(() => props.banner?.file_size || '')

// First 3 in a version load eagerly; the rest wait for the user to scroll near
const eager = computed(() => props.index < 3)

const loadIframe = () => {
  if (isLoaded.value || isLoading.value) return
  isLoading.value = true
  hasError.value = false
}

const onIframeLoad = () => {
  isLoading.value = false
  isLoaded.value = true
}

const onIframeError = () => {
  isLoading.value = false
  hasError.value = true
}

const reload = () => {
  if (!iframeEl.value) {
    loadIframe()
    return
  }
  isLoading.value = true
  iframeEl.value.src = iframeEl.value.src
}

let observer: IntersectionObserver | null = null

onMounted(() => {
  if (eager.value) {
    loadIframe()
    return
  }
  if (!('IntersectionObserver' in window)) {
    setTimeout(loadIframe, 1500)
    return
  }
  observer = new IntersectionObserver(
    (entries) => {
      for (const e of entries) {
        if (e.isIntersecting) {
          loadIframe()
          observer?.disconnect()
          observer = null
          break
        }
      }
    },
    { rootMargin: '200px', threshold: 0.05 }
  )
  if (containerEl.value) observer.observe(containerEl.value)
})

onBeforeUnmount(() => observer?.disconnect())

const frameStyle = computed(() => ({
  width: width.value + 'px',
  height: height.value + 'px',
}))

const wrapperStyle = computed(() => ({
  width: width.value + 'px',
  maxWidth: '100%',
}))
</script>

<template>
  <div
    ref="containerEl"
    class="group flex flex-col overflow-hidden border border-zinc-200 bg-white transition hover:border-zinc-300 hover:shadow-md hover:shadow-zinc-900/5 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700"
    :style="wrapperStyle"
  >
    <!-- Header -->
    <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-3 py-2 dark:border-zinc-800">
      <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2 py-0.5 font-mono text-[11px] font-semibold tabular-nums text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
        {{ width }}<span class="text-zinc-400 dark:text-zinc-500">×</span>{{ height }}
      </span>
      <span v-if="fileSize" class="font-mono text-[11px] tabular-nums text-zinc-500 dark:text-zinc-400">
        {{ fileSize }}
      </span>
    </div>

    <!-- Iframe stage -->
    <div class="relative bg-zinc-50 dark:bg-zinc-950">
      <div
        class="relative mx-auto overflow-hidden"
        :style="frameStyle"
      >
        <iframe
          v-if="isLoading || isLoaded"
          ref="iframeEl"
          :src="src"
          :width="width"
          :height="height"
          frameborder="0"
          scrolling="no"
          loading="lazy"
          class="block border-0"
          @load="onIframeLoad"
          @error="onIframeError"
        />

        <!-- Placeholder / load trigger -->
        <button
          v-if="!isLoaded && !isLoading"
          type="button"
          class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-zinc-50 text-zinc-500 transition hover:bg-zinc-100 dark:bg-zinc-950 dark:text-zinc-400 dark:hover:bg-zinc-900"
          :aria-label="`Load banner ${width}x${height}`"
          @click="loadIframe"
        >
          <span class="grid h-10 w-10 place-items-center rounded-full bg-white shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-700">
            <Play class="h-4 w-4 translate-x-[1px]" :style="{ color: 'var(--p2-accent)' }" />
          </span>
          <span class="text-xs font-medium">Click to load</span>
        </button>

        <!-- Loading -->
        <div
          v-if="isLoading && !isLoaded"
          class="pointer-events-none absolute inset-0 flex items-center justify-center bg-white/60 dark:bg-zinc-950/60"
        >
          <div
            class="h-5 w-5 animate-spin rounded-full border-2 border-zinc-200 dark:border-zinc-700"
            :style="{ borderTopColor: 'var(--p2-accent)' }"
          />
        </div>

        <!-- Error -->
        <div
          v-if="hasError"
          class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400"
        >
          <span class="text-xs font-semibold">Failed to load</span>
          <button type="button" class="text-[11px] underline" @click="reload">Retry</button>
        </div>
      </div>

      <!-- Hover action cluster -->
      <div class="pointer-events-none absolute right-2 top-2 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
        <button
          type="button"
          class="pointer-events-auto grid h-7 w-7 place-items-center rounded-md bg-white/90 text-zinc-700 shadow-sm ring-1 ring-zinc-200 backdrop-blur transition hover:bg-white hover:text-[var(--p2-accent)] dark:bg-zinc-900/90 dark:text-zinc-300 dark:ring-zinc-700 dark:hover:bg-zinc-900"
          aria-label="Reload banner"
          @click.stop="reload"
        >
          <RotateCw class="h-3.5 w-3.5" />
        </button>
        <a
          v-if="isPlanetNine"
          :href="`/previews/banner/download/${banner.id}`"
          class="pointer-events-auto grid h-7 w-7 place-items-center rounded-md bg-white/90 text-zinc-700 shadow-sm ring-1 ring-zinc-200 backdrop-blur transition hover:bg-white hover:text-[var(--p2-accent)] dark:bg-zinc-900/90 dark:text-zinc-300 dark:ring-zinc-700 dark:hover:bg-zinc-900"
          aria-label="Download banner"
        >
          <Download class="h-3.5 w-3.5" />
        </a>
      </div>
    </div>
  </div>
</template>
