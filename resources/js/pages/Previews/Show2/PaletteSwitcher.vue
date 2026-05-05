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
        class="fixed inset-0 z-50 backdrop-blur-md"
        style="background: rgba(11, 11, 16, 0.55);"
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
          class="show2-root w-full max-w-2xl overflow-hidden rounded-3xl border shadow-2xl"
          :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-bg)' }"
          @click.stop
        >
          <header
            class="flex items-center justify-between border-b px-6 py-4"
            :style="{ borderColor: 'var(--p2-hairline)' }"
          >
            <div class="flex items-center gap-2">
              <PaletteIcon class="h-4 w-4 text-[var(--p2-text-muted)]" />
              <div>
                <p class="p2-label">Theme</p>
                <h2 class="text-base font-semibold tracking-tight text-[var(--p2-text)]">Choose a palette</h2>
              </div>
            </div>
            <button
              type="button"
              class="grid h-8 w-8 place-items-center rounded-full text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-text)]"
              :style="{ background: 'var(--p2-surface-muted)' }"
              aria-label="Close"
              @click="close"
            >
              <X class="h-4 w-4" />
            </button>
          </header>

          <div class="grid max-h-[70vh] grid-cols-2 gap-3 overflow-y-auto p-6 sm:grid-cols-3 md:grid-cols-4">
            <button
              v-for="c in allColors"
              :key="c.id"
              type="button"
              :class="[
                'group relative overflow-hidden rounded-2xl border p-3 text-left transition-all duration-300 ease-[var(--p2-ease-expo)]',
                c.id === currentId ? 'shadow-md' : 'hover:-translate-y-0.5',
              ]"
              :style="{
                borderColor: c.id === currentId ? 'var(--p2-text)' : 'var(--p2-border)',
                borderWidth: c.id === currentId ? '2px' : '1px',
                background: 'var(--p2-surface)',
              }"
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
                <span class="truncate text-xs font-medium text-[var(--p2-text)]">{{ c.name }}</span>
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

          <footer
            class="border-t px-6 py-3 text-center"
            :style="{ borderColor: 'var(--p2-hairline)', background: 'var(--p2-surface-muted)' }"
          >
            <span class="p2-label">Selecting a theme refreshes the canvas with new colors</span>
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
