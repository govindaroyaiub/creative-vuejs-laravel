<script setup lang="ts">
import { computed } from 'vue'
import { ImagePlay, Video, Image, GalleryThumbnails, X, Folder } from 'lucide-vue-next'

const props = defineProps<{
  categories: any[]
  activeCategory: any
  isLoading: boolean
  isMobileOpen: boolean
}>()

defineEmits<{
  (e: 'select', id: number): void
  (e: 'close'): void
}>()

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
    <div class="h-full overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
      <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
        <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">Creatives</div>
        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
          Total: {{ categories.length }} {{ categories.length === 1 ? 'creative' : 'creatives' }}
        </div>
      </div>

      <div class="h-[calc(100%-72px)] overflow-y-auto px-3 py-3">
        <template v-if="isLoading">
          <div class="space-y-2 px-2">
            <div v-for="n in 5" :key="n" class="h-14 animate-pulse rounded-xl bg-zinc-100 dark:bg-zinc-800" />
          </div>
        </template>

        <template v-else-if="!categories.length">
          <div class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
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
                'group relative w-full rounded-xl px-3 py-2.5 text-left transition',
                cat.is_active === 1
                  ? 'text-white shadow-sm'
                  : 'text-zinc-700 hover:bg-[var(--p2-accent-soft)] dark:text-zinc-300 dark:hover:bg-[var(--p2-accent-soft)]',
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
                    cat.is_active === 1
                      ? 'bg-white/20 text-white'
                      : 'bg-zinc-100 dark:bg-zinc-800',
                  ]"
                  :style="cat.is_active !== 1 ? { color: 'var(--p2-accent)' } : undefined"
                  :title="typeLabel(cat.type)"
                >
                  <component :is="typeIcon(cat.type)" class="h-3.5 w-3.5" />
                </span>
                <div class="min-w-0 flex-1">
                  <div class="truncate text-sm font-medium">{{ cat.name }}</div>
                  <div
                    class="mt-0.5 text-[11px]"
                    :class="cat.is_active === 1 ? 'text-white/80' : 'text-zinc-400 dark:text-zinc-500'"
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
      class="fixed inset-0 z-40 bg-zinc-900/40 backdrop-blur-sm lg:hidden"
      @click="$emit('close')"
    />
  </Transition>
  <Transition name="slide-left">
    <aside
      v-if="isMobileOpen"
      class="fixed inset-y-0 left-0 z-50 w-[85%] max-w-sm bg-white shadow-2xl dark:bg-zinc-900 lg:hidden"
      @click.stop
    >
      <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
        <div>
          <div
            class="text-[11px] font-semibold uppercase tracking-[0.12em]"
            :style="{ color: 'var(--p2-accent)' }"
          >Projects</div>
          <div class="mt-1 text-xs text-zinc-500">
            {{ categories.length }} {{ categories.length === 1 ? 'project' : 'projects' }}
          </div>
        </div>
        <button
          type="button"
          class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
          aria-label="Close"
          @click="$emit('close')"
        >
          <X class="h-5 w-5" />
        </button>
      </div>
      <div class="h-[calc(100%-72px)] overflow-y-auto px-3 py-3">
        <div class="flex flex-col gap-1">
          <button
            v-for="cat in sorted"
            :key="cat.id"
            type="button"
            :class="[
              'group relative w-full rounded-xl px-3 py-2.5 text-left transition',
              cat.is_active === 1
                ? 'bg-[var(--p2-accent-soft)] text-zinc-900'
                : 'text-zinc-700 hover:bg-zinc-50',
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
                class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-zinc-100"
                :style="{ color: 'var(--p2-accent)' }"
                :title="typeLabel(cat.type)"
              >
                <component :is="typeIcon(cat.type)" class="h-3.5 w-3.5" />
              </span>
              <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-medium">{{ cat.name }}</div>
                <div class="mt-0.5 text-[11px] text-zinc-400">
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
