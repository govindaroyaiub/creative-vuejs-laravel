<script setup lang="ts">
import { ref, watch } from 'vue'
import { CheckCircle2, Upload, X, Loader2 } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

const props = defineProps<{
  open: boolean
  feedback: any
  category: any
}>()

const emit = defineEmits<{
  (e: 'update:open', v: boolean): void
}>()

const transferName = ref('')
const clientName = ref('')
const files = ref<File[]>([])
const isSubmitting = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

watch(() => props.open, (v) => {
  if (v) {
    transferName.value = ''
    clientName.value = ''
    files.value = []
  }
})

const close = () => {
  if (isSubmitting.value) return
  emit('update:open', false)
}

const onPick = () => fileInput.value?.click()
const onFiles = (e: Event) => {
  const list = (e.target as HTMLInputElement).files
  if (!list) return
  files.value = [...files.value, ...Array.from(list)]
}
const removeFile = (i: number) => {
  files.value = files.value.filter((_, idx) => idx !== i)
}

const submit = () => {
  if (!transferName.value.trim()) {
    Swal.fire({ icon: 'warning', title: 'Transfer name is required', toast: true, position: 'top-end', timer: 1600, showConfirmButton: false })
    return
  }
  const fd = new FormData()
  fd.append('transfer_name', transferName.value)
  fd.append('client_name', clientName.value)
  fd.append('feedback_id', props.feedback.id)
  files.value.forEach((f, i) => fd.append(`files[${i}]`, f, f.name))

  isSubmitting.value = true
  router.post(`/previews/feedback/approve/${props.feedback.id}`, fd, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      props.feedback.is_approved = 1
      Swal.fire({ icon: 'success', title: 'Round approved', toast: true, position: 'top-end', timer: 1400, showConfirmButton: false })
      emit('update:open', false)
    },
    onError: (errs) => {
      console.error(errs)
      Swal.fire({ icon: 'error', title: 'Approval failed', text: 'See console for details.' })
    },
    onFinish: () => { isSubmitting.value = false },
  })
}

const totalSize = (fs: File[]) =>
  fs.reduce((n, f) => n + f.size, 0)

const formatBytes = (n: number) => {
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  if (n < 1024 * 1024 * 1024) return `${(n / 1024 / 1024).toFixed(1)} MB`
  return `${(n / 1024 / 1024 / 1024).toFixed(2)} GB`
}
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
          class="update2-root w-full max-w-xl overflow-hidden rounded-3xl border shadow-2xl"
          :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-bg)' }"
          @click.stop
        >
          <header
            class="flex items-start justify-between gap-3 border-b px-6 py-4"
            :style="{ borderColor: 'var(--p2-hairline)' }"
          >
            <div>
              <div class="p2-label inline-flex items-center gap-1.5 text-emerald-600">
                <CheckCircle2 class="h-3 w-3" />
                Approve revision round
              </div>
              <h3 class="mt-1 text-lg font-semibold tracking-tight text-[var(--p2-text)]">{{ feedback?.name }}</h3>
              <p class="mt-0.5 text-xs text-[var(--p2-text-muted)]">
                Add files for the client to download as final assets.
              </p>
            </div>
            <button
              type="button"
              class="grid h-8 w-8 place-items-center rounded-full text-[var(--p2-text-muted)] transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-text)]"
              :style="{ background: 'var(--p2-surface-muted)' }"
              aria-label="Close"
              @click="close"
            >
              <X class="h-4 w-4" />
            </button>
          </header>

          <div class="space-y-4 px-6 py-5">
            <div>
              <label class="p2-label mb-1.5 block">
                Transfer name <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="transferName"
                type="text"
                placeholder="e.g. Holiday Banners — Final"
                class="w-full rounded-xl border px-3 py-2 text-sm text-[var(--p2-text)] placeholder:text-[var(--p2-text-subtle)] focus:outline-none"
                :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
              />
            </div>
            <div>
              <label class="p2-label mb-1.5 block">Client name (override)</label>
              <input
                v-model="clientName"
                type="text"
                :placeholder="category?.client_name || 'Optional override'"
                class="w-full rounded-xl border px-3 py-2 text-sm text-[var(--p2-text)] placeholder:text-[var(--p2-text-subtle)] focus:outline-none"
                :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
              />
            </div>

            <!-- Files -->
            <div>
              <label class="p2-label mb-1.5 block">Files for client</label>
              <input ref="fileInput" type="file" multiple class="hidden" @change="onFiles" />
              <button
                type="button"
                class="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed px-3 py-6 text-xs font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-accent)]"
                :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
                @click="onPick"
              >
                <Upload class="h-4 w-4" />
                Click to choose files
              </button>

              <ul v-if="files.length" class="mt-2 space-y-1.5">
                <li
                  v-for="(f, i) in files"
                  :key="i"
                  class="flex items-center justify-between gap-2 rounded-xl border px-3 py-1.5 text-xs"
                  :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface-muted)' }"
                >
                  <span class="p2-mono truncate text-[var(--p2-text)]">{{ f.name }}</span>
                  <div class="flex items-center gap-2">
                    <span class="p2-mono tabular-nums text-[var(--p2-text-subtle)]">{{ formatBytes(f.size) }}</span>
                    <button
                      type="button"
                      class="rounded-full text-[var(--p2-text-subtle)] transition-colors duration-200 ease-p2-expo hover:text-rose-500"
                      aria-label="Remove"
                      @click="removeFile(i)"
                    >
                      <X class="h-3.5 w-3.5" />
                    </button>
                  </div>
                </li>
              </ul>
              <p v-if="files.length" class="mt-1.5 text-[11px] text-[var(--p2-text-subtle)]">
                {{ files.length }} file{{ files.length === 1 ? '' : 's' }} · {{ formatBytes(totalSize(files)) }}
              </p>
            </div>
          </div>

          <footer
            class="flex justify-end gap-2 border-t px-6 py-3"
            :style="{ borderColor: 'var(--p2-hairline)', background: 'var(--p2-surface-muted)' }"
          >
            <button
              type="button"
              :disabled="isSubmitting"
              class="inline-flex h-9 items-center rounded-full border px-4 text-xs font-medium text-[var(--p2-text-muted)] transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-text)] disabled:opacity-50"
              :style="{ borderColor: 'var(--p2-border)' }"
              @click="close"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="isSubmitting"
              class="inline-flex h-9 items-center gap-1.5 rounded-full bg-emerald-600 px-4 text-xs font-semibold text-white shadow-sm transition-all duration-300 ease-p2-expo hover:-translate-y-0.5 hover:bg-emerald-500 disabled:opacity-70 disabled:hover:translate-y-0"
              @click="submit"
            >
              <Loader2 v-if="isSubmitting" class="h-3.5 w-3.5 animate-spin" />
              <CheckCircle2 v-else class="h-3.5 w-3.5" />
              {{ isSubmitting ? 'Approving…' : 'Approve' }}
            </button>
          </footer>
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
