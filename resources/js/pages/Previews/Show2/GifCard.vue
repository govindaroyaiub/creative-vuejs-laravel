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

// Same responsive hook as BannerCard — see preview-banner-responsive.css.
const bannerAreaClass = computed(() => `banner-area-${width.value}-${height.value}`)

const reload = () => {
  if (iframeEl.value) iframeEl.value.src = iframeEl.value.src
}
</script>

<template>
  <div
    :class="['group flex flex-col overflow-hidden rounded-2xl border bg-[var(--p2-surface)] transition-all duration-300 ease-[var(--p2-ease-expo)] hover:-translate-y-0.5', bannerAreaClass]"
    :style="{ ...wrapperStyle, borderColor: 'var(--p2-border)' }"
  >
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
        v-if="gif.file_size"
        class="p2-mono text-[11px] tabular-nums text-[var(--p2-text-muted)]"
      >
        {{ gif.file_size }}
      </span>
    </div>

    <div class="relative" :style="{ background: 'var(--p2-bg)' }">
      <div class="banner-card-frame mx-auto overflow-hidden" :style="frameStyle">
        <iframe
          ref="iframeEl"
          :src="`/${gif.path}`"
          :width="width"
          :height="height"
          frameborder="0"
          scrolling="no"
          sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox"
          referrerpolicy="no-referrer"
          class="block border-0"
        />
      </div>

      <div class="pointer-events-none absolute right-2 bottom-2 flex gap-1 opacity-0 transition-opacity duration-300 ease-[var(--p2-ease-expo)] group-hover:opacity-100">
        <button
          type="button"
          class="pointer-events-auto grid h-7 w-7 place-items-center rounded-full border text-[var(--p2-text-muted)] backdrop-blur transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)]"
          :style="{ background: 'var(--p2-surface-muted)', borderColor: 'var(--p2-border)' }"
          aria-label="Reload gif"
          @click="reload"
        >
          <RotateCw class="h-3.5 w-3.5" />
        </button>
        <a
          v-if="isPlanetNine"
          :href="`/${gif.path}`"
          :download="gif.name"
          class="pointer-events-auto grid h-7 w-7 place-items-center rounded-full border text-[var(--p2-text-muted)] backdrop-blur transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)]"
          :style="{ background: 'var(--p2-surface-muted)', borderColor: 'var(--p2-border)' }"
          aria-label="Download gif"
        >
          <Download class="h-3.5 w-3.5" />
        </a>
      </div>
    </div>
  </div>
</template>
