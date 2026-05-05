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
        class="fixed inset-0 z-50 backdrop-blur-md"
        style="background: rgba(11, 11, 16, 0.55);"
        @click="close"
      />
    </Transition>
    <Transition name="slide-right">
      <aside
        v-if="open"
        class="show2-root fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col shadow-2xl"
        :style="{ background: 'var(--p2-bg)' }"
      >
        <header
          class="flex items-start justify-between gap-3 border-b px-6 py-5"
          :style="{ borderColor: 'var(--p2-hairline)' }"
        >
          <div>
            <div class="p2-label inline-flex items-center gap-1.5">
              <MessageSquare class="h-3 w-3" />
              Revision Notes
            </div>
            <div class="mt-1 flex items-center gap-2">
              <h2 class="text-lg font-semibold tracking-tight text-[var(--p2-text)]">
                {{ feedback?.name || 'No round selected' }}
              </h2>
              <span
                v-if="feedback?.is_approved === 1"
                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium text-emerald-600 ring-1 ring-emerald-500/30"
                style="background: rgba(16, 185, 129, 0.08);"
              >
                <CheckCircle2 class="h-3 w-3" />
                Approved
              </span>
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

        <div class="flex-1 overflow-y-auto px-6 py-5">
          <!-- Rendered as text, NOT v-html. The feedback editor is a
               plain textarea, so the content is plain text — rendering
               it as HTML opened a stored-XSS hole reachable from any
               public preview viewer. `white-space: pre-wrap` keeps
               paragraph breaks. -->
          <div
            v-if="description"
            class="text-sm leading-relaxed text-[var(--p2-text)]"
            style="white-space: pre-wrap; word-break: break-word;"
          >{{ description }}</div>
          <div
            v-else
            class="flex h-full items-center justify-center text-center"
          >
            <div>
              <MessageSquare class="mx-auto h-10 w-10 text-[var(--p2-text-subtle)] opacity-50" />
              <p class="mt-3 text-sm font-medium text-[var(--p2-text)]">No notes for this round</p>
              <p class="mt-1 text-xs text-[var(--p2-text-muted)]">When the team adds context, it will appear here.</p>
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
