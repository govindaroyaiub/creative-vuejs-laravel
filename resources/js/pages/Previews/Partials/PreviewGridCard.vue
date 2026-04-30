<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
dayjs.extend(relativeTime);
import { Check } from 'lucide-vue-next';
import PreviewActionButtons from './PreviewActionButtons.vue';

interface Props {
    preview: any;
    status: 'inProgress' | 'completed' | 'noFeedback';
}

const props = defineProps<Props>();

const accent = computed(() => props.preview.color_palette?.primary || '#1A1A1A');

const initials = (name?: string) => {
    if (!name) return '?';
    return name.split(/\s+/).filter(Boolean).slice(0, 2).map(s => s[0]).join('').toUpperCase();
};

const total = computed(() => Number(props.preview.total_feedbacks ?? 0));
const approved = computed(() => Number(props.preview.approved_feedbacks ?? 0));
const progressPct = computed(() => total.value ? Math.round((approved.value / total.value) * 100) : 0);

const statusLabel = computed(() => ({
    inProgress: 'IN PROGRESS',
    completed: 'COMPLETED',
    noFeedback: 'NO FEEDBACK',
}[props.status]));

const formatDateRelative = (dateStr: string) => dateStr ? dayjs(dateStr).fromNow() : '';

const navigateToPreview = () => router.visit(`/previews/update/${props.preview.id}`);
</script>

<template>
    <div @click="navigateToPreview"
        class="group relative flex flex-col overflow-hidden rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A] transition-all cursor-pointer hover:border-black dark:hover:border-white hover:-translate-y-0.5 hover:shadow-[3px_3px_0_0_rgba(0,0,0,0.9)] dark:hover:shadow-[3px_3px_0_0_rgba(255,255,255,0.9)]">

        <!-- Color palette accent stripe -->
        <div class="absolute inset-y-0 left-0 w-1.5" :style="{ backgroundColor: accent }" aria-hidden="true" />

        <!-- Status pill, top-right -->
        <div class="absolute top-3 right-3 z-10">
            <span
                class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-mono uppercase tracking-widest"
                :class="status === 'completed'
                    ? 'border-black bg-black text-white dark:border-white dark:bg-white dark:text-black'
                    : status === 'inProgress'
                        ? 'border-[#666666] bg-white/80 text-[#666666] dark:border-[#999999] dark:bg-[#0A0A0A] dark:text-[#999999]'
                        : 'border-[#CCCCCC] bg-white/80 text-[#999999] dark:border-[#333333] dark:bg-[#0A0A0A] dark:text-[#666666]'">
                <Check v-if="status === 'completed'" class="h-3 w-3" :stroke-width="2" />
                {{ statusLabel }}
            </span>
        </div>

        <!-- Body -->
        <div class="pl-5 pr-4 pt-4 pb-3">
            <!-- Client eyebrow — small, monospaced, identifies the brand -->
            <div class="text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] truncate"
                :title="preview.client?.name">
                {{ preview.client?.name || '— NO CLIENT' }}
            </div>

            <!-- Preview name — biggest thing on the card -->
            <h3 class="mt-1 mb-3 line-clamp-2 break-words pr-20 text-lg font-semibold leading-tight text-black dark:text-white"
                :title="preview.name">
                {{ preview.name || 'Untitled preview' }}
            </h3>

            <!-- Feedback progress -->
            <div v-if="total > 0">
                <div
                    class="flex items-center justify-between font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                    <span>FEEDBACK</span>
                    <span class="tabular-nums text-[#1A1A1A] dark:text-[#E8E8E8]">
                        {{ approved }}/{{ total }} <span class="text-[#999999]">· {{ progressPct }}%</span>
                    </span>
                </div>
                <div class="mt-1 h-1 overflow-hidden rounded-full bg-[#E8E8E8] dark:bg-[#222222]">
                    <div class="h-full rounded-full transition-all"
                        :style="{ width: `${progressPct}%`, backgroundColor: accent }" />
                </div>
            </div>
        </div>

        <!-- Footer: uploader + actions -->
        <div
            class="mt-auto flex items-center justify-between gap-2 border-t border-[#E8E8E8] bg-[#FAFAFA] px-4 pl-5 py-2.5 dark:border-[#222222] dark:bg-black/40">
            <div class="flex min-w-0 items-center gap-2">
                <div
                    class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-black font-mono text-[10px] font-bold text-white dark:bg-white dark:text-black">
                    {{ initials(preview.uploader?.name) }}
                </div>
                <div class="min-w-0 leading-tight">
                    <div class="truncate font-mono text-[10px] uppercase tracking-wider text-[#666666] dark:text-[#999999]"
                        :title="preview.uploader?.name">
                        {{ preview.uploader?.name || 'SYSTEM' }}
                    </div>
                    <div class="font-mono text-[10px] text-[#999999] dark:text-[#666666]">
                        {{ formatDateRelative(preview.created_at) }}
                    </div>
                </div>
            </div>
            <PreviewActionButtons :preview="preview" size="sm" :show-edit="false" :stop-propagation="true" />
        </div>
    </div>
</template>
