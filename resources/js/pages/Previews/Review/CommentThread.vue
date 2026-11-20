<script setup lang="ts">
/**
 * DEMO ONLY: the conversation for one creative, across all its rounds.
 * Threads written on an earlier round keep a round tag, so the client sees
 * "Round 1: logo bigger" next to the team's "Done in Round 2".
 */
import { computed, inject, nextTick, ref, watch } from 'vue'
import { CheckCircle2, Circle, CornerDownRight, Send, Clock } from 'lucide-vue-next'
import type { LineageEntry, ReviewDemo } from './useReviewDemo'

const props = defineProps<{
  entry: LineageEntry
  isVideo: boolean
  getTime?: () => number | null
}>()

const emit = defineEmits<{ (e: 'seek', t: number): void }>()

const demo = inject<ReviewDemo>('reviewDemo')!

const threads = computed(() => demo.threadsFor(props.entry.assetKey))
const draft = ref('')
const replyTo = ref<string | null>(null)
const replyDraft = ref('')
const attachTime = ref(true)
const listEl = ref<HTMLElement | null>(null)

watch(() => props.entry.assetKey, () => {
  draft.value = ''
  replyTo.value = null
  replyDraft.value = ''
})

const fmtTime = (t: number) => {
  const m = Math.floor(t / 60)
  const s = Math.floor(t % 60)
  return `${m}:${String(s).padStart(2, '0')}`
}

const ago = (ts: number) => {
  const d = (Date.now() - ts) / 1000
  if (d < 60) return 'just now'
  if (d < 3600) return `${Math.floor(d / 60)}m`
  if (d < 86400) return `${Math.floor(d / 3600)}h`
  return `${Math.floor(d / 86400)}d`
}

const initials = (name: string) =>
  name
    .split(/\s+/)
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()

const send = async () => {
  if (!draft.value.trim()) return
  const t = props.isVideo && attachTime.value ? props.getTime?.() ?? null : null
  demo.addComment({ entry: props.entry, body: draft.value, timecode: t })
  draft.value = ''
  await nextTick()
  listEl.value?.scrollTo({ top: listEl.value.scrollHeight, behavior: 'smooth' })
}

const sendReply = (rootId: string) => {
  if (!replyDraft.value.trim()) return
  demo.addComment({ entry: props.entry, body: replyDraft.value, parentId: rootId })
  replyDraft.value = ''
  replyTo.value = null
}

const onKey = (e: KeyboardEvent, fn: () => void) => {
  if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) {
    e.preventDefault()
    fn()
  }
}
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col">
    <div ref="listEl" class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-4">
      <p v-if="!threads.length" class="px-2 py-10 text-center text-sm" :style="{ color: 'var(--p2-text-muted)' }">
        No comments yet. Be the first to leave feedback on this creative.
      </p>

      <article
        v-for="t in threads"
        :key="t.root.id"
        class="rounded-2xl border p-3 transition-opacity"
        :class="t.root.resolved ? 'opacity-70' : ''"
        :style="{ borderColor: 'var(--p2-border)', background: 'var(--p2-surface)' }"
      >
        <header class="flex items-center gap-2">
          <span
            class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-[10px] font-semibold"
            :style="t.root.author.kind === 'team'
              ? { background: 'var(--p2-accent)', color: '#fff' }
              : { background: 'var(--p2-accent-soft)', color: 'var(--p2-accent)' }"
          >{{ initials(t.root.author.name) }}</span>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
              <span class="font-semibold" :style="{ color: 'var(--p2-text)' }">{{ t.root.author.name }}</span>
              <span v-if="t.root.author.guest" class="rv-badge">Guest</span>
              <span v-else-if="t.root.author.kind === 'team'" class="rv-badge rv-badge-team">Team</span>
              <span :style="{ color: 'var(--p2-text-subtle)' }">· {{ ago(t.root.createdAt) }}</span>
            </div>
          </div>
          <span
            v-if="t.root.roundId !== entry.roundId"
            class="p2-mono shrink-0 rounded-full px-2 py-0.5 text-[10px]"
            :style="{ background: 'var(--p2-hairline)', color: 'var(--p2-text-muted)' }"
            :title="`Written on ${t.root.roundName}`"
          >{{ t.root.roundName }}</span>
          <button
            type="button"
            class="shrink-0 rounded-full p-1"
            :title="t.root.resolved ? `Resolved by ${t.root.resolved.by}. Click to reopen` : 'Mark resolved'"
            @click="demo.toggleResolved(t.root.id)"
          >
            <CheckCircle2 v-if="t.root.resolved" class="h-4 w-4 text-emerald-500" />
            <Circle v-else class="h-4 w-4" :style="{ color: 'var(--p2-text-subtle)' }" />
          </button>
        </header>

        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed" :style="{ color: 'var(--p2-text)' }">
          <button
            v-if="t.root.timecode != null"
            type="button"
            class="p2-mono mr-1 inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[11px] font-semibold"
            :style="{ background: 'var(--p2-accent-soft)', color: 'var(--p2-accent)' }"
            @click="emit('seek', t.root.timecode!)"
          ><Clock class="h-3 w-3" />{{ fmtTime(t.root.timecode!) }}</button>{{ t.root.body }}
        </p>

        <div v-for="r in t.replies" :key="r.id" class="mt-3 flex gap-2 border-l-2 pl-3" :style="{ borderColor: 'var(--p2-hairline)' }">
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
              <span class="font-semibold" :style="{ color: 'var(--p2-text)' }">{{ r.author.name }}</span>
              <span v-if="r.author.guest" class="rv-badge">Guest</span>
              <span v-else-if="r.author.kind === 'team'" class="rv-badge rv-badge-team">Team</span>
              <span :style="{ color: 'var(--p2-text-subtle)' }">· {{ ago(r.createdAt) }}</span>
            </div>
            <p class="mt-1 whitespace-pre-line text-sm" :style="{ color: 'var(--p2-text)' }">{{ r.body }}</p>
          </div>
        </div>

        <div v-if="replyTo === t.root.id" class="mt-3">
          <textarea
            v-model="replyDraft"
            rows="2"
            class="rv-input w-full resize-none rounded-xl px-3 py-2 text-sm"
            placeholder="Write a reply…"
            autofocus
            @keydown="onKey($event, () => sendReply(t.root.id))"
          />
          <div class="mt-1.5 flex justify-end gap-2 text-xs">
            <button type="button" class="rounded-full px-3 py-1.5" @click="replyTo = null">Cancel</button>
            <button type="button" class="rv-primary rounded-full px-3 py-1.5 font-medium" @click="sendReply(t.root.id)">Reply</button>
          </div>
        </div>
        <button
          v-else
          type="button"
          class="mt-2 inline-flex items-center gap-1 text-xs"
          :style="{ color: 'var(--p2-text-muted)' }"
          @click="replyTo = t.root.id; replyDraft = ''"
        >
          <CornerDownRight class="h-3.5 w-3.5" /> Reply
        </button>
      </article>
    </div>

    <!-- Composer -->
    <div class="shrink-0 border-t p-3" :style="{ borderColor: 'var(--p2-hairline)' }">
      <div class="rv-input flex items-end gap-2 rounded-2xl px-3 py-2">
        <textarea
          v-model="draft"
          rows="2"
          class="min-h-[2.5rem] w-full resize-none bg-transparent text-sm outline-none"
          :placeholder="`Comment on ${entry.roundName}…`"
          @keydown="onKey($event, send)"
        />
        <button
          type="button"
          class="rv-primary grid h-8 w-8 shrink-0 place-items-center rounded-full disabled:opacity-40"
          :disabled="!draft.trim()"
          aria-label="Send comment"
          @click="send"
        >
          <Send class="h-3.5 w-3.5" />
        </button>
      </div>
      <div class="mt-1.5 flex items-center justify-between px-1 text-[11px]" :style="{ color: 'var(--p2-text-subtle)' }">
        <label v-if="isVideo" class="inline-flex cursor-pointer items-center gap-1.5">
          <input v-model="attachTime" type="checkbox" class="h-3 w-3" /> Pin to current video time
        </label>
        <span v-else />
        <span>Ctrl + Enter to send</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rv-badge {
  border-radius: 9999px;
  padding: 0 0.4rem;
  font-size: 10px;
  line-height: 1.1rem;
  background: var(--p2-hairline);
  color: var(--p2-text-muted);
}
.rv-badge-team {
  background: var(--p2-accent-soft);
  color: var(--p2-accent);
}
.rv-input {
  border: 1px solid var(--p2-border);
  background: var(--p2-surface);
  color: var(--p2-text);
}
.rv-input:focus-within,
textarea.rv-input:focus {
  outline: none;
  border-color: var(--p2-accent);
}
.rv-primary {
  background: var(--p2-accent);
  color: #fff;
}
</style>
