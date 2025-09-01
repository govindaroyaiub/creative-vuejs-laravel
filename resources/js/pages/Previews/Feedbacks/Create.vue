<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import draggable from 'vuedraggable';
import { X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import axios from 'axios';

// ----- Props from controller -----
const props = defineProps<{
    bannerSizes: { id: number; width: number; height: number; name: string }[];
    // Optional: if you pass feedbackId from controller, great. Otherwise, set it below manually.
    feedbackId?: number;
}>();

// If you didn't pass feedbackId as a prop, set it here (e.g., read from URL or hardcode during dev).
const feedbackId = computed(() => props.feedbackId ?? (usePage().props?.feedbackId as number | undefined));

// ----- Local state -----
type BannerItem = {
    file: File;
    size_id: number | '';// required before submit
    search?: string;
    dropdownOpen?: boolean;
    highlightIndex?: number;
};
type VersionItem = {
    id: number;
    name?: string;
    isDragging: boolean;
    banners: BannerItem[];
};
type SetItem = {
    id: number;
    name: string;
    isDragging: boolean;
    versions: VersionItem[];
};

const form = reactive<{ sets: SetItem[] }>({
    sets: [],
});

const goBack = () => window.history.back();

const loading = ref(false);
const fileInputRefs = ref<Map<string, HTMLInputElement>>(new Map());
let setIdCounter = 0;
let versionIdCounter = 0;

const refKey = (setIndex: number, versionIndex: number) => `${setIndex}:${versionIndex}`;
const setFileInputRef = (el: HTMLInputElement | null, setIndex: number, versionIndex: number) => {
    const key = refKey(setIndex, versionIndex);
    if (el) fileInputRefs.value.set(key, el);
    else fileInputRefs.value.delete(key);
};

// ----- Add/Remove sets & versions -----
const addSet = () => {
    form.sets.push({
        id: ++setIdCounter,
        name: '',
        isDragging: false,
        versions: [],
    });
};

const removeSet = (setIndex: number) => {
    form.sets.splice(setIndex, 1);
};

const addVersion = (setIndex: number) => {
    const set = form.sets[setIndex];
    set.versions.push({
        id: ++versionIdCounter,
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

// ----- Upload handling -----
const triggerInput = (setIndex: number, versionIndex: number) => {
    fileInputRefs.value.get(refKey(setIndex, versionIndex))?.click();
};

const toBannerItems = (files: File[]): BannerItem[] =>
    files
        .filter((f) => f.name.toLowerCase().endsWith('.zip'))
        .map((file) => ({
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

// ----- Size dropdown -----
const filteredSizes = (query: string) => {
    const lower = query?.toLowerCase?.() ?? '';
    return props.bannerSizes.filter((s) => s.name.toLowerCase().includes(lower));
};

const selectSize = (
    setIndex: number,
    versionIndex: number,
    bannerIndex: number,
    size: { id: number; name: string },
) => {
    const banner = form.sets[setIndex].versions[versionIndex].banners[bannerIndex];
    banner.size_id = size.id;
    banner.search = size.name;
    banner.dropdownOpen = false;
    banner.highlightIndex = 0;
};

const getSizeName = (id: number | '') =>
    (id ? props.bannerSizes.find((s) => s.id === id)?.name : '') ?? '';

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

// ----- Validation gate -----
const allAssigned = computed(() => {
    if (form.sets.length === 0) return false;
    return form.sets.every((s) => {
        if (s.versions.length === 0) return false;
        return s.versions.every(
            (v) => v.banners.length > 0 && v.banners.every((b) => b.size_id !== ''),
        );
    });
});

// ----- Submit (FormData) -----
// Keys shape (example):
// sets[0][name]
// sets[0][versions][0][name]
// sets[0][versions][0][banners][0][file]         <-- File
// sets[0][versions][0][banners][0][size_id]
const buildFormData = () => {
    const fd = new FormData();
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
    // include feedback id if needed by your controller route signature
    if (feedbackId.value) fd.append('feedback_id', String(feedbackId.value));
    return fd;
};

const submit = async () => {
    if (!allAssigned.value) return;
    loading.value = true;

    try {
        const { data } = await axios.post(
            route('previews.feedback.sets.store', feedbackId.value),
            buildFormData(),
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        if (data.ok && data.redirect) {
            // let inertia do the visit
            window.location.href = data.redirect;
            // OR force browser reload:
            // window.location.href = data.redirect;
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Upload failed', 'error');
    } finally {
        loading.value = false;
    }
};
</script>

<template>

    <Head title="Add Banner Feedback Sets" />
    <AppLayout :breadcrumbs="[{ title: 'Add Feedback for Banner', href: '/previews' }]">
        <div class="mx-auto w-full max-w-5xl p-4 md:p-6">
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
                                class="rounded bg-blue-600 px-3 py-1 text-xs text-white hover:bg-blue-700">
                                + Add Version
                            </button>
                            <button @click="removeSet(setIndex)" class="text-red-500 hover:text-red-700"
                                title="Remove Set">
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
                                    @click="removeVersion(setIndex, vIndex)">
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
                                <input :ref="(el) => setFileInputRef(el, setIndex, vIndex)" type="file" class="hidden"
                                    multiple accept=".zip" @change="(e) => handleFileUpload(e, setIndex, vIndex)" />
                            </div>

                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Drag to sort. Click ❌ to remove. New upload replaces previous files.
                                </p>
                                <button v-if="version.banners.length" @click="() => clearVersion(setIndex, vIndex)"
                                    class="text-xs text-red-500 hover:underline">
                                    Clear Version
                                </button>
                            </div>

                            <!-- Banners list -->
                            <div v-if="version.banners.length">
                                <draggable v-model="version.banners" item-key="file.name" handle=".handle"
                                    class="space-y-3" ghost-class="ghost">
                                    <template #item="{ element: banner, index }">
                                        <div
                                            class="flex items-center gap-4 rounded bg-gray-50 p-3 shadow-sm dark:bg-gray-800">
                                            <!-- Sort handle -->
                                            <div class="flex w-10 items-center gap-2 text-gray-500 dark:text-gray-300">
                                                <span class="w-4 text-right font-mono text-sm">{{ index + 1 }}.</span>
                                                <span class="handle cursor-move" aria-label="Drag to reorder"
                                                    tabindex="0">☰</span>
                                            </div>

                                            <!-- File name -->
                                            <span class="w-full truncate text-sm text-gray-800 dark:text-white">
                                                📁 {{ banner.file.name }}
                                            </span>

                                            <!-- Size dropdown (searchable) -->
                                            <div class="relative w-1/2">
                                                <input v-model="banner.search" type="text" placeholder="Search size..."
                                                    @focus="() => openDropdown(banner)" @blur="() => handleBlur(banner)"
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
                                                        {{ size.name }}
                                                    </li>
                                                </ul>
                                                <div v-if="banner.size_id"
                                                    class="text-xs text-gray-500 dark:text-gray-300">
                                                    Selected: {{ getSizeName(banner.size_id) }}
                                                </div>
                                            </div>

                                            <!-- Remove -->
                                            <button class="ml-2 text-red-500 hover:text-red-700" title="Remove"
                                                @click="removeBanner(setIndex, vIndex, index)">
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
                <div class="pt-4 text-center">
                    <button @click="addSet"
                        class="mx-auto flex w-full items-center justify-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-white hover:bg-purple-700">
                        <span class="text-lg">+</span>
                        Add Another Set
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="py-12 text-center text-gray-400">
                <p class="mb-4">No banner sets created yet.</p>
                <button @click="addSet" class="rounded-lg bg-purple-600 px-6 py-3 text-white hover:bg-purple-700">
                    Create Your First Set
                </button>
            </div>

            <!-- Footer actions -->
            <div class="pt-6 flex justify-between">
                <!-- Back button -->
                <button type="button"
                    class="inline-flex min-w-[120px] items-center justify-center rounded bg-gray-300 px-4 py-2 text-gray-800 hover:bg-gray-400"
                    @click="goBack">
                    ← Back
                </button>

                <!-- Submit button -->
                <button type="button"
                    class="inline-flex min-w-[120px] items-center justify-center rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700 disabled:opacity-50"
                    :disabled="!allAssigned || loading" @click="submit">
                    <span v-if="!loading">Submit →</span>
                    <span v-else class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
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