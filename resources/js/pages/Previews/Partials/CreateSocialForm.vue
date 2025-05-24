<template>
    <div class="space-y-6">
        <h2 class="text-lg font-semibold">Step 3: Upload Social Images</h2>

        <!-- Drag & Drop Upload Area -->
        <div class="block w-full cursor-pointer border-2 border-dashed border-gray-300 p-6 text-center rounded-lg dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800"
            @click="triggerInput" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop" :class="{ 'border-green-500 bg-green-50 dark:bg-green-900': isDragging }">
            <span class="text-sm text-gray-600 dark:text-gray-300">Click or drag PNG/JPG images here to upload</span>
            <input ref="fileInput" type="file" class="hidden" multiple accept=".png,.jpg,.jpeg"
                @change="handleFileUpload" />
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            Drag to sort. Click ❌ to remove. Each image requires a name.
        </p>

        <!-- Social Images List -->
        <draggable v-model="form.socials" item-key="file.name" handle=".handle" class="space-y-3" ghost-class="ghost">
            <template #item="{ element: social, index }">
                <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
                    <!-- Sort Handle -->
                    <div class="flex items-center gap-2 w-10 text-gray-500 dark:text-gray-300">
                        <span class="font-mono text-sm w-4 text-right">{{ index + 1 }}.</span>
                        <span class="handle cursor-move">☰</span>
                    </div>

                    <!-- Image Preview -->
                    <img :src="social.preview" alt="preview" class="w-12 h-12 object-contain rounded border" />

                    <!-- Name Field -->
                    <input v-model="social.name" type="text" placeholder="Name"
                        class="w-1/2 rounded border px-3 py-1 text-sm dark:bg-gray-800 dark:text-white" />

                    <!-- File Name -->
                    <span class="truncate w-full text-xs text-gray-800 dark:text-white">
                        {{ social.file.name }}
                    </span>

                    <!-- Remove -->
                    <button class="ml-2 text-red-500 hover:text-red-700" title="Remove" @click="removeSocial(index)">
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
                :disabled="!allNamed || loading" @click="handleSubmit">
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
        socials: {
            file: File;
            name: string;
            preview: string;
        }[];
    };
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

    const newSocials = Array.from(files)
        .filter(file => /\.(png|jpe?g)$/i.test(file.name))
        .map(file => ({
            file,
            name: '',
            preview: URL.createObjectURL(file),
        }));

    props.form.socials.splice(0, props.form.socials.length, ...newSocials);
    input.value = '';
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    if (!e.dataTransfer?.files) return;

    const files = Array.from(e.dataTransfer.files);
    const newSocials = files
        .filter(file => /\.(png|jpe?g)$/i.test(file.name))
        .map(file => ({
            file,
            name: '',
            preview: URL.createObjectURL(file),
        }));

    props.form.socials.splice(0, props.form.socials.length, ...newSocials);
};

const removeSocial = (index: number) => {
    // Revoke the object URL to avoid memory leaks
    URL.revokeObjectURL(props.form.socials[index].preview);
    props.form.socials.splice(index, 1);
};

const allNamed = computed(() =>
    props.form.socials.length > 0 && props.form.socials.every(s => s.name.trim() !== '')
);
</script>

<style scoped>
.ghost {
    opacity: 0.5;
}
</style>