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
      <div v-if="open" class="fixed inset-0 z-[80] bg-zinc-900/40 backdrop-blur-sm" @click="close" />
    </Transition>
    <Transition name="pop">
      <div v-if="open" class="fixed inset-0 z-[80] flex items-center justify-center p-4" @click="close">
        <div
          class="w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
          @click.stop
        >
          <div class="flex gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
              <AlertTriangle class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
              <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Confirm delete</h3>
              <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ message }}</p>
            </div>
          </div>
          <div class="mt-5 flex justify-end gap-2">
            <button
              type="button"
              :disabled="isLoading"
              class="rounded-lg border border-zinc-200 px-3.5 py-2 text-xs font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100"
              @click="close"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="isLoading"
              class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:opacity-70"
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
