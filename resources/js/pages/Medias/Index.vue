<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Eye, Trash2, Upload, Download } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, computed, watch } from 'vue';

const page = usePage();
const medias = computed(() => page.props.medias?.data || []);
const links = computed(() => page.props.medias?.links || []);
const search = ref(page.props.search ?? '');
const modalVisible = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const newForm = ref({ name: '', file: null });
const dragOver = ref(false);

watch(search, (value) => {
    router.get(route('medias'), { search: value }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
});

const openUploadModal = () => {
    newForm.value = { name: '', file: null };
    modalVisible.value = true;
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    newForm.value.file = target.files?.[0] || null;
};

const uploadFile = () => {
    if (!newForm.value.name || !newForm.value.file) {
        Swal.fire('Error!', 'Name and file are required.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('name', newForm.value.name);
    formData.append('file', newForm.value.file);

    uploading.value = true;
    router.post(route('medias-store'), formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            modalVisible.value = false;
            Swal.fire('Uploaded!', 'Media uploaded successfully.', 'success');
        },
        onError: () => {
            Swal.fire('Error!', 'Failed to upload.', 'error');
        },
        onFinish: () => {
            uploading.value = false;
        },
    });
};

const deleteMedia = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the file.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        router.delete(route('medias-delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire('Deleted!', 'File deleted.', 'success'),
            onError: () => Swal.fire('Error!', 'Delete failed.', 'error'),
        });
    }
};

const handleDrop = (e: DragEvent) => {
    dragOver.value = false;
    if (!e.dataTransfer?.files?.[0]) return;

    newForm.value.file = e.dataTransfer.files[0];
};
</script>

<template>

    <Head title="Media Library" />
    <AppLayout :breadcrumbs="[{ title: 'Media Library', href: '/medias' }]">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <input v-model="search" placeholder="Search..."
                    class="w-full max-w-xs rounded border px-4 py-2 dark:bg-black dark:text-white" />
                <button @click="openUploadModal"
                    class="ml-4 rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                    <Upload class="mr-1 inline h-5 w-5" /> Upload
                </button>
            </div>

            <div class="rounded overflow-x-auto shadow">
                <table class="w-full rounded bg-white dark:bg-black dark:border border">
                    <thead class="bg-gray-100 text-gray-700 dark:bg-black dark:text-gray-300">
                        <tr class="bg-gray-100 dark:bg-black uppercase">
                            <th class="border-b px-4 py-2 text-center">#</th>
                            <th class="border-b px-4 py-2 text-center">Name</th>
                            <th class="border-b px-4 py-2 text-center">Uploader</th>
                            <th class="border-b px-4 py-2 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(media, index) in medias" :key="media.id" class="bg-white dark:bg-gray-900">
                            <td class="border-b px-4 py-2 text-center">{{ index + 1 }}</td>
                            <td class="border-b px-4 py-2 text-center">{{ media.name }}</td>
                            <td class="border-b px-4 py-2 text-center">
                                {{ media.uploader?.name ?? 'N/A' }}
                                <hr />
                                {{ new Date(media.created_at).toLocaleDateString('en-GB', {
                                    day: '2-digit',
                                    month: 'long',
                                    year: 'numeric'
                                }) }}
                            </td>
                            <td class="border-b px-4 py-2 text-center space-x-2">
                                <a :href="`/${media.path}`" target="_blank"
                                    class="text-indigo-600 hover:text-indigo-800">
                                    <Eye class="inline h-5 w-5" />
                                </a>
                                <a :href="route('medias-download', media.id)"
                                    class="text-green-600 hover:text-green-800">
                                    <Download class="inline h-5 w-5" />
                                </a>
                                <button @click="deleteMedia(media.id)" class="text-red-600 hover:text-red-800">
                                    <Trash2 class="inline h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="medias.length === 0">
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">No media files found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="page.props.medias?.total && links.length" class="mt-6 flex justify-center space-x-2">
                <template v-for="link in links" :key="link.label">
                    <component :is="link.url ? 'a' : 'span'" v-html="link.label" :href="link.url"
                        class="rounded border px-4 py-2 text-sm" :class="{
                            'bg-indigo-600 text-white': link.active,
                            'cursor-not-allowed text-gray-400': !link.url,
                            'hover:bg-gray-200 dark:hover:bg-black': link.url && !link.active,
                        }" />
                </template>
            </div>

            <!-- Upload Modal -->
            <div v-if="modalVisible" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div class="w-full max-w-md rounded bg-white p-6 shadow-lg dark:bg-black border">
                    <h2 class="text-lg font-bold mb-4 text-gray-800 dark:text-white">Upload Media</h2>

                    <div class="space-y-4">
                        <!-- File Name Input -->
                        <input type="text" v-model="newForm.name" placeholder="File name"
                            class="w-full rounded border px-3 py-2 dark:bg-black dark:text-white" />

                        <!-- Drag & Drop Upload Area -->
                        <div class="w-full rounded border-2 border-dashed px-4 py-8 text-center transition-all cursor-pointer"
                            :class="[
                                dragOver ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'
                            ]" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
                            @drop.prevent="handleDrop" @click="fileInput?.click()">
                            <input type="file" ref="fileInput" class="hidden" @change="handleFileChange" />
                            <p class="text-sm text-gray-500 dark:text-gray-300">
                                Drag & drop your file here, or <span
                                    class="text-blue-600 dark:text-blue-400 underline">click to
                                    upload</span>
                            </p>
                            <p class="mt-2 text-xs text-gray-400">Only one file (any type) is allowed</p>
                        </div>

                        <!-- Show selected file -->
                        <div v-if="newForm.file" class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                            {{ newForm.file.name }} — {{ (newForm.file.size / 1024 / 1024).toFixed(2) }} MB
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end gap-2 pt-4">
                            <button @click="modalVisible = false" class="px-4 py-2 rounded border text-white bg-red-600 hover:bg-red-700">
                                Cancel
                            </button>
                            <button @click="uploadFile" :disabled="uploading"
                                class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 flex items-center justify-center gap-2">
                                <svg v-if="uploading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                </svg>
                                <span>{{ uploading ? 'Uploading...' : 'Upload' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>