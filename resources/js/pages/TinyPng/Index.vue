<template>

    <Head title="TinyPNG Compressor" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-8 max-w-6xl">
            <div class="mb-8">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">TinyPNG Image Compressor</h1>
                        <p class="text-gray-600 dark:text-gray-300">Compress your images with TinyPNG. Maximum 20 images
                            allowed.</p>
                    </div>

                    <!-- ✅ Compression Count Display with Loading State -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 min-w-[200px]">
                        <div class="text-center">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">API Usage This Month
                            </h3>

                            <!-- Loading state -->
                            <div v-if="isLoadingCount" class="space-y-2">
                                <div class="text-2xl font-bold text-gray-400">
                                    <div class="animate-pulse bg-gray-300 dark:bg-gray-600 h-8 w-16 mx-auto rounded">
                                    </div>
                                </div>
                                <div class="text-xs text-gray-400">
                                    <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 w-20 mx-auto rounded">
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="animate-pulse bg-gray-300 dark:bg-gray-600 h-2 w-1/3 rounded-full">
                                    </div>
                                </div>
                                <div class="text-xs text-gray-400">Loading...</div>
                            </div>

                            <!-- Free Tier Display -->
                            <div v-else-if="compressionCount === null" class="space-y-1">
                                <div
                                    class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 rounded-full">
                                    🎉 Free Plan Active
                                </div>
                                <div class="text-xs text-gray-500">
                                    500 compressions/month
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-blue-500 transition-all duration-300"
                                        style="width: 100%"></div>
                                </div>
                                <div class="text-xs text-blue-600">
                                    500 available
                                </div>
                            </div>

                            <!-- Loaded state -->
                            <div v-else-if="compressionCount !== null" class="space-y-1">
                                <div class="text-2xl font-bold"
                                    :class="remainingCompressions > 100 ? 'text-green-600' : remainingCompressions > 50 ? 'text-yellow-600' : 'text-red-600'">
                                    {{ remainingCompressions }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ compressionCount }}/500 used
                                </div>
                                <div
                                    class="text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded-full">
                                    💎 Paid Plan
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all duration-300"
                                        :class="usagePercent <= 60 ? 'bg-green-500' : usagePercent <= 80 ? 'bg-yellow-500' : 'bg-red-500'"
                                        :style="{ width: Math.max(usagePercent, 2) + '%' }"></div>
                                </div>
                                <div class="text-xs mt-1"
                                    :class="remainingCompressions > 100 ? 'text-green-600' : remainingCompressions > 50 ? 'text-yellow-600' : 'text-red-600'">
                                    {{ remainingCompressions }} remaining
                                </div>
                            </div>

                            <!-- Error state -->
                            <div v-else class="text-gray-400 space-y-1">
                                <div class="text-lg">---</div>
                                <div class="text-xs">Unable to fetch</div>
                                <div class="text-xs text-red-500">Check API key</div>
                            </div>

                            <button @click="refreshCompressionCount" :disabled="isRefreshing || isLoadingCount"
                                class="mt-2 text-xs text-blue-600 hover:text-blue-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <span v-if="isRefreshing || isLoadingCount"
                                    class="flex items-center justify-center space-x-1">
                                    <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span>Loading...</span>
                                </span>
                                <span v-else>Refresh</span>
                            </button>
                        </div>
                    </div>
                </div>
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

                        <!-- ✅ Warning if running low on compressions -->
                        <div v-if="remainingCompressions !== null && remainingCompressions < 50"
                            class="mt-2 text-sm text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 p-2 rounded">
                            ⚠️ Only {{ remainingCompressions }} compressions remaining this month
                        </div>

                        <!-- Show when API data is still loading -->
                        <div v-if="isLoadingCount" class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                            🔄 Loading API usage data...
                        </div>
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
                            :disabled="isProcessing || files.every(f => f.status === 'completed') || (remainingCompressions !== null && remainingCompressions < files.filter(f => f.status === 'pending').length) || isLoadingCount"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            {{ isProcessing ? 'Compressing...' : 'Compress All' }}
                        </button>
                        <button @click="clearAll" :disabled="isProcessing"
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 disabled:opacity-50 transition-colors">
                            Clear All
                        </button>
                    </div>
                </div>

                <!-- ✅ Warning if not enough compressions left -->
                <div v-if="remainingCompressions !== null && remainingCompressions < files.filter(f => f.status === 'pending').length"
                    class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-lg p-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-orange-600 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
                        <span class="text-orange-700 dark:text-orange-300 font-medium">
                            Not enough API quota! You have {{ remainingCompressions }} compressions left, but {{
                                files.filter(f => f.status === 'pending').length}} files pending.
                        </span>
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
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center space-x-3">
                                            <img :src="file.preview" class="w-10 h-10 rounded object-cover"
                                                :alt="file.name">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs"
                                                    :title="file.name">
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
                                        <div class="space-y-1">
                                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                <div class="h-2 rounded-full transition-all duration-300"
                                                    :class="file.status === 'completed' ? 'bg-green-500' : file.status === 'error' ? 'bg-red-500' : 'bg-blue-500'"
                                                    :style="{ width: file.progress + '%' }"></div>
                                            </div>
                                            <span class="text-xs text-gray-500">{{ file.progress }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button @click="removeFile(index)" :disabled="isProcessing"
                                            class="text-red-600 hover:text-red-800 disabled:opacity-50 transition-colors p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20"
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
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-green-700 dark:text-green-300 font-medium">All images compressed
                                successfully!</span>
                        </div>
                        <button @click="downloadZip" :disabled="isDownloading"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 flex items-center space-x-2 transition-colors">
                            <svg v-if="isDownloading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import type { BreadcrumbItem } from '@/types';

// ✅ Add props for compression count (passed from controller)
const props = defineProps<{
    compressionCount?: number;
    isNewAccount?: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'TinyPNG', href: '/tinypng' }];

const files = ref<any[]>([]);
const dragOver = ref(false);
const isProcessing = ref(false);
const isDownloading = ref(false);
const isRefreshing = ref(false);
const isLoadingCount = ref(true); // ✅ Loading state for compression count
const fileInput = ref<HTMLInputElement | null>(null);

// ✅ Reactive compression count with better initialization
const compressionCount = ref<number | null>(
    props.compressionCount !== undefined ? props.compressionCount : null
);
const isNewAccount = ref(props.isNewAccount || false);

let fileCounter = 0;


// ✅ Add free tier detection
const isFreeTier = ref(false);

// ✅ Computed properties for API usage
const remainingCompressions = computed(() => {
    if (compressionCount.value === null) {
        return 500; // Show 500 for free tier since we don't know exact usage
    }
    return 500 - compressionCount.value;
});

const usagePercent = computed(() => {
    if (compressionCount.value === null) {
        return 0; // Unknown usage on free tier
    }
    return Math.round((compressionCount.value / 500) * 100);
});

const allCompleted = computed(() =>
    files.value.length > 0 && files.value.every(file => file.status === 'completed')
);

// ✅ Function to refresh compression count
const refreshCompressionCount = async () => {
    isRefreshing.value = true;

    try {
        const response = await axios.get('/tinypng/compression-count');

        compressionCount.value = response.data.compression_count;
        isFreeTier.value = response.data.is_free_tier || false;

        if (response.data.is_free_tier) {
            
        } else if (response.data.compression_count === null) {
            Swal.fire({
                icon: 'warning',
                title: 'API Issue',
                text: response.data.message || 'Could not fetch API usage.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000
            });
        }
    } catch (error: any) {
        console.error('Failed to fetch compression count:', error);
        compressionCount.value = null;

        Swal.fire({
            icon: 'error',
            title: 'Failed to refresh API usage',
            text: 'Could not connect to TinyPNG API.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000
        });
    }
    isRefreshing.value = false;
    isLoadingCount.value = false;
};

// Drag and drop handlers
const handleDragOver = (e: DragEvent) => {
    e.preventDefault();
    dragOver.value = true;
};

const handleDragEnter = (e: DragEvent) => {
    e.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = (e: DragEvent) => {
    e.preventDefault();
    if (!e.currentTarget?.contains(e.relatedTarget as Node)) {
        dragOver.value = false;
    }
};

const handleDrop = (e: DragEvent) => {
    e.preventDefault();
    dragOver.value = false;
    const droppedFiles = Array.from(e.dataTransfer?.files || []);
    addFiles(droppedFiles);
};

const handleFileSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const selectedFiles = Array.from(target.files || []);
    addFiles(selectedFiles);
    target.value = ''; // Reset input
};

const addFiles = (newFiles: File[]) => {
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

    // ✅ Check if user has enough API quota
    const pendingFiles = files.value.filter(f => f.status === 'pending').length;
    const totalPendingAfterAdd = pendingFiles + validFiles.length;

    if (remainingCompressions.value !== null && totalPendingAfterAdd > remainingCompressions.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Not enough API quota',
            html: `You only have <strong>${remainingCompressions.value}</strong> compressions remaining this month, but you're trying to add <strong>${totalPendingAfterAdd}</strong> files total.<br><br>Would you like to add them anyway? (They won't be compressed until you have more quota)`,
            showCancelButton: true,
            confirmButtonText: 'Add anyway',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#f59e0b'
        }).then((result) => {
            if (result.isConfirmed) {
                processValidFiles(validFiles);
            }
        });
        return;
    }

    processValidFiles(validFiles);
};

const processValidFiles = (validFiles: File[]) => {
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

const removeFile = (index: number) => {
    const fileToRemove = files.value[index];
    URL.revokeObjectURL(fileToRemove.preview);
    files.value.splice(index, 1);

    Swal.fire({
        icon: 'info',
        title: 'File Removed',
        text: `${fileToRemove.name} has been removed`,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000
    });
};

const clearAll = () => {
    if (files.value.length === 0) return;

    Swal.fire({
        title: 'Clear all files?',
        text: `This will remove all ${files.value.length} files from the list`,
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

    // ✅ Final check for API quota
    if (remainingCompressions.value !== null && pendingFiles.length > remainingCompressions.value) {
        Swal.fire({
            icon: 'error',
            title: 'Not enough API quota',
            html: `You only have <strong>${remainingCompressions.value}</strong> compressions remaining, but <strong>${pendingFiles.length}</strong> files are pending.<br><br>Please remove some files or wait for your quota to reset.`,
            confirmButtonText: 'OK'
        });
        return;
    }

    isProcessing.value = true;

    // Show progress popup
    Swal.fire({
        title: 'Compressing Images',
        html: `Processing <b>0</b> of <b>${pendingFiles.length}</b> images...<br><small>API Usage will update after each compression</small>`,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let processed = 0;
    let failed = 0;

    for (const file of pendingFiles) {
        const success = await compressFile(file);
        processed++;
        if (!success) failed++;

        // Update progress
        Swal.update({
            html: `Processing <b>${processed}</b> of <b>${pendingFiles.length}</b> images...<br>
                   <small>API Usage: ${compressionCount.value}/500 | Failed: ${failed}</small>`
        });
    }

    isProcessing.value = false;
    Swal.close();

    // Show completion summary
    const successCount = processed - failed;
    if (successCount > 0) {
        const totalOriginalSize = files.value
            .filter(file => file.status === 'completed')
            .reduce((sum, file) => sum + file.originalSize, 0);
        const totalCompressedSize = files.value
            .filter(file => file.status === 'completed')
            .reduce((sum, file) => sum + (file.compressedSize || 0), 0);
        const totalSavings = totalOriginalSize > 0 ? Math.round(((totalOriginalSize - totalCompressedSize) / totalOriginalSize) * 100) : 0;

        Swal.fire({
            icon: successCount === processed ? 'success' : 'warning',
            title: failed > 0 ? 'Compression Partially Complete' : 'Compression Complete!',
            html: `
                <div class="text-left">
                    <p><strong>${successCount} of ${processed} images compressed successfully!</strong></p>
                    ${failed > 0 ? `<p class="text-red-600"><strong>${failed} images failed to compress.</strong></p>` : ''}
                    <br>
                    <p>📊 <strong>Summary:</strong></p>
                    <p>• Original size: ${formatFileSize(totalOriginalSize)}</p>
                    <p>• Compressed size: ${formatFileSize(totalCompressedSize)}</p>
                    <p>• Total savings: <span style="color: #10b981;">${totalSavings}%</span></p>
                    <p>• API usage: <span style="color: #6b7280;">${compressionCount.value}/500</span></p>
                </div>
            `,
            showCancelButton: successCount > 0 ? true : false,
            confirmButtonText: successCount > 0 ? 'Download ZIP' : 'OK',
            cancelButtonText: 'Close',
            confirmButtonColor: '#10b981'
        }).then((result) => {
            if (result.isConfirmed && successCount > 0) {
                downloadZip();
            }
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Compression Failed',
            text: 'All images failed to compress. Please check your API key and try again.',
            confirmButtonText: 'OK'
        });
    }
};

const compressFile = async (file: any): Promise<boolean> => {
    try {
        file.status = 'processing';
        file.progress = 10;

        const formData = new FormData();
        formData.append('image', file.originalFile);

        const response = await axios.post('/tinypng/compress', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent: any) => {
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

            // ✅ Update compression count after successful compression
            if (response.data.compression_count !== undefined) {
                compressionCount.value = response.data.compression_count;
            }

            return true;
        } else {
            throw new Error(response.data.message || 'Compression failed');
        }
    } catch (error: any) {
        file.status = 'error';
        file.progress = 0;

        const errorMessage = error.response?.data?.message || error.message;
        console.error(`Compression failed for ${file.name}:`, errorMessage);

        return false;
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
    } catch (error: any) {
        console.error('Download failed:', error);
        Swal.fire({
            icon: 'error',
            title: 'Download Failed',
            text: error.response?.data?.message || 'Failed to create or download the ZIP file. Please try again.',
            confirmButtonText: 'OK'
        });
    }

    isDownloading.value = false;
};

// Utility functions
const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getStatusClass = (status: string) => {
    switch (status) {
        case 'pending': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'processing': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'completed': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'error': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getStatusText = (status: string) => {
    switch (status) {
        case 'pending': return 'Pending';
        case 'processing': return 'Processing';
        case 'completed': return 'Completed';
        case 'error': return 'Error';
        default: return 'Unknown';
    }
};

// ✅ Initialize compression count on mount
onMounted(() => {
    // If we didn't get the count from props, or if it's null, fetch it immediately
    if (compressionCount.value === null || compressionCount.value === undefined) {
        refreshCompressionCount();
    } else {
        // We have initial data from props, no need to load
        isLoadingCount.value = false;
    }
});
</script>