<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Pencil, Trash2, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';
import vueFilePond from 'vue-filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import 'filepond/dist/filepond.min.css';
const FilePond = vueFilePond(FilePondPluginFileValidateType);
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
dayjs.extend(relativeTime);

const page = usePage();
const templates = computed(() => page.props.templates ?? { data: [], links: [] });
const search = ref(page.props.filters?.search ?? '');
const loading = ref(false);
let debounceTimer: number | null = null;

function onSearchInput() {
    if (debounceTimer) window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(() => {
        loading.value = true;
        router.get(route('templates.index'), { search: search.value, page: 1 }, { preserveState: true, replace: true, onFinish: () => { loading.value = false } });
    }, 300);
}

const changePage = (url: string) => {
    if (!url) return;
    loading.value = true;
    router.get(url, { search: search.value }, { preserveState: true, onFinish: () => { loading.value = false } });
};

const showModal = ref(false);
const isEditing = ref(false);
const mode = ref<'file' | 'link'>('file');
const form = ref({ id: null as number | null, name: '', file: null as File | null, url: '', file_name: null as string | null, file_path: null as string | null });
const filePondFiles = ref<any[]>([]);

const handleFilePondUpdate = (files: any[]) => {
    filePondFiles.value = files;
    if (files.length > 0) {
        const validFiles = files
            .map(f => f.file)
            .filter((file: any) => file && file.name && file.name.toLowerCase().endsWith('.zip'));

        form.value.file = validFiles.length ? validFiles[0] : null;

        if (validFiles.length !== files.length) {
            console.warn('Some files were filtered out. Only ZIP files are allowed.');
        }
    } else {
        form.value.file = null;
    }
};

function openAdd() {
    isEditing.value = false;
    mode.value = 'file';
    form.value = { id: null, name: '', file: null, url: '', file_name: null, file_path: null };
    filePondFiles.value = [];
    showModal.value = true;
}

function openEdit(tpl: any) {
    isEditing.value = true;
    form.value = { id: tpl.id, name: tpl.name ?? '', file: null, url: tpl.url ?? '', file_name: tpl.file_name ?? null, file_path: tpl.file_path ?? null };
    // clear any previously staged files in FilePond
    filePondFiles.value = [];
    mode.value = tpl.url ? 'link' : 'file';
    showModal.value = true;
}

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    form.value.file = input.files?.[0] ?? null;
}

function closeModal() {
    showModal.value = false;
    // reset form and file pond to avoid stale data
    form.value = { id: null, name: '', file: null, url: '', file_name: null, file_path: null };
    mode.value = 'file';
    filePondFiles.value = [];
}

function submit() {
    const payload = new FormData();
    if (form.value.name) payload.append('name', form.value.name);
    if (mode.value === 'link' && form.value.url) payload.append('url', form.value.url);
    if (mode.value === 'file' && form.value.file) payload.append('file', form.value.file as File);

    if (isEditing.value && form.value.id) {
        router.post(route('templates.update', form.value.id), payload, {
            forceFormData: true,
            onSuccess: () => {
                Swal.fire({ icon: 'success', title: 'Updated', toast: true, position: 'top-end', timer: 1200, showConfirmButton: false });
                closeModal();
            },
            onError: () => Swal.fire('Error', 'Failed to update template', 'error'),
        });
    } else {
        router.post(route('templates.store'), payload, {
            forceFormData: true,
            onSuccess: () => {
                Swal.fire({ icon: 'success', title: 'Uploaded', toast: true, position: 'top-end', timer: 1200, showConfirmButton: false });
                closeModal();
            },
            onError: () => Swal.fire('Error', 'Failed to upload template', 'error'),
        });
    }
}

const remove = async (id: number) => {
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
        router.delete(route('templates.delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire({ icon: 'success', title: 'Deleted', toast: true, position: 'top-end', timer: 1200, showConfirmButton: false }),
            onError: () => Swal.fire('Error', 'Could not delete template', 'error'),
        });
    }
};

function gotoLink(link: any) {
    if (!link.url) return;
    router.visit(link.url, { preserveState: false });
}
</script>

<template>

    <Head title="Templates" />
    <AppLayout :breadcrumbs="[{ title: 'Templates', href: '/templates' }]">
        <div class="p-4 md:p-6 space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex-1">
                    <input v-model="search" @input="onSearchInput" placeholder="Search templates..."
                        aria-label="Search templates"
                        class="w-full max-w-xs rounded-2xl border px-4 py-2 dark:bg-neutral-800 dark:text-white" />
                </div>
                <div class="flex items-center">
                    <button @click="openAdd"
                        class="ml-3 rounded-xl bg-green-600 px-3 py-2 text-white hover:bg-green-700 group flex items-center justify-center whitespace-nowrap"
                        aria-label="Add Template">
                        <CirclePlus class="w-4 h-4 mr-2 group-hover:rotate-90 transition-transform duration-200" />
                        <span class="hidden sm:inline">Add Template</span>
                    </button>
                </div>
            </div>

            <!-- Table view (desktop) -->
            <div class="hidden lg:block overflow-x-auto rounded-2xl shadow">
                <table class="w-full rounded-2xl bg-white shadow dark:bg-neutral-800 dark:border border table-fixed">
                    <thead class="bg-gray-100 dark:bg-neutral-900 text-xs uppercase">
                        <tr>
                            <th class="w-16 px-4 py-3 text-center">#</th>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">File</th>
                            <th class="w-36 px-4 py-3 text-center">Created</th>
                            <th class="w-36 px-4 py-3 text-center">Updated</th>
                            <th class="w-32 px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-black">
                        <tr v-for="(tpl, idx) in templates.data" :key="tpl.id"
                            class="hover:bg-gray-50 dark:hover:bg-neutral-700 border-b">
                            <td class="text-center px-4 py-3">{{ ((templates.current_page - 1) * templates.per_page) +
                                idx + 1 }}</td>
                            <td class="px-4 py-3">{{ tpl.name }}</td>
                            <td class="px-4 py-3">
                                <template v-if="tpl.url">
                                    <a :href="tpl.url" class="text-blue-600" target="_blank"
                                        rel="noopener noreferrer">{{ tpl.url }}</a>
                                </template>
                                <template v-else>
                                    <a :href="route('templates.download', tpl.id)" class="text-blue-600">{{
                                        tpl.file_name }}</a>
                                </template>
                            </td>
                            <td class="px-4 py-3 text-center"
                                :title="dayjs(tpl.created_at).format('DD MMM YYYY, HH:mm')">{{ tpl.created_at ?
                                    dayjs(tpl.created_at).fromNow() : '-' }}</td>
                            <td class="px-4 py-3 text-center"
                                :title="dayjs(tpl.updated_at).format('DD MMM YYYY, HH:mm')">{{ tpl.updated_at ?
                                    dayjs(tpl.updated_at).fromNow() : '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <button @click="openEdit(tpl)" class="text-blue-600 hover:text-blue-800">
                                    <Pencil class="inline h-5 w-5" />
                                </button>
                                <button @click="remove(tpl.id)" class="text-red-600 hover:text-red-800 ml-3">
                                    <Trash2 class="inline h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="templates.data.length === 0">
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No templates
                                found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div class="lg:hidden space-y-4">
                <div v-for="(tpl, idx) in templates.data" :key="tpl.id"
                    class="bg-white dark:bg-neutral-800 rounded-2xl shadow border p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">#{{
                                ((templates.current_page - 1) * templates.per_page) + idx + 1 }}</div>
                            <h3 class="font-semibold text-lg capitalize break-words text-gray-900 dark:text-white">{{
                                tpl.name }}</h3>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <template v-if="tpl.url">
                                    <a :href="tpl.url" class="text-blue-600" target="_blank"
                                        rel="noopener noreferrer">{{ tpl.url }}</a>
                                </template>
                                <template v-else>
                                    {{ tpl.file_name }}
                                </template>
                            </div>
                        </div>
                        <div class="flex gap-2 ml-3">
                            <template v-if="tpl.url">
                                <a :href="tpl.url" class="text-blue-600 p-2 rounded-lg" aria-label="Open link"
                                    target="_blank" rel="noopener noreferrer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 14a1 1 0 001.414 0L21 4.414V13a1 1 0 11-2 0V6.414L11.414 16A1 1 0 0010 14z" />
                                    </svg>
                                </a>
                            </template>
                            <template v-else>
                                <a :href="route('templates.download', tpl.id)" class="text-blue-600 p-2 rounded-lg"
                                    aria-label="Download">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 3v12m0 0l3-3m-3 3l-3-3m6 9H6" />
                                    </svg>
                                </a>
                            </template>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3v12m0 0l3-3m-3 3l-3-3m6 9H6" />
                            </svg>
                            <button @click="openEdit(tpl)" class="text-blue-600 p-2 rounded-lg">
                                <Pencil class="h-5 w-5" />
                            </button>
                            <button @click="remove(tpl.id)" class="text-red-600 p-2 rounded-lg">
                                <Trash2 class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Created: <span
                            :title="dayjs(tpl.created_at).format('DD MMM YYYY, HH:mm')">{{ tpl.created_at ?
                                dayjs(tpl.created_at).fromNow() : '-' }}</span></div>
                </div>

                <div v-if="templates.data.length === 0"
                    class="bg-white dark:bg-neutral-800 rounded-2xl shadow border p-8 text-center">
                    <div class="text-gray-500 dark:text-gray-400">No templates found.</div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="templates.data.length && templates.links && templates.links.length" class="mt-6 p-4">

                <!-- Mobile/Tablet pagination (simplified) -->
                <div class="lg:hidden">
                    <!-- Results Info -->
                    <div class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">
                        Showing {{ templates.from }} to {{ templates.to }} of {{ templates.total }} templates
                    </div>

                    <!-- Simple prev/next navigation -->
                    <div class="flex items-center justify-between gap-4">
                        <button @click="changePage(templates.prev_page_url)" :disabled="!templates.prev_page_url"
                            class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2"
                            :class="templates.prev_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                            <ChevronLeft class="w-4 h-4" />
                            Previous
                        </button>

                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Page {{ templates.current_page }} of {{ templates.last_page }}
                        </span>

                        <button @click="changePage(templates.next_page_url)" :disabled="!templates.next_page_url"
                            class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2"
                            :class="templates.next_page_url
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
                        Showing {{ templates.from }} to {{ templates.to }} of {{ templates.total }} templates
                    </div>

                    <!-- Pagination Controls -->
                    <div class="flex items-center space-x-2">
                        <!-- Previous Button -->
                        <button @click="changePage(templates.prev_page_url)" :disabled="!templates.prev_page_url"
                            class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center" :class="templates.prev_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                            <ChevronLeft class="w-4 h-4 mr-1" />
                            Previous
                        </button>

                        <!-- Page Numbers -->
                        <div class="flex items-center space-x-1">
                            <template v-for="link in templates.links.slice(1, -1)" :key="link.label">
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
                        <button @click="changePage(templates.next_page_url)" :disabled="!templates.next_page_url"
                            class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center" :class="templates.next_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                            Next
                            <ChevronRight class="w-4 h-4 ml-1" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4" style="margin-top: 0!important;"
                @click.self="closeModal">
                <div
                    class="mx-4 bg-white dark:bg-neutral-800 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl border border-gray-200 dark:border-neutral-700">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-neutral-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ isEditing ? 'Edit Template' :
                            'Add Template'
                            }}</h2>
                        <button @click="closeModal" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg"><svg
                                xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg></button>
                    </div>
                    <div class="p-6">
                        <div class="mb-3">
                            <label class="block text-sm">Name</label>
                            <input v-model="form.name" class="w-full rounded-2xl border px-4 py-2" />
                        </div>
                        <div class="mb-3">
                            <label class="block text-sm mb-2">Type</label>
                            <div class="flex items-center gap-4 mb-3">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" v-model="mode" value="file" class="mr-2" />
                                    <span>Upload ZIP</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" v-model="mode" value="link" class="mr-2" />
                                    <span>External Link</span>
                                </label>
                            </div>

                            <div v-if="mode === 'file'">
                                <label class="block text-sm">Zip File</label>
                                <FilePond name="file" :files="filePondFiles" @updatefiles="handleFilePondUpdate"
                                    :allowMultiple="false"
                                    :labelIdle="'Drag & Drop your ZIP file or <span class=\'filepond--label-action\'>Browse</span>'"
                                    class="mt-1 filepond-dropzone" />

                                <div v-if="form.file" class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                    <div
                                        class="flex items-center justify-between p-2 bg-gray-50 dark:bg-neutral-700 rounded-lg">
                                        <span>{{ form.file.name }}</span>
                                        <span class="text-xs text-gray-500">{{ (form.file.size / 1024 / 1024).toFixed(2)
                                        }}
                                            MB</span>
                                    </div>
                                </div>

                                <div v-else-if="form.file_name" class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                    <div
                                        class="flex items-center justify-between p-2 bg-gray-50 dark:bg-neutral-700 rounded-lg">
                                        <span>{{ form.file_name }}</span>
                                        <a :href="route('templates.download', form.id)"
                                            class="text-blue-600 text-xs">Download</a>
                                    </div>
                                </div>
                            </div>

                            <div v-else>
                                <label class="block text-sm">URL</label>
                                <input v-model="form.url" placeholder="https://example.com/template.zip"
                                    class="w-full rounded-2xl border px-4 py-2" />
                            </div>
                        </div>
                        <div class="flex space-x-4 pt-4">
                            <button type="button" @click="closeModal"
                                class="flex-1 rounded-xl bg-red-600 px-6 py-3 text-white shadow hover:bg-red-700 transition-colors">Cancel</button>
                            <button type="button" @click="submit"
                                :disabled="(!isEditing && ((mode === 'file' && !form.file) || (mode === 'link' && !form.url)))"
                                class="flex-1 rounded-xl bg-green-600 px-6 py-3 text-white shadow hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
