<template>
    <div class="space-y-6">
        <h2 class="text-lg font-semibold">Step 3: Upload Banners</h2>

        <!-- Drag & Drop Upload Area -->
        <div class="block w-full cursor-pointer border-2 border-dashed border-gray-300 p-6 text-center rounded-lg dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800"
            @click="triggerInput" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop" :class="{ 'border-green-500 bg-green-50 dark:bg-green-900': isDragging }">
            <span class="text-sm text-gray-600 dark:text-gray-300">Click or drag ZIP files here to upload</span>
            <input ref="fileInput" type="file" class="hidden" multiple accept=".zip" @change="handleFileUpload" />
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            Drag to sort. Click ❌ to remove. New upload replaces previous files.
        </p>

        <!-- Banners List -->
        <draggable v-model="form.banners" item-key="file.name" handle=".handle" class="space-y-3" ghost-class="ghost">
            <template #item="{ element: banner, index }">
                <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
                    <!-- Sort Handle -->
                    <div class="flex items-center gap-2 w-10 text-gray-500 dark:text-gray-300">
                        <span class="font-mono text-sm w-4 text-right">{{ index + 1 }}.</span>
                        <span class="handle cursor-move">☰</span>
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
                            @keydown.enter.prevent="selectHighlighted(index, banner)"
                            @input="() => handleInputChange(banner)"
                            class="w-full mb-1 rounded border px-3 py-1 text-sm dark:bg-gray-800 dark:text-white" />

                        <ul v-if="banner.dropdownOpen && filteredSizes(banner.search).length"
                            class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded border bg-white dark:bg-gray-800 shadow-lg">
                            <li v-for="(size, sIndex) in filteredSizes(banner.search)" :key="size.id"
                                @mousedown.prevent="selectSize(index, size)" :class="[
                                    'px-3 py-1 text-sm cursor-pointer dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700',
                                    sIndex === banner.highlightIndex ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : '',
                                ]">
                                {{ size.name }}
                            </li>
                        </ul>

                        <div v-if="banner.size_id" class="text-xs text-gray-500 dark:text-gray-300">
                            Selected: {{ getSizeName(banner.size_id) }}
                        </div>
                    </div>

                    <!-- Remove -->
                    <button class="ml-2 text-red-500 hover:text-red-700" title="Remove" @click="removeBanner(index)">
                        <X class="h-5 w-5" />
                    </button>
                </div>
            </template>
        </draggable>

        <!-- Navigation -->
        <div class="flex justify-between pt-6">
            <button class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded" @click="$emit('previous')">
                ← Previous
            </button>
            <button
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center justify-center min-w-[100px]"
                :disabled="!allAssigned || loading" @click="handleSubmit">
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
            file: File;
            size_id: string | number;
            search?: string;
            dropdownOpen?: boolean;
            highlightIndex?: number;
        }[];
    };
    bannerSizes: { id: number; name: string }[];
}>();

const loading = ref(false);
const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const triggerInput = () => fileInput.value?.click();

const handleSubmit = () => {
    loading.value = true;
    setTimeout(() => {
        emit('submit');
        loading.value = false;
    }, 600);
};

const handleFileUpload = (e: Event) => {
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

    props.form.banners.splice(0, props.form.banners.length, ...newBanners);
    input.value = '';
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
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

    props.form.banners.splice(0, props.form.banners.length, ...newBanners);
};

const removeBanner = (index: number) => {
    props.form.banners.splice(index, 1);
};

const filteredSizes = (query: string) => {
    const lower = query?.toLowerCase?.() ?? '';
    return props.bannerSizes.filter(s => s.name.toLowerCase().includes(lower));
};

const selectSize = (index: number, size: { id: number; name: string }) => {
    const banner = props.form.banners[index];
    banner.size_id = size.id;
    banner.search = size.name;
    banner.dropdownOpen = false;
    banner.highlightIndex = 0;
};

const getSizeName = (id: number | string) =>
    props.bannerSizes.find(s => s.id === id)?.name ?? '';

const allAssigned = computed(() =>
    props.form.banners.length > 0 && props.form.banners.every(b => b.size_id !== '')
);

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

const selectHighlighted = (index: number, banner: any) => {
    const list = filteredSizes(banner.search);
    const selected = list[banner.highlightIndex];
    if (selected) {
        selectSize(index, selected);
    }
};
</script>

<style scoped>
.ghost {
    opacity: 0.5;
}
</style>
