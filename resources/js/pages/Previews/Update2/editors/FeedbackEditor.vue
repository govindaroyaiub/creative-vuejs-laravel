<script setup lang="ts">
import { inject, ref } from 'vue'
import { Trash2, Plus, CheckCircle2, XCircle, MessageSquare } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import type { PreviewTree } from '../usePreviewTree'
import { isDbId } from '../usePreviewTree'
import ApproveFeedbackModal from '../modals/ApproveFeedbackModal.vue'

const props = defineProps<{ feedback: any; category: any }>()
defineEmits<{ (e: 'delete'): void }>()

const tree = inject<PreviewTree>('tree')!

const showApprove = ref(false)

const onName = (e: Event) => {
  props.feedback.name = (e.target as HTMLInputElement).value.toUpperCase()
  tree.markDirty({ kind: 'feedback', id: props.feedback.id })
}
const onDesc = (e: Event) => {
  props.feedback.description = (e.target as HTMLTextAreaElement).value
  tree.markDirty({ kind: 'feedback', id: props.feedback.id })
}

const addSet = () => {
  const s = tree.addSet(props.feedback.id, '')
  if (s) tree.markDirty({ kind: 'set', id: s.id })
}

const disapprove = () => {
  if (!isDbId(props.feedback.id)) return
  Swal.fire({
    icon: 'warning',
    title: 'Disapprove this round?',
    text: 'Removes the approval and unlinks the file transfer.',
    showCancelButton: true,
    confirmButtonText: 'Disapprove',
  }).then((r) => {
    if (!r.isConfirmed) return
    router.put(`/previews/feedback/disapprove/${props.feedback.id}`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        props.feedback.is_approved = 0
        Swal.fire({ icon: 'success', title: 'Disapproved', timer: 900, showConfirmButton: false, toast: true, position: 'top-end' })
      },
      onError: (errs) => console.error(errs),
    })
  })
}
</script>

<template>
  <div class="space-y-6">
    <header class="flex items-start justify-between gap-4">
      <div class="min-w-0 flex-1">
        <div class="mb-1 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">
          <MessageSquare class="h-3 w-3" />
          Revision round
          <span
            v-if="feedback.is_approved === 1"
            class="ml-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50"
          >
            <CheckCircle2 class="h-3 w-3" /> Approved
          </span>
        </div>
        <input
          :value="feedback.name"
          class="w-full bg-transparent text-2xl font-semibold uppercase tracking-tight text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-zinc-100 dark:placeholder:text-zinc-600"
          placeholder="ROUND 1"
          @input="onName"
        />
      </div>
      <button
        type="button"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-rose-200 text-rose-600 transition hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-400 dark:hover:bg-rose-950/30"
        title="Delete round"
        aria-label="Delete round"
        @click="$emit('delete')"
      >
        <Trash2 class="h-4 w-4" />
      </button>
    </header>

    <!-- Description -->
    <section>
      <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-500">
        Notes for the client
      </label>
      <textarea
        :value="feedback.description"
        rows="5"
        placeholder="What changed in this round? What feedback is the client meant to give?"
        class="w-full resize-y rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-400 dark:border-zinc-800 dark:bg-zinc-950/30 dark:text-zinc-200 dark:placeholder:text-zinc-500"
        @input="onDesc"
      />
    </section>

    <!-- Approval actions -->
    <section v-if="isDbId(feedback.id)" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Client approval</h3>
          <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
            {{ feedback.is_approved === 1 ? 'This round has been approved by the client.' : 'Mark approved when the client signs off on this round.' }}
          </p>
        </div>
        <div class="flex gap-2">
          <button
            v-if="feedback.is_approved !== 1"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700"
            @click="showApprove = true"
          >
            <CheckCircle2 class="h-3.5 w-3.5" /> Approve
          </button>
          <button
            v-else
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-2 text-xs font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100"
            @click="disapprove"
          >
            <XCircle class="h-3.5 w-3.5" /> Disapprove
          </button>
        </div>
      </div>
    </section>

    <!-- Concepts summary -->
    <section>
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Concepts</h3>
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-md border border-zinc-200 px-2.5 py-1.5 text-[11px] font-medium text-zinc-700 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100"
          @click="addSet"
        >
          <Plus class="h-3 w-3" /> New concept
        </button>
      </div>
      <ul v-if="feedback.feedback_sets.length" class="space-y-1.5">
        <li
          v-for="s in feedback.feedback_sets"
          :key="s.id"
          class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-900"
        >
          <button
            type="button"
            class="min-w-0 flex-1 truncate text-left text-zinc-800 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100"
            @click="tree.select({ kind: 'set', id: s.id }); tree.expandPathTo({ kind: 'set', id: s.id })"
          >
            {{ s.name || 'Concept' }}
          </button>
          <span class="text-[11px] text-zinc-400 dark:text-zinc-500">{{ s.versions?.length || 0 }} sets</span>
        </li>
      </ul>
      <p v-else class="rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-center text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
        No concepts yet.
      </p>
    </section>

    <ApproveFeedbackModal v-model:open="showApprove" :feedback="feedback" :category="category" />
  </div>
</template>
