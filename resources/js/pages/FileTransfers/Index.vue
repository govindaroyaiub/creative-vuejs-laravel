<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Pencil, Trash2, Eye, ChevronLeft, ChevronRight, X, Download } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';

// Create FilePond component
import vueFilePond from 'vue-filepond';

// FilePond styles
import 'filepond/dist/filepond.min.css';

// Create FilePond component
const FilePond = vueFilePond();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'File Transfers', href: '/file-transfers' }];

const page = usePage();
const search = ref(page.props.search ?? '');
const fileTransfers = computed(() => page.props.fileTransfers ?? { data: [], links: [] });

// Modal state
const showModal = ref(false);
const showEditModal = ref(false);
const form = ref({
    name: '',
    client: '',
    files: [] as File[],
});
const editForm = ref({
    id: '',
    name: '',
    client: '',
    files: [] as File[],
    file_paths: [] as string[],
});
const fileSize = ref('0.00');
const editFileSize = ref('0.00');
const filePondFiles = ref([]);
const editFilePondFiles = ref([]);

watch(search, (value) => {
    router.get(route('file-transfers'), { search: value }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
});

const deleteFileTransfer = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        router.delete(route('file-transfers-delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire({
                title: 'Deleted!',
                text: 'File transfer deleted successfully.',
                icon: 'success',
                position: 'top-end',
                showConfirmButton: false,
                timer: 1000,
                timerProgressBar: true,
                toast: true
            }),
            onError: () => Swal.fire({
                title: 'Error!',
                text: 'Failed to delete file transfer.',
                icon: 'error',
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                toast: true
            }),
        });
    }
};

// Pagination functions
const changePage = (url: string) => {
    if (url) {
        router.get(url, { search: search.value }, {
            preserveScroll: true,
            preserveState: true,
        });
    }
};

// Modal functions
const openModal = () => {
    form.value = {
        name: '',
        client: '',
        files: [],
    };
    fileSize.value = '0.00';
    filePondFiles.value = [];
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.value = {
        name: '',
        client: '',
        files: [],
    };
    fileSize.value = '0.00';
    filePondFiles.value = [];
};

const handleFilePondUpdate = (files: any[]) => {
    filePondFiles.value = files;
    if (files.length > 0) {
        const validFiles = files
            .map(f => f.file)
            .filter(file => file && file.name.toLowerCase().endsWith('.zip'));

        form.value.files = validFiles;
        const totalBytes = validFiles.reduce((acc, file) => acc + file.size, 0);
        fileSize.value = (totalBytes / (1024 * 1024)).toFixed(2);

        // Show warning if some files were filtered out
        if (validFiles.length !== files.length) {
            console.warn('Some files were filtered out. Only ZIP files are allowed.');
        }
    } else {
        form.value.files = [];
        fileSize.value = '0.00';
    }
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
        onSuccess: () => {
            closeModal();
            Swal.fire({
                title: 'Success!',
                text: 'File transfer created successfully.',
                icon: 'success',
                position: 'top-end',
                showConfirmButton: false,
                timer: 1000,
                timerProgressBar: true,
                toast: true
            });
        },
        onError: () => {
            Swal.fire({
                title: 'Error!',
                text: 'Failed to create file transfer.',
                icon: 'error',
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                toast: true
            });
        }
    });
};

// Edit Modal functions
const openEditModal = (transfer: any) => {
    // Now file_paths should always be an array from the backend
    const filePaths = transfer.file_paths || [];

    editForm.value = {
        id: transfer.id,
        name: transfer.name,
        client: transfer.client,
        files: [],
        file_paths: filePaths,
    };
    editFileSize.value = '0.00';
    editFilePondFiles.value = [];
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.value = {
        id: '',
        name: '',
        client: '',
        files: [],
        file_paths: [],
    };
    editFileSize.value = '0.00';
    editFilePondFiles.value = [];
};

const handleEditFilePondUpdate = (files: any[]) => {
    editFilePondFiles.value = files;
    if (files.length > 0) {
        const validFiles = files
            .map(f => f.file)
            .filter(file => file && file.name.toLowerCase().endsWith('.zip'));

        editForm.value.files = validFiles;
        const totalBytes = validFiles.reduce((acc, file) => acc + file.size, 0);
        editFileSize.value = (totalBytes / (1024 * 1024)).toFixed(2);

        // Show warning if some files were filtered out
        if (validFiles.length !== files.length) {
            console.warn('Some files were filtered out. Only ZIP files are allowed.');
        }
    } else {
        editForm.value.files = [];
        editFileSize.value = '0.00';
    }
};

const handleEditSubmit = () => {
    const payload = new FormData();
    payload.append('name', editForm.value.name);
    payload.append('client', editForm.value.client);
    editForm.value.files.forEach((file) => {
        payload.append('file[]', file);
    });

    router.post(`/file-transfers-edit/${editForm.value.id}`, payload, {
        forceFormData: true,
        onSuccess: () => {
            closeEditModal();
            Swal.fire({
                title: 'Success!',
                text: 'File transfer updated successfully.',
                icon: 'success',
                position: 'top-end',
                showConfirmButton: false,
                timer: 1000,
                timerProgressBar: true,
                toast: true
            });
        },
        onError: () => {
            Swal.fire({
                title: 'Error!',
                text: 'Failed to update file transfer.',
                icon: 'error',
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                toast: true
            });
        }
    });
};
</script>

<template>

    <Head title="File Transfers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-6">
            <!-- Search & Add -->
            <div class="mb-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                <input v-model="search" placeholder="Search..."
                    class="w-full sm:max-w-xs rounded-2xl border px-4 py-2 dark:bg-neutral-800 dark:text-white" />
                <button @click="openModal"
                    class="rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700 flex items-center justify-center whitespace-nowrap group">
                    <CirclePlus class="mr-2 h-5 w-5 group-hover:rotate-90 transition-transform duration-200" />
                    Add Transfer
                </button>
            </div>

            <!-- Desktop Table -->
            <div class="hidden lg:block rounded-2xl overflow-x-auto shadow">
                <table class="w-full rounded bg-white dark:bg-neutral-800 dark:border border">
                    <thead class="bg-gray-100 text-gray-700 dark:bg-neutral-900 dark:text-gray-300">
                        <tr class="text-center text-sm uppercase">
                            <th class="px-4 py-2 border-b">#</th>
                            <th class="px-4 py-2 border-b">Name</th>
                            <th class="px-4 py-2 border-b">Client</th>
                            <th class="px-4 py-2 border-b">Uploader</th>
                            <th class="px-4 py-2 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(transfer, index) in fileTransfers.data" :key="transfer.id"
                            class="border-t text-center text-sm dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700">
                            <td class="px-4 py-2 border-b">{{ index + 1 }}</td>
                            <td class="px-4 py-2 border-b font-medium">{{ transfer.name }}</td>
                            <td class="px-4 py-2 border-b">{{ transfer.client }}</td>
                            <td class="px-4 py-2 border-b">
                                <div>{{ transfer.user.name }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ new Date(transfer.created_at).toLocaleDateString('en-GB', {
                                        day: '2-digit',
                                        month: 'short',
                                        year: 'numeric',
                                    }) }}
                                </div>
                            </td>
                            <td class="space-x-2 px-4 py-2 border-b">
                                <a :href="`/file-transfers-view/${transfer.slug}`" target="_blank"
                                    class="text-green-600 hover:text-green-800 p-1" aria-label="View Transfer">
                                    <Eye class="inline h-5 w-5" />
                                </a>
                                <button @click="openEditModal(transfer)" class="text-blue-600 hover:text-blue-800 p-1"
                                    aria-label="Edit Transfer">
                                    <Pencil class="inline h-5 w-5" />
                                </button>
                                <button @click="deleteFileTransfer(transfer.id)"
                                    class="text-red-600 hover:text-red-800 p-1" aria-label="Delete Transfer">
                                    <Trash2 class="inline h-5 w-5" />
                                </button>
                            </td>
                        </tr>

                        <tr v-if="fileTransfers.data.length === 0">
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No file
                                transfers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile/Tablet Cards -->
            <div class="lg:hidden space-y-4">
                <div v-for="(transfer, index) in fileTransfers.data" :key="transfer.id"
                    class="bg-white dark:bg-neutral-800 rounded-2xl shadow border border-gray-200 dark:border-neutral-700 p-4">

                    <!-- Header: Number + Name -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                #{{ index + 1 }}
                            </div>
                            <h3 class="font-semibold text-lg break-words text-gray-900 dark:text-white">
                                {{ transfer.name }}
                            </h3>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 ml-3">
                            <a :href="`/file-transfers-view/${transfer.slug}`" target="_blank"
                                class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20"
                                aria-label="View Transfer">
                                <Eye class="h-5 w-5" />
                            </a>
                            <button @click="openEditModal(transfer)"
                                class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                aria-label="Edit Transfer">
                                <Pencil class="h-5 w-5" />
                            </button>
                            <button @click="deleteFileTransfer(transfer.id)"
                                class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20"
                                aria-label="Delete Transfer">
                                <Trash2 class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Client -->
                    <div class="mb-3">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Client:</span> {{ transfer.client || 'No client' }}
                        </div>
                    </div>

                    <!-- Uploader & Date -->
                    <div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
                        <div>
                            <span class="font-medium">Uploader:</span> {{ transfer.user.name }}
                        </div>
                        <div>
                            {{ new Date(transfer.created_at).toLocaleDateString('en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                            }) }}
                        </div>
                    </div>
                </div>

                <!-- No results card -->
                <div v-if="fileTransfers.data.length === 0"
                    class="bg-white dark:bg-neutral-800 rounded-2xl shadow border border-gray-200 dark:border-neutral-700 p-8 text-center">
                    <div class="text-gray-500 dark:text-gray-400">No file transfers found.</div>
                </div>
            </div>

            <!-- Pagination - Responsive -->
            <div v-if="fileTransfers.data.length && fileTransfers.links?.length" class="mt-6 p-4">

                <!-- Mobile/Tablet pagination (simplified) -->
                <div class="lg:hidden">
                    <!-- Results Info -->
                    <div class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">
                        Showing {{ fileTransfers.from }} to {{ fileTransfers.to }} of {{ fileTransfers.total }}
                        transfers
                    </div>

                    <!-- Simple prev/next navigation -->
                    <div class="flex items-center justify-between gap-4">
                        <button @click="changePage(fileTransfers.prev_page_url)"
                            :disabled="!fileTransfers.prev_page_url"
                            class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2"
                            :class="fileTransfers.prev_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                            <ChevronLeft class="w-4 h-4" />
                            Previous
                        </button>

                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Page {{ fileTransfers.current_page }} of {{ fileTransfers.last_page }}
                        </span>

                        <button @click="changePage(fileTransfers.next_page_url)"
                            :disabled="!fileTransfers.next_page_url"
                            class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2"
                            :class="fileTransfers.next_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                            Next
                            <ChevronRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Desktop pagination (full features) -->
                <div class="hidden lg:flex items-center justify-between">
                    <!-- Results Info -->
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Showing {{ fileTransfers.from }} to {{ fileTransfers.to }} of {{ fileTransfers.total }}
                        transfers
                    </div>

                    <!-- Pagination Controls -->
                    <div class="flex items-center space-x-2">
                        <!-- Previous Button -->
                        <button @click="changePage(fileTransfers.prev_page_url)"
                            :disabled="!fileTransfers.prev_page_url"
                            class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center" :class="fileTransfers.prev_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                            <ChevronLeft class="w-4 h-4 mr-1" />
                            Previous
                        </button>

                        <!-- Page Numbers -->
                        <div class="flex items-center space-x-1">
                            <template v-for="link in fileTransfers.links.slice(1, -1)" :key="link.label">
                                <button v-if="link.url" @click="changePage(link.url)"
                                    class="px-3 py-2 text-sm rounded-lg transition-all duration-200"
                                    :class="link.active
                                        ? 'bg-blue-600 text-white'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'"
                                    v-html="link.label" />
                                <span v-else class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed"
                                    v-html="link.label" />
                            </template>
                        </div>

                        <!-- Next Button -->
                        <button @click="changePage(fileTransfers.next_page_url)"
                            :disabled="!fileTransfers.next_page_url"
                            class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center" :class="fileTransfers.next_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                            Next
                            <ChevronRight class="w-4 h-4 ml-1" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Transfer Modal -->
        <div v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4"
            @click.self="closeModal">
            <div
                class="bg-white dark:bg-neutral-800 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl border border-gray-200 dark:border-neutral-700">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-neutral-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Add File Transfer</h2>
                    <button @click="closeModal"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg transition-all duration-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="name"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name</label>
                            <input id="name" v-model="form.name" required type="text"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white border-gray-300 dark:border-neutral-700" />
                        </div>

                        <!-- Client -->
                        <div>
                            <label for="client"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Client</label>
                            <input id="client" v-model="form.client" required type="text"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white border-gray-300 dark:border-neutral-700" />
                        </div>

                        <!-- FilePond Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Upload ZIP
                                Files</label>
                            <FilePond name="files" :files="filePondFiles" @updatefiles="handleFilePondUpdate"
                                :allowMultiple="true"
                                :labelIdle="'Drag & Drop your ZIP files or <span class=\'filepond--label-action\'>Browse</span>'"
                                :maxFiles="10" class="mt-1" />

                            <!-- File list -->
                            <div v-if="form.files.length"
                                class="mt-3 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                                <div v-for="(file, idx) in form.files" :key="idx"
                                    class="flex justify-between items-center p-2 bg-gray-50 dark:bg-neutral-700 rounded-lg">
                                    <span>{{ file.name }}</span>
                                    <span class="text-xs text-gray-500">{{ (file.size / 1024 / 1024).toFixed(2) }}
                                        MB</span>
                                </div>
                                <div class="font-medium">Total size: {{ fileSize }} MB</div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex space-x-4 pt-4">
                            <button type="button" @click="closeModal"
                                class="flex-1 rounded-xl bg-gray-600 px-6 py-3 text-white shadow hover:bg-gray-700 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="!form.name || !form.client || !form.files.length"
                                class="flex-1 rounded-xl bg-green-600 px-6 py-3 text-white shadow hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Transfer Modal -->
        <div v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4"
            @click.self="closeEditModal">
            <div
                class="bg-white dark:bg-neutral-800 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl border border-gray-200 dark:border-neutral-700">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-neutral-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Edit File Transfer</h2>
                    <button @click="closeEditModal"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg transition-all duration-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                    <form @submit.prevent="handleEditSubmit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="editName"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name</label>
                            <input id="editName" v-model="editForm.name" required type="text"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white border-gray-300 dark:border-neutral-700" />
                        </div>

                        <!-- Client -->
                        <div>
                            <label for="editClient"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Client</label>
                            <input id="editClient" v-model="editForm.client" required type="text"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white border-gray-300 dark:border-neutral-700" />
                        </div>

                        <!-- Existing Files -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Existing Files
                                <span v-if="editForm.file_paths && editForm.file_paths.length > 0"
                                    class="text-xs font-normal text-gray-500">({{ editForm.file_paths.length }}
                                    files)</span>
                            </label>

                            <div v-if="editForm.file_paths && editForm.file_paths.length > 0"
                                class="border border-gray-300 dark:border-neutral-700 rounded-2xl p-4 bg-gray-50 dark:bg-neutral-900 space-y-2">
                                <div v-for="(file, index) in editForm.file_paths" :key="index"
                                    class="flex items-center justify-between p-3 bg-white dark:bg-neutral-800 rounded-lg border border-gray-100 dark:border-neutral-600">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10">
                                                </path>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{
                                            file
                                            }}</span>
                                    </div>
                                    <a :href="`/Transfer Files/${file}`" download
                                        class="flex items-center space-x-1 px-3 py-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                                        <Download class="h-4 w-4" />
                                        <span class="text-xs">Download</span>
                                    </a>
                                </div>
                            </div>

                            <div v-else
                                class="border border-gray-300 dark:border-neutral-700 rounded-2xl p-6 bg-gray-50 dark:bg-neutral-900 text-center">
                                <div
                                    class="p-3 bg-gray-100 dark:bg-neutral-800 rounded-full w-12 h-12 mx-auto mb-3 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">No existing files found</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Upload new files below to add
                                    them to
                                    this transfer</p>
                            </div>
                        </div>

                        <!-- Upload New ZIP Files -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Upload New
                                ZIP
                                Files</label>
                            <FilePond name="files" :files="editFilePondFiles" @updatefiles="handleEditFilePondUpdate"
                                :allowMultiple="true"
                                :labelIdle="'Drag & Drop your ZIP files or <span class=\'filepond--label-action\'>Browse</span>'"
                                :maxFiles="10" class="mt-1" />

                            <!-- New File list -->
                            <div v-if="editForm.files.length"
                                class="mt-3 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                                <div v-for="(file, idx) in editForm.files" :key="idx"
                                    class="flex justify-between items-center p-2 bg-gray-50 dark:bg-neutral-700 rounded-lg">
                                    <span>{{ file.name }}</span>
                                    <span class="text-xs text-gray-500">{{ (file.size / 1024 / 1024).toFixed(2) }}
                                        MB</span>
                                </div>
                                <div class="font-medium">Total new files size: {{ editFileSize }} MB</div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex space-x-4 pt-4">
                            <button type="button" @click="closeEditModal"
                                class="flex-1 rounded-xl bg-gray-600 px-6 py-3 text-white shadow hover:bg-gray-700 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="!editForm.name || !editForm.client"
                                class="flex-1 rounded-xl bg-blue-600 px-6 py-3 text-white shadow hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>