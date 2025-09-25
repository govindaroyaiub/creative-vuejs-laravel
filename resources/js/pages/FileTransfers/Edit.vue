<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Download, X } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'File Transfers', href: '/file-transfers' },
    { title: 'Edit File Transfer', href: '/file-transfer-edit' },
];

// Get file transfer data from Inertia props
const fileTransfer = ref(usePage().props.fileTransfer);

// Reactive form using Inertia's useForm
const form = useForm({
    name: '',
    client: '',
    files: [] as File[],
});

const fileSize = ref(0); // Store total file size in MB

// Populate form fields when the component mounts
onMounted(() => {
    if (fileTransfer.value) {
        form.name = fileTransfer.value.name;
        form.client = fileTransfer.value.client;
    }
});

// Handle file selection
const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        const filesArray = Array.from(input.files);
        form.files = filesArray;

        // Calculate total file size in MB
        let totalSize = 0;
        filesArray.forEach((file) => {
            totalSize += file.size;
        });

        fileSize.value = (totalSize / (1024 * 1024)).toFixed(2);
    }
};

const successMessage = ref('');

// Submit form for updating file transfer
const handleSubmit = async () => {
    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('client', form.client);

    form.files.forEach((file) => {
        formData.append('file[]', file);
    });

    try {
        const response = await axios.post(`/file-transfers-edit/${fileTransfer.value.id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        successMessage.value = 'File transfer updated successfully!';

        // ✅ Reset the file input and form files array
        form.files = [];
        document.getElementById('file').value = ''; // Clears file input field

        // ✅ Update file list from response (assuming server returns updated paths)
        if (response.data.file_paths) {
            fileTransfer.value.file_paths = response.data.file_paths;
        }
    } catch (error) {
        console.error('Update failed:', error.response?.data || error.message);
        successMessage.value = 'Failed to update file transfer. Please try again.';
    }
};

const dismissMessage = () => {
    successMessage.value = '';
};
</script>

<template>
    <Head title="Edit File Transfers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-2xl p-4">
            <form @submit.prevent="handleSubmit" class="mx-auto w-full max-w-2xl space-y-6" enctype="multipart/form-data">
                <div v-if="successMessage" class="relative mb-4 rounded-2xl bg-green-500 p-3 pr-10 text-white">
                    {{ successMessage }}
                    <button @click="dismissMessage" type="button" class="absolute right-2 top-2 text-white hover:text-gray-200 focus:outline-none">
                        <X class="inline h-5 w-5" />
                    </button>
                </div>
                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                <input type="hidden" name="_method" value="PUT" />
                <!-- Name Field -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        v-model="form.name"
                        required
                        class="mt-1 block w-full rounded-2xl border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-black dark:text-white dark:focus:ring-indigo-400"
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
                        class="mt-1 block w-full rounded-2xl border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-black dark:text-white dark:focus:ring-indigo-400"
                    />
                </div>

                <div v-if="fileTransfer.file_paths.length > 0" class="rounded-2xl border bg-transparent p-4 shadow-none dark:bg-transparent">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200"> Existing Files </label>
                    <ul class="space-y-1">
                        <li
                            v-for="(file, index) in fileTransfer.file_paths"
                            :key="index"
                            class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300"
                        >
                            <span class="truncate">{{ file }}</span>
                            <a
                                :href="`/Transfer Files/${file}`"
                                download
                                class="ml-4 text-xs text-indigo-600 underline hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                <Download class="inline h-5 w-5" />
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- File Upload Field -->
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Upload Zip Files</label>
                    <input
                        type="file"
                        name="file[]"
                        id="file"
                        multiple
                        accept=".zip"
                        @change="handleFileChange"
                        class="mt-1 block w-full rounded-2xl border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-black dark:text-white dark:focus:ring-indigo-400"
                    />

                    <!-- Individual file sizes -->
                    <div v-if="form.files.length > 0" class="mt-4 space-y-2">
                        <div v-for="(file, index) in form.files" :key="index" class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>{{ file.name }}</span>
                            <span>{{ (file.size / (1024 * 1024)).toFixed(2) }} MB</span>
                        </div>
                    </div>

                    <!-- Total file size -->
                    <div v-if="fileSize" class="mt-2 text-sm text-gray-600 dark:text-gray-300">Total File Size: {{ fileSize }} MB</div>
                </div>

                <!-- Submit and Back Buttons -->
                <div class="flex space-x-4">
                    <Link
                        type="button"
                        class="w-full rounded-2xl bg-red-600 px-6 py-3 text-center text-white shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-red-500 dark:hover:bg-red-600"
                        :href="route('file-transfers')"
                    >
                        Back
                    </Link>
                    <button
                        type="submit"
                        class="w-full rounded-2xl bg-indigo-600 px-6 py-3 text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        Update
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.dark .bg-black {
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
