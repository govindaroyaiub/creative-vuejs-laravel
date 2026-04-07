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

const page = usePage<any>();

const preview_id = computed<string>(() => String((page.props as any).preview_id ?? ''));
const preview_name = computed<string>(() => String((page.props as any).preview_name ?? ''));
const client_name = computed<string>(() => String((page.props as any).client_name ?? ''));

const form = ref({
    preview_id: preview_id.value || '',
    name: preview_name.value || '',
    client: client_name.value || '',
    files: [] as File[],
});
const fileSize = ref('0.00');
const filePondFiles = ref<any[]>([]);

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
            handleCancel();
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

const handleCancel = () => {
    if (window.history.length > 1) {
        history.back();
    }
};
</script>

<template>

    <Head title="File Transfers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black flex justify-center">
            <div class="p-4 md:p-6 w-full max-w-6xl">
                <div
                    class="bg-white dark:bg-[#111111] rounded-lg w-full border-2 border-[#CCCCCC] dark:border-[#222222]">
                    <!-- Form Content -->
                    <div class="p-6">
                        <form @submit.prevent="handleSubmit" class="space-y-6">
                            <!-- Name -->
                            <div>
                                <label for="name"
                                    class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white mb-1">Name</label>
                                <input id="name" v-model="form.name" required type="text"
                                    class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors" />
                            </div>

                            <!-- Client -->
                            <div>
                                <label for="client"
                                    class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white mb-1">Client</label>
                                <input id="client" v-model="form.client" required type="text"
                                    class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors" />
                            </div>

                            <!-- FilePond Upload -->
                            <div>
                                <label
                                    class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white mb-1">Upload
                                    ZIP
                                    Files</label>
                                <FilePond name="files" :files="filePondFiles" @updatefiles="handleFilePondUpdate"
                                    :allowMultiple="true"
                                    :labelIdle="'Drag & Drop your ZIP files or <span class=\'filepond--label-action\'>Browse</span>'"
                                    :maxFiles="10" class="mt-1" />

                                <!-- File list -->
                                <div v-if="form.files.length" class="mt-3 text-sm text-black dark:text-white space-y-1">
                                    <div v-for="(file, idx) in form.files" :key="idx"
                                        class="flex justify-between items-center p-2 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                                        <span class="font-mono">{{ file.name }}</span>
                                        <span class="text-xs font-mono text-[#666666] dark:text-[#999999]">{{ (file.size
                                            / 1024 / 1024).toFixed(2) }}
                                            MB</span>
                                    </div>
                                    <div class="font-mono text-xs uppercase tracking-wider text-black dark:text-white">
                                        Total size: {{ fileSize }} MB</div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex gap-4 pt-4">
                                <button type="button" @click="handleCancel"
                                    class="flex-1 rounded-full bg-white dark:bg-black border-2 border-[#D71921] px-6 py-3 text-[#D71921] hover:bg-[#D71921] hover:text-white transition-colors text-xs uppercase font-mono tracking-wider">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="!form.name || !form.client || !form.files.length"
                                    class="flex-1 rounded-full bg-black dark:bg-white border-2 border-black dark:border-white px-6 py-3 text-white dark:text-black hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-xs uppercase font-mono tracking-wider">
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