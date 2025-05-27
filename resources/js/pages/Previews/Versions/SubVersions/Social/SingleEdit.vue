<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { ref, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const subSocial = computed(() => page.props.subSocial);
const preview = computed(() => page.props.preview);
const version = computed(() => page.props.version);
const subVersion = computed(() => page.props.subVersion);

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.id}` },
    { title: 'Edit Social Image', href: '#' },
];

const form = useForm({
    file: null as File | null,
});

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const previewUrl = ref(subSocial.value.path ? '/' + subSocial.value.path : '');

const triggerInput = () => fileInput.value?.click();

const handleFileUpload = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const files = input.files;
    if (!files || files.length === 0) return;
    const file = files[0];
    if (!/\.(jpe?g|png)$/i.test(file.name)) {
        Swal.fire('Error', 'Only JPG/PNG images allowed.', 'error');
        return;
    }
    form.file = file;
    previewUrl.value = URL.createObjectURL(file);
    input.value = '';
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    const files = e.dataTransfer?.files;
    if (!files || files.length === 0) return;
    const file = files[0];
    if (!/\.(jpe?g|png)$/i.test(file.name)) {
        Swal.fire('Error', 'Only JPG/PNG images allowed.', 'error');
        return;
    }
    form.file = file;
    previewUrl.value = URL.createObjectURL(file);
};

const removeImage = () => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    form.file = null;
    previewUrl.value = '';
};

const handleSubmit = () => {
    if (!form.file) {
        Swal.fire('Error', 'Please select an image.', 'error');
        return;
    }
    const payload = new FormData();
    payload.append('file', form.file);

    axios.post(`/previews/social/single/edit/${subSocial.value.id}`, payload)
        .then(() => {
            Swal.fire('Success', 'Image updated successfully.', 'success');
        })
        .catch(() => {
            Swal.fire('Error', 'Something went wrong.', 'error');
        });
};
</script>

<template>

    <Head title="Edit Social Image" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-lg mx-auto space-y-6 w-full">
            <h2 class="text-xl font-bold mb-4">Edit Social Image</h2>

            <div class="cursor-pointer border-2 border-dashed border-gray-300 p-6 text-center rounded-lg dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800"
                @click="triggerInput" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop" :class="{ 'border-green-500 bg-green-50 dark:bg-green-900': isDragging }">
                <span class="text-sm text-gray-600 dark:text-gray-300">
                    Click or drag a PNG/JPG image here to upload (only one)
                </span>
                <input ref="fileInput" type="file" class="hidden" accept=".png,.jpg,.jpeg" @change="handleFileUpload" />
            </div>

            <div v-if="previewUrl" class="flex flex-col items-center space-y-2">
                <img :src="previewUrl" alt="preview" class="w-48 h-48 object-contain rounded border" />
                <button class="text-red-500 hover:text-red-700" @click="removeImage">Remove</button>
            </div>

            <div class="flex space-x-4 mt-4">
                <button type="submit" :disabled="!form.file" @click="handleSubmit"
                    class="w-full rounded-lg bg-indigo-600 px-6 py-3 text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    Update
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
.ghost {
    opacity: 0.5;
}
</style>