<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Trash2, CirclePlus, X, Share2, Settings2, ChevronLeft, ChevronRight, LayoutGrid, List, ListFilter } from 'lucide-vue-next';
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
dayjs.extend(relativeTime)
import Swal from 'sweetalert2';
import { computed, ref, watch, nextTick, onMounted, onUnmounted } from 'vue';
import PreviewStepBasicInfo from './Partials/PreviewStepBasicInfo.vue';

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

// Server-provided previews are the source of truth; also apply client-side
// filtering so the table updates immediately while server search remains available.
const filteredPreviews = computed(() => {
    let list = previews.value.data || [];

    // Apply search filter
    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter((p: any) =>
            (p.name || '').toLowerCase().includes(q) ||
            (p.client?.name || '').toLowerCase().includes(q) ||
            (p.uploader?.name || '').toLowerCase().includes(q)
        );
    }

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
    router.get(route('previews-index'), {
        search: search.value,
        view: activeTab.value,
        page: 1,
        from_date: filters.value.fromDate,
        to_date: filters.value.toDate,
        uploader_id: filters.value.uploaderId,
        keywords: filters.value.keywords
    }, { preserveState: true, replace: true, onFinish: () => { loading.value = false; showFilters.value = false } })
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
        // Send current view so backend paginates appropriately
        loading.value = true
        router.get(route('previews-index'), {
            search: search.value,
            view: activeTab.value,
            page: 1,
            from_date: filters.value.fromDate,
            to_date: filters.value.toDate,
            uploader_id: filters.value.uploaderId,
            keywords: filters.value.keywords
        }, { preserveState: true, replace: true, onFinish: () => { loading.value = false } })
    }, 350)
}

function switchTab(tab: 'grid' | 'table') {
    activeTab.value = tab
    loading.value = true
    // Send view parameter so backend knows how to paginate
    router.get(route('previews-index'), {
        search: search.value,
        view: tab,
        page: 1,
        from_date: filters.value.fromDate,
        to_date: filters.value.toDate,
        uploader_id: filters.value.uploaderId,
        keywords: filters.value.keywords
    }, { preserveState: true, replace: true, onFinish: () => { loading.value = false } })
}

function formatDateRelative(dateStr: string) {
    if (!dateStr) return ''
    return dayjs(dateStr).fromNow()
}

const deletePreview = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the preview.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        router.delete(route('previews-delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire('Deleted!', 'Preview deleted successfully.', 'success'),
            onError: () => Swal.fire('Error!', 'Failed to delete preview.', 'error'),
        });
    }
};

const getTypes = (preview: any) => {
    const types = new Set();
    preview.categories?.forEach((v: any) => types.add((v.type || '').toLowerCase()));
    return Array.from(types).join(', ');
};

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
        router.get(url, {
            search: search.value,
            view: activeTab.value,
            from_date: filters.value.fromDate,
            to_date: filters.value.toDate,
            uploader_id: filters.value.uploaderId,
            keywords: filters.value.keywords
        }, {
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

function expandGroup(group: string) {
    if (group === 'inProgress') {
        const remaining = groups.value.inProgress.length - inProgressLimit.value
        inProgressLimit.value = Math.min(inProgressLimit.value + 6, groups.value.inProgress.length)
    } else if (group === 'completed') {
        const remaining = groups.value.completed.length - completedLimit.value
        completedLimit.value = Math.min(completedLimit.value + 6, groups.value.completed.length)
    } else if (group === 'noFeedback') {
        const remaining = groups.value.noFeedback.length - noFeedbackLimit.value
        noFeedbackLimit.value = Math.min(noFeedbackLimit.value + 6, groups.value.noFeedback.length)
    }
}

const groups = computed(() => {
    const inProgress: any[] = []
    const completed: any[] = []
    const noFeedback: any[] = []

    // Use the same source as filteredPreviews so grouping matches search results
    const sourceList = previews.value.data || []
    const q = (search.value || '').toLowerCase()
    const list = q
        ? sourceList.filter((p: any) =>
            (p.name || '').toLowerCase().includes(q) ||
            (p.client?.name || '').toLowerCase().includes(q) ||
            (p.uploader?.name || '').toLowerCase().includes(q)
        )
        : sourceList

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
        <div
            class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-50 to-gray-50 dark:from-black dark:via-black dark:to-black">
            <div class="p-4 md:p-6 space-y-4">
                <!-- Tabs / Search (Add button placed next to tabs) -->
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1 flex items-center gap-2">
                        <input v-model="search" @input="onSearchInput" placeholder="Search..."
                            aria-label="Search previews"
                            class="w-full max-w-xs rounded-2xl border px-4 py-2 dark:bg-neutral-800 dark:text-white" />

                        <!-- Filter Button -->
                        <div class="relative">
                            <button @click.stop="showFilters = !showFilters"
                                :class="(filters.fromDate || filters.toDate || filters.uploaderId || filters.keywords) ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-700' : 'bg-white dark:bg-neutral-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-neutral-600'"
                                class="px-2 py-2 rounded-xl border flex items-center gap-2 hover:bg-gray-50 dark:hover:bg-neutral-700 transition relative">
                                <ListFilter class="w-5 h-5" />
                                <span
                                    v-if="filters.fromDate || filters.toDate || filters.uploaderId || filters.keywords"
                                    class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-blue-600 rounded-full border-2 border-white dark:border-neutral-800"></span>
                            </button>

                            <!-- Filter Dropdown -->
                            <Transition name="fade-slide">
                                <div v-if="showFilters" v-click-away="() => showFilters = false" @click.stop
                                    class="absolute left-0 mt-2 w-80 bg-white dark:bg-neutral-800 rounded-xl shadow-lg border border-gray-200 dark:border-neutral-700 p-4 z-50">
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                                                <input v-model="filters.fromDate" type="date"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-neutral-600 px-2 py-2 text-sm dark:bg-neutral-700 dark:text-white" />
                                            </div>

                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                                                <input v-model="filters.toDate" type="date"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-neutral-600 px-2 py-2 text-sm dark:bg-neutral-700 dark:text-white" />
                                            </div>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Uploaded
                                                By</label>
                                            <select v-model="filters.uploaderId"
                                                class="w-full rounded-lg border border-gray-300 dark:border-neutral-600 px-3 py-2 dark:bg-neutral-700 dark:text-white">
                                                <option value="">All Users</option>
                                                <option v-for="user in users" :key="user.id" :value="user.id">{{
                                                    user.name
                                                    }}</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keywords</label>
                                            <input v-model="filters.keywords" type="text"
                                                placeholder="Enter keywords..."
                                                class="w-full rounded-lg border border-gray-300 dark:border-neutral-600 px-3 py-2 dark:bg-neutral-700 dark:text-white" />
                                        </div>

                                        <div class="flex gap-2 pt-2">
                                            <button @click="applyFilters"
                                                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                                Apply Filters
                                            </button>
                                            <button @click="clearFilters"
                                                class="px-4 py-2 bg-gray-200 dark:bg-neutral-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-neutral-600 transition">
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
                            :class="activeTab === 'grid' ? 'bg-gray-100 dark:bg-neutral-900 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'"
                            class="px-3 py-2 rounded-xl flex items-center gap-2">
                            <LayoutGrid class="w-5 h-5" />
                            <span class="hidden sm:inline">Grid</span>
                        </button>
                        <button @click="switchTab('table')" :aria-pressed="activeTab === 'table'"
                            :class="activeTab === 'table' ? 'bg-gray-100 dark:bg-neutral-900 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'"
                            class="px-3 py-2 rounded-xl flex items-center gap-2">
                            <List class="w-5 h-5" />
                            <span class="hidden sm:inline">Table</span>
                        </button>

                        <button @click="showModal = true"
                            class="ml-3 rounded-xl bg-green-600 px-3 py-2 text-white hover:bg-green-700 group flex items-center justify-center whitespace-nowrap"
                            aria-label="Add Preview">
                            <CirclePlus class="w-4 h-4 mr-2 group-hover:rotate-90 transition-transform duration-200" />
                            <span class="hidden sm:inline">Add Preview</span>
                        </button>
                    </div>
                </div>

                <!-- Conditional Views -->
                <Transition name="fade-slide" mode="out-in">
                    <div :key="activeTab">
                        <div v-if="activeTab === 'table'">
                            <!-- Desktop Table -->
                            <div class="hidden lg:block overflow-x-auto rounded-2xl shadow">
                                <table
                                    class="w-full rounded-2xl bg-white shadow dark:bg-neutral-800 dark:border border table-fixed">
                                    <thead class="bg-gray-100 dark:bg-neutral-900 text-xs uppercase">
                                        <tr>
                                            <th class="w-16 px-4 py-3 text-center border-b">#</th>
                                            <th class="w-80 px-4 py-3 text-left border-b">Name & Client</th>
                                            <th class="w-48 px-4 py-3 text-center border-b">Team</th>
                                            <th class="w-36 px-4 py-3 text-center border-b">Uploader</th>
                                            <th class="w-32 px-4 py-3 text-center border-b">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-black">
                                        <tr v-for="(preview, index) in filteredPreviews" :key="preview.id"
                                            class="hover:bg-gray-50 dark:hover:bg-neutral-700 border-b">
                                            <td class="w-16 text-center px-4 py-3 font-medium border-b">
                                                {{ ((previews.current_page - 1) * previews.per_page) + index + 1 }}
                                            </td>
                                            <td class="w-80 px-4 py-3 text-left border-b">
                                                <div class="font-semibold capitalize break-words" :title="preview.name">
                                                    {{
                                                        preview.name
                                                    }}</div>
                                                <div class="text-xs text-gray-500 flex gap-2 items-center">
                                                    <div class="h-5 w-5 rounded-full border flex-shrink-0"
                                                        :style="{ backgroundColor: preview.color_palette?.primary ?? 'red' }"
                                                        title="Primary Color"></div>
                                                    <span class="break-words">{{ preview.client?.name ?? '-' }}</span> -
                                                    <div class="text-xs text-gray-400 break-words">{{ getTypes(preview)
                                                        ||
                                                        '-' }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="w-48 px-4 py-3 text-center border-b">
                                                <div class="flex justify-center flex-wrap gap-1">
                                                    <span v-for="u in preview.team_users" :key="u.id"
                                                        class="inline-block rounded-2xl bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700 dark:bg-indigo-800 dark:text-white truncate">
                                                        {{ u.name }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="w-36 px-4 py-3 text-center border-b">
                                                <div class="text-sm truncate" :title="preview.uploader?.name">{{
                                                    preview.uploader?.name
                                                    ?? '-' }}</div>
                                                <div class="text-xs text-gray-400">{{ new
                                                    Date(preview.created_at).toLocaleDateString('en-GB', {
                                                        day: '2-digit', month: 'short', year: 'numeric'
                                                    }) }}</div>
                                            </td>
                                            <td class="w-32 text-center px-4 py-3 border-b">
                                                <div class="flex justify-center space-x-1">
                                                    <a :href="route('previews-show', preview.slug)"
                                                        class="text-green-600 hover:text-green-800 p-1" target="_blank"
                                                        rel="noopener" aria-label="View Preview">
                                                        <Eye class="h-4 w-4" />
                                                    </a>
                                                    <a :href="`${preview.client?.preview_url}/previews/show/${preview.slug}`"
                                                        class="text-yellow-600 hover:text-yellow-800 p-1"
                                                        target="_blank" rel="noopener" aria-label="Share Preview">
                                                        <Share2 class="h-4 w-4" />
                                                    </a>
                                                    <Link :href="route('previews.update.all', preview.id)"
                                                        class="text-indigo-600 hover:text-indigo-800 p-1"
                                                        aria-label="Edit Preview">
                                                        <Settings2 class="h-4 w-4" />
                                                    </Link>
                                                    <button @click="deletePreview(preview.id)"
                                                        class="text-red-600 hover:text-red-800 p-1"
                                                        aria-label="Delete Preview">
                                                        <Trash2 class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="filteredPreviews.length === 0">
                                            <td colspan="5"
                                                class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                                No
                                                previews
                                                found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile/Tablet Cards (table view simplified) -->
                            <div class="lg:hidden space-y-4">
                                <div v-for="(preview, index) in filteredPreviews" :key="preview.id"
                                    class="bg-white dark:bg-neutral-800 rounded-2xl shadow border border-gray-200 dark:border-neutral-700 p-4">

                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                    #{{ ((previews.current_page - 1) * previews.per_page) + index + 1 }}
                                                </span>
                                                <div class="h-4 w-4 rounded-full border flex-shrink-0"
                                                    :style="{ backgroundColor: preview.color_palette?.primary ?? '#ccc' }"
                                                    title="Primary Color"></div>
                                            </div>
                                            <h3
                                                class="font-semibold text-lg capitalize break-words text-gray-900 dark:text-white">
                                                {{ preview.name }}
                                            </h3>
                                        </div>

                                        <div class="flex gap-2 ml-3">
                                            <a :href="route('previews-show', preview.slug)"
                                                class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20"
                                                target="_blank" rel="noopener" aria-label="View Preview">
                                                <Eye class="h-5 w-5" />
                                            </a>
                                            <a :href="`${preview.client?.preview_url}/previews/show/${preview.slug}`"
                                                class="text-yellow-600 hover:text-yellow-800 p-2 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20"
                                                target="_blank" rel="noopener" aria-label="Share Preview">
                                                <Share2 class="h-5 w-5" />
                                            </a>
                                            <Link :href="route('previews.update.all', preview.id)"
                                                class="text-indigo-600 hover:text-indigo-800 p-2 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                                                aria-label="Edit Preview">
                                                <Settings2 class="h-5 w-5" />
                                            </Link>
                                            <button @click="deletePreview(preview.id)"
                                                class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20"
                                                aria-label="Delete Preview">
                                                <Trash2 class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">Client:</span> {{ preview.client?.name ?? 'No client'}}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400" v-if="getTypes(preview)">
                                            <span class="font-medium">Types:</span> {{ getTypes(preview) }}
                                        </div>
                                    </div>

                                    <div
                                        class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
                                        <div>
                                            <span class="font-medium">Uploader:</span> {{ preview.uploader?.name ??
                                                'Unknown' }}
                                        </div>
                                        <div>
                                            {{ new Date(preview.created_at).toLocaleDateString('en-GB', {
                                                day: '2-digit', month: 'short', year: 'numeric'
                                            }) }}
                                        </div>
                                    </div>
                                </div>

                                <div v-if="filteredPreviews.length === 0"
                                    class="bg-white dark:bg-neutral-800 rounded-2xl shadow border border-gray-200 dark:border-neutral-700 p-8 text-center">
                                    <div class="text-gray-500 dark:text-gray-400">No previews found.</div>
                                </div>
                            </div>
                        </div>

                        <div v-else>
                            <!-- Grid (notion-like grouped view) -->
                            <div v-if="filteredPreviews.length" class="space-y-8">
                                <!-- In Progress -->
                                <section>
                                    <div class="flex items-center justify-between mb-1">
                                        <h3 class="text-lg font-semibold whitespace-nowrap">In Progress</h3>
                                        <div class="text-sm text-gray-500">Showing {{ Math.min(groups.inProgress.length,
                                            inProgressLimit) }} of {{ groups.inProgress.length }}</div>
                                    </div>
                                    <div
                                        class="h-0.5 w-full bg-gradient-to-r from-yellow-400 via-yellow-300 to-yellow-200 rounded-full mb-4">
                                    </div>
                                    <div ref="inProgressContainer">
                                        <div v-if="groups.inProgress.length"
                                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <div v-for="p in groups.inProgress.slice(0, inProgressLimit)" :key="p.id"
                                                class="bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700 shadow p-3 hover:shadow-md transition">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <a :href="`/previews/update/${p.id}`"
                                                            class="text-lg font-semibold text-blue-600 dark:text-blue-300 hover:underline">{{
                                                                p.name }}</a>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">Created
                                                            by: {{
                                                                p.uploader?.name || 'System' }}</div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="text-xs text-gray-500">{{
                                                            formatDateRelative(p.created_at)
                                                            }}</div>
                                                        <div class="mt-2"><span
                                                                class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 whitespace-nowrap">In
                                                                Progress</span></div>
                                                    </div>
                                                </div>
                                                <div class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                                    <div class="text-xs text-gray-500">Latest Summary:</div>
                                                    <div
                                                        class="mt-3 text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-neutral-700 rounded-md p-2">
                                                        {{ p.latest_feedback_description ?
                                                            (p.latest_feedback_description.length
                                                                > 150 ?
                                                                p.latest_feedback_description.slice(0, 150) + '...' :
                                                                p.latest_feedback_description) : 'No recent feedback summary' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-sm text-gray-500">No previews in progress.</div>
                                    </div>
                                    <div v-if="groups.inProgress.length > inProgressDefault" class="mt-4 text-center">
                                        <button v-if="inProgressLimit < groups.inProgress.length"
                                            @click="expandGroup('inProgress')"
                                            class="px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-neutral-700 rounded-lg transition">
                                            See More ({{ Math.min(6, groups.inProgress.length - inProgressLimit) }}
                                            more)
                                        </button>
                                        <button v-else-if="inProgressLimit > inProgressDefault"
                                            @click="collapseTo('inProgress', inProgressContainer, inProgressDefault)"
                                            class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-neutral-700 rounded-lg transition">
                                            See Less
                                        </button>
                                    </div>
                                </section>

                                <!-- Completed -->
                                <section>
                                    <div class="flex items-center justify-between mb-1">
                                        <h3 class="text-lg font-semibold whitespace-nowrap">Completed</h3>
                                        <div class="text-sm text-gray-500">Showing {{ Math.min(groups.completed.length,
                                            completedLimit) }} of {{ groups.completed.length }}</div>
                                    </div>
                                    <div
                                        class="h-0.5 w-full bg-gradient-to-r from-green-400 via-green-300 to-green-200 rounded-full mb-4">
                                    </div>
                                    <div ref="completedContainer">
                                        <div v-if="groups.completed.length"
                                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <div v-for="p in groups.completed.slice(0, completedLimit)" :key="p.id"
                                                class="bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700 shadow p-4 hover:shadow-md transition">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <a :href="`/previews/update/${p.id}`"
                                                            class="text-lg font-semibold text-blue-600 dark:text-blue-300 hover:underline">{{
                                                                p.name }}</a>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">Created
                                                            by: {{
                                                                p.uploader?.name || 'System' }}</div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="text-xs text-gray-500">{{
                                                            formatDateRelative(p.created_at)
                                                            }}</div>
                                                        <div class="mt-2"><span
                                                                class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 whitespace-nowrap">Completed</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                                    <div class="text-xs text-gray-500">Latest Summary:</div>
                                                    <div
                                                        class="mt-3 text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-neutral-700 rounded-md p-2">
                                                        {{ p.latest_feedback_description ?
                                                            (p.latest_feedback_description.length
                                                                > 150 ?
                                                                p.latest_feedback_description.slice(0, 150) + '...' :
                                                                p.latest_feedback_description) : 'No recent feedback summary' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-sm text-gray-500">No completed previews.</div>
                                    </div>
                                    <div v-if="groups.completed.length > completedDefault" class="mt-4 text-center">
                                        <button v-if="completedLimit < groups.completed.length"
                                            @click="expandGroup('completed')"
                                            class="px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-neutral-700 rounded-lg transition">
                                            See More ({{ Math.min(6, groups.completed.length - completedLimit) }} more)
                                        </button>
                                        <button v-else-if="completedLimit > completedDefault"
                                            @click="collapseTo('completed', completedContainer, completedDefault)"
                                            class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-neutral-700 rounded-lg transition">
                                            See Less
                                        </button>
                                    </div>
                                </section>

                                <!-- No Feedback -->
                                <section>
                                    <div class="flex items-center justify-between mb-1">
                                        <h3 class="text-lg font-semibold whitespace-nowrap">No Feedback</h3>
                                        <div class="text-sm text-gray-500">Showing {{ Math.min(groups.noFeedback.length,
                                            noFeedbackLimit) }} of {{ groups.noFeedback.length }}</div>
                                    </div>
                                    <div
                                        class="h-0.5 w-full bg-gradient-to-r from-gray-400 via-gray-300 to-gray-200 rounded-full mb-4">
                                    </div>
                                    <div ref="noFeedbackContainer">
                                        <div v-if="groups.noFeedback.length"
                                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <div v-for="p in groups.noFeedback.slice(0, noFeedbackLimit)" :key="p.id"
                                                class="bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700 shadow p-4 hover:shadow-md transition">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <a :href="`/previews/update/${p.id}`"
                                                            class="text-lg font-semibold text-blue-600 dark:text-blue-300 hover:underline">{{
                                                                p.name }}</a>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">Created
                                                            by: {{
                                                                p.uploader?.name || 'System' }}</div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="text-xs text-gray-500">{{
                                                            formatDateRelative(p.created_at)
                                                            }}</div>
                                                        <div class="mt-2"><span
                                                                class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">No
                                                                Feedback</span></div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="mt-3 text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-neutral-700 rounded-md p-2">
                                                    {{ p.latest_feedback_description ?
                                                        (p.latest_feedback_description.length
                                                            >
                                                            150 ?
                                                            p.latest_feedback_description.slice(0, 150) + '...' :
                                                            p.latest_feedback_description)
                                                    : 'No recent feedback summary' }}</div>
                                            </div>
                                        </div>
                                        <div v-else class="text-sm text-gray-500">No previews without feedback.</div>
                                    </div>
                                    <div v-if="groups.noFeedback.length > noFeedbackDefault" class="mt-4 text-center">
                                        <button v-if="noFeedbackLimit < groups.noFeedback.length"
                                            @click="expandGroup('noFeedback')"
                                            class="px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-neutral-700 rounded-lg transition">
                                            See More ({{ Math.min(6, groups.noFeedback.length - noFeedbackLimit) }}
                                            more)
                                        </button>
                                        <button v-else-if="noFeedbackLimit > noFeedbackDefault"
                                            @click="collapseTo('noFeedback', noFeedbackContainer, noFeedbackDefault)"
                                            class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-neutral-700 rounded-lg transition">
                                            See Less
                                        </button>
                                    </div>
                                </section>
                            </div>

                            <div v-else class="bg-white dark:bg-neutral-800 rounded-xl p-8 text-center">
                                <div class="text-gray-600 dark:text-gray-400">No previews found.</div>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Pagination (shown for table view only) -->
                <div v-if="activeTab === 'table' && filteredPreviews.length && previews.links && previews.links.length"
                    class="mt-6 p-4">

                    <!-- Mobile/Tablet pagination (simplified) -->
                    <div class="lg:hidden">
                        <!-- Results Info -->
                        <div class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">
                            Showing {{ previews.from }} to {{ previews.to }} of {{ previews.total }} previews
                        </div>

                        <!-- Simple prev/next navigation -->
                        <div class="flex items-center justify-between gap-4">
                            <button @click="changePage(previews.prev_page_url)" :disabled="!previews.prev_page_url"
                                class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2"
                                :class="previews.prev_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                <ChevronLeft class="w-4 h-4" />
                                Previous
                            </button>

                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                Page {{ previews.current_page }} of {{ previews.last_page }}
                            </span>

                            <button @click="changePage(previews.next_page_url)" :disabled="!previews.next_page_url"
                                class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2"
                                :class="previews.next_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                Next
                                <ChevronRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Desktop pagination (full features) -->
                    <div class="hidden lg:flex items-center justify-between">
                        <!-- Results Info -->
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Showing {{ previews.from }} to {{ previews.to }} of {{ previews.total }} previews
                        </div>

                        <!-- Pagination Controls -->
                        <div class="flex items-center space-x-2">
                            <!-- Previous Button -->
                            <button @click="changePage(previews.prev_page_url)" :disabled="!previews.prev_page_url"
                                class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center"
                                :class="previews.prev_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                <ChevronLeft class="w-4 h-4 mr-1" />
                                Previous
                            </button>

                            <!-- Page Numbers -->
                            <div class="flex items-center space-x-1">
                                <template v-for="link in previews.links.slice(1, -1)" :key="link.label">
                                    <button v-if="link.url" @click="changePage(link.url)"
                                        class="px-3 py-2 text-sm rounded-lg transition-all duration-200"
                                        :class="link.active
                                            ? 'bg-blue-600 text-white'
                                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'"
                                        v-html="link.label" />
                                    <span v-else class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed"
                                        v-html="link.label" />
                                </template>
                            </div>

                            <!-- Next Button -->
                            <button @click="changePage(previews.next_page_url)" :disabled="!previews.next_page_url"
                                class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center"
                                :class="previews.next_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                Next
                                <ChevronRight class="w-4 h-4 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4"
                @click.self="closeModal">
                <div
                    class="bg-white dark:bg-neutral-800 rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl border border-gray-200 dark:border-neutral-700">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-neutral-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Create New Preview</h2>
                        <button @click="closeModal"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg transition-all duration-200">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
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