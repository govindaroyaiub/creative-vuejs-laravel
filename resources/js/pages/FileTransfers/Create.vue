<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Add File Transfer',
        href: '/file-transfer-add',
    },
];
</script>

<template>
    <Head title="Add File Transfers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <form @submit.prevent="handleSubmit" class="mx-auto w-full max-w-2xl space-y-6">
                <!-- Name Field -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        v-model="form.name"
                        required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:ring-indigo-400"
                    />
                </div>

                <!-- Client Field -->
                <div>
                    <label for="client" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Client</label>
                    <input
                        type="text"
                        name="client"
                        id="client"
                        v-model="form.client"
                        required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:ring-indigo-400"
                    />
                </div>

                <!-- File Field -->
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Upload Zip Files</label>
                    <input
                        type="file"
                        name="file[]"
                        id="file"
                        multiple
                        accept=".zip"
                        @change="handleFileChange"
                        required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:ring-indigo-400"
                    />

                    <!-- Individual file sizes -->
                    <div v-if="form.files.length > 0" class="mt-4 space-y-2">
                        <div v-for="(file, index) in form.files" :key="index" class="text-sm text-gray-600 dark:text-gray-300">
                            {{ file.name }} - {{ (file.size / (1024 * 1024)).toFixed(2) }} MB
                        </div>
                    </div>

                    <!-- Total file size -->
                    <div v-if="fileSize" class="mt-2 text-sm text-gray-600 dark:text-gray-300">Total File Size: {{ fileSize }} MB</div>
                </div>

                <!-- Submit and Back Buttons -->
                <div class="flex space-x-4">
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-6 py-3 text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        Submit
                    </button>
                    <button
                        type="button"
                        class="w-full rounded-lg bg-red-600 px-6 py-3 text-white shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-red-500 dark:hover:bg-red-600"
                        @click="goBack"
                    >
                        Back
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
<script lang="ts">
export default {
    data() {
        return {
            form: {
                name: '',
                client: '',
                files: [], // This will hold the selected files
            },
            fileSize: 0, // Store total file size in MB
        };
    },
    methods: {
        handleFileChange(event) {
            const files = Array.from(event.target.files);
            this.form.files = files;

            // Calculate total file size in MB
            let totalSize = 0;
            files.forEach((file) => {
                totalSize += file.size;
            });

            // Convert bytes to MB and round off
            this.fileSize = (totalSize / (1024 * 1024)).toFixed(2);
        },
        handleSubmit() {
            const formData = new FormData();
            formData.append('name', this.form.name);
            formData.append('client', this.form.client);
            this.form.files.forEach((file) => {
                formData.append('file[]', file);
            });

            // Send the form data using Inertia post
            this.$inertia.post('/file-transfers-add', formData);
        },
        goBack() {
            // Handle back button click
            window.history.back();
        },
    },
};
</script>

<style scoped>
.dark .bg-gray-700 {
    background-color: #2a2a2a;
}

.dark .text-gray-300 {
    color: #e0e0e0;
}

.dark .border-gray-600 {
    border-color: #4a4a4a;
}

.dark .focus\:ring-indigo-500 {
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.5);
}
</style>
