<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ArrowDownRight,
    ArrowUpRight,
    Calendar,
    ChartLine,
    ChevronDown,
    Download,
    FileSpreadsheet,
    FileJson,
    FileText,
    Loader2,
    Minus,
    Printer,
    RotateCcw,
    Sparkles,
    Table as TableIcon,
    UploadCloud,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Swal from 'sweetalert2';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    CategoryScale,
    LinearScale,
    PointElement,
    Filler,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, Filler);

// =============================================================
// Types
// =============================================================
interface SourceEntry { key: string; display_name: string }
interface Section { name: string; sources: SourceEntry[] }
interface RowEntry { date: string; cells: Record<string, number> }
interface Period { type: 'month' | 'year' | 'custom'; month?: string; year?: number; start?: string; end?: string; label: string; slug: string }
interface UploadResult {
    accepted: { file: string; source: string; rows: number; first_date: string; last_date: string }[];
    skipped: { file: string; reason: string }[];
    rows_touched: number;
}
interface ComparisonCard {
    kind: 'mom' | 'yoy';
    current_label: string;
    current_short: string;
    previous_label: string;
    previous_short: string;
    current_total: number;
    previous_total: number;
}
interface ReportsPageProps extends SharedData {
    period: Period;
    latestWeek: number | null;
    momCard: ComparisonCard | null;
    yoyCard: ComparisonCard | null;
    sections: Section[];
    rows: RowEntry[];
    totals: Record<string, number>;
    availablePeriods: { months: string[]; years: string[] };
    sources: { id: number; key: string; display_name: string; section: string; filename_pattern: string }[];
}

// =============================================================
// Page state
// =============================================================
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Generate Reports', href: '/generate/reports' }];
const page = usePage<ReportsPageProps>();
const period = computed(() => page.props.period);
const latestWeek = computed(() => page.props.latestWeek ?? null);
const weekLabel = computed(() => latestWeek.value !== null ? 'Week ' + String(latestWeek.value).padStart(2, '0') : null);
const momCard = computed(() => page.props.momCard ?? null);
const yoyCard = computed(() => page.props.yoyCard ?? null);
const sections = computed(() => page.props.sections ?? []);
const rows = computed(() => page.props.rows ?? []);
const totals = computed(() => page.props.totals ?? {});
const availablePeriods = computed(() => page.props.availablePeriods ?? { months: [], years: [] });
const sourcesList = computed(() => page.props.sources ?? []);

const flashUpload = computed<UploadResult | undefined>(() => (page.props as any).flash?.upload_result);
watch(flashUpload, (result) => {
    if (!result) return;
    const acc = result.accepted.length;
    const skip = result.skipped.length;
    const total = result.rows_touched;
    const skippedDetails = result.skipped.map((s) => `<li>${s.file} — ${s.reason}</li>`).join('');

    if (acc > 0) uploadOpen.value = false;

    Swal.fire({
        title: skip ? 'Partial upload' : 'Upload complete',
        icon: skip && !acc ? 'error' : skip ? 'warning' : 'success',
        html: `<div style="text-align:left">
            <p>${acc} file${acc === 1 ? '' : 's'} accepted, ${total} row${total === 1 ? '' : 's'} written.</p>
            ${skip ? `<p style="margin-top:.5rem"><strong>Skipped:</strong></p><ul style="margin:0 0 0 1rem">${skippedDetails}</ul>` : ''}
        </div>`,
        confirmButtonColor: '#000',
        customClass: { popup: 'rounded-lg' },
    });
}, { immediate: true });

// =============================================================
// Period (Month / Year / Custom + selector)
// =============================================================
type ViewMode = 'month' | 'year' | 'custom';
const viewMode = ref<ViewMode>(period.value.type);
const selectedMonth = ref(period.value.month ?? new Date().toISOString().slice(0, 7));
const selectedYear = ref(period.value.year ?? new Date().getFullYear());
const todayIso = new Date().toISOString().slice(0, 10);
const customStart = ref(period.value.start ?? todayIso);
const customEnd = ref(period.value.end ?? todayIso);

watch(viewMode, (mode) => navigate(mode));
watch(selectedMonth, () => { if (viewMode.value === 'month') navigate('month'); });
watch(selectedYear, () => { if (viewMode.value === 'year') navigate('year'); });

function applyCustomRange() {
    if (!customStart.value || !customEnd.value) return;
    if (customEnd.value < customStart.value) return;
    navigate('custom');
}

function navigate(mode: ViewMode) {
    const params: Record<string, string | number> = { view: mode };
    if (mode === 'month') params.month = selectedMonth.value;
    if (mode === 'year') params.year = selectedYear.value;
    if (mode === 'custom') {
        if (!customStart.value || !customEnd.value) return;
        params.start = customStart.value;
        params.end = customEnd.value;
    }
    router.get(route('generate-reports.index'), params, {
        preserveScroll: true, preserveState: true, replace: true,
    });
}
function resetMonthToLatest() {
    const months = availablePeriods.value.months;
    if (!months.length) return;
    selectedMonth.value = months[months.length - 1] ?? selectedMonth.value;
}

// =============================================================
// View toggle — combined Table view vs Insights
// =============================================================
type TabKey = 'table' | 'insights';
const activeTab = ref<TabKey>('table');


// =============================================================
// Upload (modal + drag-anywhere overlay)
// =============================================================
const uploadOpen = ref(false);
const dragOver = ref(false);          // inside the modal's drop zone
const pageDrag = ref(false);          // page-level drag overlay
const isUploading = ref(false);
const uploadInput = ref<HTMLInputElement | null>(null);
const showPatterns = ref(false);

function pickFiles() { uploadInput.value?.click(); }
function onInputChange(e: Event) {
    const files = (e.target as HTMLInputElement).files;
    if (files && files.length) doUpload(Array.from(files));
    if (uploadInput.value) uploadInput.value.value = '';
}
function onZoneDrop(e: DragEvent) {
    e.preventDefault(); dragOver.value = false;
    const files = e.dataTransfer?.files;
    if (files && files.length) doUpload(Array.from(files));
}
function doUpload(files: File[]) {
    isUploading.value = true;
    const form = new FormData();
    files.forEach((f) => form.append('files[]', f));
    router.post(route('generate-reports.upload'), form, {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => { uploadOpen.value = false; },
        onFinish: () => { isUploading.value = false; },
        onError: () => Swal.fire({ title: 'Upload failed', icon: 'error', text: 'See server logs.', confirmButtonColor: '#000' }),
    });
}

// Page-level drag listeners — show a full-screen "drop anywhere" overlay
// only while files are being dragged over the window. We count enters
// vs leaves so flicker over child elements doesn't dismiss the overlay.
let dragDepth = 0;
function hasFiles(e: DragEvent): boolean {
    return Array.from(e.dataTransfer?.types ?? []).includes('Files');
}
function onWinDragEnter(e: DragEvent) {
    if (!hasFiles(e)) return;
    e.preventDefault(); dragDepth++; pageDrag.value = true;
}
function onWinDragLeave(e: DragEvent) {
    if (!hasFiles(e)) return;
    e.preventDefault(); dragDepth = Math.max(0, dragDepth - 1);
    if (dragDepth === 0) pageDrag.value = false;
}
function onWinDragOver(e: DragEvent) { if (hasFiles(e)) e.preventDefault(); }
function onWinDrop(e: DragEvent) {
    if (!hasFiles(e)) return;
    e.preventDefault(); dragDepth = 0; pageDrag.value = false;
    const files = e.dataTransfer?.files;
    if (files && files.length) {
        uploadOpen.value = true;
        doUpload(Array.from(files));
    }
}
onMounted(() => {
    window.addEventListener('dragenter', onWinDragEnter);
    window.addEventListener('dragleave', onWinDragLeave);
    window.addEventListener('dragover', onWinDragOver);
    window.addEventListener('drop', onWinDrop);
});
onBeforeUnmount(() => {
    window.removeEventListener('dragenter', onWinDragEnter);
    window.removeEventListener('dragleave', onWinDragLeave);
    window.removeEventListener('dragover', onWinDragOver);
    window.removeEventListener('drop', onWinDrop);
});

// =============================================================
// Export
// =============================================================
function exportAs(format: 'csv' | 'xlsx' | 'json' | 'pdf') {
    const params = new URLSearchParams();
    params.set('format', format);
    params.set('view', viewMode.value);
    if (viewMode.value === 'month') params.set('month', selectedMonth.value);
    else if (viewMode.value === 'year') params.set('year', String(selectedYear.value));
    else if (viewMode.value === 'custom') {
        params.set('start', customStart.value);
        params.set('end', customEnd.value);
    }
    window.location.href = route('generate-reports.export') + '?' + params.toString();
}

// =============================================================
// Formatting helpers
// =============================================================
function formatEur(n: number | undefined | null): string {
    if (n === undefined || n === null || Number.isNaN(n)) return '—';
    return '€ ' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function formatDate(iso: string): string {
    const d = new Date(iso);
    return `${d.getMonth() + 1}/${d.getDate()}/${d.getFullYear()}`;
}
function formatDateShort(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
function formatMonthLabel(ym: string): string {
    const parts = ym.split('-');
    const y = Number(parts[0] ?? new Date().getFullYear());
    const m = Number(parts[1] ?? 1);
    return new Date(y, m - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}
function sectionLabel(name: string): string {
    if (name === 'Outstream') return 'Outstream Ad Position';
    if (name === 'Sticky') return 'Sticky Bottom Ad Position';
    return name;
}

// =============================================================
// KPIs + Insights
// =============================================================
const grandTotalsBySection = computed(() => {
    const out: Record<string, number> = {};
    for (const sec of sections.value) {
        out[sec.name] = sec.sources.reduce((s, src) => s + (totals.value[src.key] ?? 0), 0);
    }
    return out;
});
const grandTotalAll = computed(() => Object.values(grandTotalsBySection.value).reduce((s, v) => s + v, 0));

// Build a delta object given the current grand total and a prior-period total.
// When the prior period has no data, return a "(no data)" placeholder so the
// card stays visible — hiding it confused users into wondering why the
// comparison vanished after switching months.
function buildDelta(curr: number, prevTotal: number) {
    if (prevTotal <= 0) {
        return { kind: 'none' as const, label: '(no data)', icon: Minus, color: 'text-muted-foreground' };
    }
    const diff = curr - prevTotal;
    const pct = (diff / prevTotal) * 100;
    const sign = diff >= 0 ? '+' : '';
    return {
        kind: diff >= 0 ? ('up' as const) : ('down' as const),
        label: `${sign}${pct.toFixed(1)}%`,
        diff,
        icon: diff >= 0 ? ArrowUpRight : ArrowDownRight,
        color: diff >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400',
    };
}
const momDelta = computed(() => momCard.value ? buildDelta(Number(momCard.value.current_total), Number(momCard.value.previous_total)) : null);
const yoyDelta = computed(() => yoyCard.value ? buildDelta(Number(yoyCard.value.current_total), Number(yoyCard.value.previous_total)) : null);

const dayTotalsBySection = computed(() => {
    const map: Record<string, Record<string, number>> = {};
    for (const sec of sections.value) map[sec.name] = {};
    for (const row of rows.value) {
        for (const sec of sections.value) {
            const t = sec.sources.reduce((s, src) => s + (row.cells[src.key] ?? 0), 0);
            (map[sec.name] ??= {})[row.date] = t;
        }
    }
    return map;
});
function dayTotal(sectionName: string, date: string): number {
    return dayTotalsBySection.value[sectionName]?.[date] ?? 0;
}
function dayTotalAll(date: string): number {
    return sections.value.reduce((s, sec) => s + dayTotal(sec.name, date), 0);
}

const bestDay = computed(() => {
    if (!rows.value.length) return null;
    let bestDate = rows.value[0]!.date;
    let bestVal = dayTotalAll(bestDate);
    for (const r of rows.value) {
        const v = dayTotalAll(r.date);
        if (v > bestVal) { bestVal = v; bestDate = r.date; }
    }
    return { date: bestDate, value: bestVal };
});

// Sparkline of day totals (used inside the Grand Total tile).
const sparkline = computed(() => {
    const values = rows.value.map((r) => dayTotalAll(r.date));
    if (values.length < 2) return { path: '', area: '', width: 120, height: 28 };
    const w = 120, h = 28, pad = 2;
    const max = Math.max(...values);
    const min = Math.min(...values);
    const range = max - min || 1;
    const pts = values.map((v, i) => {
        const x = pad + (i * (w - pad * 2)) / (values.length - 1);
        const y = h - pad - ((v - min) / range) * (h - pad * 2);
        return [x, y];
    });
    const path = pts.map(([x, y], i) => (i === 0 ? `M${x},${y}` : `L${x},${y}`)).join(' ');
    const area = `${path} L${pts[pts.length - 1]![0]},${h} L${pts[0]![0]},${h} Z`;
    return { path, area, width: w, height: h };
});

// Chart for Insights tab
const chartData = computed(() => {
    const labels = rows.value.map((r) => formatDate(r.date));
    const palette = [
        { border: '#000000', bg: 'rgba(0,0,0,0.06)' },
        { border: '#16a34a', bg: 'rgba(22,163,74,0.10)' },
        { border: '#dc2626', bg: 'rgba(220,38,38,0.10)' },
        { border: '#ea580c', bg: 'rgba(234,88,12,0.10)' },
        { border: '#9333ea', bg: 'rgba(147,51,234,0.10)' },
        { border: '#0891b2', bg: 'rgba(8,145,178,0.10)' },
        { border: '#ca8a04', bg: 'rgba(202,138,4,0.10)' },
    ];
    let i = 0;
    const datasets: any[] = [];
    for (const sec of sections.value) {
        for (const src of sec.sources) {
            const c = palette[i % palette.length] ?? palette[0]!;
            i++;
            datasets.push({
                label: `${sec.name} · ${src.display_name}`,
                data: rows.value.map((r) => r.cells[src.key] ?? 0),
                borderColor: c.border,
                backgroundColor: c.bg,
                tension: 0.25,
                pointRadius: 2,
                fill: false,
            });
        }
    }
    return { labels, datasets };
});
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' as const } },
    scales: { y: { ticks: { callback: (v: any) => '€' + Number(v).toFixed(0) } } },
};
</script>

<template>
    <Head title="Generate Reports" />
    <AppLayout :breadcrumbs="breadcrumbs">

        <!-- =========================================================
             Page-level "drop anywhere" overlay
             ========================================================= -->
        <Transition
            enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0"
        >
            <div v-if="pageDrag" class="fixed inset-0 z-[80] flex items-center justify-center bg-background/70 backdrop-blur-sm pointer-events-none">
                <div class="rounded-2xl border-2 border-dashed border-primary px-12 py-10 text-center bg-background shadow-xl">
                    <UploadCloud class="mx-auto h-10 w-10 text-primary mb-2" />
                    <p class="text-lg font-semibold tracking-tight">Drop your reports anywhere</p>
                    <p class="text-sm text-muted-foreground mt-1 font-mono tracking-wide">We'll match each file by name and stitch them in.</p>
                </div>
            </div>
        </Transition>

        <div class="flex flex-col gap-6 p-4 sm:p-6">

            <!-- =========================================================
                 Page heading
                 ========================================================= -->
            <div class="flex items-start gap-4">
                <div class="flex-1">
                    <h1 class="text-3xl font-semibold tracking-tight">Comparative Analysis of Revenue</h1>
                    <p class="mt-1 text-sm font-mono tracking-wide text-muted-foreground">
                        {{ period.label }}
                        <span class="mx-2 text-muted-foreground/40">·</span>
                        {{ rows.length }} {{ rows.length === 1 ? 'day' : 'days' }}
                        <span class="mx-2 text-muted-foreground/40">·</span>
                        {{ sourcesList.length }} sources tracked
                    </p>
                </div>

                <!-- Top-right cluster: WEEK pill + comparison cards on a row -->
                <div class="shrink-0 flex flex-col items-end gap-2">
                    <span
                        v-if="weekLabel"
                        class="inline-flex items-center rounded-full bg-foreground text-background px-4 py-1.5 text-xs font-mono uppercase tracking-[0.25em]"
                        title="Calendar week of the latest day in this period"
                    >{{ weekLabel }}</span>

                    <div class="flex items-stretch gap-2 flex-wrap justify-end">
                        <!-- Month-over-month: latest month vs previous month -->
                        <div
                            v-if="momCard && momDelta"
                            class="rounded-xl border-2 px-4 py-2.5 min-w-[220px]"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-[10px] uppercase tracking-[0.25em] text-muted-foreground font-mono">{{ momCard.current_short }} vs {{ momCard.previous_short }}</p>
                                <span class="text-[9px] uppercase tracking-[0.2em] text-muted-foreground/70 font-mono">M-o-M</span>
                            </div>
                            <p class="mt-1 text-2xl font-semibold tabular-nums flex items-center justify-end gap-1" :class="momDelta.color">
                                <component :is="momDelta.icon" class="h-5 w-5" />
                                <span>{{ momDelta.label }}</span>
                            </p>
                            <p class="mt-1 text-[11px] text-muted-foreground font-mono tracking-wide tabular-nums text-right">
                                {{ formatEur(momCard.current_total) }}
                                <span class="text-muted-foreground/50 mx-0.5">vs</span>
                                {{ formatEur(momCard.previous_total) }}
                            </p>
                        </div>

                        <!-- Year-over-year: latest year vs previous year -->
                        <div
                            v-if="yoyCard && yoyDelta"
                            class="rounded-xl border-2 px-4 py-2.5 min-w-[220px]"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-[10px] uppercase tracking-[0.25em] text-muted-foreground font-mono">{{ yoyCard.current_short }} vs {{ yoyCard.previous_short }}</p>
                                <span class="text-[9px] uppercase tracking-[0.2em] text-muted-foreground/70 font-mono">Y-o-Y</span>
                            </div>
                            <p class="mt-1 text-2xl font-semibold tabular-nums flex items-center justify-end gap-1" :class="yoyDelta.color">
                                <component :is="yoyDelta.icon" class="h-5 w-5" />
                                <span>{{ yoyDelta.label }}</span>
                            </p>
                            <p class="mt-1 text-[11px] text-muted-foreground font-mono tracking-wide tabular-nums text-right">
                                {{ formatEur(yoyCard.current_total) }}
                                <span class="text-muted-foreground/50 mx-0.5">vs</span>
                                {{ formatEur(yoyCard.previous_total) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =========================================================
                 Sticky action bar
                 ========================================================= -->
            <div class="sticky top-0 z-30 -mx-4 sm:-mx-6 px-4 sm:px-6 py-3 border-y bg-background/85 backdrop-blur supports-[backdrop-filter]:bg-background/65">
                <div class="flex flex-wrap items-center gap-3">

                    <!-- Period (Month|Year|Custom + picker) -->
                    <div class="flex items-center gap-2">
                        <div class="inline-flex rounded-full border-2 bg-background overflow-hidden font-mono tracking-wide text-xs">
                            <button
                                type="button"
                                class="px-3 py-1.5 transition"
                                :class="viewMode === 'month' ? 'bg-foreground text-background' : 'hover:bg-muted'"
                                @click="viewMode = 'month'"
                            >MONTH</button>
                            <button
                                type="button"
                                class="px-3 py-1.5 transition border-l-2"
                                :class="viewMode === 'year' ? 'bg-foreground text-background' : 'hover:bg-muted'"
                                @click="viewMode = 'year'"
                            >YEAR</button>
                            <button
                                type="button"
                                class="px-3 py-1.5 transition border-l-2"
                                :class="viewMode === 'custom' ? 'bg-foreground text-background' : 'hover:bg-muted'"
                                @click="viewMode = 'custom'"
                            >CUSTOM</button>
                        </div>

                        <div v-if="viewMode === 'month'" class="flex items-center gap-1">
                            <Calendar class="h-4 w-4 text-muted-foreground" />
                            <select v-model="selectedMonth" class="rounded-md border-2 bg-background px-2 py-1.5 text-sm font-mono tracking-wide">
                                <option v-if="!availablePeriods.months.length" :value="selectedMonth">{{ formatMonthLabel(selectedMonth) }}</option>
                                <option v-for="m in availablePeriods.months" :key="m" :value="m">{{ formatMonthLabel(m) }}</option>
                            </select>
                            <button v-if="availablePeriods.months.length" type="button" class="text-muted-foreground hover:text-foreground p-1" @click="resetMonthToLatest" title="Latest month with data">
                                <RotateCcw class="h-4 w-4" />
                            </button>
                        </div>
                        <div v-else-if="viewMode === 'year'" class="flex items-center gap-1">
                            <Calendar class="h-4 w-4 text-muted-foreground" />
                            <select v-model.number="selectedYear" class="rounded-md border-2 bg-background px-2 py-1.5 text-sm font-mono tracking-wide">
                                <option v-if="!availablePeriods.years.length" :value="selectedYear">{{ selectedYear }}</option>
                                <option v-for="y in availablePeriods.years" :key="y" :value="Number(y)">{{ y }}</option>
                            </select>
                        </div>
                        <div v-else class="flex items-center gap-1">
                            <Calendar class="h-4 w-4 text-muted-foreground" />
                            <input
                                type="date"
                                v-model="customStart"
                                :max="customEnd || undefined"
                                class="rounded-md border-2 bg-background px-2 py-1.5 text-sm font-mono tracking-wide"
                            />
                            <span class="text-muted-foreground text-xs font-mono">→</span>
                            <input
                                type="date"
                                v-model="customEnd"
                                :min="customStart || undefined"
                                class="rounded-md border-2 bg-background px-2 py-1.5 text-sm font-mono tracking-wide"
                            />
                            <Button size="sm" variant="outline" class="rounded-full border-2 font-mono tracking-wide ml-1" @click="applyCustomRange">
                                APPLY
                            </Button>
                        </div>
                    </div>

                    <div class="hidden md:block h-6 w-px bg-border" />

                    <!-- Tab toggle (Table|Insights) -->
                    <div class="inline-flex rounded-full border-2 bg-background overflow-hidden font-mono tracking-wide text-xs">
                        <button
                            type="button"
                            class="px-3 py-1.5 transition flex items-center gap-1.5"
                            :class="activeTab === 'table' ? 'bg-foreground text-background' : 'hover:bg-muted'"
                            @click="activeTab = 'table'"
                        ><TableIcon class="h-3.5 w-3.5" /> TABLE</button>
                        <button
                            type="button"
                            class="px-3 py-1.5 transition flex items-center gap-1.5 border-l-2"
                            :class="activeTab === 'insights' ? 'bg-foreground text-background' : 'hover:bg-muted'"
                            @click="activeTab = 'insights'"
                        ><ChartLine class="h-3.5 w-3.5" /> INSIGHTS</button>
                    </div>

                    <!-- Spacer pushes actions right -->
                    <div class="grow" />

                    <!-- Export dropdown -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button size="sm" variant="outline" class="rounded-full border-2 font-mono tracking-wide" :disabled="!rows.length">
                                <Download class="h-4 w-4 mr-1" /> EXPORT <ChevronDown class="h-3.5 w-3.5 ml-1 opacity-60" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-44">
                            <DropdownMenuLabel class="text-xs uppercase tracking-wider">Format</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem @select="exportAs('pdf')" class="cursor-pointer">
                                <Printer class="h-4 w-4 mr-2" /> PDF
                            </DropdownMenuItem>
                            <DropdownMenuItem @select="exportAs('xlsx')" class="cursor-pointer">
                                <FileSpreadsheet class="h-4 w-4 mr-2" /> XLSX
                            </DropdownMenuItem>
                            <DropdownMenuItem @select="exportAs('csv')" class="cursor-pointer">
                                <FileText class="h-4 w-4 mr-2" /> CSV
                            </DropdownMenuItem>
                            <DropdownMenuItem @select="exportAs('json')" class="cursor-pointer">
                                <FileJson class="h-4 w-4 mr-2" /> JSON
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <!-- Primary upload button -->
                    <Button size="sm" class="rounded-full font-mono tracking-wide" @click="uploadOpen = true">
                        <UploadCloud class="h-4 w-4 mr-1" /> UPLOAD REPORTS
                    </Button>
                </div>
            </div>

            <!-- =========================================================
                 KPI strip — only when data present
                 ========================================================= -->
            <div v-if="rows.length" class="grid grid-cols-2 md:grid-cols-4 gap-3">

                <!-- Grand total + sparkline -->
                <div class="rounded-xl border-2 p-4 bg-foreground text-background">
                    <p class="text-[10px] uppercase tracking-[0.25em] opacity-70 font-mono">Grand Total</p>
                    <p class="text-2xl font-semibold tabular-nums mt-1">{{ formatEur(grandTotalAll) }}</p>
                    <svg v-if="sparkline.path" :viewBox="`0 0 ${sparkline.width} ${sparkline.height}`" class="mt-2 w-full h-7" preserveAspectRatio="none">
                        <path :d="sparkline.area" fill="currentColor" fill-opacity="0.18" />
                        <path :d="sparkline.path" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>

                <!-- Per-section -->
                <div v-for="sec in sections" :key="sec.name" class="rounded-xl border-2 p-4">
                    <p class="text-[10px] uppercase tracking-[0.25em] text-muted-foreground font-mono">{{ sectionLabel(sec.name) }}</p>
                    <p class="text-2xl font-semibold tabular-nums mt-1">{{ formatEur(grandTotalsBySection[sec.name]) }}</p>
                    <p class="text-xs text-muted-foreground mt-2 font-mono tracking-wide">{{ sec.sources.length }} provider{{ sec.sources.length === 1 ? '' : 's' }}</p>
                </div>

                <!-- Best day -->
                <div class="rounded-xl border-2 p-4">
                    <p class="text-[10px] uppercase tracking-[0.25em] text-muted-foreground font-mono">Best Day</p>
                    <p class="text-2xl font-semibold tabular-nums mt-1">{{ bestDay ? formatEur(bestDay.value) : '—' }}</p>
                    <p class="text-xs text-muted-foreground mt-2 font-mono tracking-wide">{{ bestDay ? formatDateShort(bestDay.date) : '' }}</p>
                </div>
            </div>

            <!-- =========================================================
                 Empty state
                 ========================================================= -->
            <div v-if="!rows.length" class="rounded-2xl border-2 border-dashed p-12 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-muted">
                    <Sparkles class="h-7 w-7 text-muted-foreground" />
                </div>
                <h2 class="text-xl font-semibold tracking-tight">No revenue uploaded for {{ period.label }} yet</h2>
                <p class="mt-2 text-sm text-muted-foreground max-w-xl mx-auto font-mono tracking-wide">
                    Drop your GAM, SeedTag, ShowHeroes or Teads <code>.xlsx</code> exports and we'll stitch them into one comparative view. Filenames decide where each file lands.
                </p>
                <div class="mt-5 flex items-center justify-center gap-3">
                    <Button class="rounded-full font-mono tracking-wide" @click="uploadOpen = true">
                        <UploadCloud class="h-4 w-4 mr-1" /> UPLOAD REPORTS
                    </Button>
                    <button v-if="availablePeriods.months.length" type="button" class="text-sm text-foreground underline underline-offset-4 decoration-2 font-mono tracking-wide" @click="resetMonthToLatest">
                        Jump to {{ formatMonthLabel(availablePeriods.months[availablePeriods.months.length - 1] ?? '') }}
                    </button>
                </div>
            </div>

            <!-- =========================================================
                 TABLE — single combined grid with grouped section headers
                 ========================================================= -->
            <div v-if="rows.length && activeTab === 'table'" class="rounded-xl border-2 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <!-- Section group row: "" + Outstream (colspan=N) + Sticky (colspan=M) ... -->
                            <tr class="bg-foreground text-background">
                                <th class="px-3 py-2 text-left font-mono uppercase tracking-[0.25em] text-[10px] border-r border-background/20"></th>
                                <template v-for="(section, sIdx) in sections" :key="section.name">
                                    <th
                                        :colspan="section.sources.length"
                                        class="px-3 py-2 text-center font-mono uppercase tracking-[0.25em] text-[10px]"
                                        :class="sIdx < sections.length - 1 ? 'border-r border-background/20' : ''"
                                    >{{ sectionLabel(section.name) }}</th>
                                </template>
                            </tr>
                            <!-- Provider header row -->
                            <tr class="bg-muted/50">
                                <th class="px-3 py-2 text-left font-mono tracking-wide text-xs uppercase">Date</th>
                                <template v-for="(section, sIdx) in sections" :key="`hd-${section.name}`">
                                    <th
                                        v-for="(src, srcIdx) in section.sources"
                                        :key="src.key"
                                        class="px-3 py-2 text-right font-mono tracking-wide text-xs uppercase"
                                        :class="srcIdx === section.sources.length - 1 && sIdx < sections.length - 1 ? 'border-r-2' : ''"
                                    >{{ src.display_name }}</th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in rows" :key="row.date" class="border-t hover:bg-muted/30">
                                <td class="px-3 py-1.5 font-mono">{{ formatDate(row.date) }}</td>
                                <template v-for="(section, sIdx) in sections" :key="`bd-${section.name}-${row.date}`">
                                    <td
                                        v-for="(src, srcIdx) in section.sources"
                                        :key="src.key"
                                        class="px-3 py-1.5 text-right tabular-nums"
                                        :class="srcIdx === section.sources.length - 1 && sIdx < sections.length - 1 ? 'border-r-2' : ''"
                                    >{{ formatEur(row.cells[src.key]) }}</td>
                                </template>
                            </tr>
                            <tr class="border-t-2 bg-muted/40 font-semibold">
                                <td class="px-3 py-2 font-mono uppercase tracking-wide text-xs">Total</td>
                                <template v-for="(section, sIdx) in sections" :key="`tot-${section.name}`">
                                    <td
                                        v-for="(src, srcIdx) in section.sources"
                                        :key="src.key"
                                        class="px-3 py-2 text-right tabular-nums"
                                        :class="srcIdx === section.sources.length - 1 && sIdx < sections.length - 1 ? 'border-r-2' : ''"
                                    >{{ formatEur(totals[src.key]) }}</td>
                                </template>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =========================================================
                 INSIGHTS TAB
                 ========================================================= -->
            <div v-if="rows.length && activeTab === 'insights'" class="space-y-5">
                <div class="rounded-xl border-2 p-4">
                    <p class="text-xs font-mono uppercase tracking-[0.25em] text-muted-foreground mb-3">Daily revenue by provider</p>
                    <div class="h-80">
                        <Line :data="chartData" :options="chartOptions" />
                    </div>
                </div>

                <div class="rounded-xl border-2 overflow-hidden">
                    <div class="bg-foreground text-background px-4 py-2 text-xs font-mono uppercase tracking-[0.25em]">Daily totals</div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-mono tracking-wide text-xs uppercase">Date</th>
                                    <th v-for="sec in sections" :key="sec.name" class="px-3 py-2 text-right font-mono tracking-wide text-xs uppercase">{{ sectionLabel(sec.name) }}</th>
                                    <th class="px-3 py-2 text-right font-mono tracking-wide text-xs uppercase">Day Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in rows" :key="`day-${row.date}`" class="border-t hover:bg-muted/30">
                                    <td class="px-3 py-1.5 font-mono">{{ formatDate(row.date) }}</td>
                                    <td v-for="sec in sections" :key="sec.name" class="px-3 py-1.5 text-right tabular-nums">
                                        {{ formatEur(dayTotal(sec.name, row.date)) }}
                                    </td>
                                    <td class="px-3 py-1.5 text-right tabular-nums font-medium">
                                        {{ formatEur(dayTotalAll(row.date)) }}
                                    </td>
                                </tr>
                                <tr class="border-t-2 bg-muted/40 font-semibold">
                                    <td class="px-3 py-2 font-mono uppercase tracking-wide text-xs">Total</td>
                                    <td v-for="sec in sections" :key="sec.name" class="px-3 py-2 text-right tabular-nums">
                                        {{ formatEur(grandTotalsBySection[sec.name]) }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ formatEur(grandTotalAll) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================
             Upload Reports — Dialog
             ========================================================= -->
        <Dialog v-model:open="uploadOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="font-mono tracking-wide uppercase text-base">Upload Reports</DialogTitle>
                    <DialogDescription>
                        Drop the week's <code>.xlsx</code> exports below. Files are matched to a source by filename.
                    </DialogDescription>
                </DialogHeader>

                <!-- Drop zone -->
                <div
                    class="rounded-xl border-2 border-dashed p-8 text-center transition cursor-pointer select-none"
                    :class="dragOver ? 'border-foreground bg-muted/40' : 'border-muted-foreground/30 hover:border-muted-foreground/60'"
                    @click="pickFiles"
                    @dragover.prevent="dragOver = true"
                    @dragleave.prevent="dragOver = false"
                    @drop="onZoneDrop"
                >
                    <input ref="uploadInput" type="file" multiple accept=".xlsx,.xls,.csv" class="hidden" @change="onInputChange" />
                    <div v-if="!isUploading">
                        <UploadCloud class="mx-auto h-9 w-9 text-muted-foreground mb-2" />
                        <p class="text-sm font-medium">Drop files here, or click to choose</p>
                        <p class="text-xs text-muted-foreground mt-1 font-mono tracking-wide">.xlsx / .xls / .csv — up to 20 MB each</p>
                    </div>
                    <div v-else class="flex items-center justify-center gap-2 py-2">
                        <Loader2 class="h-5 w-5 animate-spin" />
                        <p class="text-sm font-medium">Uploading & parsing...</p>
                    </div>
                </div>

                <!-- Recognized patterns (collapsible inside the modal) -->
                <div class="rounded-lg border">
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 text-sm font-mono tracking-wide" @click="showPatterns = !showPatterns">
                        <span>Recognized filename patterns ({{ sourcesList.length }})</span>
                        <ChevronDown class="h-4 w-4 transition-transform" :class="showPatterns ? 'rotate-180' : ''" />
                    </button>
                    <div v-if="showPatterns" class="border-t p-3 grid gap-2 sm:grid-cols-2 max-h-56 overflow-y-auto">
                        <div v-for="src in sourcesList" :key="src.id" class="flex items-start justify-between gap-2 rounded-md border p-2">
                            <div>
                                <p class="text-sm font-medium">{{ src.display_name }} <span class="text-muted-foreground text-xs">({{ src.section }})</span></p>
                                <p class="text-[10px] text-muted-foreground font-mono">{{ src.key }}</p>
                            </div>
                            <code class="text-[10px] whitespace-nowrap rounded bg-muted px-1.5 py-1">{{ src.filename_pattern }}</code>
                        </div>
                    </div>
                </div>

                <DialogFooter class="text-xs text-muted-foreground font-mono tracking-wide sm:justify-start">
                    Re-uploaded dates overwrite existing values.
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
