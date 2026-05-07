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

// Hooks the responsive scaling rules in `preview-banner-responsive.css`.
// At narrow viewports the matching `.banner-area-W-H` rule shrinks the
// outer wrapper + inner frame and applies `transform: scale()` to the
// iframe so wide banners fit without horizontal overflow.
const bannerAreaClass = computed(() => `banner-area-${width.value}-${height.value}`)
</script>

<template>
  <div
    ref="containerEl"
    :class="['group flex flex-col overflow-hidden border bg-[var(--p2-surface)] transition-all duration-300 ease-[var(--p2-ease-expo)] hover:-translate-y-0.5', bannerAreaClass]"
    :style="{ ...wrapperStyle, borderColor: 'var(--p2-border)', boxShadow: '0 1px 0 var(--p2-hairline)' }"
  >
    <!-- Header — dimensions + filesize, mono. -->
    <div
      class="flex items-center justify-between gap-2 border-b px-3 py-2"
      :style="{ borderColor: 'var(--p2-hairline)' }"
    >
      <span
        class="p2-mono inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[11px] font-semibold tabular-nums text-[var(--p2-text)]"
        :style="{ background: 'var(--p2-accent-soft)' }"
      >
        {{ width }}<span class="text-[var(--p2-text-subtle)]">×</span>{{ height }}
      </span>
      <span
        v-if="fileSize"
        class="p2-mono text-[11px] tabular-nums text-[var(--p2-text-muted)]"
      >
        {{ fileSize }}
      </span>
    </div>

    <!-- Iframe stage -->
    <div class="relative" :style="{ background: 'var(--p2-bg)' }">
      <div
        class="banner-card-frame relative mx-auto overflow-hidden"
        :style="frameStyle"
      >
        <!-- sandbox: allow-scripts (banners are HTML5 + JS) and
             allow-popups (so clickTag links can open in a new tab),
             but explicitly NOT allow-same-origin — that's the bit
             that previously let a malicious banner read app cookies
             and call authenticated endpoints in the parent's origin. -->
        <iframe
          v-if="isLoading || isLoaded"
          ref="iframeEl"
          :src="src"
          :width="width"
          :height="height"
          frameborder="0"
          scrolling="no"
          loading="lazy"
          sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox"
          referrerpolicy="no-referrer"
          class="block border-0"
          @load="onIframeLoad"
          @error="onIframeError"
        />

        <!-- Placeholder / load trigger -->
        <button
          v-if="!isLoaded && !isLoading"
          type="button"
          class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-text)]"
          :style="{ background: 'var(--p2-bg)' }"
          :aria-label="`Load banner ${width}x${height}`"
          @click="loadIframe"
        >
          <span
            class="grid h-10 w-10 place-items-center rounded-full border"
            :style="{ background: 'var(--p2-surface)', borderColor: 'var(--p2-border)' }"
          >
            <Play class="h-4 w-4 translate-x-[1px]" :style="{ color: 'var(--p2-accent)' }" />
          </span>
          <span class="p2-label text-[10px]">Click to load</span>
        </button>

        <!-- Loading -->
        <div
          v-if="isLoading && !isLoaded"
          class="pointer-events-none absolute inset-0 flex items-center justify-center"
          :style="{ background: 'color-mix(in srgb, var(--p2-bg) 60%, transparent)' }"
        >
          <div
            class="h-5 w-5 animate-spin rounded-full border-2"
            :style="{ borderColor: 'var(--p2-border)', borderTopColor: 'var(--p2-accent)' }"
          />
        </div>

        <!-- Error -->
        <div
          v-if="hasError"
          class="absolute inset-0 flex flex-col items-center justify-center gap-1 text-rose-500"
          style="background: rgba(244, 63, 94, 0.06);"
        >
          <span class="p2-label text-rose-500">Failed to load</span>
          <button type="button" class="text-[11px] underline" @click="reload">Retry</button>
        </div>
      </div>

      <!-- Hover action cluster -->
      <div class="pointer-events-none absolute right-2 bottom-2 flex gap-1 opacity-0 transition-opacity duration-300 ease-[var(--p2-ease-expo)] group-hover:opacity-100">
        <button
          type="button"
          class="pointer-events-auto grid h-7 w-7 place-items-center rounded-full border text-[var(--p2-text-muted)] backdrop-blur transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)]"
          :style="{ background: 'var(--p2-surface-muted)', borderColor: 'var(--p2-border)' }"
          aria-label="Reload banner"
          @click.stop="reload"
        >
          <RotateCw class="h-3.5 w-3.5" />
        </button>
        <a
          v-if="isPlanetNine"
          :href="`/previews/banner/download/${banner.id}`"
          class="pointer-events-auto grid h-7 w-7 place-items-center rounded-full border text-[var(--p2-text-muted)] backdrop-blur transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)]"
          :style="{ background: 'var(--p2-surface-muted)', borderColor: 'var(--p2-border)' }"
          aria-label="Download banner"
        >
          <Download class="h-3.5 w-3.5" />
        </a>
      </div>
    </div>
  </div>
</template>
