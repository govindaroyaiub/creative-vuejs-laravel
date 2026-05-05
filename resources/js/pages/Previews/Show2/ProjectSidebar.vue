<script setup lang="ts">
import { computed, inject, type Ref } from 'vue'
import { ImagePlay, Video, Image, GalleryThumbnails, X, Folder } from 'lucide-vue-next'

const props = defineProps<{
  categories: any[]
  activeCategory: any
  isLoading: boolean
  isMobileOpen: boolean
  preview?: any
  client?: any
  headerLogo?: any
}>()

defineEmits<{
  (e: 'select', id: number): void
  (e: 'close'): void
}>()

// Same visibility logic as the topbar — keeps the two locations in
// sync so whichever logo is "currently active" is the one that
// transfers between zones on scroll.
const showPlanetNineLogo = computed(() => props.preview?.show_planetnine_logo === 1 && props.headerLogo?.logo)
const showSidebarLogo = computed(() => props.preview?.show_sidebar_logo === 1 && props.client?.logo)
const hasAnyLogo = computed(() => showPlanetNineLogo.value || showSidebarLogo.value)

// Scroll state (provided by Show2.vue). When true, the topbar logo is
// faded out and we slide our copy into the top of the sidebar card.
const isScrolled = inject<Ref<boolean>>('show2Scrolled')

const formatDate = (s: string) => {
  if (!s) return ''
  const d = new Date(s)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const typeIcon = (type: string) => {
  switch (type) {
    case 'banner':
      return GalleryThumbnails
    case 'video':
      return Video
    case 'social':
      return Image
    case 'gif':
      return ImagePlay
    default:
      return Folder
  }
}

const typeLabel = (type: string) => {
  switch (type) {
    case 'banner':
      return 'Banners'
    case 'video':
      return 'Videos'
    case 'social':
      return 'Social'
    case 'gif':
      return 'GIFs'
    default:
      return 'Project'
  }
}

const sorted = computed(() =>
  [...props.categories].sort(
    (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  )
)
</script>

<template>
  <!-- Desktop -->
  <aside
    class="sticky top-[80px] hidden h-[calc(100vh-100px)] w-72 shrink-0 lg:block"
    data-tour="projects"
  >
    <div
      class="p2-glass flex h-full flex-col overflow-hidden rounded-3xl"
    >
      <!-- Logo header — only mounted when there's actually a logo
           AND the user has scrolled past the topbar. Slides down into
           place via max-h + opacity so the rest of the sidebar shifts
           smoothly instead of jumping. -->
      <div
        v-if="hasAnyLogo"
        class="overflow-hidden border-b transition-all duration-500 ease-[var(--p2-ease-cinema)]"
        :class="isScrolled ? 'max-h-24 opacity-100' : 'max-h-0 opacity-0 border-b-transparent'"
        :style="{ borderColor: isScrolled ? 'var(--p2-hairline)' : 'transparent' }"
      >
        <div class="flex items-center justify-center px-5 py-4">
          <img
            v-if="showPlanetNineLogo"
            :src="`/logos/${headerLogo.logo}`"
            alt="logo"
            class="h-10 w-auto rounded"
          />
          <img
            v-else-if="showSidebarLogo"
            :src="`/logos/${client.logo}`"
            alt="logo"
            class="h-8 w-auto rounded"
          />
        </div>
      </div>

      <div class="shrink-0 border-b px-5 py-4" :style="{ borderColor: 'var(--p2-hairline)' }">
        <p class="p2-label">Creatives</p>
        <div class="mt-1 text-xs text-[var(--p2-text-muted)]">
          <span class="p2-mono">{{ categories.length }}</span>
          {{ categories.length === 1 ? 'creative' : 'creatives' }}
        </div>
      </div>

      <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3">
        <template v-if="isLoading">
          <div class="space-y-2 px-2">
            <div
              v-for="n in 5"
              :key="n"
              class="h-14 animate-pulse rounded-xl"
              :style="{ background: 'var(--p2-hairline)' }"
            />
          </div>
        </template>

        <template v-else-if="!categories.length">
          <div class="px-4 py-8 text-center text-sm text-[var(--p2-text-muted)]">
            No projects yet.
          </div>
        </template>

        <template v-else>
          <div class="flex flex-col gap-1">
            <button
              v-for="cat in sorted"
              :key="cat.id"
              type="button"
              :class="[
                'group relative w-full rounded-xl px-3 py-2.5 text-left transition-all duration-300 ease-[var(--p2-ease-expo)]',
                cat.is_active === 1
                  ? 'text-white shadow-sm'
                  : 'text-[var(--p2-text)] hover:bg-[var(--p2-accent-soft)]',
              ]"
              :style="
                cat.is_active === 1
                  ? { background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }
                  : undefined
              "
              @click="$emit('select', cat.id)"
            >
              <div class="flex items-center gap-2.5">
                <span
                  :class="[
                    'grid h-7 w-7 shrink-0 place-items-center rounded-lg',
                    cat.is_active === 1 ? 'bg-white/20 text-white' : '',
                  ]"
                  :style="cat.is_active !== 1
                    ? { color: 'var(--p2-accent)', background: 'var(--p2-accent-soft)' }
                    : undefined"
                  :title="typeLabel(cat.type)"
                >
                  <component :is="typeIcon(cat.type)" class="h-3.5 w-3.5" />
                </span>
                <div class="min-w-0 flex-1">
                  <div class="truncate text-sm font-medium">{{ cat.name }}</div>
                  <div
                    class="p2-mono mt-0.5 text-[11px] tracking-wide"
                    :class="cat.is_active === 1 ? 'text-white/80' : 'text-[var(--p2-text-subtle)]'"
                  >
                    {{ formatDate(cat.created_at) }}
                  </div>
                </div>
              </div>
            </button>
          </div>
        </template>
      </div>
    </div>
  </aside>

  <!-- Mobile drawer -->
  <Transition name="fade">
    <div
      v-if="isMobileOpen"
      class="fixed inset-0 z-40 backdrop-blur-md lg:hidden"
      style="background: rgba(11, 11, 16, 0.55);"
      @click="$emit('close')"
    />
  </Transition>
  <Transition name="slide-left">
    <aside
      v-if="isMobileOpen"
      class="fixed inset-y-0 left-0 z-50 w-[85%] max-w-sm shadow-2xl lg:hidden"
      :style="{ background: 'var(--p2-bg)' }"
      @click.stop
    >
      <div
        class="flex items-center justify-between border-b px-5 py-4"
        :style="{ borderColor: 'var(--p2-hairline)' }"
      >
        <div>
          <p class="p2-label" :style="{ color: 'var(--p2-accent)' }">Projects</p>
          <div class="mt-1 text-xs text-[var(--p2-text-muted)]">
            <span class="p2-mono">{{ categories.length }}</span>
            {{ categories.length === 1 ? 'project' : 'projects' }}
          </div>
        </div>
        <button
          type="button"
          class="grid h-8 w-8 place-items-center rounded-full text-[var(--p2-text-muted)] transition-colors duration-200 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-text)]"
          :style="{ background: 'var(--p2-surface-muted)', borderColor: 'var(--p2-border)' }"
          aria-label="Close"
          @click="$emit('close')"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
      <div class="h-[calc(100%-72px)] overflow-y-auto px-3 py-3">
        <div class="flex flex-col gap-1">
          <button
            v-for="cat in sorted"
            :key="cat.id"
            type="button"
            :class="[
              'group relative w-full rounded-xl px-3 py-2.5 text-left transition-all duration-300 ease-[var(--p2-ease-expo)]',
              cat.is_active === 1
                ? 'bg-[var(--p2-accent-soft)] text-[var(--p2-text)]'
                : 'text-[var(--p2-text)] hover:bg-[var(--p2-accent-soft)]',
            ]"
            @click="$emit('select', cat.id)"
          >
            <span
              v-if="cat.is_active === 1"
              class="absolute inset-y-2 left-0 w-0.5 rounded-r-full"
              :style="{ background: 'var(--p2-accent)' }"
            />
            <div class="flex items-center gap-2.5">
              <span
                class="grid h-7 w-7 shrink-0 place-items-center rounded-lg"
                :style="{ color: 'var(--p2-accent)', background: 'var(--p2-accent-soft)' }"
                :title="typeLabel(cat.type)"
              >
                <component :is="typeIcon(cat.type)" class="h-3.5 w-3.5" />
              </span>
              <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-medium">{{ cat.name }}</div>
                <div class="p2-mono mt-0.5 text-[11px] tracking-wide text-[var(--p2-text-subtle)]">
                  {{ formatDate(cat.created_at) }}
                </div>
              </div>
            </div>
          </button>
        </div>
      </div>
    </aside>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.slide-left-enter-active,
.slide-left-leave-active {
  transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}
.slide-left-enter-from,
.slide-left-leave-to {
  transform: translateX(-100%);
}
</style>
