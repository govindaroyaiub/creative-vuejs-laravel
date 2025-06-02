<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import draggable from 'vuedraggable';
import { X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import axios from 'axios';

const saving = ref(false);

const page = usePage();
const preview = computed(() => page.props.preview);
const bannerSizes = computed(() => page.props.bannerSizes.map((s: any) => ({
    id: s.id,
    label: `${s.width}x${s.height}`,
})));

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.id}` },
    { title: 'Create Version', href: '#' },
];

const form = useForm({
    name: '',
    description: '',
    type: '',
    sub_version_name: '',
    banners: [],
    socials: [], // <-- Add socials array
    videos: []
});

const fileInput = ref<HTMLInputElement | null>(null);
const socialFileInput = ref<HTMLInputElement | null>(null); // <-- For social images
const isDragging = ref(false);
const isSocialDragging = ref(false); // <-- For social images

const triggerInput = () => fileInput.value?.click();
const triggerSocialInput = () => socialFileInput.value?.click(); // <-- For social images

const handleFileUpload = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const files = input.files;
    if (!files) return;

    const zips = Array.from(files).filter(f => f.name.endsWith('.zip'));
    form.banners = zips.map(file => ({
        file,
        size_id: '',
        search: '',
        dropdownOpen: false,
        highlightIndex: 0,
    }));

    input.value = '';
};

const videoSizes = computed(() => page.props.videoSizes || []);

function addVideo() {
    form.videos.push({
        id: Date.now() + Math.random(),
        name: '',
        size_id: '',
        codec: '',
        fps: '',
        path: null,
        pathName: '',
        companion_banner_path: null,
        companionBannerName: '',
    });
}
function removeVideo(index) {
    form.videos.splice(index, 1);
}
function triggerVideoInput(index) {
    document.getElementById(`videoInput-${index}`)?.click();
}
function triggerBannerInput(index) {
    document.getElementById(`bannerInput-${index}`)?.click();
}
function onVideoFileChange(event, index, field) {
    const input = event.target;
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        if (field === 'path') {
            form.videos[index].path = file;
            form.videos[index].pathName = file.name;
        } else if (field === 'companion_banner_path') {
            if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
                alert('Only JPG, PNG, or GIF images are allowed.');
                return;
            }
            form.videos[index].companion_banner_path = file;
            form.videos[index].companionBannerName = file.name;
        }
    }
}

// Track drag state for each video row/field
const videoDragActive = ref<{ [key: string]: boolean }>({});

function setVideoDragActive(index: number, type: 'video' | 'banner', active: boolean) {
    videoDragActive.value[index + '-' + type] = active;
}

function handleVideoDrop(event: DragEvent, index: number, field: string) {
    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
        const file = files[0];
        if (field === 'path') {
            form.videos[index].path = file;
            form.videos[index].pathName = file.name;
        } else if (field === 'companion_banner_path') {
            if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
                alert('Only JPG, PNG, or GIF images are allowed.');
                setVideoDragActive(index, field === 'path' ? 'video' : 'banner', false);
                return;
            }
            form.videos[index].companion_banner_path = file;
            form.videos[index].companionBannerName = file.name;
        }
    }
    setVideoDragActive(index, field === 'path' ? 'video' : 'banner', false);
}

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    const files = e.dataTransfer?.files;
    if (!files) return;

    const zips = Array.from(files).filter(f => f.name.endsWith('.zip'));
    form.banners = zips.map(file => ({
        file,
        size_id: '',
        search: '',
        dropdownOpen: false,
        highlightIndex: 0,
    }));
};

const removeBanner = (i: number) => form.banners.splice(i, 1);

const filteredSizes = (query: string) => {
    const q = query.toLowerCase();
    return bannerSizes.value.filter(s => s.label.toLowerCase().includes(q));
};

const selectSize = (i: number, size: any) => {
    const b = form.banners[i];
    b.size_id = size.id;
    b.search = size.label;
    b.dropdownOpen = false;
    b.highlightIndex = 0;
};

const getSizeLabel = (id: number | string) => {
    const size = bannerSizes.value.find(s => s.id === id);
    return size ? size.label : '';
};

const handleBlur = (banner: any) => setTimeout(() => (banner.dropdownOpen = false), 150);
const handleInputChange = (banner: any) => {
    if (!banner.search?.trim()) banner.size_id = '';
    banner.highlightIndex = 0;
};
const openDropdown = (banner: any) => { banner.dropdownOpen = true; banner.highlightIndex = 0; };
const moveDown = (banner: any) => {
    const list = filteredSizes(banner.search);
    if (banner.highlightIndex < list.length - 1) banner.highlightIndex++;
};
const moveUp = (banner: any) => {
    if (banner.highlightIndex > 0) banner.highlightIndex--;
};
const selectHighlighted = (i: number, banner: any) => {
    const list = filteredSizes(banner.search);
    const selected = list[banner.highlightIndex];
    if (selected) selectSize(i, selected);
};

const allAssigned = computed(() =>
    form.banners.length > 0 && form.banners.every(b => b.size_id !== '')
);

// --- SOCIAL LOGIC START ---
const handleSocialFileUpload = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const files = input.files;
    if (!files) return;

    const images = Array.from(files).filter(f => /\.(jpe?g|png)$/i.test(f.name));
    form.socials = images.map(file => ({
        file,
        name: '',
        preview: URL.createObjectURL(file),
    }));

    input.value = '';
};

const handleSocialDrop = (e: DragEvent) => {
    isSocialDragging.value = false;
    const files = e.dataTransfer?.files;
    if (!files) return;

    const images = Array.from(files).filter(f => /\.(jpe?g|png)$/i.test(f.name));
    form.socials = images.map(file => ({
        file,
        name: '',
        preview: URL.createObjectURL(file),
    }));
};

const removeSocial = (i: number) => {
    URL.revokeObjectURL(form.socials[i].preview);
    form.socials.splice(i, 1);
};

const allSocialNamed = computed(() =>
    form.socials.length > 0 && form.socials.every(s => s.name.trim() !== '')
);
// --- SOCIAL LOGIC END ---

const handleSubmit = () => {
    saving.value = true;
    const payload = new FormData();
    payload.append('name', form.name);
    payload.append('description', form.description);
    payload.append('type', form.type);
    payload.append('sub_version_name', form.sub_version_name);

    if (form.type === 'banner') {
        form.banners.forEach((b, i) => {
            payload.append(`banners[${i}][file]`, b.file);
            payload.append(`banners[${i}][size_id]`, b.size_id);
            payload.append(`banners[${i}][position]`, String(i));
        });
    }

    // --- SOCIAL SUBMIT LOGIC ---
    if (form.type === 'social') {
        form.socials.forEach((s, i) => {
            payload.append(`socials[${i}][file]`, s.file);
            payload.append(`socials[${i}][name]`, s.name);
            payload.append(`socials[${i}][position]`, String(i));
        });
    }

    if (form.type === 'video') {
        form.videos.forEach((v, i) => {
            payload.append(`videos[${i}][name]`, v.name);
            payload.append(`videos[${i}][size_id]`, v.size_id);
            payload.append(`videos[${i}][codec]`, v.codec);
            payload.append(`videos[${i}][fps]`, v.fps);
            payload.append(`videos[${i}][path]`, v.path);
            if (v.companion_banner_path) {
                payload.append(`videos[${i}][companion_banner_path]`, v.companion_banner_path);
            }
            payload.append(`videos[${i}][position]`, String(i));
        });
    }

    axios.post(`/previews/version/add/${preview.value.id}`, payload)
        .then((response) => {
            saving.value = false;
            if (response.data?.redirect_to) {
                window.location.href = response.data.redirect_to;
            } else {
                Swal.fire('Success', 'Version created', 'success');
            }
        })
        .catch(() => {
            saving.value = false;
            Swal.fire('Error', 'Something went wrong', 'error');
        });
};
</script>

<template>

    <Head title="Create Version" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-4xl mx-auto space-y-6 w-full">
            <div>
                <label class="block font-medium mb-1">Version Name</label>
                <input v-model="form.name" type="text"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
                    placeholder="e.g. New Feedback" />
            </div>

            <div>
                <label class="block font-medium mb-1">Version Description</label>
                <textarea v-model="form.description"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
                    placeholder="e.g. Version Description"></textarea>
            </div>

            <div>
                <label class="block font-medium mb-1">Sub Version Name</label>
                <input v-model="form.sub_version_name" type="text"
                    class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white"
                    placeholder="e.g. Version 3/4/5" />
            </div>

            <div>
                <label class="block font-medium mb-2">Select Project Type</label>
                <div class="grid grid-cols-2 gap-4">
                    <button v-for="option in ['banner', 'video', 'social', 'gif']" :key="option"
                        @click="form.type = option" type="button" :class="[
                            'py-4 text-center rounded-lg border text-sm font-medium transition',
                            form.type === option
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        ]">
                        {{ option.charAt(0).toUpperCase() + option.slice(1) }}
                    </button>
                </div>
            </div>

            <!-- Banner Upload -->
            <div v-if="form.type === 'banner'" class="space-y-6">
                <div class="border-2 border-dashed p-6 text-center rounded-lg cursor-pointer" @click="triggerInput"
                    @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop" :class="{ 'border-green-500 bg-green-50': isDragging }">
                    <span class="text-sm text-gray-600">Click or drag ZIP files here to upload</span>
                    <input ref="fileInput" type="file" class="hidden" multiple accept=".zip"
                        @change="handleFileUpload" />
                </div>

                <draggable v-model="form.banners" item-key="file.name" handle=".handle" class="space-y-3"
                    ghost-class="ghost">
                    <template #item="{ element: banner, index }">
                        <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
                            <div class="flex items-center gap-2 w-10 text-gray-500 dark:text-gray-300">
                                <span class="font-mono text-sm w-4 text-right">{{ index + 1 }}.</span>
                                <span class="handle cursor-move">☰</span>
                            </div>

                            <span class="truncate w-full text-sm text-gray-800 dark:text-white">
                                📁 {{ banner.file.name }}
                            </span>

                            <!-- Size Search Dropdown -->
                            <div class="relative w-1/2">
                                <input v-model="banner.search" type="text" placeholder="Search size..."
                                    autocomplete="off" @focus="() => openDropdown(banner)"
                                    @blur="() => handleBlur(banner)" @keydown.down.prevent="moveDown(banner)"
                                    @keydown.up.prevent="moveUp(banner)"
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
                                        {{ size.label }}
                                    </li>
                                </ul>

                                <div v-if="banner.size_id" class="text-xs text-gray-500 dark:text-gray-300">
                                    Selected: {{ getSizeLabel(banner.size_id) }}
                                </div>
                            </div>

                            <button class="ml-2 text-red-500 hover:text-red-700" title="Remove"
                                @click="removeBanner(index)">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </template>
                </draggable>
            </div>

            <!-- SOCIAL UPLOAD -->
            <div v-if="form.type === 'social'" class="space-y-6">
                <div class="border-2 border-dashed p-6 text-center rounded-lg cursor-pointer"
                    @click="triggerSocialInput" @dragover.prevent="isSocialDragging = true"
                    @dragleave.prevent="isSocialDragging = false" @drop.prevent="handleSocialDrop"
                    :class="{ 'border-green-500 bg-green-50': isSocialDragging }">
                    <span class="text-sm text-gray-600">Click or drag PNG/JPG images here to upload</span>
                    <input ref="socialFileInput" type="file" class="hidden" multiple accept=".png,.jpg,.jpeg"
                        @change="handleSocialFileUpload" />
                </div>

                <draggable v-model="form.socials" item-key="file.name" handle=".handle" class="space-y-3"
                    ghost-class="ghost">
                    <template #item="{ element: social, index }">
                        <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
                            <div class="flex items-center gap-2 w-10 text-gray-500 dark:text-gray-300">
                                <span class="font-mono text-sm w-4 text-right">{{ index + 1 }}.</span>
                                <span class="handle cursor-move">☰</span>
                            </div>
                            <img :src="social.preview" alt="preview" class="w-12 h-12 object-contain rounded border" />
                            <input v-model="social.name" type="text" placeholder="Name"
                                class="w-1/2 rounded border px-3 py-1 text-sm dark:bg-gray-800 dark:text-white" />
                            <span class="truncate w-full text-xs text-gray-800 dark:text-white">
                                {{ social.file.name }}
                            </span>
                            <button class="ml-2 text-red-500 hover:text-red-700" title="Remove"
                                @click="removeSocial(index)">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </template>
                </draggable>
            </div>

            <!-- Video Section with Drag-and-Drop for File Inputs -->
            <div v-if="form.type === 'video'" class="space-y-6">
                <draggable v-model="form.videos" item-key="id" handle=".handle" class="space-y-3" ghost-class="ghost">
                    <template #item="{ element: video, index }">
                        <div class="bg-gray-50 p-4 rounded shadow mb-4 flex gap-2">
                            <span class="handle cursor-move select-none text-gray-400 mr-2">☰</span>
                            <div class="flex-1">
                                <!-- First row: Name, Video Size -->
                                <div class="grid grid-cols-2 gap-4 mb-2">
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Title</label>
                                        <input v-model="video.name" type="text" class="input w-full py-2"
                                            placeholder="Name" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Video Size</label>
                                        <select v-model="video.size_id" class="input w-full py-2">
                                            <option value="">Select Size</option>
                                            <option v-for="size in videoSizes" :key="size.id" :value="size.id">
                                                {{ size.name }} ({{ size.width }} x {{ size.height }})
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <!-- Second row: Codec & FPS -->
                                <div class="grid grid-cols-2 gap-4 mb-2">
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Codec</label>
                                        <input v-model="video.codec" type="text" class="input w-full py-2"
                                            placeholder="e.g. h264" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium mb-1">FPS</label>
                                        <input v-model="video.fps" type="text" class="input w-full py-2"
                                            placeholder="e.g. 30 FPS" />
                                    </div>
                                </div>
                                <!-- File uploads with drag-and-drop -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Video File</label>
                                        <div class="drop-area" @click="() => triggerVideoInput(index)"
                                            @dragover.prevent="setVideoDragActive(index, 'video', true)"
                                            @dragleave.prevent="setVideoDragActive(index, 'video', false)"
                                            @drop.prevent="e => handleVideoDrop(e, index, 'path')"
                                            :class="{ 'drop-active': videoDragActive[index + '-video'] }">
                                            <span class="text-gray-600 text-sm">Upload Video File Here</span>
                                            <input :id="`videoInput-${index}`" type="file" accept="video/*"
                                                class="hidden" @change="e => onVideoFileChange(e, index, 'path')" />
                                            <div v-if="video.pathName" class="text-xs text-gray-500 mt-1">{{
                                                video.pathName }}</div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Companion Banner
                                            (optional)</label>
                                        <div class="drop-area" @click="() => triggerBannerInput(index)"
                                            @dragover.prevent="setVideoDragActive(index, 'banner', true)"
                                            @dragleave.prevent="setVideoDragActive(index, 'banner', false)"
                                            @drop.prevent="e => handleVideoDrop(e, index, 'companion_banner_path')"
                                            :class="{ 'drop-active': videoDragActive[index + '-banner'] }">
                                            <span class="text-gray-600 text-sm">Upload JPG/PNG/GIF Image Here</span>
                                            <input :id="`bannerInput-${index}`" type="file"
                                                accept=".jpg,.jpeg,.png,.gif" class="hidden"
                                                @change="e => onVideoFileChange(e, index, 'companion_banner_path')" />
                                            <div v-if="video.companionBannerName" class="text-xs text-gray-500 mt-1">{{
                                                video.companionBannerName }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button @click="removeVideo(index)"
                                class="text-red-500 hover:underline text-sm mt-2 flex items-center">
                                <X class="w-4 h-4 mr-1" /> Remove
                            </button>
                        </div>
                    </template>
                </draggable>
                <button @click="addVideo"
                    class="mb-4 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 w-full flex items-center justify-center">
                    <span class="mr-2">+</span> Add Video
                </button>
            </div>

            <!-- Submit Button -->
            <div class="flex space-x-4">
                <button type="button"
                    :disabled="saving || (form.type === 'banner' && (!allAssigned || form.processing)) || (form.type === 'social' && (!allSocialNamed || form.processing))"
                    @click="handleSubmit"
                    class="w-full rounded-lg bg-green-600 px-6 py-3 text-white shadow-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 dark:bg-green-500 dark:hover:bg-green-600">
                    <span v-if="!saving">Save</span>
                    <span v-else class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Saving...
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

<style scoped>
.drop-area {
    border: 2px dashed #d1d5db;
    border-radius: 6px;
    padding: 1.5rem 1rem;
    text-align: center;
    transition: border-color 0.2s, background 0.2s;
    background: #f9fafb;
    margin-bottom: 0.5rem;
    cursor: pointer;
    position: relative;
}

.drop-area.drop-active {
    border-color: #22c55e;
    background: #e0ffe7;
}

.ghost {
    opacity: 0.5;
}
</style>