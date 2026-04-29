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
      <div v-if="open" class="fixed inset-0 z-[80] bg-zinc-900/40 backdrop-blur-sm" @click="close" />
    </Transition>
    <Transition name="pop">
      <div v-if="open" class="fixed inset-0 z-[80] flex items-center justify-center p-4" @click="close">
        <div
          class="w-full max-w-xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
          @click.stop
        >
          <header class="flex items-start justify-between gap-3 border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
            <div>
              <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-emerald-600 dark:text-emerald-400">
                <CheckCircle2 class="h-3 w-3" />
                Approve revision round
              </div>
              <h3 class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ feedback?.name }}</h3>
              <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                Add files for the client to download as final assets.
              </p>
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

          <div class="space-y-4 px-6 py-5">
            <div>
              <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">
                Transfer name <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="transferName"
                type="text"
                placeholder="e.g. Holiday Banners — Final"
                class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-400 dark:border-zinc-800 dark:bg-zinc-950/30 dark:text-zinc-200 dark:placeholder:text-zinc-500"
              />
            </div>
            <div>
              <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">
                Client name (override)
              </label>
              <input
                v-model="clientName"
                type="text"
                :placeholder="category?.client_name || 'Optional override'"
                class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-400 dark:border-zinc-800 dark:bg-zinc-950/30 dark:text-zinc-200 dark:placeholder:text-zinc-500"
              />
            </div>

            <!-- Files -->
            <div>
              <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">
                Files for client
              </label>
              <input ref="fileInput" type="file" multiple class="hidden" @change="onFiles" />
              <button
                type="button"
                class="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed border-zinc-200 bg-white px-3 py-6 text-xs font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:text-zinc-100"
                @click="onPick"
              >
                <Upload class="h-4 w-4" />
                Click to choose files
              </button>

              <ul v-if="files.length" class="mt-2 space-y-1.5">
                <li
                  v-for="(f, i) in files"
                  :key="i"
                  class="flex items-center justify-between gap-2 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs dark:border-zinc-800 dark:bg-zinc-950/40"
                >
                  <span class="truncate font-mono text-zinc-700 dark:text-zinc-300">{{ f.name }}</span>
                  <div class="flex items-center gap-2">
                    <span class="font-mono tabular-nums text-zinc-400 dark:text-zinc-500">{{ formatBytes(f.size) }}</span>
                    <button
                      type="button"
                      class="rounded text-zinc-400 hover:text-rose-500"
                      aria-label="Remove"
                      @click="removeFile(i)"
                    >
                      <X class="h-3.5 w-3.5" />
                    </button>
                  </div>
                </li>
              </ul>
              <p v-if="files.length" class="mt-1.5 text-[11px] text-zinc-400 dark:text-zinc-500">
                {{ files.length }} file{{ files.length === 1 ? '' : 's' }} · {{ formatBytes(totalSize(files)) }}
              </p>
            </div>
          </div>

          <footer class="flex justify-end gap-2 border-t border-zinc-100 bg-zinc-50/50 px-6 py-3 dark:border-zinc-800 dark:bg-zinc-950/30">
            <button
              type="button"
              :disabled="isSubmitting"
              class="rounded-lg border border-zinc-200 px-3.5 py-2 text-xs font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100"
              @click="close"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="isSubmitting"
              class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-70"
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
