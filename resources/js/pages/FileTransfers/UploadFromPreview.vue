<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

// Create FilePond component
import vueFilePond from 'vue-filepond';

// FilePond styles
import 'filepond/dist/filepond.min.css';

// Create FilePond component
const FilePond = vueFilePond();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'File Transfers', href: '/file-transfers' }, { title: 'Upload From Preview', href: '/file-transfers/upload-from-preview' }];

const page = usePage();
const fileTransfers = computed(() => page.props.fileTransfers ?? { data: [], links: [] });

const preview_id = computed(() => page.props.preview_id);
const preview_name = computed(() => page.props.preview_name);
const client_name = computed(() => page.props.client_name);

const form = ref({
    preview_id : preview_id.value || '',
    name: preview_name.value || '',
    client: client_name.value || '',
    files: [] as File[],
});
const fileSize = ref('0.00');
const filePondFiles = ref([]);

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
    payload.append('preview_id', preview_id.value || '');
    payload.append('name', form.value.name);
    payload.append('client', form.value.client);
    form.value.files.forEach((file) => {
        payload.append('file[]', file);
    });

    router.post('/file-transfers-upload-from-preview', payload, {
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
</script>

<template>

    <Head title="File Transfers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex justify-center">
            <div class="p-4 md:p-6 w-full max-w-6xl">
                <div class="bg-white dark:bg-neutral-800 rounded-2xl w-full shadow-lg border border-gray-200 dark:border-neutral-700">
                    <!-- Form Content -->
                    <div class="p-6">
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

                            <!-- Submit Button -->
                            <div class="flex gap-4 pt-4">
                                <button type="button" @click="router.get('/file-transfers')"
                                    class="flex-1 rounded-xl bg-red-600 px-6 py-3 text-white shadow hover:bg-red-700 transition-colors">
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
        </div>
    </AppLayout>
</template>