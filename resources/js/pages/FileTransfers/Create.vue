<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'File Transfers', href: '/file-transfers' },
    { title: 'Add File Transfer', href: '/file-transfer-create' },
];

const form = ref({
    name: '',
    client: '',
    files: [] as File[],
});

const fileSize = ref('0.00');
const dragOver = ref(false);

const handleFileDrop = (event: DragEvent) => {
    dragOver.value = false;
    if (!event.dataTransfer?.files) return;

    const droppedFiles = Array.from(event.dataTransfer.files).filter((file) =>
        file.name.toLowerCase().endsWith('.zip')
    );
    form.value.files = droppedFiles;

    const totalBytes = droppedFiles.reduce((acc, file) => acc + file.size, 0);
    fileSize.value = (totalBytes / (1024 * 1024)).toFixed(2);
};

const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files) return;

    const selected = Array.from(input.files).filter((file) =>
        file.name.toLowerCase().endsWith('.zip')
    );
    form.value.files = selected;

    const totalBytes = selected.reduce((acc, file) => acc + file.size, 0);
    fileSize.value = (totalBytes / (1024 * 1024)).toFixed(2);
};

const handleSubmit = () => {
    const payload = new FormData();
    payload.append('name', form.value.name);
    payload.append('client', form.value.client);
    form.value.files.forEach((file) => {
        payload.append('file[]', file);
    });

    router.post('/file-transfers-add', payload, {
        forceFormData: true,
    });
};

const goBack = () => window.history.back();
</script>

<template>

    <Head title="Add File Transfers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <form @submit.prevent="handleSubmit" class="mx-auto w-full max-w-2xl space-y-6">

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
                    <input id="name" v-model="form.name" required type="text"
                        class="mt-1 block w-full rounded-md border px-3 py-2 dark:bg-gray-700 dark:text-white" />
                </div>

                <!-- Client -->
                <div>
                    <label for="client"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-200">Client</label>
                    <input id="client" v-model="form.client" required type="text"
                        class="mt-1 block w-full rounded-md border px-3 py-2 dark:bg-gray-700 dark:text-white" />
                </div>

                <!-- Drag & Drop Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Upload ZIP
                        Files</label>

                    <div @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
                        @drop.prevent="handleFileDrop"
                        :class="['flex flex-col items-center justify-center border-2 border-dashed p-6 rounded-lg transition-all',
                            dragOver ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600']">
                        <input type="file" multiple accept=".zip" @change="handleFileChange" hidden id="fileUpload" />
                        <label for="fileUpload"
                            class="cursor-pointer text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                            Click to upload or drag ZIP files here
                        </label>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Only .zip files are allowed</p>
                    </div>

                    <!-- File list -->
                    <div v-if="form.files.length" class="mt-3 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        <div v-for="(file, idx) in form.files" :key="idx">
                            {{ file.name }} - {{ (file.size / 1024 / 1024).toFixed(2) }} MB
                        </div>
                        <div>Total size: {{ fileSize }} MB</div>
                    </div>
                </div>

                <!-- Submit / Back -->
                <div class="flex space-x-4">
                    <button type="button" @click="goBack"
                        class="w-full rounded-lg bg-red-600 px-6 py-3 text-white shadow hover:bg-red-700">
                        Back
                    </button>
                    <button type="submit"
                        class="w-full rounded-lg bg-green-600 px-6 py-3 text-white shadow hover:bg-green-700">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>