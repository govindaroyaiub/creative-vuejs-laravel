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
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-3">
                        <input v-model="set.name" type="text" :placeholder="`Set ${setIndex + 1} Name`"
                            class="text-md font-medium text-gray-800 dark:text-white bg-transparent border-b border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:outline-none px-2 py-1" />
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ set.banners.length }} file(s)
                        </span>
                        <button @click="removeSet(setIndex)" class="text-red-500 hover:text-red-700" title="Remove Set">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Upload Area for this set -->
                <div class="block w-full cursor-pointer border-2 border-dashed border-gray-300 p-6 text-center rounded-lg dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-green-500 mb-4"
                    tabindex="0" aria-label="Upload ZIP banners" @click="() => triggerInput(setIndex)"
                    @keydown.enter.prevent="() => triggerInput(setIndex)" @dragover.prevent="set.isDragging = true"
                    @dragleave.prevent="set.isDragging = false" @drop.prevent="(e) => handleDrop(e, setIndex)"
                    :class="{ 'border-green-500 bg-green-50 dark:bg-green-900': set.isDragging }">
                    <span class="text-sm text-gray-600 dark:text-gray-300">
                        Click or drag ZIP files here to upload for {{ set.name || `Set ${setIndex + 1}` }}
                    </span>
                    <input :ref="(el) => setFileInputRef(el, setIndex)" type="file" class="hidden" multiple
                        accept=".zip" @change="(e) => handleFileUpload(e, setIndex)" />
                </div>

                <div class="flex justify-between items-center mb-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Drag to sort. Click ❌ to remove. New upload replaces previous files.
                    </p>
                    <button v-if="set.banners.length" @click="() => clearSet(setIndex)"
                        class="text-xs text-red-500 hover:underline">
                        Clear Set
                    </button>
                </div>

                <!-- Banners List for this set -->
                <div v-if="set.banners.length">
                    <draggable v-model="set.banners" item-key="file.name" handle=".handle" class="space-y-3"
                        ghost-class="ghost">
                        <template #item="{ element: banner, index }">
                            <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
                                <!-- Sort Handle -->
                                <div class="flex items-center gap-2 w-10 text-gray-500 dark:text-gray-300">
                                    <span class="font-mono text-sm w-4 text-right">{{ index + 1 }}.</span>
                                    <span class="handle cursor-move" aria-label="Drag to reorder" tabindex="0">☰</span>
                                </div>

                                <!-- File Name -->
                                <span class="truncate w-full text-sm text-gray-800 dark:text-white">
                                    📁 {{ banner.file.name }}
                                </span>

                                <!-- Size Dropdown -->
                                <div class="relative w-1/2">
                                    <input v-model="banner.search" type="text" placeholder="Search size..."
                                        @focus="() => openDropdown(banner)" @blur="() => handleBlur(banner)"
                                        @keydown.down.prevent="moveDown(banner)" @keydown.up.prevent="moveUp(banner)"
                                        @keydown.enter.prevent="selectHighlighted(setIndex, index, banner)"
                                        @input="() => handleInputChange(banner)"
                                        class="w-full mb-1 rounded border px-3 py-1 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-green-500"
                                        aria-label="Search and select banner size" />

                                    <ul v-if="banner.dropdownOpen && filteredSizes(banner.search).length"
                                        class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded border bg-white dark:bg-gray-800 shadow-lg">
                                        <li v-for="(size, sIndex) in filteredSizes(banner.search)" :key="size.id"
                                            @mousedown.prevent="selectSize(setIndex, index, size)" :class="[
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

                                <!-- Remove -->
                                <button class="ml-2 text-red-500 hover:text-red-700" title="Remove"
                                    @click="removeBanner(setIndex, index)">
                                    <X class="h-5 w-5" />
                                </button>
                            </div>
                        </template>
                    </draggable>
                </div>
                <div v-else class="text-center text-gray-400 py-4 text-sm">
                    No banners uploaded for this set yet.
                </div>
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
const fileInputRefs = ref<Map<number, HTMLInputElement>>(new Map());
let setIdCounter = 0;

const setFileInputRef = (el: HTMLInputElement | null, setIndex: number) => {
    if (el) {
        fileInputRefs.value.set(setIndex, el);
    } else {
        fileInputRefs.value.delete(setIndex);
    }
};

const addSet = () => {
    props.form.banners.push({
        id: ++setIdCounter,
        name: '', // Initialize with empty name
        isDragging: false,
        banners: []
    });
};

const removeSet = (setIndex: number) => {
    props.form.banners.splice(setIndex, 1);
};

const clearSet = (setIndex: number) => {
    props.form.banners[setIndex].banners.splice(0);
};

const triggerInput = (setIndex: number) => {
    fileInputRefs.value.get(setIndex)?.click();
};

const handleSubmit = () => {
    loading.value = true;
    emit('submit', {
        onDone: () => {
            loading.value = false;
        }
    });
};

const handleFileUpload = (e: Event, setIndex: number) => {
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

    const currentSet = props.form.banners[setIndex];
    currentSet.banners.splice(0, currentSet.banners.length, ...newBanners);
    input.value = '';
};

const handleDrop = (e: DragEvent, setIndex: number) => {
    const currentSet = props.form.banners[setIndex];
    currentSet.isDragging = false;
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

    currentSet.banners.splice(0, currentSet.banners.length, ...newBanners);
};

const removeBanner = (setIndex: number, bannerIndex: number) => {
    props.form.banners[setIndex].banners.splice(bannerIndex, 1);
};

const filteredSizes = (query: string) => {
    const lower = query?.toLowerCase?.() ?? '';
    return props.bannerSizes.filter(s => s.name.toLowerCase().includes(lower));
};

const selectSize = (setIndex: number, bannerIndex: number, size: { id: number; name: string }) => {
    const banner = props.form.banners[setIndex].banners[bannerIndex];
    banner.size_id = size.id;
    banner.search = size.name;
    banner.dropdownOpen = false;
    banner.highlightIndex = 0;
};

const getSizeName = (id: number | string) =>
    props.bannerSizes.find(s => s.id === id)?.name ?? '';

const allAssigned = computed(() => {
    if (props.form.banners.length === 0) return false;

    return props.form.banners.every(set =>
        set.banners.length > 0 && set.banners.every(b => b.size_id !== '')
    );
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

const selectHighlighted = (setIndex: number, bannerIndex: number, banner: any) => {
    const list = filteredSizes(banner.search);
    const selected = list[banner.highlightIndex];
    if (selected) {
        selectSize(setIndex, bannerIndex, selected);
    }
};
</script>

<style scoped>
.ghost {
    opacity: 0.5;
}
</style>