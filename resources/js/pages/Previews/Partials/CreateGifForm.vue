<script setup lang="ts">
import { ref, computed } from 'vue';
import draggable from 'vuedraggable';

const emit = defineEmits(['previous', 'submit']);

const props = defineProps<{
    form: {
        gifs: {
            file: File;
            sizes: number[];
            url?: string;
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

function handleDrop(e: DragEvent) {
    isDragging.value = false;
    if (!e.dataTransfer?.files) return;
    handleFiles(e.dataTransfer.files);
}

function handleInput(e: Event) {
    const files = (e.target as HTMLInputElement).files;
    if (files) handleFiles(files);
}

function handleFiles(files: FileList) {
    Array.from(files).forEach(file => {
        if (file.type === 'image/gif') {
            props.form.gifs.push({
                file,
                url: URL.createObjectURL(file),
                sizes: [],
                search: '',
                dropdownOpen: false,
                highlightIndex: 0,
            });
        }
    });
}

function removeGif(i: number) {
    URL.revokeObjectURL(props.form.gifs[i].url);
    props.form.gifs.splice(i, 1);
}

function filteredSizes(gif: any) {
    const lower = gif.search?.toLowerCase?.() ?? '';
    return props.bannerSizes.filter(s => s.name.toLowerCase().includes(lower) && !gif.sizes.includes(s.id));
}

function selectSize(gif: any, size: { id: number; name: string }) {
    gif.sizes = [size.id]; // Only allow one size per GIF
    gif.search = '';
    gif.dropdownOpen = false;
    gif.highlightIndex = 0;
}

function removeSize(gif: any, sizeId: number) {
    gif.sizes = gif.sizes.filter((id: number) => id !== sizeId);
}

function handleBlur(gif: any) {
    setTimeout(() => {
        gif.dropdownOpen = false;
    }, 150);
}

function openDropdown(gif: any) {
    gif.dropdownOpen = true;
    gif.highlightIndex = 0;
}

function moveDown(gif: any) {
    const list = filteredSizes(gif);
    if (gif.highlightIndex < list.length - 1) gif.highlightIndex++;
}

function moveUp(gif: any) {
    if (gif.highlightIndex > 0) gif.highlightIndex--;
}

function selectHighlighted(gif: any) {
    const list = filteredSizes(gif);
    const selected = list[gif.highlightIndex];
    if (selected) {
        selectSize(gif, selected);
    }
}

function selectedSizeName(gif: any) {
    if (gif.sizes.length) {
        const size = props.bannerSizes.find(s => s.id === gif.sizes[0]);
        return size ? size.name : '';
    }
    return '';
}

const allAssigned = computed(() =>
    props.form.gifs.length > 0 && props.form.gifs.every(gif => gif.sizes.length === 1)
);

const handleSubmit = () => {
    loading.value = true;
    emit('submit', {
        onDone: () => {
            loading.value = false;
        }
    });
};
</script>

<template>
    <div class="space-y-6">
        <h2 class="text-lg font-semibold">Upload GIFs</h2>

        <!-- Drag & Drop Upload Area -->
        <div class="block w-full cursor-pointer border-2 border-dashed border-gray-300 p-6 text-center rounded-lg hover:bg-gray-50"
            @click="triggerInput" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop" :class="{ 'border-green-500 bg-green-50': isDragging }">
            <span class="text-sm text-gray-600">Click or drag GIF files here to upload</span>
            <input ref="fileInput" type="file" class="hidden" multiple accept=".gif" @change="handleInput" />
        </div>

        <p class="text-sm text-gray-500">
            Drag to sort. Click ❌ to remove. New upload replaces previous files.
        </p>

        <!-- GIFs List -->
        <draggable v-model="props.form.gifs" item-key="file.name" handle=".handle" class="space-y-3"
            ghost-class="ghost">
            <template #item="{ element: gif, index }">
                <div class="flex items-center gap-4 bg-gray-50 p-3 rounded shadow-sm">
                    <!-- Sort Handle -->
                    <div class="flex items-center gap-2 w-10 text-gray-500">
                        <span class="font-mono text-sm w-4 text-right">{{ index + 1 }}.</span>
                        <span class="handle cursor-move">☰</span>
                    </div>

                    <!-- GIF Preview -->
                    <img :src="gif.url" alt="GIF" class="w-20 h-20 object-contain rounded-lg border" />

                    <!-- File Name -->
                    <div class="min-w-0 w-40 truncate text-xs text-gray-700 font-medium">{{ gif.file.name }}</div>

                    <!-- Size Single-Select -->
                    <div class="relative w-1/2">
                        <input :value="selectedSizeName(gif) ? selectedSizeName(gif) : gif.search" type="text"
                            placeholder="Search size..." @focus="() => openDropdown(gif)" @blur="() => handleBlur(gif)"
                            @keydown.down.prevent="moveDown(gif)" @keydown.up.prevent="moveUp(gif)"
                            @keydown.enter.prevent="selectHighlighted(gif)"
                            @input="(e) => { if (!selectedSizeName(gif)) gif.search = (e.target as HTMLInputElement)?.value }"
                            class="w-full mb-1 rounded border px-3 py-1 text-sm" :readonly="!!selectedSizeName(gif)"
                            @click="() => { if (selectedSizeName(gif)) { gif.sizes = []; gif.search = ''; openDropdown(gif); } }" />

                        <ul v-if="gif.dropdownOpen && filteredSizes(gif).length"
                            class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded border bg-white shadow-lg">
                            <li v-for="(size, sIndex) in filteredSizes(gif)" :key="size.id"
                                @mousedown.prevent="selectSize(gif, size)" :class="[
                                    'px-3 py-1 text-sm cursor-pointer hover:bg-gray-100',
                                    sIndex === gif.highlightIndex ? 'bg-gray-200 font-semibold' : '',
                                ]">
                                {{ size.name }}
                            </li>
                        </ul>
                    </div>

                    <!-- Remove -->
                    <button class="ml-2 text-red-500 hover:text-red-700" title="Remove" @click="removeGif(index)">
                        ❌
                    </button>
                </div>
            </template>
        </draggable>
        <!-- Navigation -->
        <div class="flex justify-between pt-6">
            <button class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg" @click="$emit('previous')">
                ← Previous
            </button>
            <button
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center justify-center min-w-[100px]"
                :disabled="!allAssigned || loading" @click="handleSubmit" aria-label="Submit GIFs">
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

<style scoped>
.ghost {
    opacity: 0.5;
}
</style>