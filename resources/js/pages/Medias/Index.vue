<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Eye, Trash2, Upload, Download, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, computed, watch } from 'vue';

// Create FilePond component
import vueFilePond from 'vue-filepond';

// FilePond styles
import 'filepond/dist/filepond.min.css';

// Create FilePond component
const FilePond = vueFilePond();

const page = usePage();
const medias = computed(() => page.props.medias?.data || []);
const links = computed(() => page.props.medias?.links || []);
const search = ref(page.props.search ?? '');
const modalVisible = ref(false);
const uploading = ref(false);
const newForm = ref({ name: '', file: null });
const filePondFiles = ref([]);
const selectedIds = ref<number[]>([]);
const selectAllChecked = ref(false);
const showAll = ref(false);

watch(search, (value) => {
    router.get(route('medias'), { search: value }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
});

const openUploadModal = () => {
    newForm.value = { name: '', file: null };
    filePondFiles.value = [];
    modalVisible.value = true;
};

const handleFilePondUpdate = (files: any[]) => {
    filePondFiles.value = files;
    if (files.length > 0 && files[0].file) {
        newForm.value.file = files[0].file;
    } else {
        newForm.value.file = null;
    }
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

const toggleSelectAllPage = () => {
    if (selectAllChecked.value) {
        selectedIds.value = medias.value.map((m: any) => m.id);
    } else {
        selectedIds.value = [];
    }
};

watch(medias, () => {
    if (selectAllChecked.value) {
        selectedIds.value = medias.value.map((m: any) => m.id);
    }
});

const bulkDelete = async () => {
    if (!selectedIds.value.length) {
        Swal.fire('No selection', 'Please select one or more media files.', 'info');
        return;
    }

    const result = await Swal.fire({
        title: 'Delete selected files?',
        text: `This will permanently delete ${selectedIds.value.length} file(s).`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete',
    });

    if (result.isConfirmed) {
        router.post(route('medias-bulk-delete'), { ids: selectedIds.value }, {
            preserveState: true,
            onSuccess: () => {
                Swal.fire('Deleted', `${selectedIds.value.length} file(s) deleted.`, 'success');
                selectedIds.value = [];
                selectAllChecked.value = false;
                router.reload();
            },
            onError: () => {
                Swal.fire('Error', 'Bulk delete failed.', 'error');
            }
        });
    }
};

const toggleShowAll = () => {
    showAll.value = !showAll.value;
    if (showAll.value) {
        router.get(route('medias'), { per_page: 'all', search: search.value }, {
            preserveState: true,
            replace: true,
        });
    } else {
        router.get(route('medias'), { search: search.value }, {
            preserveState: true,
            replace: true,
        });
    }
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

// Pagination functions
const changePage = (url: string) => {
    if (url) {
        router.get(url, { search: search.value }, {
            preserveScroll: true,
            preserveState: true,
        });
    }
};



const getFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<template>

    <Head title="Media Library" />
    <AppLayout :breadcrumbs="[{ title: 'Media Library', href: '/medias' }]">
        <div
            class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-black dark:via-black dark:to-black">
            <div class="p-4 md:p-6">
                <!-- Search & Upload (aligned like FileTransfers) -->
                <div class="mb-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                    <div class="flex-1 items-center gap-3">
                        <input v-model="search" placeholder="Search..."
                            class="w-full max-w-xs rounded-2xl border px-4 py-2 dark:bg-neutral-800 dark:text-white" />

                        <!-- Show all toggle -->
                        <button @click="toggleShowAll"
                            class="ml-2 rounded-xl bg-gray-200 dark:bg-neutral-700 px-3 py-2 text-sm hover:bg-gray-300 dark:hover:bg-neutral-600 transition whitespace-nowrap">
                            <span v-if="!showAll">Show all</span>
                            <span v-else>Paged</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <div v-if="selectedIds.length > 0" class="flex items-center space-x-3">
                            <div class="text-sm">Selected: <strong>{{ selectedIds.length }}</strong></div>
                            <button @click="bulkDelete"
                                class="rounded-xl bg-red-600 px-3 py-2 text-white hover:bg-red-700">Delete
                                Selected</button>
                        </div>

                        <button @click="openUploadModal"
                            class="rounded-xl bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 flex items-center justify-center whitespace-nowrap group">
                            <Upload class="mr-2 h-5 w-5 group-hover:animate-bounce transition-transform duration-200" />
                            Upload
                        </button>
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="hidden lg:block rounded-2xl overflow-x-auto shadow">
                    <table class="w-full rounded bg-white dark:bg-neutral-800 dark:border border">
                        <thead class="bg-gray-100 text-gray-700 dark:bg-neutral-900 dark:text-gray-300">
                            <tr class="bg-gray-100 dark:bg-neutral-900 uppercase text-sm">
                                <th class="border-b px-4 py-2 text-center">
                                    <input type="checkbox" class="form-checkbox" v-model="selectAllChecked"
                                        @change="toggleSelectAllPage" />
                                </th>
                                <th class="border-b px-4 py-2 text-center">#</th>
                                <th class="border-b px-4 py-2 text-center">Name</th>
                                <th class="border-b px-4 py-2 text-center">Uploader</th>
                                <th class="border-b px-4 py-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(media, index) in medias" :key="media.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800 border-b">
                                <td class="border-b px-4 py-2 text-center">
                                    <input type="checkbox" class="form-checkbox" :value="media.id"
                                        v-model="selectedIds" />
                                </td>
                                <td class="border-b px-4 py-2 text-center">{{ index + 1 }}</td>
                                <td class="border-b px-4 py-2 text-center font-medium">{{ media.name }}</td>
                                <td class="border-b px-4 py-2 text-center">
                                    <div>{{ media.uploader?.name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ new Date(media.created_at).toLocaleDateString('en-GB', {
                                            day: '2-digit',
                                            month: 'short',
                                            year: 'numeric'
                                        }) }}
                                    </div>
                                </td>
                                <td class="border-b px-4 py-2 text-center space-x-2">
                                    <a :href="`/${media.path}`" target="_blank"
                                        class="text-indigo-600 hover:text-indigo-800 p-1" aria-label="View Media">
                                        <Eye class="inline h-5 w-5" />
                                    </a>
                                    <a :href="route('medias-download', media.id)"
                                        class="text-green-600 hover:text-green-800 p-1" aria-label="Download Media">
                                        <Download class="inline h-5 w-5" />
                                    </a>
                                    <button @click="deleteMedia(media.id)" class="text-red-600 hover:text-red-800 p-1"
                                        aria-label="Delete Media">
                                        <Trash2 class="inline h-5 w-5" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="medias.length === 0">
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No media
                                    files found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile/Tablet Cards -->
                <div class="lg:hidden space-y-4">
                    <div v-for="(media, index) in medias" :key="media.id"
                        class="bg-white dark:bg-neutral-800 rounded-2xl shadow border border-gray-200 dark:border-neutral-700 p-4">

                        <!-- Header: Number + Name -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="mr-3 flex items-start">
                                <input type="checkbox" class="form-checkbox mt-1 mr-3" :value="media.id"
                                    v-model="selectedIds" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    #{{ index + 1 }}
                                </div>
                                <h3 class="font-semibold text-lg break-words text-gray-900 dark:text-white">
                                    {{ media.name }}
                                </h3>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 ml-3">
                                <a :href="`/${media.path}`" target="_blank"
                                    class="text-indigo-600 hover:text-indigo-800 p-2 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                                    aria-label="View Media">
                                    <Eye class="h-5 w-5" />
                                </a>
                                <a :href="route('medias-download', media.id)"
                                    class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20"
                                    aria-label="Download Media">
                                    <Download class="h-5 w-5" />
                                </a>
                                <button @click="deleteMedia(media.id)"
                                    class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20"
                                    aria-label="Delete Media">
                                    <Trash2 class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <!-- File Info -->
                        <div class="mb-3">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                <span class="font-medium">File Type:</span> {{ media.type || 'Unknown' }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400" v-if="media.size">
                                <span class="font-medium">Size:</span> {{ getFileSize(media.size) }}
                            </div>
                        </div>

                        <!-- Uploader & Date -->
                        <div class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
                            <div>
                                <span class="font-medium">Uploader:</span> {{ media.uploader?.name ?? 'N/A' }}
                            </div>
                            <div>
                                {{ new Date(media.created_at).toLocaleDateString('en-GB', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric'
                                }) }}
                            </div>
                        </div>
                    </div>

                    <!-- No results card -->
                    <div v-if="medias.length === 0"
                        class="bg-white dark:bg-neutral-800 rounded-2xl shadow border border-gray-200 dark:border-neutral-700 p-8 text-center">
                        <div class="text-gray-500 dark:text-gray-400">No media files found.</div>
                    </div>
                </div>

                <!-- Pagination - Responsive -->
                <div v-if="medias.length && links.length" class="mt-6 p-4">

                    <!-- Mobile/Tablet pagination (simplified) -->
                    <div class="lg:hidden">
                        <!-- Results Info -->
                        <div class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3"
                            v-if="page.props.medias?.total">
                            Showing {{ page.props.medias?.from }} to {{ page.props.medias?.to }} of {{
                                page.props.medias?.total }}
                            files
                        </div>

                        <!-- Simple prev/next navigation -->
                        <div class="flex items-center justify-between gap-4">
                            <button @click="changePage(page.props.medias?.prev_page_url)"
                                :disabled="!page.props.medias?.prev_page_url"
                                class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2"
                                :class="page.props.medias?.prev_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                <ChevronLeft class="w-4 h-4" />
                                Previous
                            </button>

                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                Page {{ page.props.medias?.current_page }} of {{ page.props.medias?.last_page }}
                            </span>

                            <button @click="changePage(page.props.medias?.next_page_url)"
                                :disabled="!page.props.medias?.next_page_url"
                                class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2"
                                :class="page.props.medias?.next_page_url
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
                            Showing {{ page.props.medias?.from }} to {{ page.props.medias?.to }} of {{
                                page.props.medias?.total }}
                            files
                        </div>

                        <!-- Pagination Controls -->
                        <div class="flex items-center space-x-2">
                            <!-- Previous Button -->
                            <button @click="changePage(page.props.medias?.prev_page_url)"
                                :disabled="!page.props.medias?.prev_page_url"
                                class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center"
                                :class="page.props.medias?.prev_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                <ChevronLeft class="w-4 h-4 mr-1" />
                                Previous
                            </button>

                            <!-- Page Numbers -->
                            <div class="flex items-center space-x-1">
                                <template v-for="link in links.slice(1, -1)" :key="link.label">
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
                            <button @click="changePage(page.props.medias?.next_page_url)"
                                :disabled="!page.props.medias?.next_page_url"
                                class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center"
                                :class="page.props.medias?.next_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                Next
                                <ChevronRight class="w-4 h-4 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upload Modal -->
                <div v-if="modalVisible"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
                    @click.self="modalVisible = false">
                    <div
                        class="w-full max-w-md rounded-2xl bg-white p-6 shadow-lg dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700">
                        <h2 class="text-lg font-bold mb-4 text-gray-800 dark:text-white">Upload Media</h2>

                        <div class="space-y-4">
                            <!-- File Name Input -->
                            <input type="text" v-model="newForm.name" placeholder="File name"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-neutral-800 dark:text-white border-gray-300 dark:border-neutral-700" />

                            <!-- FilePond Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Upload
                                    File</label>
                                <FilePond name="file" :files="filePondFiles" @updatefiles="handleFilePondUpdate"
                                    :allowMultiple="false"
                                    :labelIdle="'Drag & Drop your file or <span class=\'filepond--label-action\'>Browse</span>'"
                                    :maxFiles="1" class="mt-1" />
                            </div>

                            <!-- Buttons -->
                            <div class="flex flex-col sm:flex-row justify-end gap-2 pt-4">
                                <button @click="modalVisible = false"
                                    class="px-4 py-2 rounded-xl border text-gray-700 bg-white hover:bg-gray-50 dark:bg-neutral-800 dark:text-gray-300 dark:hover:bg-gray-700 border-gray-300 dark:border-neutral-700">
                                    Cancel
                                </button>
                                <button @click="uploadFile" :disabled="uploading"
                                    class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
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
        </div>
    </AppLayout>
</template>