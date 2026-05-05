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
        <div class="p2-label mb-1 inline-flex items-center gap-1.5">
          <MessageSquare class="h-3 w-3" />
          Revision round
          <span
            v-if="feedback.is_approved === 1"
            class="ml-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium text-emerald-600 ring-1 ring-emerald-500/30"
            style="background: rgba(16, 185, 129, 0.08); letter-spacing: 0;"
          >
            <CheckCircle2 class="h-3 w-3" /> Approved
          </span>
        </div>
        <input
          :value="feedback.name"
          class="w-full rounded-xl border px-3 py-2 text-2xl font-semibold uppercase tracking-tight text-[var(--p2-text)] outline-none transition-colors duration-200 ease-[var(--p2-ease-expo)] placeholder:text-[var(--p2-text-subtle)]"
          :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
          placeholder="ROUND 1"
          @input="onName"
        />
      </div>
      <button
        type="button"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-rose-500/30 text-rose-500 transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:border-rose-500/50 hover:bg-rose-500/10"
        title="Delete round"
        aria-label="Delete round"
        @click="$emit('delete')"
      >
        <Trash2 class="h-4 w-4" />
      </button>
    </header>

    <!-- Description -->
    <section>
      <label class="p2-label mb-2 block">Notes for the client</label>
      <textarea
        :value="feedback.description"
        rows="5"
        placeholder="What changed in this round? What feedback is the client meant to give?"
        class="w-full resize-y rounded-xl border px-3 py-2 text-sm text-[var(--p2-text)] placeholder:text-[var(--p2-text-subtle)] focus:outline-none"
        :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
        @input="onDesc"
      />
    </section>

    <!-- Approval actions -->
    <section
      v-if="isDbId(feedback.id)"
      class="rounded-2xl border p-4"
      :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h3 class="text-sm font-semibold tracking-tight text-[var(--p2-text)]">Client approval</h3>
          <p class="mt-0.5 text-xs text-[var(--p2-text-muted)]">
            {{ feedback.is_approved === 1 ? 'This round has been approved by the client.' : 'Mark approved when the client signs off on this round.' }}
          </p>
        </div>
        <div class="flex gap-2">
          <button
            v-if="feedback.is_approved !== 1"
            type="button"
            class="inline-flex h-9 items-center gap-1.5 rounded-full bg-emerald-600 px-4 text-xs font-semibold text-white shadow-sm transition-all duration-300 ease-[var(--p2-ease-expo)] hover:-translate-y-0.5 hover:bg-emerald-500"
            @click="showApprove = true"
          >
            <CheckCircle2 class="h-3.5 w-3.5" /> Approve
          </button>
          <button
            v-else
            type="button"
            class="inline-flex h-9 items-center gap-1.5 rounded-full border border-rose-500/30 bg-rose-500/5 px-4 text-xs font-semibold text-rose-500 transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:border-rose-500/50 hover:bg-rose-500/10"
            @click="disapprove"
          >
            <XCircle class="h-3.5 w-3.5" /> Disapprove
          </button>
        </div>
      </div>
    </section>

    <!-- Versions summary -->
    <section>
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold tracking-tight text-[var(--p2-text)]">Versions</h3>
        <button
          type="button"
          class="inline-flex h-8 items-center gap-1 rounded-full px-3 text-[11px] font-semibold text-white transition-all duration-300 ease-[var(--p2-ease-expo)] hover:-translate-y-0.5"
          :style="{ background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }"
          @click="addSet"
        >
          <Plus class="h-3 w-3" /> New version
        </button>
      </div>
      <ul v-if="feedback.feedback_sets.length" class="space-y-1.5">
        <li
          v-for="s in feedback.feedback_sets"
          :key="s.id"
          class="flex items-center justify-between rounded-xl border px-3 py-2 text-sm transition-colors duration-300 ease-[var(--p2-ease-expo)] hover:border-[var(--p2-accent-muted)]"
          :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
        >
          <button
            type="button"
            class="min-w-0 flex-1 truncate text-left text-[var(--p2-text)] transition-colors duration-200 ease-[var(--p2-ease-expo)] hover:text-[var(--p2-accent)]"
            @click="tree.select({ kind: 'set', id: s.id }); tree.expandPathTo({ kind: 'set', id: s.id })"
          >
            {{ s.name || 'Version' }}
          </button>
          <span class="p2-mono text-[11px] text-[var(--p2-text-subtle)]">{{ s.versions?.length || 0 }} sets</span>
        </li>
      </ul>
      <p
        v-else
        class="rounded-2xl border border-dashed px-4 py-6 text-center text-xs text-[var(--p2-text-muted)]"
        :style="{ borderColor: 'var(--p2-border)' }"
      >
        No versions yet.
      </p>
    </section>

    <ApproveFeedbackModal v-model:open="showApprove" :feedback="feedback" :category="category" />
  </div>
</template>
