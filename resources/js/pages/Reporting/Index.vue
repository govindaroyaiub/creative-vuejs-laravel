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
import { Upload, Download, AlertTriangle, CheckCircle2, XCircle, Loader2, CalendarDays, Coins, Eye, TrendingUp, Award, CalendarCheck, X, FileText, FileSpreadsheet, FileJson, ArrowLeft, ExternalLink, Plus, Link2, Mail, Copy, Trash2, RefreshCw, Circle, Settings, Minus, ChevronDown, Gauge, FileArchive, PackageCheck } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue';
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

// ─── Welcome splash ("Loading the reports…") — every page load, capped at 5s,
// dismissible by click/keypress/Esc. Purely cosmetic, no data dependency. ────
const SPLASH_STATUS_LINES = ['Fetching Adhese…', 'Crunching RPM…', 'Stacking partners…', 'Polishing pixels…'];
const showSplash = ref(true);
const splashStatusIndex = ref(0);
let splashTimeout: ReturnType<typeof setTimeout> | null = null;
let splashStatusInterval: ReturnType<typeof setInterval> | null = null;
function dismissSplash() {
    if (!showSplash.value) return;
    showSplash.value = false;
    if (splashTimeout) clearTimeout(splashTimeout);
    if (splashStatusInterval) clearInterval(splashStatusInterval);
    window.removeEventListener('keydown', onSplashKeydown);
}
function onSplashKeydown(e: KeyboardEvent) {
    dismissSplash();
    void e;
}
onMounted(() => {
    splashTimeout = setTimeout(dismissSplash, 5000);
    splashStatusInterval = setInterval(() => {
        splashStatusIndex.value = (splashStatusIndex.value + 1) % SPLASH_STATUS_LINES.length;
    }, 900);
    window.addEventListener('keydown', onSplashKeydown);
});
onBeforeUnmount(() => {
    if (splashTimeout) clearTimeout(splashTimeout);
    if (splashStatusInterval) clearInterval(splashStatusInterval);
    window.removeEventListener('keydown', onSplashKeydown);
});

// ─── Data sync (import the committed JSON snapshot into this machine's DB) ──────
const sync = computed<any>(() => (page.props.sync as any) ?? { available: false });
const syncing = ref(false);
function performSync() {
    router.post('/reporting/sync', {}, {
        preserveScroll: true, preserveState: true,
        onStart: () => (syncing.value = true),
        onFinish: () => (syncing.value = false),
        onSuccess: () => {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Reporting data updated', timer: 1500, showConfirmButton: false });
            // Prompt for any imported day left with Adhese revenue but no impressions.
            promptMissingAdhese();
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
onMounted(() => {
    // A pending sync takes priority (its own modal); otherwise surface any day
    // that has Adhese revenue but no impressions yet so it can be filled in.
    if (sync.value.available) confirmSync();
    else promptMissingAdhese();
});

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

// ─── Settings modal (Ogury rate + reminder day + upload file names) ─────────────
const showSettings = ref(false);
const settingsDay = ref(reminderDay.value);
// Editable upload-recognition patterns per partner (comma-separated substrings).
const FILE_PATTERN_FIELDS = [
    { key: 'teads', label: 'Teads' }, { key: 'ogury', label: 'Ogury' }, { key: 'gam', label: 'GAM' },
    { key: 'seedtag', label: 'SeedTag' }, { key: 'adform', label: 'Adform' },
    { key: 'showheroes', label: 'Showheroes' }, { key: 'analytics', label: 'Analytics' },
    { key: 'adhese', label: 'Adhese' }, { key: 'outbrain', label: 'Outbrain' },
    { key: 'preferreddeals', label: 'Preferred Deals' }, { key: 'gam_f1m', label: 'GAM F1M' },
];
const filePatterns = ref<Record<string, string>>({ ...((page.props.filePatterns as Record<string, string>) ?? {}) });
// When on, the Ogury export is converted to the legacy Report/Statistics layout in the ZIP download.
const oguryOldFormat = ref<boolean>(!!(page.props.oguryOldFormat as boolean));
function openSettings() {
    settingsDay.value = reminderDay.value;
    filePatterns.value = { ...((page.props.filePatterns as Record<string, string>) ?? {}) };
    oguryOldFormat.value = !!(page.props.oguryOldFormat as boolean);
    rpmAmber.value = (page.props.rpmAmber as number) ?? 7.5;
    rpmRed.value = (page.props.rpmRed as number) ?? 8;
    showSettings.value = true;
}
function saveSettings() {
    router.post('/reporting/config', { oguryRate: oguryRate.value, reminderDay: settingsDay.value, rpmAmber: rpmAmber.value, rpmRed: rpmRed.value, oguryOldFormat: oguryOldFormat.value, filePatterns: filePatterns.value }, {
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
type SubjectCtx = { dateStr: string; week: number; monthName: string; monthYear: number };
function subjectContext(): SubjectCtx {
    const now = new Date();
    const day = now.getDate();
    const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    return {
        dateStr: `${MONTH_NAMES[now.getMonth()]} ${day}${ordinalSuffix(day)} ${now.getFullYear()}`,
        week: isoWeekNumber(now),
        monthName: MONTH_NAMES[lastMonth.getMonth()] ?? '',
        monthYear: lastMonth.getFullYear(),
    };
}

// The report file to attach for each site (matches what's downloaded to
// Downloads/reports), so the exact filename can be copied when composing the email.
type EmailCfg = { to: { name: string; email: string }[]; cc?: { name: string; email: string }[]; body: string; bodyHtml?: string; subject: (c: SubjectCtx) => string; attachment: (c: SubjectCtx) => string };
const SITE_EMAILS: Record<string, EmailCfg> = {
    f1maximaal: {
        to: [
            { name: 'Martijn van der Spek (TopGear Nederland)', email: 'martijn@topgear.nl' },
            { name: 'Robert Heijmans (VDS Publishers)', email: 'robert@vds-publishers.nl' },
        ],
        cc: [{ name: 'Chris Beijer', email: 'chris@planetnine.com' }, { name: 'Taco Stomps', email: 'taco@planetnine.com' }],
        body: `Hi everyone,\n\nPlease find the attached performance report for F1Maximaal.\n\nBest regards,`,
        subject: (c) => `F1Maximaal Revenue Report - ${c.dateStr}`,
        attachment: (c) => `Planetnine-Report-f1maximaal.nl-${c.monthName}-${c.monthYear}.xlsx`,
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
        attachment: (c) => `TG-revenue-report-week${c.week}.xlsx`,
    },
    horses: {
        to: [
            { name: '', email: 'a.vanbel@eisma.nl' }, { name: '', email: 'J.vanderMolen@eisma.nl' },
            { name: '', email: 'c.haentjens@eisma.nl' }, { name: '', email: 'M.Matser@eisma.nl' },
        ],
        cc: [{ name: 'Chris Beijer', email: 'chris@planetnine.com' }, { name: 'Taco Stomps', email: 'taco@planetnine.com' }],
        body: `Dear All,\n\nHope you are doing well!\n\nPlease find this week's revenue reports for Horses.nl attached. Should you have any questions, please reach out.\n\nBest regards`,
        subject: (c) => `Horses.nl - Ad Revenue Reports - Week ${c.week}`,
        attachment: (c) => `Horses-revenue-report-week${c.week}.xlsx`,
    },
    festileaks: {
        to: [{ name: '', email: 'finance@festileaks.com' }, { name: 'Jos Willemsen', email: 'jos@festileaks.com' }],
        cc: [{ name: 'Chris Beijer', email: 'chris@planetnine.com' }, { name: 'Taco Stomps', email: 'taco@planetnine.com' }],
        body: `Dear Jos,\n\nHope you are doing well!\n\nPlease find this week's Festileaks revenue reports attached. Should you have any questions, please reach out.\n\nThe files are also uploaded to this drive link: https://drive.google.com/drive/folders/1Nqwyg5jnMT986bmUewcyQMGsJIAjWEOE?usp=drive_link\n\nBest regards`,
        bodyHtml: `<p>Dear Jos,</p><p>Hope you are doing well!</p><p>Please find this week's Festileaks revenue reports attached. Should you have any questions, please reach out.</p><p>The files are also uploaded to this drive link: <a href="https://drive.google.com/drive/folders/1Nqwyg5jnMT986bmUewcyQMGsJIAjWEOE?usp=drive_link">Link Here</a></p><p>Best regards</p>`,
        subject: (c) => `Festileaks Ad Revenue Reports - Week ${c.week - 1}`,
        attachment: (c) => `Festileaks-revenue-report-week${c.week - 1}.xlsx`,
    },
};

const emailCfg = computed<EmailCfg | null>(() => SITE_EMAILS[selectedSite.value] ?? null);
const emailSubject = computed(() => (emailCfg.value ? emailCfg.value.subject(subjectContext()) : ''));
const emailAttachment = computed(() => (emailCfg.value ? emailCfg.value.attachment(subjectContext()) : ''));
function copy(text: string) {
    navigator.clipboard?.writeText(text);
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Copied', timer: 1000, showConfirmButton: false });
}

// Copy the message with both plain-text and rich-HTML flavours. When pasted
// into Gmail (rich compose) the HTML wins so "Link Here" stays clickable;
// plain-text targets fall back to the bare URL, which Gmail auto-links anyway.
async function copyBody(cfg: EmailCfg) {
    const done = () => Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Copied', timer: 1000, showConfirmButton: false });
    const html = cfg.bodyHtml;
    if (html && typeof ClipboardItem !== 'undefined' && navigator.clipboard?.write) {
        try {
            await navigator.clipboard.write([new ClipboardItem({
                'text/html': new Blob([html], { type: 'text/html' }),
                'text/plain': new Blob([cfg.body], { type: 'text/plain' }),
            })]);
            done();
            return;
        } catch {
            // fall through to plain-text copy below
        }
    }
    navigator.clipboard?.writeText(cfg.body);
    done();
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
watch(selectedSite, () => {
    // Verify is per-site — clear the staged file + widget so a report uploaded
    // for one site doesn't linger when switching to another.
    verifyFile.value = null;
    verifyPond.value?.removeFiles();
});
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
const PRESETS = ['Last 7 days', 'This month', 'Last month', 'All'] as const;
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

// RPM (revenue per 1000 pageviews). A high RPM means analytics pageviews are
// under-reported (incomplete/late-finalized GA4 data) — the day's analytics file
// likely needs re-uploading. F1Maximaal only; other sites carry no pageviews.
// Editable in Settings; default to amber >=7.5, red >=8. Reactive so the table
// re-tints immediately after the thresholds are saved.
const rpmAmber = ref<number>((page.props.rpmAmber as number) ?? 7.5);
const rpmRed = ref<number>((page.props.rpmRed as number) ?? 8);
const dayRevenue = (d: any) => PARTNERS.reduce((t, p) => t + (d.revenue?.[p.key] ?? 0), 0);
const rpmFor = (d: any): number | null => {
    const views = d.analytics?.views ?? 0;
    return views > 0 ? (dayRevenue(d) / views) * 1000 : null;
};
const rpmTier = (d: any): 'red' | 'amber' | null => {
    // Only F1Maximaal carries pageviews; on other sites "revenue but no views"
    // is the normal state, not an anomaly — never tint their rows.
    if (selectedSite.value !== 'f1maximaal') return null;
    const rpm = rpmFor(d);
    // Missing/zero pageviews on a day that has revenue is itself the "re-upload
    // analytics" signal (RPM is effectively infinite) — flag red.
    if (rpm === null) return dayRevenue(d) > 0 ? 'red' : null;
    if (rpm >= rpmRed.value) return 'red';
    if (rpm >= rpmAmber.value) return 'amber';
    return null;
};
// Click-to-highlight on the Table tab — purely visual, no selection semantics
// elsewhere on the page. Re-clicking the same row clears it.
const selectedRow = ref<string | null>(null);
function toggleRow(dateKey: string) {
    selectedRow.value = selectedRow.value === dateKey ? null : dateKey;
}
const rpmRowClass = (d: any) => {
    const t = rpmTier(d);
    return t === 'red' ? 'bg-red-500/10' : t === 'amber' ? 'bg-amber-400/10' : '';
};
// Same banding as the table rows (border + soft fill) — used on the Summary
// tab's "Latest day" block so anomalies stand out there too.
const rpmCardClass = (d: any) => {
    const t = rpmTier(d);
    return t === 'red' ? 'border-red-500/40 bg-red-500/5' : t === 'amber' ? 'border-amber-400/40 bg-amber-400/5' : '';
};
// Most recent day in the active date-range filter — backs the Summary tab's
// "Latest day" block (F1Maximaal only; same fields Days used to show).
const latestDay = computed<any | null>(() => days.value[days.value.length - 1] ?? null);
const blendedRpm = computed<number | null>(() => {
    const views = days.value.reduce((t, d) => t + (d.analytics?.views ?? 0), 0);
    return views > 0 ? (partnerTotals.value.grand / views) * 1000 : null;
});
// Same amber/red banding as the table rows, applied to the range-wide blended
// RPM so the Summary KPI card signals when the period as a whole looks off.
const blendedRpmTier = computed<'red' | 'amber' | null>(() => {
    if (blendedRpm.value === null) return null;
    if (blendedRpm.value >= rpmRed.value) return 'red';
    if (blendedRpm.value >= rpmAmber.value) return 'amber';
    return null;
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
    // No draw-in animation: charts live under v-show, so Chart.js would replay
    // its entry animation every time the Summary tab is revealed.
    animation: false as const,
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
    animation: false as const,
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
// A file matches a partner when its name contains any of the (comma-separated,
// user-editable) needles configured for that partner in Settings.
function matchesPattern(name: string, csv: string | undefined): boolean {
    return (csv ?? '').split(',').map((s) => s.trim().toLowerCase()).some((nd) => nd !== '' && name.includes(nd));
}
function detectType(filename: string): string {
    const n = filename.toLowerCase();
    const p = filePatterns.value;
    if (matchesPattern(n, p.adhese)) return 'adhese';
    if (matchesPattern(n, p.analytics)) return 'analytics';
    if (matchesPattern(n, p.adform)) return 'adform';
    if (matchesPattern(n, p.gam)) return 'gam';
    if (matchesPattern(n, p.ogury)) return 'ogury';
    if (matchesPattern(n, p.seedtag)) return 'seedtag';
    if (matchesPattern(n, p.showheroes)) return 'showheroes';
    if (matchesPattern(n, p.teads)) return 'teads';
    if (matchesPattern(n, p.outbrain)) return 'outbrain';
    if (n.startsWith('impressions') && n.includes('f1')) return 'impressions_f1';
    if (matchesPattern(n, p.preferreddeals)) return 'preferreddeals';
    if (matchesPattern(n, p.gam_f1m)) return 'gam_f1m';
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

// ─── Adhese impressions gaps ─────────────────────────────────────────────────
// Adhese impressions are entered by hand (never read from a file), so a day can
// end up with Adhese *revenue* but no impressions — e.g. the day already existed
// (from another partner or a sync) when its Adhese revenue arrived, so the old
// new-dates-only prompt never fired for it. Surface EVERY such gap, not just
// brand-new dates, so days like these can't silently stay blank.
const missingAdhese = computed<any[]>(() =>
    selectedSite.value === 'f1maximaal'
        ? days.value.filter((d: any) => (d.revenue?.adhese ?? 0) > 0 && d.impressions?.adhese == null)
        : []);

function promptMissingAdhese(): boolean {
    if (!missingAdhese.value.length) return false;
    adheseEntries.value = missingAdhese.value.map((d: any) => ({ dateKey: d.dateKey, adhese: null }));
    showAdheseModal.value = true;
    return true;
}
// Manual entry point behind the "Fill now" banner button.
const openAdheseGaps = promptMissingAdhese;

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
    const fd = new FormData();
    selectedUploadFiles.value.forEach((f) => fd.append('files[]', f, f.name));
    router.post('/reporting/process', fd, {
        forceFormData: true, preserveScroll: true, preserveState: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
        onSuccess: () => {
            selectedUploadFiles.value = [];
            // Prompt for any day left with Adhese revenue but no impressions —
            // covers brand-new dates and any that slipped through earlier.
            promptMissingAdhese();
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
    dlState.value = 'idle';
    showDownload.value = true;
}
// Zip-preparing state: fetch+stream (instead of a bare location.href) so we can show
// real progress once bytes start arriving, and an honest "still working" animation
// for the server-side build time before the first byte shows up.
const dlState = ref<'idle' | 'working' | 'done' | 'error'>('idle');
const dlProgress = ref(0);       // 0-100, only meaningful once dlKnownSize is true
const dlKnownSize = ref(false);
const dlPhase = ref('');
const dlError = ref('');
let dlAbort: AbortController | null = null;
let dlPhaseTimer: ReturnType<typeof setInterval> | null = null;
const DL_PHASES = ['Gathering files…', 'Compressing…', 'Packaging zip…', 'Almost done…'];

function stopPhaseTimer() {
    if (dlPhaseTimer) { clearInterval(dlPhaseTimer); dlPhaseTimer = null; }
}
function cancelDownload() {
    dlAbort?.abort();
}
async function confirmDownload() {
    const params = new URLSearchParams();
    if (dlFrom.value) params.set('from', dlFrom.value);
    if (dlTo.value) params.set('to', dlTo.value);
    if (selectedFiles.value.length) params.set('files', selectedFiles.value.join(','));

    dlState.value = 'working';
    dlProgress.value = 0;
    dlKnownSize.value = false;
    let phaseIdx = 0;
    dlPhase.value = DL_PHASES[0] ?? '';
    stopPhaseTimer();
    dlPhaseTimer = setInterval(() => {
        phaseIdx = Math.min(phaseIdx + 1, DL_PHASES.length - 1);
        dlPhase.value = DL_PHASES[phaseIdx] ?? dlPhase.value;
    }, 900);

    dlAbort = new AbortController();
    try {
        const res = await fetch('/reporting/download?' + params.toString(), { signal: dlAbort.signal });
        if (!res.ok) {
            const body = await res.json().catch(() => null);
            throw new Error(body?.error ?? 'Could not build the zip.');
        }

        const total = Number(res.headers.get('Content-Length')) || 0;
        dlKnownSize.value = total > 0;
        const reader = res.body?.getReader();
        const chunks: BlobPart[] = [];
        let received = 0;
        if (reader) {
            stopPhaseTimer();
            dlPhase.value = 'Downloading…';
            for (;;) {
                const { done, value } = await reader.read();
                if (done) break;
                chunks.push(value);
                received += value.length;
                if (total > 0) dlProgress.value = Math.min(100, Math.round((received / total) * 100));
            }
        }
        stopPhaseTimer();
        dlProgress.value = 100;
        dlPhase.value = 'Ready!';

        const blob = new Blob(chunks, { type: 'application/zip' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'F1Maximaal Reports.zip';
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 4000);

        dlState.value = 'done';
        setTimeout(() => { showDownload.value = false; dlState.value = 'idle'; }, 900);
    } catch (e: any) {
        stopPhaseTimer();
        if (e?.name === 'AbortError') { dlState.value = 'idle'; return; }
        dlState.value = 'error';
        dlError.value = e?.message ?? 'Something went wrong building the zip.';
    }
}
// Export the dashboard table itself (current site + date range) as csv/xlsx/json.
// Distinct from the file download above — this is the computed daily figures.
const showExportMenu = ref(false);
function exportTable(format: 'csv' | 'xlsx' | 'json') {
    showExportMenu.value = false;
    const params = new URLSearchParams({ site: selectedSite.value, format });
    if (from.value) params.set('from', from.value);
    if (to.value) params.set('to', to.value);
    window.location.href = '/reporting/export-table?' + params.toString();
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
        onSuccess: () => {
            // Rendered inline (preserveState — no remount, no splash replay, no
            // tab change), but Inertia points the address bar at this POST-only
            // URL. Fix it up in place so a refresh hits GET /reporting instead
            // of 405ing on /reporting/verify(-weekly).
            window.history.replaceState(window.history.state, '', '/reporting');
        },
    });
}

const tabs = [
    { id: 'summary', label: 'Summary' }, { id: 'table', label: 'Table' },
    { id: 'verify', label: 'Verify' }, { id: 'email', label: 'Email' },
];
</script>

<template>
    <Head title="Reporting" />
    <div class="rpt-root relative min-h-screen">
        <!-- Welcome splash — cosmetic only, dismiss on click/key/5s timeout. -->
        <Transition name="rpt-splash-fade">
            <div v-if="showSplash" class="rpt-splash" @click="dismissSplash">
                <div aria-hidden="true" class="rpt-ambient" />
                <div aria-hidden="true" class="rpt-stars rpt-stars--always" />
                <div class="rpt-splash-content">
                    <div class="rpt-splash-mark">
                        <span class="rpt-splash-ring" />
                        <span class="rpt-splash-mark-text">P9</span>
                    </div>
                    <h1 class="rpt-splash-title">Loading the reports<span class="rpt-splash-dots"><span>.</span><span>.</span><span>.</span></span></h1>
                    <p class="rpt-splash-status">{{ SPLASH_STATUS_LINES[splashStatusIndex] }}</p>
                    <div class="rpt-splash-bar"><div class="rpt-splash-bar-fill" /></div>
                    <button type="button" class="rpt-splash-skip" @click.stop="dismissSplash">Skip →</button>
                </div>
            </div>
        </Transition>

        <!-- Decorative ambient color wash + starfield (dark mode only), same
             technique as the Previews/Update2 editor's chrome. -->
        <div aria-hidden="true" class="rpt-ambient" />
        <div aria-hidden="true" class="rpt-stars" />
        <!-- Standalone top bar (no app sidebar) with a way back -->
        <header class="sticky top-0 z-40 border-b border-[var(--rpt-hairline)] bg-[var(--rpt-surface-muted)] backdrop-blur">
            <div class="flex items-center justify-between gap-3 px-6 py-3">
                <div class="flex items-center gap-3">
                    <Link href="/dashboard" title="Back to dashboard" aria-label="Back"
                        class="grid h-9 w-9 place-items-center rounded-full border border-[var(--rpt-border)] text-muted-foreground transition hover:text-foreground">
                        <ArrowLeft class="h-4 w-4" />
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

        <div class="relative z-[1] flex w-full flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold">Reporting</h1>
                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium tabular-nums">Week {{ weekNumber }}</span>
                    </div>
                    <p class="text-sm text-muted-foreground">Ad-partner impressions & revenue across the four sites.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <Button class="rounded-full" variant="outline" :disabled="syncing" @click="checkForData">
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': syncing }" /> Check for data
                    </Button>
                    <Button class="rounded-full" variant="outline" @click="showLinks = true"><Link2 class="mr-2 h-4 w-4" /> Report links</Button>
                    <Button class="rounded-full" variant="outline" @click="openDownload"><Download class="mr-2 h-4 w-4" /> Download Reports</Button>
                    <Button class="rounded-full" variant="outline" @click="openSettings"><Settings class="mr-2 h-4 w-4" /> Settings</Button>
                </div>
            </div>

            <!-- Data migration banner -->
            <div v-if="syncing" class="flex items-center gap-3 rounded-xl border border-[#e2483d]/40 bg-[#e2483d]/5 p-4 text-sm">
                <Loader2 class="h-5 w-5 shrink-0 animate-spin text-[#e2483d]" />
                <div><span class="font-medium">New data found — migration in progress.</span> Please wait…</div>
            </div>

            <!-- Upload card: checklist drop zone -->
            <Card class="rpt-glass">
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
                        <Button class="rounded-full" :disabled="processing" @click="processFiles">
                            <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                            Process {{ selectedUploadFiles.length }} file{{ selectedUploadFiles.length === 1 ? '' : 's' }}
                        </Button>
                        <button class="text-xs text-muted-foreground hover:underline" @click="selectedUploadFiles = []">Clear</button>
                    </div>
                </CardContent>
            </Card>

            <!-- Site selector + tabs -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-1 rounded-full border p-1">
                    <button v-for="s in sites" :key="s.id"
                        class="rounded-full px-3 py-1.5 text-sm transition"
                        :class="selectedSite === s.id ? 'bg-[#e2483d] text-white' : 'hover:bg-muted'"
                        @click="selectedSite = s.id">{{ s.name }}</button>
                </div>
                <div class="flex gap-1 rounded-full border p-1">
                    <button v-for="t in tabs" :key="t.id"
                        class="rounded-full px-3 py-1.5 text-sm transition"
                        :class="activeTab === t.id ? 'bg-foreground text-background' : 'hover:bg-muted'"
                        @click="activeTab = t.id">{{ t.label }}</button>
                </div>
            </div>

            <!-- Date range filter -->
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 rounded-xl border bg-card p-2">
                <!-- preset pills -->
                <div class="flex flex-wrap items-center gap-1 rounded-full bg-muted/50 p-1">
                    <button v-for="p in PRESETS" :key="p"
                        class="rounded-full px-3 py-1.5 text-xs font-medium transition"
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
            <Card v-if="anomalies.length && activeTab === 'summary'" class="rpt-glass border-amber-500/40">
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
                <span v-if="sync.syncedAt" class="rounded-full bg-[#e2483d]/10 px-2.5 py-1 font-semibold text-[#e2483d]">Data snapshot: {{ sync.syncedAt }}</span>
                <span v-else class="rounded-full bg-[#e2483d]/10 px-2.5 py-1 font-semibold text-[#e2483d]">No data snapshot imported yet.</span>
                <button v-if="sync.available" class="rounded-full bg-[#e2483d]/10 px-2 py-0.5 font-medium text-[#e2483d] hover:bg-[#e2483d]/20" @click="confirmSync">
                    {{ sync.pending }} day{{ sync.pending === 1 ? '' : 's' }} ready to sync
                </button>
            </div>

            <!-- SUMMARY -->
            <div v-show="activeTab === 'summary'" class="flex flex-col gap-4">
                <!-- KPI cards. Grid (not flex-row) so cards wrap cleanly at every
                     width instead of being squeezed into one non-wrapping row. -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card class="rpt-glass">
                        <CardContent class="flex items-center gap-2.5 pt-5">
                            <div class="shrink-0 rounded-lg bg-[#e2483d]/10 p-2 text-[#e2483d]"><Coins class="h-5 w-5" /></div>
                            <div class="min-w-0"><div class="rpt-label">Total revenue</div><div class="truncate text-base font-semibold tracking-tight">{{ eur(partnerTotals.grand) }}</div></div>
                        </CardContent>
                    </Card>
                    <Card v-if="selectedSite === 'f1maximaal'" class="rpt-glass">
                        <CardContent class="flex items-center gap-2.5 pt-5">
                            <div class="shrink-0 rounded-lg bg-blue-500/10 p-2 text-blue-500"><Eye class="h-5 w-5" /></div>
                            <div class="min-w-0"><div class="rpt-label">Impressions</div><div class="truncate text-base font-semibold tracking-tight">{{ num(impressionsSoldTotal) }}</div></div>
                        </CardContent>
                    </Card>
                    <Card v-if="selectedSite === 'f1maximaal'" class="rpt-glass">
                        <CardContent class="flex items-center gap-2.5 pt-5">
                            <div class="shrink-0 rounded-lg p-2"
                                :class="blendedRpmTier === 'red' ? 'bg-red-500/10 text-red-500' : blendedRpmTier === 'amber' ? 'bg-amber-500/10 text-amber-500' : 'bg-cyan-500/10 text-cyan-500'">
                                <Gauge class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="rpt-label">RPM</div>
                                <div class="truncate text-base font-semibold tracking-tight">{{ blendedRpm === null ? '—' : blendedRpm.toFixed(2) }}</div>
                                <div class="truncate text-[11px] text-muted-foreground">€ / 1k pageviews</div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="rpt-glass">
                        <CardContent class="flex items-center gap-2.5 pt-5">
                            <div class="shrink-0 rounded-lg bg-emerald-500/10 p-2 text-emerald-500"><TrendingUp class="h-5 w-5" /></div>
                            <div class="min-w-0"><div class="rpt-label">Avg / day</div><div class="truncate text-base font-semibold tracking-tight">{{ eur(avgDaily) }}</div></div>
                        </CardContent>
                    </Card>
                    <Card class="rpt-glass">
                        <CardContent class="flex items-center gap-2.5 pt-5">
                            <div class="shrink-0 rounded-lg bg-amber-500/10 p-2 text-amber-500"><Award class="h-5 w-5" /></div>
                            <div class="min-w-0"><div class="rpt-label">Top partner</div><div class="truncate text-base font-semibold tracking-tight">{{ topPartner?.label ?? '—' }}</div></div>
                        </CardContent>
                    </Card>
                    <Card class="rpt-glass">
                        <CardContent class="flex items-center gap-2.5 pt-5">
                            <div class="shrink-0 rounded-lg bg-violet-500/10 p-2 text-violet-500"><CalendarCheck class="h-5 w-5" /></div>
                            <div class="min-w-0">
                                <div class="rpt-label">Best day</div>
                                <div class="truncate text-base font-semibold tracking-tight">{{ bestDay ? eur(bestDay.total) : '—' }}</div>
                                <div v-if="bestDay" class="truncate text-[11px] text-muted-foreground">{{ bestDay.dateKey }} · {{ bestDay.partner }}</div>
                            </div>
                        </CardContent>
                    </Card>
                    <!-- Latest day — carried over from the old Days tab. F1Maximaal only
                         (Adhese impressions / Impr. sold / Ad requests don't exist for other
                         sites); moves with the active date-range preset like the rest of Summary. -->
                    <Card v-if="selectedSite === 'f1maximaal' && latestDay" class="rpt-glass sm:col-span-2" :class="rpmCardClass(latestDay)">
                        <CardContent class="flex flex-col gap-2 pt-5">
                            <div class="rpt-label">Latest day · {{ latestDay.dateKey }}</div>
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="text-muted-foreground">Adhese impr.</span>
                                <Input v-model.number="latestDay.impressions.adhese" type="number"
                                    :class="['h-7 w-24 px-1.5 text-right text-[11px] leading-none', (latestDay.revenue?.adhese ?? 0) > 0 && latestDay.impressions?.adhese == null ? 'ring-1 ring-amber-400 focus-visible:ring-amber-400' : '']"
                                    @change="saveAdhese(latestDay)" />
                            </div>
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="text-muted-foreground">Impr. sold</span>
                                <span class="font-medium tabular-nums">{{ num(latestDay.impressionsSold || 0) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="text-muted-foreground">Ad requests</span>
                                <span class="font-medium tabular-nums">{{ num(latestDay.totalAdRequests || 0) }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Charts -->
                <div class="grid gap-4 lg:grid-cols-3">
                    <Card class="rpt-glass lg:col-span-2">
                        <CardHeader class="pb-2 font-medium">Revenue trend</CardHeader>
                        <CardContent><div class="h-72"><Line :data="revenueChart" :options="chartOptions" /></div></CardContent>
                    </Card>
                    <Card class="rpt-glass">
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
            <Card class="rpt-glass" v-show="activeTab === 'table'">
                <CardContent class="overflow-x-auto pt-6">
                    <!-- Export the table data itself (current site + date range).
                         Separate from the file "Download" — this is the computed figures. -->
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <div class="text-xs text-muted-foreground">
                            {{ days.length }} day{{ days.length === 1 ? '' : 's' }}<span v-if="from || to"> · {{ from || '…' }} → {{ to || '…' }}</span>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button class="rounded-full" variant="outline" size="sm" :disabled="!days.length">
                                    <Download class="mr-2 h-4 w-4" /> Export <ChevronDown class="ml-1 h-3.5 w-3.5" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="min-w-44">
                                <button class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition hover:bg-muted" @click="exportTable('xlsx')"><FileSpreadsheet class="h-4 w-4 text-emerald-600" /> Excel (.xlsx)</button>
                                <button class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition hover:bg-muted" @click="exportTable('csv')"><FileText class="h-4 w-4 text-blue-600" /> CSV (.csv)</button>
                                <button class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition hover:bg-muted" @click="exportTable('json')"><FileJson class="h-4 w-4 text-amber-600" /> JSON (.json)</button>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                    <!-- Surfaces days that have Adhese revenue but no impressions yet
                         (impressions are entered by hand) so a gap can't stay silent. -->
                    <div v-if="missingAdhese.length" class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-700/60 dark:bg-amber-950/40 dark:text-amber-200">
                        <span>{{ missingAdhese.length }} day{{ missingAdhese.length === 1 ? '' : 's' }} in this range {{ missingAdhese.length === 1 ? 'has' : 'have' }} Adhese revenue but no impressions: {{ missingAdhese.map((d) => d.dateKey).join(', ') }}.</span>
                        <button class="shrink-0 rounded-md border border-amber-400 px-2 py-1 font-medium transition hover:bg-amber-100 dark:hover:bg-amber-900/40" @click="openAdheseGaps">Fill now</button>
                    </div>
                    <table class="w-full whitespace-nowrap text-xs">
                        <thead>
                            <tr class="rpt-label border-b text-left">
                                <th class="px-1.5 py-2">Date</th>
                                <th v-for="p in PARTNERS" :key="p.key" class="px-1.5 py-2 text-right leading-tight">
                                    <template v-if="p.lines">
                                        {{ p.lines[0] }}<br>{{ p.lines[1] }}
                                    </template>
                                    <template v-else>{{ p.label }}</template>
                                </th>
                                <th class="px-1.5 py-2 text-right">Total</th>
                                <th v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-2 text-right">RPM</th>
                                <th v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-2 text-right leading-tight">Adhese<br>impr.</th>
                                <th v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-2 text-right leading-tight">Impr.<br>sold</th>
                                <th v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-2 text-right leading-tight">Ad<br>requests</th>
                                <th class="px-1.5 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in days" :key="d.dateKey" class="cursor-pointer border-b last:border-0"
                                :class="[rpmRowClass(d), selectedRow === d.dateKey ? 'rpt-row-selected' : '']"
                                @click="toggleRow(d.dateKey)">
                                <td class="px-1.5 py-1 font-medium">{{ d.dateKey }}</td>
                                <td v-for="p in PARTNERS" :key="p.key" class="px-1.5 py-1 text-right" :class="{ 'text-muted-foreground': !(d.revenue?.[p.key]) }">
                                    {{ (d.revenue?.[p.key] ?? 0).toFixed(2) }}
                                </td>
                                <td class="px-1.5 py-1 text-right font-semibold">
                                    {{ PARTNERS.reduce((t, p) => t + (d.revenue?.[p.key] ?? 0), 0).toFixed(2) }}
                                </td>
                                <td v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-1 text-right tabular-nums">
                                    {{ rpmFor(d) === null ? '—' : rpmFor(d)!.toFixed(2) }}
                                </td>
                                <td v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-1 text-right">
                                    <Input v-model.number="d.impressions.adhese" type="number" :class="['ml-auto h-7 w-24 px-1.5 text-right text-[11px] leading-none', (d.revenue?.adhese ?? 0) > 0 && d.impressions?.adhese == null ? 'ring-1 ring-amber-400 focus-visible:ring-amber-400' : '']" @change="saveAdhese(d)" />
                                </td>
                                <td v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-1 text-right">{{ num(d.impressionsSold || 0) }}</td>
                                <td v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-1 text-right">{{ num(d.totalAdRequests || 0) }}</td>
                                <td class="px-1.5 py-1 text-right">
                                    <button class="text-muted-foreground transition hover:text-red-500" title="Delete day" @click.stop="deleteDay(d)"><Trash2 class="h-3.5 w-3.5" /></button>
                                </td>
                            </tr>
                            <!-- Totals row -->
                            <tr v-if="days.length" class="border-t-2 bg-muted/30 font-semibold">
                                <td class="px-1.5 py-1.5 text-xs uppercase tracking-wide text-muted-foreground">Total</td>
                                <td v-for="p in PARTNERS" :key="p.key" class="px-1.5 py-1.5 text-right">
                                    {{ partnerTotals.totals[p.key].toFixed(2) }}
                                </td>
                                <td class="px-1.5 py-1.5 text-right">{{ partnerTotals.grand.toFixed(2) }}</td>
                                <td v-if="selectedSite === 'f1maximaal'" class="px-1.5 py-1.5 text-right tabular-nums">{{ blendedRpm === null ? '—' : blendedRpm.toFixed(2) }}</td>
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
            <Card class="rpt-glass" v-show="activeTab === 'verify'">
                <CardHeader class="pb-2 font-medium">
                    Verify {{ selectedSite === 'f1maximaal' ? 'monthly' : 'weekly' }} report — {{ sites.find((s) => s.id === selectedSite)?.name }}
                </CardHeader>
                <CardContent class="flex flex-col gap-3">
                    <FilePond ref="verifyPond" :allow-multiple="false" :instant-upload="false" label-idle="Drop the Planetnine report here" @updatefiles="onVerifyUpdate" />
                    <div v-if="verifyFile">
                        <Button class="rounded-full" :disabled="verifying" @click="runVerify"><Loader2 v-if="verifying" class="mr-2 h-4 w-4 animate-spin" /> Run verification</Button>
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
                                        <tr class="rpt-label border-b bg-muted/40 text-left">
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
            <Card class="rpt-glass" v-show="activeTab === 'email'">
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
                    <!-- Attachment -->
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Attachment</span>
                            <button class="text-xs text-[#e2483d] hover:underline" @click="copy(emailAttachment)">Copy</button>
                        </div>
                        <button type="button"
                            class="flex items-center gap-2 rounded-md border px-3 py-2 text-left font-mono text-xs transition hover:bg-[#e2483d]/10 hover:text-[#e2483d]"
                            :title="`Copy ${emailAttachment}`" @click="copy(emailAttachment)">
                            <FileText class="h-4 w-4 shrink-0 opacity-60" />
                            <span class="min-w-0 break-all">{{ emailAttachment }}</span>
                        </button>
                    </div>
                    <!-- Message -->
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Message</span>
                            <button class="text-xs text-[#e2483d] hover:underline" @click="copyBody(emailCfg)">Copy</button>
                        </div>
                        <pre class="whitespace-pre-wrap rounded-md border px-3 py-2 font-sans text-sm">{{ emailCfg.body }}</pre>
                    </div>
                </CardContent>
            </Card>

            <!-- Report links modal -->
            <div v-if="showLinks" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showLinks = false">
                <Card class="rpt-glass w-full max-w-lg">
                    <CardHeader class="flex flex-row items-center justify-between gap-2 pb-2">
                        <div class="flex items-center gap-2"><Link2 class="h-5 w-5 text-[#e2483d]" /><span class="font-medium">Report sources</span></div>
                        <div class="flex items-center gap-2">
                            <Button class="rounded-full" variant="outline" size="sm" @click="showAddLink = !showAddLink"><Plus class="mr-1 h-4 w-4" /> Add</Button>
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
                                <Button class="rounded-full" size="sm" @click="addLink">Add</Button>
                                <Button class="rounded-full" size="sm" variant="ghost" @click="showAddLink = false">Cancel</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Download modal -->
            <div v-if="showDownload" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="dlState !== 'working' && (showDownload = false)">
                <Card class="rpt-glass w-full max-w-2xl">
                    <CardHeader class="flex flex-row items-center justify-between gap-2 pb-3">
                        <span class="flex items-center gap-2 font-medium"><Download class="h-5 w-5 text-[#e2483d]" /> Download reports</span>
                        <button v-if="dlState !== 'working'" class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" title="Close" @click="showDownload = false"><X class="h-4 w-4" /></button>
                    </CardHeader>

                    <!-- Zip build/download in flight, or its outcome -->
                    <CardContent v-if="dlState !== 'idle'" class="flex flex-col items-center gap-5 py-10 text-center">
                        <template v-if="dlState === 'working'">
                            <div class="relative flex h-16 w-16 items-center justify-center">
                                <span class="absolute inset-0 rounded-full bg-[#e2483d]/15 zip-ping"></span>
                                <FileArchive class="relative h-9 w-9 text-[#e2483d] zip-bob" />
                            </div>
                            <Transition name="zip-phase" mode="out-in">
                                <p :key="dlPhase" class="text-sm font-medium">{{ dlPhase }}</p>
                            </Transition>
                            <div class="h-2 w-full max-w-xs overflow-hidden rounded-full bg-muted">
                                <div v-if="dlKnownSize" class="h-full rounded-full bg-[#e2483d] transition-[width] duration-200 ease-out"
                                    :style="{ width: dlProgress + '%' }"></div>
                                <div v-else class="h-full w-full rounded-full zip-stripes"></div>
                            </div>
                            <span v-if="dlKnownSize" class="-mt-3 text-xs tabular-nums text-muted-foreground">{{ dlProgress }}%</span>
                            <button class="text-xs text-muted-foreground hover:text-foreground hover:underline" @click="cancelDownload">Cancel</button>
                        </template>

                        <template v-else-if="dlState === 'done'">
                            <PackageCheck class="h-14 w-14 text-emerald-500 zip-pop" />
                            <p class="text-sm font-medium">Zip ready — download started.</p>
                        </template>

                        <template v-else>
                            <AlertTriangle class="h-14 w-14 text-red-500" />
                            <p class="max-w-sm text-sm font-medium">{{ dlError }}</p>
                            <Button class="rounded-full" variant="outline" @click="dlState = 'idle'">Try again</Button>
                        </template>
                    </CardContent>

                    <!-- File + date-range picker -->
                    <CardContent v-else class="flex flex-col gap-4">
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
                                        class="rounded-full border px-2.5 py-1 text-xs transition hover:bg-muted" @click="dlPreset(p)">{{ p }}</button>
                                </div>
                                <DateRangePicker v-model:from="dlFrom" v-model:to="dlTo" class="mt-1" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t pt-3">
                            <span class="text-xs text-muted-foreground">{{ selectedFiles.length }} of {{ uploadFiles.length }} file(s) selected</span>
                            <Button class="rounded-full" :disabled="!selectedFiles.length" @click="confirmDownload"><Download class="mr-2 h-4 w-4" /> Download</Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Settings modal -->
            <div v-if="showSettings" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showSettings = false">
                <Card class="rpt-glass flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden p-0">
                    <div class="flex items-center justify-between gap-2 border-b px-6 py-4">
                        <span class="flex items-center gap-2 font-medium"><Settings class="h-5 w-5 text-[#e2483d]" /> Settings</span>
                        <button class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="showSettings = false"><X class="h-4 w-4" /></button>
                    </div>

                    <div class="flex flex-1 flex-col gap-6 overflow-y-auto px-6 py-5">
                        <!-- General -->
                        <section class="flex flex-col gap-3">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">General</h3>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="flex flex-col gap-1">
                                    <span class="text-sm font-medium">Ogury € rate</span>
                                    <Input v-model.number="oguryRate" type="number" step="0.001" />
                                </label>
                                <label class="flex flex-col gap-1">
                                    <span class="text-sm font-medium">Deliverables reminder day</span>
                                    <select v-model.number="settingsDay" class="rounded-md border bg-background px-2 py-2 text-sm outline-none focus:ring-2 focus:ring-[#e2483d]/40">
                                        <option v-for="(d, i) in DAY_NAMES" :key="i" :value="i">{{ d }}</option>
                                    </select>
                                </label>
                            </div>
                            <p class="text-[11px] text-muted-foreground">The weekly deliverables reminder appears on the selected day.</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="flex flex-col gap-1">
                                    <span class="text-sm font-medium">RPM warning — min (amber)</span>
                                    <Input v-model.number="rpmAmber" type="number" step="0.1" min="0" />
                                </label>
                                <label class="flex flex-col gap-1">
                                    <span class="text-sm font-medium">RPM alert — max (red)</span>
                                    <Input v-model.number="rpmRed" type="number" step="0.1" min="0" />
                                </label>
                            </div>
                            <p class="text-[11px] text-muted-foreground">A F1Maximaal day's row turns amber at or above the min and red at or above the max. A high RPM means analytics pageviews are under-reported — re-upload that day's analytics file.</p>
                            <label class="mt-1 flex items-start justify-between gap-3 rounded-md border p-3">
                                <span class="flex flex-col gap-0.5">
                                    <span class="text-sm font-medium">Convert Ogury report to old format?</span>
                                    <span class="text-[11px] text-muted-foreground">When on, the Ogury file in the download is converted to the legacy “Report / Statistics” layout. Off keeps the current export.</span>
                                </span>
                                <input v-model="oguryOldFormat" type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 accent-[#e2483d]" />
                            </label>
                        </section>

                        <!-- Upload file names -->
                        <section class="flex flex-col gap-3 border-t pt-5">
                            <div>
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Upload file names</h3>
                                <p class="mt-1 text-[11px] text-muted-foreground">
                                    A dropped file is recognised when its name contains any of these values (comma-separated for alternatives).
                                    Update one if a partner renames its export. Leave blank to use the built-in default.
                                </p>
                            </div>
                            <div class="grid gap-x-4 gap-y-3 sm:grid-cols-2">
                                <label v-for="f in FILE_PATTERN_FIELDS" :key="f.key" class="flex flex-col gap-1">
                                    <span class="text-xs font-medium text-muted-foreground">{{ f.label }}</span>
                                    <Input v-model="filePatterns[f.key]" spellcheck="false" class="font-mono text-xs" placeholder="e.g. report_finance" />
                                </label>
                            </div>
                        </section>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t px-6 py-4">
                        <button class="text-sm text-muted-foreground hover:underline" @click="showSettings = false">Cancel</button>
                        <Button class="rounded-full" @click="saveSettings">Save changes</Button>
                    </div>
                </Card>
            </div>

            <!-- Adhese impressions batch modal — fires after process/sync when new F1 dates appear -->
            <div v-if="showAdheseModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <Card class="rpt-glass w-full max-w-md">
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
                            <Button class="rounded-full" :disabled="adheseSaving" @click="saveAdheseBatch">
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

<style scoped>
/* ---------- Reporting chrome (mirrors Previews/Update2's visual language:
   JetBrains Mono, glass surfaces, ambient accent glow, dark-mode starfield)
   with a fixed accent instead of a per-item color palette. ---------- */
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap');

.rpt-root {
    --rpt-accent:        #e2483d;
    --rpt-accent-2:      #f59e0b;
    --rpt-accent-soft:   #e2483d1a;
    --rpt-accent-muted:  #e2483d38;
    --rpt-accent-glow:   #e2483d66;
    --rpt-accent-2-soft: #f59e0b14;
    --rpt-accent-2-glow: #f59e0b59;

    --rpt-bg:            #fafafa;
    --rpt-surface:       #ffffff;
    --rpt-surface-muted: rgba(255, 255, 255, 0.85);
    --rpt-text:          #18181b;
    --rpt-text-muted:    #71717a;
    --rpt-border:        rgba(15, 15, 20, 0.08);
    --rpt-hairline:      rgba(15, 15, 20, 0.06);

    background-color: var(--rpt-bg);
    color: var(--rpt-text);
    font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-feature-settings: 'cv11', 'ss01', 'tnum';
}
.dark .rpt-root {
    --rpt-bg:            #0B0B10;
    --rpt-surface:       #1E1E23;
    --rpt-surface-muted: rgba(30, 30, 35, 0.45);
    --rpt-text:          #F8FAFC;
    --rpt-text-muted:    #94A3B8;
    --rpt-border:        rgba(255, 255, 255, 0.10);
    --rpt-hairline:      rgba(255, 255, 255, 0.06);
}

.rpt-root :focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px var(--rpt-bg), 0 0 0 4px var(--rpt-accent);
    border-radius: inherit;
}

.rpt-glass {
    background: var(--rpt-surface-muted);
    border: 1px solid var(--rpt-border);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.rpt-label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--rpt-text-muted);
}

/* Click-to-highlight table row (Table tab). */
.rpt-row-selected {
    background: var(--rpt-accent-soft) !important;
    box-shadow: inset 3px 0 0 0 var(--rpt-accent);
}

/* ---------- Welcome splash ---------- */
.rpt-splash {
    position: fixed;
    inset: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    background: var(--rpt-bg);
    overflow: hidden;
}
.rpt-splash .rpt-ambient,
.rpt-splash .rpt-stars { opacity: 0.7; }
.rpt-stars--always { opacity: 0.55 !important; animation: rpt-twinkle 6s ease-in-out infinite; }

.rpt-splash-fade-enter-active,
.rpt-splash-fade-leave-active { transition: opacity 400ms cubic-bezier(0.22, 1, 0.36, 1); }
.rpt-splash-fade-enter-from,
.rpt-splash-fade-leave-to { opacity: 0; }

.rpt-splash-content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    text-align: center;
    padding: 24px;
}

.rpt-splash-mark {
    position: relative;
    display: grid;
    place-items: center;
    width: 72px;
    height: 72px;
    margin-bottom: 8px;
}
.rpt-splash-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 2px solid var(--rpt-accent-muted);
    border-top-color: var(--rpt-accent);
    animation: rpt-splash-spin 1s linear infinite;
}
.rpt-splash-mark-text {
    font-size: 15px;
    font-weight: 600;
    letter-spacing: 0.05em;
    color: var(--rpt-accent);
}
@keyframes rpt-splash-spin { to { transform: rotate(360deg); } }

.rpt-splash-title {
    font-size: 20px;
    font-weight: 600;
    letter-spacing: -0.01em;
    color: var(--rpt-text);
}
.rpt-splash-dots span {
    animation: rpt-splash-dot 1.2s ease-in-out infinite;
    opacity: 0.2;
}
.rpt-splash-dots span:nth-child(2) { animation-delay: 0.2s; }
.rpt-splash-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes rpt-splash-dot { 0%, 100% { opacity: 0.2; } 50% { opacity: 1; } }

.rpt-splash-status {
    font-size: 12px;
    color: var(--rpt-text-muted);
    min-height: 1.2em;
}

.rpt-splash-bar {
    width: 180px;
    height: 3px;
    border-radius: 999px;
    background: var(--rpt-border);
    overflow: hidden;
    margin-top: 4px;
}
.rpt-splash-bar-fill {
    height: 100%;
    width: 0%;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--rpt-accent), var(--rpt-accent-2));
    animation: rpt-splash-fill 5s linear forwards;
}
@keyframes rpt-splash-fill { to { width: 100%; } }

.rpt-splash-skip {
    margin-top: 10px;
    font-size: 11px;
    font-weight: 500;
    color: var(--rpt-text-muted);
    background: none;
    border: none;
    cursor: pointer;
    transition: color 200ms;
}
.rpt-splash-skip:hover { color: var(--rpt-accent); }

@media (prefers-reduced-motion: reduce) {
    .rpt-splash-ring, .rpt-splash-dots span, .rpt-splash-bar-fill, .rpt-stars--always {
        animation: none !important;
    }
    .rpt-splash-bar-fill { width: 100%; }
}

/* ---------- Ambient backdrop ---------- */
.rpt-ambient {
    pointer-events: none;
    position: absolute;
    inset: 0;
    z-index: 0;
    background:
        radial-gradient(55% 45% at 0% 0%, var(--rpt-accent-soft) 0%, transparent 70%),
        radial-gradient(40% 40% at 100% 0%, var(--rpt-accent-2-soft) 0%, transparent 75%);
    opacity: 0.5;
}
.dark .rpt-ambient {
    background:
        radial-gradient(70% 55% at 5% 0%, var(--rpt-accent-glow) 0%, transparent 65%),
        radial-gradient(60% 55% at 100% 25%, var(--rpt-accent-2-glow) 0%, transparent 70%),
        radial-gradient(60% 60% at 100% 100%, var(--rpt-accent-glow) 0%, transparent 75%);
    opacity: 0.55;
}

.rpt-stars {
    pointer-events: none;
    position: absolute;
    inset: 0;
    z-index: 0;
    opacity: 0;
    transition: opacity 600ms cubic-bezier(0.22, 1, 0.36, 1);
    background-image:
        radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.85), transparent 50%),
        radial-gradient(1px 1px at 60% 70%, rgba(255,255,255,0.7),  transparent 50%),
        radial-gradient(1.5px 1.5px at 80% 20%, rgba(255,255,255,0.6), transparent 50%),
        radial-gradient(1px 1px at 35% 85%, rgba(255,255,255,0.5), transparent 50%),
        radial-gradient(1px 1px at 90% 50%, rgba(255,255,255,0.65), transparent 50%);
    background-size: 1200px 800px;
}
.dark .rpt-stars { opacity: 0.55; animation: rpt-twinkle 6s ease-in-out infinite; }
@keyframes rpt-twinkle {
    0%, 100% { opacity: 0.4; }
    50%      { opacity: 0.65; }
}


@media (prefers-reduced-motion: reduce) {
    .rpt-root *, .rpt-root *::before, .rpt-root *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    .rpt-stars { animation: none; }
}

/* Zip-download progress animations (Download modal "working" state). */
@keyframes zip-ping {
    0% { transform: scale(0.85); opacity: 0.9; }
    70%, 100% { transform: scale(1.55); opacity: 0; }
}
.zip-ping { animation: zip-ping 1.4s cubic-bezier(0, 0, 0.2, 1) infinite; }

@keyframes zip-bob {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-3px) rotate(-4deg); }
}
.zip-bob { animation: zip-bob 1.1s ease-in-out infinite; }

@keyframes zip-stripes {
    to { background-position: 28px 0; }
}
.zip-stripes {
    background-image: repeating-linear-gradient(45deg, #e2483d 0 10px, rgba(226, 72, 61, 0.45) 10px 20px);
    background-size: 28px 28px;
    animation: zip-stripes 0.75s linear infinite;
}

@keyframes zip-pop {
    0% { transform: scale(0.4); opacity: 0; }
    65% { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(1); }
}
.zip-pop { animation: zip-pop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1); }

.zip-phase-enter-active, .zip-phase-leave-active { transition: all 0.25s ease; }
.zip-phase-enter-from { opacity: 0; transform: translateY(4px); }
.zip-phase-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
