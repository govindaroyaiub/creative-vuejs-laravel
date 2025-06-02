<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';
import { Plus, X } from 'lucide-vue-next';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps<{
    subVersionId: number,
    subVersionName: string,
    videoSizes: Array<{ id: number, name: string, width: number, height: number }>,
    preview: { id: number, name: string }
}>();

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: props.preview?.name ?? '', href: props.preview ? `/previews/show/${props.preview.id}` : '#' },
    { title: 'Edit Video Sub Version', href: '#' },
];

const saving = ref(false);

const form = ref({
    name: props.subVersionName,
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

// Template refs for file inputs
const videoInputRefs = ref<HTMLInputElement[]>([]);
const bannerInputRefs = ref<HTMLInputElement[]>([]);

function triggerInput(index: number, type: 'video' | 'banner') {
    nextTick(() => {
        if (type === 'video') {
            videoInputRefs.value[index]?.click();
        } else {
            bannerInputRefs.value[index]?.click();
        }
    });
}

function addVideo() {
    form.value.videos.push({
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
    form.value.videos.splice(index, 1);
}

function onFileChange(event: Event, index: number, field: string) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        if (field === 'path') {
            form.value.videos[index].path = file;
            form.value.videos[index].pathName = file.name;
        } else if (field === 'companion_banner_path') {
            if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
                alert('Only JPG, PNG, or GIF images are allowed.');
                return;
            }
            form.value.videos[index].companion_banner_path = file;
            form.value.videos[index].companionBannerName = file.name;
        }
    }
}

const dragActive = ref<{ [key: string]: boolean }>({});

function setDragActive(index: number, type: 'video' | 'banner', active: boolean) {
    dragActive.value[`${index}-${type}`] = active;
}

function handleDropFile(e: DragEvent, index: number, field: 'path' | 'companion_banner_path') {
    setDragActive(index, field === 'path' ? 'video' : 'banner', false);
    const files = e.dataTransfer?.files;
    if (!files || files.length === 0) return;
    const file = files[0];
    if (field === 'path') {
        form.value.videos[index].path = file;
        form.value.videos[index].pathName = file.name;
    } else if (field === 'companion_banner_path') {
        if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
            alert('Only JPG, PNG, or GIF images are allowed.');
            return;
        }
        form.value.videos[index].companion_banner_path = file;
        form.value.videos[index].companionBannerName = file.name;
    }
}

function handleSubmit() {
    if (!form.value.name) {
        Swal.fire('Error', 'Please enter a sub version name.', 'error');
        return;
    }
    for (const [i, video] of form.value.videos.entries()) {
        if (!video.name) {
            Swal.fire('Error', `Please enter a title for video #${i + 1}.`, 'error');
            return;
        }
        if (!video.size_id) {
            Swal.fire('Error', `Please select a video size for video #${i + 1}.`, 'error');
            return;
        }
        if (!video.codec) {
            Swal.fire('Error', `Please enter a codec for video #${i + 1}.`, 'error');
            return;
        }
        if (!video.fps) {
            Swal.fire('Error', `Please enter FPS for video #${i + 1}.`, 'error');
            return;
        }
    }

    saving.value = true; // Start loading

    const payload = new FormData();
    payload.append('name', form.value.name);

    if (form.value.videos.length > 0) {
        form.value.videos.forEach((video, i) => {
            payload.append(`videos[${i}][name]`, video.name);
            payload.append(`videos[${i}][size_id]`, String(video.size_id));
            payload.append(`videos[${i}][codec]`, video.codec);
            payload.append(`videos[${i}][fps]`, video.fps);
            if (video.path) {
                payload.append(`videos[${i}][path]`, video.path);
            }
            if (video.companion_banner_path) {
                payload.append(`videos[${i}][companion_banner_path]`, video.companion_banner_path);
            }
        });
    }

    axios.post(`/previews/version/video/edit/subVersion/${props.subVersionId}`, payload)
        .then(() => {
            Swal.fire('Success', 'Video SubVersion updated successfully.', 'success');
            saving.value = false;
        })
        .catch(() => {
            Swal.fire('Error', 'Something went wrong.', 'error');
            saving.value = false;
        });
}
</script>

<template>

    <Head title="Edit Video Sub Version" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto p-6 w-full">
            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Sub Version Name</label>
                <input v-model="form.name" type="text" class="input" placeholder="e.g Version 2/3/4" />
            </div>
            <div v-for="(video, i) in form.videos" :key="video.id"
                class="bg-gray-50 p-4 rounded shadow mb-4 flex gap-2">
                <div class="flex-1">
                    <div class="grid grid-cols-2 gap-4 mb-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Title</label>
                            <input v-model="video.name" type="text" class="input" placeholder="Name" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Video Size</label>
                            <select v-model="video.size_id" class="input">
                                <option value="">Select Size</option>
                                <option v-for="size in props.videoSizes" :key="size.id" :value="size.id">
                                    {{ size.name }} ({{ size.width }} x {{ size.height }})
                                </option>
                            </select>
                        </div>
                    </div>
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
                    <div class="grid grid-cols-2 gap-4 mb-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Video File</label>
                            <div class="drop-area" :class="{ 'drop-active': dragActive[`${i}-video`] }"
                                @click="() => triggerInput(i, 'video')"
                                @dragover.prevent="setDragActive(i, 'video', true)"
                                @dragleave.prevent="setDragActive(i, 'video', false)"
                                @drop.prevent="e => handleDropFile(e, i, 'path')">
                                <span v-if="!dragActive[`${i}-video`]" class="text-gray-600 text-sm">Upload Video File
                                    Here</span>
                                <span v-else class="text-green-600 text-sm font-semibold">Drop video file!</span>
                                <input ref="videoInputRefs" type="file" accept="video/*" class="hidden"
                                    @change="e => onFileChange(e, i, 'path')" />
                                <div v-if="video.pathName" class="text-xs text-gray-500 mt-1">{{ video.pathName }}</div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Companion Banner (optional)</label>
                            <div class="drop-area" :class="{ 'drop-active': dragActive[`${i}-banner`] }"
                                @click="() => triggerInput(i, 'banner')"
                                @dragover.prevent="setDragActive(i, 'banner', true)"
                                @dragleave.prevent="setDragActive(i, 'banner', false)"
                                @drop.prevent="e => handleDropFile(e, i, 'companion_banner_path')">
                                <span v-if="!dragActive[`${i}-banner`]" class="text-gray-600 text-sm">Upload JPG/PNG/GIF
                                    Image Here</span>
                                <span v-else class="text-green-600 text-sm font-semibold">Drop image file!</span>
                                <input ref="bannerInputRefs" type="file" accept=".jpg,.jpeg,.png,.gif" class="hidden"
                                    @change="e => onFileChange(e, i, 'companion_banner_path')" />
                                <div v-if="video.companionBannerName" class="text-xs text-gray-500 mt-1">{{
                                    video.companionBannerName }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <button @click="removeVideo(i)" class="text-red-500 hover:underline text-sm mt-2 flex items-center">
                    <X class="w-4 h-4 mr-1" /> Remove
                </button>
            </div>
            <button @click="addVideo"
                class="mb-4 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 w-full flex items-center justify-center">
                <Plus class="mr-2 h-5 w-5" /> Add Video
            </button>
            <div class="flex space-x-4">
                <button class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded" :disabled="saving"
                    @click="handleSubmit">
                    <span v-if="!saving">Save</span>
                    <span v-else class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Saving...
                    </span>
                </button>
                <a :href="`/previews/show/${props.preview.id}`"
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
    border-radius: 4px;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}

.drop-area.drop-active {
    border-color: #22c55e;
    background: #e0ffe7;
}
</style>