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
        Swal.fire({
            title: 'Error!',
            text: 'Name and file are required.',
            icon: 'error',
            customClass: { popup: 'rounded-lg' }
        });
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
            Swal.fire({
                title: 'Uploaded!',
                text: 'Media uploaded successfully.',
                icon: 'success',
                customClass: { popup: 'rounded-lg' }
            });
        },
        onError: () => {
            Swal.fire({
                title: 'Error!',
                text: 'Failed to upload.',
                icon: 'error',
                customClass: { popup: 'rounded-lg' }
            });
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
        Swal.fire({
            title: 'No selection',
            text: 'Please select one or more media files.',
            icon: 'info',
            customClass: { popup: 'rounded-lg' }
        });
        return;
    }

    const result = await Swal.fire({
        title: 'Delete selected files?',
        text: `This will permanently delete ${selectedIds.value.length} file(s).`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#D71921',
        cancelButtonColor: '#000000',
        confirmButtonText: 'Yes, delete',
        customClass: { popup: 'rounded-lg' }
    });

    if (result.isConfirmed) {
        router.post(route('medias-bulk-delete'), { ids: selectedIds.value }, {
            preserveState: true,
            onSuccess: () => {
                Swal.fire({
                    title: 'Deleted',
                    text: `${selectedIds.value.length} file(s) deleted.`,
                    icon: 'success',
                    customClass: { popup: 'rounded-lg' }
                });
                selectedIds.value = [];
                selectAllChecked.value = false;
                router.reload();
            },
            onError: () => {
                Swal.fire({
                    title: 'Error',
                    text: 'Bulk delete failed.',
                    icon: 'error',
                    customClass: { popup: 'rounded-lg' }
                });
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
        confirmButtonColor: '#D71921',
        cancelButtonColor: '#000000',
        confirmButtonText: 'Yes, delete it!',
        customClass: { popup: 'rounded-lg' }
    });

    if (result.isConfirmed) {
        router.delete(route('medias-delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire({
                title: 'Deleted!',
                text: 'File deleted.',
                icon: 'success',
                customClass: { popup: 'rounded-lg' }
            }),
            onError: () => Swal.fire({
                title: 'Error!',
                text: 'Delete failed.',
                icon: 'error',
                customClass: { popup: 'rounded-lg' }
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
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 md:p-6">
                <!-- Search & Upload (aligned like FileTransfers) -->
                <div class="mb-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                    <div class="flex-1 items-center gap-3">
                        <input v-model="search" placeholder="Search..."
                            class="w-full max-w-xs rounded-full border-2 border-[#CCCCCC] dark:border-[#333333] px-4 py-2 bg-white dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] placeholder-[#999999] focus:outline-none focus:border-black dark:focus:border-white transition-colors" />

                        <!-- Show all toggle -->
                        <button @click="toggleShowAll"
                            class="ml-2 rounded-full bg-white dark:bg-black border-2 border-black dark:border-white px-3 py-2 text-xs uppercase font-mono tracking-wider text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-colors whitespace-nowrap">
                            <span v-if="!showAll">Show all</span>
                            <span v-else>Paged</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <div v-if="selectedIds.length > 0" class="flex items-center space-x-3">
                            <div class="text-xs uppercase font-mono tracking-wider text-black dark:text-white">Selected:
                                <strong class="tabular-nums">{{ selectedIds.length }}</strong></div>
                            <button @click="bulkDelete"
                                class="rounded-full bg-white dark:bg-black border-2 border-[#D71921] px-3 py-2 text-xs uppercase font-mono tracking-wider text-[#D71921] hover:bg-[#D71921] hover:text-white transition-colors">Delete
                                Selected</button>
                        </div>

                        <button @click="openUploadModal"
                            class="rounded-full bg-black dark:bg-white border-2 border-black dark:border-white px-4 py-2 text-white dark:text-black hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white flex items-center justify-center whitespace-nowrap transition-colors duration-200">
                            <Upload :stroke-width="1.5" class="mr-2 h-4 w-4" />
                            <span class="text-xs uppercase font-mono tracking-wider">Upload</span>
                        </button>
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="hidden lg:block rounded-lg overflow-x-auto border-2 border-[#CCCCCC] dark:border-[#222222]">
                    <table class="w-full bg-white dark:bg-[#111111]">
                        <thead class="bg-[#F5F5F5] dark:bg-[#0A0A0A]">
                            <tr>
                                <th
                                    class="border-b-2 border-[#CCCCCC] dark:border-[#222222] px-4 py-2 text-center text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    <input type="checkbox" class="form-checkbox" v-model="selectAllChecked"
                                        @change="toggleSelectAllPage" />
                                </th>
                                <th
                                    class="border-b-2 border-[#CCCCCC] dark:border-[#222222] px-4 py-2 text-center text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    #</th>
                                <th
                                    class="border-b-2 border-[#CCCCCC] dark:border-[#222222] px-4 py-2 text-center text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    Name</th>
                                <th
                                    class="border-b-2 border-[#CCCCCC] dark:border-[#222222] px-4 py-2 text-center text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    Uploader</th>
                                <th
                                    class="border-b-2 border-[#CCCCCC] dark:border-[#222222] px-4 py-2 text-center text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(media, index) in medias" :key="media.id"
                                class="hover:bg-[#F5F5F5] dark:hover:bg-[#0A0A0A] border-b-2 border-[#E8E8E8] dark:border-[#222222] transition-colors duration-200">
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" class="form-checkbox" :value="media.id"
                                        v-model="selectedIds" />
                                </td>
                                <td class="px-4 py-2 text-center font-mono tabular-nums text-black dark:text-white">{{
                                    index + 1 }}</td>
                                <td
                                    class="px-4 py-2 text-center uppercase font-mono tracking-wider text-black dark:text-white">
                                    {{ media.name }}</td>
                                <td class="px-4 py-2 text-center">
                                    <div class="text-black dark:text-white">{{ media.uploader?.name ?? 'N/A' }}</div>
                                    <div class="text-xs font-mono text-[#666666] dark:text-[#999999]">
                                        {{ new Date(media.created_at).toLocaleDateString('en-GB', {
                                            day: '2-digit',
                                            month: 'short',
                                            year: 'numeric'
                                        }) }}
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-center space-x-2">
                                    <a :href="`/${media.path}`" target="_blank"
                                        class="inline-flex items-center justify-center p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-black dark:border-white rounded-full transition-colors duration-200"
                                        aria-label="View Media">
                                        <Eye :stroke-width="1.5" class="inline h-4 w-4" />
                                    </a>
                                    <a :href="route('medias-download', media.id)"
                                        class="inline-flex items-center justify-center p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-black dark:border-white rounded-full transition-colors duration-200"
                                        aria-label="Download Media">
                                        <Download :stroke-width="1.5" class="inline h-4 w-4" />
                                    </a>
                                    <button @click="deleteMedia(media.id)"
                                        class="inline-flex items-center justify-center p-2 text-[#D71921] hover:bg-[#D71921] hover:text-white border-2 border-[#D71921] rounded-full transition-colors duration-200"
                                        aria-label="Delete Media">
                                        <Trash2 :stroke-width="1.5" class="inline h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="medias.length === 0">
                                <td colspan="5" class="px-4 py-6 text-center text-[#666666] dark:text-[#999999]">No
                                    media
                                    files found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile/Tablet Cards -->
                <div class="lg:hidden space-y-4">
                    <div v-for="(media, index) in medias" :key="media.id"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#CCCCCC] dark:border-[#222222] p-4">

                        <!-- Header: Number + Name -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="mr-3 flex items-start">
                                <input type="checkbox" class="form-checkbox mt-1 mr-3" :value="media.id"
                                    v-model="selectedIds" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-mono text-[#666666] dark:text-[#999999] mb-1">
                                    #{{ index + 1 }}
                                </div>
                                <h3
                                    class="text-sm uppercase font-mono tracking-wider break-words text-black dark:text-white">
                                    {{ media.name }}
                                </h3>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 ml-3">
                                <a :href="`/${media.path}`" target="_blank"
                                    class="p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-black dark:border-white rounded-full transition-colors duration-200"
                                    aria-label="View Media">
                                    <Eye :stroke-width="1.5" class="h-4 w-4" />
                                </a>
                                <a :href="route('medias-download', media.id)"
                                    class="p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-black dark:border-white rounded-full transition-colors duration-200"
                                    aria-label="Download Media">
                                    <Download :stroke-width="1.5" class="h-4 w-4" />
                                </a>
                                <button @click="deleteMedia(media.id)"
                                    class="p-2 text-[#D71921] hover:bg-[#D71921] hover:text-white border-2 border-[#D71921] rounded-full transition-colors duration-200"
                                    aria-label="Delete Media">
                                    <Trash2 :stroke-width="1.5" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- File Info -->
                        <div class="mb-3">
                            <div class="text-xs font-mono text-[#666666] dark:text-[#999999] mb-1">
                                <span class="uppercase tracking-wider text-black dark:text-white">File Type:</span> {{
                                media.type || 'Unknown' }}
                            </div>
                            <div class="text-xs font-mono text-[#666666] dark:text-[#999999]" v-if="media.size">
                                <span class="uppercase tracking-wider text-black dark:text-white">Size:</span> {{
                                getFileSize(media.size) }}
                            </div>
                        </div>

                        <!-- Uploader & Date -->
                        <div
                            class="flex justify-between items-center text-xs font-mono text-[#666666] dark:text-[#999999]">
                            <div>
                                <span class="uppercase tracking-wider text-black dark:text-white">Uploader:</span> {{
                                media.uploader?.name ?? 'N/A' }}
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
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#CCCCCC] dark:border-[#222222] p-8 text-center">
                        <div class="text-[#666666] dark:text-[#999999]">No media files found.</div>
                    </div>
                </div>

                <!-- Pagination - Responsive -->
                <div v-if="medias.length && links.length" class="mt-6 p-4">

                    <!-- Mobile/Tablet pagination (simplified) -->
                    <div class="lg:hidden">
                        <!-- Results Info -->
                        <div class="text-xs font-mono text-[#666666] dark:text-[#999999] text-center mb-3"
                            v-if="page.props.medias?.total">
                            Showing {{ page.props.medias?.from }} to {{ page.props.medias?.to }} of {{
                                page.props.medias?.total }}
                            files
                        </div>

                        <!-- Simple prev/next navigation -->
                        <div class="flex items-center justify-between gap-4">
                            <button @click="changePage(page.props.medias?.prev_page_url)"
                                :disabled="!page.props.medias?.prev_page_url"
                                class="px-4 py-2 text-xs uppercase font-mono tracking-wider rounded-full transition-colors duration-200 flex items-center gap-2 border-2"
                                :class="page.props.medias?.prev_page_url
                                    ? 'text-black dark:text-white border-black dark:border-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black'
                                    : 'text-[#CCCCCC] cursor-not-allowed border-[#CCCCCC] dark:border-[#333333]'">
                                <ChevronLeft :stroke-width="1.5" class="w-4 h-4" />
                                Previous
                            </button>

                            <span class="text-xs font-mono tabular-nums text-[#666666] dark:text-[#999999]">
                                Page {{ page.props.medias?.current_page }} of {{ page.props.medias?.last_page }}
                            </span>

                            <button @click="changePage(page.props.medias?.next_page_url)"
                                :disabled="!page.props.medias?.next_page_url"
                                class="px-4 py-2 text-xs uppercase font-mono tracking-wider rounded-full transition-colors duration-200 flex items-center gap-2 border-2"
                                :class="page.props.medias?.next_page_url
                                    ? 'text-black dark:text-white border-black dark:border-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black'
                                    : 'text-[#CCCCCC] cursor-not-allowed border-[#CCCCCC] dark:border-[#333333]'">
                                Next
                                <ChevronRight :stroke-width="1.5" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Desktop pagination (full features) -->
                    <div class="hidden lg:flex items-center justify-between">
                        <!-- Results Info -->
                        <div class="text-xs font-mono text-[#666666] dark:text-[#999999]">
                            Showing {{ page.props.medias?.from }} to {{ page.props.medias?.to }} of {{
                                page.props.medias?.total }}
                            files
                        </div>

                        <!-- Pagination Controls -->
                        <div class="flex items-center space-x-2">
                            <!-- Previous Button -->
                            <button @click="changePage(page.props.medias?.prev_page_url)"
                                :disabled="!page.props.medias?.prev_page_url"
                                class="px-3 py-2 text-xs uppercase font-mono tracking-wider rounded-full transition-colors duration-200 flex items-center border-2"
                                :class="page.props.medias?.prev_page_url
                                    ? 'text-black dark:text-white border-black dark:border-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black'
                                    : 'text-[#CCCCCC] cursor-not-allowed border-[#CCCCCC] dark:border-[#333333]'">
                                <ChevronLeft :stroke-width="1.5" class="w-4 h-4 mr-1" />
                                Previous
                            </button>

                            <!-- Page Numbers -->
                            <div class="flex items-center space-x-1">
                                <template v-for="link in links.slice(1, -1)" :key="link.label">
                                    <button v-if="link.url" @click="changePage(link.url)"
                                        class="px-3 py-2 text-xs font-mono tabular-nums rounded-full transition-colors duration-200 border-2"
                                        :class="link.active
                                            ? 'bg-black dark:bg-white text-white dark:text-black border-black dark:border-white'
                                            : 'text-black dark:text-white border-[#CCCCCC] dark:border-[#333333] hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black hover:border-black dark:hover:border-white'"
                                        v-html="link.label" />
                                    <span v-else class="px-3 py-2 text-xs font-mono text-[#CCCCCC] cursor-not-allowed"
                                        v-html="link.label" />
                                </template>
                            </div>

                            <!-- Next Button -->
                            <button @click="changePage(page.props.medias?.next_page_url)"
                                :disabled="!page.props.medias?.next_page_url"
                                class="px-3 py-2 text-xs uppercase font-mono tracking-wider rounded-full transition-colors duration-200 flex items-center border-2"
                                :class="page.props.medias?.next_page_url
                                    ? 'text-black dark:text-white border-black dark:border-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black'
                                    : 'text-[#CCCCCC] cursor-not-allowed border-[#CCCCCC] dark:border-[#333333]'">
                                Next
                                <ChevronRight :stroke-width="1.5" class="w-4 h-4 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upload Modal -->
                <div v-if="modalVisible"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
                    @click.self="modalVisible = false">
                    <div
                        class="w-full max-w-md rounded-lg bg-white p-6 dark:bg-[#111111] border-2 border-[#CCCCCC] dark:border-[#222222]">
                        <h2 class="text-xs uppercase font-mono tracking-widest mb-4 text-black dark:text-white">Upload
                            Media</h2>

                        <div class="space-y-4">
                            <!-- File Name Input -->
                            <input type="text" v-model="newForm.name" placeholder="File name"
                                class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] bg-white dark:bg-black text-black dark:text-white px-3 py-2 focus:outline-none focus:border-black dark:focus:border-white transition-colors" />

                            <!-- FilePond Upload -->
                            <div>
                                <label
                                    class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white mb-2">Upload
                                    File</label>
                                <FilePond name="file" :files="filePondFiles" @updatefiles="handleFilePondUpdate"
                                    :allowMultiple="false"
                                    :labelIdle="'Drag & Drop your file or <span class=\'filepond--label-action\'>Browse</span>'"
                                    :maxFiles="1" class="mt-1" />
                            </div>

                            <!-- Buttons -->
                            <div class="flex flex-col sm:flex-row justify-end gap-2 pt-4">
                                <button @click="modalVisible = false"
                                    class="px-4 py-2 rounded-full border-2 border-[#D71921] text-[#D71921] bg-white dark:bg-black hover:bg-[#D71921] hover:text-white transition-colors text-xs uppercase font-mono tracking-wider">
                                    Cancel
                                </button>
                                <button @click="uploadFile" :disabled="uploading"
                                    class="px-4 py-2 rounded-full bg-black dark:bg-white border-2 border-black dark:border-white text-white dark:text-black hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-colors text-xs uppercase font-mono tracking-wider">
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