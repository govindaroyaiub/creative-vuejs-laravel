<template>
    <div>
        <h2 class="text-lg font-semibold mb-4">Step 3: Add Videos</h2>
        <div class="max-h-[60vh] overflow-y-auto pr-2">
            <div v-for="(video, i) in videos" :key="i" class="border rounded p-4 mb-4 bg-gray-50">
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
                <div class="grid grid-cols-2 gap-4">
                    <!-- Video File Drag & Drop -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Video File</label>
                        <div class="drop-area" @click="triggerInput(i, 'video')"
                            @dragover.prevent="setDragActive(i, 'video', true)"
                            @dragleave.prevent="setDragActive(i, 'video', false)"
                            @drop.prevent="handleDrop($event, i, 'path')"
                            :class="{ 'drop-active': dragActive[i + '-video'] }">
                            <span class="text-gray-600 text-sm">
                                Click or drag video file here to upload
                            </span>
                            <input ref="videoInputs" type="file" accept="video/*" class="hidden"
                                @change="onFileChange($event, i, 'path')" />
                            <div v-if="video.pathName" class="text-xs text-gray-500 mt-1">{{ video.pathName }}</div>
                        </div>
                    </div>
                    <!-- Companion Banner Drag & Drop -->
                    <div>
                        <label class="block text-sm font-medium mb-1">Companion Banner (optional)</label>
                        <div class="drop-area" @click="triggerInput(i, 'banner')"
                            @dragover.prevent="setDragActive(i, 'banner', true)"
                            @dragleave.prevent="setDragActive(i, 'banner', false)"
                            @drop.prevent="handleDrop($event, i, 'companion_banner_path')"
                            :class="{ 'drop-active': dragActive[i + '-banner'] }">
                            <span class="text-gray-600 text-sm">
                                Click or drag image (JPG, PNG, GIF) here to upload
                            </span>
                            <input ref="bannerInputs" type="file" accept=".jpg,.jpeg,.png,.gif" class="hidden"
                                @change="onFileChange($event, i, 'companion_banner_path')" />
                            <div v-if="video.companionBannerName" class="text-xs text-gray-500 mt-1">{{
                                video.companionBannerName }}</div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end mt-2">
                    <button @click="removeVideo(i)" class="text-red-500 hover:underline text-sm">Remove</button>
                </div>
            </div>
        </div>
        <button @click="addVideo" class="mb-4 px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 w-full">
            <Plus class="mr-1 inline h-5 w-5" />
        </button>
        <!-- Sticky Navigation -->
        <div class="sticky bottom-0 bg-white pt-6 z-10 flex justify-between">
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
import { ref, watch, nextTick, computed } from 'vue';
import { Plus } from 'lucide-vue-next';

const props = defineProps<{
    form: any,
    videoSizes: Array<{ id: number, name: string, width: number, height: number }>
}>();

const videos = ref(props.form.videos ?? []);
const dragActive = ref<{ [key: string]: boolean }>({});
const videoInputs = ref([]);
const bannerInputs = ref([]);
const loading = ref(false);

watch(videos, (val) => {
    props.form.videos = val;
}, { deep: true });

const allAssigned = computed(() =>
    videos.value.length > 0 &&
    videos.value.every(v => v.name && v.path && v.size_id)
);

function addVideo() {
    videos.value.push({
        name: '',
        path: null,
        pathName: '',
        companion_banner_path: null,
        companionBannerName: '',
        size_id: ''
    });
    nextTick(() => { });
}

function removeVideo(index: number) {
    videos.value.splice(index, 1);
}

function triggerInput(index: number, type: 'video' | 'banner') {
    if (type === 'video') {
        videoInputs.value[index]?.click();
    } else {
        bannerInputs.value[index]?.click();
    }
}

function onFileChange(event: Event, index: number, field: string) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        if (field === 'path') {
            videos.value[index].path = file;
            videos.value[index].pathName = file.name;
        } else if (field === 'companion_banner_path') {
            if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
                alert('Only JPG, PNG, or GIF images are allowed.');
                return;
            }
            videos.value[index].companion_banner_path = file;
            videos.value[index].companionBannerName = file.name;
        }
    }
}

function handleDrop(event: DragEvent, index: number, field: string) {
    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
        const file = files[0];
        if (field === 'path') {
            videos.value[index].path = file;
            videos.value[index].pathName = file.name;
        } else if (field === 'companion_banner_path') {
            if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
                alert('Only JPG, PNG, or GIF images are allowed.');
                return;
            }
            videos.value[index].companion_banner_path = file;
            videos.value[index].companionBannerName = file.name;
        }
    }
    setDragActive(index, field === 'path' ? 'video' : 'banner', false);
}

function setDragActive(index: number, type: 'video' | 'banner', active: boolean) {
    dragActive.value[index + '-' + type] = active;
}

function handleSubmit() {
    if (!allAssigned.value) {
        alert('Please fill all required fields for each video.');
        return;
    }
    loading.value = true;
    // Simulate async upload, replace with your actual submit logic
    setTimeout(() => {
        loading.value = false;
        emit('submit');
    }, 1500);
}

const emit = defineEmits(['submit', 'previous']);
</script>

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

.sticky {
    position: sticky;
}
</style>