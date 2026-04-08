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
                Swal.fire({ icon: 'success', title: 'Updated', toast: true, position: 'top-end', timer: 1200, showConfirmButton: false, customClass: { popup: 'rounded-lg' } });
                closeModal();
            },
            onError: () => Swal.fire({ title: 'Error', text: 'Failed to update template', icon: 'error', customClass: { popup: 'rounded-lg' } }),
        });
    } else {
        router.post(route('templates.store'), payload, {
            forceFormData: true,
            onSuccess: () => {
                Swal.fire({ icon: 'success', title: 'Uploaded', toast: true, position: 'top-end', timer: 1200, showConfirmButton: false, customClass: { popup: 'rounded-lg' } });
                closeModal();
            },
            onError: () => Swal.fire({ title: 'Error', text: 'Failed to upload template', icon: 'error', customClass: { popup: 'rounded-lg' } }),
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
        cancelButtonColor: '#000000',
        confirmButtonText: 'Yes, delete it!',
        customClass: { popup: 'rounded-lg' }
    });

    if (result.isConfirmed) {
        router.delete(route('templates.delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire({ icon: 'success', title: 'Deleted', toast: true, position: 'top-end', timer: 1200, showConfirmButton: false, customClass: { popup: 'rounded-lg' } }),
            onError: () => Swal.fire({ title: 'Error', text: 'Could not delete template', icon: 'error', customClass: { popup: 'rounded-lg' } }),
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
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 md:p-6 space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1">
                        <input v-model="search" @input="onSearchInput" placeholder="Search templates..."
                            aria-label="Search templates"
                            class="w-full max-w-xs rounded-full border-2 border-[#CCCCCC] dark:border-[#333333] px-4 py-2 bg-white dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] placeholder-[#999999] focus:outline-none focus:border-black dark:focus:border-white transition-colors" />
                    </div>
                    <div class="flex items-center">
                        <button @click="openAdd"
                            class="ml-3 rounded-full bg-black dark:bg-white px-4 py-2 text-white dark:text-black border-2 border-black dark:border-white hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white group flex items-center justify-center whitespace-nowrap transition-colors duration-200"
                            aria-label="Add Template">
                            <CirclePlus :stroke-width="1.5" class="w-4 h-4 mr-2" />
                            <span class="hidden sm:inline text-xs uppercase font-mono tracking-wider">Add
                                Template</span>
                        </button>
                    </div>
                </div>

                <!-- Table view (desktop) -->
                <div class="hidden lg:block overflow-x-auto rounded-lg border-2 border-[#CCCCCC] dark:border-[#222222]">
                    <table class="w-full bg-white dark:bg-[#111111] table-fixed">
                        <thead class="bg-[#F5F5F5] dark:bg-[#0A0A0A]">
                            <tr>
                                <th
                                    class="w-16 px-4 py-3 text-center text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    #</th>
                                <th
                                    class="px-4 py-3 text-left text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    Name</th>
                                <th
                                    class="px-4 py-3 text-left text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    File</th>
                                <th
                                    class="w-36 px-4 py-3 text-center text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    Created</th>
                                <th
                                    class="w-36 px-4 py-3 text-center text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    Updated</th>
                                <th
                                    class="w-32 px-4 py-3 text-center text-xs uppercase font-mono tracking-widest text-black dark:text-white">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8E8E8] dark:divide-[#222222]">
                            <tr v-for="(tpl, idx) in templates.data" :key="tpl.id"
                                class="hover:bg-[#F5F5F5] dark:hover:bg-[#0A0A0A] transition-colors duration-200">
                                <td class="text-center px-4 py-3 font-mono tabular-nums text-black dark:text-white">{{
                                    ((templates.current_page - 1) * templates.per_page)
                                    +
                                    idx + 1 }}</td>
                                <td class="px-4 py-3 uppercase font-mono text-black dark:text-white">{{ tpl.name }}</td>
                                <td class="px-4 py-3">
                                    <template v-if="tpl.url">
                                        <a :href="tpl.url"
                                            class="text-black dark:text-white underline hover:no-underline"
                                            target="_blank" rel="noopener noreferrer">{{ tpl.url }}</a>
                                    </template>
                                    <template v-else>
                                        <a :href="route('templates.download', tpl.id)"
                                            class="text-black dark:text-white underline hover:no-underline">{{
                                                tpl.file_name }}</a>
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-center text-xs font-mono text-[#666666] dark:text-[#999999]"
                                    :title="dayjs(tpl.created_at).format('DD MMM YYYY, HH:mm')">{{ tpl.created_at ?
                                        dayjs(tpl.created_at).fromNow() : '-' }}</td>
                                <td class="px-4 py-3 text-center text-xs font-mono text-[#666666] dark:text-[#999999]"
                                    :title="dayjs(tpl.updated_at).format('DD MMM YYYY, HH:mm')">{{ tpl.updated_at ?
                                        dayjs(tpl.updated_at).fromNow() : '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button @click="openEdit(tpl)"
                                        class="p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-black dark:border-white rounded-full transition-colors duration-200">
                                        <Pencil :stroke-width="1.5" class="inline h-4 w-4" />
                                    </button>
                                    <button @click="remove(tpl.id)"
                                        class="p-2 text-[#D71921] hover:bg-[#D71921] hover:text-white border-2 border-[#D71921] rounded-full ml-3 transition-colors duration-200">
                                        <Trash2 :stroke-width="1.5" class="inline h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="templates.data.length === 0">
                                <td colspan="6" class="px-4 py-6 text-center text-[#666666] dark:text-[#999999]">No
                                    templates
                                    found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile cards -->
                <div class="lg:hidden space-y-4">
                    <div v-for="(tpl, idx) in templates.data" :key="tpl.id"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#CCCCCC] dark:border-[#222222] p-4">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-mono text-[#666666] dark:text-[#999999]">#{{
                                    ((templates.current_page - 1) * templates.per_page) + idx + 1 }}</div>
                                <h3
                                    class="text-sm uppercase font-mono tracking-wider break-words text-black dark:text-white">
                                    {{
                                        tpl.name }}</h3>
                                <div class="text-xs font-mono text-[#666666] dark:text-[#999999]">
                                    <template v-if="tpl.url">
                                        <a :href="tpl.url"
                                            class="text-black dark:text-white underline hover:no-underline"
                                            target="_blank" rel="noopener noreferrer">{{ tpl.url }}</a>
                                    </template>
                                    <template v-else>
                                        {{ tpl.file_name }}
                                    </template>
                                </div>
                            </div>
                            <div class="flex gap-2 ml-3">
                                <template v-if="tpl.url">
                                    <a :href="tpl.url"
                                        class="p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white rounded-full transition-colors duration-200"
                                        aria-label="Open link" target="_blank" rel="noopener noreferrer">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M10 14a1 1 0 001.414 0L21 4.414V13a1 1 0 11-2 0V6.414L11.414 16A1 1 0 0010 14z" />
                                        </svg>
                                    </a>
                                </template>
                                <template v-else>
                                    <a :href="route('templates.download', tpl.id)"
                                        class="p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white rounded-full transition-colors duration-200"
                                        aria-label="Download">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 3v12m0 0l3-3m-3 3l-3-3m6 9H6" />
                                        </svg>
                                    </a>
                                </template>
                                <button @click="openEdit(tpl)"
                                    class="p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white rounded-full transition-colors duration-200">
                                    <Pencil :stroke-width="1.5" class="h-4 w-4" />
                                </button>
                                <button @click="remove(tpl.id)"
                                    class="p-2 text-[#D71921] hover:bg-[#D71921] hover:text-white border-2 border-[#D71921] rounded-full transition-colors duration-200">
                                    <Trash2 :stroke-width="1.5" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                        <div class="text-xs font-mono text-[#666666] dark:text-[#999999]">Created: <span
                                :title="dayjs(tpl.created_at).format('DD MMM YYYY, HH:mm')">{{ tpl.created_at ?
                                    dayjs(tpl.created_at).fromNow() : '-' }}</span></div>
                    </div>

                    <div v-if="templates.data.length === 0"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#CCCCCC] dark:border-[#222222] p-8 text-center">
                        <div class="text-[#666666] dark:text-[#999999]">No templates found.</div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="templates.data.length && templates.links && templates.links.length" class="mt-6 p-4">

                    <!-- Mobile/Tablet pagination (simplified) -->
                    <div class="lg:hidden">
                        <!-- Results Info -->
                        <div class="text-xs font-mono text-[#666666] dark:text-[#999999] text-center mb-3">
                            Showing {{ templates.from }} to {{ templates.to }} of {{ templates.total }} templates
                        </div>

                        <!-- Simple prev/next navigation -->
                        <div class="flex items-center justify-between gap-4">
                            <button @click="changePage(templates.prev_page_url)" :disabled="!templates.prev_page_url"
                                class="px-4 py-2 text-xs uppercase font-mono tracking-wider rounded-full transition-colors duration-200 flex items-center gap-2 border-2"
                                :class="templates.prev_page_url
                                    ? 'text-black dark:text-white border-black dark:border-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black'
                                    : 'text-[#CCCCCC] cursor-not-allowed border-[#CCCCCC] dark:border-[#333333]'">
                                <ChevronLeft :stroke-width="1.5" class="w-4 h-4" />
                                Previous
                            </button>

                            <span class="text-xs font-mono tabular-nums text-[#666666] dark:text-[#999999]">
                                Page {{ templates.current_page }} of {{ templates.last_page }}
                            </span>

                            <button @click="changePage(templates.next_page_url)" :disabled="!templates.next_page_url"
                                class="px-4 py-2 text-xs uppercase font-mono tracking-wider rounded-full transition-colors duration-200 flex items-center gap-2 border-2"
                                :class="templates.next_page_url
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
                            Showing {{ templates.from }} to {{ templates.to }} of {{ templates.total }} templates
                        </div>

                        <!-- Pagination Controls -->
                        <div class="flex items-center space-x-2">
                            <!-- Previous Button -->
                            <button @click="changePage(templates.prev_page_url)" :disabled="!templates.prev_page_url"
                                class="px-3 py-2 text-xs uppercase font-mono tracking-wider rounded-full transition-colors duration-200 flex items-center border-2"
                                :class="templates.prev_page_url
                                    ? 'text-black dark:text-white border-black dark:border-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black'
                                    : 'text-[#CCCCCC] cursor-not-allowed border-[#CCCCCC] dark:border-[#333333]'">
                                <ChevronLeft :stroke-width="1.5" class="w-4 h-4 mr-1" />
                                Previous
                            </button>

                            <!-- Page Numbers -->
                            <div class="flex items-center space-x-1">
                                <template v-for="link in templates.links.slice(1, -1)" :key="link.label">
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
                            <button @click="changePage(templates.next_page_url)" :disabled="!templates.next_page_url"
                                class="px-3 py-2 text-xs uppercase font-mono tracking-wider rounded-full transition-colors duration-200 flex items-center border-2"
                                :class="templates.next_page_url
                                    ? 'text-black dark:text-white border-black dark:border-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black'
                                    : 'text-[#CCCCCC] cursor-not-allowed border-[#CCCCCC] dark:border-[#333333]'">
                                Next
                                <ChevronRight :stroke-width="1.5" class="w-4 h-4 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal -->
                <div v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4"
                    style="margin-top: 0!important;" @click.self="closeModal">
                    <div
                        class="mx-4 bg-white dark:bg-[#111111] rounded-lg w-full max-w-2xl overflow-hidden border-2 border-[#CCCCCC] dark:border-[#222222]">
                        <div
                            class="flex items-center justify-between p-4 border-b-2 border-[#CCCCCC] dark:border-[#222222]">
                            <h2 class="text-xs uppercase font-mono tracking-widest text-black dark:text-white">{{
                                isEditing ? 'EditTemplate' :
                                'Add Template'
                                }}</h2>
                            <button @click="closeModal"
                                class="p-2 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white rounded-full transition-colors duration-200"><svg
                                    xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg></button>
                        </div>
                        <div class="p-6">
                            <div class="mb-3">
                                <label
                                    class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white mb-2">Name</label>
                                <input v-model="form.name"
                                    class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] bg-white dark:bg-black text-black dark:text-white px-4 py-2 focus:outline-none focus:border-black dark:focus:border-white transition-colors" />
                            </div>
                            <div class="mb-3">
                                <label
                                    class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white mb-2">Type</label>
                                <div class="flex items-center gap-4 mb-3">
                                    <label
                                        class="inline-flex items-center cursor-pointer text-xs uppercase font-mono tracking-wider text-black dark:text-white">
                                        <input type="radio" v-model="mode" value="file" class="mr-2" />
                                        <span>Upload ZIP</span>
                                    </label>
                                    <label
                                        class="inline-flex items-center cursor-pointer text-xs uppercase font-mono tracking-wider text-black dark:text-white">
                                        <input type="radio" v-model="mode" value="link" class="mr-2" />
                                        <span>External Link</span>
                                    </label>
                                </div>

                                <div v-if="mode === 'file'">
                                    <label
                                        class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white">Zip
                                        File</label>
                                    <FilePond name="file" :files="filePondFiles" @updatefiles="handleFilePondUpdate"
                                        :allowMultiple="false"
                                        :labelIdle="'Drag & Drop your ZIP file or <span class=\'filepond--label-action\'>Browse</span>'"
                                        class="mt-1 filepond-dropzone" />

                                    <div v-if="form.file" class="mt-3 text-sm text-black dark:text-white">
                                        <div
                                            class="flex items-center justify-between p-2 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                                            <span class="font-mono text-sm">{{ form.file.name }}</span>
                                            <span class="text-xs font-mono text-[#666666] dark:text-[#999999]">{{
                                                (form.file.size / 1024
                                                    /
                                                1024).toFixed(2)
                                                }}
                                                MB</span>
                                        </div>
                                    </div>

                                    <div v-else-if="form.file_name" class="mt-3 text-sm text-black dark:text-white">
                                        <div
                                            class="flex items-center justify-between p-2 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                                            <span class="font-mono text-sm">{{ form.file_name }}</span>
                                            <a :href="route('templates.download', form.id)"
                                                class="text-xs font-mono uppercase tracking-wider text-black dark:text-white underline hover:no-underline">Download</a>
                                        </div>
                                    </div>
                                </div>

                                <div v-else>
                                    <label
                                        class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white">URL</label>
                                    <input v-model="form.url" placeholder="https://example.com/template.zip"
                                        class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] bg-white dark:bg-black text-black dark:text-white px-4 py-2 focus:outline-none focus:border-black dark:focus:border-white transition-colors" />
                                </div>
                            </div>
                            <div class="flex space-x-4 pt-4">
                                <button type="button" @click="closeModal"
                                    class="flex-1 rounded-full bg-white dark:bg-black border-2 border-[#D71921] px-6 py-3 text-[#D71921] hover:bg-[#D71921] hover:text-white transition-colors text-xs uppercase font-mono tracking-wider">Cancel</button>
                                <button type="button" @click="submit"
                                    :disabled="(!isEditing && ((mode === 'file' && !form.file) || (mode === 'link' && !form.url)))"
                                    class="flex-1 rounded-full bg-black dark:bg-white border-2 border-black dark:border-white px-6 py-3 text-white dark:text-black hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-xs uppercase font-mono tracking-wider">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
