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
      <div v-if="open" class="fixed inset-0 z-[80] bg-zinc-900/40 backdrop-blur-sm" @click="close" />
    </Transition>
    <Transition name="pop">
      <div v-if="open" class="fixed inset-0 z-[80] flex items-center justify-center p-4" @click="close">
        <div
          class="w-full max-w-lg overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
          @click.stop @keydown.enter="submit">
          <!-- Header -->
          <header
            class="flex items-start justify-between gap-3 border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
            <div>
              <div
                class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">
                <FolderPlus class="h-3 w-3" />
                New category
              </div>
              <h3 class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Create a new category</h3>
              <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                A category groups related assets within the preview.
              </p>
            </div>
            <button type="button"
              class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
              aria-label="Close" @click="close">
              <X class="h-5 w-5" />
            </button>
          </header>

          <!-- Body -->
          <div class="space-y-5 px-6 py-5">
            <!-- Name -->
            <div>
              <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">
                Category name <span class="text-rose-500">*</span>
              </label>
              <input ref="nameInput" v-model="name" type="text" placeholder="e.g. Holiday Campaign Banners"
                class="w-full rounded-lg border bg-white px-3 py-2 text-sm text-zinc-800 placeholder:text-zinc-400 focus:outline-none focus:ring-1 dark:bg-zinc-950/30 dark:text-zinc-200 dark:placeholder:text-zinc-500"
                :class="error
                  ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-400 dark:border-rose-900/60'
                  : 'border-zinc-200 focus:border-zinc-400 focus:ring-zinc-400 dark:border-zinc-800'"
                @input="error = ''" />
              <p v-if="error" class="mt-1 text-xs font-medium text-rose-500">{{ error }}</p>
            </div>

            <!-- Type picker -->
            <div>
              <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">
                Type
              </label>
              <div class="grid grid-cols-2 gap-2">
                <button v-for="t in types" :key="t.value" type="button" :class="[
                  'group relative flex items-start gap-2.5 rounded-lg border-2 px-3 py-2.5 text-left transition',
                  type === t.value
                    ? 'border-zinc-900 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-800'
                    : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-800 dark:hover:border-zinc-700',
                ]" @click="type = t.value">
                  <span :class="[
                    'grid h-7 w-7 shrink-0 place-items-center rounded-md transition',
                    type === t.value
                      ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                      : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
                  ]">
                    <component :is="t.icon" class="h-3.5 w-3.5" />
                  </span>
                  <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ t.label }}</span>
                    <span class="block text-[11px] text-zinc-500 dark:text-zinc-400">{{ t.hint }}</span>
                  </span>
                  <Check v-if="type === t.value"
                    class="absolute right-2 top-2 h-3.5 w-3.5 text-zinc-900 dark:text-zinc-100" />
                </button>
              </div>
              <p class="mt-2 text-[11px] text-zinc-400 dark:text-zinc-500">
                The type can't be changed after creation.
              </p>
            </div>
          </div>

          <!-- Footer -->
          <footer
            class="flex justify-end gap-2 border-t border-zinc-100 bg-zinc-50/50 px-6 py-3 dark:border-zinc-800 dark:bg-zinc-950/30">
            <button type="button"
              class="rounded-lg border border-zinc-200 px-3.5 py-2 text-xs font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100"
              @click="close">
              Cancel
            </button>
            <button type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-900 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white"
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
