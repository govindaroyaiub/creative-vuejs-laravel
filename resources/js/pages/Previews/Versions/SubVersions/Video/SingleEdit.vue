<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps<{
    subVideo: {
        id: number,
        name: string,
        size_id: number | string,
        codec: string,
        fps: string,
        path: string | null,
        companion_banner_path: string | null,
    },
    videoSizes: Array<{ id: number, name: string, width: number, height: number }>,
    preview: { id: number, name: string }
}>();

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: props.preview?.name ?? '', href: props.preview ? `/previews/show/${props.preview.id}` : '#' },
    { title: 'Edit Video', href: '#' },
];

const saving = ref(false);

const form = ref({
    size_id: props.subVideo.size_id,
    codec: props.subVideo.codec,
    fps: props.subVideo.fps,
    videoFile: null as File | null,
    videoFileName: '',
    companionBanner: null as File | null,
    companionBannerName: '',
    removeCompanionBanner: false,
});

const videoInputRef = ref<HTMLInputElement | null>(null);
const bannerInputRef = ref<HTMLInputElement | null>(null);

const dragActive = ref<{ [key: string]: boolean }>({});

function setDragActive(type: 'video' | 'banner', active: boolean) {
    dragActive.value[type] = active;
}

function triggerInput(type: 'video' | 'banner') {
    nextTick(() => {
        if (type === 'video') {
            videoInputRef.value?.click();
        } else {
            bannerInputRef.value?.click();
        }
    });
}

function onFileChange(event: Event, field: 'videoFile' | 'companionBanner') {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        if (field === 'videoFile') {
            form.value.videoFile = file;
            form.value.videoFileName = file.name;
        } else if (field === 'companionBanner') {
            if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
                alert('Only JPG, PNG, or GIF images are allowed.');
                return;
            }
            form.value.companionBanner = file;
            form.value.companionBannerName = file.name;
            form.value.removeCompanionBanner = false; // If uploading new, don't remove
        }
    }
}

function handleDropFile(e: DragEvent, field: 'videoFile' | 'companionBanner') {
    setDragActive(field === 'videoFile' ? 'video' : 'banner', false);
    const files = e.dataTransfer?.files;
    if (!files || files.length === 0) return;
    const file = files[0];
    if (field === 'videoFile') {
        form.value.videoFile = file;
        form.value.videoFileName = file.name;
    } else if (field === 'companionBanner') {
        if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
            alert('Only JPG, PNG, or GIF images are allowed.');
            return;
        }
        form.value.companionBanner = file;
        form.value.companionBannerName = file.name;
        form.value.removeCompanionBanner = false;
    }
}

function removeCompanionBanner() {
    form.value.removeCompanionBanner = true;
    form.value.companionBanner = null;
    form.value.companionBannerName = '';
}

function handleSubmit() {
    if (!form.value.size_id) {
        Swal.fire('Error', 'Please select a video size.', 'error');
        return;
    }
    if (!form.value.codec) {
        Swal.fire('Error', 'Please enter a codec.', 'error');
        return;
    }
    if (!form.value.fps) {
        Swal.fire('Error', 'Please enter FPS.', 'error');
        return;
    }

    saving.value = true;

    const payload = new FormData();
    payload.append('size_id', form.value.size_id as any);
    payload.append('codec', form.value.codec);
    payload.append('fps', form.value.fps);

    if (form.value.videoFile) {
        payload.append('videoFile', form.value.videoFile);
    }
    if (form.value.companionBanner) {
        payload.append('companionBanner', form.value.companionBanner);
    }
    if (form.value.removeCompanionBanner) {
        payload.append('remove_companion_banner', '1');
    }

    axios.post(`/previews/video/single/edit/${props.subVideo.id}`, payload)
        .then(response => {
            saving.value = false;
            Swal.fire('Success', 'Video updated successfully.', 'success');
            // Optionally redirect after a delay:
            // setTimeout(() => {
            //     window.location.href = response.data?.redirect_to;
            // }, 1200);
        })
        .catch(() => {
            saving.value = false;
            Swal.fire('Error', 'Something went wrong.', 'error');
        });
}
</script>

<template>
    <Head title="Edit Video" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-2xl mx-auto p-6 w-full">
            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Video Size</label>
                <select v-model="form.size_id" class="input">
                    <option value="">Select Size</option>
                    <option v-for="size in props.videoSizes" :key="size.id" :value="size.id">
                        {{ size.name }} ({{ size.width }} x {{ size.height }})
                    </option>
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Codec</label>
                <input v-model="form.codec" type="text" class="input" placeholder="Codec" />
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">FPS</label>
                <input v-model="form.fps" type="text" class="input" placeholder="FPS" />
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Video File</label>
                <div class="drop-area" :class="{ 'drop-active': dragActive['video'] }"
                    @click="() => triggerInput('video')"
                    @dragover.prevent="setDragActive('video', true)"
                    @dragleave.prevent="setDragActive('video', false)"
                    @drop.prevent="e => handleDropFile(e, 'videoFile')">
                    <span v-if="!dragActive['video']" class="text-gray-600 text-sm">Upload Video File Here</span>
                    <span v-else class="text-green-600 text-sm font-semibold">Drop video file!</span>
                    <input
                        ref="videoInputRef"
                        type="file"
                        accept="video/*"
                        class="hidden"
                        @change="e => onFileChange(e, 'videoFile')"
                    />
                    <div v-if="form.videoFileName" class="text-xs text-gray-500 mt-1">{{ form.videoFileName }}</div>
                    <div v-else-if="props.subVideo.path" class="text-xs text-gray-500 mt-1">
                        Current: <a :href="`/${props.subVideo.path}`" target="_blank" class="underline">{{ props.subVideo.path.split('/').pop() }}</a>
                    </div>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Companion Banner (optional)</label>
                <div class="drop-area" :class="{ 'drop-active': dragActive['banner'] }"
                    @click="() => triggerInput('banner')"
                    @dragover.prevent="setDragActive('banner', true)"
                    @dragleave.prevent="setDragActive('banner', false)"
                    @drop.prevent="e => handleDropFile(e, 'companionBanner')">
                    <span v-if="!dragActive['banner']" class="text-gray-600 text-sm">Upload JPG/PNG/GIF Image Here</span>
                    <span v-else class="text-green-600 text-sm font-semibold">Drop image file!</span>
                    <input
                        ref="bannerInputRef"
                        type="file"
                        accept=".jpg,.jpeg,.png,.gif"
                        class="hidden"
                        @change="e => onFileChange(e, 'companionBanner')"
                    />
                    <div v-if="form.companionBannerName" class="text-xs text-gray-500 mt-1">{{ form.companionBannerName }}</div>
                    <div v-else-if="props.subVideo.companion_banner_path && !form.removeCompanionBanner" class="text-xs text-gray-500 mt-1">
                        Current: 
                        <a :href="`/${props.subVideo.companion_banner_path}`" target="_blank" class="underline">
                            {{ props.subVideo.companion_banner_path.split('/').pop() }}
                        </a>
                        <button
                            type="button"
                            class="ml-2 text-red-500 underline text-xs"
                            @click.stop="removeCompanionBanner"
                        >
                            Remove
                        </button>
                    </div>
                    <div v-if="form.removeCompanionBanner" class="text-xs text-red-500 mt-1">
                        Companion banner will be removed.
                    </div>
                </div>
            </div>
            <div class="flex space-x-4">
                <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg"
                    :disabled="saving"
                    @click="handleSubmit">
                    <span v-if="!saving">Update</span>
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