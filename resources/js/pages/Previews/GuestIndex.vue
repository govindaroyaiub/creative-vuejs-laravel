<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Trash2, CirclePlus, X, Share2, Settings2, ChevronLeft, ChevronRight } from 'lucide-vue-next';
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

// Pagination functions
const changePage = (url: string) => {
    if (url) {
        router.get(url, { search: search.value }, {
            preserveScroll: true,
            preserveState: true,
        });
    }
};



</script>

<template>

    <Head title="Previews" />
    <AppLayout :breadcrumbs="[{ title: 'Previews', href: '/previews' }]">
        <div class="p-4 md:p-6 space-y-4">
            <!-- Search & Create -->
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                <input v-model="search" placeholder="SEARCH PREVIEWS..." aria-label="Search previews"
                    class="w-full sm:max-w-xs rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-4 py-2 bg-white dark:bg-[#111111] text-[#1A1A1A] dark:text-[#E8E8E8] placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white font-mono text-sm uppercase tracking-wider" />
            </div>

            <!-- Desktop Table -->
            <div class="hidden lg:block overflow-x-auto rounded-lg">
                <table
                    class="w-full rounded-lg bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] table-fixed">
                    <thead class="bg-[#F5F5F5] dark:bg-black text-xs uppercase font-mono tracking-widest">
                        <tr>
                            <th
                                class="w-16 px-4 py-3 text-center border-b border-[#E8E8E8] dark:border-[#222222] text-[#666666] dark:text-[#999999]">
                                #</th>
                            <th
                                class="w-80 px-4 py-3 text-left border-b border-[#E8E8E8] dark:border-[#222222] text-[#666666] dark:text-[#999999]">
                                NAME & CLIENT</th>
                            <th
                                class="w-36 px-4 py-3 text-center border-b border-[#E8E8E8] dark:border-[#222222] text-[#666666] dark:text-[#999999]">
                                DATE</th>
                            <th
                                class="w-32 px-4 py-3 text-center border-b border-[#E8E8E8] dark:border-[#222222] text-[#666666] dark:text-[#999999]">
                                ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E8E8E8] dark:divide-[#222222]">
                        <tr v-for="(preview, index) in filteredPreviews" :key="preview.id"
                            class="hover:bg-[#F5F5F5] dark:hover:bg-black border-b border-[#E8E8E8] dark:border-[#222222] transition-colors">
                            <td class="w-16 text-center px-4 py-3 font-medium border-b">
                                {{ ((previews.current_page - 1) * previews.per_page) + index + 1 }}
                            </td>
                            <td class="w-80 px-4 py-3 text-left border-b">
                                <div class="font-semibold capitalize break-words" :title="preview.name">{{ preview.name
                                }}</div>
                                <div
                                    class="text-xs text-[#666666] dark:text-[#999999] flex gap-2 items-center font-mono uppercase tracking-wider">
                                    <div class="h-5 w-5 rounded-full border flex-shrink-0"
                                        :style="{ backgroundColor: preview.color_palette?.primary ?? 'red' }"
                                        title="Primary Color"></div>
                                    <span class="break-words">{{ preview.client?.name ?? '-' }}</span> -
                                    <div class="text-xs text-[#666666] dark:text-[#999999] break-words">{{
                                        getTypes(preview) || '-' }}</div>
                                </div>
                            </td>
                            <td class="w-36 px-4 py-3 text-center border-b">
                                <div class="text-xs text-[#666666] dark:text-[#999999] font-mono">{{ new
                                    Date(preview.created_at).toLocaleDateString('en-GB', {
                                        day: '2-digit', month: 'short', year: 'numeric'
                                    }) }}</div>
                            </td>
                            <td class="w-32 text-center px-4 py-3 border-b">
                                <div class="flex justify-center space-x-1">
                                    <a :href="`${preview.client?.preview_url}/previews/show/${preview.slug}`"
                                        class="text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white p-1 rounded-full hover:border hover:border-black dark:hover:border-white transition-colors"
                                        target="_blank" rel="noopener" aria-label="Share Preview">
                                        <Eye class="h-4 w-4" stroke-width="1.5" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredPreviews.length === 0">
                            <td colspan="4"
                                class="px-4 py-6 text-center text-[#666666] dark:text-[#999999] uppercase tracking-widest font-mono">
                                NO PREVIEWS FOUND</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile/Tablet Cards -->
            <div class="lg:hidden space-y-4">
                <div v-for="(preview, index) in filteredPreviews" :key="preview.id"
                    class="bg-[#F5F5F5] dark:bg-black rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4">

                    <!-- Header: Number + Name -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="text-sm font-medium text-[#666666] dark:text-[#999999] font-mono uppercase tracking-wider">
                                    #{{ ((previews.current_page - 1) * previews.per_page) + index + 1 }}
                                </span>
                                <div class="h-4 w-4 rounded-full border flex-shrink-0"
                                    :style="{ backgroundColor: preview.color_palette?.primary ?? '#ccc' }"
                                    title="Primary Color"></div>
                            </div>
                            <h3 class="font-semibold text-lg capitalize break-words text-black dark:text-white">
                                {{ preview.name }}
                            </h3>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 ml-3">
                            <a :href="route('previews-show', preview.slug)"
                                class="text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white p-2 rounded-full hover:border hover:border-black dark:hover:border-white transition-colors"
                                target="_blank" rel="noopener" aria-label="View Preview">
                                <Eye class="h-5 w-5" stroke-width="1.5" />
                            </a>
                            <a :href="`${preview.client?.preview_url}/previews/show/${preview.slug}`"
                                class="text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white p-2 rounded-full hover:border hover:border-black dark:hover:border-white transition-colors"
                                target="_blank" rel="noopener" aria-label="Share Preview">
                                <Share2 class="h-5 w-5" stroke-width="1.5" />
                            </a>
                            <Link :href="route('previews.update.all', preview.id)"
                                class="text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white p-2 rounded-full hover:border hover:border-black dark:hover:border-white transition-colors"
                                aria-label="Edit Preview">
                                <Settings2 class="h-5 w-5" stroke-width="1.5" />
                            </Link>
                            <button @click="deletePreview(preview.id)"
                                class="text-[#D71921] hover:bg-[#D71921] hover:text-white p-2 rounded-full border border-[#D71921] transition-all"
                                aria-label="Delete Preview">
                                <Trash2 class="h-5 w-5" stroke-width="1.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Client & Types -->
                    <div class="mb-3">
                        <div class="text-sm text-[#666666] dark:text-[#999999] uppercase tracking-wider font-mono">
                            <span class="font-medium">CLIENT:</span> {{ preview.client?.name ?? 'No client' }}
                        </div>
                        <div class="text-sm text-[#666666] dark:text-[#999999] uppercase tracking-wider font-mono"
                            v-if="getTypes(preview)">
                            <span class="font-medium">TYPES:</span> {{ getTypes(preview) }}
                        </div>
                    </div>

                    <!-- Team -->
                    <div class="mb-3" v-if="preview.team_users?.length">
                        <div
                            class="text-sm font-medium text-[#666666] dark:text-[#999999] mb-1 uppercase tracking-wider font-mono">
                            TEAM:</div>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="u in preview.team_users" :key="u.id"
                                class="inline-block rounded-full border border-black dark:border-white px-3 py-1 text-xs text-black dark:text-white uppercase tracking-wider font-mono">
                                {{ u.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Uploader & Date -->
                    <div
                        class="flex justify-between items-center text-sm text-[#666666] dark:text-[#999999] uppercase tracking-wider font-mono">
                        <div>
                            <span class="font-medium">UPLOADER:</span> {{ preview.uploader?.name ?? 'Unknown' }}
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
                    class="bg-[#F5F5F5] dark:bg-black rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-8 text-center">
                    <div class="text-[#666666] dark:text-[#999999] uppercase tracking-widest font-mono">NO PREVIEWS
                        FOUND</div>
                </div>
            </div>

            <!-- Pagination - Responsive -->
            <div v-if="filteredPreviews.length && previews.links && previews.links.length" class="mt-6 p-4">

                <!-- Mobile/Tablet pagination (simplified) -->
                <div class="lg:hidden">
                    <!-- Results Info -->
                    <div
                        class="text-sm text-[#666666] dark:text-[#999999] text-center mb-3 uppercase tracking-wider font-mono">
                        Showing {{ previews.from }} to {{ previews.to }} of {{ previews.total }} previews
                    </div>

                    <!-- Simple prev/next navigation -->
                    <div class="flex items-center justify-between gap-4">
                        <button @click="changePage(previews.prev_page_url)" :disabled="!previews.prev_page_url"
                            class="px-4 py-2 text-sm rounded-full transition-all duration-200 flex items-center gap-2 uppercase tracking-wider font-mono"
                            :class="previews.prev_page_url
                                ? 'text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white'
                                : 'text-[#CCCCCC] cursor-not-allowed border-2 border-[#CCCCCC]'">
                            <ChevronLeft class="w-4 h-4" stroke-width="1.5" />
                            Previous
                        </button>

                        <span class="text-sm text-[#666666] dark:text-[#999999] uppercase tracking-wider font-mono">
                            Page {{ previews.current_page }} of {{ previews.last_page }}
                        </span>

                        <button @click="changePage(previews.next_page_url)" :disabled="!previews.next_page_url"
                            class="px-4 py-2 text-sm rounded-full transition-all duration-200 flex items-center gap-2 uppercase tracking-wider font-mono"
                            :class="previews.next_page_url
                                ? 'text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white'
                                : 'text-[#CCCCCC] cursor-not-allowed border-2 border-[#CCCCCC]'">
                            Next
                            <ChevronRight class="w-4 h-4" stroke-width="1.5" />
                        </button>
                    </div>
                </div>

                <!-- Desktop pagination (full features) -->
                <div class="hidden lg:flex items-center justify-between">
                    <!-- Results Info -->
                    <div class="text-sm text-[#666666] dark:text-[#999999] uppercase tracking-wider font-mono">
                        Showing {{ previews.from }} to {{ previews.to }} of {{ previews.total }} previews
                    </div>

                    <!-- Pagination Controls -->
                    <div class="flex items-center space-x-2">
                        <!-- Previous Button -->
                        <button @click="changePage(previews.prev_page_url)" :disabled="!previews.prev_page_url"
                            class="px-3 py-2 text-sm rounded-full transition-all duration-200 flex items-center uppercase tracking-wider font-mono"
                            :class="previews.prev_page_url
                                ? 'text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white'
                                : 'text-[#CCCCCC] cursor-not-allowed border-2 border-[#CCCCCC]'">
                            <ChevronLeft class="w-4 h-4 mr-1" stroke-width="1.5" />
                            Previous
                        </button>

                        <!-- Page Numbers -->
                        <div class="flex items-center space-x-1">
                            <template v-for="link in previews.links.slice(1, -1)" :key="link.label">
                                <button v-if="link.url" @click="changePage(link.url)"
                                    class="px-3 py-2 text-sm rounded-full transition-all duration-200 font-mono tabular-nums"
                                    :class="link.active
                                        ? 'bg-black text-white dark:bg-white dark:text-black border-2 border-black dark:border-white'
                                        : 'text-[#666666] dark:text-[#999999] hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-[#E8E8E8] dark:border-[#222222]'"
                                    v-html="link.label" />
                                <span v-else
                                    class="px-3 py-2 text-sm text-[#CCCCCC] cursor-not-allowed font-mono tabular-nums"
                                    v-html="link.label" />
                            </template>
                        </div>

                        <!-- Next Button -->
                        <button @click="changePage(previews.next_page_url)" :disabled="!previews.next_page_url"
                            class="px-3 py-2 text-sm rounded-full transition-all duration-200 flex items-center uppercase tracking-wider font-mono"
                            :class="previews.next_page_url
                                ? 'text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white'
                                : 'text-[#CCCCCC] cursor-not-allowed border-2 border-[#CCCCCC]'">
                            Next
                            <ChevronRight class="w-4 h-4 ml-1" stroke-width="1.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>