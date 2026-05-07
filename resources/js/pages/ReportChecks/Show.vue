<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ShieldCheck, ShieldAlert, ShieldX, Trash2, ArrowLeft, FileText, ChevronDown, ChevronRight, Lightbulb, FileInput, Copy } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

interface ExplanationComponent { label: string; value: number | string | null; ref?: string | null; currency?: string | null; }
interface Explanation {
    why: string;
    formula?: string;
    components?: ExplanationComponent[];
    sources?: string[];
    hint?: string | null;
}
interface Issue {
    id: number;
    sheet: string;
    cell_ref: string | null;
    kind: string;
    severity: 'minor' | 'major';
    expected: number | null;
    found: number | null;
    delta: number | null;
    message: string;
    explanation: Explanation | null;
}
interface Revenue { id: number; date: string; partner: string; section: string; revenue_eur: string; revenue_local: string | null; currency_local: string | null; }
interface FileRow { id: number; source_key: string; filename: string; sha256: string; parsed_row_count: number; }
interface Check {
    id: number;
    publisher: string;
    period_start: string;
    period_end: string;
    outcome_filename: string;
    status: string;
    fx_rate_used: number | null;
    error_message: string | null;
    totals_snapshot: { grand: number; by_section: Record<string, number>; by_partner: Record<string, number>; by_date: Record<string, number> } | null;
    uploader: { id: number; name: string; email: string } | null;
    files: FileRow[];
    issues: Issue[];
    revenues: Revenue[];
    created_at: string;
}

const page = usePage();
const check = computed(() => page.props.check as Check);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Check Reports', href: '/reports/checks' },
    { title: `#${check.value.id}`, href: `/reports/checks/${check.value.id}` },
]);

const statusMeta = computed(() => {
    switch (check.value.status) {
        case 'pass':       return { label: 'All checks passed', icon: ShieldCheck, cls: 'border-green-600/40 bg-green-50 dark:bg-green-900/10', text: 'text-green-700 dark:text-green-300' };
        case 'fail_minor': return { label: 'Minor discrepancies found', icon: ShieldAlert, cls: 'border-yellow-600/40 bg-yellow-50 dark:bg-yellow-900/10', text: 'text-yellow-700 dark:text-yellow-300' };
        case 'fail_major': return { label: 'Calculation errors found', icon: ShieldX, cls: 'border-[#D71921]/40 bg-[#D71921]/5', text: 'text-[#D71921]' };
        case 'error':      return { label: 'Check failed to run', icon: ShieldX, cls: 'border-[#D71921]/40 bg-[#D71921]/5', text: 'text-[#D71921]' };
        default:           return { label: 'Pending', icon: ShieldAlert, cls: 'border-[#E8E8E8] dark:border-[#222222]', text: 'text-[#666666] dark:text-[#999999]' };
    }
});

const fmtEur = (v: number | string | null) => {
    if (v === null) return '—';
    const n = typeof v === 'string' ? parseFloat(v) : v;
    return new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' }).format(n);
};
const fmtNum = (v: number | string | null) => {
    if (v === null) return '—';
    const n = typeof v === 'string' ? parseFloat(v) : v;
    return new Intl.NumberFormat('en-US').format(n);
};
const fmtDate = (s: string) => new Date(s).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });

const issuesBySeverity = computed(() => {
    const major: Issue[] = [];
    const minor: Issue[] = [];
    for (const i of check.value.issues ?? []) (i.severity === 'major' ? major : minor).push(i);
    return { major, minor };
});

// Track which issue rows are expanded so users can reveal explanations on demand.
const expanded = ref<Set<number>>(new Set());
const toggleExpand = (id: number) => {
    if (expanded.value.has(id)) expanded.value.delete(id);
    else expanded.value.add(id);
    expanded.value = new Set(expanded.value); // trigger reactivity
};

const fmtComponent = (v: number | string | null, currency?: string | null): string => {
    if (v === null) return '—';
    const n = typeof v === 'string' ? parseFloat(v) : v;
    if (Number.isNaN(n)) return String(v);
    if (currency === 'USD') return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(n);
    // Heuristic: small fractional numbers render as plain (rates, ratios); larger numbers as integers; in-between as EUR.
    if (Math.abs(n) < 10 && !Number.isInteger(n)) return n.toFixed(4);
    if (Number.isInteger(n) && Math.abs(n) >= 1000) return new Intl.NumberFormat('en-US').format(n);
    return new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' }).format(n);
};

// Build a paste-ready plain-text summary of an issue. Designed to be readable
// in Slack/email/Notion without any markup — just text columns and dashes.
const buildIssueText = (i: Issue): string => {
    const lines: string[] = [];
    const cellRef = i.cell_ref ? `${i.sheet}!${i.cell_ref}` : i.sheet;
    const sevLabel = i.severity === 'major' ? 'MAJOR' : 'MINOR';

    lines.push(`[${sevLabel}] ${cellRef}`);
    lines.push('─'.repeat(48));
    lines.push(i.message);
    lines.push('');
    if (i.expected !== null) lines.push(`Expected : ${fmtNum(i.expected)}`);
    if (i.found    !== null) lines.push(`Found    : ${fmtNum(i.found)}`);
    if (i.delta    !== null) lines.push(`Δ        : ${(i.delta > 0 ? '+' : '') + fmtNum(i.delta)}`);

    if (i.explanation) {
        lines.push('');
        lines.push('Why this value should be:');
        lines.push(`  ${i.explanation.why}`);

        if (i.explanation.components && i.explanation.components.length) {
            lines.push('');
            lines.push('Made up of:');
            for (const c of i.explanation.components) {
                const ref = c.ref ? `  [${c.ref}]` : '';
                lines.push(`  · ${c.label} = ${fmtComponent(c.value ?? null, c.currency)}${ref}`);
            }
        }

        if (i.explanation.sources && i.explanation.sources.length) {
            lines.push('');
            lines.push(`Source file${i.explanation.sources.length > 1 ? 's' : ''}: ${i.explanation.sources.join(', ')}`);
        }

        if (i.explanation.hint) {
            lines.push('');
            lines.push('Where to look:');
            lines.push(`  ${i.explanation.hint}`);
        }
    }

    lines.push('');
    lines.push(`— Check #${check.value.id} · ${check.value.publisher} · ${fmtDate(check.value.period_start)} – ${fmtDate(check.value.period_end)}`);

    return lines.join('\n');
};

const copyIssue = async (i: Issue) => {
    const text = buildIssueText(i);
    try {
        await navigator.clipboard.writeText(text);
        Swal.fire({
            toast: true, position: 'top-end',
            icon: 'success', title: 'Copied to clipboard',
            timer: 1500, showConfirmButton: false,
        });
    } catch {
        Swal.fire({ icon: 'error', title: 'Copy failed', text: 'Your browser blocked clipboard access. Try selecting the text manually.' });
    }
};

const truthByDate = computed(() => {
    const map: Record<string, Record<string, Record<string, number>>> = {};
    for (const r of check.value.revenues ?? []) {
        // Laravel serializes the `date` cast as a full ISO datetime (`2026-05-01T00:00:00...Z`)
        // but `totals_snapshot.by_date` uses `Y-m-d`. Slice to date-only so the keys line up.
        const day = (map[r.date.substring(0, 10)] ??= {});
        const sec = (day[r.section] ??= {});
        sec[r.partner] = parseFloat(r.revenue_eur);
    }
    return map;
});

const partners = computed(() => Object.keys(check.value.totals_snapshot?.by_partner ?? {}).sort());
const dates = computed(() => Object.keys(check.value.totals_snapshot?.by_date ?? {}).sort());
const sections = computed(() => ['display', 'sticky', 'inarticle', 'interscroller']);

const partnerSectionTotal = (section: string, partner: string) => {
    let sum = 0;
    for (const d of dates.value) sum += truthByDate.value[d]?.[section]?.[partner] ?? 0;
    return sum;
};

const deleteCheck = async () => {
    const result = await Swal.fire({
        title: 'Delete this check?',
        text: 'The validation result and all linked issues will be removed.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });
    if (!result.isConfirmed) return;
    router.delete(route('report-checks.destroy', check.value.id), {
        onSuccess: () => Swal.fire('Deleted!', 'Check removed.', 'success'),
    });
};
</script>

<template>
    <Head :title="`Check #${check.id}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">

                <!-- Header -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <Link href="/reports/checks"
                            class="text-[#666666] dark:text-[#999999] hover:text-black hover:dark:text-white transition-colors">
                            <ArrowLeft class="h-5 w-5" stroke-width="1.5" />
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-black dark:text-white uppercase font-mono tracking-wider">
                                Check #{{ check.id }}
                            </h1>
                            <p class="text-xs text-[#666666] dark:text-[#999999] font-mono mt-1">
                                {{ check.outcome_filename }}
                            </p>
                        </div>
                    </div>
                    <button @click="deleteCheck"
                        class="rounded-full px-4 py-2 border-2 border-[#D71921] text-[#D71921] hover:bg-[#D71921] hover:text-white uppercase font-mono tracking-wider text-xs transition-colors">
                        <Trash2 class="mr-1 inline h-4 w-4" stroke-width="1.5" /> Delete
                    </button>
                </div>

                <!-- Status banner -->
                <div :class="['rounded-lg border-2 p-6 flex items-start gap-4', statusMeta.cls]">
                    <component :is="statusMeta.icon" :class="['h-10 w-10 shrink-0', statusMeta.text]" stroke-width="1.5" />
                    <div class="flex-1 min-w-0">
                        <div :class="['text-lg font-bold uppercase font-mono tracking-wider', statusMeta.text]">
                            {{ statusMeta.label }}
                        </div>
                        <p class="text-xs text-[#666666] dark:text-[#999999] mt-1">
                            <span v-if="check.status === 'fail_major'">{{ issuesBySeverity.major.length }} major issue(s) detected.</span>
                            <span v-else-if="check.status === 'fail_minor'">{{ issuesBySeverity.minor.length }} minor discrepancy(ies) within tolerance.</span>
                            <span v-else-if="check.status === 'pass'">All {{ partners.length * dates.length * sections.length }}+ cells match the recomputed truth.</span>
                            <span v-else-if="check.status === 'error'">{{ check.error_message }}</span>
                        </p>
                    </div>
                </div>

                <!-- Meta cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4 bg-white dark:bg-[#111111]">
                        <div class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">Period</div>
                        <div class="mt-1 text-sm font-mono text-black dark:text-white">{{ fmtDate(check.period_start) }} – {{ fmtDate(check.period_end) }}</div>
                    </div>
                    <div class="rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4 bg-white dark:bg-[#111111]">
                        <div class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">Publisher</div>
                        <div class="mt-1 text-sm font-mono text-black dark:text-white capitalize">{{ check.publisher }}</div>
                    </div>
                    <div class="rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4 bg-white dark:bg-[#111111]">
                        <div class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">FX (USD→EUR)</div>
                        <div class="mt-1 text-sm font-mono text-black dark:text-white">{{ check.fx_rate_used ? Number(check.fx_rate_used).toFixed(4) : '—' }}</div>
                    </div>
                    <div class="rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4 bg-white dark:bg-[#111111]">
                        <div class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">Grand total</div>
                        <div class="mt-1 text-sm font-mono text-black dark:text-white">{{ fmtEur(check.totals_snapshot?.grand ?? null) }}</div>
                    </div>
                </div>

                <!-- Issues -->
                <div v-if="check.issues.length"
                    class="rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#111111] overflow-hidden">
                    <div class="px-4 py-3 bg-[#F5F5F5] dark:bg-[#0A0A0A] border-b-2 border-[#E8E8E8] dark:border-[#222222]">
                        <h2 class="text-sm font-bold text-black dark:text-white uppercase font-mono tracking-wider">Issues ({{ check.issues.length }})</h2>
                        <p class="text-xs text-[#666666] dark:text-[#999999] mt-1">Click any row to see why the value should be different and where to look.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] bg-[#F5F5F5]/50 dark:bg-[#0A0A0A]/50">
                                <tr class="text-left">
                                    <th class="px-4 py-2 w-6"></th>
                                    <th class="px-4 py-2">Sev</th>
                                    <th class="px-4 py-2">Sheet</th>
                                    <th class="px-4 py-2">Cell</th>
                                    <th class="px-4 py-2">Kind</th>
                                    <th class="px-4 py-2 text-right">Expected</th>
                                    <th class="px-4 py-2 text-right">Found</th>
                                    <th class="px-4 py-2 text-right">Δ</th>
                                    <th class="px-4 py-2">Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="i in [...issuesBySeverity.major, ...issuesBySeverity.minor]" :key="i.id">
                                    <tr @click="toggleExpand(i.id)"
                                        class="border-t border-[#E8E8E8] dark:border-[#222222] cursor-pointer hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] transition-colors">
                                        <td class="px-4 py-2">
                                            <component :is="expanded.has(i.id) ? ChevronDown : ChevronRight"
                                                class="h-4 w-4 text-[#666666] dark:text-[#999999]" stroke-width="1.5" />
                                        </td>
                                        <td class="px-4 py-2">
                                            <span :class="i.severity === 'major' ? 'bg-[#D71921]/10 text-[#D71921]' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300'"
                                                class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold uppercase font-mono">
                                                {{ i.severity }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-black dark:text-white">{{ i.sheet }}</td>
                                        <td class="px-4 py-2 font-mono text-xs text-[#666666] dark:text-[#999999]">{{ i.cell_ref ?? '—' }}</td>
                                        <td class="px-4 py-2 font-mono text-xs text-[#666666] dark:text-[#999999]">{{ i.kind }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ fmtNum(i.expected) }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ fmtNum(i.found) }}</td>
                                        <td class="px-4 py-2 text-right font-mono"
                                            :class="i.delta && i.delta > 0 ? 'text-green-600' : i.delta && i.delta < 0 ? 'text-[#D71921]' : ''">
                                            {{ i.delta !== null ? (i.delta > 0 ? '+' : '') + fmtNum(i.delta) : '—' }}
                                        </td>
                                        <td class="px-4 py-2 text-xs text-[#666666] dark:text-[#999999]">{{ i.message }}</td>
                                    </tr>
                                    <tr v-if="expanded.has(i.id)" :key="`${i.id}-detail`"
                                        class="border-t border-[#E8E8E8] dark:border-[#222222] bg-[#F5F5F5]/50 dark:bg-[#0A0A0A]/50">
                                        <td colspan="9" class="px-6 py-4">
                                            <div class="flex justify-end mb-3">
                                                <button @click.stop="copyIssue(i)" type="button"
                                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 border-2 border-[#CCCCCC] dark:border-[#333333] text-black dark:text-white hover:bg-black hover:text-white hover:dark:bg-white hover:dark:text-black uppercase font-mono tracking-wider text-xs transition-colors">
                                                    <Copy class="h-3.5 w-3.5" stroke-width="1.5" />
                                                    Copy issue
                                                </button>
                                            </div>
                                            <div v-if="i.explanation" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <!-- Why -->
                                                <div class="md:col-span-2 space-y-3">
                                                    <div>
                                                        <div class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">Why this value?</div>
                                                        <div class="text-sm text-black dark:text-white">{{ i.explanation.why }}</div>
                                                    </div>
                                                    <div v-if="i.explanation.components && i.explanation.components.length">
                                                        <div class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-2">Made up of</div>
                                                        <ul class="space-y-1 text-xs font-mono">
                                                            <li v-for="(c, idx) in i.explanation.components" :key="idx"
                                                                class="flex items-baseline justify-between gap-3 px-3 py-1.5 rounded bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222]">
                                                                <span class="text-black dark:text-white">{{ c.label }}</span>
                                                                <span class="flex items-center gap-2 text-[#666666] dark:text-[#999999]">
                                                                    <span class="text-black dark:text-white">{{ fmtComponent(c.value ?? null, c.currency) }}</span>
                                                                    <span v-if="c.ref" class="text-[10px] uppercase tracking-wider rounded border border-[#CCCCCC] dark:border-[#333333] px-1.5 py-0.5">{{ c.ref }}</span>
                                                                </span>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                <!-- Sources + Hint -->
                                                <div class="space-y-3">
                                                    <div v-if="i.explanation.sources && i.explanation.sources.length">
                                                        <div class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-2 flex items-center gap-1">
                                                            <FileInput class="h-3.5 w-3.5" stroke-width="1.5" />
                                                            Source files
                                                        </div>
                                                        <ul class="space-y-1 text-xs font-mono">
                                                            <li v-for="src in i.explanation.sources" :key="src"
                                                                class="px-3 py-1.5 rounded bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] text-black dark:text-white">
                                                                {{ src }}
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <div v-if="i.explanation.hint"
                                                        class="px-3 py-2 rounded border border-yellow-600/40 bg-yellow-50 dark:bg-yellow-900/10">
                                                        <div class="text-xs uppercase font-mono tracking-wider text-yellow-700 dark:text-yellow-300 mb-1 flex items-center gap-1">
                                                            <Lightbulb class="h-3.5 w-3.5" stroke-width="1.5" />
                                                            Where to look
                                                        </div>
                                                        <div class="text-xs text-[#666666] dark:text-[#999999]">{{ i.explanation.hint }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="text-xs text-[#666666] dark:text-[#999999] italic">No additional explanation available.</div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Truth table -->
                <div class="rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#111111] overflow-hidden">
                    <div class="px-4 py-3 bg-[#F5F5F5] dark:bg-[#0A0A0A] border-b-2 border-[#E8E8E8] dark:border-[#222222] flex items-center justify-between">
                        <h2 class="text-sm font-bold text-black dark:text-white uppercase font-mono tracking-wider">Recomputed truth</h2>
                        <span class="text-xs font-mono text-[#666666] dark:text-[#999999]">{{ check.revenues.length }} rows</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] bg-[#F5F5F5]/50 dark:bg-[#0A0A0A]/50">
                                <tr class="text-left">
                                    <th class="px-3 py-2">Section</th>
                                    <th class="px-3 py-2">Date</th>
                                    <th class="px-3 py-2 text-right" v-for="p in partners" :key="p">{{ p }}</th>
                                    <th class="px-3 py-2 text-right border-l-2 border-[#E8E8E8] dark:border-[#222222]">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="section in sections" :key="section">
                                    <tr v-for="(d, i) in dates" :key="`${section}-${d}`" class="border-t border-[#E8E8E8] dark:border-[#222222]">
                                        <td v-if="i === 0" :rowspan="dates.length"
                                            class="px-3 py-2 font-mono text-xs uppercase tracking-wider text-black dark:text-white border-r-2 border-[#E8E8E8] dark:border-[#222222]">
                                            {{ section }}
                                        </td>
                                        <td class="px-3 py-2 font-mono text-xs text-[#666666] dark:text-[#999999]">{{ fmtDate(d) }}</td>
                                        <td v-for="p in partners" :key="p"
                                            class="px-3 py-2 text-right font-mono text-xs"
                                            :class="(truthByDate[d]?.[section]?.[p] ?? 0) === 0 ? 'text-[#999999]' : 'text-black dark:text-white'">
                                            {{ truthByDate[d]?.[section]?.[p] ? fmtEur(truthByDate[d][section][p]) : '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono text-xs font-semibold border-l-2 border-[#E8E8E8] dark:border-[#222222]">
                                            {{ fmtEur(Object.values(truthByDate[d]?.[section] ?? {}).reduce((a, b) => a + b, 0)) }}
                                        </td>
                                    </tr>
                                    <tr class="border-t-2 border-[#E8E8E8] dark:border-[#222222] bg-[#F5F5F5]/40 dark:bg-[#0A0A0A]/40">
                                        <td class="px-3 py-2 font-mono text-xs uppercase tracking-wider text-black dark:text-white border-r-2 border-[#E8E8E8] dark:border-[#222222]"></td>
                                        <td class="px-3 py-2 font-mono text-xs uppercase tracking-wider">Total</td>
                                        <td v-for="p in partners" :key="p" class="px-3 py-2 text-right font-mono text-xs font-semibold">
                                            {{ partnerSectionTotal(section, p) ? fmtEur(partnerSectionTotal(section, p)) : '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono text-xs font-bold border-l-2 border-[#E8E8E8] dark:border-[#222222]">
                                            {{ fmtEur(check.totals_snapshot?.by_section?.[section] ?? 0) }}
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Files audit -->
                <div class="rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#111111] overflow-hidden">
                    <div class="px-4 py-3 bg-[#F5F5F5] dark:bg-[#0A0A0A] border-b-2 border-[#E8E8E8] dark:border-[#222222]">
                        <h2 class="text-sm font-bold text-black dark:text-white uppercase font-mono tracking-wider">Uploaded files</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] bg-[#F5F5F5]/50 dark:bg-[#0A0A0A]/50">
                                <tr class="text-left">
                                    <th class="px-4 py-2">Source</th>
                                    <th class="px-4 py-2">Filename</th>
                                    <th class="px-4 py-2 text-right">Rows</th>
                                    <th class="px-4 py-2">SHA-256</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="f in check.files" :key="f.id" class="border-t border-[#E8E8E8] dark:border-[#222222]">
                                    <td class="px-4 py-2 font-mono uppercase tracking-wider text-xs text-black dark:text-white">{{ f.source_key }}</td>
                                    <td class="px-4 py-2"><FileText class="inline h-3.5 w-3.5 mr-1 text-[#666666] dark:text-[#999999]" stroke-width="1.5" />{{ f.filename }}</td>
                                    <td class="px-4 py-2 text-right font-mono text-xs">{{ fmtNum(f.parsed_row_count) }}</td>
                                    <td class="px-4 py-2 font-mono text-xs text-[#666666] dark:text-[#999999]">{{ f.sha256.substring(0, 16) }}…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
