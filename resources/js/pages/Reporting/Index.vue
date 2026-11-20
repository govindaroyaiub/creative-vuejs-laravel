<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import DateRangePicker from '@/components/DateRangePicker.vue';
import { useInitials } from '@/composables/useInitials';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Upload, Download, AlertTriangle, CheckCircle2, XCircle, Loader2, CalendarDays, X, FileText, FileSpreadsheet, FileJson, ArrowLeft, ExternalLink, Plus, Link2, Mail, Copy, Trash2, RefreshCw, Settings, Minus, ChevronDown, ChevronUp, MoreHorizontal, FileArchive, PackageCheck, Calculator } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, onMounted, watch } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS, Title, Tooltip, Legend, Filler,
    LineElement, CategoryScale, LinearScale, PointElement, BarElement, BarController, LineController,
} from 'chart.js';

import vueFilePond from 'vue-filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import 'filepond/dist/filepond.min.css';
const FilePond = vueFilePond(FilePondPluginFileValidateType);

// LineController: the RPM strip is a bar chart with two dashed threshold lines.
ChartJS.register(Title, Tooltip, Legend, Filler, LineElement, CategoryScale, LinearScale, PointElement, BarElement, BarController, LineController);


const page = usePage();
const store = computed<any>(() => page.props.store);
const sites = computed<any[]>(() => (page.props.sites as any[]) ?? []);
const authUser = computed<any>(() => (page.props.auth as any)?.user ?? null);
const { getInitials } = useInitials();

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

// ─── "How it's calculated" reference panel — read-only formula documentation
// so the numbers on this page can be explained on the spot. ─────────────────────
const showFormulas = ref(false);

// ─── Settings modal (Ogury rate + reminder day + upload file names) ─────────────
const showSettings = ref(false);
const settingsDay = ref(reminderDay.value);
// Editable upload-recognition patterns per partner (comma-separated substrings).
const FILE_PATTERN_FIELDS = [
    { key: 'teads', label: 'Teads' }, { key: 'ogury', label: 'Ogury' }, { key: 'gam', label: 'GAM' },
    { key: 'seedtag', label: 'SeedTag' }, { key: 'adform', label: 'Adform' },
    { key: 'showheroes', label: 'Showheroes' }, { key: 'analytics', label: 'Analytics' },
    { key: 'adhese', label: 'Adhese' }, { key: 'adsense', label: 'Adsense' },
    { key: 'gumgum', label: 'Gumgum' }, { key: 'outbrain', label: 'Outbrain' },
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
// Host + favicon for the report-links cards (visual only).
const linkHost = (u: string): string => { try { return new URL(u).hostname.replace(/^www\./, ''); } catch { return ''; } };
const linkFavicon = (u: string): string => { const h = linkHost(u); return h ? `https://www.google.com/s2/favicons?domain=${h}&sz=64` : ''; };
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
    jfk: {
        to: [
            { name: 'Martijn van der Spek (TopGear Nederland)', email: 'martijn@topgear.nl' },
            { name: 'Roland van der Spek (VDS Publishers)', email: 'roland@vds-publishers.nl' },
            { name: 'Robert Heijmans (VDS Publishers)', email: 'robert@vds-publishers.nl' },
        ],
        cc: [{ name: 'Chris Beijer', email: 'chris@planetnine.com' }, { name: 'Taco Stomps', email: 'taco@planetnine.com' }],
        body: `Dear Martijn, Robert and Roland,\n\nHope you are doing well!\n\nPlease find this week's revenue reports for JFK.men attached. If you have any questions, please reach out.\n\nBest regards`,
        subject: (c) => `JFK.men Ad Revenue Reports - Week ${c.week}`,
        attachment: (c) => `JFK-revenue-report-week${c.week}.xlsx`,
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

const ALL_PARTNERS: { key: string; label: string; lines?: [string, string] }[] = [
    { key: 'adhese', label: 'Adhese' }, { key: 'gam', label: 'GAM' },
    { key: 'seedtag', label: 'SeedTag' }, { key: 'teads', label: 'Teads' },
    { key: 'showheroes', label: 'Showheroes' }, { key: 'adform', label: 'Adform' },
    { key: 'ogury', label: 'Ogury' }, { key: 'outbrain', label: 'Outbrain' },
    { key: 'preferredDeals', label: 'Preferred Deals', lines: ['Preferred', 'Deals'] },
    { key: 'adsense', label: 'Adsense' }, { key: 'gumgum', label: 'Gumgum' },
];
// Outbrain and Preferred Deals only ever run on F1Maximaal (extraction itself
// is F1-only, see ReportProcessor) — Topgear/Horses/Festileaks always show 0
// for both, so hide the columns there instead of displaying dead zeros.
const PARTNERS = computed(() => selectedSite.value === 'f1maximaal'
    ? ALL_PARTNERS
    : ALL_PARTNERS.filter((p) => p.key !== 'outbrain' && p.key !== 'preferredDeals'));

const selectedSite = ref('f1maximaal');
const activeTab = ref<'summary' | 'impressions' | 'revenue' | 'verify' | 'email'>('summary');
watch(selectedSite, () => {
    // Verify is per-site — clear the staged file + widget so a report uploaded
    // for one site doesn't linger when switching to another.
    verifyFile.value = null;
    verifyPond.value?.removeFiles();
});
const oguryRate = ref<number>(store.value?.config?.oguryRate ?? 0.85);
const processing = ref(false);
// Optional YYYY-MM: process/backfill that whole month instead of the default
// "current month + last 7 days" window.
const backfillPeriod = ref('');
const showUpload = ref(false);
const showAdheseModal = ref(false);
const adheseEntries = ref<{ site: string; siteName: string; dateKey: string; adhese: number | null }[]>([]);
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
    // A day that hasn't been (re)processed since impressions/analytics became
    // per-site has no `impressions` object at all yet — normalize it here once
    // so every template access (including v-model, which can't use `?.` as an
    // assignment target) can rely on it always being an object.
    arr.forEach((d: any) => { if (!d.impressions) d.impressions = {}; });
    return arr;
});

// Table-tab row order only — everything else (charts, latestDay, totals) keeps
// `days` chronological ascending. Default newest-first; click the Date header
// to flip.
const dateSort = ref<'asc' | 'desc'>('desc');
function toggleDateSort() {
    dateSort.value = dateSort.value === 'desc' ? 'asc' : 'desc';
}
const tableDays = computed(() => {
    const arr = [...days.value];
    return dateSort.value === 'asc' ? arr : arr.reverse();
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

// The Impressions and Revenue tabs render the same table, differing only in which
// per-partner map (counts vs €) they show — driven straight off the active tab.
const tableMetric = computed<'revenue' | 'impressions'>(() => activeTab.value === 'revenue' ? 'revenue' : 'impressions');

const partnerTotals = computed(() => {
    const totals: Record<string, number> = {};
    const impTotals: Record<string, number> = {};
    let grand = 0;
    let impGrand = 0;
    for (const p of PARTNERS.value) { totals[p.key] = 0; impTotals[p.key] = 0; }
    for (const d of days.value) {
        for (const p of PARTNERS.value) {
            const v = d.revenue?.[p.key] ?? 0;
            totals[p.key] += v; grand += v;
            const iv = d.impressions?.[p.key] ?? 0;
            impTotals[p.key] += iv; impGrand += iv;
        }
    }
    return { totals, grand, impTotals, impGrand };
});

// Adhese impressions / RPM / Impr. sold / Ad requests — this whole bundle is
// only meaningful for F1Maximaal and Topgear (the sites Adhese, GA4 analytics,
// and the GAM ad-requests export are actually wired up for). Horses and
// Festileaks don't track any of it, by design, not as a gap to fill in.
// RPM needs GA4 analytics, wired only for F1Maximaal + Topgear.
const RPM_SITES = ['f1maximaal', 'topgear'];
// Impression-side columns (Impr. sold / Ad requests / manual Adhese input) apply
// to any site that tracks impressions — F1, Topgear, and JFK (no analytics, so
// no RPM, but it still has GAM ad-requests + Adhese revenue to reconcile).
const IMPRESSION_SITES = ['f1maximaal', 'topgear', 'jfk'];
const supportsRpm = computed(() => RPM_SITES.includes(selectedSite.value));
const supportsImpressions = computed(() => IMPRESSION_SITES.includes(selectedSite.value));

// In the impressions view the manual "Adhese impr." input column already shows
// (and edits) Adhese impressions, so drop the generic read-only Adhese partner
// column there to avoid two Adhese columns. Revenue view keeps it (adhese
// revenue). The Total still sums the full PARTNERS list, so adhese impressions
// stay counted regardless of whether this column is shown.
const displayPartners = computed(() =>
    supportsImpressions.value && tableMetric.value === 'impressions'
        ? PARTNERS.value.filter((p) => p.key !== 'adhese')
        : PARTNERS.value,
);

// RPM (revenue per 1000 pageviews). A high RPM means analytics pageviews are
// under-reported (incomplete/late-finalized GA4 data) — the day's analytics file
// likely needs re-uploading. rpmFor() itself works for any site with analytics
// merged; anomaly tinting (rpmTier, below) stays F1Maximaal-only for now until
// Topgear's analytics upload cadence is as proven as F1's.
// Editable in Settings; default to amber >=7.5, red >=8. Reactive so the table
// re-tints immediately after the thresholds are saved.
const rpmAmber = ref<number>((page.props.rpmAmber as number) ?? 7.5);
const rpmRed = ref<number>((page.props.rpmRed as number) ?? 8);
const dayRevenue = (d: any) => PARTNERS.value.reduce((t, p) => t + (d.revenue?.[p.key] ?? 0), 0);
const rpmFor = (d: any): number | null => {
    const views = d.analytics?.views ?? 0;
    return views > 0 ? (dayRevenue(d) / views) * 1000 : null;
};
const rpmTier = (d: any): 'red' | 'amber' | null => {
    // Anomaly tinting applies to whichever sites carry Adhese/RPM at all
    // (F1Maximaal + Topgear) — Topgear's analytics cadence is proven now,
    // same bundle gate as everywhere else on this page.
    if (!supportsRpm.value) return null;
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
// Most recent day in the active date-range filter — the Summary tab's
// "Latest day" panel answers the page's question: did that day come in, and
// does its RPM look right?
const latestDay = computed<any | null>(() => days.value[days.value.length - 1] ?? null);
const blendedRpm = computed<number | null>(() => {
    const views = days.value.reduce((t, d) => t + (d.analytics?.views ?? 0), 0);
    return views > 0 ? (partnerTotals.value.grand / views) * 1000 : null;
});
// Same amber/red banding as the table rows, applied to the range-wide blended
// RPM so the range strip signals when the period as a whole looks off.
const blendedRpmTier = computed<'red' | 'amber' | null>(() => {
    if (blendedRpm.value === null) return null;
    if (blendedRpm.value >= rpmRed.value) return 'red';
    if (blendedRpm.value >= rpmAmber.value) return 'amber';
    return null;
});
const impressionsSoldTotal = computed(() =>
    days.value.reduce((t, d) => t + (d.impressionsSold || 0), 0));

const avgDaily = computed(() => (days.value.length ? partnerTotals.value.grand / days.value.length : 0));

// Partners that actually have revenue, ranked by share — the "Revenue by
// partner" list; its first row is the top partner.
const partnerBreakdown = computed(() => {
    const t = partnerTotals.value.totals;
    const grand = partnerTotals.value.grand || 1;
    return PARTNERS.value.map((p) => ({ ...p, total: t[p.key] ?? 0, pct: ((t[p.key] ?? 0) / grand) * 100 }))
        .filter((x) => x.total > 0)
        .sort((a, b) => b.total - a.total);
});

const bestDay = computed(() => {
    let best: { dateKey: string; total: number; partner: string } | null = null;
    for (const d of days.value) {
        const tot = dayRevenue(d);
        if (!best || tot > best.total) {
            let top = { label: '—', v: -1 };
            for (const p of PARTNERS.value) {
                const v = d.revenue?.[p.key] ?? 0;
                if (v > top.v) top = { label: p.label, v };
            }
            best = { dateKey: d.dateKey, total: tot, partner: top.label };
        }
    }
    return best;
});

// ─── Latest day (Summary panel) ───────────────────────────────────────────────
// Every stored day for the site, oldest first, ignoring the range filter: the
// panel compares the latest day with the stored day before it, and the RPM
// strip shows the 14 days up to it even when the range starts later.
const siteDaysAll = computed<any[]>(() => {
    const map = store.value?.sites?.[selectedSite.value]?.days ?? {};
    return Object.values(map).sort((a: any, b: any) => a.dateKey.localeCompare(b.dateKey));
});
const latestIdx = computed(() => (latestDay.value ? siteDaysAll.value.findIndex((d) => d.dateKey === latestDay.value.dateKey) : -1));
const prevDay = computed<any | null>(() => (latestIdx.value > 0 ? siteDaysAll.value[latestIdx.value - 1] : null));
const siteName = computed(() => sites.value.find((s) => s.id === selectedSite.value)?.name ?? '');

const asDate = (k: string) => new Date(k + 'T12:00:00');
const fmtDay = (k: string) => asDate(k).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
const fmtDayLong = (k: string) => asDate(k).toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const fmtDayShort = (k: string) => asDate(k).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });

// A change against the day before, signed. Colour says good or bad for the
// business (more revenue is good); figures that aren't judged stay grey.
type Delta = { text: string; tone: 'pos' | 'neg' | 'flat' };
function delta(cur: number, prev: number | null | undefined, fmt: (n: number) => string, judged = true): Delta | null {
    if (prev == null) return null;
    const diff = cur - prev;
    if (Math.abs(diff) < 0.005) return { text: '±0', tone: 'flat' };
    return { text: (diff > 0 ? '+' : '−') + fmt(Math.abs(diff)), tone: !judged ? 'flat' : diff > 0 ? 'pos' : 'neg' };
}
const pct = (part: number, whole: number) => (whole > 0 ? ((part / whole) * 100).toFixed(1) + '%' : '—');

const adheseMissing = computed(() => {
    const d = latestDay.value;
    return !!d && supportsImpressions.value && (d.revenue?.adhese ?? 0) > 0 && d.impressions?.adhese == null;
});

// The figures under the headline number, each against the day before.
const latestRows = computed(() => {
    const d = latestDay.value;
    if (!d) return [];
    const p = prevDay.value;
    const rows: { label: string; value: string; sub?: string | undefined; delta: Delta | null }[] = [
        { label: 'Revenue', value: eur(dayRevenue(d)), delta: delta(dayRevenue(d), p ? dayRevenue(p) : null, eur) },
    ];
    if (supportsRpm.value) {
        const v = d.analytics?.views;
        rows.push({ label: 'Pageviews', value: v != null ? num(v) : '—', delta: v != null ? delta(v, p?.analytics?.views, num) : null });
    }
    if (supportsImpressions.value) {
        const sold = d.impressionsSold || 0;
        const req = d.totalAdRequests || 0;
        rows.push({ label: 'Impressions sold', value: num(sold), sub: req ? pct(sold, req) + ' of requests' : undefined, delta: delta(sold, p ? p.impressionsSold || 0 : null, num) });
        rows.push({ label: 'Ad requests', value: num(req), delta: delta(req, p ? p.totalAdRequests || 0 : null, num, false) });
    }
    return rows;
});

const tierText = (t: 'red' | 'amber' | null, r: number | null) =>
    t === 'red' ? (r === null ? 'No pageviews' : 'Over the alert') : t === 'amber' ? 'Over the watch line' : 'Normal';

// ─── Charts ──────────────────────────────────────────────────────────────────
// One hue on the screen's main data: the latest day, in the brand red. Every
// other day is grey; amber and red are kept for the RPM alert levels.
const HUE = '#e2483d';
const BAR = 'rgba(128,128,128,0.32)';
const BAR_STRONG = 'rgba(128,128,128,0.85)';
const WATCH = '#e3a23b';
const ALERT = '#c4242a';

const revenueChart = computed(() => ({
    labels: days.value.map((d) => fmtDayShort(d.dateKey)),
    datasets: [{
        label: 'Revenue',
        data: days.value.map(dayRevenue),
        backgroundColor: days.value.map((d) => (d.dateKey === latestDay.value?.dateKey ? HUE : BAR)),
        borderRadius: 0, barPercentage: 0.8, categoryPercentage: 0.9,
    }],
}));

// RPM against the alert band: the 14 stored days up to the latest one, with
// the Settings thresholds as dashed lines. Sites without analytics get their
// daily revenue here instead.
const stripDays = computed(() => (latestIdx.value >= 0 ? siteDaysAll.value.slice(Math.max(0, latestIdx.value - 13), latestIdx.value + 1) : []));
const stripChart = computed(() => {
    const ds = stripDays.value;
    const last = ds.length - 1;
    const labels = ds.map((d) => fmtDayShort(d.dateKey));
    if (!supportsRpm.value) {
        return { labels, datasets: [{ type: 'bar' as const, label: 'Revenue', data: ds.map(dayRevenue), backgroundColor: ds.map((_, i) => (i === last ? HUE : BAR)), borderRadius: 0, barPercentage: 0.75, categoryPercentage: 0.9 }] };
    }
    const flat = (v: number, label: string, color: string) => ({
        type: 'line' as const, label, data: ds.map(() => v), borderColor: color, borderWidth: 1, borderDash: [4, 4], pointRadius: 0, pointHoverRadius: 0, order: 0,
    });
    return {
        labels,
        datasets: [
            {
                type: 'bar' as const, label: 'RPM', order: 1,
                data: ds.map((d) => rpmFor(d)),
                backgroundColor: ds.map((d, i) => {
                    const t = rpmTier(d);
                    return t === 'red' ? ALERT : t === 'amber' ? WATCH : i === last ? BAR_STRONG : BAR;
                }),
                borderRadius: 0, barPercentage: 0.75, categoryPercentage: 0.9,
            },
            flat(rpmAmber.value, 'Watch', WATCH),
            flat(rpmRed.value, 'Alert', ALERT),
        ],
    };
});

const gridColor = 'rgba(128,128,128,0.14)';
const tickColor = 'rgba(128,128,128,0.9)';
const tip = {
    backgroundColor: 'rgba(17,17,17,0.92)', padding: 8, cornerRadius: 4, displayColors: false,
    titleFont: { size: 11 }, bodyFont: { size: 12 },
};
// X labels on one even step, counted back from the latest day so it is always
// labelled (autoSkip would drop it).
const stepTicks = (labels: string[], most: number) => {
    const step = Math.max(1, Math.ceil(labels.length / most));
    const last = labels.length - 1;
    return { color: tickColor, font: { size: 11 }, maxRotation: 0, autoSkip: false,
        callback: (_v: any, i: number) => ((last - i) % step === 0 ? labels[i] : '') };
};
const axisX = { grid: { display: false }, border: { display: false }, ticks: { color: tickColor, font: { size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } };
const chartOptions = {
    responsive: true, maintainAspectRatio: false,
    // No draw-in animation: charts live under v-show, so Chart.js would replay
    // its entry animation every time the Summary tab is revealed.
    animation: false as const,
    interaction: { mode: 'index' as const, intersect: false },
    plugins: {
        legend: { display: false },
        tooltip: { ...tip, callbacks: { label: (c: any) => eur(c.parsed.y) } },
    },
    scales: {
        x: axisX,
        y: { beginAtZero: true, grid: { color: gridColor }, border: { display: false }, ticks: { color: tickColor, font: { size: 11 }, maxTicksLimit: 5, callback: (v: any) => '€' + Number(v).toLocaleString('en-US') } },
    },
};
const revenueOptions = computed(() => ({
    ...chartOptions,
    scales: { ...chartOptions.scales, x: { ...axisX, ticks: stepTicks(revenueChart.value.labels, 8) } },
}));
const stripOptions = computed(() => {
    const vals = ((stripChart.value.datasets[0]?.data ?? []) as (number | null)[]).filter((v): v is number => typeof v === 'number');
    const rpm = supportsRpm.value;
    return {
        ...chartOptions,
        plugins: {
            legend: { display: false },
            tooltip: {
                ...tip,
                filter: (c: any) => c.dataset.type === 'bar',
                callbacks: { label: (c: any) => (rpm ? (c.parsed.y == null ? 'No pageviews' : 'RPM ' + c.parsed.y.toFixed(2)) : eur(c.parsed.y)) },
            },
        },
        scales: {
            x: { ...axisX, ticks: stepTicks(stripChart.value.labels, 5) },
            y: rpm
                ? { beginAtZero: true, max: Math.ceil((Math.max(rpmRed.value, ...vals) + 0.25) / 2) * 2, grid: { color: gridColor }, border: { display: false }, ticks: { stepSize: 2, color: tickColor, font: { size: 11 }, callback: (v: any) => Number(v).toFixed(0) } }
                : chartOptions.scales.y,
        },
    };
});

// ─── Anomalies (zero/missing only) ─────────────────────────────────────────────
// A partner that reports on most days of the range but drops to 0 (or has no
// value at all) on a given day is almost always a missing/broken upload rather
// than a real zero. Checked per metric — revenue and, on sites that track them,
// impressions — so the message can name exactly which number went missing and
// what that partner normally does on a day.
const anomaliesOpen = ref(false);
const METRICS: { key: 'revenue' | 'impressions'; label: string; unit: 'eur' | 'count' }[] = [
    { key: 'revenue', label: 'Revenue', unit: 'eur' },
    { key: 'impressions', label: 'Impressions', unit: 'count' },
];
const median = (xs: number[]) => {
    const a = [...xs].sort((x, y) => x - y);
    const m = a.length >> 1;
    return a.length % 2 ? (a[m] ?? 0) : ((a[m - 1] ?? 0) + (a[m] ?? 0)) / 2;
};
const anomalies = computed(() => {
    const arr = days.value; const n = arr.length; const out: any[] = [];
    for (const metric of METRICS) {
        // Impressions only exist for the sites that actually track them —
        // flagging "0 impressions" on Horses/Festileaks would be pure noise.
        if (metric.key === 'impressions' && !supportsImpressions.value) continue;
        for (const p of PARTNERS.value) {
            const vals = arr.map((d) => d[metric.key]?.[p.key] ?? null);
            const reported = vals.filter((v): v is number => (v ?? 0) > 0);
            // Needs a real habit to break: >=4 days in range, reporting on at
            // least 60% of them, and at least one day that isn't reporting.
            if (!(n >= 4 && reported.length >= Math.ceil(0.6 * n) && reported.length < n)) continue;
            const typical = median(reported);
            vals.forEach((v, i) => {
                if (v != null && v !== 0) return;
                out.push({
                    dateKey: arr[i].dateKey,
                    partner: p.label,
                    metric: metric.label,
                    unit: metric.unit,
                    missing: v == null,
                    typical,
                    reportedDays: reported.length,
                    totalDays: n,
                });
            });
        }
    }
    return out.sort((a, b) => b.dateKey.localeCompare(a.dateKey) || a.partner.localeCompare(b.partner));
});
// "€0.00" / "0 impressions" vs "no value at all" — the two are different
// problems (partner reported a zero vs the file never landed), so say which.
const anomalyValue = (a: any) => (a.missing ? 'no data' : a.unit === 'eur' ? eur(0) : '0');
const anomalyTypical = (a: any) => (a.unit === 'eur' ? eur(a.typical) : num(Math.round(a.typical)));

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
    if (matchesPattern(n, p.adsense)) return 'adsense';
    if (matchesPattern(n, p.gumgum)) return 'gumgum';
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
    // Same GAM per-site ad-requests export as gam_f1m ("Copy of {site}" or
    // "GAM - {site}") — mirrors the server-side fallback in Reporting::detectFileType().
    if ((n.startsWith('copy of ') || n.startsWith('gam - ')) && !n.includes('general data download')) return 'gam_f1m';
    return 'unknown';
}

const REQUIRED: { key: string; label: string }[] = [
    { key: 'adform', label: 'Adform' }, { key: 'gam', label: 'GAM' },
    { key: 'ogury', label: 'Ogury' }, { key: 'seedtag', label: 'SeedTag' }, { key: 'showheroes', label: 'Showheroes' },
    { key: 'teads', label: 'Teads' },
    { key: 'adsense', label: 'Adsense' }, { key: 'gumgum', label: 'Gumgum' },
    { key: 'analytics_f1', label: 'Analytics (F1)' }, { key: 'analytics_tg', label: 'Analytics (TopGear)' },
    { key: 'gam_f1m_f1', label: 'GAM Ad Requests (F1)' }, { key: 'gam_f1m_tg', label: 'GAM Ad Requests (TopGear)' }, { key: 'gam_f1m_jfk', label: 'GAM Ad Requests (JFK)' },
    { key: 'adhese_f1', label: 'Adhese (F1)' }, { key: 'adhese_tg', label: 'Adhese (TopGear)' }, { key: 'adhese_fl', label: 'Adhese (Festileaks)' }, { key: 'adhese_jfk', label: 'Adhese (JFK)' },
    { key: 'outbrain', label: 'Outbrain' }, { key: 'preferreddeals', label: 'Preferred Deals' },
];

// A GA4 export or GAM ad-requests export carries no site column (one property/
// site per file), so — same as Adhese — the site comes from the filename: TG/FL
// explicit, F1Maximaal is the default for anything else.
function siteSuffixFromName(name: string): 'f1' | 'tg' | 'fl' | 'jfk' {
    const n = name.toLowerCase();
    if (n.includes(' tg') || n.includes('topgear')) return 'tg';
    if (n.includes(' fl') || n.includes('festileaks')) return 'fl';
    if (n.includes('jfk')) return 'jfk';
    return 'f1';
}

// Live checklist: each required file is checked once a matching dropped file is
// detected; unchecked items are the ones still missing.
const checklist = computed(() => {
    const detected = new Set<string>();
    const perSite = new Set<string>(); // e.g. 'adhese_tg', 'analytics_f1', 'gam_f1m_fl'
    const fileFor: Record<string, string> = {};
    for (const f of selectedUploadFiles.value) {
        const t = detectType(f.name);
        if (t === 'adhese' || t === 'analytics' || t === 'gam_f1m') {
            const k = `${t}_${siteSuffixFromName(f.name)}`;
            perSite.add(k);
            if (!fileFor[k]) fileFor[k] = f.name;
        } else {
            detected.add(t);
            if (!fileFor[t]) fileFor[t] = f.name;
        }
    }
    return REQUIRED.map((r) => {
        const isPerSite = r.key.startsWith('adhese_') || r.key.startsWith('analytics_') || r.key.startsWith('gam_f1m_');
        const checked = isPerSite ? perSite.has(r.key) : detected.has(r.key);
        return { ...r, checked, file: checked ? fileFor[r.key] : null };
    });
});
const requiredCount = computed(() => checklist.value.length);
const checkedCount = computed(() => checklist.value.filter((c) => c.checked).length);
// The upload modal lists only what still matters: required files not added yet,
// and every added file under the name it was recognised as (null = unknown).
const missingItems = computed(() => checklist.value.filter((c) => !c.checked));
function uploadLabel(name: string): string | null {
    const t = detectType(name);
    if (t === 'unknown') return null;
    const key = t === 'adhese' || t === 'analytics' || t === 'gam_f1m' ? `${t}_${siteSuffixFromName(name)}` : t;
    return REQUIRED.find((r) => r.key === key)?.label ?? name;
}
const addedFiles = computed(() => selectedUploadFiles.value.map((f) => ({ file: f, label: uploadLabel(f.name) })));

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
//
// Scans EVERY site in the Adhese/RPM bundle (F1Maximaal + Topgear), not just
// whichever tab happens to be selected — a single upload run can leave a gap
// on either site, and switching tabs never re-checks for one, so a per-site
// scan here is the only way both ever get surfaced in the same modal.
const missingAdhese = computed<any[]>(() => {
    const out: any[] = [];
    for (const siteId of IMPRESSION_SITES) {
        const map = store.value?.sites?.[siteId]?.days ?? {};
        const siteName = sites.value.find((s: any) => s.id === siteId)?.name ?? siteId;
        for (const d of Object.values(map) as any[]) {
            if ((d.revenue?.adhese ?? 0) > 0 && d.impressions?.adhese == null) {
                out.push({ site: siteId, siteName, dateKey: d.dateKey });
            }
        }
    }
    return out.sort((a, b) => a.dateKey.localeCompare(b.dateKey) || a.site.localeCompare(b.site));
});

// Pivot the flat entry list into a date-by-site grid for the modal: dates are
// rows, the impression sites are fixed columns, each cell is the entry to fill
// (or null where that day/site has no gap → rendered as a dash).
const adheseSiteCols = computed(() =>
    IMPRESSION_SITES.map((id) => ({ id, name: sites.value.find((s: any) => s.id === id)?.name ?? id })),
);
const adheseGrid = computed(() => {
    const byDate = new Map<string, Record<string, any>>();
    for (const e of adheseEntries.value) {
        if (!byDate.has(e.dateKey)) byDate.set(e.dateKey, {});
        byDate.get(e.dateKey)![e.site] = e;
    }
    return [...byDate.keys()].sort().map((dateKey) => ({
        dateKey,
        cells: IMPRESSION_SITES.map((siteId) => ({ siteId, entry: byDate.get(dateKey)![siteId] ?? null })),
    }));
});

function promptMissingAdhese(): boolean {
    if (!missingAdhese.value.length) return false;
    adheseEntries.value = missingAdhese.value.map((d) => ({ site: d.site, siteName: d.siteName, dateKey: d.dateKey, adhese: null }));
    showAdheseModal.value = true;
    return true;
}
// Manual entry point behind the "Fill now" banner button.
const openAdheseGaps = promptMissingAdhese;

// The backend endpoint saves one site's entries per call, so a mixed-site
// batch (e.g. F1 + Topgear gaps in the same modal) posts once per site,
// site-by-site, before closing the modal.
function saveAdheseBatch() {
    adheseSaving.value = true;
    const bySite = new Map<string, typeof adheseEntries.value>();
    for (const e of adheseEntries.value) {
        if (!bySite.has(e.site)) bySite.set(e.site, []);
        bySite.get(e.site)!.push(e);
    }
    const siteIds = [...bySite.keys()];

    const postNext = (i: number) => {
        if (i >= siteIds.length) {
            adheseSaving.value = false;
            showAdheseModal.value = false;
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Adhese impressions saved', timer: 1300, showConfirmButton: false });
            return;
        }
        const siteId = siteIds[i];
        router.post('/reporting/save-adhese-batch', { entries: bySite.get(siteId), site: siteId }, {
            preserveScroll: true, preserveState: true,
            onSuccess: () => postNext(i + 1),
            onError: () => { adheseSaving.value = false; },
        });
    };
    postNext(0);
}

// ─── Upload / process ──────────────────────────────────────────────────────────
function processFiles() {
    if (!selectedUploadFiles.value.length) { Swal.fire('No files', 'Add the partner files first.', 'info'); return; }
    const fd = new FormData();
    selectedUploadFiles.value.forEach((f) => fd.append('files[]', f, f.name));
    // Backfill an older month: without it the run keeps only the current month +
    // last 7 days, dropping everything earlier in the uploaded files.
    if (backfillPeriod.value) fd.append('period', backfillPeriod.value);
    router.post('/reporting/process', fd, {
        forceFormData: true, preserveScroll: true, preserveState: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
        onSuccess: () => {
            selectedUploadFiles.value = [];
            showUpload.value = false;
            // Prompt for any day left with Adhese revenue but no impressions —
            // covers brand-new dates and any that slipped through earlier.
            promptMissingAdhese();
            if (!showAdheseModal.value) {
                Swal.fire({ icon: 'success', title: 'Processed', timer: 1400, showConfirmButton: false });
            }
        },
    });
}

function saveAdhese(day: any) {
    router.post('/reporting/save-adhese', { dateKey: day.dateKey, adhese: day.impressions?.adhese ?? null, site: selectedSite.value }, {
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

        // Filename comes from the server's Content-Disposition (it already
        // knows the selected range) — don't duplicate that naming logic here.
        const disposition = res.headers.get('Content-Disposition') ?? '';
        const filenameMatch = disposition.match(/filename="?([^"]+)"?/);
        const downloadName = filenameMatch?.[1] ?? 'Partners Report.zip';

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
        a.download = downloadName;
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
// Sites that now receive the full Trend-sheet Planetnine report (Ad Requests,
// Impressions Sold, Total Revenue, per-partner breakdown), same as F1. Others
// (Horses, Festileaks) still only get the simpler weekly Demand Partners report.
const MONTHLY_VERIFY_SITES = ['f1maximaal', 'topgear'];

function runVerify() {
    if (!verifyFile.value) { Swal.fire('No file', 'Add the Planetnine report.', 'info'); return; }
    const fd = new FormData();
    fd.append('file', verifyFile.value, verifyFile.value.name);
    const isMonthly = MONTHLY_VERIFY_SITES.includes(selectedSite.value);
    const url = isMonthly ? '/reporting/verify' : '/reporting/verify-weekly';
    if (selectedSite.value !== 'f1maximaal') fd.append('site', selectedSite.value);
    router.post(url, fd, {
        forceFormData: true, preserveScroll: true, preserveState: true,
        onStart: () => (verifying.value = true),
        onFinish: () => (verifying.value = false),
        onSuccess: () => {
            // Rendered inline (preserveState — no remount, no tab change), but
            // Inertia points the address bar at this POST-only
            // URL. Fix it up in place so a refresh hits GET /reporting instead
            // of 405ing on /reporting/verify(-weekly).
            window.history.replaceState(window.history.state, '', '/reporting');
        },
    });
}

const tabs = [
    { id: 'summary', label: 'Summary' },
    { id: 'impressions', label: 'Impressions' }, { id: 'revenue', label: 'Revenue' },
    { id: 'email', label: 'Email' }, { id: 'verify', label: 'Verify' },
];
</script>

<template>
    <Head title="Reporting" />
    <div class="rpt-root relative min-h-screen">
        <!-- Decorative ambient color wash + starfield (dark mode only), same
             technique as the Previews/Update2 editor's chrome. -->
        <div aria-hidden="true" class="rpt-stars" />
        <!-- Standalone top bar (no app sidebar) with a way back -->
        <header class="sticky top-0 z-40 border-b border-[var(--rpt-hairline)] bg-[var(--rpt-bg)]">
            <div class="flex w-full items-center justify-between gap-3 px-4 py-2.5 sm:px-6 lg:px-8">
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

        <!-- No z-index here: it would trap every modal (z-50) below the sticky top bar (z-40).
             Coming after the starfield in the page is enough to paint above it. -->
        <div class="relative flex w-full flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Title row: the page, the selected site, and the jobs -->
            <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-4">
                <div class="min-w-[18rem] flex-1">
                    <h1 class="text-2xl font-semibold tracking-tight">Reporting</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ siteName }}</p>
                </div>
                <div class="flex max-w-full flex-wrap items-center gap-2">
                    <span class="mr-1 basis-full text-xs text-muted-foreground sm:basis-auto">
                        <template v-if="sync.syncedAt">Snapshot {{ sync.syncedAt }}</template>
                        <template v-else>No snapshot imported yet</template>
                        <button v-if="sync.available" class="ml-1 font-medium text-foreground underline-offset-2 hover:underline" @click="confirmSync">
                            · {{ sync.pending }} day{{ sync.pending === 1 ? '' : 's' }} to sync
                        </button>
                    </span>
                    <Button variant="outline" class="h-9 max-sm:order-2" @click="openDownload"><Download /> Download reports</Button>
                    <Button class="h-9 max-sm:order-1" @click="showUpload = true">
                        <Upload /> Upload files
                        <span v-if="selectedUploadFiles.length" class="rounded bg-primary-foreground/20 px-1.5 text-xs tabular-nums">{{ selectedUploadFiles.length }}</span>
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline" size="icon" class="h-9 w-9 max-sm:order-3" aria-label="More actions"><MoreHorizontal /></Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-52">
                            <DropdownMenuItem :disabled="syncing" @select="checkForData"><RefreshCw :class="{ 'animate-spin': syncing }" /> Check for data</DropdownMenuItem>
                            <DropdownMenuItem @select="showLinks = true"><Link2 /> Report links</DropdownMenuItem>
                            <DropdownMenuItem @select="showFormulas = true"><Calculator /> How it's calculated</DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem @select="openSettings"><Settings /> Settings</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- Data migration banner -->
            <div v-if="syncing" class="rpt-panel flex items-center gap-3 p-4 text-sm">
                <Loader2 class="h-4 w-4 shrink-0 animate-spin text-muted-foreground" />
                <div><span class="font-medium">New data found, importing it.</span> <span class="text-muted-foreground">Please wait…</span></div>
            </div>

            <!-- Sites -->
            <nav class="rpt-tabs -mt-2 flex gap-6 overflow-x-auto" aria-label="Site">
                <button v-for="s in sites" :key="s.id" :aria-current="selectedSite === s.id ? 'page' : undefined"
                    class="shrink-0 border-b-2 pb-2.5 pt-1 text-sm transition"
                    :class="selectedSite === s.id ? 'border-foreground font-medium text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    @click="selectedSite = s.id">{{ s.name }}</button>
            </nav>

            <!-- View, then date range -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="rpt-track flex max-w-full gap-0.5 overflow-x-auto" role="tablist" aria-label="View">
                    <button v-for="t in tabs" :key="t.id" role="tab" :aria-selected="activeTab === t.id"
                        class="rpt-seg" :class="{ 'rpt-seg-on': activeTab === t.id }"
                        @click="activeTab = t.id">{{ t.label }}</button>
                </div>
                <div class="flex min-w-0 max-w-full flex-wrap items-center gap-2">
                    <div class="rpt-track flex max-w-full gap-0.5 overflow-x-auto" role="radiogroup" aria-label="Date range">
                        <button v-for="p in PRESETS" :key="p" role="radio" :aria-checked="activePreset === p"
                            class="rpt-seg" :class="{ 'rpt-seg-on': activePreset === p }"
                            @click="applyPreset(p)">{{ p }}</button>
                    </div>
                    <DateRangePicker v-model:from="from" v-model:to="to" @change="activePreset = ''" />
                    <span class="text-xs text-muted-foreground tabular-nums">{{ days.length }} days · week {{ weekNumber }}</span>
                </div>
            </div>

            <!-- Anomalies (zero/missing partners for the selected range) — Summary tab only, collapsed by default -->
            <section v-if="anomalies.length && activeTab === 'summary'" class="rpt-panel">
                <button class="flex w-full items-center gap-2 px-5 py-3.5 text-left text-sm" :aria-expanded="anomaliesOpen" @click="anomaliesOpen = !anomaliesOpen">
                    <AlertTriangle class="h-4 w-4 shrink-0 text-amber-500" />
                    <span class="font-medium">Needs attention</span>
                    <span class="rounded bg-amber-500/15 px-1.5 py-0.5 text-xs font-medium tabular-nums text-amber-700 dark:text-amber-400">
                        {{ anomalies.length }} missing partner {{ anomalies.length === 1 ? 'figure' : 'figures' }}
                    </span>
                    <ChevronDown class="ml-auto h-4 w-4 text-muted-foreground transition-transform" :class="{ 'rotate-180': anomaliesOpen }" />
                </button>
                <div v-show="anomaliesOpen" class="border-t border-[var(--rpt-hairline)] px-5 pb-4 pt-3">
                    <p class="pb-3 text-xs text-muted-foreground">
                        Partners that report on most days of this range, but reported nothing on the days below — usually a missing or broken upload for
                        <span class="font-medium text-foreground">{{ siteName }}</span>.
                    </p>
                    <ul class="flex flex-col text-sm">
                        <li v-for="(a, i) in anomalies.slice(0, 60)" :key="i"
                            class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-[var(--rpt-hairline)] py-2 last:border-0">
                            <span class="w-24 shrink-0 tabular-nums text-muted-foreground">{{ fmtDayShort(a.dateKey) }}</span>
                            <span class="font-medium">{{ a.partner }}</span>
                            <span class="text-xs text-muted-foreground">{{ a.metric }}</span>
                            <span class="font-medium tabular-nums" :class="a.missing ? 'text-muted-foreground' : 'text-amber-700 dark:text-amber-400'">{{ anomalyValue(a) }}</span>
                            <span class="text-xs text-muted-foreground">
                                usually about <span class="tabular-nums text-foreground">{{ anomalyTypical(a) }}</span> a day
                                (reported on {{ a.reportedDays }} of {{ a.totalDays }} days)
                            </span>
                        </li>
                    </ul>
                    <p v-if="anomalies.length > 60" class="pt-2 text-xs text-muted-foreground">
                        +{{ anomalies.length - 60 }} more — narrow the date range to see the rest.
                    </p>
                </div>
            </section>

            <!-- SUMMARY -->
            <div v-show="activeTab === 'summary'" class="flex flex-col gap-4">
                <!-- Latest day: the answer, the day's figures against the day before, and
                     RPM against the alert band (revenue for sites without analytics). -->
                <section v-if="latestDay" class="rpt-panel grid grid-cols-1 gap-x-8 gap-y-6 p-5 lg:grid-cols-12" aria-labelledby="latest-day-title">
                    <div class="flex min-w-0 flex-col lg:col-span-3">
                        <h2 id="latest-day-title" class="text-sm text-muted-foreground">Latest day</h2>
                        <div class="text-sm font-medium">{{ fmtDayLong(latestDay.dateKey) }}</div>

                        <template v-if="supportsRpm">
                            <div class="mt-5 flex items-baseline gap-2">
                                <span class="text-4xl font-semibold tabular-nums tracking-tight"
                                    :class="rpmTier(latestDay) === 'red' ? 'text-red-600 dark:text-red-400' : rpmTier(latestDay) === 'amber' ? 'text-amber-600 dark:text-amber-400' : ''">
                                    {{ rpmFor(latestDay) === null ? '—' : rpmFor(latestDay)!.toFixed(2) }}
                                </span>
                                <span class="text-sm text-muted-foreground">RPM</span>
                            </div>
                            <div class="mt-1 text-xs text-muted-foreground">€ per 1,000 pageviews</div>
                            <div class="mt-3">
                                <span class="rpt-pill"
                                    :class="rpmTier(latestDay) === 'red' ? 'rpt-pill-neg' : rpmTier(latestDay) === 'amber' ? 'rpt-pill-warn' : 'rpt-pill-pos'">
                                    {{ tierText(rpmTier(latestDay), rpmFor(latestDay)) }}
                                </span>
                                <span class="ml-2 text-xs text-muted-foreground tabular-nums">watch {{ rpmAmber.toFixed(2) }} · alert {{ rpmRed.toFixed(2) }}</span>
                            </div>
                        </template>
                        <template v-else>
                            <div class="mt-5 text-4xl font-semibold tabular-nums tracking-tight">{{ eur(dayRevenue(latestDay)) }}</div>
                            <div class="mt-1 text-xs text-muted-foreground">Revenue that day</div>
                        </template>

                        <label v-if="supportsImpressions" class="mt-auto flex flex-col gap-1.5 pt-6 text-sm">
                            <span class="text-muted-foreground">Adhese impressions</span>
                            <Input v-model.number="latestDay.impressions.adhese" type="number" placeholder="Not entered"
                                :class="['h-9 w-full tabular-nums', adheseMissing ? 'border-amber-400 focus-visible:ring-amber-400' : '']"
                                @change="saveAdhese(latestDay)" />
                        </label>
                    </div>

                    <div class="min-w-0 lg:col-span-4">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="text-sm font-medium">That day</h3>
                            <span class="text-xs text-muted-foreground">{{ prevDay ? 'change vs ' + fmtDay(prevDay.dateKey) : 'no earlier day stored' }}</span>
                        </div>
                        <dl class="mt-2">
                            <div v-for="r in latestRows" :key="r.label" class="grid grid-cols-[1fr_auto_6.5rem] items-baseline gap-x-3 border-b border-[var(--rpt-hairline)] py-2.5 last:border-0">
                                <dt class="text-sm text-muted-foreground">{{ r.label }}<span v-if="r.sub" class="block text-xs">{{ r.sub }}</span></dt>
                                <dd class="text-right text-sm font-medium tabular-nums">{{ r.value }}</dd>
                                <dd class="text-right text-xs tabular-nums"
                                    :class="!r.delta || r.delta.tone === 'flat' ? 'text-muted-foreground' : r.delta.tone === 'pos' ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                                    {{ r.delta ? r.delta.text : '—' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="flex min-w-0 flex-col lg:col-span-5">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="text-sm font-medium">{{ supportsRpm ? 'RPM, last 14 days' : 'Revenue, last 14 days' }}</h3>
                            <span v-if="supportsRpm" class="flex items-center gap-3 text-xs text-muted-foreground">
                                <span class="flex items-center gap-1.5"><span class="rpt-dash" :style="{ color: WATCH }" />watch</span>
                                <span class="flex items-center gap-1.5"><span class="rpt-dash" :style="{ color: ALERT }" />alert</span>
                            </span>
                        </div>
                        <div class="relative mt-3 h-44 lg:h-auto lg:min-h-44 lg:flex-1">
                            <Bar :data="(stripChart as any)" :options="(stripOptions as any)" />
                        </div>
                    </div>
                </section>
                <section v-else class="rpt-panel p-5">
                    <h2 class="text-sm text-muted-foreground">Latest day</h2>
                    <p class="mt-2 text-sm">No data for {{ siteName }} in this range yet. Upload files, or pick a wider range.</p>
                </section>

                <!-- This range, as one strip -->
                <section aria-labelledby="range-title">
                    <h2 id="range-title" class="mb-2 text-sm font-medium">
                        This range <span class="font-normal text-muted-foreground tabular-nums">· {{ days.length ? fmtDayShort(days[0].dateKey) + ' – ' + fmtDayShort(days[days.length - 1].dateKey) : 'no days' }}</span>
                    </h2>
                    <div class="rpt-panel rpt-strip grid grid-cols-2 lg:grid-flow-col lg:auto-cols-fr lg:grid-cols-none">
                        <div class="rpt-cell">
                            <div class="text-xs text-muted-foreground">Revenue</div>
                            <div class="mt-1 text-2xl font-semibold tabular-nums tracking-tight">{{ eur(partnerTotals.grand) }}</div>
                            <div class="mt-0.5 text-xs text-muted-foreground tabular-nums">{{ days.length }} day{{ days.length === 1 ? '' : 's' }}</div>
                        </div>
                        <div class="rpt-cell">
                            <div class="text-xs text-muted-foreground">Average a day</div>
                            <div class="mt-1 text-2xl font-semibold tabular-nums tracking-tight">{{ eur(avgDaily) }}</div>
                        </div>
                        <div v-if="supportsImpressions" class="rpt-cell">
                            <div class="text-xs text-muted-foreground">Impressions sold</div>
                            <div class="mt-1 text-2xl font-semibold tabular-nums tracking-tight">{{ num(impressionsSoldTotal) }}</div>
                        </div>
                        <div v-if="supportsRpm" class="rpt-cell">
                            <div class="text-xs text-muted-foreground">RPM, whole range</div>
                            <div class="mt-1 text-2xl font-semibold tabular-nums tracking-tight"
                                :class="blendedRpmTier === 'red' ? 'text-red-600 dark:text-red-400' : blendedRpmTier === 'amber' ? 'text-amber-600 dark:text-amber-400' : ''">
                                {{ blendedRpm === null ? '—' : blendedRpm.toFixed(2) }}
                            </div>
                            <div class="mt-0.5 text-xs text-muted-foreground">€ per 1,000 pageviews</div>
                        </div>
                        <div class="rpt-cell">
                            <div class="text-xs text-muted-foreground">Best day</div>
                            <div class="mt-1 text-2xl font-semibold tabular-nums tracking-tight">{{ bestDay ? eur(bestDay.total) : '—' }}</div>
                            <div v-if="bestDay" class="mt-0.5 truncate text-xs text-muted-foreground">{{ fmtDay(bestDay.dateKey) }} · {{ bestDay.partner }}</div>
                        </div>
                    </div>
                </section>

                <!-- Revenue per day + by partner -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <section class="rpt-panel flex min-w-0 flex-col p-5 lg:col-span-2" aria-labelledby="daily-title">
                        <div class="flex items-baseline justify-between gap-3">
                            <h2 id="daily-title" class="text-sm font-medium">Revenue per day</h2>
                            <span v-if="latestDay" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <span class="h-2.5 w-2.5" :style="{ background: HUE }" /> {{ fmtDayShort(latestDay.dateKey) }}, latest day
                            </span>
                        </div>
                        <div class="relative mt-3 min-h-64 flex-1">
                            <Bar :data="revenueChart" :options="(revenueOptions as any)" />
                        </div>
                    </section>
                    <section class="rpt-panel min-w-0 p-5" aria-labelledby="partner-title">
                        <div class="flex items-baseline justify-between gap-3">
                            <h2 id="partner-title" class="text-sm font-medium">Revenue by partner</h2>
                            <span class="text-xs text-muted-foreground tabular-nums">{{ eur(partnerTotals.grand) }}</span>
                        </div>
                        <ul class="mt-3 flex flex-col">
                            <li v-for="(p, i) in partnerBreakdown" :key="p.key" class="py-1.5">
                                <div class="flex items-baseline justify-between gap-3 text-sm">
                                    <span class="truncate" :class="i === 0 ? 'font-medium' : ''">{{ p.label }}</span>
                                    <span class="shrink-0 tabular-nums">
                                        <span class="mr-2 text-xs text-muted-foreground">{{ p.pct.toFixed(1) }}%</span>{{ eur(p.total) }}
                                    </span>
                                </div>
                                <div class="mt-1.5 mb-1 h-[3px]">
                                    <div class="h-full rounded-full" :class="i === 0 ? 'bg-foreground' : 'bg-muted-foreground/30'"
                                        :style="{ width: (p.total / (partnerBreakdown[0]?.total || 1)) * 100 + '%' }" />
                                </div>
                            </li>
                        </ul>
                        <p v-if="!partnerBreakdown.length" class="mt-3 rounded border border-dashed border-[var(--rpt-border)] px-3 py-2.5 text-sm text-muted-foreground">No revenue in this range.</p>
                    </section>
                </div>
            </div>

            <!-- TABLE -->
            <Card class="rpt-glass" v-show="activeTab === 'impressions' || activeTab === 'revenue'">
                <CardContent class="overflow-x-auto pt-6">
                    <!-- Export the table data itself (current site + date range).
                         Separate from the file "Download" — this is the computed figures. -->
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <div class="text-xs text-muted-foreground">
                            {{ tableMetric === 'revenue' ? 'Revenue (€)' : 'Impressions' }} · {{ days.length }} day{{ days.length === 1 ? '' : 's' }}<span v-if="from || to"> · {{ from || '…' }} → {{ to || '…' }}</span>
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
                                <th class="cursor-pointer select-none px-1.5 py-2" title="Sort by date" @click="toggleDateSort">
                                    <span class="inline-flex items-center gap-1">
                                        Date
                                        <ChevronUp v-if="dateSort === 'asc'" class="h-3 w-3" />
                                        <ChevronDown v-else class="h-3 w-3" />
                                    </span>
                                </th>
                                <th v-for="p in displayPartners" :key="p.key" class="px-1.5 py-2 text-right leading-tight">
                                    <template v-if="p.lines">
                                        {{ p.lines[0] }}<br>{{ p.lines[1] }}
                                    </template>
                                    <template v-else>{{ p.label }}</template>
                                </th>
                                <th class="px-1.5 py-2 text-right">Total</th>
                                <th v-if="supportsRpm && tableMetric === 'impressions'" class="px-1.5 py-2 text-right">RPM</th>
                                <th v-if="supportsImpressions && tableMetric === 'impressions'" class="px-1.5 py-2 text-right leading-tight">Adhese<br>impr.</th>
                                <th v-if="supportsImpressions && tableMetric === 'impressions'" class="px-1.5 py-2 text-right leading-tight">Impr.<br>sold</th>
                                <th v-if="supportsImpressions && tableMetric === 'impressions'" class="px-1.5 py-2 text-right leading-tight">Ad<br>requests</th>
                                <th class="px-1.5 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in tableDays" :key="d.dateKey" class="cursor-pointer border-b last:border-0"
                                :class="[rpmRowClass(d), selectedRow === d.dateKey ? 'rpt-row-selected' : '']"
                                @click="toggleRow(d.dateKey)">
                                <td class="px-1.5 py-1 font-medium">{{ d.dateKey }}</td>
                                <td v-for="p in displayPartners" :key="p.key" class="px-1.5 py-1 text-right" :class="{ 'text-muted-foreground': tableMetric === 'revenue' ? !(d.revenue?.[p.key]) : !(d.impressions?.[p.key]) }">
                                    {{ tableMetric === 'revenue' ? (d.revenue?.[p.key] ?? 0).toFixed(2) : num(d.impressions?.[p.key] ?? 0) }}
                                </td>
                                <td class="px-1.5 py-1 text-right font-semibold">
                                    {{ tableMetric === 'revenue'
                                        ? PARTNERS.reduce((t, p) => t + (d.revenue?.[p.key] ?? 0), 0).toFixed(2)
                                        : num(PARTNERS.reduce((t, p) => t + (d.impressions?.[p.key] ?? 0), 0)) }}
                                </td>
                                <td v-if="supportsRpm && tableMetric === 'impressions'" class="px-1.5 py-1 text-right tabular-nums">
                                    {{ rpmFor(d) === null ? '—' : rpmFor(d)!.toFixed(2) }}
                                </td>
                                <td v-if="supportsImpressions && tableMetric === 'impressions'" class="px-1.5 py-1 text-right">
                                    <Input v-model.number="d.impressions.adhese" type="number" :class="['ml-auto h-7 w-24 px-1.5 text-right text-[11px] leading-none', (d.revenue?.adhese ?? 0) > 0 && d.impressions?.adhese == null ? 'ring-1 ring-amber-400 focus-visible:ring-amber-400' : '']" @change="saveAdhese(d)" />
                                </td>
                                <td v-if="supportsImpressions && tableMetric === 'impressions'" class="px-1.5 py-1 text-right">{{ num(d.impressionsSold || 0) }}</td>
                                <td v-if="supportsImpressions && tableMetric === 'impressions'" class="px-1.5 py-1 text-right">{{ num(d.totalAdRequests || 0) }}</td>
                                <td class="px-1.5 py-1 text-right">
                                    <button class="text-muted-foreground transition hover:text-red-500" title="Delete day" @click.stop="deleteDay(d)"><Trash2 class="h-3.5 w-3.5" /></button>
                                </td>
                            </tr>
                            <!-- Totals row -->
                            <tr v-if="days.length" class="border-t-2 bg-muted/30 font-semibold">
                                <td class="px-1.5 py-1.5 text-xs uppercase tracking-wide text-muted-foreground">Total</td>
                                <td v-for="p in displayPartners" :key="p.key" class="px-1.5 py-1.5 text-right">
                                    {{ tableMetric === 'revenue' ? partnerTotals.totals[p.key].toFixed(2) : num(partnerTotals.impTotals[p.key]) }}
                                </td>
                                <td class="px-1.5 py-1.5 text-right">{{ tableMetric === 'revenue' ? partnerTotals.grand.toFixed(2) : num(partnerTotals.impGrand) }}</td>
                                <td v-if="supportsRpm && tableMetric === 'impressions'" class="px-1.5 py-1.5 text-right tabular-nums">{{ blendedRpm === null ? '—' : blendedRpm.toFixed(2) }}</td>
                                <td v-if="supportsImpressions && tableMetric === 'impressions'" class="px-1.5 py-1.5 text-right">—</td>
                                <td v-if="supportsImpressions && tableMetric === 'impressions'" class="px-1.5 py-1.5 text-right">{{ num(days.reduce((t, d) => t + (d.impressionsSold || 0), 0)) }}</td>
                                <td v-if="supportsImpressions && tableMetric === 'impressions'" class="px-1.5 py-1.5 text-right">{{ num(days.reduce((t, d) => t + (d.totalAdRequests || 0), 0)) }}</td>
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
                    Verify {{ MONTHLY_VERIFY_SITES.includes(selectedSite) ? 'monthly' : 'weekly' }} report — {{ sites.find((s) => s.id === selectedSite)?.name }}
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
                <Card class="rpt-glass rpt-modal w-full max-w-2xl">
                    <CardHeader class="flex flex-row items-center justify-between gap-2 pb-2">
                        <div class="flex items-center gap-2"><Link2 class="h-5 w-5 text-[#e2483d]" /><span class="font-medium">Report sources</span><span class="rounded-full bg-muted px-2 py-0.5 text-[11px] tabular-nums text-muted-foreground">{{ reportLinks.length }}</span></div>
                        <div class="flex items-center gap-2">
                            <Button class="rounded-full" variant="outline" size="sm" @click="showAddLink = !showAddLink"><Plus class="mr-1 h-4 w-4" /> Add</Button>
                            <button class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" title="Close" @click="showLinks = false"><X class="h-4 w-4" /></button>
                        </div>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3">
                        <p class="text-xs text-muted-foreground">Open a partner's page in a new tab to download its report.</p>

                        <div class="grid max-h-[60vh] gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
                            <div v-for="(l, i) in reportLinks" :key="i"
                                class="group relative flex items-center rounded-xl border bg-card transition hover:border-[#e2483d]/50 hover:shadow-sm">
                                <a :href="l.url" target="_blank" rel="noopener noreferrer" class="flex min-w-0 flex-1 items-center gap-3 px-3 py-2.5">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-lg border bg-white">
                                        <img :src="linkFavicon(l.url)" :alt="l.label" class="h-5 w-5"
                                            loading="lazy" @error="(($event.target as HTMLImageElement).style.visibility = 'hidden')" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium">{{ l.label }}</span>
                                        <span class="block truncate text-[11px] text-muted-foreground">{{ linkHost(l.url) }}</span>
                                    </span>
                                    <ExternalLink class="h-3.5 w-3.5 shrink-0 text-muted-foreground opacity-0 transition group-hover:opacity-100" />
                                </a>
                                <button class="absolute right-1.5 top-1.5 rounded-md bg-card/80 p-0.5 text-muted-foreground opacity-0 transition hover:text-red-500 group-hover:opacity-100"
                                    title="Remove" @click="removeLink(i)"><X class="h-3.5 w-3.5" /></button>
                            </div>
                            <p v-if="!reportLinks.length" class="col-span-full py-6 text-center text-sm text-muted-foreground">No sources yet — add one.</p>
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
                <Card class="rpt-glass rpt-modal w-full max-w-2xl">
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

            <!-- "How it's calculated" reference panel -->
            <div v-if="showFormulas" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showFormulas = false">
                <Card class="rpt-glass rpt-modal flex max-h-[88vh] w-full max-w-3xl flex-col overflow-hidden p-0">
                    <div class="flex items-center justify-between gap-2 border-b px-6 py-4">
                        <span class="flex items-center gap-2 font-medium"><Calculator class="h-5 w-5 text-[#e2483d]" /> How it's calculated</span>
                        <button class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="showFormulas = false"><X class="h-4 w-4" /></button>
                    </div>

                    <div class="flex flex-1 flex-col gap-6 overflow-y-auto px-6 py-5 text-sm">
                        <p class="text-[13px] text-muted-foreground">
                            Every figure on this page is derived from the uploaded partner files — nothing is estimated or invented.
                            Values are read from each partner's own export and summed per day. This reflects the current settings live.
                        </p>

                        <!-- Revenue -->
                        <section class="flex flex-col gap-2">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Revenue (per partner, per day)</h3>
                            <p class="text-[13px]">Read straight from each partner's uploaded export and summed by date. Source column per partner:</p>
                            <div class="overflow-x-auto rounded-lg border">
                                <table class="w-full text-[13px]">
                                    <thead class="border-b bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                        <tr><th class="px-3 py-2">Partner</th><th class="px-3 py-2">Source column</th></tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr><td class="px-3 py-1.5 font-medium">SeedTag</td><td class="px-3 py-1.5">"Estimated Earnings in EUR"</td></tr>
                                        <tr><td class="px-3 py-1.5 font-medium">Teads</td><td class="px-3 py-1.5">"Revenue" (report_finance)</td></tr>
                                        <tr><td class="px-3 py-1.5 font-medium">Showheroes</td><td class="px-3 py-1.5">"Premium Revenue"</td></tr>
                                        <tr><td class="px-3 py-1.5 font-medium">GAM</td><td class="px-3 py-1.5">"AdSense revenue" + "Ad Exchange revenue"</td></tr>
                                        <tr><td class="px-3 py-1.5 font-medium">Adform</td><td class="px-3 py-1.5">revenue column (auto-detected)</td></tr>
                                        <tr><td class="px-3 py-1.5 font-medium">Ogury</td><td class="px-3 py-1.5">"Revenues" × rate (<b>{{ oguryRate }}</b>) — the only adjusted partner</td></tr>
                                        <tr><td class="px-3 py-1.5 font-medium">Adhese</td><td class="px-3 py-1.5">Adhese export, summed per site</td></tr>
                                        <tr><td class="px-3 py-1.5 font-medium">Outbrain</td><td class="px-3 py-1.5">"Revenue"</td></tr>
                                        <tr><td class="px-3 py-1.5 font-medium">Preferred Deals</td><td class="px-3 py-1.5">GAM preferred-deals file</td></tr>
                                        <tr><td class="px-3 py-1.5 font-medium">Adsense</td><td class="px-3 py-1.5">AdSense export "Estimated earnings", per site by host</td></tr>
                                        <tr><td class="px-3 py-1.5 font-medium">Gumgum</td><td class="px-3 py-1.5">GumGum export "Publisher Currency Revenue", per site by host</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="rounded-md bg-muted/40 px-3 py-2 font-mono text-[12px]">Day total revenue = sum of all partner revenues that day</p>
                        </section>

                        <!-- Impressions -->
                        <section class="flex flex-col gap-2 border-t pt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Impressions</h3>
                            <p class="text-[13px]">Each partner's impressions come from its file's impressions column. Two special cases:</p>
                            <ul class="ml-4 list-disc text-[13px] text-muted-foreground">
                                <li><span class="text-foreground">GAM impressions</span> = "Ad Exchange impressions" + "AdSense impressions"</li>
                                <li><span class="text-foreground">Adhese impressions</span> are entered <b>manually</b> (never read from a file)</li>
                            </ul>
                            <p class="rounded-md bg-muted/40 px-3 py-2 font-mono text-[12px]">Impressions Sold = seedtag + teads + showheroes + gam + adform + ogury + outbrain + adhese + preferredDeals + adsense + gumgum (impressions)</p>
                        </section>

                        <!-- Ad requests -->
                        <section class="flex flex-col gap-2 border-t pt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total Ad Requests</h3>
                            <p class="rounded-md bg-muted/40 px-3 py-2 font-mono text-[12px]">Total Ad Requests = value from the GAM ad-requests export ("Copy of {site}")</p>
                        </section>

                        <!-- RPM -->
                        <section class="flex flex-col gap-2 border-t pt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">RPM (revenue per mille)</h3>
                            <p class="rounded-md bg-muted/40 px-3 py-2 font-mono text-[12px]">RPM = (day total revenue ÷ analytics pageviews) × 1000</p>
                            <p class="rounded-md bg-muted/40 px-3 py-2 font-mono text-[12px]">Blended RPM (range) = (total revenue ÷ total pageviews) × 1000</p>
                            <p class="text-[13px] text-muted-foreground">
                                A row/card tints <span class="font-medium text-amber-500">amber ≥ {{ rpmAmber }}</span> and
                                <span class="font-medium text-red-500">red ≥ {{ rpmRed }}</span> (editable in Settings).
                                A high RPM means GA4 pageviews are under-reported — re-upload that day's analytics file.
                                Revenue present but zero pageviews also flags red.
                            </p>
                        </section>

                        <!-- Verify -->
                        <section class="flex flex-col gap-2 border-t pt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Verify tab — tolerances vs Planetnine report</h3>
                            <p class="text-[13px]">A day passes when our stored value is within tolerance of the Planetnine report:</p>
                            <ul class="ml-4 list-disc text-[13px] text-muted-foreground">
                                <li>Impressions Sold: <span class="text-foreground">± 50</span></li>
                                <li>Total Ad Requests: <span class="text-foreground">± 10</span></li>
                                <li>Total Revenue &amp; each partner: <span class="text-foreground">± 0.5</span></li>
                            </ul>
                        </section>

                        <p class="border-t pt-4 text-[12px] text-muted-foreground">
                            Pipeline: upload → detect partner by filename → read the columns above → keep current month + last 7 days →
                            merge into the day (a partner missing from a run keeps its previous value, never zeroed) → save.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t px-6 py-4">
                        <Button class="rounded-full" @click="showFormulas = false">Close</Button>
                    </div>
                </Card>
            </div>

            <!-- Settings modal -->
            <div v-if="showSettings" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showSettings = false">
                <Card class="rpt-glass rpt-modal flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden p-0">
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

            <!-- Adhese impressions batch modal — fires after process/sync for any gap
                 on F1Maximaal or Topgear, regardless of which tab is active -->
            <div v-if="showAdheseModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <Card class="rpt-glass rpt-modal w-full max-w-xl">
                    <CardHeader class="flex flex-row items-center justify-between gap-2 pb-2">
                        <span class="font-medium">Adhese impressions</span>
                        <button class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" @click="showAdheseModal = false"><X class="h-4 w-4" /></button>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3">
                        <p class="text-sm text-muted-foreground">
                            {{ adheseEntries.length }} day{{ adheseEntries.length === 1 ? '' : 's' }} need an Adhese impression count.
                        </p>
                        <div class="max-h-72 overflow-auto">
                            <table class="w-full border-collapse text-sm">
                                <thead>
                                    <tr class="border-b">
                                        <th class="px-2 py-1.5 text-left text-xs font-medium text-muted-foreground">Date</th>
                                        <th v-for="col in adheseSiteCols" :key="col.id" class="px-2 py-1.5 text-center text-xs font-medium text-muted-foreground">{{ col.name }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in adheseGrid" :key="row.dateKey" class="border-b last:border-0">
                                        <td class="whitespace-nowrap px-2 py-1 font-mono text-xs text-muted-foreground">{{ row.dateKey }}</td>
                                        <td v-for="cell in row.cells" :key="cell.siteId" class="px-1 py-1">
                                            <Input v-if="cell.entry" v-model.number="cell.entry.adhese" type="number" placeholder="0" class="w-full text-center" />
                                            <span v-else class="block text-center text-muted-foreground">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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

        <!-- Upload modal. Shows only what still matters: the files still missing
             and the files added, never a full checklist. -->
        <div v-if="showUpload" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="!processing && (showUpload = false)">
            <section class="rpt-modal flex max-h-[calc(100dvh-2rem)] w-full max-w-2xl flex-col rounded-lg" role="dialog" aria-modal="true" aria-labelledby="upload-title">
                <header class="flex items-center justify-between gap-3 px-5 pb-3 pt-4">
                    <h2 id="upload-title" class="flex items-center gap-2 text-base font-medium">
                        <Upload class="h-4 w-4 text-muted-foreground" /> Upload files
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="rpt-pill tabular-nums" :class="checkedCount === requiredCount ? 'rpt-pill-pos' : 'bg-muted text-muted-foreground'">
                            {{ checkedCount }} of {{ requiredCount }} added
                        </span>
                        <button v-if="!processing" class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground" aria-label="Close" @click="showUpload = false"><X class="h-4 w-4" /></button>
                    </div>
                </header>

                <div class="flex min-h-0 flex-col gap-4 overflow-y-auto px-5 pb-4">
                    <input ref="fileInput" type="file" multiple class="hidden" @change="onFilesChosen" />
                    <div class="flex items-center justify-center rounded-lg border-2 border-dashed px-4 py-4 text-sm transition"
                        :class="dragging ? 'border-[#e2483d] bg-[#e2483d]/5 text-foreground' : 'border-[var(--rpt-border)] text-muted-foreground'"
                        @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="onDrop">
                        <span>Drop the reports here, or
                            <button type="button" class="font-medium text-foreground underline underline-offset-2" @click="fileInput?.click()">browse</button>
                        </span>
                    </div>

                    <div v-if="missingItems.length">
                        <h3 class="text-sm font-medium">Still missing <span class="font-normal text-muted-foreground tabular-nums">· {{ missingItems.length }}</span></h3>
                        <ul class="mt-2 flex flex-wrap gap-1.5">
                            <li v-for="m in missingItems" :key="m.key" class="rpt-chip text-muted-foreground">{{ m.label }}</li>
                        </ul>
                    </div>
                    <p v-else class="flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                        <CheckCircle2 class="h-4 w-4" /> All {{ requiredCount }} files added
                    </p>

                    <div v-if="addedFiles.length">
                        <h3 class="text-sm font-medium">Added <span class="font-normal text-muted-foreground tabular-nums">· {{ addedFiles.length }}</span></h3>
                        <ul class="mt-2 flex flex-wrap gap-1.5">
                            <li v-for="(a, i) in addedFiles" :key="i" :title="a.file.name"
                                class="rpt-chip" :class="a.label ? '' : 'rpt-pill-neg'">
                                <span class="max-w-[16rem] truncate">{{ a.label ?? a.file.name }}</span>
                                <span v-if="!a.label" class="opacity-80">· not recognized</span>
                                <button class="-mr-1 rounded p-0.5 opacity-60 transition hover:opacity-100" :aria-label="'Remove ' + a.file.name" @click="removeFile(a.file)"><X class="h-3 w-3" /></button>
                            </li>
                        </ul>
                    </div>
                </div>

                <footer class="flex flex-wrap items-center gap-3 border-t border-[var(--rpt-hairline)] px-5 py-3">
                    <!-- Backfill an older month (else only current month + last 7 days is kept) -->
                    <label class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        Backfill month
                        <input v-model="backfillPeriod" type="month" class="h-8 rounded-md border border-[var(--rpt-border)] bg-background px-2 text-xs text-foreground" />
                    </label>
                    <button v-if="backfillPeriod" class="text-xs text-muted-foreground hover:underline" @click="backfillPeriod = ''">reset</button>
                    <div class="ml-auto flex items-center gap-3">
                        <button v-if="selectedUploadFiles.length && !processing" class="text-xs text-muted-foreground hover:underline" @click="selectedUploadFiles = []">Clear</button>
                        <Button class="h-9" :disabled="processing || !selectedUploadFiles.length" @click="processFiles">
                            <Loader2 v-if="processing" class="h-4 w-4 animate-spin" />
                            Process {{ selectedUploadFiles.length }} file{{ selectedUploadFiles.length === 1 ? '' : 's' }}
                        </Button>
                    </div>
                </footer>
            </section>
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
    --rpt-track:         #f0f0f1;

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
    --rpt-track:         #16161b;
}

.rpt-root :focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px var(--rpt-bg), 0 0 0 4px var(--rpt-accent);
    border-radius: inherit;
}

.rpt-glass {
    background: var(--rpt-surface);
    border: 1px solid var(--rpt-border);
}

/* ---------- Summary building blocks: flat panels, one tone step, 1px edges. */
.rpt-panel {
    background: var(--rpt-surface);
    border: 1px solid var(--rpt-border);
    border-radius: 8px;
}
/* Segmented control: a grey track, the selected option a white chip. */
.rpt-track {
    padding: 3px;
    border-radius: 8px;
    background: var(--rpt-track);
    scrollbar-width: none;
}
.rpt-track::-webkit-scrollbar { display: none; }
.rpt-seg {
    flex: none;
    height: 28px;
    padding: 0 10px;
    border-radius: 6px;
    font-size: 13px;
    white-space: nowrap;
    color: var(--rpt-text-muted);
    transition: color 120ms, background-color 120ms;
}
.rpt-seg:hover { color: var(--rpt-text); }
.rpt-seg-on {
    background: var(--rpt-surface);
    color: var(--rpt-text);
    font-weight: 500;
    box-shadow: 0 0 0 1px var(--rpt-border);
}
/* Pale status pairs: tint with dark ink of the same hue. */
.rpt-pill {
    display: inline-flex;
    align-items: center;
    height: 22px;
    padding: 0 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
}
.rpt-pill-pos  { background: #dcfae5; color: #04250e; }
.rpt-pill-warn { background: #fef3c7; color: #92400e; }
.rpt-pill-neg  { background: #ffedea; color: #831c1e; }
.dark .rpt-pill-pos  { background: #172a1d; color: #3fc07a; }
.dark .rpt-pill-warn { background: #2b2210; color: #f5b94b; }
.dark .rpt-pill-neg  { background: #2c1a20; color: #e5566f; }
/* A small file chip in the upload modal. */
.rpt-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 26px;
    padding: 0 8px;
    border-radius: 4px;
    font-size: 12px;
    background: var(--rpt-track);
    white-space: nowrap;
}
/* Legend swatch for a dashed threshold line: the mark itself, scaled down. */
.rpt-dash {
    display: inline-block;
    width: 14px;
    border-top: 1.5px dashed currentColor;
}
/* Underline tabs on a full-width rule; the rule is an inset shadow so the
   active underline paints over it without overflowing the scroll box. */
.rpt-tabs { box-shadow: inset 0 -1px 0 var(--rpt-border); scrollbar-width: none; }
.rpt-tabs::-webkit-scrollbar { display: none; }
/* One container split by 1px dividers into equal cells. */
.rpt-cell { padding: 16px 20px; min-width: 0; }
.rpt-strip > .rpt-cell + .rpt-cell { border-left: 1px solid var(--rpt-hairline); }
@media (max-width: 1023px) {
    .rpt-strip > .rpt-cell:nth-child(odd) { border-left: 0; }
    .rpt-strip > .rpt-cell:nth-child(n + 3) { border-top: 1px solid var(--rpt-hairline); }
    .rpt-strip > .rpt-cell:last-child:nth-child(odd) { grid-column: 1 / -1; }
}

/* Modal dialogs sit on a dark overlay, so they must be SOLID — the translucent
   glass surface let the page bleed through and washed out low-opacity hover
   fills (they blended into the background). Opaque surface + shadow fixes it. */
.rpt-modal {
    background: var(--rpt-surface);
    border: 1px solid var(--rpt-border);
    box-shadow: 0 24px 60px -12px rgba(0, 0, 0, 0.35);
}
.dark .rpt-modal {
    box-shadow: 0 24px 60px -12px rgba(0, 0, 0, 0.65);
}
/* Give hover fills a visible, accent-tinted state on the solid surface so they
   read clearly instead of dissolving into the panel. */
.rpt-modal .hover\:bg-muted:hover,
.rpt-modal [class*="hover:bg-muted"]:hover {
    background-color: var(--rpt-accent-soft) !important;
}

.rpt-label {
    font-size: 12px;
    font-weight: 500;
    color: var(--rpt-text-muted);
}

/* Click-to-highlight table row (Table tab). */
.rpt-row-selected {
    background: var(--rpt-accent-soft) !important;
    box-shadow: inset 3px 0 0 0 var(--rpt-accent);
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
