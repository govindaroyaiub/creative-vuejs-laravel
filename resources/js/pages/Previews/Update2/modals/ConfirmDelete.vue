<script setup lang="ts">
import { watch } from 'vue'
import { AlertTriangle, Loader2 } from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
  message: string
  isLoading?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', v: boolean): void
  (e: 'confirm'): void
}>()

const close = () => {
  if (props.isLoading) return
  emit('update:open', false)
}

const onKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') close()
}

watch(() => props.open, (v) => {
  if (v) document.addEventListener('keydown', onKeydown)
  else document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-[80] backdrop-blur-md"
        style="background: rgba(11, 11, 16, 0.55);"
        @click="close"
      />
    </Transition>
    <Transition name="pop">
      <div v-if="open" class="fixed inset-0 z-[80] flex items-center justify-center p-4" @click="close">
        <div
          class="update2-root w-full max-w-md rounded-3xl border p-5 shadow-2xl"
          :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-bg)' }"
          @click.stop
        >
          <div class="flex gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-rose-500/10 text-rose-500">
              <AlertTriangle class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
              <p class="p2-label">Confirm</p>
              <h3 class="mt-1 text-base font-semibold tracking-tight text-[var(--p2-text)]">Confirm delete</h3>
              <p class="mt-1 text-sm text-[var(--p2-text-muted)]">{{ message }}</p>
            </div>
          </div>
          <div class="mt-5 flex justify-end gap-2">
            <button
              type="button"
              :disabled="isLoading"
              class="inline-flex h-9 items-center rounded-full border px-4 text-xs font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-text)] disabled:opacity-50"
              :style="{ borderColor: 'var(--p2-border)' }"
              @click="close"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="isLoading"
              class="inline-flex h-9 items-center gap-1.5 rounded-full bg-rose-600 px-4 text-xs font-semibold text-white shadow-sm transition-all duration-300 ease-p2-expo hover:-translate-y-0.5 hover:bg-rose-500 disabled:opacity-70 disabled:hover:translate-y-0"
              @click="$emit('confirm')"
            >
              <Loader2 v-if="isLoading" class="h-3.5 w-3.5 animate-spin" />
              {{ isLoading ? 'Deleting…' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.pop-enter-active, .pop-leave-active { transition: all 0.22s cubic-bezier(0.32, 0.72, 0, 1); }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: scale(0.96); }
</style>
