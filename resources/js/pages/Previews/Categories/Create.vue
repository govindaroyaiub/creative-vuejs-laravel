<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';
import draggable from 'vuedraggable';
import { X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import axios from 'axios';

const saving = ref(false);

const page = usePage();
const preview = computed(() => page.props.preview);
const bannerSizes = computed(() =>
    page.props.bannerSizes.map((s: any) => ({
        id: s.id,
        label: `${s.width}x${s.height}`,
    })),
);
const videoSizes = computed(() =>
    page.props.videoSizes?.map((s: any) => ({
        id: s.id,
        label: `${s.width}x${s.height}`,
    })) ?? [],
);

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.slug}` },
    { title: 'Create New Category', href: '#' },
];

/* ------------------------------ State ------------------------------ */

type BannerItem = {
    id: string;
    file: File;
    size_id: number | ''; // require before submit
    search?: string;
    dropdownOpen?: boolean;
    highlightIndex?: number;
};
type VersionItem = {
    id: string;
    name?: string;
    isDragging: boolean;
    banners: BannerItem[];
};
type SetItem = {
    id: string;
    name: string;
    isDragging: boolean;
    versions: VersionItem[];
};

const categoryName = ref('');
const description = ref('');
const feedbackName = ref('F1');
const activeType = ref<'banner' | 'video' | 'gif' | 'social'>('banner');

const form = reactive<{ sets: SetItem[] }>({
    sets: [],
});

const fileInputRefs = ref<Map<string, HTMLInputElement>>(new Map());
const refKey = (setIndex: number, versionIndex: number) => `${setIndex}:${versionIndex}`;
const setFileInputRef = (el: HTMLInputElement | null, setIndex: number, versionIndex: number) => {
    const key = refKey(setIndex, versionIndex);
    if (el) fileInputRefs.value.set(key, el);
    else fileInputRefs.value.delete(key);
};

const goBack = () => window.history.back();

/* --------------------------- Set/Version ops --------------------------- */
const addSet = () => {
    form.sets.push({
        id: crypto.randomUUID(),
        name: '',
        isDragging: false,
        versions: [],
    });
};

const removeSet = (setIndex: number) => {
    form.sets.splice(setIndex, 1);
};

const addVersion = (setIndex: number) => {
    form.sets[setIndex].versions.push({
        id: crypto.randomUUID(),
        name: '',
        isDragging: false,
        banners: [],
    });
};

const removeVersion = (setIndex: number, versionIndex: number) => {
    form.sets[setIndex].versions.splice(versionIndex, 1);
};

const clearVersion = (setIndex: number, versionIndex: number) => {
    form.sets[setIndex].versions[versionIndex].banners.splice(0);
};

/* ---------------------------- Upload handlers ---------------------------- */
const triggerInput = (setIndex: number, versionIndex: number) => {
    fileInputRefs.value.get(refKey(setIndex, versionIndex))?.click();
};

const toBannerItems = (files: File[]): BannerItem[] =>
    files
        .filter((f) => f.name.toLowerCase().endsWith('.zip'))
        .map((file) => ({
            id: crypto.randomUUID(),
            file,
            size_id: '',
            search: '',
            dropdownOpen: false,
            highlightIndex: 0,
        }));

const handleFileUpload = (e: Event, setIndex: number, versionIndex: number) => {
    const input = e.target as HTMLInputElement;
    if (!input.files) return;
    const version = form.sets[setIndex].versions[versionIndex];
    // Replace existing files with newly selected
    version.banners.splice(0, version.banners.length, ...toBannerItems(Array.from(input.files)));
    input.value = '';
};

const handleDrop = (e: DragEvent, setIndex: number, versionIndex: number) => {
    const files = Array.from(e.dataTransfer?.files ?? []);
    const version = form.sets[setIndex].versions[versionIndex];
    version.isDragging = false;
    if (!files.length) return;
    version.banners.splice(0, version.banners.length, ...toBannerItems(files));
};

const removeBanner = (setIndex: number, versionIndex: number, bannerIndex: number) => {
    form.sets[setIndex].versions[versionIndex].banners.splice(bannerIndex, 1);
};

/* --------------------------- Size dropdown (Banner) --------------------------- */
const filteredSizes = (query: string) => {
    const lower = query?.toLowerCase?.() ?? '';
    return bannerSizes.value.filter((s: any) => s.label.toLowerCase().includes(lower));
};

const selectSize = (
    setIndex: number,
    versionIndex: number,
    bannerIndex: number,
    size: { id: number; label: string },
) => {
    const banner = form.sets[setIndex].versions[versionIndex].banners[bannerIndex];
    banner.size_id = size.id;
    banner.search = size.label;
    banner.dropdownOpen = false;
    banner.highlightIndex = 0;
};

const getSizeLabel = (id: number | '') =>
    (id ? bannerSizes.value.find((s: any) => s.id === id)?.label : '') ?? '';

const openDropdown = (banner: BannerItem) => {
    banner.dropdownOpen = true;
    banner.highlightIndex = 0;
};
const handleBlur = (banner: BannerItem) => {
    setTimeout(() => (banner.dropdownOpen = false), 150);
};
const handleInputChange = (banner: BannerItem) => {
    if (!banner.search?.trim()) banner.size_id = '';
    banner.highlightIndex = 0;
};
const moveDown = (banner: BannerItem) => {
    const list = filteredSizes(banner.search || '');
    if ((banner.highlightIndex ?? 0) < list.length - 1) banner.highlightIndex = (banner.highlightIndex ?? 0) + 1;
};
const moveUp = (banner: BannerItem) => {
    if ((banner.highlightIndex ?? 0) > 0) banner.highlightIndex = (banner.highlightIndex ?? 0) - 1;
};
const selectHighlighted = (
    setIndex: number,
    versionIndex: number,
    bannerIndex: number,
    banner: BannerItem,
) => {
    const list = filteredSizes(banner.search || '');
    const selected = list[banner.highlightIndex ?? 0];
    if (selected) selectSize(setIndex, versionIndex, bannerIndex, selected);
};

/* ------------------------------ Validation ------------------------------ */
const allAssigned = computed(() => {
    if (activeType.value !== 'banner') return false; // for now we only save banners
    if (!categoryName.value.trim()) return false;
    if (form.sets.length === 0) return false;

    return form.sets.every((s) => {
        if (s.versions.length === 0) return false;
        return s.versions.every(
            (v) => v.banners.length > 0 && v.banners.every((b) => b.size_id !== ''),
        );
    });
});

/* ------------------------------- Submit ------------------------------- */
const buildFormData = () => {
    const fd = new FormData();
    fd.append('category_name', categoryName.value);
    fd.append('feedback_name', feedbackName.value);
    fd.append('description', description.value);
    fd.append('type', activeType.value); // 'banner'

    form.sets.forEach((s, si) => {
        if (s.name) fd.append(`sets[${si}][name]`, s.name);
        s.versions.forEach((v, vi) => {
            if (v.name) fd.append(`sets[${si}][versions][${vi}][name]`, v.name);
            v.banners.forEach((b, bi) => {
                fd.append(`sets[${si}][versions][${vi}][banners][${bi}][file]`, b.file);
                fd.append(`sets[${si}][versions][${vi}][banners][${bi}][size_id]`, String(b.size_id));
            });
        });
    });

    // If your endpoint needs preview id:
    if (preview.value?.id) fd.append('preview_id', String(preview.value.id));
    return fd;
};

const submit = async () => {
    if (!allAssigned.value || saving.value) return;
    saving.value = true;

    try {
        // Adjust this route name to your real store endpoint
        const url = route('previews.categories.store', preview.value.id); // e.g. POST /previews/{id}/categories
        const { data } = await axios.post(url, buildFormData(), {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        if (data?.ok && data?.redirect) {
            // router.visit(data.redirect); // or window.location.href = data.redirect;
            window.location.href = "/previews/show/" + preview.value.slug;
            return;
        }

        // Fallback UX
        Swal.fire('Saved', 'Category created successfully.', 'success');
    } catch (err: any) {
        console.error(err);
        Swal.fire('Error', err?.response?.data?.message ?? 'Failed to save category', 'error');
    } finally {
        saving.value = false;
    }
};

</script>

<template>

    <Head title="Create New Category" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-4xl mx-auto space-y-6 w-full">
            <!-- 1) Category Name -->
            <div>
                <label class="block text-sm font-medium mb-1">Category Name</label>
                <input v-model="categoryName" type="text" placeholder="Enter Category Name"
                    class="w-full rounded border px-3 py-2 dark:bg-gray-800 dark:text-white" />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Feedback Name</label>
                <input v-model="feedbackName" type="text" placeholder="Enter Feedback Name"
                    class="w-full rounded border px-3 py-2 dark:bg-gray-800 dark:text-white" />
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Feedback Description</label>
                <textarea v-model="description" placeholder="Enter Description"
                    class="w-full rounded border px-3 py-2 dark:bg-gray-800 dark:text-white"></textarea>
            </div>
            <!-- 2) Type buttons -->
            <div class="grid grid-cols-2 gap-4 pt-2">
                <button class="px-4 py-3 rounded text-sm font-medium border"
                    :class="activeType === 'banner' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 dark:text-white'"
                    @click="activeType = 'banner'" type="button">
                    Banner
                </button>
                <button class="px-4 py-3 rounded text-sm font-medium border"
                    :class="activeType === 'video' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 dark:text-white'"
                    @click="activeType = 'video'" type="button">
                    Video
                </button>
                <button class="px-4 py-3 rounded text-sm font-medium border"
                    :class="activeType === 'gif' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 dark:text-white'"
                    @click="activeType = 'gif'" type="button">
                    Gif
                </button>
                <button class="px-4 py-3 rounded text-sm font-medium border"
                    :class="activeType === 'social' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 dark:text-white'"
                    @click="activeType = 'social'" type="button">
                    Social
                </button>
            </div>

            <!-- 3) Upload areas (only Banner functional now) -->
            <div v-show="activeType === 'banner'" class="space-y-6">
                <!-- Sets -->
                <div v-if="form.sets.length" class="space-y-6">
                    <div v-for="(set, setIndex) in form.sets" :key="set.id"
                        class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <!-- Set header -->
                        <div class="mb-2 flex items-center justify-between">
                            <input v-model="set.name" type="text" :placeholder="`Set ${setIndex + 1} (optional)`"
                                class="w-full max-w-sm border-b border-gray-300 bg-transparent px-2 py-1 text-base font-medium text-gray-800 focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:text-white" />
                            <div class="flex items-center gap-2">
                                <button @click="addVersion(setIndex)"
                                    class="rounded bg-blue-600 px-3 py-1 text-xs text-white hover:bg-blue-700"
                                    type="button">
                                    + Add Version
                                </button>
                                <button @click="removeSet(setIndex)" class="text-red-500 hover:text-red-700"
                                    title="Remove Set" type="button">
                                    <X class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <!-- Versions -->
                        <div v-if="set.versions.length" class="space-y-4">
                            <div v-for="(version, vIndex) in set.versions" :key="version.id"
                                class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <div class="mb-1 flex items-center justify-between">
                                    <input v-model="version.name" type="text"
                                        :placeholder="`Version ${vIndex + 1} (optional)`"
                                        class="w-full max-w-xs border-b border-gray-300 bg-transparent px-2 py-1 text-sm text-gray-800 focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:text-white" />
                                    <button class="text-xs text-red-500 hover:text-red-700"
                                        @click="removeVersion(setIndex, vIndex)" type="button">
                                        Remove Version
                                    </button>
                                </div>

                                <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ version.banners.length }} file(s)
                                </div>

                                <!-- Dropzone -->
                                <div class="mb-3 block w-full cursor-pointer rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 dark:border-gray-600 dark:hover:bg-gray-800"
                                    tabindex="0" aria-label="Upload ZIP banners"
                                    @click="() => triggerInput(setIndex, vIndex)"
                                    @keydown.enter.prevent="() => triggerInput(setIndex, vIndex)"
                                    @dragover.prevent="version.isDragging = true"
                                    @dragleave.prevent="version.isDragging = false"
                                    @drop.prevent="(e) => handleDrop(e, setIndex, vIndex)"
                                    :class="{ 'border-green-500 bg-green-50 dark:bg-green-900': version.isDragging }">
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                        Click or drag <b>.zip</b> files here for
                                        {{ set.name || `Set ${setIndex + 1}` }}
                                        <span v-if="version.name">(Version: {{ version.name }})</span>
                                    </span>
                                    <input :ref="(el) => setFileInputRef(el, setIndex, vIndex)" type="file"
                                        class="hidden" multiple accept=".zip"
                                        @change="(e) => handleFileUpload(e, setIndex, vIndex)" />
                                </div>

                                <div class="mb-3 flex items-center justify-between">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Drag to sort. Click ❌ to remove. New upload replaces previous files.
                                    </p>
                                    <button v-if="version.banners.length" @click="() => clearVersion(setIndex, vIndex)"
                                        class="text-xs text-red-500 hover:underline" type="button">
                                        Clear Version
                                    </button>
                                </div>

                                <!-- Banners list -->
                                <div v-if="version.banners.length">
                                    <draggable v-model="version.banners" item-key="id" handle=".handle"
                                        class="space-y-3" ghost-class="ghost">
                                        <template #item="{ element: banner, index }">
                                            <div
                                                class="flex items-center gap-4 rounded bg-gray-50 p-3 shadow-sm dark:bg-gray-800">
                                                <!-- Sort handle -->
                                                <div
                                                    class="flex w-10 items-center gap-2 text-gray-500 dark:text-gray-300">
                                                    <span class="w-4 text-right font-mono text-sm">{{ index + 1
                                                        }}.</span>
                                                    <span class="handle cursor-move" aria-label="Drag to reorder"
                                                        tabindex="0">☰</span>
                                                </div>

                                                <!-- File name -->
                                                <span class="w-full truncate text-sm text-gray-800 dark:text-white">
                                                    📁 {{ banner.file.name }}
                                                </span>

                                                <!-- Size dropdown (searchable) -->
                                                <div class="relative w-1/2">
                                                    <input v-model="banner.search" type="text"
                                                        placeholder="Search size..." @focus="() => openDropdown(banner)"
                                                        @blur="() => handleBlur(banner)"
                                                        @keydown.down.prevent="moveDown(banner)"
                                                        @keydown.up.prevent="moveUp(banner)"
                                                        @keydown.enter.prevent="selectHighlighted(setIndex, vIndex, index, banner)"
                                                        @input="() => handleInputChange(banner)"
                                                        class="mb-1 w-full rounded border px-3 py-1 text-sm focus:ring-2 focus:ring-green-500 dark:bg-gray-800 dark:text-white"
                                                        aria-label="Search and select banner size" />
                                                    <ul v-if="banner.dropdownOpen && filteredSizes(banner.search || '').length"
                                                        class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded border bg-white shadow-lg dark:bg-gray-800">
                                                        <li v-for="(size, sIndex) in filteredSizes(banner.search || '')"
                                                            :key="size.id"
                                                            @mousedown.prevent="selectSize(setIndex, vIndex, index, size)"
                                                            :class="[
                                                                'cursor-pointer px-3 py-1 text-sm dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700',
                                                                (banner.highlightIndex ?? 0) === sIndex ? 'bg-green-100 dark:bg-green-700 font-semibold' : '',
                                                            ]">
                                                            {{ size.label }}
                                                        </li>
                                                    </ul>
                                                    <div v-if="banner.size_id"
                                                        class="text-xs text-gray-500 dark:text-gray-300">
                                                        Selected: {{ getSizeLabel(banner.size_id) }}
                                                    </div>
                                                </div>

                                                <!-- Remove -->
                                                <button class="ml-2 text-red-500 hover:text-red-700" title="Remove"
                                                    @click="removeBanner(setIndex, vIndex, index)" type="button">
                                                    <X class="h-5 w-5" />
                                                </button>
                                            </div>
                                        </template>
                                    </draggable>
                                </div>
                                <div v-else class="py-4 text-center text-sm text-gray-400">
                                    No banners uploaded for this version yet.
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-2 text-center text-sm text-gray-400">
                            No versions yet. Click "Add Version".
                        </div>
                    </div>

                    <!-- Add Set -->
                    <div class="pt-2 text-center">
                        <button @click="addSet"
                            class="mx-auto flex w-full items-center justify-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-white hover:bg-purple-700"
                            type="button">
                            <span class="text-lg">+</span>
                            Add Another Set
                        </button>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="py-4 text-center text-gray-400">
                    <p class="mb-3">No banner sets created yet.</p>
                    <button @click="addSet" class="rounded-lg bg-purple-600 px-6 py-3 text-white hover:bg-purple-700"
                        type="button">
                        Create Your First Set
                    </button>
                </div>
            </div>

            <!-- Footer actions -->
            <div class="pt-4 flex justify-between">
                <button type="button"
                    class="inline-flex min-w-[120px] items-center justify-center rounded bg-gray-300 px-4 py-2 text-gray-800 hover:bg-gray-400"
                    @click="goBack">
                    ← Back
                </button>

                <button type="button"
                    class="inline-flex min-w-[120px] items-center justify-center rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700 disabled:opacity-50"
                    :disabled="!allAssigned || saving" @click="submit">
                    <span v-if="!saving">Save →</span>
                    <span v-else class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a 8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
                        </svg>
                        Uploading...
                    </span>
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.ghost {
    opacity: 0.5;
}
</style>