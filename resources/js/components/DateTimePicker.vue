<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import dayjs, { type Dayjs } from 'dayjs';
import { PopoverRoot, PopoverTrigger, PopoverPortal, PopoverContent } from 'radix-vue';
import { CalendarClock, ChevronLeft, ChevronRight } from 'lucide-vue-next';

// Two-way bound as a naive local datetime string 'YYYY-MM-DDTHH:mm' (matches the
// value the backend formats/parses in the app timezone). Empty = unset.
const model = defineModel<string>({ default: '' });
const emit = defineEmits<{ change: [] }>();

const open = ref(false);
const view = ref<Dayjs>(dayjs());

// Parsed current value (null when unset/invalid).
const current = computed(() => {
    if (!model.value) return null;
    const d = dayjs(model.value);
    return d.isValid() ? d : null;
});

// Time portion as an <input type="time"> value; defaults to noon when unset.
const timeStr = computed({
    get: () => (current.value ? current.value.format('HH:mm') : '12:00'),
    set: (t: string) => {
        const [h, m] = t.split(':').map(Number);
        const base = current.value ?? view.value;
        commit(base.hour(h || 0).minute(m || 0));
    },
});

// Jump the visible month to the selected date when opened.
watch(open, (o) => { if (o) view.value = (current.value ?? dayjs()); });

const label = computed(() =>
    current.value ? current.value.format('MMM D, YYYY · HH:mm') : 'Pick a date & time'
);

const weeks = computed(() => {
    const start = view.value.startOf('month').startOf('week');
    const cells: Dayjs[] = [];
    let d = start;
    for (let i = 0; i < 42; i++) { cells.push(d); d = d.add(1, 'day'); }
    const out: Dayjs[][] = [];
    for (let i = 0; i < 42; i += 7) out.push(cells.slice(i, i + 7));
    return out;
});

const isSelected = (d: Dayjs) => !!current.value && d.isSame(current.value, 'day');
const isToday = (d: Dayjs) => d.isSame(dayjs(), 'day');

function commit(d: Dayjs) {
    model.value = d.format('YYYY-MM-DDTHH:mm');
    emit('change');
}

function pick(d: Dayjs) {
    // Keep the current time-of-day; only swap the calendar date.
    const base = current.value ?? dayjs().hour(12).minute(0);
    commit(d.hour(base.hour()).minute(base.minute()));
}

function setNow() {
    const now = dayjs();
    view.value = now;
    commit(now);
}
</script>

<template>
    <PopoverRoot v-model:open="open">
        <PopoverTrigger as-child>
            <button type="button"
                class="inline-flex w-full items-center gap-2 rounded-lg border border-[#CCCCCC] dark:border-[#333333] bg-white dark:bg-[#111111] px-2 py-2 text-sm text-black dark:text-white outline-none transition hover:border-black dark:hover:border-white focus:border-black dark:focus:border-white">
                <CalendarClock class="h-4 w-4 text-[#666666] dark:text-[#999999]" />
                <span :class="{ 'text-[#999999] dark:text-[#666666]': !current }">{{ label }}</span>
            </button>
        </PopoverTrigger>
        <PopoverPortal>
            <PopoverContent align="start" :side-offset="6"
                class="z-50 rounded-xl border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A] p-3 font-mono text-black dark:text-white shadow-xl">
                <div class="mb-2 flex items-center justify-between">
                    <button type="button" class="rounded p-1 text-[#666666] dark:text-[#999999] hover:bg-[#F5F5F5] dark:hover:bg-black" @click="view = view.subtract(1, 'month')"><ChevronLeft class="h-4 w-4" /></button>
                    <span class="text-sm font-medium">{{ view.format('MMMM YYYY') }}</span>
                    <button type="button" class="rounded p-1 text-[#666666] dark:text-[#999999] hover:bg-[#F5F5F5] dark:hover:bg-black" @click="view = view.add(1, 'month')"><ChevronRight class="h-4 w-4" /></button>
                </div>
                <div class="grid grid-cols-7 gap-0.5 text-center text-[10px] text-[#999999]">
                    <span v-for="w in ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']" :key="w" class="py-1">{{ w }}</span>
                </div>
                <div class="grid grid-cols-7 gap-0.5">
                    <template v-for="(week, wi) in weeks" :key="wi">
                        <button v-for="d in week" :key="d.format('YYYY-MM-DD')" type="button"
                            class="h-8 w-8 rounded-md text-xs transition"
                            :class="[
                                d.month() !== view.month() ? 'text-[#999999]/40 dark:text-[#666666]/60' : '',
                                isSelected(d) ? 'bg-[#e2483d] font-semibold text-white'
                                    : isToday(d) ? 'ring-1 ring-[#e2483d]/50 hover:bg-[#F5F5F5] dark:hover:bg-black'
                                    : 'hover:bg-[#F5F5F5] dark:hover:bg-black',
                            ]"
                            @click="pick(d)">{{ d.date() }}</button>
                    </template>
                </div>
                <div class="mt-3 flex items-center gap-2 border-t border-[#E8E8E8] dark:border-[#222222] pt-3">
                    <label class="text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">Time</label>
                    <input type="time" v-model="timeStr"
                        class="flex-1 rounded-md border border-[#CCCCCC] dark:border-[#333333] bg-white dark:bg-[#111111] px-2 py-1 text-xs text-black dark:text-white outline-none focus:border-black dark:focus:border-white" />
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <button type="button" class="text-xs text-[#666666] dark:text-[#999999] hover:underline" @click="setNow">Now</button>
                    <button type="button" class="text-xs font-medium text-[#e2483d] hover:underline" @click="open = false">Done</button>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>
