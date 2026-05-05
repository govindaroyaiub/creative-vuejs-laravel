<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { X, Image as ImageIcon, Film, Share2, Sparkles, FolderPlus, Check } from 'lucide-vue-next'
import type { AssetType } from '../usePreviewTree'

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', v: boolean): void
  (e: 'create', value: { name: string; type: AssetType }): void
}>()

const name = ref('')
const type = ref<AssetType>('banner')
const error = ref('')
const nameInput = ref<HTMLInputElement | null>(null)

watch(
  () => props.open,
  (v) => {
    if (v) {
      name.value = ''
      type.value = 'banner'
      error.value = ''
      nextTick(() => nameInput.value?.focus())
    }
  }
)

const close = () => emit('update:open', false)

const onKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') close()
}

watch(() => props.open, (v) => {
  if (v) document.addEventListener('keydown', onKeydown)
  else document.removeEventListener('keydown', onKeydown)
})

const submit = () => {
  const trimmed = name.value.trim()
  if (!trimmed) {
    error.value = 'Project name is required'
    nameInput.value?.focus()
    return
  }
  emit('create', { name: trimmed, type: type.value })
  emit('update:open', false)
}

const types: { value: AssetType; icon: any; label: string; hint: string }[] = [
  { value: 'banner', icon: ImageIcon, label: 'Banner', hint: 'HTML5 zip banners' },
  { value: 'video', icon: Film, label: 'Video', hint: 'MP4, MOV, etc.' },
  { value: 'social', icon: Share2, label: 'Social', hint: 'Instagram / FB images' },
  { value: 'gif', icon: Sparkles, label: 'GIF', hint: 'Animated banners' },
]
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
          class="update2-root w-full max-w-lg overflow-hidden rounded-3xl border shadow-2xl"
          :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-bg)' }"
          @click.stop @keydown.enter="submit">
          <!-- Header -->
          <header
            class="flex items-start justify-between gap-3 border-b px-6 py-4"
            :style="{ borderColor: 'var(--p2-hairline)' }">
            <div>
              <div class="p2-label inline-flex items-center gap-1.5">
                <FolderPlus class="h-3 w-3" />
                New category
              </div>
              <h3 class="mt-1 text-lg font-semibold tracking-tight text-[var(--p2-text)]">Create a new category</h3>
              <p class="mt-0.5 text-xs text-[var(--p2-text-muted)]">
                A category groups related assets within the preview.
              </p>
            </div>
            <button type="button"
              class="grid h-8 w-8 place-items-center rounded-full text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-text)]"
              :style="{ background: 'var(--p2-surface-muted)' }"
              aria-label="Close" @click="close">
              <X class="h-4 w-4" />
            </button>
          </header>

          <!-- Body -->
          <div class="space-y-5 px-6 py-5">
            <!-- Name -->
            <div>
              <label class="p2-label mb-1.5 block">
                Category name <span class="text-rose-500">*</span>
              </label>
              <input ref="nameInput" v-model="name" type="text" placeholder="e.g. Holiday Campaign Banners"
                class="w-full rounded-xl border px-3 py-2 text-sm text-[var(--p2-text)] placeholder:text-[var(--p2-text-subtle)] focus:outline-none"
                :style="{
                  borderColor: error ? 'rgba(244, 63, 94, 0.4)' : 'var(--p2-border)',
                  background: 'var(--p2-surface)',
                }"
                @input="error = ''" />
              <p v-if="error" class="mt-1 text-xs font-medium text-rose-500">{{ error }}</p>
            </div>

            <!-- Type picker -->
            <div>
              <label class="p2-label mb-2 block">Type</label>
              <div class="grid grid-cols-2 gap-2">
                <button v-for="t in types" :key="t.value" type="button"
                  class="group relative flex items-start gap-2.5 rounded-xl border-2 px-3 py-2.5 text-left transition-all duration-300 ease-[var(--p2-ease-expo)]"
                  :style="type === t.value
                    ? { borderColor: 'var(--p2-accent)', background: 'var(--p2-accent-soft)' }
                    : { borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
                  @click="type = t.value">
                  <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md transition-colors duration-300 ease-[var(--p2-ease-expo)]"
                    :style="type === t.value
                      ? { background: 'var(--p2-accent)', color: 'white' }
                      : { background: 'var(--p2-accent-soft)', color: 'var(--p2-accent)' }">
                    <component :is="t.icon" class="h-3.5 w-3.5" />
                  </span>
                  <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-[var(--p2-text)]">{{ t.label }}</span>
                    <span class="block text-[11px] text-[var(--p2-text-muted)]">{{ t.hint }}</span>
                  </span>
                  <Check v-if="type === t.value"
                    class="absolute right-2 top-2 h-3.5 w-3.5"
                    :style="{ color: 'var(--p2-accent)' }" />
                </button>
              </div>
              <p class="mt-2 text-[11px] text-[var(--p2-text-subtle)]">
                The type can't be changed after creation.
              </p>
            </div>
          </div>

          <!-- Footer -->
          <footer
            class="flex justify-end gap-2 border-t px-6 py-3"
            :style="{ borderColor: 'var(--p2-hairline)', background: 'var(--p2-surface-muted)' }">
            <button type="button"
              class="inline-flex h-9 items-center rounded-full border px-4 text-xs font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-text)]"
              :style="{ borderColor: 'var(--p2-border)' }"
              @click="close">
              Cancel
            </button>
            <!-- High-contrast neutral CTA. Using the dynamic accent
                 gradient here meant pale palettes (yellow, mint, etc.)
                 left the white label illegible. The neutral pattern
                 reads cleanly in both modes regardless of palette. -->
            <button type="button"
              class="inline-flex h-9 items-center gap-1.5 rounded-full px-4 text-xs font-semibold shadow-sm transition-all duration-300 ease-[var(--p2-ease-expo)] hover:-translate-y-0.5"
              :style="{ background: 'var(--p2-text)', color: 'var(--p2-bg)' }"
              @click="submit">
              <FolderPlus class="h-3.5 w-3.5" />
              Create Category
            </button>
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
