<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import DateRangePicker from '@/components/DateRangePicker.vue';
import { useInitials } from '@/composables/useInitials';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Upload, Download, AlertTriangle, CheckCircle2, XCircle, Loader2, CalendarDays, Coins, Eye, TrendingUp, Award, CalendarCheck, X, FileText, ArrowLeft, ExternalLink, Plus, Link2, Mail, Copy, Trash2, RefreshCw, Circle, Settings, Minus, ChevronDown } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, onMounted } from 'vue';
import { Line, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS, Title, Tooltip, Legend, Filler,
    LineElement, CategoryScale, LinearScale, PointElement, ArcElement,
} from 'chart.js';

import vueFilePond from 'vue-filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import 'filepond/dist/filepond.min.css';
const FilePond = vueFilePond(FilePondPluginFileValidateType);

ChartJS.register(Title, Tooltip, Legend, Filler, LineElement, CategoryScale, LinearScale, PointElement, ArcElement);


const page = usePage();
const store = computed<any>(() => page.props.store);
const sites = computed<any[]>(() => (page.props.sites as any[]) ?? []);
const authUser = computed<any>(() => (page.props.auth as any)?.user ?? null);
const { getInitials } = useInitials();

// ─── Data sync (import the committed JSON snapshot into this machine's DB) ──────
const sync = computed<any>(() => (page.props.sync as any) ?? { available: false });
const syncing = ref(false);
function performSync() {
    const prevF1Keys = new Set(Object.keys(store.value?.sites?.f1maximaal?.days ?? {}));
    router.post('/reporting/sync', {}, {
        preserveScroll: true, preserveState: true,
        onStart: () => (syncing.value = true),
        onFinish: () => (syncing.value = false),
        onSuccess: () => {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Reporting data updated', timer: 1500, showConfirmButton: false });
            checkNewAdhesDates(prevF1Keys);
        },
    });
}
function confirmSync() {
    const n = sync.value.newDays ?? 0;
    const c = sync.value.changedDays ?? 0;
    const bits = [];
    if (n) bits.push(`<b>${n}</b> new day${n === 1 ? '' : 's'}`);
    if (c) bits.push(`<b>${c}</b> updated day${c === 1 ? '' : 's'}`);
    const summary = bits.length ? bits.join(' and ') : 'updated data';
    Swal.fire({
        icon: 'info',
        title: 'New reporting data found',
        html: `The committed snapshot${sync.value.exportedAt ? ` (<b>${sync.value.exportedAt}</b>)` : ''} has ${summary} to bring in. Import into this device?`,
        showCancelButton: true,
        confirmButtonText: 'Import now',
        confirmButtonColor: '#e2483d',
        cancelButtonText: 'Not now',
    }).then((r) => { if (r.isConfirmed) performSync(); });
}
function checkForData() {
    router.reload({
        onSuccess: () => {
            if (sync.value.available) confirmSync();
            else Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Already up to date', timer: 1400, showConfirmButton: false });
        },
    });
}
// Silent check on load — if the pulled snapshot is newer, ask before importing.
onMounted(() => { if (sync.value.available) confirmSync(); });

// ─── Deliverables reminder (configurable day; minimizable, never closable) ──────
const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const reminderDay = computed<number>(() => (page.props.reminderDay ?? 3) as number);
const reminderDayName = computed(() => DAY_NAMES[reminderDay.value] ?? 'Wednesday');
const isReminderDay = computed(() => new Date().getDay() === reminderDay.value);
const wnMinimized = ref(false);
const wnKey = () => 'wn-min-' + new Date().toISOString().slice(0, 10);
function minimizeWednesday() {
    wnMinimized.value = true;
    try { localStorage.setItem(wnKey(), '1'); } catch { /* ignore */ }
}
function expandWednesday() {
    wnMinimized.value = false;
    try { localStorage.removeItem(wnKey()); } catch { /* ignore */ }
}
onMounted(() => {
    try { wnMinimized.value = !!localStorage.getItem(wnKey()); } catch { /* ignore */ }
});

// ─── Settings modal (Ogury rate + reminder day) ────────────────────────────────
const showSettings = ref(false);
const settingsDay = ref(reminderDay.value);
function openSettings() { settingsDay.value = reminderDay.value; showSettings.value = true; }
function saveSettings() {
    router.post('/reporting/config', { oguryRate: oguryRate.value, reminderDay: settingsDay.value }, {
        preserveScroll: true, preserveState: true,
        onSuccess: () => { showSettings.value = false; Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Settings saved', timer: 1300, showConfirmButton: false }); },
    });
}

// ─── Report-source links (where each partner report is downloaded from) ────────
const reportLinks = ref<{ label: string; url: string }[]>([...((page.props.reportLinks as any[]) ?? [])]);
const newLink = ref({ label: '', url: '' });
const showAddLink = ref(false);
const showLinks = ref(false);
function saveLinks() {
    router.post('/reporting/links', { links: reportLinks.value }, { preserveScroll: true, preserveState: true });
}
function addLink() {
    if (!newLink.value.label.trim() || !newLink.value.url.trim()) { Swal.fire('Missing info', 'Enter a name and a URL.', 'info'); return; }
    reportLinks.value.push({ label: newLink.value.label.trim(), url: newLink.value.url.trim() });
    newLink.value = { label: '', url: '' };
    showAddLink.value = false;
    saveLinks();
}
function removeLink(i: number) {
    reportLinks.value.splice(i, 1);
    saveLinks();
}

// ─── Email report (per-site recipients + week-numbered subject) ─────────────────
const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
function isoWeekNumber(date: Date): number {
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = (d.getUTCDay() + 6) % 7;
    d.setUTCDate(d.getUTCDate() - dayNum + 3);
    const firstThursday = new Date(Date.UTC(d.getUTCFullYear(), 0, 4));
    const firstDayNum = (firstThursday.getUTCDay() + 6) % 7;
    firstThursday.setUTCDate(firstThursday.getUTCDate() - firstDayNum + 3);
    return 1 + Math.round((d.getTime() - firstThursday.getTime()) / (7 * 24 * 3600 * 1000));
}
function ordinalSuffix(n: number): string {
    const t = n % 100;
    if (t >= 11 && t <= 13) return 'th';
    return { 1: 'st', 2: 'nd', 3: 'rd' }[n % 10] ?? 'th';
}
const weekNumber = computed(() => isoWeekNumber(new Date()));
function subjectContext() {
    const now = new Date();
    const day = now.getDate();
    return { dateStr: `${MONTH_NAMES[now.getMonth()]} ${day}${ordinalSuffix(day)} ${now.getFullYear()}`, week: isoWeekNumber(now) };
}

type EmailCfg = { to: { name: string; email: string }[]; cc?: { name: string; email: string }[]; body: string; subject: (c: { dateStr: string; week: number }) => string };
const SITE_EMAILS: Record<string, EmailCfg> = {
    f1maximaal: {
        to: [
            { name: 'Martijn van der Spek (TopGear Nederland)', email: 'martijn@topgear.nl' },
            { name: 'Robert Heijmans (VDS Publishers)', email: 'robert@vds-publishers.nl' },
        ],
        cc: [{ name: 'Chris Beijer', email: 'chris@planetnine.com' }, { name: 'Taco Stomps', email: 'taco@planetnine.com' }],
        body: `Hi everyone,\n\nPlease find the attached performance report for F1Maximaal.\n\nBest regards,`,
        subject: (c) => `F1Maximaal Revenue Report - ${c.dateStr}`,
    },
    topgear: {
        to: [
            { name: 'Martijn van der Spek (TopGear Nederland)', email: 'martijn@topgear.nl' },
            { name: 'Roland van der Spek (VDS Publishers)', email: 'roland@vds-publishers.nl' },
            { name: 'Robert Heijmans (VDS Publishers)', email: 'robert@vds-publishers.nl' },
        ],
        cc: [{ name: 'Chris Beijer', email: 'chris@planetnine.com' }, { name: 'Taco Stomps', email: 'taco@planetnine.com' }],
        body: `Dear Martijn, Robert and Roland,\n\nHope you are doing well!\n\nPlease find this week's revenue reports for TopGear attached. If you have any questions, please reach out.\n\nBest regards`,
        subject: (c) => `Top Gear Ad Revenue Reports - Week ${c.week}`,
    },
    horses: {
        to: [
            { name: '', email: 'a.vanbel@eisma.nl' }, { name: '', email: 'J.vanderMolen@eisma.nl' },
            { name: '', email: 'c.haentjens@eisma.nl' }, { name: '', email: 'M.Matser@eisma.nl' },
        ],
        cc: [{ name: 'Chris Beijer', email: 'chris@planetnine.com' }, { name: 'Taco Stomps', email: 'taco@planetnine.com' }],
        body: `Dear All,\n\nHope you are doing well!\n\nPlease find this week's revenue reports for Horses.nl attached. Should you have any questions, please reach out.\n\nBest regards`,
        subject: (c) => `Horses.nl - Ad Revenue Reports - Week ${c.week}`,
    },
    festileaks: {
        to: [{ name: '', email: 'finance@festileaks.com' }, { name: 'Jos Willemsen', email: 'jos@festileaks.com' }],
        cc: [{ name: 'Chris Beijer', email: 'chris@planetnine.com' }, { name: 'Taco Stomps', email: 'taco@planetnine.com' }],
        body: `Dear Jos,\n\nHope you are doing well!\n\nPlease find this week's Festileaks revenue reports attached. Should you have any questions, please reach out.\n\nBest regards`,
        subject: (c) => `Festileaks Ad Revenue Reports - Week ${c.week - 1}`,
    },
};

const emailCfg = computed<EmailCfg | null>(() => SITE_EMAILS[selectedSite.value] ?? null);
const emailSubject = computed(() => (emailCfg.value ? emailCfg.value.subject(subjectContext()) : ''));
function copy(text: string) {
    navigator.clipboard?.writeText(text);
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Copied', timer: 1000, showConfirmButton: false });
}

const PARTNERS: { key: string; label: string; lines?: [string, string] }[] = [
    { key: 'adhese', label: 'Adhese' }, { key: 'gam', label: 'GAM' },
    { key: 'seedtag', label: 'SeedTag' }, { key: 'teads', label: 'Teads' },
    { key: 'showheroes', label: 'Showheroes' }, { key: 'adform', label: 'Adform' },
    { key: 'ogury', label: 'Ogury' }, { key: 'outbrain', label: 'Outbrain' },
    { key: 'preferredDeals', label: 'Preferred Deals', lines: ['Preferred', 'Deals'] },
];
const COLORS = ['#e2483d', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6', '#a3a3a3', '#6366f1'];

const selectedSite = ref('f1maximaal');
const activeTab = ref<'summary' | 'table' | 'verify' | 'email'>('summary');
const oguryRate = ref<number>(store.value?.config?.oguryRate ?? 0.85);
const processing = ref(false);
const showAdheseModal = ref(false);
const adheseEntries = ref<{ dateKey: string; adhese: number | null }[]>([]);
const adheseSaving = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const dragging = ref(false);
const selectedUploadFiles = ref<File[]>([]);

// Date-range filter. Defaults to the current month (preset applied below).
const from = ref('');
const to = ref('');
const PRESETS = ['Yesterday', 'Last 7 days', 'This month', 'Last month', 'All'] as const;
const activePreset = ref<string>('This month');

const days = computed<any[]>(() => {
    const map = store.value?.sites?.[selectedSite.value]?.days ?? {};
    let arr = Object.values(map).sort((a: any, b: any) => a.dateKey.localeCompare(b.dateKey));
    if (from.value) arr = arr.filter((d: any) => d.dateKey >= from.value);
    if (to.value) arr = arr.filter((d: any) => d.dateKey <= to.value);
    return arr;
});

const ymd = (d: Date) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
function applyPreset(name: string) {
    activePreset.value = name;
    const now = new Date();
    if (name === 'Today') { from.value = to.value = ymd(now); }
    else if (name === 'Yesterday') { const y = new Date(now); y.setDate(now.getDate() - 1); from.value = to.value = ymd(y); }
    else if (name === 'Last 7 days') { const s = new Date(now); s.setDate(now.getDate() - 6); from.value = ymd(s); to.value = ymd(now); }
    else if (name === 'This month') { from.value = ymd(new Date(now.getFullYear(), now.getMonth(), 1)); to.value = ymd(now); }
    else if (name === 'Last month') { from.value = ymd(new Date(now.getFullYear(), now.getMonth() - 1, 1)); to.value = ymd(new Date(now.getFullYear(), now.getMonth(), 0)); }
    else { from.value = ''; to.value = ''; }
}
applyPreset('This month');

const eur = (n: number) => '€' + (n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const num = (n: number) => (n || 0).toLocaleString('en-US');

const partnerTotals = computed(() => {
    const totals: Record<string, number> = {};
    let grand = 0;
    for (const p of PARTNERS) totals[p.key] = 0;
    for (const d of days.value) {
        for (const p of PARTNERS) {
            const v = d.revenue?.[p.key] ?? 0;
            totals[p.key] += v; grand += v;
        }
    }
    return { totals, grand };
});

const impressionsSoldTotal = computed(() =>
    days.value.reduce((t, d) => t + (d.impressionsSold || 0), 0));

const avgDaily = computed(() => (days.value.length ? partnerTotals.value.grand / days.value.length : 0));

// Partners that actually have revenue, ranked, with colour + share — drives both
// the doughnut and the legend list.
const partnerBreakdown = computed(() => {
    const t = partnerTotals.value.totals;
    const grand = partnerTotals.value.grand || 1;
    return PARTNERS.map((p, i) => ({ ...p, total: t[p.key], pct: (t[p.key] / grand) * 100, color: COLORS[i % COLORS.length] }))
        .filter((x) => x.total > 0)
        .sort((a, b) => b.total - a.total);
});
const topPartner = computed(() => partnerBreakdown.value[0] ?? null);

const bestDay = computed(() => {
    let best: { dateKey: string; total: number; partner: string } | null = null;
    for (const d of days.value) {
        const tot = PARTNERS.reduce((t, p) => t + (d.revenue?.[p.key] ?? 0), 0);
        if (!best || tot > best.total) {
            let top = { label: '—', v: -1 };
            for (const p of PARTNERS) {
                const v = d.revenue?.[p.key] ?? 0;
                if (v > top.v) top = { label: p.label, v };
            }
            best = { dateKey: d.dateKey, total: tot, partner: top.label };
        }
    }
    return best;
});

// ─── Charts ──────────────────────────────────────────────────────────────────
const revenueChart = computed(() => ({
    labels: days.value.map((d) => d.dateKey.slice(5)),
    datasets: [{
        label: 'Revenue',
        data: days.value.map((d) => PARTNERS.reduce((t, p) => t + (d.revenue?.[p.key] ?? 0), 0)),
        borderColor: '#e2483d',
        borderWidth: 2,
        tension: 0.4,
        pointRadius: 0,
        pointHoverRadius: 4,
        pointHoverBackgroundColor: '#e2483d',
        fill: true,
        backgroundColor: (ctx: any) => {
            const { ctx: c, chartArea } = ctx.chart;
            if (!chartArea) return 'rgba(226,72,61,0.15)';
            const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            g.addColorStop(0, 'rgba(226,72,61,0.30)');
            g.addColorStop(1, 'rgba(226,72,61,0)');
            return g;
        },
    }],
}));
const splitChart = computed(() => ({
    labels: partnerBreakdown.value.map((p) => p.label),
    datasets: [{ data: partnerBreakdown.value.map((p) => p.total), backgroundColor: partnerBreakdown.value.map((p) => p.color), borderWidth: 0, hoverOffset: 6 }],
}));

const gridColor = 'rgba(128,128,128,0.12)';
const tickColor = 'rgba(128,128,128,0.8)';
const tip = {
    backgroundColor: 'rgba(17,17,17,0.9)', padding: 10, cornerRadius: 8, displayColors: false,
    titleFont: { size: 11 }, bodyFont: { size: 12, weight: 'bold' as const },
};
const chartOptions = {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index' as const, intersect: false },
    plugins: {
        legend: { display: false },
        tooltip: { ...tip, callbacks: { label: (c: any) => '€' + c.parsed.y.toLocaleString('en-US', { maximumFractionDigits: 0 }) } },
    },
    scales: {
        x: { grid: { display: false }, border: { display: false }, ticks: { color: tickColor, font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } },
        y: { grid: { color: gridColor }, border: { display: false }, ticks: { color: tickColor, font: { size: 10 }, maxTicksLimit: 5, callback: (v: any) => '€' + v } },
    },
};
const doughnutOptions = {
    responsive: true, maintainAspectRatio: false, cutout: '70%',
    plugins: {
        legend: { display: false },
        tooltip: { ...tip, callbacks: { label: (c: any) => c.label + ': €' + c.parsed.toLocaleString('en-US', { maximumFractionDigits: 0 }) } },
    },
};

// ─── Anomalies (zero/missing only) ─────────────────────────────────────────────
const anomaliesOpen = ref(false);
const anomalies = computed(() => {
    const arr = days.value; const n = arr.length; const out: any[] = [];
    for (const p of PARTNERS) {
        const vals = arr.map((d) => { const v = d.revenue?.[p.key]; return v == null ? null : v; });
        const present = vals.filter((v) => (v ?? 0) > 0).length;
        if (!(n >= 4 && present >= Math.ceil(0.6 * n) && present < n)) continue;
        vals.forEach((v, i) => {
            if (v == null || v === 0) out.push({ dateKey: arr[i].dateKey, detail: `${p.label} usually reports but was 0` });
        });
    }
    return out.sort((a, b) => b.dateKey.localeCompare(a.dateKey));
});

// ─── File selection + client-side missing-files detection ──────────────────────
// Mirrors the server's detectFileType so we can tell which required reports are
// missing the moment files are dropped — before any processing.
function detectType(filename: string): string {
    const n = filename.toLowerCase();
    if (n.includes('adhese gateway') || n.startsWith('adhese')) return 'adhese';
    if (n.includes('pages_and_screens') || n.includes('content_group')) return 'analytics';
    if (/^tg[\s_]\d/.test(n) || n.startsWith('tg 2')) return 'adform';
    if (n.includes('copy of general data download')) return 'gam';
    if (n.startsWith('export-ad-units')) return 'ogury';
    if (n.startsWith('revenue-export')) return 'seedtag';
    if (n.startsWith('topgear-')) return 'showheroes';
    if (n.startsWith('report_finance')) return 'teads';
    if (n.includes('current-view') || n.includes('all publishers')) return 'outbrain';
    if (n.startsWith('impressions') && n.includes('f1')) return 'impressions_f1';
    if (n.includes('preferred') && n.includes('deal')) return 'preferreddeals';
    if (/copy.*f1max/.test(n) || n.includes('copy of f1maximaal')) return 'gam_f1m';
    return 'unknown';
}

const REQUIRED: { key: string; label: string }[] = [
    { key: 'adform', label: 'Adform' }, { key: 'gam', label: 'GAM' }, { key: 'gam_f1m', label: 'GAM F1M' },
    { key: 'ogury', label: 'Ogury' }, { key: 'seedtag', label: 'SeedTag' }, { key: 'showheroes', label: 'Showheroes' },
    { key: 'teads', label: 'Teads' }, { key: 'analytics', label: 'Analytics' },
    { key: 'adhese_f1', label: 'Adhese (F1)' }, { key: 'adhese_tg', label: 'Adhese (TopGear)' }, { key: 'adhese_fl', label: 'Adhese (Festileaks)' },
    { key: 'outbrain', label: 'Outbrain' }, { key: 'preferreddeals', label: 'Preferred Deals' },
];

// Live checklist: each required file is checked once a matching dropped file is
// detected; unchecked items are the ones still missing.
const checklist = computed(() => {
    const detected = new Set<string>();
    const adhese = new Set<string>();
    const fileFor: Record<string, string> = {};
    for (const f of selectedUploadFiles.value) {
        const t = detectType(f.name);
        if (t === 'adhese') {
            const n = f.name.toLowerCase();
            let k = 'adhese_f1';
            if (n.includes('adhese tg') || n.includes('adhese topgear')) k = 'adhese_tg';
            else if (n.includes('adhese fl') || n.includes('adhese festileaks')) k = 'adhese_fl';
            adhese.add(k);
            if (!fileFor[k]) fileFor[k] = f.name;
        } else {
            detected.add(t);
            if (!fileFor[t]) fileFor[t] = f.name;
        }
    }
    return REQUIRED.map((r) => {
        const checked = r.key.startsWith('adhese_') ? adhese.has(r.key) : detected.has(r.key);
        return { ...r, checked, file: checked ? fileFor[r.key] : null };
    });
});
const requiredCount = computed(() => checklist.value.length);
const checkedCount = computed(() => checklist.value.filter((c) => c.checked).length);
// Dropped files that don't map to a required slot (unrecognised reports).
const extraFiles = computed(() => selectedUploadFiles.value.filter((f) => {
    const t = detectType(f.name);
    return !REQUIRED.some((r) => (r.key.startsWith('adhese_') ? t === 'adhese' : r.key === t));
}));

function addFiles(list: FileList | null) {
    if (!list) return;
    selectedUploadFiles.value = [...selectedUploadFiles.value, ...Array.from(list)];
}
function onFilesChosen(e: Event) {
    addFiles((e.target as HTMLInputElement).files);
    (e.target as HTMLInputElement).value = '';
}
function onDrop(e: DragEvent) {
    dragging.value = false;
    addFiles(e.dataTransfer?.files ?? null);
}
function removeFile(f: File) {
    selectedUploadFiles.value = selectedUploadFiles.value.filter((x) => x !== f);
}

// ─── Adhese batch entry ────────────────────────────────────────────────────────
function checkNewAdhesDates(prevKeys: Set<string>) {
    const newDays = store.value?.sites?.f1maximaal?.days ?? {};
    const toFill = Object.keys(newDays)
        .filter((dk) => !prevKeys.has(dk) && newDays[dk]?.impressions?.adhese == null)
        .sort();
    if (toFill.length > 0) {
        adheseEntries.value = toFill.map((dk) => ({ dateKey: dk, adhese: null }));
        showAdheseModal.value = true;
    }
}

function saveAdheseBatch() {
    adheseSaving.value = true;
    router.post('/reporting/save-adhese-batch', { entries: adheseEntries.value }, {
        preserveScroll: true, preserveState: true,
        onFinish: () => { adheseSaving.value = false; },
        onSuccess: () => {
            showAdheseModal.value = false;
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Adhese impressions saved', timer: 1300, showConfirmButton: false });
        },
    });
}

// ─── Upload / process ──────────────────────────────────────────────────────────
function processFiles() {
    if (!selectedUploadFiles.value.length) { Swal.fire('No files', 'Add the partner files first.', 'info'); return; }
    const prevF1Keys = new Set(Object.keys(store.value?.sites?.f1maximaal?.days ?? {}));
    const fd = new FormData();
    selectedUploadFiles.value.forEach((f) => fd.append('files[]', f, f.name));
    router.post('/reporting/process', fd, {
        forceFormData: true, preserveScroll: true, preserveState: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
        onSuccess: () => {
            selectedUploadFiles.value = [];
            checkNewAdhesDates(prevF1Keys);
            if (!showAdheseModal.value) {
                Swal.fire({ icon: 'success', title: 'Processed', timer: 1400, showConfirmButton: false });
            }
        },
        onError: (errors: any) => Swal.fire('Upload failed', (Object.values(errors)[0] as string) ?? 'Error', 'error'),
    });
}

function saveAdhese(day: any) {
    router.post('/reporting/save-adhese', { dateKey: day.dateKey, adhese: day.impressions?.adhese ?? null }, {
        preserveScroll: true, preserveState: true,
    });
}

function deleteDay(day: any) {
    Swal.fire({
        icon: 'warning', title: `Delete ${day.dateKey}?`,
        text: `Remove all ${sites.value.find((s) => s.id === selectedSite.value)?.name} data for this day.`,
        showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#e2483d',
    }).then((res) => {
        if (!res.isConfirmed) return;
        router.delete(`/reporting/${selectedSite.value}/${day.dateKey}`, { preserveScroll: true, preserveState: true });
    });
}

// Download modal — choose which files (and the current date range) to include.
const uploadFiles = computed<string[]>(() => (page.props.uploadFiles as string[]) ?? []);
const showDownload = ref(false);
const selectedFiles = ref<string[]>([]);
const dlFrom = ref('');
const dlTo = ref('');
function openDownload() {
    selectedFiles.value = [...uploadFiles.value];
    dlFrom.value = from.value;   // pre-fill with the current dashboard range
    dlTo.value = to.value;
    showDownload.value = true;
}
function confirmDownload() {
    const params = new URLSearchParams();
    if (dlFrom.value) params.set('from', dlFrom.value);
    if (dlTo.value) params.set('to', dlTo.value);
    if (selectedFiles.value.length) params.set('files', selectedFiles.value.join(','));
    window.location.href = '/reporting/download?' + params.toString();
    showDownload.value = false;
}
const allFilesSelected = computed(() => uploadFiles.value.length > 0 && selectedFiles.value.length === uploadFiles.value.length);
function toggleAllFiles() {
    selectedFiles.value = allFilesSelected.value ? [] : [...uploadFiles.value];
}
const DL_PRESETS = ['This month', 'Last month', 'All time'] as const;
function dlPreset(name: string) {
    const now = new Date();
    if (name === 'This month') { dlFrom.value = ymd(new Date(now.getFullYear(), now.getMonth(), 1)); dlTo.value = ymd(now); }
    else if (name === 'Last month') { dlFrom.value = ymd(new Date(now.getFullYear(), now.getMonth() - 1, 1)); dlTo.value = ymd(new Date(now.getFullYear(), now.getMonth(), 0)); }
    else { dlFrom.value = ''; dlTo.value = ''; }
}

// ─── Verify ──────────────────────────────────────────────────────────────────
const verifying = ref(false);
const verifyPond = ref<any>(null);
const verifyFile = ref<File | null>(null);
const verifyRows = computed<any[] | null>(() => (page.props.verifyResult as any)?.rows ?? null);
const verifyError = computed<string>(() => (page.props.verifyError as string) ?? '');
const passed = (c: any) => Math.abs((c.pn || 0) - (c.us || 0)) <= c.tol;

const verifyDayCount = computed(() => verifyRows.value?.length ?? 0);
const verifyMismatches = computed(() => {
    const out: any[] = [];
    for (const r of verifyRows.value ?? []) {
        for (const c of r.checks) {
            if (!passed(c)) out.push({ dateKey: r.dateKey, label: c.label, us: c.us || 0, pn: c.pn || 0, diff: (c.us || 0) - (c.pn || 0) });
        }
    }
    return out;
});

function onVerifyUpdate(items: any[]) {
    verifyFile.value = items.length ? (items[0].file as File) : null;
}
function runVerify() {
    if (!verifyFile.value) { Swal.fire('No file', 'Add the Planetnine report.', 'info'); return; }
    const fd = new FormData();
    fd.append('file', verifyFile.value, verifyFile.value.name);
    const url = selectedSite.value === 'f1maximaal' ? '/reporting/verify' : '/reporting/verify-weekly';
    if (selectedSite.value !== 'f1maximaal') fd.append('site', selectedSite.value);
    router.post(url, fd, {
        forceFormData: true, preserveScroll: true, preserveState: true,
        onStart: () => (verifying.value = true),
        onFinish: () => (verifying.value = false),
    });
}

const tabs = [
    { id: 'summary', label: 'Summary' }, { id: 'table', label: 'Table' },
    { id: 'verify', label: 'Verify' }, { id: 'email', label: 'Email' },
] as const;
</script>

<template>
    <Head title="Reporting" />
    <div class="min-h-screen bg-background font-mono text-foreground">
        <!-- Standalone top bar (no app sidebar) with a way back -->
        <header class="sticky top-0 z-40 border-b bg-background/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3">
                <div class="flex items-center gap-3">
                    <Link href="/dashboard"
                        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm text-muted-foreground transition hover:bg-muted hover:text-foreground">
                        <ArrowLeft class="h-4 w-4" /> Back
                    </Link>
                    <!-- <span class="text-sm font-semibold">Reporting</span> -->
                </div>

                <!-- User menu (same avatar/profile controls as the app navbar) -->
                <DropdownMenu v-if="authUser">
                    <DropdownMenuTrigger as-child>
                        <button class="rounded-full outline-none focus:ring-2 focus:ring-[#e2483d]/40">
                            <Avatar class="size-8 overflow-hidden rounded-full">
                                <AvatarImage v-if="authUser.avatar" :src="authUser.avatar" :alt="authUser.name" />
                                <AvatarFallback class="rounded-full bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                    {{ getInitials(authUser.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="authUser" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>

        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold">Reporting</h1>
                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium tabular-nums">Week {{ weekNumber }}</span>
                    </div>
                    <p class="text-sm text-muted-foreground">Ad-partner impressions & revenue across the four sites.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="outline" :disabled="syncing" @click="checkForData">
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': syncing }" /> Check for data
                    </Button>
                    <Button variant="outline" @click="showLinks = true"><Link2 class="mr-2 h-4 w-4" /> Report links</Button>
                    <Button variant="outline" @click="openDownload"><Download class="mr-2 h-4 w-4" /> Download</Button>
                    <Button variant="outline" @click="openSettings"><Settings class="mr-2 h-4 w-4" /> Settings</Button>
                </div>
            </div>

            <!-- Data migration banner -->
            <div v-if="syncing" class="flex items-center gap-3 rounded-xl border border-[#e2483d]/40 bg-[#e2483d]/5 p-4 text-sm">
                <Loader2 class="h-5 w-5 shrink-0 animate-spin text-[#e2483d]" />
                <div><span class="font-medium">New data found — migration in progress.</span> Please wait…</div>
            </div>

            <!-- Upload card: checklist drop zone -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between gap-2 pb-2">
                    <div class="flex items-center gap-2">
                        <Upload class="h-5 w-5 text-[#e2483d]" />
                        <span class="font-medium">Upload partner files</span>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium tabular-nums"
                        :class="checkedCount === requiredCount ? 'bg-emerald-500/15 text-emerald-600' : 'bg-muted text-muted-foreground'">
                        {{ checkedCount }}/{{ requiredCount }} ready
                    </span>
                </CardHeader>
                <CardContent class="flex flex-col gap-3">
                    <input ref="fileInput" type="file" multiple class="hidden" @change="onFilesChosen" />
                    <div class="rounded-xl border-2 border-dashed p-4 transition"
                        :class="dragging ? 'border-[#e2483d] bg-[#e2483d]/5' : 'border-muted-foreground/25'"
                        @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="onDrop">
                        <p class="mb-3 text-center text-sm text-muted-foreground">
                            Drag &amp; drop reports here, or
                            <button type="button" class="font-medium text-[#e2483d] hover:underline" @click="fileInput?.click()">browse</button>
                            — checked = received, unchecked = still missing.
                        </p>
                        <div class="grid gap-1.5 sm:grid-cols-2">
                            <div v-for="item in checklist" :key="item.key"
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm transition"
                                :class="item.checked ? 'bg-emerald-500/10' : 'bg-muted/40'">
                                <CheckCircle2 v-if="item.checked" class="h-4 w-4 shrink-0 text-emerald-500" />
                                <Circle v-else class="h-4 w-4 shrink-0 text-muted-foreground/40" />
                                <span :class="item.checked ? 'font-medium' : 'text-muted-foreground'">{{ item.label }}</span>
                                <span v-if="item.file" class="ml-auto max-w-[45%] truncate text-[11px] text-muted-foreground">{{ item.file }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Extra/optional files dropped that aren't in the checklist -->
                    <div v-if="extraFiles.length" class="flex flex-wrap gap-1.5">
                        <span v-for="(f, i) in extraFiles" :key="i" class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-1 text-xs">
                            <FileText class="h-3 w-3 text-muted-foreground" /> {{ f.name }}
                            <button class="text-muted-foreground hover:text-red-500" @click="removeFile(f)"><X class="h-3 w-3" /></button>
                        </span>
                    </div>

                    <div v-if="selectedUploadFiles.length" class="flex items-center gap-3">
                        <Button :disabled="processing" @click="processFiles">
                            <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                            Process {{ selectedUploadFiles.length }} file{{ selectedUploadFiles.length === 1 ? '' : 's' }}
                        </Button>
                        <button class="text-xs text-muted-foreground hover:underline" @click="selectedUploadFiles = []">Clear</button>
                    </div>
                </CardContent>
            </Card>

            <!-- Site selector + tabs -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-1 rounded-lg border p-1">
                    <button v-for="s in sites" :key="s.id"
                        class="rounded-md px-3 py-1.5 text-sm transition"
                        :class="selectedSite === s.id ? 'bg-[#e2483d] text-white' : 'hover:bg-muted'"
                        @click="selectedSite = s.id">{{ s.name }}</button>
                </div>
                <div class="flex gap-1 rounded-lg border p-1">
                    <button v-for="t in tabs" :key="t.id"
                        class="rounded-md px-3 py-1.5 text-sm transition"
                        :class="activeTab === t.id ? 'bg-foreground text-background' : 'hover:bg-muted'"
                        @click="activeTab = t.id">{{ t.label }}</button>
                </div>
            </div>

            <!-- Date range filter -->
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 rounded-xl border bg-card p-2">
                <!-- preset pills -->
                <div class="flex flex-wrap items-center gap-1 rounded-lg bg-muted/50 p-1">
                    <button v-for="p in PRESETS" :key="p"
                        class="rounded-md px-3 py-1.5 text-xs font-medium transition"
                        :class="activePreset === p ? 'bg-[#e2483d] text-white shadow-sm' : 'text-muted-foreground hover:bg-background hover:text-foreground'"
                        @click="applyPreset(p)">{{ p }}</button>
                </div>

                <!-- range picker + count -->
                <div class="flex items-center gap-2">
                    <DateRangePicker v-model:from="from" v-model:to="to" @change="activePreset = ''" />
                    <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium">{{ days.length }} days</span>
                </div>
            </div>

            <!-- Anomalies (zero/missing partners for the selected range) — Summary tab only, collapsed by default -->
            <Card v-if="anomalies.length && activeTab === 'summary'" class="border-amber-500/40">
                <button class="flex w-full items-center gap-2 px-6 py-4 text-left" @click="anomaliesOpen = !anomaliesOpen">
                    <AlertTriangle class="h-5 w-5 shrink-0 text-amber-500" />
                    <span class="font-medium">Anomalies <span class="text-muted-foreground">({{ anomalies.length }})</span></span>
                    <ChevronDown class="ml-auto h-4 w-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': anomaliesOpen }" />
                </button>
                <CardContent v-show="anomaliesOpen" class="pt-0">
                    <ul class="flex flex-col gap-1 text-sm">
                        <li v-for="(a, i) in anomalies.slice(0, 60)" :key="i" class="flex items-center gap-2">
                            <span class="rounded bg-amber-500/15 px-1.5 py-0.5 text-xs font-medium tabular-nums">{{ a.dateKey }}</span>
                            <span class="text-muted-foreground">{{ a.detail }}</span>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <!-- Snapshot / sync footer -->
            <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 pt-2 text-center text-xs text-muted-foreground">
                <span v-if="sync.syncedAt">Data snapshot: {{ sync.syncedAt }}</span>
                <span v-else>No data snapshot imported yet.</span>
                <button v-if="sync.available" class="rounded-full bg-[#e2483d]/10 px-2 py-0.5 font-medium text-[#e2483d] hover:bg-[#e2483d]/20" @click="confirmSync">
                    {{ sync.pending }} day{{ sync.pending === 1 ? '' : 's' }} ready to sync
                </button>
            </div>

            <!-- SUMMARY -->
            <div v-show="activeTab === 'summary'" class="flex flex-col gap-4">
                <!-- KPI cards (single row) -->
                <div class="flex flex-col gap-4 md:flex-row">
                    <Card class="min-w-0 flex-1">
                        <CardContent class="flex items-center gap-2.5 pt-5">
                            <div class="shrink-0 rounded-lg bg-[#e2483d]/10 p-2 text-[#e2483d]"><Coins class="h-5 w-5" /></div>
                            <div class="min-w-0"><div class="text-xs text-muted-foreground">Total revenue</div><div class="truncate text-base font-semibold tracking-tight">{{ eur(partnerTotals.grand) }}</div></div>
                        </CardContent>
                    </Card>
                    <Card v-if="selectedSite === 'f1maximaal'" class="min-w-0 flex-1">
                        <CardContent class="flex items-center gap-2.5 pt-5">
                            <div class="shrink-0 rounded-lg bg-blue-500/10 p-2 text-blue-500"><Eye class="h-5 w-5" /></div>
                            <div class="min-w-0"><div class="text-xs text-muted-foreground">Impressions</div><div class="truncate text-base font-semibold tracking-tight">{{ num(impressionsSoldTotal) }}</div></div>
                        </CardContent>
                    </Card>
                    <Card class="min-w-0 flex-1">
                        <CardContent class="flex items-center gap-2.5 pt-5">
                            <div class="shrink-0 rounded-lg bg-emerald-500/10 p-2 text-emerald-500"><TrendingUp class="h-5 w-5" /></div>
                            <div class="min-w-0"><div class="text-xs text-muted-foreground">Avg / day</div><div class="truncate text-base font-semibold tracking-tight">{{ eur(avgDaily) }}</div></div>
                        </CardContent>
                    </Card>
                    <Card class="min-w-0 flex-1">
                        <CardContent class="flex items-center gap-2.5 pt-5">
                            <div class="shrink-0 rounded-lg bg-amber-500/10 p-2 text-amber-500"><Award class="h-5 w-5" /></div>
                            <div class="min-w-0"><div class="text-xs text-muted-foreground">Top partner</div><div class="truncate text-base font-semibold tracking-tight">{{ topPartner?.label ?? '—' }}</div></div>
                        </CardContent>
                    </Card>
                    <Card class="min-w-0 flex-1">
                        <CardContent class="flex items-center gap-2.5 pt-5">
                            <div class="shrink-0 rounded-lg bg-violet-500/10 p-2 text-violet-500"><CalendarCheck class="h-5 w-5" /></div>
                            <div class="min-w-0">
                                <div class="text-xs text-muted-foreground">Best day</div>
                                <div class="truncate text-base font-semibold tracking-tight">{{ bestDay ? eur(bestDay.total) : '—' }}</div>
                                <div v-if="bestDay" class="truncate text-[11px] text-muted-foreground">{{ bestDay.dateKey }} · {{ bestDay.partner }}</div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Charts -->
                <div class="grid gap-4 lg:grid-cols-3">
                    <Card class="lg:col-span-2">
                        <CardHeader class="pb-2 font-medium">Revenue trend</CardHeader>
                        <CardContent><div class="h-72"><Line :data="revenueChart" :options="chartOptions" /></div></CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="pb-2 font-medium">Revenue by partner</CardHeader>
                        <CardContent class="flex flex-col gap-4">
                            <div class="relative mx-auto h-44 w-44">
                                <Doughnut :data="splitChart" :options="doughnutOptions" />
                                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-[11px] text-muted-foreground">Total</span>
                                    <span class="text-sm font-semibold">{{ eur(partnerTotals.grand) }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <div v-for="p in partnerBreakdown" :key="p.key" class="flex items-center gap-2 text-xs">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ background: p.color }"></span>
                                    <span class="flex-1 truncate">{{ p.label }}</span>
                                    <span class="tabular-nums text-muted-foreground">{{ p.pct.toFixed(1) }}%</span>
                                    <span class="w-20 text-right font-medium tabular-nums">{{ eur(p.total) }}</span>
                                </div>
                                <p v-if="!partnerBreakdown.length" class="py-4 text-center text-sm text-muted-foreground">No revenue in this range.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- TABLE -->
            <Card v-show="activeTab === 'table'">
                <CardContent class="overflow-x-auto pt-6">
                    <table class="w-full whitespace-nowrap text-xs">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="px-1.5 py-2">Date</th>
                                <th v-for="p in PARTNERS" :key="p.key" class="px-1.5 py-2 text-right leading-tight">
                                    <template v-if="p.lines">
                                        {{ p.lines[0] }}<br>{{ p.lines[1] }}
                                    </template>
                                    <template v-else>{{ p.label }}</template>
                                </th>
                                <th class="px-1.5 py-2 text-right">Total</th>
                                <th v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-2 text-right leading-tight">Adhese<br>impr.</th>
                                <th v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-2 text-right leading-tight">Impr.<br>sold</th>
                                <th v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-2 text-right leading-tight">Ad<br>requests</th>
                                <th class="px-1.5 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in days" :key="d.dateKey" class="border-b last:border-0">
                                <td class="px-1.5 py-1 font-medium">{{ d.dateKey }}</td>
                                <td v-for="p in PARTNERS" :key="p.key" class="px-1.5 py-1 text-right" :class="{ 'text-muted-foreground': !(d.revenue?.[p.key]) }">
                                    {{ (d.revenue?.[p.key] ?? 0).toFixed(2) }}
                                </td>
                                <td class="px-1.5 py-1 text-right font-semibold">
                                    {{ PARTNERS.reduce((t, p) => t + (d.revenue?.[p.key] ?? 0), 0).toFixed(2) }}
                                </td>
                                <td v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-1 text-right">
                                    <Input v-model.number="d.impressions.adhese" type="number" class="ml-auto h-7 w-24 px-1.5 text-right text-[11px] leading-none" @change="saveAdhese(d)" />
                                </td>
                                <td v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-1 text-right">{{ num(d.impressionsSold || 0) }}</td>
                                <td v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-1 text-right">{{ num(d.totalAdRequests || 0) }}</td>
                                <td class="px-1.5 py-1 text-right">
                                    <button class="text-muted-foreground transition hover:text-red-500" title="Delete day" @click="deleteDay(d)"><Trash2 class="h-3.5 w-3.5" /></button>
                                </td>
                            </tr>
                            <!-- Totals row -->
                            <tr v-if="days.length" class="border-t-2 bg-muted/30 font-semibold">
                                <td class="px-1.5 py-1.5 text-xs uppercase tracking-wide text-muted-foreground">Total</td>
                                <td v-for="p in PARTNERS" :key="p.key" class="px-1.5 py-1.5 text-right">
                                    {{ partnerTotals.totals[p.key].toFixed(2) }}
                                </td>
                                <td class="px-1.5 py-1.5 text-right">{{ partnerTotals.grand.toFixed(2) }}</td>
                                <td v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-1.5 text-right">—</td>
                                <td v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-1.5 text-right">{{ num(days.reduce((t, d) => t + (d.impressionsSold || 0), 0)) }}</td>
                                <td v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-1.5 text-right">{{ num(days.reduce((t, d) => t + (d.totalAdRequests || 0), 0)) }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!days.length" class="py-6 text-center text-muted-foreground">No data yet — upload partner files above.</p>
                </CardContent>
            </Card>

            <!-- VERIFY -->
            <Card v-show="activeTab === 'verify'">
                <CardHeader class="pb-2 font-medium">
                    Verify {{ selectedSite === 'f1maximaal' ? 'monthly' : 'weekly' }} report — {{ sites.find((s) => s.id === selectedSite)?.name }}
                </CardHeader>
                <CardContent class="flex flex-col gap-3">
                    <FilePond ref="verifyPond" :allow-multiple="false" :instant-upload="false" label-idle="Drop the Planetnine report here" @updatefiles="onVerifyUpdate" />
                    <div v-if="verifyFile">
                        <Button :disabled="verifying" @click="runVerify"><Loader2 v-if="verifying" class="mr-2 h-4 w-4 animate-spin" /> Run verification</Button>
                    </div>
                    <p v-if="verifyError" class="text-sm text-red-500">{{ verifyError }}</p>

                    <template v-if="verifyRows">
                        <!-- All reconciled -->
                        <div v-if="!verifyMismatches.length"
                            class="flex items-center gap-2 rounded-lg border border-emerald-500/40 bg-emerald-500/5 p-4 text-sm font-medium text-emerald-600">
                            <CheckCircle2 class="h-5 w-5 shrink-0" />
                            All {{ verifyDayCount }} day{{ verifyDayCount === 1 ? '' : 's' }} reconcile with the report.
                        </div>

                        <!-- Discrepancies only -->
                        <div v-else class="flex flex-col gap-2">
                            <div class="flex items-center gap-2 text-sm font-medium text-red-600">
                                <AlertTriangle class="h-5 w-5 shrink-0" />
                                {{ verifyMismatches.length }} discrepanc{{ verifyMismatches.length === 1 ? 'y' : 'ies' }} found
                                <span class="font-normal text-muted-foreground">· {{ verifyDayCount }} days checked</span>
                            </div>
                            <div class="overflow-x-auto rounded-lg border">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                            <th class="px-3 py-2 font-medium">Date</th>
                                            <th class="px-3 py-2 font-medium">Check</th>
                                            <th class="px-3 py-2 text-right font-medium">Ours</th>
                                            <th class="px-3 py-2 text-right font-medium">Report</th>
                                            <th class="px-3 py-2 text-right font-medium">Δ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(m, i) in verifyMismatches" :key="i" class="border-b last:border-0">
                                            <td class="px-3 py-2 tabular-nums">{{ m.dateKey }}</td>
                                            <td class="px-3 py-2">{{ m.label }}</td>
                                            <td class="px-3 py-2 text-right tabular-nums">{{ m.us.toFixed(2) }}</td>
                                            <td class="px-3 py-2 text-right tabular-nums">{{ m.pn.toFixed(2) }}</td>
                                            <td class="px-3 py-2 text-right font-medium tabular-nums" :class="m.diff >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                                {{ m.diff >= 0 ? '+' : '' }}{{ m.diff.toFixed(2) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </CardContent>
            </Card>

            <!-- EMAIL -->
            <Card v-show="activeTab === 'email'">
                <CardHeader class="flex flex-row items-center gap-2 pb-2 font-medium">
                    <Mail class="h-5 w-5 text-[#e2483d]" /> Email report — {{ sites.find((s) => s.id === selectedSite)?.name }}
                </CardHeader>
                <CardContent v-if="emailCfg" class="flex flex-col gap-3 text-sm">
                    <!-- To -->
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">To <span class="normal-case text-[10px]">(click to copy)</span></span>
                        <div class="flex flex-wrap gap-1">
                            <button v-for="r in emailCfg.to" :key="r.email" type="button"
                                class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-1 text-xs transition hover:bg-[#e2483d]/10 hover:text-[#e2483d]"
                                :title="`Copy ${r.email}`" @click="copy(r.email)">
                                <Copy class="h-3 w-3 opacity-60" /> {{ r.name ? `${r.name} <${r.email}>` : r.email }}
                            </button>
                        </div>
                    </div>
                    <!-- Cc -->
                    <div v-if="emailCfg.cc?.length" class="flex flex-col gap-1">
                        <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Cc <span class="normal-case text-[10px]">(click to copy)</span></span>
                        <div class="flex flex-wrap gap-1">
                            <button v-for="r in emailCfg.cc" :key="r.email" type="button"
                                class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-1 text-xs transition hover:bg-[#e2483d]/10 hover:text-[#e2483d]"
                                :title="`Copy ${r.email}`" @click="copy(r.email)">
                                <Copy class="h-3 w-3 opacity-60" /> {{ r.name ? `${r.name} <${r.email}>` : r.email }}
                            </button>
                        </div>
                    </div>
                    <!-- Subject -->
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Subject</span>
                            <button class="text-xs text-[#e2483d] hover:underline" @click="copy(emailSubject)">Copy</button>
                        </div>
                        <div class="rounded-md border px-3 py-2">{{ emailSubject }}</div>
                    </div>
                    <!-- Message -->
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Message</span>
                            <button class="text-xs text-[#e2483d] hover:underline" @click="copy(emailCfg.body)">Copy</button>
                        </div>
                        <pre class="whitespace-pre-wrap rounded-md border px-3 py-2 font-sans text-sm">{{ emailCfg.body }}</pre>
                    </div>
                </CardContent>
            </Card>

            <!-- Report links modal -->
            <div v-if="showLinks" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showLinks = false">
                <Card class="w-full max-w-lg">
                    <CardHeader class="flex flex-row items-center justify-between gap-2 pb-2">
                        <div class="flex items-center gap-2"><Link2 class="h-5 w-5 text-[#e2483d]" /><span class="font-medium">Report sources</span></div>
                        <div class="flex items-center gap-2">
                            <Button variant="outline" size="sm" @click="showAddLink = !showAddLink"><Plus class="mr-1 h-4 w-4" /> Add</Button>
                            <button class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" title="Close" @click="showLinks = false"><X class="h-4 w-4" /></button>
                        </div>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3">
                        <p class="text-xs text-muted-foreground">Open a partner's page in a new tab to download its report.</p>

                        <div class="flex max-h-80 flex-col gap-2 overflow-y-auto">
                            <div v-for="(l, i) in reportLinks" :key="i"
                                class="group flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition hover:bg-muted/40">
                                <a :href="l.url" target="_blank" rel="noopener noreferrer" class="flex min-w-0 flex-1 items-center gap-2">
                                    <ExternalLink class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                    <span class="min-w-0">
                                        <span class="block font-medium">{{ l.label }}</span>
                                        <span class="block truncate text-[11px] text-muted-foreground">{{ l.url }}</span>
                                    </span>
                                </a>
                                <button class="text-muted-foreground opacity-0 transition hover:text-red-500 group-hover:opacity-100"
                                    title="Remove" @click="removeLink(i)"><X class="h-4 w-4" /></button>
                            </div>
                            <p v-if="!reportLinks.length" class="text-sm text-muted-foreground">No sources yet — add one.</p>
                        </div>

                        <div v-if="showAddLink" class="flex flex-col gap-2 rounded-lg border bg-muted/30 p-3">
                            <Input v-model="newLink.label" placeholder="Name (e.g. SeedTag)" />
                            <Input v-model="newLink.url" placeholder="https://…" @keyup.enter="addLink" />
                            <div class="flex gap-2">
                                <Button size="sm" @click="addLink">Add</Button>
                                <Button size="sm" variant="ghost" @click="showAddLink = false">Cancel</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Download modal -->
            <div v-if="showDownload" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showDownload = false">
                <Card class="w-full max-w-2xl">
                    <CardHeader class="flex flex-row items-center justify-between gap-2 pb-3">
                        <span class="flex items-center gap-2 font-medium"><Download class="h-5 w-5 text-[#e2483d]" /> Download reports</span>
                        <button class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" title="Close" @click="showDownload = false"><X class="h-4 w-4" /></button>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <!-- Left: files -->
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Files</span>
                                    <button v-if="uploadFiles.length" class="text-xs text-[#e2483d] hover:underline" @click="toggleAllFiles">
                                        {{ allFilesSelected ? 'Clear all' : 'Select all' }}
                                    </button>
                                </div>
                                <div class="flex max-h-64 flex-col gap-1 overflow-y-auto rounded-lg border p-1">
                                    <label v-for="f in uploadFiles" :key="f"
                                        class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm transition hover:bg-muted">
                                        <input type="checkbox" :value="f" v-model="selectedFiles" class="accent-[#e2483d]" />
                                        <FileText class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                        <span class="truncate">{{ f }}</span>
                                    </label>
                                    <p v-if="!uploadFiles.length" class="px-2 py-3 text-sm text-muted-foreground">No files available yet — process an upload first.</p>
                                </div>
                            </div>

                            <!-- Right: date filter -->
                            <div class="flex flex-col gap-2">
                                <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Date range</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <button v-for="p in DL_PRESETS" :key="p"
                                        class="rounded-md border px-2.5 py-1 text-xs transition hover:bg-muted" @click="dlPreset(p)">{{ p }}</button>
                                </div>
                                <DateRangePicker v-model:from="dlFrom" v-model:to="dlTo" class="mt-1" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t pt-3">
                            <span class="text-xs text-muted-foreground">{{ selectedFiles.length }} of {{ uploadFiles.length }} file(s) selected</span>
                            <Button :disabled="!selectedFiles.length" @click="confirmDownload"><Download class="mr-2 h-4 w-4" /> Download</Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Settings modal -->
            <div v-if="showSettings" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showSettings = false">
                <Card class="w-full max-w-sm">
                    <CardHeader class="flex flex-row items-center justify-between gap-2 pb-2">
                        <span class="flex items-center gap-2 font-medium"><Settings class="h-5 w-5 text-[#e2483d]" /> Settings</span>
                        <button class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="showSettings = false"><X class="h-4 w-4" /></button>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Ogury € rate</span>
                            <Input v-model.number="oguryRate" type="number" step="0.001" />
                        </label>
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Deliverables reminder day</span>
                            <select v-model.number="settingsDay" class="rounded-md border bg-background px-2 py-2 text-sm outline-none focus:ring-2 focus:ring-[#e2483d]/40">
                                <option v-for="(d, i) in DAY_NAMES" :key="i" :value="i">{{ d }}</option>
                            </select>
                            <span class="text-[11px] text-muted-foreground">The weekly deliverables reminder appears on this day.</span>
                        </label>
                        <div class="flex justify-end border-t pt-3">
                            <Button @click="saveSettings">Save</Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Adhese impressions batch modal — fires after process/sync when new F1 dates appear -->
            <div v-if="showAdheseModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <Card class="w-full max-w-md">
                    <CardHeader class="flex flex-row items-center justify-between gap-2 pb-2">
                        <span class="font-medium">Adhese impressions</span>
                        <button class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="showAdheseModal = false"><X class="h-4 w-4" /></button>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3">
                        <p class="text-sm text-muted-foreground">
                            {{ adheseEntries.length }} new day{{ adheseEntries.length === 1 ? '' : 's' }} added — enter the Adhese impression counts for F1Maximaal.
                        </p>
                        <div class="flex max-h-64 flex-col gap-2 overflow-y-auto">
                            <div v-for="entry in adheseEntries" :key="entry.dateKey" class="flex items-center gap-3">
                                <span class="w-24 shrink-0 font-mono text-sm">{{ entry.dateKey }}</span>
                                <Input v-model.number="entry.adhese" type="number" placeholder="0" class="flex-1" />
                            </div>
                        </div>
                        <div class="flex items-center justify-between border-t pt-3">
                            <button class="text-sm text-muted-foreground hover:underline" @click="showAdheseModal = false">Skip</button>
                            <Button :disabled="adheseSaving" @click="saveAdheseBatch">
                                <Loader2 v-if="adheseSaving" class="mr-2 h-4 w-4 animate-spin" /> Save impressions
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Deliverables reminder (configured day only; minimizable, never closable) -->
            <!-- minimized pill -->
            <button v-if="isReminderDay && wnMinimized"
                class="fixed bottom-6 right-6 z-40 inline-flex items-center gap-2 rounded-full border border-l-4 border-l-[#e2483d] bg-card px-4 py-2 text-xs font-semibold shadow-2xl transition hover:bg-muted"
                @click="expandWednesday">
                <CalendarDays class="h-4 w-4 text-[#e2483d]" /> {{ reminderDayName }} deliverables
            </button>
            <!-- full card -->
            <div v-if="isReminderDay && !wnMinimized" class="fixed bottom-6 right-6 z-40 w-80 rounded-xl border border-l-4 border-l-[#e2483d] bg-card p-4 shadow-2xl">
                <div class="mb-1 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm font-bold"><CalendarDays class="h-4 w-4" /> {{ reminderDayName }} Deliverables — Week {{ weekNumber }}</div>
                    <button class="text-muted-foreground hover:text-foreground" aria-label="Minimize" title="Minimize" @click="minimizeWednesday"><Minus class="h-4 w-4" /></button>
                </div>
                <p class="mb-3 text-xs text-muted-foreground">The following weekly reports are due today:</p>
                <ol class="flex flex-col gap-2.5">
                    <li class="flex flex-col gap-0.5 border-l-2 pl-3">
                        <span class="text-xs font-semibold">Top Gear Weekly Report — Week {{ weekNumber }}</span>
                        <span class="font-mono text-[11px] text-muted-foreground">TG-revenue-report-week{{ weekNumber }}</span>
                    </li>
                    <li class="flex flex-col gap-0.5 border-l-2 pl-3">
                        <span class="text-xs font-semibold">Horses Weekly Report — Week {{ weekNumber }}</span>
                        <span class="font-mono text-[11px] text-muted-foreground">Horses-revenue-report-week{{ weekNumber }}</span>
                    </li>
                    <li class="flex flex-col gap-0.5 border-l-2 pl-3">
                        <span class="text-xs font-semibold">Festileaks Weekly Report — Week {{ weekNumber - 1 }} <span class="font-normal text-muted-foreground">(previous week)</span></span>
                        <span class="font-mono text-[11px] text-muted-foreground">Festileaks-revenue-report-week{{ weekNumber - 1 }}</span>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</template>
