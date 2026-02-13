<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Pencil, Trash2, CirclePlus, Search, ExternalLink, Palette, Building2, Users, X, Eye } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, computed, onMounted, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';

// Create FilePond component
import vueFilePond from 'vue-filepond';

// FilePond plugins
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';

// FilePond styles
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';

// Create FilePond component with image preview
const FilePond = vueFilePond(FilePondPluginFileValidateType, FilePondPluginImagePreview);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Clients', href: '/clients' }];

const page = usePage();
const clients = computed(() => page.props.clients);
const colorPalettes = computed(() => page.props.colorPalettes || []);
const flash = computed(() => page.props.flash);
const search = ref(page.props.search ?? '');
const isLoading = ref(false);

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);

// Image viewer modal
const showImageModal = ref(false);
const currentImage = ref('');

const onKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') closeImageModal();
};

const openImageModal = (src: string) => {
    currentImage.value = src;
    showImageModal.value = true;
    document.addEventListener('keydown', onKeydown);
};

const closeImageModal = () => {
    showImageModal.value = false;
    currentImage.value = '';
    document.removeEventListener('keydown', onKeydown);
};

// Form states
const createForm = ref({
    name: '',
    website: '',
    preview_url: 'https://creative.planetnine.com',
    color_palette_id: '',
    logo: null as File | null,
});

const editForm = ref({
    id: null as number | null,
    name: '',
    website: '',
    preview_url: '',
    color_palette_id: null as number | null,
    logo: null as File | null,
});

// FilePond states
const createFilePondFiles = ref([]);
const editFilePondFiles = ref([]);
const logoPreview = ref<string>('');

watch(search, (value) => {
    isLoading.value = true;
    setTimeout(() => {
        router.get(route('clients'), { search: value }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onFinish: () => {
                isLoading.value = false;
            }
        });
    }, 300); // Debounce search
});

// Modal handlers
const openCreateModal = () => {
    createForm.value = {
        name: '',
        website: '',
        preview_url: 'https://creative.planetnine.com',
        color_palette_id: '',
        logo: null,
    };
    createFilePondFiles.value = [];
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.value = {
        name: '',
        website: '',
        preview_url: 'https://creative.planetnine.com',
        color_palette_id: '',
        logo: null,
    };
    createFilePondFiles.value = [];
};

const openEditModal = (client: any) => {
    editForm.value = {
        id: client.id,
        name: client.name,
        website: client.website,
        preview_url: client.preview_url || '',
        color_palette_id: client.color_palette_id,
        logo: null,
    };
    editFilePondFiles.value = [];
    logoPreview.value = client.logo ? `/logos/${client.logo}` : '';
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.value = {
        id: null,
        name: '',
        website: '',
        preview_url: '',
        color_palette_id: null,
        logo: null,
    };
    editFilePondFiles.value = [];
    logoPreview.value = '';
};

// FilePond handlers
const handleCreateFilePondUpdate = (files: any[]) => {
    createFilePondFiles.value = files;
    if (files.length > 0 && files[0].file) {
        createForm.value.logo = files[0].file;
    } else {
        createForm.value.logo = null;
    }
};

const handleEditFilePondUpdate = (files: any[]) => {
    editFilePondFiles.value = files;
    if (files.length > 0 && files[0].file) {
        editForm.value.logo = files[0].file;
        logoPreview.value = URL.createObjectURL(files[0].file);
    } else {
        editForm.value.logo = null;
    }
};

// Form submissions
const handleCreateSubmit = () => {
    const formData = new FormData();
    formData.append('name', createForm.value.name);
    formData.append('website', createForm.value.website);
    formData.append('preview_url', createForm.value.preview_url);
    formData.append('color_palette_id', String(createForm.value.color_palette_id));
    if (createForm.value.logo) {
        formData.append('logo', createForm.value.logo);
    }

    router.post('/clients-store', formData, {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Client created successfully!',
                timer: 1000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                timerProgressBar: true,
            });
        },
        onError: () => Swal.fire('Error!', 'Failed to create client.', 'error'),
    });
};

const handleEditSubmit = () => {
    const formData = new FormData();
    formData.append('name', editForm.value.name);
    formData.append('website', editForm.value.website);
    formData.append('preview_url', editForm.value.preview_url);
    formData.append('color_palette_id', editForm.value.color_palette_id?.toString() || '');
    if (editForm.value.logo) {
        formData.append('logo', editForm.value.logo);
    }

    router.post(`/clients-update/${editForm.value.id}`, formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeEditModal();
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Client updated successfully!',
                timer: 1000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                timerProgressBar: true,
            });
        },
        onError: () => Swal.fire('Error!', 'Failed to update client.', 'error'),
    });
};

const deleteClient = async (id: number, name: string) => {
    const result = await Swal.fire({
        title: 'Delete Client?',
        html: `Are you sure you want to delete <strong>${name}</strong>?<br><small class="text-gray-500">This action cannot be undone!</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-md',
            confirmButton: 'rounded-lg px-6 py-2',
            cancelButton: 'rounded-lg px-6 py-2'
        }
    });

    if (result.isConfirmed) {
        router.delete(route('clients-delete', id), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    title: 'Deleted!',
                    text: 'Client deleted successfully.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    customClass: { popup: 'rounded-md' }
                });
            },
            onError: () => {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to delete client.',
                    icon: 'error',
                    customClass: { popup: 'rounded-md' }
                });
            }
        });
    }
};

const copyColor = (hex: string) => {
    navigator.clipboard.writeText(hex).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `Copied: ${hex}`,
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true,
            customClass: {
                popup: 'rounded-lg text-sm'
            }
        });
    });
};

onMounted(() => {
    if (flash.value?.success) {
        Swal.fire({
            title: 'Success!',
            text: flash.value.success,
            icon: 'success',
            timer: 1000,
            showConfirmButton: false,
            customClass: { popup: 'rounded-md' }
        });
    }
});

const totalClients = computed(() => clients.value?.total || 0);
</script>

<template>

    <Head title="Clients Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-50 to-gray-50 dark:from-black dark:via-black dark:to-black">
            <div class="p-6 space-y-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                    <div
                        class="bg-white dark:bg-neutral-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Clients</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ totalClients }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-md">
                                <Users class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="rounded-2xl flex items-center space-x-4">
                    <div class="flex flex-col sm:flex-row gap-4 w-full">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                            <input v-model="search" placeholder="Search clients by name, website..."
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" />
                            <div v-if="isLoading" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <div
                                    class="animate-spin rounded-full h-4 w-4 border-2 border-blue-600 border-t-transparent">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button @click="openCreateModal"
                        class="w-1/6 inline-flex justify-center items-center px-6 py-3 bg-green-600 text-white rounded-xl hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-sm hover:shadow-md group">
                        <CirclePlus class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-200" />
                        Add Client
                    </button>
                </div>

                <!-- Clients Grid -->
                <div v-if="clients?.data?.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="(client, index) in clients.data" :key="client.id"
                        class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg hover:border-blue-200 dark:hover:border-neutral-600 transition-all duration-200 overflow-hidden group">
                        <!-- Card Header -->
                        <div class="py-2 px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center justify-start">
                                    <img v-if="client.logo" :src="`/logos/${client.logo}`" :alt="client.name + ' logo'"
                                        @click="openImageModal(`/logos/${client.logo}`)"
                                        class="h-20 w-40 aspect-auto object-contain rounded-xl bg-neutral-300 p-1 dark:bg-neutral-700 border cursor-pointer hover:scale-105 transition-transform duration-150" />
                                    <Building2 v-else class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="flex items-center justify-end">
                                    <h1 class="font-semibold text-gray-900 dark:text-white text-xl">{{ client.name
                                        }}</h1>
                                </div>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="px-6 pb-2 space-y-4">
                            <!-- Website -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Website:</span>
                                <a :href="client.website" target="_blank"
                                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center group/link">
                                    {{ client.website?.replace(/^https?:\/\//, '') || 'Not set' }}
                                    <ExternalLink
                                        class="w-3 h-3 ml-1 group-hover/link:translate-x-0.5 group-hover/link:-translate-y-0.5 transition-transform duration-200" />
                                </a>
                            </div>

                            <!-- Preview URL -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Preview:</span>
                                <a v-if="client.preview_url" :href="client.preview_url" target="_blank"
                                    class="text-sm text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 flex items-center group/link">
                                    Visit Preview
                                    <Eye
                                        class="w-3 h-3 ml-1 group-hover/link:scale-110 transition-transform duration-200" />
                                </a>
                                <span v-else class="text-sm text-gray-400">Not configured</span>
                            </div>

                            <!-- Brand Color -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Brand Color:</span>
                                <button v-if="client.color_palette?.primary"
                                    @click="copyColor(client.color_palette.primary)"
                                    class="flex items-center space-x-2 group/color hover:scale-105 transition-transform duration-200"
                                    :title="`Click to copy: ${client.color_palette.primary}`">
                                    <div :style="{ backgroundColor: client.color_palette.primary }"
                                        class="w-6 h-6 rounded-lg border-2 border-white dark:border-neutral-700 shadow-sm">
                                    </div>
                                    <span
                                        class="text-xs text-gray-500 font-mono group-hover/color:text-blue-600 transition-colors">
                                        {{ client.color_palette.primary }}
                                    </span>
                                </button>
                                <span v-else class="text-sm text-gray-400 flex items-center">
                                    <Palette class="w-4 h-4 mr-1" />
                                    Not set
                                </span>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div
                            class="px-6 py-4 bg-gray-50 dark:bg-neutral-900 border-t border-gray-200 dark:border-neutral-700">
                            <div class="flex justify-end space-x-2">
                                <button @click="openEditModal(client)"
                                    class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group/edit"
                                    title="Edit Client">
                                    <Pencil
                                        class="w-4 h-4 group-hover/edit:scale-110 transition-transform duration-200" />
                                </button>
                                <button @click="deleteClient(client.id, client.name)"
                                    class="p-2 text-red-600 hover:text-red-800 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-lg transition-all duration-200 group/delete"
                                    title="Delete Client">
                                    <Trash2
                                        class="w-4 h-4 group-hover/delete:scale-110 transition-transform duration-200" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else
                    class="bg-white dark:bg-neutral-800 rounded-md shadow-sm border border-gray-200 dark:border-neutral-700 p-12 text-center">
                    <div
                        class="w-20 h-20 mx-auto bg-gray-100 dark:bg-neutral-800 rounded-full flex items-center justify-center mb-6">
                        <Building2 class="w-10 h-10 text-gray-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No clients found</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                        {{ search ? 'Try adjusting your search terms' : 'Get started by adding your first client' }}
                    </p>
                    <button v-if="!search" @click="openCreateModal"
                        class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200">
                        <CirclePlus class="w-5 h-5 mr-2" />
                        Add Your First Client
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="clients?.links?.length > 3"
                    class="bg-white dark:bg-neutral-800 rounded-md shadow-sm border border-gray-200 dark:border-neutral-700 p-6">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Showing {{ clients.from }} to {{ clients.to }} of {{ clients.total }} clients
                        </div>
                        <div class="flex space-x-1">
                            <template v-for="link in clients.links" :key="link.label">
                                <component :is="link.url ? 'a' : 'span'" v-html="link.label" :href="link.url"
                                    class="px-3 py-2 text-sm rounded-xl transition-all duration-200" :class="{
                                        'bg-blue-600 text-white shadow-sm': link.active,
                                        'text-gray-400 cursor-not-allowed': !link.url,
                                        'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white': link.url && !link.active,
                                    }" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Client Modal -->
        <div v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
            @click.self="closeCreateModal">
            <div
                class="bg-white dark:bg-neutral-800 rounded-2xl w-full max-w-2xl max-h-[90vh] shadow-2xl border border-gray-200 dark:border-neutral-700 flex flex-col">
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-neutral-700 flex-shrink-0">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Add New Client</h2>
                    <button @click="closeCreateModal"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg transition-all duration-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto flex-1 min-h-0">
                    <form @submit.prevent="handleCreateSubmit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="create-name"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name</label>
                            <input id="create-name" v-model="createForm.name" required type="text"
                                placeholder="e.g. Acme Corp"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white border-gray-300 dark:border-neutral-700" />
                        </div>

                        <!-- Website -->
                        <div>
                            <label for="create-website"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Website</label>
                            <input id="create-website" v-model="createForm.website" required type="url"
                                placeholder="e.g. https://example.com"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white border-gray-300 dark:border-neutral-700" />
                        </div>

                        <!-- Preview URL -->
                        <div>
                            <label for="create-preview"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Preview
                                URL</label>
                            <input id="create-preview" v-model="createForm.preview_url" type="url"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white border-gray-300 dark:border-neutral-700" />
                        </div>

                        <!-- Brand Color -->
                        <div>
                            <label for="create-color"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Brand
                                Color</label>
                            <select id="create-color" v-model="createForm.color_palette_id"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white border-gray-300 dark:border-neutral-700"
                                required>
                                <option disabled value="">Select a color palette</option>
                                <option v-for="palette in colorPalettes" :key="palette.id" :value="palette.id">
                                    {{ palette.name }}
                                </option>
                            </select>

                            <!-- Color Preview -->
                            <div v-if="createForm.color_palette_id" class="mt-2 flex items-center gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Preview:</span>
                                <div v-if="colorPalettes.find(p => p.id == createForm.color_palette_id)"
                                    :style="{ backgroundColor: colorPalettes.find(p => p.id == createForm.color_palette_id)?.primary }"
                                    class="h-5 w-10 rounded-lg border border-gray-300 dark:border-neutral-600"></div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{colorPalettes.find(p => p.id == createForm.color_palette_id)?.primary}}
                                </span>
                            </div>
                        </div>

                        <!-- Logo Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Logo
                                (Image)</label>
                            <FilePond name="logo" :files="createFilePondFiles" @updatefiles="handleCreateFilePondUpdate"
                                :allowMultiple="false" :acceptedFileTypes="['image/*']"
                                :labelIdle="'Drag & Drop your logo or <span class=\'filepond--label-action\'>Browse</span>'"
                                :maxFiles="1" :imagePreviewHeight="170" class="mt-1" />
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex space-x-4 pt-4">
                            <button type="button" @click="closeCreateModal"
                                class="flex-1 rounded-xl bg-red-600 px-6 py-3 text-white shadow hover:bg-red-700 transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                :disabled="!createForm.name || !createForm.website || !createForm.color_palette_id"
                                class="flex-1 rounded-xl bg-green-600 px-6 py-3 text-white shadow hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                Create Client
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Client Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
            @click.self="closeEditModal">
            <div
                class="bg-white dark:bg-neutral-800 rounded-2xl w-full max-w-2xl max-h-[90vh] shadow-2xl border border-gray-200 dark:border-neutral-700 flex flex-col">
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-neutral-700 flex-shrink-0">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Edit Client</h2>
                    <button @click="closeEditModal"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg transition-all duration-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto flex-1 min-h-0">
                    <form @submit.prevent="handleEditSubmit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="edit-name"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name</label>
                            <input id="edit-name" v-model="editForm.name" required type="text"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white border-gray-300 dark:border-neutral-700" />
                        </div>

                        <!-- Website -->
                        <div>
                            <label for="edit-website"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Website</label>
                            <input id="edit-website" v-model="editForm.website" required type="url"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white border-gray-300 dark:border-neutral-700" />
                        </div>

                        <!-- Preview URL -->
                        <div>
                            <label for="edit-preview"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Preview
                                URL</label>
                            <input id="edit-preview" v-model="editForm.preview_url" type="url"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white border-gray-300 dark:border-neutral-700" />
                        </div>

                        <!-- Brand Color -->
                        <div>
                            <label for="edit-color"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Brand
                                Color</label>
                            <select id="edit-color" v-model="editForm.color_palette_id"
                                class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white border-gray-300 dark:border-neutral-700">
                                <option :value="null">Select a palette</option>
                                <option v-for="palette in colorPalettes" :key="palette.id" :value="palette.id">
                                    {{ palette.name }} ({{ palette.primary }})
                                </option>
                            </select>

                            <!-- Color Preview -->
                            <div v-if="editForm.color_palette_id" class="mt-2 flex items-center gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Selected:</span>
                                <div v-if="colorPalettes.find(p => p.id == editForm.color_palette_id)"
                                    :style="{ backgroundColor: colorPalettes.find(p => p.id == editForm.color_palette_id)?.primary }"
                                    class="h-6 w-12 rounded-lg border border-gray-300 dark:border-neutral-600"></div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{colorPalettes.find(p => p.id == editForm.color_palette_id)?.primary}}
                                </span>
                            </div>
                        </div>

                        <!-- Current Logo Display -->
                        <div v-if="logoPreview && !editForm.logo">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Current
                                Logo</label>
                            <div class="mb-4">
                                <img :src="logoPreview" alt="Current Logo" class="h-16 mx-auto rounded-lg" />
                            </div>
                        </div>

                        <!-- Logo Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                {{ logoPreview && !editForm.logo ? 'Upload New Logo' : 'Logo (Image)' }}
                            </label>
                            <FilePond name="logo" :files="editFilePondFiles" @updatefiles="handleEditFilePondUpdate"
                                :allowMultiple="false" :acceptedFileTypes="['image/*']"
                                :labelIdle="'Drag & Drop your logo or <span class=\'filepond--label-action\'>Browse</span>'"
                                :maxFiles="1" :imagePreviewHeight="170" class="mt-1" />
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex space-x-4 pt-4">
                            <button type="button" @click="closeEditModal"
                                class="flex-1 rounded-xl bg-red-600 px-6 py-3 text-white shadow hover:bg-red-700 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="!editForm.name || !editForm.website"
                                class="flex-1 rounded-xl bg-blue-600 px-6 py-3 text-white shadow hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                Update Client
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Image Viewer Modal -->
        <div v-if="showImageModal"
            class="fixed inset-0 flex items-center justify-center bg-white dark:bg-black bg-opacity-80 p-4" style="z-index: 999;"
            @click.self="closeImageModal">
            <div class="relative max-w-4xl w-full max-h-[90vh]">
                <button @click="closeImageModal"
                    class="absolute right-1 top-2 z-50 p-2 bg-black/40 hover:bg-black/60 rounded-full text-white">
                    <X class="w-5 h-5" />
                </button>
                <img :src="currentImage" alt="Preview"
                    class="w-full h-auto max-h-[25vh] object-contain rounded-lg mx-auto" />
            </div>
        </div>

    </AppLayout>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}
</style>