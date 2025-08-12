<template>
    <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Step 3: Upload Banners</h2>
        </div>

        <!-- Banner Sets -->
        <div v-if="form.banners.length" class="space-y-6">
            <div v-for="(set, setIndex) in form.banners" :key="set.id"
                class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-white dark:bg-gray-900">
                <!-- Set Header -->
                <div class="flex justify-between items-center mb-2">
                    <div class="flex items-center gap-3">
                        <input v-model="set.name" type="text" :placeholder="`Set ${setIndex + 1} (optional)`"
                            class="text-md font-medium text-gray-800 dark:text-white bg-transparent border-b border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:outline-none px-2 py-1" />
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="addVersion(setIndex)" class="text-xs rounded px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white">
                            + Add Version
                        </button>
                        <button @click="removeSet(setIndex)" class="text-red-500 hover:text-red-700" title="Remove Set">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Version Actions (moved to header; button always visible) -->

                <!-- Versions List -->
                <div v-if="set.versions?.length" class="space-y-4">
                    <div v-for="(version, vIndex) in set.versions" :key="version.id" class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <!-- Version Header -->
                        <div class="flex items-center justify-between mb-1">
                            <input v-model="version.name" type="text" :placeholder="`Version ${vIndex + 1} (optional)`"
                                class="text-sm text-gray-800 dark:text-white bg-transparent border-b border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:outline-none px-2 py-1 w-full max-w-xs" />
                            <button class="text-red-500 hover:text-red-700 text-xs" @click="removeVersion(setIndex, vIndex)">Remove Version</button>
                        </div>
                        <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">{{ version.banners.length }} file(s)</div>

                        <!-- Upload Area for this version -->
                        <div class="block w-full cursor-pointer border-2 border-dashed border-gray-300 p-6 text-center rounded-lg dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500 mb-3"
                            tabindex="0" aria-label="Upload ZIP banners"
                            @click="() => triggerInput(setIndex, vIndex)"
                            @keydown.enter.prevent="() => triggerInput(setIndex, vIndex)"
                            @dragover.prevent="version.isDragging = true"
                            @dragleave.prevent="version.isDragging = false"
                            @drop.prevent="(e) => handleDrop(e, setIndex, vIndex)"
                            :class="{ 'border-green-500 bg-green-50 dark:bg-green-900': version.isDragging }">
                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                Click or drag ZIP files here to upload for {{ set.name || `Set ${setIndex + 1}` }}
                                <span v-if="version.name">(Version: {{ version.name }})</span>
                            </span>
                            <input :ref="(el) => setFileInputRef(el, setIndex, vIndex)" type="file" class="hidden" multiple accept=".zip"
                                @change="(e) => handleFileUpload(e, setIndex, vIndex)" />
                        </div>

                        <div class="flex justify-between items-center mb-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Drag to sort. Click ❌ to remove. New upload replaces previous files.</p>
                            <button v-if="version.banners.length" @click="() => clearVersion(setIndex, vIndex)" class="text-xs text-red-500 hover:underline">Clear Version</button>
                        </div>

                        <!-- Banners List for this version -->
                        <div v-if="version.banners.length">
                            <draggable v-model="version.banners" item-key="file.name" handle=".handle" class="space-y-3" ghost-class="ghost">
                                <template #item="{ element: banner, index }">
                                    <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
                                        <!-- Sort Handle -->
                                        <div class="flex items-center gap-2 w-10 text-gray-500 dark:text-gray-300">
                                            <span class="font-mono text-sm w-4 text-right">{{ index + 1 }}.</span>
                                            <span class="handle cursor-move" aria-label="Drag to reorder" tabindex="0">☰</span>
                                        </div>

                                        <!-- File Name -->
                                        <span class="truncate w-full text-sm text-gray-800 dark:text-white">📁 {{ banner.file.name }}</span>

                                        <!-- Size Dropdown -->
                                        <div class="relative w-1/2">
                                            <input v-model="banner.search" type="text" placeholder="Search size..."
                                                @focus="() => openDropdown(banner)" @blur="() => handleBlur(banner)"
                                                @keydown.down.prevent="moveDown(banner)" @keydown.up.prevent="moveUp(banner)"
                                                @keydown.enter.prevent="selectHighlighted(setIndex, vIndex, index, banner)"
                                                @input="() => handleInputChange(banner)"
                                                class="w-full mb-1 rounded border px-3 py-1 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-green-500"
                                                aria-label="Search and select banner size" />

                                            <ul v-if="banner.dropdownOpen && filteredSizes(banner.search).length"
                                                class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded border bg-white dark:bg-gray-800 shadow-lg">
                                                <li v-for="(size, sIndex) in filteredSizes(banner.search)" :key="size.id"
                                                    @mousedown.prevent="selectSize(setIndex, vIndex, index, size)"
                                                    :class="[
                                                        'px-3 py-1 text-sm cursor-pointer dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700',
                                                        sIndex === banner.highlightIndex ? 'bg-green-100 dark:bg-green-700 font-semibold' : '',
                                                    ]">
                                                    {{ size.name }}
                                                </li>
                                            </ul>

                                            <div v-if="banner.size_id" class="text-xs text-gray-500 dark:text-gray-300">
                                                Selected: {{ getSizeName(banner.size_id) }}
                                            </div>
                                        </div>

                                        <!-- Remove banner -->
                                        <button class="ml-2 text-red-500 hover:text-red-700" title="Remove"
                                            @click="removeBanner(setIndex, vIndex, index)">
                                            <X class="h-5 w-5" />
                                        </button>
                                    </div>
                                </template>
                            </draggable>
                        </div>
                        <div v-else class="text-center text-gray-400 py-4 text-sm">No banners uploaded for this version yet.</div>
                    </div>
                </div>
                <div v-else class="text-center text-gray-400 py-2 text-sm">No versions yet. Click "Add Version".</div>
            </div>

            <!-- Add Set Button (after all sets) -->
            <div class="text-center pt-4">
                <button @click="addSet"
                    class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg flex items-center justify-center text-center gap-2 mx-auto">
                    <span class="text-lg">+</span>
                    Add Another Set
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center text-gray-400 py-12">
            <p class="mb-4">No banner sets created yet.</p>
            <button @click="addSet" class="w-full px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg">
                Create Your First Set
            </button>
        </div>

        <!-- Navigation -->
        <div class="flex justify-between pt-6">
            <button class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg" @click="$emit('previous')">
                ← Previous
            </button>
            <button
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center justify-center min-w-[100px]"
                :disabled="!allAssigned || loading" @click="handleSubmit" aria-label="Submit banners">
                <span v-if="!loading">Submit →</span>
                <span v-else class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    Uploading...
                </span>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import draggable from 'vuedraggable';
import { X } from 'lucide-vue-next';

const emit = defineEmits(['submit', 'previous']);

const props = defineProps<{
    form: {
        banners: {
            id: number;
            name: string; // Added name property
            isDragging: boolean;
            versions?: {
                id: number;
                name?: string;
                isDragging: boolean;
                banners: {
                    file: File;
                    size_id: string | number;
                    search?: string;
                    dropdownOpen?: boolean;
                    highlightIndex?: number;
                }[];
            }[];
            banners: {
                file: File;
                size_id: string | number;
                search?: string;
                dropdownOpen?: boolean;
                highlightIndex?: number;
            }[];
        }[];
    };
    bannerSizes: { id: number; name: string }[];
}>();

const loading = ref(false);
const fileInputRefs = ref<Map<string, HTMLInputElement>>(new Map());
let setIdCounter = 0;
let versionIdCounter = 0;

const refKey = (setIndex: number, versionIndex?: number) => `${setIndex}:${versionIndex ?? -1}`;

const setFileInputRef = (el: HTMLInputElement | null, setIndex: number, versionIndex?: number) => {
    const key = refKey(setIndex, versionIndex);
    if (el) fileInputRefs.value.set(key, el);
    else fileInputRefs.value.delete(key);
};

const addSet = () => {
    props.form.banners.push({
        id: ++setIdCounter,
        name: '', // Initialize with empty name
        isDragging: false,
        versions: [],
        banners: []
    });
};

const removeSet = (setIndex: number) => {
    props.form.banners.splice(setIndex, 1);
};

const clearSet = (setIndex: number) => {
    props.form.banners[setIndex].banners.splice(0);
};

const clearVersion = (setIndex: number, versionIndex: number) => {
    const version = props.form.banners[setIndex].versions?.[versionIndex];
    if (!version) return;
    version.banners.splice(0);
};

const triggerInput = (setIndex: number, versionIndex: number) => {
    fileInputRefs.value.get(refKey(setIndex, versionIndex))?.click();
};

const addVersion = (setIndex: number) => {
    const set = props.form.banners[setIndex];
    if (!set.versions) set.versions = [];
    set.versions.push({
        id: ++versionIdCounter,
        name: '',
        isDragging: false,
        banners: [],
    });
};

const removeVersion = (setIndex: number, versionIndex: number) => {
    props.form.banners[setIndex].versions?.splice(versionIndex, 1);
};

const handleSubmit = () => {
    loading.value = true;
    emit('submit', {
        onDone: () => {
            loading.value = false;
        }
    });
};

const handleFileUpload = (e: Event, setIndex: number, versionIndex: number) => {
    const input = e.target as HTMLInputElement;
    const files = input.files;
    if (!files) return;

    const newBanners = Array.from(files)
        .filter(file => file.name.toLowerCase().endsWith('.zip'))
        .map(file => ({
            file,
            size_id: '',
            search: '',
            dropdownOpen: false,
            highlightIndex: 0,
        }));

    const version = props.form.banners[setIndex].versions?.[versionIndex];
    if (!version) return;
    version.banners.splice(0, version.banners.length, ...newBanners);
    input.value = '';
};

const handleDrop = (e: DragEvent, setIndex: number, versionIndex: number) => {
    const version = props.form.banners[setIndex].versions?.[versionIndex];
    if (!version) return;
    version.isDragging = false;
    if (!e.dataTransfer?.files) return;

    const files = Array.from(e.dataTransfer.files);
    const newBanners = files
        .filter(file => file.name.toLowerCase().endsWith('.zip'))
        .map(file => ({
            file,
            size_id: '',
            search: '',
            dropdownOpen: false,
            highlightIndex: 0,
        }));

    version.banners.splice(0, version.banners.length, ...newBanners);
};

const removeBanner = (setIndex: number, versionIndex: number, bannerIndex: number) => {
    props.form.banners[setIndex].versions?.[versionIndex].banners.splice(bannerIndex, 1);
};

const filteredSizes = (query: string) => {
    const lower = query?.toLowerCase?.() ?? '';
    return props.bannerSizes.filter(s => s.name.toLowerCase().includes(lower));
};

const selectSize = (setIndex: number, versionIndex: number, bannerIndex: number, size: { id: number; name: string }) => {
    const banner = props.form.banners[setIndex].versions?.[versionIndex].banners[bannerIndex];
    banner.size_id = size.id;
    banner.search = size.name;
    banner.dropdownOpen = false;
    banner.highlightIndex = 0;
};

const getSizeName = (id: number | string) =>
    props.bannerSizes.find(s => s.id === id)?.name ?? '';

const allAssigned = computed(() => {
    if (props.form.banners.length === 0) return false;

    return props.form.banners.every(set => {
        const versions = set.versions ?? [];
        if (versions.length === 0) return false;
        return versions.every(v => v.banners.length > 0 && v.banners.every(b => b.size_id !== ''));
    });
});

const handleBlur = (banner: any) => {
    setTimeout(() => {
        banner.dropdownOpen = false;
    }, 150);
};

const handleInputChange = (banner: any) => {
    if (!banner.search?.trim()) {
        banner.size_id = '';
    }
    banner.highlightIndex = 0;
};

const openDropdown = (banner: any) => {
    banner.dropdownOpen = true;
    banner.highlightIndex = 0;
};

const moveDown = (banner: any) => {
    const list = filteredSizes(banner.search);
    if (banner.highlightIndex < list.length - 1) banner.highlightIndex++;
};

const moveUp = (banner: any) => {
    if (banner.highlightIndex > 0) banner.highlightIndex--;
};

const selectHighlighted = (setIndex: number, versionIndex: number, bannerIndex: number, banner: any) => {
    const list = filteredSizes(banner.search);
    const selected = list[banner.highlightIndex];
    if (selected) {
        selectSize(setIndex, versionIndex, bannerIndex, selected);
    }
};

const totalFiles = (set: any) => (set.versions ?? []).reduce((sum: number, v: any) => sum + (v.banners?.length ?? 0), 0);
</script>

<style scoped>
.ghost {
    opacity: 0.5;
}
</style>