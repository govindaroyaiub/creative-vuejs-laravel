<template>

    <Head title="Clients" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-8 max-w-6xl">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">TinyPNG Image Compressor</h1>
                <p class="text-gray-600 dark:text-gray-300">Compress your images with TinyPNG. Maximum 20 images
                    allowed.
                </p>
            </div>

            <!-- Upload Area -->
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 mb-6 text-center transition-colors"
                :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/10': dragOver }" @drop="handleDrop"
                @dragover="handleDragOver" @dragenter="handleDragEnter" @dragleave="handleDragLeave">
                <div class="space-y-4">
                    <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                        </path>
                    </svg>
                    <div>
                        <p class="text-lg text-gray-600 dark:text-gray-300">
                            Drop images here or
                            <label for="fileInput" class="text-blue-600 hover:text-blue-500 cursor-pointer">browse
                                files</label>
                        </p>
                        <input type="file" id="fileInput" ref="fileInput" class="hidden" multiple
                            accept="image/png,image/jpeg,image/jpg" @change="handleFileSelect">
                        <p class="text-sm text-gray-500">PNG, JPG up to 5MB each. Max 20 files.</p>
                    </div>
                </div>
            </div>

            <!-- File List -->
            <div v-if="files.length > 0" class="space-y-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Images ({{ files.length }}/20)
                    </h2>
                    <div class="space-x-2">
                        <button @click="compressAll"
                            :disabled="isProcessing || files.every(f => f.status === 'completed')"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ isProcessing ? 'Compressing...' : 'Compress All' }}
                        </button>
                        <button @click="clearAll" :disabled="isProcessing"
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 disabled:opacity-50">
                            Clear All
                        </button>
                    </div>
                </div>

                <!-- Files Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="max-h-96 overflow-y-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        File</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        Size</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        Status</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        Progress</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                <tr v-for="(file, index) in files" :key="file.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center space-x-3">
                                            <img :src="file.preview" class="w-10 h-10 rounded object-cover"
                                                :alt="file.name">
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">
                                                    {{ file.name }}</p>
                                                <p class="text-xs text-gray-500">{{ file.originalFile.type }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        <div>
                                            <div>Original: {{ formatFileSize(file.originalSize) }}</div>
                                            <div v-if="file.compressedSize" class="text-green-600">
                                                Compressed: {{ formatFileSize(file.compressedSize) }}
                                                <span class="text-xs">({{ file.savings }}% saved)</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="getStatusClass(file.status)">
                                            {{ getStatusText(file.status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all duration-300"
                                                :class="file.status === 'completed' ? 'bg-green-500' : file.status === 'error' ? 'bg-red-500' : 'bg-blue-500'"
                                                :style="{ width: file.progress + '%' }"></div>
                                        </div>
                                        <span class="text-xs text-gray-500 mt-1">{{ file.progress }}%</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button @click="removeFile(index)" :disabled="isProcessing"
                                            class="text-red-600 hover:text-red-800 disabled:opacity-50"
                                            title="Remove file">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Download Section -->
                <div v-if="allCompleted"
                    class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span class="text-green-700 dark:text-green-300 font-medium">All images compressed
                                successfully!</span>
                        </div>
                        <button @click="downloadZip" :disabled="isDownloading"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <span>{{ isDownloading ? 'Creating ZIP...' : 'Download ZIP' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'TinyPNG', href: '/tinypng' }];

const files = ref([]);
const dragOver = ref(false);
const isProcessing = ref(false);
const isDownloading = ref(false);
const fileInput = ref(null);

let fileCounter = 0;

const allCompleted = computed(() =>
    files.value.length > 0 && files.value.every(file => file.status === 'completed')
);

// Drag and drop handlers
const handleDragOver = (e) => {
    e.preventDefault();
    dragOver.value = true;
};

const handleDragEnter = (e) => {
    e.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = (e) => {
    e.preventDefault();
    if (!e.currentTarget.contains(e.relatedTarget)) {
        dragOver.value = false;
    }
};

const handleDrop = (e) => {
    e.preventDefault();
    dragOver.value = false;
    const droppedFiles = Array.from(e.dataTransfer.files);
    addFiles(droppedFiles);
};

const handleFileSelect = (e) => {
    const selectedFiles = Array.from(e.target.files);
    addFiles(selectedFiles);
    e.target.value = ''; // Reset input
};

const addFiles = (newFiles) => {
    const validFiles = newFiles.filter(file => {
        if (!['image/png', 'image/jpeg', 'image/jpg'].includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid file type',
                text: `${file.name} is not a supported image format (PNG, JPG, JPEG only)`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000
            });
            return false;
        }
        if (file.size > 5 * 1024 * 1024) { // 5MB
            Swal.fire({
                icon: 'error',
                title: 'File too large',
                text: `${file.name} exceeds 5MB limit`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000
            });
            return false;
        }
        return true;
    });

    if (files.value.length + validFiles.length > 20) {
        Swal.fire({
            icon: 'warning',
            title: 'Too many files',
            text: 'Maximum 20 files allowed at once',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000
        });
        return;
    }

    validFiles.forEach(file => {
        const fileObject = {
            id: ++fileCounter,
            name: file.name,
            originalFile: file,
            originalSize: file.size,
            compressedSize: null,
            savings: 0,
            status: 'pending', // pending, processing, completed, error
            progress: 0,
            preview: URL.createObjectURL(file)
        };
        files.value.push(fileObject);
    });

    if (validFiles.length > 0) {
        Swal.fire({
            icon: 'success',
            title: 'Files Added',
            text: `${validFiles.length} file(s) added successfully`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
    }
};

const removeFile = (index) => {
    URL.revokeObjectURL(files.value[index].preview);
    files.value.splice(index, 1);

    Swal.fire({
        icon: 'info',
        title: 'File Removed',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500
    });
};

const clearAll = () => {
    if (files.value.length === 0) return;

    Swal.fire({
        title: 'Clear all files?',
        text: "This will remove all files from the list",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, clear all'
    }).then((result) => {
        if (result.isConfirmed) {
            files.value.forEach(file => URL.revokeObjectURL(file.preview));
            files.value = [];

            Swal.fire({
                icon: 'success',
                title: 'Cleared!',
                text: 'All files have been removed',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        }
    });
};

const compressAll = async () => {
    const pendingFiles = files.value.filter(file => file.status === 'pending');

    if (pendingFiles.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No files to compress',
            text: 'All files have already been processed',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        return;
    }

    isProcessing.value = true;

    // Show progress popup
    Swal.fire({
        title: 'Compressing Images',
        html: `Processing <b>0</b> of <b>${pendingFiles.length}</b> images...`,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let processed = 0;
    for (const file of pendingFiles) {
        await compressFile(file);
        processed++;

        // Update progress
        Swal.update({
            html: `Processing <b>${processed}</b> of <b>${pendingFiles.length}</b> images...`
        });
    }

    isProcessing.value = false;

    Swal.close();

    if (allCompleted.value) {
        const totalOriginalSize = files.value.reduce((sum, file) => sum + file.originalSize, 0);
        const totalCompressedSize = files.value.reduce((sum, file) => sum + (file.compressedSize || 0), 0);
        const totalSavings = Math.round(((totalOriginalSize - totalCompressedSize) / totalOriginalSize) * 100);

        Swal.fire({
            icon: 'success',
            title: 'Compression Complete!',
            html: `
                <div class="text-left">
                    <p><strong>All ${files.value.length} images compressed successfully!</strong></p>
                    <br>
                    <p>📊 <strong>Summary:</strong></p>
                    <p>• Original size: ${formatFileSize(totalOriginalSize)}</p>
                    <p>• Compressed size: ${formatFileSize(totalCompressedSize)}</p>
                    <p>• Total savings: <span style="color: #10b981;">${totalSavings}%</span></p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Download ZIP',
            cancelButtonText: 'Close',
            confirmButtonColor: '#10b981'
        }).then((result) => {
            if (result.isConfirmed) {
                downloadZip();
            }
        });
    }
};

const compressFile = async (file) => {
    try {
        file.status = 'processing';
        file.progress = 10;

        const formData = new FormData();
        formData.append('image', file.originalFile);

        const response = await axios.post('/tinypng/compress', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    const progress = Math.round((progressEvent.loaded / progressEvent.total) * 50);
                    file.progress = 10 + progress; // 10-60% for upload
                }
            },
        });

        file.progress = 80;

        if (response.data.success) {
            file.compressedSize = response.data.compressed_size;
            file.savings = response.data.savings_percent;
            file.status = 'completed';
            file.progress = 100;
        } else {
            throw new Error(response.data.message || 'Compression failed');
        }
    } catch (error) {
        file.status = 'error';
        file.progress = 0;

        Swal.fire({
            icon: 'error',
            title: 'Compression Failed',
            text: `Failed to compress ${file.name}: ${error.response?.data?.message || error.message}`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000
        });
    }
};

const downloadZip = async () => {
    isDownloading.value = true;

    Swal.fire({
        title: 'Creating ZIP file...',
        text: 'Please wait while we package your compressed images',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await axios.post('/tinypng/download-zip', {}, {
            responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'application/zip' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'tinified.zip';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        Swal.fire({
            icon: 'success',
            title: 'Download Started!',
            text: 'Your compressed images are being downloaded as tinified.zip',
            timer: 3000,
            showConfirmButton: false
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Download Failed',
            text: 'Failed to create or download the ZIP file. Please try again.',
            confirmButtonText: 'OK'
        });
    }

    isDownloading.value = false;
};

// Utility functions
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getStatusClass = (status) => {
    switch (status) {
        case 'pending': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'processing': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'completed': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'error': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getStatusText = (status) => {
    switch (status) {
        case 'pending': return 'Pending';
        case 'processing': return 'Processing';
        case 'completed': return 'Completed';
        case 'error': return 'Error';
        default: return 'Unknown';
    }
};
</script>