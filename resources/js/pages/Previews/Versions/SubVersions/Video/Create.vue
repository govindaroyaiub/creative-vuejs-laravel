<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Plus, X } from 'lucide-vue-next';
import draggable from 'vuedraggable';
import axios from 'axios'; // Use axios for API requests

const page = usePage();
const version = computed(() => page.props.version);
const preview = computed(() => version.value.preview);
const videoSizes = computed(() => page.props.videoSizes);

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.id}` },
    { title: 'Create Video Sub Version', href: '#' },
];

const form = useForm({
    name: '',
    videos: [] as {
        id: number,
        name: string,
        size_id: string | number,
        codec: string,
        fps: string,
        path: File | null,
        pathName?: string,
        companion_banner_path: File | null,
        companionBannerName?: string,
    }[],
});

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

function removeVideo(index: number) {
    form.videos.splice(index, 1);
}

function triggerInput(index: number, type: 'video' | 'banner') {
    const refName = type === 'video' ? `videoInput-${index}` : `bannerInput-${index}`;
    document.getElementById(refName)?.click();
}

function onFileChange(event: Event, index: number, field: string) {
    const input = event.target as HTMLInputElement;
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

function handleSubmit() {
    if (
        !form.name ||
        form.videos.length === 0 ||
        form.videos.some(v => !v.name || !v.size_id || !v.path)
    ) {
        alert('Please fill all required fields.');
        return;
    }

    const formData = new FormData();
    formData.append('name', form.name);

    form.videos.forEach((video, i) => {
        formData.append(`videos[${i}][name]`, video.name);
        formData.append(`videos[${i}][size_id]`, video.size_id);
        formData.append(`videos[${i}][codec]`, video.codec);
        formData.append(`videos[${i}][fps]`, video.fps);
        formData.append(`videos[${i}][path]`, video.path);
        if (video.companion_banner_path) {
            formData.append(`videos[${i}][companion_banner_path]`, video.companion_banner_path);
        }
    });

    axios.post(route('store-video-subVersion', version.value.id), formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    })
        .then((response) => {
            if (response.data?.redirect_to) {
                window.location.href = response.data.redirect_to;
            }
        })
        .catch(() => {
            Swal.fire('Error', 'Something went wrong.', 'error');
        });
}
</script>

<template>

    <Head title="Create Video Sub Version" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl mx-auto p-6 w-full">
            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Sub Version Name</label>
                <input v-model="form.name" type="text" class="input" placeholder="e.g Version 2/3/4" />
            </div>
            <draggable v-model="form.videos" item-key="id" handle=".handle" class="space-y-3" ghost-class="ghost">
                <template #item="{ element: video, index: i }">
                    <div class="bg-gray-50 p-4 rounded shadow mb-4 flex gap-2">
                        <span class="handle cursor-move select-none text-gray-400 mr-2">☰</span>
                        <div class="flex-1">
                            <!-- First row: Name, Video Size -->
                            <div class="grid grid-cols-2 gap-4 mb-2">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Title</label>
                                    <input v-model="video.name" type="text" class="input" placeholder="Name" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Video Size</label>
                                    <select v-model="video.size_id" class="input">
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
                                    <input v-model="video.codec" type="text" class="input" placeholder="e.g. h264" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">FPS</label>
                                    <input v-model="video.fps" type="text" class="input" placeholder="e.g. 30 FPS" />
                                </div>
                            </div>
                            <!-- File uploads -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Video File</label>
                                    <div class="drop-area" @click="() => triggerInput(i, 'video')">
                                        <span class="text-gray-600 text-sm">Upload Video File Here</span>
                                        <input :id="`videoInput-${i}`" type="file" accept="video/*" class="hidden"
                                            @change="e => onFileChange(e, i, 'path')" />
                                        <div v-if="video.pathName" class="text-xs text-gray-500 mt-1">{{ video.pathName
                                            }}</div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Companion Banner (optional)</label>
                                    <div class="drop-area" @click="() => triggerInput(i, 'banner')">
                                        <span class="text-gray-600 text-sm">Upload JPG/PNG/GIF Image Here</span>
                                        <input :id="`bannerInput-${i}`" type="file" accept=".jpg,.jpeg,.png,.gif"
                                            class="hidden" @change="e => onFileChange(e, i, 'companion_banner_path')" />
                                        <div v-if="video.companionBannerName" class="text-xs text-gray-500 mt-1">{{
                                            video.companionBannerName }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button @click="removeVideo(i)"
                            class="text-red-500 hover:underline text-sm mt-2 flex items-center">
                            <X class="w-4 h-4 mr-1" /> Remove
                        </button>
                    </div>
                </template>
            </draggable>
            <button @click="addVideo"
                class="mb-4 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 w-full flex items-center justify-center">
                <Plus class="mr-2 h-5 w-5" /> Add Video
            </button>
            <!-- Submit Button -->
            <div class="flex space-x-4">
                <button class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded"
                    :disabled="form.processing" @click="handleSubmit">
                    Save
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
.input {
    border: 1px solid #d1d5db;
    border-radius: 4px;
    padding: 0.5rem;
    width: 100%;
}

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