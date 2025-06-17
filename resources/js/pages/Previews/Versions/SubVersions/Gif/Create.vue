<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const version = computed(() => page.props.version);
const preview = computed(() => version.value.preview);

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.id}` },
    { title: 'Create Gif Sub Version', href: '#' },
];

// Replace with your actual sizes or fetch from props/api
const bannerSizes = ref(page.props.bannerSizes || [
    // { id: 1, label: '300x250' }
]);

const form = useForm({
    sub_version_name: '',
    gifs: []
});

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
            form.gifs.push({
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
    URL.revokeObjectURL(form.gifs[i].url);
    form.gifs.splice(i, 1);
}

function filteredSizes(search: string) {
    const lower = search?.toLowerCase?.() ?? '';
    return bannerSizes.value.filter(s => s.label.toLowerCase().includes(lower));
}

function getSizeLabel(id: number | string) {
    const size = bannerSizes.value.find(s => s.id === id);
    return size ? size.label : '';
}

function selectSize(gif: any, size: { id: number; label: string }) {
    gif.sizes = [size.id];
    gif.search = '';
    gif.dropdownOpen = false;
    gif.highlightIndex = 0;
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
    const list = filteredSizes(gif.search);
    if (gif.highlightIndex < list.length - 1) gif.highlightIndex++;
}

function moveUp(gif: any) {
    if (gif.highlightIndex > 0) gif.highlightIndex--;
}

function selectHighlightedGif(index: number, gif: any) {
    const list = filteredSizes(gif.search);
    const selected = list[gif.highlightIndex];
    if (selected) selectSize(gif, selected);
}

const allAssigned = computed(() =>
    form.gifs.length > 0 && form.gifs.every(gif => gif.sizes.length === 1)
);

const handleSubmit = () => {
    loading.value = true;
    const payload = new FormData();
    payload.append('sub_version_name', form.sub_version_name);

    form.gifs.forEach((gif, i) => {
        payload.append(`gifs[${i}][file]`, gif.file);
        gif.sizes.forEach((sizeId, j) => {
            payload.append(`gifs[${i}][sizes][${j}]`, sizeId);
        });
        payload.append(`gifs[${i}][position]`, i); // <-- Add position
    });

    axios.post(`/previews/version/${version.value.id}/gif/add/subVersion`, payload)
        .then((response) => {
            loading.value = false;
            if (response.data?.redirect_to) {
                window.location.href = response.data.redirect_to;
            } else {
                Swal.fire('Success', 'GIF Sub Version created', 'success');
            }
        })
        .catch((error) => {
            console.error(error);
            loading.value = false;
            Swal.fire('Error', 'Something went wrong', 'error');
        });
};
</script>

<template>

    <Head title="Create Gif Sub Version" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-4xl mx-auto space-y-6 w-full">
            <div>
                <label class="block font-medium mb-1">Sub Version Name</label>
                <input v-model="form.sub_version_name" type="text"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
                    placeholder="Enter sub version name" />
            </div>

            <div class="space-y-6">
                <h2 class="text-lg font-semibold">Upload GIFs</h2>
                <div class="block w-full cursor-pointer border-2 border-dashed border-gray-300 p-6 text-center rounded-lg hover:bg-gray-50"
                    @click="triggerInput" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop" :class="{ 'border-green-500 bg-green-50': isDragging }">
                    <span class="text-sm text-gray-600">Click or drag GIF files here to upload</span>
                    <input ref="fileInput" type="file" class="hidden" multiple accept=".gif" @change="handleInput" />
                </div>

                <draggable v-model="form.gifs" item-key="file.name" handle=".handle" class="space-y-3"
                    ghost-class="ghost">
                    <template #item="{ element: gif, index }">
                        <div class="flex items-center gap-4 bg-gray-50 p-3 rounded shadow-sm">
                            <div class="flex items-center gap-2 w-10 text-gray-500">
                                <span class="font-mono text-sm w-4 text-right">{{ index + 1 }}.</span>
                                <span class="handle cursor-move">☰</span>
                            </div>
                            <img :src="gif.url" alt="GIF" class="w-20 h-20 object-contain rounded-lg border" />
                            <div class="min-w-0 w-40 truncate text-xs text-gray-700 font-medium">{{ gif.file.name }}
                            </div>
                            <div class="relative w-1/2">
                                <input :value="gif.sizes.length ? getSizeLabel(gif.sizes[0]) : gif.search" type="text"
                                    placeholder="Search size..." @focus="() => openDropdown(gif)"
                                    @blur="() => handleBlur(gif)" @keydown.down.prevent="moveDown(gif)"
                                    @keydown.up.prevent="moveUp(gif)"
                                    @keydown.enter.prevent="selectHighlightedGif(index, gif)"
                                    @input="(e) => { if (!gif.sizes.length) gif.search = (e.target as HTMLInputElement)?.value }"
                                    class="w-full mb-1 rounded border px-3 py-1 text-sm" :readonly="!!gif.sizes.length"
                                    @click="() => { if (gif.sizes.length) { gif.sizes = []; gif.search = ''; openDropdown(gif); } }" />
                                <ul v-if="gif.dropdownOpen && filteredSizes(gif.search).length"
                                    class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded border bg-white shadow-lg">
                                    <li v-for="(size, sIndex) in filteredSizes(gif.search)" :key="size.id"
                                        @mousedown.prevent="selectSize(gif, size)" :class="[
                                            'px-3 py-1 text-sm cursor-pointer hover:bg-gray-100',
                                            sIndex === gif.highlightIndex ? 'bg-gray-200 font-semibold' : '',
                                        ]">
                                        {{ size.label }}
                                    </li>
                                </ul>
                            </div>
                            <button class="ml-2 text-red-500 hover:text-red-700" title="Remove"
                                @click="removeGif(index)">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </template>
                </draggable>
            </div>

            <!-- Submit Button -->
            <div class="flex space-x-4">
                <button
                    class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center justify-center min-w-[100px]"
                    :disabled="!allAssigned || loading" @click="handleSubmit" aria-label="Submit GIFs">
                    <span v-if="!loading">Save</span>
                    <span v-else class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Uploading...
                    </span>
                </button>
                <a :href="`/previews/show/${preview.id}`"
                    class="w-full text-center rounded-lg bg-red-600 px-6 py-3 text-white shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Back
                </a>
            </div>
        </div>
    </AppLayout>
</template>