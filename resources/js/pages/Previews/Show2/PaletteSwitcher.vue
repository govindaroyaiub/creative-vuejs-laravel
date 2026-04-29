<script setup lang="ts">
import { watch } from 'vue'
import { X, Check, Palette as PaletteIcon } from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
  allColors: any[]
  currentId: number | undefined
}>()

const emit = defineEmits<{
  (e: 'update:open', v: boolean): void
  (e: 'select', id: number): void
}>()

const close = () => emit('update:open', false)

const onKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') close()
}

watch(
  () => props.open,
  (v) => {
    if (v) document.addEventListener('keydown', onKeydown)
    else document.removeEventListener('keydown', onKeydown)
  }
)
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-50 bg-zinc-900/30 backdrop-blur-sm"
        @click="close"
      />
    </Transition>
    <Transition name="pop">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click="close"
      >
        <div
          class="w-full max-w-2xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
          @click.stop
        >
          <header class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
            <div class="flex items-center gap-2">
              <PaletteIcon class="h-4 w-4 text-zinc-500 dark:text-zinc-400" />
              <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Choose a theme</h2>
            </div>
            <button
              type="button"
              class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
              aria-label="Close"
              @click="close"
            >
              <X class="h-5 w-5" />
            </button>
          </header>

          <div class="grid max-h-[70vh] grid-cols-2 gap-3 overflow-y-auto p-6 sm:grid-cols-3 md:grid-cols-4">
            <button
              v-for="c in allColors"
              :key="c.id"
              type="button"
              :class="[
                'group relative overflow-hidden rounded-xl border-2 p-3 text-left transition',
                c.id === currentId
                  ? 'border-zinc-900 shadow-md dark:border-zinc-100'
                  : 'border-zinc-200 hover:border-zinc-300 hover:shadow-md dark:border-zinc-700 dark:hover:border-zinc-600',
              ]"
              @click="emit('select', c.id)"
            >
              <!-- Color swatches stack -->
              <div class="flex gap-1">
                <span
                  class="h-10 flex-1 rounded-lg"
                  :style="{ background: c.primary }"
                />
                <div class="flex flex-col gap-1">
                  <span class="h-[18px] w-6 rounded" :style="{ background: c.tertiary }" />
                  <span class="h-[18px] w-6 rounded" :style="{ background: c.quaternary || c.secondary }" />
                </div>
              </div>
              <div class="mt-2 flex items-center justify-between gap-1">
                <span class="truncate text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ c.name }}</span>
                <span
                  v-if="c.id === currentId"
                  class="grid h-5 w-5 shrink-0 place-items-center rounded-full text-white"
                  :style="{ background: c.primary }"
                >
                  <Check class="h-3 w-3" />
                </span>
              </div>
            </button>
          </div>

          <footer class="border-t border-zinc-100 bg-zinc-50 px-6 py-3 text-center text-[11px] text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950/40 dark:text-zinc-400">
            Selecting a theme will refresh the page with new colors.
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
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
.pop-enter-active,
.pop-leave-active {
  transition: all 0.22s cubic-bezier(0.32, 0.72, 0, 1);
}
.pop-enter-from,
.pop-leave-to {
  opacity: 0;
  transform: scale(0.96);
}
</style>
