<script setup lang="ts">
import { ref } from 'vue'
import { HelpCircle, X, Compass, Mail, Sparkle } from 'lucide-vue-next'

defineEmits<{
  (e: 'start-tour'): void
}>()

const isOpen = ref(false)
const showContactReply = ref(false)

const close = () => { isOpen.value = false }
const toggle = () => { isOpen.value = !isOpen.value }
</script>

<template>
  <div class="relative w-[19rem] max-w-[calc(100vw-2.5rem)]">
    <!-- Chat panel (above the FAB) -->
    <Transition name="assistant-panel">
      <div
        v-if="isOpen"
        class="mb-3 origin-bottom-right overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl shadow-zinc-900/15 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black/40"
      >
        <!-- Header -->
        <div
          class="flex items-center gap-3 border-b border-zinc-100 px-4 py-3 dark:border-zinc-800"
          :style="{
            background:
              'linear-gradient(135deg, color-mix(in srgb, var(--p2-accent) 9%, transparent) 0%, color-mix(in srgb, var(--p2-accent-2) 7%, transparent) 100%)',
          }"
        >
          <div
            class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-white shadow-md"
            :style="{
              background:
                'linear-gradient(135deg, var(--p2-accent), var(--p2-accent-2))',
              boxShadow:
                '0 8px 22px -6px color-mix(in srgb, var(--p2-accent) 50%, transparent)',
            }"
          >
            <Sparkle class="h-4 w-4" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Preview Assistant</div>
            <div class="text-[11px] text-zinc-500 dark:text-zinc-400">Here whenever you need a hand.</div>
          </div>
          <button
            type="button"
            class="grid h-7 w-7 shrink-0 place-items-center rounded-md text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
            aria-label="Close assistant"
            @click="close"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Messages -->
        <div class="space-y-3 p-4">
          <div class="max-w-[85%] rounded-2xl rounded-bl-md bg-zinc-100 px-3.5 py-2.5 text-sm text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100">
            How can I help?
          </div>

          <div class="flex flex-col gap-2 pt-1">
            <button
              type="button"
              class="group flex items-center gap-2.5 rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-left text-sm font-medium text-zinc-800 transition hover:border-[var(--p2-accent)] hover:bg-[color-mix(in_srgb,var(--p2-accent)_5%,transparent)] hover:text-[var(--p2-accent)] dark:border-zinc-700 dark:bg-zinc-800/40 dark:text-zinc-100"
              @click="$emit('start-tour'); close()"
            >
              <Compass class="h-4 w-4" :style="{ color: 'var(--p2-accent)' }" />
              <span class="flex-1">Take the tour</span>
            </button>

            <button
              v-if="!showContactReply"
              type="button"
              class="group flex items-center gap-2.5 rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-left text-sm font-medium text-zinc-800 transition hover:border-[var(--p2-accent)] hover:bg-[color-mix(in_srgb,var(--p2-accent)_5%,transparent)] hover:text-[var(--p2-accent)] dark:border-zinc-700 dark:bg-zinc-800/40 dark:text-zinc-100"
              @click="showContactReply = true"
            >
              <Mail class="h-4 w-4" :style="{ color: 'var(--p2-accent)' }" />
              <span class="flex-1">Contact us</span>
            </button>

            <Transition name="contact-reveal">
              <div
                v-if="showContactReply"
                class="max-w-[85%] rounded-2xl rounded-bl-md bg-zinc-100 px-3.5 py-2.5 text-sm leading-relaxed text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100"
              >
                Drop us a line at
                <a
                  href="mailto:support@planetnine.com"
                  class="font-medium underline-offset-2 hover:underline"
                  :style="{ color: 'var(--p2-accent)' }"
                >support@planetnine.com</a>.
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Floating action button -->
    <button
      type="button"
      class="ml-auto grid h-12 w-12 place-items-center rounded-full text-white shadow-xl transition active:scale-95"
      :class="isOpen ? '' : 'hover:scale-105'"
      :style="{
        background:
          'linear-gradient(135deg, var(--p2-accent), var(--p2-accent-2))',
        boxShadow:
          '0 18px 50px -10px color-mix(in srgb, var(--p2-accent) 50%, transparent)',
      }"
      :aria-label="isOpen ? 'Close assistant' : 'Need help?'"
      :title="isOpen ? 'Close' : 'Need help?'"
      @click="toggle"
    >
      <Transition name="fab-icon" mode="out-in">
        <X v-if="isOpen" key="x" class="h-5 w-5" />
        <HelpCircle v-else key="q" class="h-5 w-5" />
      </Transition>
    </button>
  </div>
</template>

<style scoped>
.assistant-panel-enter-active {
  transition: all 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}
.assistant-panel-leave-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.assistant-panel-enter-from,
.assistant-panel-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(0.96);
}

.contact-reveal-enter-active {
  transition: all 0.24s cubic-bezier(0.32, 0.72, 0, 1);
}
.contact-reveal-leave-active {
  transition: all 0.16s ease;
}
.contact-reveal-enter-from,
.contact-reveal-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.fab-icon-enter-active,
.fab-icon-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.fab-icon-enter-from,
.fab-icon-leave-to {
  opacity: 0;
  transform: scale(0.7) rotate(-45deg);
}
</style>
