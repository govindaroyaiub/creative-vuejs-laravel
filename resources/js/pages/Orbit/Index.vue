<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChevronLeft,
    ChevronRight,
    ChevronRight as ChevronRightIcon,
    CirclePlus,
    Copy,
    Eye,
    MousePointerClick,
    Trash2,
    X,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

interface Embed {
    id: number;
    banner_id: number | null;
    banner_name: string | null;
    preview_name: string | null;
    width: number | null;
    height: number | null;
    tag_url: string | null;
    snippet: string | null;
    impressions_count: number;
    clicks_count: number;
    is_active: boolean;
    created_at: string;
}

interface Totals {
    impressions: number;
    clicks: number;
    embeds: number;
}

type PeriodValue = 'today' | 'week' | 'month' | 'year' | 'custom';

interface Period {
    value: PeriodValue;
    from: string;
    to: string;
    label: string;
}

const PERIOD_OPTIONS: { value: PeriodValue; label: string }[] = [
    { value: 'today', label: 'Today' },
    { value: 'week',  label: 'This Week' },
    { value: 'month', label: 'This Month' },
    { value: 'year',  label: 'This Year' },
    { value: 'custom', label: 'Custom' },
];

interface AvailablePreview {
    id: number;
    name: string;
    available_banner_count: number;
}

interface AvailableBanner {
    id: number;
    name: string;
    width: number | null;
    height: number | null;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Orbit', href: '/orbit' }];

const page = usePage();
const embeds = computed<any>(() => page.props.embeds);
const totals = computed<Totals>(() => (page.props.totals as Totals) ?? { impressions: 0, clicks: 0, embeds: 0 });
const period = computed<Period>(() =>
    (page.props.period as Period) ?? { value: 'month', from: '', to: '', label: '' });
const ctr = computed(() => {
    const i = totals.value.impressions;
    if (!i) return '—';
    return ((totals.value.clicks / i) * 100).toFixed(2) + '%';
});
const hasData = computed(() => totals.value.impressions > 0 || totals.value.clicks > 0);
const search = ref<string>((page.props.search as string) ?? '');
const customFrom = ref<string>(period.value.value === 'custom' ? period.value.from : '');
const customTo = ref<string>(period.value.value === 'custom' ? period.value.to : '');

const buildParams = (overrides: Record<string, string | undefined> = {}) => {
    const params: Record<string, string> = {
        search: search.value,
        period: period.value.value,
    };
    if (period.value.value === 'custom') {
        if (period.value.from) params.from = period.value.from;
        if (period.value.to) params.to = period.value.to;
    }
    for (const [k, v] of Object.entries(overrides)) {
        if (v === undefined || v === '') delete params[k];
        else params[k] = v;
    }
    return params;
};

watch(search, (value) => {
    router.get(route('orbit.index'), buildParams({ search: value }), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
});

const selectPeriod = (value: PeriodValue) => {
    // Always navigate. For 'custom' the server falls back to current
    // month if no from/to is provided, then the inputs appear so the
    // user can pick a real range and hit Apply.
    router.get(route('orbit.index'), {
        search: search.value,
        period: value,
    }, { preserveScroll: true, preserveState: true, replace: true });
};

const applyCustom = () => {
    if (!customFrom.value || !customTo.value) return;
    router.get(route('orbit.index'), {
        search: search.value,
        period: 'custom',
        from: customFrom.value,
        to: customTo.value,
    }, { preserveScroll: true, preserveState: true, replace: true });
};

const changePage = (url: string | null) => {
    if (!url) return;
    router.get(url, buildParams(), {
        preserveScroll: true,
        preserveState: true,
    });
};

const toast = (icon: 'success' | 'error', title: string) =>
    Swal.fire({
        icon,
        title,
        timer: 1500,
        showConfirmButton: false,
        toast: true,
        position: 'top-end',
        timerProgressBar: true,
        customClass: { popup: 'rounded-lg' },
    });

const copySnippet = async (snippet: string | null) => {
    if (!snippet) return;
    try {
        await navigator.clipboard.writeText(snippet);
        toast('success', 'Snippet copied');
    } catch {
        toast('error', 'Copy failed');
    }
};

const toggleActive = (embed: Embed) => {
    router.post(route('orbit.toggle', embed.id), {}, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => toast('success', embed.is_active ? 'Deactivated' : 'Activated'),
        onError: () => toast('error', 'Toggle failed'),
    });
};

const removeEmbed = async (id: number) => {
    const result = await Swal.fire({
        title: 'Remove from Orbit?',
        text: 'The banner stays — only the embed entry is removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, remove',
        customClass: { popup: 'rounded-lg' },
    });

    if (!result.isConfirmed) return;

    router.delete(route('orbit.destroy', id), {
        preserveScroll: true,
        onSuccess: () => toast('success', 'Removed'),
        onError: () => toast('error', 'Remove failed'),
    });
};

// Picker modal — two-step flow: previews → banners-for-preview.
const pickerOpen = ref(false);
const pickerStep = ref<'previews' | 'banners'>('previews');
const pickerLoading = ref(false);
const pickerError = ref<string | null>(null);
const pickerPreviews = ref<AvailablePreview[]>([]);
const pickerBanners = ref<AvailableBanner[]>([]);
const selectedPreview = ref<AvailablePreview | null>(null);
const selectedBannerIds = ref<Set<number>>(new Set());
const publishing = ref(false);

const allBannersSelected = computed(() =>
    pickerBanners.value.length > 0 &&
    selectedBannerIds.value.size === pickerBanners.value.length,
);

const toggleBannerSelected = (id: number) => {
    const next = new Set(selectedBannerIds.value);
    next.has(id) ? next.delete(id) : next.add(id);
    selectedBannerIds.value = next;
};

const toggleAllBanners = () => {
    selectedBannerIds.value = allBannersSelected.value
        ? new Set()
        : new Set(pickerBanners.value.map((b) => b.id));
};

const fetchPreviews = async () => {
    pickerLoading.value = true;
    pickerError.value = null;
    try {
        const { data } = await axios.get(route('orbit.available-previews'));
        pickerPreviews.value = data.previews ?? [];
    } catch {
        pickerError.value = 'Could not load previews.';
    } finally {
        pickerLoading.value = false;
    }
};

const fetchBannersForPreview = async (previewId: number) => {
    pickerLoading.value = true;
    pickerError.value = null;
    try {
        const { data } = await axios.get(route('orbit.available-banners', previewId));
        pickerBanners.value = data.banners ?? [];
    } catch {
        pickerError.value = 'Could not load banners.';
    } finally {
        pickerLoading.value = false;
    }
};

const openPicker = () => {
    pickerOpen.value = true;
    pickerStep.value = 'previews';
    selectedPreview.value = null;
    pickerBanners.value = [];
    fetchPreviews();
};

const closePicker = () => {
    pickerOpen.value = false;
};

const selectPickerPreview = (preview: AvailablePreview) => {
    selectedPreview.value = preview;
    pickerStep.value = 'banners';
    selectedBannerIds.value = new Set();
    fetchBannersForPreview(preview.id);
};

const backToPreviews = () => {
    pickerStep.value = 'previews';
    selectedPreview.value = null;
    pickerBanners.value = [];
    selectedBannerIds.value = new Set();
};

const publishSelected = () => {
    const ids = Array.from(selectedBannerIds.value);
    if (ids.length === 0) return;
    publishing.value = true;
    router.post(route('orbit.store'), { banner_ids: ids }, {
        preserveScroll: true,
        onSuccess: () => {
            toast('success', `Published ${ids.length} tag${ids.length === 1 ? '' : 's'}`);
            closePicker();
        },
        onError: (errors) => {
            const first = Object.values(errors ?? {})[0];
            toast('error', String(first ?? 'Publish failed'));
        },
        onFinish: () => { publishing.value = false; },
    });
};
</script>

<template>

    <Head title="Orbit" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 md:p-6">
                <!-- Period selector -->
                <div class="mb-3 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap gap-2">
                        <button v-for="opt in PERIOD_OPTIONS" :key="opt.value" type="button"
                            @click="selectPeriod(opt.value)"
                            class="rounded-full border-2 px-4 py-1.5 font-mono text-xs tracking-wide transition-colors"
                            :class="period.value === opt.value
                                ? 'border-black bg-black text-white dark:border-white dark:bg-white dark:text-black'
                                : 'border-[#CCCCCC] text-[#666666] hover:border-black hover:text-black dark:border-[#333333] dark:text-[#999999] dark:hover:border-white dark:hover:text-white'">
                            {{ opt.label }}
                        </button>
                    </div>
                    <div
                        class="font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                        Showing: {{ period.label }}
                    </div>
                </div>

                <!-- Custom range inputs -->
                <div v-if="period.value === 'custom'"
                    class="mb-4 flex flex-col gap-2 rounded-lg border-2 border-[#E8E8E8] bg-[#F5F5F5] p-3 sm:flex-row sm:items-center dark:border-[#222222] dark:bg-[#0A0A0A]">
                    <label class="font-mono text-xs uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                        From
                        <input v-model="customFrom" type="date"
                            class="ml-2 rounded border-2 border-[#CCCCCC] bg-white px-2 py-1 font-mono text-xs text-[#1A1A1A] focus:border-black focus:outline-none dark:border-[#333333] dark:bg-black dark:text-[#E8E8E8] dark:focus:border-white" />
                    </label>
                    <label class="font-mono text-xs uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                        To
                        <input v-model="customTo" type="date"
                            class="ml-2 rounded border-2 border-[#CCCCCC] bg-white px-2 py-1 font-mono text-xs text-[#1A1A1A] focus:border-black focus:outline-none dark:border-[#333333] dark:bg-black dark:text-[#E8E8E8] dark:focus:border-white" />
                    </label>
                    <button type="button" @click="applyCustom"
                        :disabled="!customFrom || !customTo"
                        class="ml-auto rounded-full border-2 border-black bg-black px-4 py-1.5 font-mono text-xs tracking-wide text-white transition-colors hover:bg-white hover:text-black disabled:cursor-not-allowed disabled:opacity-50 dark:border-white dark:bg-white dark:text-black dark:hover:bg-black dark:hover:text-white">
                        Apply
                    </button>
                </div>

                <!-- Totals -->
                <div v-if="hasData" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div
                        class="rounded-lg border-2 border-[#E8E8E8] bg-white p-4 dark:border-[#222222] dark:bg-[#111111]">
                        <div class="flex items-center justify-between">
                            <div
                                class="font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                Total Impressions
                            </div>
                            <Eye class="h-4 w-4 text-[#666666] dark:text-[#999999]" stroke-width="1.5" />
                        </div>
                        <div class="mt-2 font-mono text-3xl font-semibold tabular-nums">
                            {{ totals.impressions.toLocaleString() }}
                        </div>
                    </div>
                    <div
                        class="rounded-lg border-2 border-[#E8E8E8] bg-white p-4 dark:border-[#222222] dark:bg-[#111111]">
                        <div class="flex items-center justify-between">
                            <div
                                class="font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                Total Clicks
                            </div>
                            <MousePointerClick class="h-4 w-4 text-[#666666] dark:text-[#999999]" stroke-width="1.5" />
                        </div>
                        <div class="mt-2 font-mono text-3xl font-semibold tabular-nums">
                            {{ totals.clicks.toLocaleString() }}
                        </div>
                    </div>
                    <div
                        class="rounded-lg border-2 border-[#E8E8E8] bg-white p-4 dark:border-[#222222] dark:bg-[#111111]">
                        <div class="flex items-center justify-between">
                            <div
                                class="font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                CTR
                            </div>
                        </div>
                        <div class="mt-2 font-mono text-3xl font-semibold tabular-nums">
                            {{ ctr }}
                        </div>
                    </div>
                </div>

                <div v-else
                    class="mb-4 rounded-lg border-2 border-dashed border-[#CCCCCC] bg-white p-8 text-center dark:border-[#333333] dark:bg-[#111111]">
                    <div class="font-mono text-sm uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                        No data
                    </div>
                </div>

                <!-- Search & Add -->
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <input v-model="search" placeholder="Search by banner or preview..."
                        class="w-full rounded-full border-2 border-[#CCCCCC] bg-white px-4 py-2 text-[#1A1A1A] transition-colors focus:border-black focus:outline-none sm:max-w-xs dark:border-[#333333] dark:bg-black dark:text-[#E8E8E8] dark:focus:border-white" />
                    <button @click="openPicker"
                        class="group flex items-center justify-center whitespace-nowrap rounded-full border-2 border-black bg-black px-4 py-2 font-mono text-sm tracking-wide text-white transition-colors hover:bg-white hover:text-black dark:border-white dark:bg-white dark:text-black dark:hover:bg-black dark:hover:text-white">
                        <CirclePlus class="mr-2 h-5 w-5 transition-transform duration-200 group-hover:rotate-90"
                            stroke-width="1.5" />
                        Add embed
                    </button>
                </div>

                <!-- Desktop Table -->
                <div class="hidden overflow-x-auto rounded-lg border-2 border-[#E8E8E8] lg:block dark:border-[#222222]">
                    <table class="w-full rounded bg-white dark:bg-[#111111]">
                        <thead class="bg-[#F5F5F5] text-black dark:bg-black dark:text-white">
                            <tr class="text-left font-mono text-xs tracking-wide">
                                <th class="border-b border-[#E8E8E8] px-4 py-3 text-center dark:border-[#222222]">#</th>
                                <th class="border-b border-[#E8E8E8] px-4 py-3 dark:border-[#222222]">Asset</th>
                                <th class="border-b border-[#E8E8E8] px-4 py-3 text-right dark:border-[#222222]">
                                    Impressions
                                </th>
                                <th class="border-b border-[#E8E8E8] px-4 py-3 text-right dark:border-[#222222]">Clicks
                                </th>
                                <th class="border-b border-[#E8E8E8] px-4 py-3 text-center dark:border-[#222222]">Status
                                </th>
                                <th class="border-b border-[#E8E8E8] px-4 py-3 text-center dark:border-[#222222]">Added
                                </th>
                                <th class="border-b border-[#E8E8E8] px-4 py-3 text-center dark:border-[#222222]">Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(embed, index) in embeds.data as Embed[]" :key="embed.id"
                                :class="['border-t border-[#E8E8E8] text-sm transition-colors hover:bg-[#F5F5F5] dark:border-[#222222] hover:dark:bg-[#0A0A0A]',
                                    !embed.is_active && 'opacity-50']">
                                <td
                                    class="border-b border-[#E8E8E8] px-4 py-3 text-center tabular-nums text-[#666666] dark:border-[#222222] dark:text-[#999999]">
                                    {{ (embeds.from ?? 1) + index }}
                                </td>
                                <td class="border-b border-[#E8E8E8] px-4 py-3 dark:border-[#222222]">
                                    <div class="font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                        {{ embed.preview_name || '—' }}
                                    </div>
                                    <div class="mt-0.5 break-all font-medium">{{ embed.banner_name || '—' }}</div>
                                    <div class="mt-0.5 font-mono text-xs tabular-nums text-[#666666] dark:text-[#999999]">
                                        {{ embed.width }}×{{ embed.height }}
                                    </div>
                                </td>
                                <td
                                    class="border-b border-[#E8E8E8] px-4 py-3 text-right font-mono font-semibold tabular-nums dark:border-[#222222]">
                                    {{ embed.impressions_count.toLocaleString() }}
                                </td>
                                <td
                                    class="border-b border-[#E8E8E8] px-4 py-3 text-right font-mono font-semibold tabular-nums dark:border-[#222222]">
                                    {{ embed.clicks_count.toLocaleString() }}
                                </td>
                                <td class="border-b border-[#E8E8E8] px-4 py-3 text-center dark:border-[#222222]">
                                    <button type="button" @click="toggleActive(embed)" role="switch"
                                        :aria-checked="embed.is_active"
                                        :aria-label="embed.is_active ? 'Deactivate embed' : 'Activate embed'"
                                        :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                                            embed.is_active
                                                ? 'bg-black dark:bg-white'
                                                : 'bg-[#CCCCCC] dark:bg-[#333333]']">
                                        <span aria-hidden="true"
                                            :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform dark:bg-black',
                                                embed.is_active ? 'translate-x-[18px]' : 'translate-x-0.5']" />
                                    </button>
                                </td>
                                <td
                                    class="border-b border-[#E8E8E8] px-4 py-3 text-center text-xs text-[#666666] dark:border-[#222222] dark:text-[#999999]">
                                    {{ new Date(embed.created_at).toLocaleDateString('en-GB', {
                                        day: '2-digit',
                                        month: 'short', year: 'numeric'
                                    }) }}
                                </td>
                                <td class="space-x-2 border-b border-[#E8E8E8] px-4 py-3 text-center dark:border-[#222222]">
                                    <button @click="copySnippet(embed.snippet)"
                                        class="p-1 text-black transition-colors hover:text-[#666666] dark:text-white dark:hover:text-[#999999]"
                                        aria-label="Copy snippet">
                                        <Copy class="inline h-5 w-5" stroke-width="1.5" />
                                    </button>
                                    <button @click="removeEmbed(embed.id)"
                                        class="p-1 text-[#D71921] transition-colors hover:text-red-700"
                                        aria-label="Remove embed">
                                        <Trash2 class="inline h-5 w-5" stroke-width="1.5" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!embeds.data.length">
                                <td colspan="7"
                                    class="px-4 py-8 text-center font-mono text-sm tracking-wide text-[#666666] dark:text-[#999999]">
                                    No embeds yet. Click "Add embed" to publish one.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile/Tablet Cards -->
                <div class="space-y-4 lg:hidden">
                    <div v-for="(embed, index) in embeds.data as Embed[]" :key="embed.id"
                        :class="['rounded-lg border-2 border-[#E8E8E8] bg-white p-4 transition-colors hover:border-black dark:border-[#222222] dark:bg-[#111111] hover:dark:border-white',
                            !embed.is_active && 'opacity-50']">
                        <div class="mb-3 flex items-start justify-between">
                            <div class="min-w-0 flex-1">
                                <div
                                    class="mb-1 font-mono text-xs tabular-nums tracking-wide text-[#666666] dark:text-[#999999]">
                                    #{{ (embeds.from ?? 1) + index }}
                                </div>
                                <div
                                    class="font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                    {{ embed.preview_name || '—' }}
                                </div>
                                <h3 class="mt-0.5 break-words text-sm font-semibold">
                                    {{ embed.banner_name || '—' }}
                                </h3>
                                <div class="mt-0.5 font-mono text-xs tabular-nums text-[#666666] dark:text-[#999999]">
                                    {{ embed.width }}×{{ embed.height }}
                                </div>
                            </div>
                            <div class="ml-3 flex items-center gap-2">
                                <button type="button" @click="toggleActive(embed)" role="switch"
                                    :aria-checked="embed.is_active"
                                    :aria-label="embed.is_active ? 'Deactivate embed' : 'Activate embed'"
                                    :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                                        embed.is_active
                                            ? 'bg-black dark:bg-white'
                                            : 'bg-[#CCCCCC] dark:bg-[#333333]']">
                                    <span aria-hidden="true"
                                        :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform dark:bg-black',
                                            embed.is_active ? 'translate-x-[18px]' : 'translate-x-0.5']" />
                                </button>
                                <button @click="copySnippet(embed.snippet)"
                                    class="rounded-lg p-2 text-black transition-colors hover:text-[#666666] dark:text-white dark:hover:text-[#999999]"
                                    aria-label="Copy snippet">
                                    <Copy class="h-5 w-5" stroke-width="1.5" />
                                </button>
                                <button @click="removeEmbed(embed.id)"
                                    class="rounded-lg p-2 text-[#D71921] transition-colors hover:text-red-700"
                                    aria-label="Remove embed">
                                    <Trash2 class="h-5 w-5" stroke-width="1.5" />
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div
                                class="rounded border border-[#E8E8E8] px-3 py-2 dark:border-[#222222]">
                                <div
                                    class="font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                    Impressions
                                </div>
                                <div class="mt-1 font-mono text-lg font-semibold tabular-nums">
                                    {{ embed.impressions_count.toLocaleString() }}
                                </div>
                            </div>
                            <div
                                class="rounded border border-[#E8E8E8] px-3 py-2 dark:border-[#222222]">
                                <div
                                    class="font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                    Clicks
                                </div>
                                <div class="mt-1 font-mono text-lg font-semibold tabular-nums">
                                    {{ embed.clicks_count.toLocaleString() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!embeds.data.length"
                        class="rounded-lg border-2 border-[#E8E8E8] bg-white p-8 text-center dark:border-[#222222] dark:bg-[#111111]">
                        <div class="font-mono text-sm tracking-wide text-[#666666] dark:text-[#999999]">
                            No embeds yet. Click "Add embed" to publish one.
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="embeds.data.length && embeds.links?.length" class="mt-6 p-4">
                    <div class="flex items-center justify-between">
                        <div class="font-mono text-xs tracking-wide text-[#666666] dark:text-[#999999]">
                            Showing <span class="tabular-nums">{{ embeds.from }}</span> to <span class="tabular-nums">{{
                                embeds.to }}</span> of <span class="tabular-nums">{{ embeds.total }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="changePage(embeds.prev_page_url)" :disabled="!embeds.prev_page_url"
                                class="flex items-center rounded-full px-4 py-2 font-mono text-xs tracking-wide transition-colors"
                                :class="embeds.prev_page_url
                                    ? 'border-2 border-black text-black hover:bg-black hover:text-white dark:border-white dark:text-white hover:dark:bg-white hover:dark:text-black'
                                    : 'cursor-not-allowed border-2 border-[#CCCCCC] text-[#CCCCCC] dark:border-[#333333] dark:text-[#333333]'">
                                <ChevronLeft class="mr-1 h-4 w-4" stroke-width="1.5" />
                                Previous
                            </button>
                            <span class="font-mono text-xs tracking-wide text-[#666666] dark:text-[#999999]">
                                Page <span class="tabular-nums">{{ embeds.current_page }}</span> of <span
                                    class="tabular-nums">{{ embeds.last_page }}</span>
                            </span>
                            <button @click="changePage(embeds.next_page_url)" :disabled="!embeds.next_page_url"
                                class="flex items-center rounded-full px-4 py-2 font-mono text-xs tracking-wide transition-colors"
                                :class="embeds.next_page_url
                                    ? 'border-2 border-black text-black hover:bg-black hover:text-white dark:border-white dark:text-white hover:dark:bg-white hover:dark:text-black'
                                    : 'cursor-not-allowed border-2 border-[#CCCCCC] text-[#CCCCCC] dark:border-[#333333] dark:text-[#333333]'">
                                Next
                                <ChevronRight class="ml-1 h-4 w-4" stroke-width="1.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Picker Modal — two-step: previews list → banners-for-preview -->
            <div v-if="pickerOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                @click.self="closePicker">
                <div
                    class="flex max-h-[85vh] w-full max-w-2xl flex-col rounded-lg border-2 border-black bg-white dark:border-white dark:bg-[#111111]">
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between gap-3 border-b-2 border-[#E8E8E8] px-5 py-4 dark:border-[#222222]">
                        <div class="flex min-w-0 items-center gap-3">
                            <button v-if="pickerStep === 'banners'" type="button" @click="backToPreviews"
                                aria-label="Back to previews"
                                class="rounded-lg p-1 text-black transition-colors hover:text-[#666666] dark:text-white dark:hover:text-[#999999]">
                                <ArrowLeft class="h-5 w-5" stroke-width="1.5" />
                            </button>
                            <div class="min-w-0">
                                <h2 class="truncate font-mono text-sm uppercase tracking-wide">
                                    <template v-if="pickerStep === 'previews'">Pick a preview</template>
                                    <template v-else>{{ selectedPreview?.name || 'Banners' }}</template>
                                </h2>
                                <div v-if="pickerStep === 'banners'"
                                    class="font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                    Select banners to publish
                                </div>
                            </div>
                        </div>
                        <button @click="closePicker" aria-label="Close"
                            class="rounded-lg p-1 text-black transition-colors hover:text-[#666666] dark:text-white dark:hover:text-[#999999]">
                            <X class="h-5 w-5" stroke-width="1.5" />
                        </button>
                    </div>

                    <!-- Banner-step toolbar: select-all + count -->
                    <div v-if="pickerStep === 'banners' && pickerBanners.length"
                        class="flex items-center justify-between gap-4 border-b border-[#E8E8E8] px-5 py-3 dark:border-[#222222]">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" :checked="allBannersSelected" @change="toggleAllBanners"
                                class="h-4 w-4 cursor-pointer accent-black dark:accent-white" />
                            <span class="font-mono text-xs uppercase tracking-widest">
                                {{ allBannersSelected ? 'Deselect all' : 'Select all' }}
                            </span>
                        </label>
                        <span class="font-mono text-xs tabular-nums text-[#666666] dark:text-[#999999]">
                            {{ selectedBannerIds.size }} / {{ pickerBanners.length }} selected
                        </span>
                    </div>

                    <!-- Body. min-height keeps the modal from jumping
                         when the async fetch completes — skeleton rows
                         hold the space while data loads. -->
                    <div class="flex min-h-[420px] flex-1 flex-col overflow-y-auto px-2 py-2">
                        <div v-if="pickerError"
                            class="px-4 py-8 text-center font-mono text-sm text-[#D71921]">
                            {{ pickerError }}
                        </div>

                        <!-- Step 1: previews -->
                        <template v-else-if="pickerStep === 'previews'">
                            <div v-if="pickerLoading">
                                <div
                                    class="flex items-center gap-2 px-4 py-2 font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                    <span class="relative flex h-1.5 w-1.5">
                                        <span
                                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-black opacity-75 dark:bg-white" />
                                        <span
                                            class="relative inline-flex h-1.5 w-1.5 rounded-full bg-black dark:bg-white" />
                                    </span>
                                    Fetching previews
                                </div>
                                <ul class="divide-y divide-[#E8E8E8] dark:divide-[#222222]">
                                    <li v-for="n in 6" :key="n"
                                        class="flex items-center justify-between gap-4 px-3 py-3">
                                        <div class="flex-1 space-y-2">
                                            <div
                                                class="h-3.5 animate-pulse rounded bg-[#E8E8E8] dark:bg-[#222222]"
                                                :style="{ width: 30 + ((n * 13) % 50) + '%' }" />
                                            <div
                                                class="h-2.5 w-1/3 animate-pulse rounded bg-[#F5F5F5] dark:bg-[#0F0F0F]" />
                                        </div>
                                        <div class="h-4 w-4 animate-pulse rounded bg-[#E8E8E8] dark:bg-[#222222]" />
                                    </li>
                                </ul>
                            </div>
                            <div v-else-if="!pickerPreviews.length"
                                class="px-4 py-8 text-center font-mono text-sm text-[#666666] dark:text-[#999999]">
                                No previews with available banners.
                            </div>
                            <ul v-else class="divide-y divide-[#E8E8E8] dark:divide-[#222222]">
                                <li v-for="preview in pickerPreviews" :key="preview.id">
                                    <button type="button" @click="selectPickerPreview(preview)"
                                        class="flex w-full items-center justify-between gap-4 rounded-lg px-3 py-3 text-left transition-colors hover:bg-[#F5F5F5] dark:hover:bg-[#0A0A0A]">
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-sm font-semibold uppercase">
                                                {{ preview.name }}
                                            </div>
                                            <div
                                                class="mt-0.5 font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                                {{ preview.available_banner_count }}
                                                banner{{ preview.available_banner_count === 1 ? '' : 's' }}
                                                available
                                            </div>
                                        </div>
                                        <ChevronRightIcon class="h-5 w-5 text-[#666666] dark:text-[#999999]"
                                            stroke-width="1.5" />
                                    </button>
                                </li>
                            </ul>
                        </template>

                        <!-- Step 2: banners for the chosen preview -->
                        <template v-else>
                            <div v-if="pickerLoading">
                                <div
                                    class="flex items-center gap-2 px-4 py-2 font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                    <span class="relative flex h-1.5 w-1.5">
                                        <span
                                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-black opacity-75 dark:bg-white" />
                                        <span
                                            class="relative inline-flex h-1.5 w-1.5 rounded-full bg-black dark:bg-white" />
                                    </span>
                                    Fetching banners
                                </div>
                                <ul class="divide-y divide-[#E8E8E8] dark:divide-[#222222]">
                                    <li v-for="n in 6" :key="n"
                                        class="flex items-center justify-between gap-4 px-3 py-3">
                                        <div class="flex flex-1 items-center gap-3">
                                            <div
                                                class="h-4 w-4 animate-pulse rounded bg-[#E8E8E8] dark:bg-[#222222]" />
                                            <div
                                                class="h-3.5 animate-pulse rounded bg-[#E8E8E8] dark:bg-[#222222]"
                                                :style="{ width: 35 + ((n * 11) % 45) + '%' }" />
                                        </div>
                                        <div class="h-3 w-14 animate-pulse rounded bg-[#F5F5F5] dark:bg-[#0F0F0F]" />
                                    </li>
                                </ul>
                            </div>
                            <div v-else-if="!pickerBanners.length"
                                class="px-4 py-8 text-center font-mono text-sm text-[#666666] dark:text-[#999999]">
                                No banners available for this preview.
                            </div>
                            <ul v-else class="divide-y divide-[#E8E8E8] dark:divide-[#222222]">
                                <li v-for="banner in pickerBanners" :key="banner.id">
                                    <label
                                        class="flex w-full cursor-pointer items-center justify-between gap-4 rounded-lg px-3 py-3 text-left transition-colors hover:bg-[#F5F5F5] dark:hover:bg-[#0A0A0A]">
                                        <div class="flex min-w-0 flex-1 items-center gap-3">
                                            <input type="checkbox"
                                                :checked="selectedBannerIds.has(banner.id)"
                                                @change="toggleBannerSelected(banner.id)"
                                                class="h-4 w-4 cursor-pointer accent-black dark:accent-white" />
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-sm">{{ banner.name }}</div>
                                            </div>
                                        </div>
                                        <div
                                            class="font-mono text-xs tabular-nums text-[#666666] dark:text-[#999999]">
                                            {{ banner.width }}×{{ banner.height }}
                                        </div>
                                    </label>
                                </li>
                            </ul>
                        </template>
                    </div>

                    <!-- Banner-step footer: publish button -->
                    <div v-if="pickerStep === 'banners'"
                        class="flex items-center justify-end gap-3 border-t-2 border-[#E8E8E8] px-5 py-3 dark:border-[#222222]">
                        <button type="button" @click="closePicker"
                            class="rounded-full border-2 border-[#CCCCCC] px-4 py-1.5 font-mono text-xs tracking-wide text-[#666666] transition-colors hover:border-black hover:text-black dark:border-[#333333] dark:text-[#999999] dark:hover:border-white dark:hover:text-white">
                            Cancel
                        </button>
                        <button type="button" @click="publishSelected"
                            :disabled="!selectedBannerIds.size || publishing"
                            class="rounded-full border-2 border-black bg-black px-4 py-1.5 font-mono text-xs tracking-wide text-white transition-colors hover:bg-white hover:text-black disabled:cursor-not-allowed disabled:opacity-50 dark:border-white dark:bg-white dark:text-black dark:hover:bg-black dark:hover:text-white">
                            <template v-if="publishing">Publishing…</template>
                            <template v-else-if="selectedBannerIds.size === 0">Publish</template>
                            <template v-else>
                                Publish {{ selectedBannerIds.size }}
                                tag{{ selectedBannerIds.size === 1 ? '' : 's' }}
                            </template>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
