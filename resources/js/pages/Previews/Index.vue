<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, X, LayoutGrid, List, ListFilter, Image as ImageIcon, Film, Share2, Sparkles } from 'lucide-vue-next';
import dayjs from 'dayjs'
import Swal from 'sweetalert2';
import { computed, ref, nextTick } from 'vue';
import PreviewStepBasicInfo from './Partials/PreviewStepBasicInfo.vue';
import PreviewActionButtons from './Partials/PreviewActionButtons.vue';
import PreviewGridCard from './Partials/PreviewGridCard.vue';
import PreviewPagination from './Partials/PreviewPagination.vue';

// Click-away directive
const vClickAway = {
    mounted(el: any, binding: any) {
        el.clickAwayEvent = (event: Event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value(event);
            }
        };
        document.addEventListener('click', el.clickAwayEvent);
    },
    unmounted(el: any) {
        document.removeEventListener('click', el.clickAwayEvent);
    }
};

const loading = ref(false);
const page = usePage();
const search = ref('');
const debounceTimer = ref<number | null>(null)
const showModal = ref(false);
const activeTab = ref<'grid' | 'table'>('grid')
const showFilters = ref(false);
const filters = ref({
    fromDate: '',
    toDate: '',
    uploaderId: '',
    keywords: ''
});

const previews = computed(() => page.props.previews ?? { data: [], links: [] });
const clients = computed(() => page.props.clients ?? []);
const users = computed(() => page.props.users ?? []);
const colorPalettes = computed(() => page.props.colorPalettes ?? []);
const authUser = computed(() => page.props.auth?.user ?? {});

function getDefaultFormData() {
    return {
        name: '',
        client_id: '',
        header_logo_id: '1',
        team_ids: [authUser.value.id],
        color_palette_id: '5',
        requires_login: false,
        show_planetnine_logo: true,
        show_sidebar_logo: true,
        show_footer: true,
    };
}

const formData = ref(getDefaultFormData());

// Helper to build router parameters
function getRouterParams(additionalParams = {}) {
    return {
        search: search.value,
        view: activeTab.value,
        from_date: filters.value.fromDate,
        to_date: filters.value.toDate,
        uploader_id: filters.value.uploaderId,
        keywords: filters.value.keywords,
        ...additionalParams
    };
}

// Helper to filter previews by search query
function applySearchFilter(list: any[], searchQuery: string) {
    if (!searchQuery) return list;
    const q = searchQuery.toLowerCase();
    return list.filter((p: any) =>
        (p.name || '').toLowerCase().includes(q) ||
        (p.client?.name || '').toLowerCase().includes(q) ||
        (p.uploader?.name || '').toLowerCase().includes(q)
    );
}

// Server-provided previews are the source of truth; also apply client-side
// filtering so the table updates immediately while server search remains available.
const filteredPreviews = computed(() => {
    let list = previews.value.data || [];

    // Apply search filter
    list = applySearchFilter(list, search.value);

    // Apply date range filter
    if (filters.value.fromDate) {
        list = list.filter((p: any) => {
            const createdDate = dayjs(p.created_at).format('YYYY-MM-DD');
            return createdDate >= filters.value.fromDate;
        });
    }
    if (filters.value.toDate) {
        list = list.filter((p: any) => {
            const createdDate = dayjs(p.created_at).format('YYYY-MM-DD');
            return createdDate <= filters.value.toDate;
        });
    }

    // Apply uploader filter
    if (filters.value.uploaderId) {
        list = list.filter((p: any) => p.uploader_id == filters.value.uploaderId);
    }

    // Apply keywords filter
    if (filters.value.keywords) {
        const keywords = filters.value.keywords.toLowerCase();
        list = list.filter((p: any) =>
            (p.name || '').toLowerCase().includes(keywords) ||
            (p.client?.name || '').toLowerCase().includes(keywords)
        );
    }

    return list;
});

function applyFilters() {
    loading.value = true
    router.get(route('previews-index'), getRouterParams({ page: 1 }), {
        preserveState: true,
        replace: true,
        onFinish: () => { loading.value = false; showFilters.value = false }
    })
}

function clearFilters() {
    // Reset filter fields in the UI but do not auto-apply.
    // This keeps the dropdown open so the user can adjust or click Apply.
    filters.value.fromDate = ''
    filters.value.toDate = ''
    filters.value.uploaderId = ''
    filters.value.keywords = ''
}

function onSearchInput() {
    if (debounceTimer.value) clearTimeout(debounceTimer.value)
    debounceTimer.value = window.setTimeout(() => {
        loading.value = true
        router.get(route('previews-index'), getRouterParams({ page: 1 }), {
            preserveState: true,
            replace: true,
            onFinish: () => { loading.value = false }
        })
    }, 350)
}

function switchTab(tab: 'grid' | 'table') {
    activeTab.value = tab
    loading.value = true
    router.get(route('previews-index'), getRouterParams({ view: tab, page: 1 }), {
        preserveState: true,
        replace: true,
        onFinish: () => { loading.value = false }
    })
}

// ---------------------------------------------------------------------
// Per-row meta for the table view: type chips, totals, progress pct.
// Same logic as PreviewGridCard but evaluated lazily here so the table
// can render counts inline without giving every row its own component.
// ---------------------------------------------------------------------
type AssetKey = 'banner' | 'video' | 'social' | 'gif';
const TYPE_META: Record<AssetKey, { icon: any; label: string }> = {
    banner: { icon: ImageIcon, label: 'banners' },
    video: { icon: Film, label: 'videos' },
    social: { icon: Share2, label: 'posts' },
    gif: { icon: Sparkles, label: 'gifs' },
};
const TYPE_ORDER: AssetKey[] = ['banner', 'video', 'social', 'gif'];

function getTypeChips(preview: any) {
    const counts: Record<AssetKey, number> = { banner: 0, video: 0, social: 0, gif: 0 };
    for (const c of preview.categories || []) {
        const t = (c.type || '').toLowerCase() as AssetKey;
        if (!(t in counts)) continue;
        let n = 0;
        for (const f of c.feedbacks || []) {
            for (const s of (f.feedback_sets || f.feedbackSets || [])) {
                for (const v of s.versions || []) {
                    n += v[`${t}s`]?.length || 0;
                }
            }
        }
        counts[t] += n;
    }
    return TYPE_ORDER
        .filter(k => counts[k] > 0)
        .map(k => ({ key: k, icon: TYPE_META[k].icon, label: TYPE_META[k].label, count: counts[k] }));
}

function getTotals(preview: any) {
    let total = Number(preview.total_feedbacks ?? 0);
    let approved = Number(preview.approved_feedbacks ?? 0);
    if (!total && Array.isArray(preview.categories)) {
        for (const c of preview.categories) {
            for (const f of (c.feedbacks || [])) {
                total += 1;
                if (f.is_approved) approved += 1;
            }
        }
    }
    return { total, approved, pct: total ? Math.round((approved / total) * 100) : 0 };
}

function initials(name?: string) {
    if (!name) return '?';
    return name.split(/\s+/).filter(Boolean).slice(0, 2).map(s => s[0]).join('').toUpperCase();
}

function rowAccent(preview: any) {
    return preview.color_palette?.primary || '#1A1A1A';
}

function openPreview(preview: any) {
    router.visit(`/previews/update/${preview.id}`);
}

const closeModal = () => {
    showModal.value = false;
    formData.value = getDefaultFormData();
};

const updateFormData = (key: string, value: any) => {
    (formData.value as any)[key] = value;
};

const submitForm = () => {
    const payload = new FormData();
    payload.append('name', formData.value.name);
    payload.append('client_id', formData.value.client_id);
    payload.append('header_logo_id', formData.value.header_logo_id);
    payload.append('color_palette_id', formData.value.color_palette_id ?? '');
    payload.append('requires_login', formData.value.requires_login ? '1' : '0');
    payload.append('show_planetnine_logo', formData.value.show_planetnine_logo ? '1' : '0');
    payload.append('show_sidebar_logo', formData.value.show_sidebar_logo ? '1' : '0');
    payload.append('show_footer', formData.value.show_footer ? '1' : '0');

    formData.value.team_ids.forEach(id => payload.append('team_ids[]', id));

    router.post(route('previews-store'), payload, {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Preview created successfully!',
                timer: 1000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                timerProgressBar: true,
            });
            closeModal();
        },
        onError: (error) => {
            console.log(error);
            Swal.fire('Error!', 'Failed to create preview.', 'error');
        }
    });
};

// Pagination functions (only used in table view)
const changePage = (url: string) => {
    if (url) {
        loading.value = true
        router.get(url, getRouterParams(), {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => { loading.value = false }
        });
    }
};

// Grouping for Grid view (using filtered results)
// Show a maximum of 6 blocks per group for grid layout
const inProgressDefault = 6
const completedDefault = 6
const noFeedbackDefault = 6

const inProgressLimit = ref(inProgressDefault)
const completedLimit = ref(completedDefault)
const noFeedbackLimit = ref(noFeedbackDefault)

// container refs for height animation when collapsing
const inProgressContainer = ref(null)
const completedContainer = ref(null)
const noFeedbackContainer = ref(null)

// Collapse a group to its target count. `group` is one of: 'inProgress' | 'completed' | 'noFeedback'
async function collapseTo(group: 'inProgress' | 'completed' | 'noFeedback', containerRef: any, target: number) {
    // map group name to the corresponding ref
    let limitRef: any
    if (group === 'inProgress') limitRef = inProgressLimit
    else if (group === 'completed') limitRef = completedLimit
    else limitRef = noFeedbackLimit

    const el: HTMLElement | null = containerRef?.value || null
    if (!el) {
        limitRef.value = target
        return
    }

    // Find the grid element inside the container
    const grid = el.querySelector('.grid') as HTMLElement | null
    if (!grid) {
        limitRef.value = target
        return
    }

    const children = Array.from(grid.children) as HTMLElement[]
    if (target >= children.length) {
        // nothing to collapse
        limitRef.value = target
        return
    }

    // measure start height (total container)
    const startHeight = el.scrollHeight
    // Lock start height so DOM updates don't cause a visual jump
    el.style.height = `${startHeight}px`
    el.style.overflow = 'hidden'

    // Update the limit immediately so items are removed from DOM
    limitRef.value = target
    await nextTick()

    const endHeight = el.scrollHeight

    if (Math.abs(startHeight - endHeight) < 1) {
        // nothing visually to do; clear styles and return
        el.style.height = ''
        el.style.overflow = ''
        return
    }

    el.style.transition = 'height 320ms ease'
    // force reflow
    void el.offsetHeight
    requestAnimationFrame(() => {
        el.style.height = `${endHeight}px`
    })

    const cleanup = () => {
        el.style.transition = ''
        el.style.height = ''
        el.style.overflow = ''
        el.removeEventListener('transitionend', cleanup)
    }

    el.addEventListener('transitionend', cleanup)
}

function expandGroup(group: 'inProgress' | 'completed' | 'noFeedback') {
    const limitMap = { inProgress: inProgressLimit, completed: completedLimit, noFeedback: noFeedbackLimit };
    const lengthMap = { inProgress: groups.value.inProgress.length, completed: groups.value.completed.length, noFeedback: groups.value.noFeedback.length };

    const limitRef = limitMap[group];
    const totalLength = lengthMap[group];
    limitRef.value = Math.min(limitRef.value + 6, totalLength);
}

const groups = computed(() => {
    const inProgress: any[] = []
    const completed: any[] = []
    const noFeedback: any[] = []

    // Use the same source as filteredPreviews so grouping matches search results
    const sourceList = previews.value.data || []
    const list = applySearchFilter(sourceList, search.value)

    for (const p of list) {
        // Derive totals from relationships if backend didn't provide them
        let total = 0
        let approved = 0
        let latestFeedback: any = null

        if (Array.isArray(p.categories)) {
            for (const c of p.categories) {
                if (Array.isArray(c.feedbacks)) {
                    total += c.feedbacks.length
                    for (const f of c.feedbacks) {
                        if (f.is_approved) approved += 1
                        if (!latestFeedback || new Date(f.updated_at) > new Date(latestFeedback.updated_at)) {
                            latestFeedback = f
                        }
                    }
                }
            }
        }

        // Fallback to server-provided fields if present
        total = Number(p.total_feedbacks ?? total ?? 0)
        approved = Number(p.approved_feedbacks ?? approved ?? 0)

        // Attach latest summary to preview for card rendering
        if (!p.latest_feedback_description) {
            p.latest_feedback_description = latestFeedback ? latestFeedback.description : null
        }

        if (total === 0) {
            noFeedback.push(p)
        } else if (approved >= total) {
            completed.push(p)
        } else {
            inProgress.push(p)
        }
    }
    return { inProgress, completed, noFeedback }
})
</script>

<template>

    <Head title="Previews" />
    <AppLayout :breadcrumbs="[{ title: 'Previews', href: '/previews' }]">
        <div class="min-h-screen bg-[#FFFFFF] dark:bg-black font-mono">
            <div class="p-4 md:p-6 space-y-6">
                <!-- Tabs / Search (Add button placed next to tabs) -->
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1 flex items-center gap-2">
                        <input v-model="search" @input="onSearchInput" placeholder="Search Previews..."
                            aria-label="Search previews"
                            class="w-full max-w-xs border border-[#CCCCCC] dark:border-[#333333] px-4 py-2 bg-white dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white text-sm tracking-wider rounded-full" />

                        <!-- Filter Button -->
                        <div class="relative">
                            <button @click.stop="showFilters = !showFilters"
                                :class="(filters.fromDate || filters.toDate || filters.uploaderId || filters.keywords) ? 'bg-white dark:bg-[#111111] text-black dark:text-white border-black dark:border-white' : 'bg-white dark:bg-[#111111] text-[#666666] dark:text-[#999999] border-[#CCCCCC] dark:border-[#333333]'"
                                class="px-2 py-2 rounded-lg border flex items-center gap-2 hover:border-black dark:hover:border-white transition relative">
                                <ListFilter class="w-5 h-5" />
                                <span
                                    v-if="filters.fromDate || filters.toDate || filters.uploaderId || filters.keywords"
                                    class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-[#D71921] rounded-full border-2 border-white dark:border-black"></span>
                            </button>

                            <!-- Filter Dropdown -->
                            <Transition name="fade-slide">
                                <div v-if="showFilters" v-click-away="() => showFilters = false" @click.stop
                                    class="absolute left-0 mt-2 w-80 bg-white dark:bg-[#111111] rounded-lg border-2 border-black dark:border-white p-4 z-50">
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label
                                                    class="block text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">FROM</label>
                                                <input v-model="filters.fromDate" type="date"
                                                    class="w-full rounded border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm bg-[#F5F5F5] dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] focus:outline-none focus:border-black dark:focus:border-white" />
                                            </div>

                                            <div>
                                                <label
                                                    class="block text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">TO</label>
                                                <input v-model="filters.toDate" type="date"
                                                    class="w-full rounded border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm bg-[#F5F5F5] dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] focus:outline-none focus:border-black dark:focus:border-white" />
                                            </div>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">UPLOADED
                                                BY</label>
                                            <select v-model="filters.uploaderId"
                                                class="w-full rounded border border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-[#F5F5F5] dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] focus:outline-none focus:border-black dark:focus:border-white">
                                                <option value="">All Users</option>
                                                <option v-for="user in users" :key="user.id" :value="user.id">{{
                                                    user.name
                                                }}</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">KEYWORDS</label>
                                            <input v-model="filters.keywords" type="text"
                                                placeholder="ENTER KEYWORDS..."
                                                class="w-full rounded border border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-[#F5F5F5] dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white font-mono text-sm uppercase tracking-wider" />
                                        </div>

                                        <div class="flex gap-2 pt-2">
                                            <button @click="applyFilters"
                                                class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-full hover:bg-[#1A1A1A] dark:hover:bg-[#E8E8E8] transition font-mono text-xs tracking-wide">
                                                Apply
                                            </button>
                                            <button @click="clearFilters"
                                                class="px-4 py-2 border border-[#CCCCCC] dark:border-[#333333] text-[#1A1A1A] dark:text-[#E8E8E8] rounded-full hover:border-black dark:hover:border-white transition font-mono text-xs tracking-wide">
                                                Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="switchTab('grid')" :aria-pressed="activeTab === 'grid'"
                            :class="activeTab === 'grid' ? 'bg-black dark:bg-white text-white dark:text-black border-black dark:border-white' : 'text-[#666666] dark:text-[#999999] border-[#E8E8E8] dark:border-[#222222]'"
                            class="px-3 py-2 rounded-full border flex items-center gap-2 transition-colors font-mono text-xs tracking-wide">
                            <LayoutGrid class="w-4 h-4" :stroke-width="1.5" />
                            <span class="hidden sm:inline">Grid</span>
                        </button>
                        <button @click="switchTab('table')" :aria-pressed="activeTab === 'table'"
                            :class="activeTab === 'table' ? 'bg-black dark:bg-white text-white dark:text-black border-black dark:border-white' : 'text-[#666666] dark:text-[#999999] border-[#E8E8E8] dark:border-[#222222]'"
                            class="px-3 py-2 rounded-full border flex items-center gap-2 transition-colors font-mono text-xs tracking-wide">
                            <List class="w-4 h-4" :stroke-width="1.5" />
                            <span class="hidden sm:inline">Table</span>
                        </button>

                        <button @click="showModal = true"
                            class="ml-3 bg-black text-white border-2 border-black hover:bg-white hover:text-black dark:bg-white dark:text-black dark:border-white dark:hover:bg-black dark:hover:text-white rounded-full px-3 py-2 group flex items-center justify-center whitespace-nowrap text-xs font-mono tracking-wide transition-colors"
                            aria-label="Add Preview">
                            <CirclePlus class="w-4 h-4 mr-2 group-hover:rotate-90 transition-transform duration-200"
                                :stroke-width="1.5" />
                            <span class="hidden sm:inline">Add</span>
                        </button>
                    </div>
                </div>

                <!-- Conditional Views -->
                <Transition name="fade-slide" mode="out-in">
                    <div :key="activeTab">
                        <div v-if="activeTab === 'table'">
                            <!-- Desktop Table -->
                            <div
                                class="hidden lg:block overflow-x-auto rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A]">
                                <table class="w-full">
                                    <thead
                                        class="bg-[#F5F5F5] dark:bg-black text-[10px] uppercase font-mono tracking-widest text-[#666666] dark:text-[#999999]">
                                        <tr>
                                            <th class="w-1.5 p-0" aria-hidden="true"></th>
                                            <th class="w-12 px-2 py-2 text-center font-medium">#</th>
                                            <th class="px-2 py-2 text-left font-medium">PREVIEW · CLIENT</th>
                                            <th class="w-40 px-2 py-2 text-left font-medium">TYPES</th>
                                            <th class="w-40 px-2 py-2 text-left font-medium">TEAM</th>
                                            <th class="w-36 px-2 py-2 text-left font-medium">UPLOADED</th>
                                            <th class="w-32 px-2 py-2 text-center font-medium">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#E8E8E8] dark:divide-[#222222]">
                                        <tr v-for="(preview, index) in filteredPreviews" :key="preview.id"
                                            class="group cursor-pointer transition-colors hover:bg-[#F5F5F5] dark:hover:bg-black/40"
                                            @click="openPreview(preview)">
                                            <!-- color palette accent -->
                                            <td class="p-0"
                                                :style="{ backgroundColor: rowAccent(preview) }"
                                                :title="`Brand colour: ${rowAccent(preview)}`"></td>

                                            <td class="px-2 py-2 text-center font-mono text-xs tabular-nums text-[#666666] dark:text-[#999999]">
                                                {{ ((previews.current_page - 1) * previews.per_page) + index + 1 }}
                                            </td>

                                            <!-- Name + client -->
                                            <td class="px-2 py-2 max-w-0">
                                                <div class="truncate text-sm font-semibold text-[#1A1A1A] dark:text-[#E8E8E8]"
                                                    :title="preview.name">
                                                    {{ preview.name || 'Untitled preview' }}
                                                </div>
                                                <div class="mt-0.5 truncate font-mono text-[10px] uppercase tracking-widest text-[#666666] dark:text-[#999999]"
                                                    :title="preview.client?.name">
                                                    {{ preview.client?.name || '— NO CLIENT' }}
                                                </div>
                                            </td>

                                            <!-- Types -->
                                            <td class="px-2 py-2">
                                                <div v-if="getTypeChips(preview).length"
                                                    class="flex flex-wrap items-center gap-1">
                                                    <span v-for="t in getTypeChips(preview)" :key="t.key"
                                                        class="inline-flex items-center gap-1 rounded-md border border-[#E8E8E8] bg-[#F5F5F5] px-1.5 py-0.5 font-mono text-[10px] tracking-wider text-[#1A1A1A] dark:border-[#222222] dark:bg-black dark:text-[#E8E8E8]"
                                                        :title="`${t.count} ${t.label}`">
                                                        <component :is="t.icon" class="h-3 w-3" :stroke-width="1.5" />
                                                        {{ t.count }}
                                                    </span>
                                                </div>
                                                <span v-else
                                                    class="font-mono text-[10px] uppercase tracking-widest text-[#999999]">—</span>
                                            </td>

                                            <!-- Team avatars -->
                                            <td class="px-2 py-2">
                                                <div v-if="preview.team_users?.length"
                                                    class="flex items-center -space-x-1.5">
                                                    <div v-for="u in preview.team_users.slice(0, 4)" :key="u.id"
                                                        class="grid h-6 w-6 place-items-center rounded-full bg-black font-mono text-[9px] font-bold text-white ring-2 ring-white dark:bg-white dark:text-black dark:ring-[#0A0A0A]"
                                                        :title="u.name">
                                                        {{ initials(u.name) }}
                                                    </div>
                                                    <span v-if="preview.team_users.length > 4"
                                                        class="grid h-6 w-6 place-items-center rounded-full bg-[#E8E8E8] font-mono text-[9px] font-bold text-[#1A1A1A] ring-2 ring-white dark:bg-[#222222] dark:text-[#E8E8E8] dark:ring-[#0A0A0A]">
                                                        +{{ preview.team_users.length - 4 }}
                                                    </span>
                                                </div>
                                                <span v-else
                                                    class="font-mono text-[10px] uppercase tracking-widest text-[#999999]">—</span>
                                            </td>

                                            <!-- Uploader + date -->
                                            <td class="px-2 py-2">
                                                <div class="truncate text-xs text-[#1A1A1A] dark:text-[#E8E8E8]"
                                                    :title="preview.uploader?.name">
                                                    {{ preview.uploader?.name ?? '—' }}
                                                </div>
                                                <div class="font-mono text-[10px] text-[#999999]">
                                                    {{ new Date(preview.created_at).toLocaleDateString('en-GB', {
                                                        day: '2-digit', month: 'short', year: 'numeric'
                                                    }) }}
                                                </div>
                                            </td>

                                            <td class="px-2 py-2 text-center" @click.stop>
                                                <div class="flex justify-center">
                                                    <PreviewActionButtons :preview="preview" size="sm"
                                                        :stop-propagation="true" />
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="filteredPreviews.length === 0">
                                            <td colspan="7"
                                                class="px-4 py-12 text-center font-mono text-[10px] uppercase tracking-widest text-[#999999]">
                                                NO PREVIEWS FOUND
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile/Tablet: reuse the redesigned grid card in a single column -->
                            <div class="lg:hidden grid grid-cols-1 gap-4">
                                <PreviewGridCard v-for="preview in filteredPreviews" :key="preview.id"
                                    :preview="preview" :status="getTotals(preview).total === 0
                                        ? 'noFeedback'
                                        : (getTotals(preview).approved >= getTotals(preview).total ? 'completed' : 'inProgress')" />
                                <div v-if="filteredPreviews.length === 0"
                                    class="rounded-lg border border-[#E8E8E8] bg-white p-8 text-center font-mono text-xs uppercase tracking-widest text-[#999999] dark:border-[#222222] dark:bg-[#0A0A0A]">
                                    NO PREVIEWS FOUND
                                </div>
                            </div>
                        </div>

                        <div v-else>
                            <!-- Grid (notion-like grouped view) -->
                            <div v-if="filteredPreviews.length" class="space-y-8">
                                <!-- In Progress -->
                                <section>
                                    <div class="flex items-center justify-between mb-1">
                                        <h3
                                            class="text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                            IN PROGRESS</h3>
                                        <div class="text-xs text-[#666666] dark:text-[#999999] font-mono">{{
                                            Math.min(groups.inProgress.length,
                                            inProgressLimit) }} / {{ groups.inProgress.length }}</div>
                                    </div>
                                    <div class="h-px w-full bg-[#666666] dark:bg-[#999999] mb-4">
                                    </div>
                                    <div ref="inProgressContainer">
                                        <div v-if="groups.inProgress.length"
                                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <PreviewGridCard v-for="p in groups.inProgress.slice(0, inProgressLimit)"
                                                :key="p.id" :preview="p" status="inProgress" />
                                        </div>
                                        <div v-else
                                            class="text-xs text-[#666666] dark:text-[#999999] font-mono tracking-wide">
                                            NO PREVIEWS IN PROGRESS</div>
                                    </div>
                                    <div v-if="groups.inProgress.length > inProgressDefault" class="mt-4 text-center">
                                        <button v-if="inProgressLimit < groups.inProgress.length"
                                            @click="expandGroup('inProgress')"
                                            class="px-4 py-2 text-xs font-mono tracking-wide border border-[#CCCCCC] dark:border-[#333333] text-[#1A1A1A] dark:text-[#E8E8E8] hover:border-black dark:hover:border-white rounded-full transition-colors">
                                            Show {{ Math.min(6, groups.inProgress.length - inProgressLimit) }} more
                                        </button>
                                        <button v-else-if="inProgressLimit > inProgressDefault"
                                            @click="collapseTo('inProgress', inProgressContainer, inProgressDefault)"
                                            class="px-4 py-2 text-xs font-mono tracking-wide border border-[#CCCCCC] dark:border-[#333333] text-[#1A1A1A] dark:text-[#E8E8E8] hover:border-black dark:hover:border-white rounded-full transition-colors">
                                            Show less
                                        </button>
                                    </div>
                                </section>

                                <!-- Completed -->
                                <section>
                                    <div class="flex items-center justify-between mb-1">
                                        <h3
                                            class="text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                            COMPLETED</h3>
                                        <div class="text-xs text-[#666666] dark:text-[#999999] font-mono">{{
                                            Math.min(groups.completed.length,
                                            completedLimit) }} / {{ groups.completed.length }}</div>
                                    </div>
                                    <div class="h-px w-full bg-black dark:bg-white mb-4">
                                    </div>
                                    <div ref="completedContainer">
                                        <div v-if="groups.completed.length"
                                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <PreviewGridCard v-for="p in groups.completed.slice(0, completedLimit)"
                                                :key="p.id" :preview="p" status="completed" />
                                        </div>
                                        <div v-else
                                            class="text-xs text-[#666666] dark:text-[#999999] font-mono tracking-wide">
                                            NO COMPLETED PREVIEWS</div>
                                    </div>
                                    <div v-if="groups.completed.length > completedDefault" class="mt-4 text-center">
                                        <button v-if="completedLimit < groups.completed.length"
                                            @click="expandGroup('completed')"
                                            class="px-4 py-2 text-xs font-mono tracking-wide border border-[#CCCCCC] dark:border-[#333333] text-[#1A1A1A] dark:text-[#E8E8E8] hover:border-black dark:hover:border-white rounded-full transition-colors">
                                            Show {{ Math.min(6, groups.completed.length - completedLimit) }} more
                                        </button>
                                        <button v-else-if="completedLimit > completedDefault"
                                            @click="collapseTo('completed', completedContainer, completedDefault)"
                                            class="px-4 py-2 text-xs font-mono tracking-wide border border-[#CCCCCC] dark:border-[#333333] text-[#1A1A1A] dark:text-[#E8E8E8] hover:border-black dark:hover:border-white rounded-full transition-colors">
                                            Show less
                                        </button>
                                    </div>
                                </section>

                                <!-- No Feedback -->
                                <section>
                                    <div class="flex items-center justify-between mb-1">
                                        <h3
                                            class="text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                            NO FEEDBACK</h3>
                                        <div class="text-xs text-[#666666] dark:text-[#999999] font-mono">{{
                                            Math.min(groups.noFeedback.length,
                                            noFeedbackLimit) }} / {{ groups.noFeedback.length }}</div>
                                    </div>
                                    <div class="h-px w-full bg-[#CCCCCC] dark:bg-[#333333] mb-4">
                                    </div>
                                    <div ref="noFeedbackContainer">
                                        <div v-if="groups.noFeedback.length"
                                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <PreviewGridCard v-for="p in groups.noFeedback.slice(0, noFeedbackLimit)"
                                                :key="p.id" :preview="p" status="noFeedback" />
                                        </div>
                                        <div v-else
                                            class="text-xs text-[#666666] dark:text-[#999999] font-mono tracking-wide">
                                            NO PREVIEWS WITHOUT FEEDBACK</div>
                                    </div>
                                    <div v-if="groups.noFeedback.length > noFeedbackDefault" class="mt-4 text-center">
                                        <button v-if="noFeedbackLimit < groups.noFeedback.length"
                                            @click="expandGroup('noFeedback')"
                                            class="px-4 py-2 text-xs font-mono tracking-wide border border-[#CCCCCC] dark:border-[#333333] text-[#1A1A1A] dark:text-[#E8E8E8] hover:border-black dark:hover:border-white rounded-full transition-colors">
                                            Show {{ Math.min(6, groups.noFeedback.length - noFeedbackLimit) }} more
                                        </button>
                                        <button v-else-if="noFeedbackLimit > noFeedbackDefault"
                                            @click="collapseTo('noFeedback', noFeedbackContainer, noFeedbackDefault)"
                                            class="px-4 py-2 text-xs font-mono tracking-wide border border-[#CCCCCC] dark:border-[#333333] text-[#1A1A1A] dark:text-[#E8E8E8] hover:border-black dark:hover:border-white rounded-full transition-colors">
                                            Show less
                                        </button>
                                    </div>
                                </section>
                            </div>

                            <div v-else
                                class="bg-white dark:bg-[#111111] rounded-lg p-8 text-center border border-[#E8E8E8] dark:border-[#222222]">
                                <div
                                    class="text-[#666666] dark:text-[#999999] font-mono tracking-wide text-sm">
                                    NO PREVIEWS FOUND</div>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Pagination (shown for table view only) -->
                <PreviewPagination v-if="activeTab === 'table' && filteredPreviews.length" :pagination-data="previews"
                    :on-page-change="changePage" />
            </div>

            <!-- Modal -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                @click.self="closeModal">
                <div
                    class="bg-white dark:bg-[#111111] rounded-lg w-full max-w-4xl max-h-[90vh] overflow-hidden border-2 border-black dark:border-white p-2">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-2 py-2 border-b border-[#E8E8E8] dark:border-[#222222]">
                        <h2 class="text-base font-semibold font-mono text-black dark:text-white">Create new preview</h2>
                        <button @click="closeModal"
                            class="p-2 text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white border border-transparent hover:border-[#CCCCCC] dark:hover:border-[#333333] rounded transition-colors">
                            <X class="w-4 h-4" :stroke-width="1.5" />
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="px-2 py-2 overflow-y-auto max-h-[calc(90vh-80px)]">
                        <PreviewStepBasicInfo :form="formData" :users="users" :clients="clients"
                            :colorPalettes="colorPalettes" :authUser="authUser" @submit="submitForm" @close="closeModal"
                            @updateForm="updateFormData" />
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>

<style scoped>
/* Smooth fade + slight vertical slide when switching tabs */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: opacity 200ms ease, transform 200ms ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

.fade-slide-enter-to {
    opacity: 1;
    transform: translateY(0);
}

.fade-slide-leave-from {
    opacity: 1;
    transform: translateY(0);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>