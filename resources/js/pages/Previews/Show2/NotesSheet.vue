<script setup lang="ts">
import { computed, watch } from 'vue'
import { X, MessageSquare, CheckCircle2 } from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
  feedback: any
}>()

const emit = defineEmits<{
  (e: 'update:open', v: boolean): void
}>()

const close = () => emit('update:open', false)

const onKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') close()
}

watch(
  () => props.open,
  (v) => {
    if (v) {
      document.addEventListener('keydown', onKeydown)
      document.body.style.overflow = 'hidden'
    } else {
      document.removeEventListener('keydown', onKeydown)
      document.body.style.overflow = ''
    }
  }
)

const description = computed(() => props.feedback?.description || '')
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-50 bg-zinc-900/40 backdrop-blur-sm"
        @click="close"
      />
    </Transition>
    <Transition name="slide-right">
      <aside
        v-if="open"
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-white shadow-2xl dark:bg-zinc-900"
      >
        <header class="flex items-start justify-between gap-3 border-b border-zinc-100 px-6 py-5 dark:border-zinc-800">
          <div>
            <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">
              <MessageSquare class="h-3 w-3" />
              Revision Notes
            </div>
            <div class="mt-1 flex items-center gap-2">
              <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                {{ feedback?.name || 'No round selected' }}
              </h2>
              <span
                v-if="feedback?.is_approved === 1"
                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800/60"
              >
                <CheckCircle2 class="h-3 w-3" />
                Approved
              </span>
            </div>
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

        <div class="flex-1 overflow-y-auto px-6 py-5">
          <div
            v-if="description"
            class="prose prose-sm max-w-none text-zinc-700 prose-headings:text-zinc-900 prose-a:text-[var(--p2-accent)] dark:prose-invert dark:text-zinc-300 dark:prose-headings:text-zinc-100"
            v-html="description"
          />
          <div
            v-else
            class="flex h-full items-center justify-center text-center"
          >
            <div>
              <MessageSquare class="mx-auto h-10 w-10 text-zinc-300 dark:text-zinc-700" />
              <p class="mt-3 text-sm font-medium text-zinc-700 dark:text-zinc-200">No notes for this round</p>
              <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">When the team adds context, it will appear here.</p>
            </div>
          </div>
        </div>
      </aside>
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
.slide-right-enter-active,
.slide-right-leave-active {
  transition: transform 0.32s cubic-bezier(0.32, 0.72, 0, 1);
}
.slide-right-enter-from,
.slide-right-leave-to {
  transform: translateX(100%);
}
</style>
