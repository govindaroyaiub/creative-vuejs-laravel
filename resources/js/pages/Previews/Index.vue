<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Pencil, Trash2, CirclePlus, X, Share2, Settings2, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';
import PreviewStepBasicInfo from './Partials/PreviewStepBasicInfo.vue';

const loading = ref(false);
const page = usePage();
const search = ref('');
const showModal = ref(false);

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

const filteredPreviews = computed(() => {
    if (search.value) {
        const q = search.value.toLowerCase();
        return previews.value.data.filter(
            (p: any) =>
                p.name.toLowerCase().includes(q) ||
                p.client?.name?.toLowerCase().includes(q) ||
                p.uploader?.name?.toLowerCase().includes(q)
        );
    }
    return previews.value.data;
});

watch(search, (val) => {
    router.get(route('previews-index'), { search: val }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
});

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
                timer: 3000,
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

// Pagination functions
const changePage = (url: string) => {
    if (url) {
        router.get(url, { search: search.value }, {
            preserveScroll: true,
            preserveState: true,
        });
    }
};

const goToPage = (pageNumber: number) => {
    router.get(route('previews-index'), {
        page: pageNumber,
        search: search.value
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

</script>

<template>

    <Head title="Previews" />
    <AppLayout :breadcrumbs="[{ title: 'Previews', href: '/previews' }]">
        <div class="p-4 md:p-6 space-y-4">
            <!-- Search & Create -->
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                <input v-model="search" placeholder="Search..." aria-label="Search previews"
                    class="w-full sm:max-w-xs rounded-2xl border px-4 py-2 dark:bg-black dark:text-white" />
                <button @click="showModal = true"
                    class="rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700 group flex items-center justify-center whitespace-nowrap"
                    aria-label="Add Preview">
                    <CirclePlus class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-200" />
                    Add Preview
                </button>
            </div>

            <!-- Desktop Table -->
            <div class="hidden lg:block overflow-x-auto rounded-2xl shadow">
                <table class="w-full rounded-2xl bg-white shadow dark:bg-black dark:border border table-fixed">
                    <thead class="bg-gray-100 dark:bg-black text-xs uppercase">
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
                            class="hover:bg-gray-50 dark:hover:bg-gray-800 border-b">
                            <td class="w-16 text-center px-4 py-3 font-medium border-b">
                                {{ ((previews.current_page - 1) * previews.per_page) + index + 1 }}
                            </td>
                            <td class="w-80 px-4 py-3 text-left border-b">
                                <div class="font-semibold capitalize break-words" :title="preview.name">{{ preview.name
                                    }}</div>
                                <div class="text-xs text-gray-500 flex gap-2 items-center">
                                    <div class="h-5 w-5 rounded-full border flex-shrink-0"
                                        :style="{ backgroundColor: preview.color_palette?.primary ?? '#ccc' }"
                                        title="Primary Color"></div>
                                    <span class="break-words">{{ preview.client?.name ?? '-' }}</span> -
                                    <div class="text-xs text-gray-400 break-words">{{ getTypes(preview) || '-' }}</div>
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
                                <div class="text-sm truncate" :title="preview.uploader?.name">{{ preview.uploader?.name
                                    ?? '-' }}</div>
                                <div class="text-xs text-gray-400">{{ new
                                    Date(preview.created_at).toLocaleDateString('en-GB', {
                                        day: '2-digit', month: 'short', year: 'numeric'
                                    }) }}</div>
                            </td>
                            <td class="w-32 text-center px-4 py-3 border-b">
                                <div class="flex justify-center space-x-1">
                                    <a :href="route('previews-show', preview.slug)"
                                        class="text-green-600 hover:text-green-800 p-1" target="_blank" rel="noopener"
                                        aria-label="View Preview">
                                        <Eye class="h-4 w-4" />
                                    </a>
                                    <a :href="`${preview.client?.preview_url}/previews/show/${preview.slug}`"
                                        class="text-yellow-600 hover:text-yellow-800 p-1" target="_blank" rel="noopener"
                                        aria-label="Share Preview">
                                        <Share2 class="h-4 w-4" />
                                    </a>
                                    <Link :href="route('previews.update.all', preview.id)"
                                        class="text-indigo-600 hover:text-indigo-800 p-1" aria-label="Edit Preview">
                                    <Settings2 class="h-4 w-4" />
                                    </Link>
                                    <button @click="deletePreview(preview.id)"
                                        class="text-red-600 hover:text-red-800 p-1" aria-label="Delete Preview">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredPreviews.length === 0">
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No previews
                                found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile/Tablet Cards -->
            <div class="lg:hidden space-y-4">
                <div v-for="(preview, index) in filteredPreviews" :key="preview.id"
                    class="bg-white dark:bg-black rounded-2xl shadow border border-gray-200 dark:border-gray-700 p-4">

                    <!-- Header: Number + Name -->
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
                            <h3 class="font-semibold text-lg capitalize break-words text-gray-900 dark:text-white">
                                {{ preview.name }}
                            </h3>
                        </div>

                        <!-- Actions -->
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

                    <!-- Client & Types -->
                    <div class="mb-3">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Client:</span> {{ preview.client?.name ?? 'No client' }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400" v-if="getTypes(preview)">
                            <span class="font-medium">Types:</span> {{ getTypes(preview) }}
                        </div>
                    </div>

                    <!-- Team -->
                    <div class="mb-3" v-if="preview.team_users?.length">
                        <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Team:</div>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="u in preview.team_users" :key="u.id"
                                class="inline-block rounded-xl bg-indigo-100 px-3 py-1 text-sm text-indigo-700 dark:bg-indigo-800 dark:text-white">
                                {{ u.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Uploader & Date -->
                    <div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
                        <div>
                            <span class="font-medium">Uploader:</span> {{ preview.uploader?.name ?? 'Unknown' }}
                        </div>
                        <div>
                            {{ new Date(preview.created_at).toLocaleDateString('en-GB', {
                                day: '2-digit', month: 'short', year: 'numeric'
                            }) }}
                        </div>
                    </div>
                </div>

                <!-- No results card -->
                <div v-if="filteredPreviews.length === 0"
                    class="bg-white dark:bg-black rounded-2xl shadow border border-gray-200 dark:border-gray-700 p-8 text-center">
                    <div class="text-gray-500 dark:text-gray-400">No previews found.</div>
                </div>
            </div>

            <!-- Pagination - responsive -->
            <div v-if="previews.links && previews.links.length"
                class="bg-white dark:bg-black rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">

                <!-- Mobile pagination (simplified) -->
                <div class="lg:hidden">
                    <!-- Results Info -->
                    <div class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">
                        Showing {{ previews.from }} to {{ previews.to }} of {{ previews.total }} previews
                    </div>

                    <!-- Simple prev/next + page selector -->
                    <div class="flex items-center justify-between gap-2">
                        <button @click="changePage(previews.prev_page_url)" :disabled="!previews.prev_page_url"
                            class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center flex-1 justify-center"
                            :class="previews.prev_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-gray-600'
                                : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-gray-700'">
                            <ChevronLeft class="w-4 h-4 mr-1" />
                            Previous
                        </button>

                        <select :value="previews.current_page" @change="goToPage(parseInt($event.target.value))"
                            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-black text-gray-900 dark:text-white text-sm min-w-0">
                            <option v-for="pageNum in previews.last_page" :key="pageNum" :value="pageNum">
                                {{ pageNum }}
                            </option>
                        </select>

                        <button @click="changePage(previews.next_page_url)" :disabled="!previews.next_page_url"
                            class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center flex-1 justify-center"
                            :class="previews.next_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-gray-600'
                                : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-gray-700'">
                            Next
                            <ChevronRight class="w-4 h-4 ml-1" />
                        </button>
                    </div>
                </div>

                <!-- Desktop pagination (full features) -->
                <div class="hidden lg:flex items-center justify-between">
                    <!-- Results Info -->
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Showing {{ previews.from }} to {{ previews.to }} of {{ previews.total }} previews
                    </div>

                    <!-- Quick Page Jump -->
                    <div class="flex items-center justify-center space-x-2 text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Go to page:</span>
                        <select :value="previews.current_page" @change="goToPage(parseInt($event.target.value))"
                            class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-black text-gray-900 dark:text-white text-sm">
                            <option v-for="pageNum in previews.last_page" :key="pageNum" :value="pageNum">
                                {{ pageNum }}
                            </option>
                        </select>
                        <span class="text-gray-500 dark:text-gray-400">of {{ previews.last_page }}</span>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="flex items-center space-x-2">
                        <!-- Previous Button -->
                        <button @click="changePage(previews.prev_page_url)" :disabled="!previews.prev_page_url"
                            class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center" :class="previews.prev_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900'
                                : 'text-gray-400 cursor-not-allowed'">
                            <ChevronLeft class="w-4 h-4 mr-1" />
                            Previous
                        </button>

                        <!-- Page Numbers -->
                        <div class="flex items-center space-x-1">
                            <template v-for="link in previews.links.slice(1, -1)" :key="link.label">
                                <button v-if="link.url" @click="changePage(link.url)"
                                    class="px-3 py-2 text-sm rounded-lg transition-all duration-200" :class="link.active
                                        ? 'bg-blue-600 text-white'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900'"
                                    v-html="link.label" />
                                <span v-else class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed"
                                    v-html="link.label" />
                            </template>
                        </div>

                        <!-- Next Button -->
                        <button @click="changePage(previews.next_page_url)" :disabled="!previews.next_page_url"
                            class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center" :class="previews.next_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900'
                                : 'text-gray-400 cursor-not-allowed'">
                            Next
                            <ChevronRight class="w-4 h-4 ml-1" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4">
            <div
                class="bg-white dark:bg-black rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl border border-gray-200 dark:border-gray-700">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Create New Preview</h2>
                    <button @click="closeModal"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg transition-all duration-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
                    <PreviewStepBasicInfo :form="formData" :users="users" :clients="clients"
                        :colorPalettes="colorPalettes" :authUser="authUser" @submit="submitForm" @close="closeModal" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>