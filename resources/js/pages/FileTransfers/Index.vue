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

const page = usePage<any>();
const search = ref<string>((page.props as any).search ?? '');
const fileTransfers = computed<any>(() => (page.props as any).fileTransfers ?? { data: [], links: [] });

// Bulk selection state
const selectedIds = ref<number[]>([]);
const selectAllChecked = ref(false);
const showAllOnPage = ref(false);

const selectedCount = computed(() => selectedIds.value.length);

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
const filePondFiles = ref<any[]>([]);
const editFilePondFiles = ref<any[]>([]);

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
        confirmButtonColor: '#D71921',
        cancelButtonColor: '#000000',
        confirmButtonText: 'Yes, delete it!',
        customClass: { popup: 'rounded-lg' }
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
                toast: true,
                customClass: { popup: 'rounded-lg' }
            }),
            onError: () => Swal.fire({
                title: 'Error!',
                text: 'Failed to delete file transfer.',
                icon: 'error',
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                toast: true,
                customClass: { popup: 'rounded-lg' }
            }),
        });
    }
};

const toggleRowSelection = (id: number, checked: boolean) => {
    if (checked) {
        if (!selectedIds.value.includes(id)) selectedIds.value.push(id);
    } else {
        selectedIds.value = selectedIds.value.filter(i => i !== id);
        selectAllChecked.value = false;
    }
}

const toggleSelectAllPage = () => {
    const deletableIds = (fileTransfers.value.data || []).filter((t: any) => !t.preview_id).map((t: any) => t.id);
    if (selectAllChecked.value) {
        // select all deletable on page
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...deletableIds]));
    } else {
        // remove deletable ids from selection
        selectedIds.value = selectedIds.value.filter(id => !deletableIds.includes(id));
    }
}

const toggleShowAll = () => {
    // Request backend with per_page=all (backend expected to handle 'all' or large number)
    showAllOnPage.value = !showAllOnPage.value;
    const params: any = { search: search.value };
    if (showAllOnPage.value) params.per_page = 'all';
    router.get(route('file-transfers'), params, { preserveState: true, preserveScroll: true, replace: true });
}

const bulkDelete = async () => {
    if (!selectedIds.value.length) return;

    const result = await Swal.fire({
        title: `Delete ${selectedIds.value.length} transfer(s)?`,
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#D71921',
        cancelButtonColor: '#000000',
        confirmButtonText: 'Yes, delete them!',
        customClass: { popup: 'rounded-lg' }
    });

    if (!result.isConfirmed) return;

    // Frontend-only: call backend route (implement backend later)
    router.post('/file-transfers/bulk-delete', { ids: selectedIds.value }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                title: 'Deleted!',
                text: 'Selected file transfers deleted successfully.',
                icon: 'success',
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
                toast: true,
                customClass: { popup: 'rounded-lg' }
            });
            // clear selection
            selectedIds.value = [];
            selectAllChecked.value = false;
        },
        onError: () => Swal.fire({ title: 'Error!', text: 'Failed to delete selected transfers.', icon: 'error', customClass: { popup: 'rounded-lg' } })
    });
}

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
                toast: true,
                customClass: { popup: 'rounded-lg' }
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
                toast: true,
                customClass: { popup: 'rounded-lg' }
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
                toast: true,
                customClass: { popup: 'rounded-lg' }
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
                toast: true,
                customClass: { popup: 'rounded-lg' }
            });
        }
    });
};
</script>

<template>

    <Head title="File Transfers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 md:p-6">
                <!-- Search & Add -->
                <div class="mb-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                    <div class="flex-1 items-center gap-3">
                        <input v-model="search" placeholder="Search..."
                            class="w-full max-w-xs rounded-full border-2 border-[#CCCCCC] dark:border-[#333333] px-4 py-2 bg-white dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] placeholder-[#999999] focus:outline-none focus:border-black dark:focus:border-white transition-colors" />

                        <!-- Show all toggle -->
                        <button @click="toggleShowAll"
                            class="ml-2 rounded-full bg-white dark:bg-black border-2 border-black dark:border-white px-3 py-2 text-xs font-mono tracking-wide text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-colors whitespace-nowrap">
                            <span v-if="!showAllOnPage">Show all</span>
                            <span v-else>Paged</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <div v-if="selectedCount > 0" class="flex items-center space-x-3">
                            <div class="text-xs font-mono tracking-wide text-black dark:text-white">Selected:
                                <strong class="tabular-nums">{{ selectedCount }}</strong>
                            </div>
                            <button @click="bulkDelete"
                                class="rounded-full bg-white dark:bg-black border-2 border-[#D71921] px-3 py-2 text-xs font-mono tracking-wide text-[#D71921] hover:bg-[#D71921] hover:text-white transition-colors">Delete
                                Selected</button>
                        </div>

                        <button @click="openModal"
                            class="rounded-full bg-black dark:bg-white border-2 border-black dark:border-white px-4 py-2 text-white dark:text-black hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white flex items-center justify-center whitespace-nowrap transition-colors duration-200">
                            <CirclePlus :stroke-width="1.5" class="mr-2 h-4 w-4" />
                            <span class="text-xs font-mono tracking-wide">Add Transfer</span>
                        </button>
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="hidden lg:block rounded-lg overflow-x-auto border-2 border-[#CCCCCC] dark:border-[#222222]">
                    <table class="w-full bg-white dark:bg-[#111111]">
                        <thead class="bg-[#F5F5F5] dark:bg-[#0A0A0A]">
                            <tr class="text-center">
                                <th
                                    class="px-4 py-2 border-b-2 border-[#CCCCCC] dark:border-[#222222] text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    <input type="checkbox" v-model="selectAllChecked" @change="toggleSelectAllPage"
                                        aria-label="Select all on page" />
                                </th>
                                <th
                                    class="px-4 py-2 border-b-2 border-[#CCCCCC] dark:border-[#222222] text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    #</th>
                                <th
                                    class="px-4 py-2 border-b-2 border-[#CCCCCC] dark:border-[#222222] text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    Name</th>
                                <th
                                    class="px-4 py-2 border-b-2 border-[#CCCCCC] dark:border-[#222222] text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    Client</th>
                                <th
                                    class="px-4 py-2 border-b-2 border-[#CCCCCC] dark:border-[#222222] text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    Uploader</th>
                                <th
                                    class="px-4 py-2 border-b-2 border-[#CCCCCC] dark:border-[#222222] text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    Preview</th>
                                <th
                                    class="px-4 py-2 border-b-2 border-[#CCCCCC] dark:border-[#222222] text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(transfer, index) in fileTransfers.data" :key="transfer.id"
                                class="text-center text-sm border-b-2 border-[#E8E8E8] dark:border-[#222222] hover:bg-[#F5F5F5] dark:hover:bg-[#0A0A0A] transition-colors duration-200">
                                <td class="px-4 py-2">
                                    <input type="checkbox" :disabled="!!transfer.preview_id"
                                        :checked="selectedIds.includes(transfer.id)"
                                        @change="$event && toggleRowSelection(transfer.id, ($event.target as HTMLInputElement).checked)"
                                        aria-label="Select transfer" />
                                </td>
                                <td class="px-4 py-2 font-mono tabular-nums text-black dark:text-white">{{ Number(index)
                                    + 1 }}</td>
                                <td class="px-4 py-2 font-mono tracking-wide text-black dark:text-white">{{
                                    transfer.name }}</td>
                                <td class="px-4 py-2 font-mono tracking-wide text-black dark:text-white">{{
                                    transfer.client }}</td>
                                <td class="px-4 py-2">
                                    <div class="text-black dark:text-white">{{ transfer.user.name }}</div>
                                    <div class="text-xs font-mono text-[#666666] dark:text-[#999999]">
                                        {{ new Date(transfer.created_at).toLocaleDateString('en-GB', {
                                            day: '2-digit',
                                            month: 'short',
                                            year: 'numeric',
                                        }) }}
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <div v-if="!transfer.preview_id">-</div>
                                    <div v-else>
                                        <a :href="`/previews/update/${transfer.preview_id}`" target="_blank"
                                            class="text-black dark:text-white underline hover:no-underline">Here</a>
                                    </div>
                                </td>
                                <td class="space-x-2 px-4 py-2">
                                    <a :href="`/file-transfers-view/${transfer.slug}`" target="_blank"
                                        class="inline-flex items-center justify-center p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-black dark:border-white rounded-full transition-colors duration-200"
                                        aria-label="View Transfer">
                                        <Eye :stroke-width="1.5" class="inline h-4 w-4" />
                                    </a>
                                    <button @click="openEditModal(transfer)"
                                        class="inline-flex items-center justify-center p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-black dark:border-white rounded-full transition-colors duration-200"
                                        aria-label="Edit Transfer">
                                        <Pencil :stroke-width="1.5" class="inline h-4 w-4" />
                                    </button>
                                    <button @click="!transfer.preview_id && deleteFileTransfer(transfer.id)"
                                        :disabled="!!transfer.preview_id"
                                        :class="transfer.preview_id
                                            ? 'inline-flex items-center justify-center p-2 text-[#CCCCCC] border-2 border-[#CCCCCC] dark:border-[#333333] rounded-full cursor-not-allowed'
                                            : 'inline-flex items-center justify-center p-2 text-[#D71921] hover:bg-[#D71921] hover:text-white border-2 border-[#D71921] rounded-full transition-colors duration-200'"
                                        aria-label="Delete Transfer"
                                        :title="transfer.preview_id ? 'Cannot delete — linked to a preview' : 'Delete Transfer'">
                                        <Trash2 :stroke-width="1.5" class="inline h-4 w-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="fileTransfers.data.length === 0">
                                <td colspan="7" class="px-4 py-6 text-center text-[#666666] dark:text-[#999999]">No file
                                    transfers found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile/Tablet Cards -->
                <div class="lg:hidden space-y-4">
                    <div v-for="(transfer, index) in fileTransfers.data" :key="transfer.id"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#CCCCCC] dark:border-[#222222] p-4">

                        <!-- Header: Number + Name -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-mono text-[#666666] dark:text-[#999999] mb-1">
                                    #{{ Number(index) + 1 }}
                                </div>
                                <h3
                                    class="text-sm font-mono tracking-wide break-words text-black dark:text-white">
                                    {{ transfer.name }}
                                </h3>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 ml-3">
                                <div class="flex items-center mr-2">
                                    <input type="checkbox" :disabled="!!transfer.preview_id"
                                        :checked="selectedIds.includes(transfer.id)"
                                        @change="$event && toggleRowSelection(transfer.id, ($event.target as HTMLInputElement).checked)"
                                        class="mr-2" />
                                </div>
                                <a :href="`/file-transfers-view/${transfer.slug}`" target="_blank"
                                    class="p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-black dark:border-white rounded-full transition-colors duration-200"
                                    aria-label="View Transfer">
                                    <Eye :stroke-width="1.5" class="h-4 w-4" />
                                </a>
                                <button @click="openEditModal(transfer)"
                                    class="p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-black dark:border-white rounded-full transition-colors duration-200"
                                    aria-label="Edit Transfer">
                                    <Pencil :stroke-width="1.5" class="h-4 w-4" />
                                </button>
                                <button @click="!transfer.preview_id && deleteFileTransfer(transfer.id)"
                                    :disabled="!!transfer.preview_id"
                                    :class="transfer.preview_id
                                        ? 'text-[#CCCCCC] border-2 border-[#CCCCCC] dark:border-[#333333] cursor-not-allowed p-2 rounded-full'
                                        : 'text-[#D71921] hover:bg-[#D71921] hover:text-white border-2 border-[#D71921] p-2 rounded-full transition-colors duration-200'"
                                    aria-label="Delete Transfer"
                                    :title="transfer.preview_id ? 'Cannot delete — linked to a preview' : 'Delete Transfer'">
                                    <Trash2 :stroke-width="1.5" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Client -->
                        <div class="mb-3">
                            <div class="text-xs font-mono text-[#666666] dark:text-[#999999]">
                                <span class="uppercase tracking-wider text-black dark:text-white">Client:</span> {{
                                    transfer.client || 'No client' }}
                            </div>
                        </div>

                        <!-- Uploader & Date -->
                        <div
                            class="flex justify-between items-center text-xs font-mono text-[#666666] dark:text-[#999999]">
                            <div>
                                <span class="uppercase tracking-wider text-black dark:text-white">Uploader:</span> {{
                                    transfer.user.name }}
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
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#CCCCCC] dark:border-[#222222] p-8 text-center">
                        <div class="text-[#666666] dark:text-[#999999]">No file transfers found.</div>
                    </div>
                </div>

                <!-- Pagination - Responsive -->
                <div v-if="fileTransfers.data.length && fileTransfers.links?.length" class="mt-6 p-4">

                    <!-- Mobile/Tablet pagination (simplified) -->
                    <div class="lg:hidden">
                        <!-- Results Info -->
                        <div class="text-xs font-mono text-[#666666] dark:text-[#999999] text-center mb-3">
                            Showing {{ fileTransfers.from }} to {{ fileTransfers.to }} of {{ fileTransfers.total }}
                            transfers
                        </div>

                        <!-- Simple prev/next navigation -->
                        <div class="flex items-center justify-between gap-4">
                            <button @click="changePage(fileTransfers.prev_page_url)"
                                :disabled="!fileTransfers.prev_page_url"
                                class="px-4 py-2 text-xs font-mono tracking-wide rounded-full transition-colors duration-200 flex items-center gap-2 border-2"
                                :class="fileTransfers.prev_page_url
                                    ? 'text-black dark:text-white border-black dark:border-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black'
                                    : 'text-[#CCCCCC] cursor-not-allowed border-[#CCCCCC] dark:border-[#333333]'">
                                <ChevronLeft :stroke-width="1.5" class="w-4 h-4" />
                                Previous
                            </button>

                            <span class="text-xs font-mono tabular-nums text-[#666666] dark:text-[#999999]">
                                Page {{ fileTransfers.current_page }} of {{ fileTransfers.last_page }}
                            </span>

                            <button @click="changePage(fileTransfers.next_page_url)"
                                :disabled="!fileTransfers.next_page_url"
                                class="px-4 py-2 text-xs font-mono tracking-wide rounded-full transition-colors duration-200 flex items-center gap-2 border-2"
                                :class="fileTransfers.next_page_url
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
                            Showing {{ fileTransfers.from }} to {{ fileTransfers.to }} of {{ fileTransfers.total }}
                            transfers
                        </div>

                        <!-- Pagination Controls -->
                        <div class="flex items-center space-x-2">
                            <!-- Previous Button -->
                            <button @click="changePage(fileTransfers.prev_page_url)"
                                :disabled="!fileTransfers.prev_page_url"
                                class="px-3 py-2 text-xs font-mono tracking-wide rounded-full transition-colors duration-200 flex items-center border-2"
                                :class="fileTransfers.prev_page_url
                                    ? 'text-black dark:text-white border-black dark:border-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black'
                                    : 'text-[#CCCCCC] cursor-not-allowed border-[#CCCCCC] dark:border-[#333333]'">
                                <ChevronLeft :stroke-width="1.5" class="w-4 h-4 mr-1" />
                                Previous
                            </button>

                            <!-- Page Numbers -->
                            <div class="flex items-center space-x-1">
                                <template v-for="link in fileTransfers.links.slice(1, -1)" :key="link.label">
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
                            <button @click="changePage(fileTransfers.next_page_url)"
                                :disabled="!fileTransfers.next_page_url"
                                class="px-3 py-2 text-xs font-mono tracking-wide rounded-full transition-colors duration-200 flex items-center border-2"
                                :class="fileTransfers.next_page_url
                                    ? 'text-black dark:text-white border-black dark:border-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black'
                                    : 'text-[#CCCCCC] cursor-not-allowed border-[#CCCCCC] dark:border-[#333333]'">
                                Next
                                <ChevronRight :stroke-width="1.5" class="w-4 h-4 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Transfer Modal -->
            <div v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                @click.self="closeModal">
                <div
                    class="bg-white dark:bg-[#111111] rounded-lg w-full max-w-2xl max-h-[90vh] overflow-hidden border-2 border-black dark:border-white">
                    <!-- Modal Header -->
                    <div
                        class="flex items-center justify-between p-4 border-b border-[#E8E8E8] dark:border-[#222222]">
                        <h2 class="text-base font-semibold font-mono text-black dark:text-white">Add file transfer</h2>
                        <button @click="closeModal"
                            class="p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white rounded-full transition-colors duration-200">
                            <X :stroke-width="1.5" class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                        <form @submit.prevent="handleSubmit" class="space-y-6">
                            <!-- Name -->
                            <div>
                                <label for="name"
                                    class="block text-xs font-mono tracking-wide text-black dark:text-white mb-1">Name</label>
                                <input id="name" v-model="form.name" required type="text"
                                    class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors" />
                            </div>

                            <!-- Client -->
                            <div>
                                <label for="client"
                                    class="block text-xs font-mono tracking-wide text-black dark:text-white mb-1">Client</label>
                                <input id="client" v-model="form.client" required type="text"
                                    class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors" />
                            </div>

                            <!-- FilePond Upload -->
                            <div>
                                <label
                                    class="block text-xs font-mono tracking-wide text-black dark:text-white mb-1">Upload
                                    ZIP
                                    Files</label>
                                <FilePond name="files" :files="filePondFiles" @updatefiles="handleFilePondUpdate"
                                    :allowMultiple="true"
                                    :labelIdle="'Drag & Drop your ZIP files or <span class=\'filepond--label-action\'>Browse</span>'"
                                    :maxFiles="10" class="mt-1" />

                                <!-- File list -->
                                <div v-if="form.files.length" class="mt-3 text-sm text-black dark:text-white space-y-1">
                                    <div v-for="(file, idx) in form.files" :key="idx"
                                        class="flex items-center justify-between gap-3 p-2 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                                        <span class="min-w-0 flex-1 truncate font-mono">{{ file.name }}</span>
                                        <span
                                            class="flex-shrink-0 text-xs font-mono text-[#666666] dark:text-[#999999]">{{
                                                (file.size
                                                    / 1024 /
                                            1024).toFixed(2) }}
                                            MB</span>
                                    </div>
                                    <div class="font-mono text-xs uppercase tracking-wider text-black dark:text-white">
                                        Total size:
                                        {{ fileSize }} MB</div>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="flex space-x-4 pt-4">
                                <button type="button" @click="closeModal"
                                    class="flex-1 rounded-full bg-white dark:bg-black border-2 border-[#D71921] px-6 py-3 text-[#D71921] hover:bg-[#D71921] hover:text-white transition-colors text-xs font-mono tracking-wide">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="!form.name || !form.client || !form.files.length"
                                    class="flex-1 rounded-full bg-black dark:bg-white border-2 border-black dark:border-white px-6 py-3 text-white dark:text-black hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-xs font-mono tracking-wide">
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Transfer Modal -->
            <div v-if="showEditModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                @click.self="closeEditModal">
                <div
                    class="bg-white dark:bg-[#111111] rounded-lg w-full max-w-2xl max-h-[90vh] overflow-hidden border-2 border-black dark:border-white">
                    <!-- Modal Header -->
                    <div
                        class="flex items-center justify-between p-4 border-b border-[#E8E8E8] dark:border-[#222222]">
                        <h2 class="text-base font-semibold font-mono text-black dark:text-white">Edit file transfer</h2>
                        <button @click="closeEditModal"
                            class="p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white rounded-full transition-colors duration-200">
                            <X :stroke-width="1.5" class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                        <form @submit.prevent="handleEditSubmit" class="space-y-6">
                            <!-- Name -->
                            <div>
                                <label for="editName"
                                    class="block text-xs font-mono tracking-wide text-black dark:text-white mb-1">Name</label>
                                <input id="editName" v-model="editForm.name" required type="text"
                                    class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors" />
                            </div>

                            <!-- Client -->
                            <div>
                                <label for="editClient"
                                    class="block text-xs font-mono tracking-wide text-black dark:text-white mb-1">Client</label>
                                <input id="editClient" v-model="editForm.client" required type="text"
                                    class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors" />
                            </div>

                            <!-- Existing Files -->
                            <div>
                                <label
                                    class="block text-xs font-mono tracking-wide text-black dark:text-white mb-2">
                                    Existing Files
                                    <span v-if="editForm.file_paths && editForm.file_paths.length > 0"
                                        class="text-xs font-normal text-[#666666] dark:text-[#999999]">({{
                                            editForm.file_paths.length }}
                                        files)</span>
                                </label>

                                <div v-if="editForm.file_paths && editForm.file_paths.length > 0"
                                    class="border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg p-4 bg-[#F5F5F5] dark:bg-[#0A0A0A] space-y-2">
                                    <div v-for="(file, index) in editForm.file_paths" :key="index"
                                        class="flex items-center justify-between gap-3 p-3 bg-white dark:bg-black rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                                        <div class="flex min-w-0 flex-1 items-center space-x-3">
                                            <div
                                                class="flex-shrink-0 p-2 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                                                <svg class="w-4 h-4 text-black dark:text-white" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10">
                                                    </path>
                                                </svg>
                                            </div>
                                            <span
                                                class="min-w-0 flex-1 truncate text-sm font-mono text-black dark:text-white">{{
                                                    file
                                                }}</span>
                                        </div>
                                        <a :href="`/Transfer Files/${file}`" download
                                            class="flex flex-shrink-0 items-center space-x-1 px-3 py-1 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white rounded-full transition-colors">
                                            <Download :stroke-width="1.5" class="h-4 w-4" />
                                            <span class="text-xs font-mono tracking-wide">Download</span>
                                        </a>
                                    </div>
                                </div>

                                <div v-else
                                    class="border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg p-6 bg-[#F5F5F5] dark:bg-[#0A0A0A] text-center">
                                    <div
                                        class="p-3 bg-white dark:bg-black border-2 border-[#E8E8E8] dark:border-[#222222] rounded-full w-12 h-12 mx-auto mb-3 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-[#999999]" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <p class="text-sm text-[#666666] dark:text-[#999999]">No existing files found</p>
                                    <p class="text-xs text-[#999999] dark:text-[#666666] mt-1">Upload new files below to
                                        add
                                        them to
                                        this transfer</p>
                                </div>
                            </div>

                            <!-- Upload New ZIP Files -->
                            <div>
                                <label
                                    class="block text-xs font-mono tracking-wide text-black dark:text-white mb-1">Upload
                                    New
                                    ZIP
                                    Files</label>
                                <FilePond name="files" :files="editFilePondFiles"
                                    @updatefiles="handleEditFilePondUpdate" :allowMultiple="true"
                                    :labelIdle="'Drag & Drop your ZIP files or <span class=\'filepond--label-action\'>Browse</span>'"
                                    :maxFiles="10" class="mt-1" />

                                <!-- New File list -->
                                <div v-if="editForm.files.length"
                                    class="mt-3 text-sm text-black dark:text-white space-y-1">
                                    <div v-for="(file, idx) in editForm.files" :key="idx"
                                        class="flex items-center justify-between gap-3 p-2 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                                        <span class="min-w-0 flex-1 truncate font-mono">{{ file.name }}</span>
                                        <span
                                            class="flex-shrink-0 text-xs font-mono text-[#666666] dark:text-[#999999]">{{
                                                (file.size
                                                    / 1024 /
                                            1024).toFixed(2) }}
                                            MB</span>
                                    </div>
                                    <div class="font-mono text-xs uppercase tracking-wider text-black dark:text-white">
                                        Total new
                                        files size: {{ editFileSize }} MB</div>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="flex space-x-4 pt-4">
                                <button type="button" @click="closeEditModal"
                                    class="flex-1 rounded-full bg-white dark:bg-black border-2 border-[#D71921] px-6 py-3 text-[#D71921] hover:bg-[#D71921] hover:text-white transition-colors text-xs font-mono tracking-wide">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="!editForm.name || !editForm.client"
                                    class="flex-1 rounded-full bg-black dark:bg-white border-2 border-black dark:border-white px-6 py-3 text-white dark:text-black hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-xs font-mono tracking-wide">
                                    Update
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>