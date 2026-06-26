<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import dayjs, { type Dayjs } from 'dayjs';
import { PopoverRoot, PopoverTrigger, PopoverPortal, PopoverContent } from 'radix-vue';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next';

// Two-way bound ISO date strings ('YYYY-MM-DD'); empty = open-ended.
const from = defineModel<string>('from', { default: '' });
const to = defineModel<string>('to', { default: '' });
const emit = defineEmits<{ change: [] }>();

const open = ref(false);
const view = ref<Dayjs>(dayjs());

// Jump the visible month to the start of the range when opened.
watch(open, (o) => { if (o) view.value = dayjs(from.value || undefined); });

const label = computed(() => {
    if (!from.value && !to.value) return 'All dates';
    const f = from.value ? dayjs(from.value).format('MMM D') : '…';
    const t = to.value ? dayjs(to.value).format('MMM D, YYYY') : '…';
    return `${f} – ${t}`;
});

const weeks = computed(() => {
    const start = view.value.startOf('month').startOf('week');
    const cells: Dayjs[] = [];
    let d = start;
    for (let i = 0; i < 42; i++) { cells.push(d); d = d.add(1, 'day'); }
    const out: Dayjs[][] = [];
    for (let i = 0; i < 42; i += 7) out.push(cells.slice(i, i + 7));
    return out;
});

const ymd = (d: Dayjs) => d.format('YYYY-MM-DD');
const isStart = (d: Dayjs) => !!from.value && d.isSame(dayjs(from.value), 'day');
const isEnd = (d: Dayjs) => !!to.value && d.isSame(dayjs(to.value), 'day');
const inRange = (d: Dayjs) => !!from.value && !!to.value && d.isAfter(dayjs(from.value), 'day') && d.isBefore(dayjs(to.value), 'day');
const isToday = (d: Dayjs) => d.isSame(dayjs(), 'day');

function pick(d: Dayjs) {
    const k = ymd(d);
    if (!from.value || (from.value && to.value)) {
        // Start a fresh range.
        from.value = k;
        to.value = '';
    } else {
        // Second click completes the range (swap if picked earlier). Stays open so
        // the user can review/adjust — closes via Done or clicking outside.
        if (dayjs(k).isBefore(dayjs(from.value), 'day')) { to.value = from.value; from.value = k; }
        else { to.value = k; }
    }
    emit('change');
}
function clear() {
    from.value = '';
    to.value = '';
    emit('change');
}
</script>

<template>
    <PopoverRoot v-model:open="open">
        <PopoverTrigger as-child>
            <button type="button"
                class="inline-flex items-center gap-2 rounded-md border bg-background px-3 py-1.5 text-xs outline-none transition hover:bg-muted focus:ring-2 focus:ring-[#e2483d]/40">
                <CalendarDays class="h-4 w-4 text-muted-foreground" />
                <span>{{ label }}</span>
            </button>
        </PopoverTrigger>
        <PopoverPortal>
            <PopoverContent align="end" :side-offset="6"
                class="z-50 rounded-xl border bg-card p-3 font-mono text-foreground shadow-xl">
                <div class="mb-2 flex items-center justify-between">
                    <button type="button" class="rounded p-1 text-muted-foreground hover:bg-muted" @click="view = view.subtract(1, 'month')"><ChevronLeft class="h-4 w-4" /></button>
                    <span class="text-sm font-medium">{{ view.format('MMMM YYYY') }}</span>
                    <button type="button" class="rounded p-1 text-muted-foreground hover:bg-muted" @click="view = view.add(1, 'month')"><ChevronRight class="h-4 w-4" /></button>
                </div>
                <div class="grid grid-cols-7 gap-0.5 text-center text-[10px] text-muted-foreground">
                    <span v-for="w in ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']" :key="w" class="py-1">{{ w }}</span>
                </div>
                <div class="grid grid-cols-7 gap-0.5">
                    <template v-for="(week, wi) in weeks" :key="wi">
                        <button v-for="d in week" :key="d.format('YYYY-MM-DD')" type="button"
                            class="h-8 w-8 rounded-md text-xs transition"
                            :class="[
                                d.month() !== view.month() ? 'text-muted-foreground/40' : '',
                                (isStart(d) || isEnd(d)) ? 'bg-[#e2483d] font-semibold text-white'
                                    : inRange(d) ? 'bg-[#e2483d]/15'
                                    : isToday(d) ? 'ring-1 ring-[#e2483d]/50 hover:bg-muted'
                                    : 'hover:bg-muted',
                            ]"
                            @click="pick(d)">{{ d.date() }}</button>
                    </template>
                </div>
                <div class="mt-2 flex items-center justify-between border-t pt-2">
                    <button type="button" class="text-xs text-muted-foreground hover:underline" @click="clear">Clear</button>
                    <button type="button" class="text-xs font-medium text-[#e2483d] hover:underline" @click="open = false">Done</button>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
