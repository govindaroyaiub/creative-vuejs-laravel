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
            popup: 'rounded-lg',
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
                    customClass: { popup: 'rounded-lg' }
                });
            },
            onError: () => {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to delete client.',
                    icon: 'error',
                    customClass: { popup: 'rounded-lg' }
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
            customClass: { popup: 'rounded-lg' }
        });
    }
});

const totalClients = computed(() => clients.value?.total || 0);
</script>

<template>

    <Head title="Clients Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-6 space-y-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                    <div class="bg-white dark:bg-black border-2 border-[#E8E8E8] dark:border-[#222222] rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p
                                    class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">
                                    Total Clients</p>
                                <p class="text-3xl font-bold font-mono tabular-nums text-black dark:text-white">{{
                                    totalClients }}</p>
                            </div>
                            <div
                                class="p-3 bg-[#F5F5F5] dark:bg-[#111111] border-2 border-[#E8E8E8] dark:border-[#222222] rounded-lg">
                                <Users class="w-6 h-6 text-black dark:text-white" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="flex items-center space-x-4">
                    <div class="flex flex-col sm:flex-row gap-4 w-full">
                        <div class="relative flex-1">
                            <Search
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 text-[#666666] dark:text-[#999999] w-4 h-4" />
                            <input v-model="search" placeholder="Search clients by name, website..."
                                class="w-full pl-10 pr-4 py-3 border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg bg-white dark:bg-[#111111] text-black dark:text-white placeholder-[#666666] dark:placeholder-[#999999] focus:outline-none focus:border-black dark:focus:border-white transition-colors" />
                            <div v-if="isLoading" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <div
                                    class="animate-spin rounded-full h-4 w-4 border-2 border-blue-600 border-t-transparent">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button @click="openCreateModal"
                        class="w-1/5 inline-flex items-center justify-center px-6 py-3 bg-black dark:bg-white text-white dark:text-black rounded-full hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white transition-colors uppercase font-mono tracking-wider text-sm group">
                        <CirclePlus class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-200" />
                        Add Client
                    </button>
                </div>

                <!-- Clients Grid -->
                <div v-if="clients?.data?.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="(client, index) in clients.data" :key="client.id"
                        class="bg-white dark:bg-black border-2 border-[#E8E8E8] dark:border-[#222222] rounded-lg hover:border-black hover:dark:border-white transition-all duration-200 overflow-hidden group">
                        <!-- Card Header -->
                        <div class="py-2 px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center justify-start">
                                    <img v-if="client.logo" :src="`/logos/${client.logo}`" :alt="client.name + ' logo'"
                                        @click="openImageModal(`/logos/${client.logo}`)"
                                        class="h-20 w-40 aspect-auto object-contain rounded-lg bg-[#F5F5F5] p-1 dark:bg-[#F0F0F0] border-2 border-[#E8E8E8] dark:border-[#222222] cursor-pointer hover:scale-105 transition-transform duration-150" />
                                    <Building2 v-else class="w-6 h-6 text-black dark:text-white" />
                                </div>
                                <div class="flex items-center justify-end">
                                    <h1
                                        class="font-semibold text-black dark:text-white text-lg uppercase font-mono tracking-wider text-end">
                                        {{ client.name}}</h1>
                                </div>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="px-6 pb-2 space-y-4">
                            <!-- Website -->
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">Website:</span>
                                <a :href="client.website" target="_blank"
                                    class="text-xs font-mono text-black dark:text-white hover:underline flex items-center group/link">
                                    {{ client.website?.replace(/^https?:\/\//, '') || 'Not set' }}
                                    <ExternalLink
                                        class="w-3 h-3 ml-1 group-hover/link:translate-x-0.5 group-hover/link:-translate-y-0.5 transition-transform duration-200" />
                                </a>
                            </div>

                            <!-- Preview URL -->
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">Preview:</span>
                                <a v-if="client.preview_url" :href="client.preview_url" target="_blank"
                                    class="text-xs font-mono text-black dark:text-white hover:underline flex items-center group/link">
                                    Visit Preview
                                    <Eye
                                        class="w-3 h-3 ml-1 group-hover/link:scale-110 transition-transform duration-200" />
                                </a>
                                <span v-else class="text-xs text-[#999999] uppercase font-mono">Not configured</span>
                            </div>

                            <!-- Brand Color -->
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">Brand
                                    Color:</span>
                                <button v-if="client.color_palette?.primary"
                                    @click="copyColor(client.color_palette.primary)"
                                    class="flex items-center space-x-2 group/color hover:scale-105 transition-transform duration-200"
                                    :title="`Click to copy: ${client.color_palette.primary}`">
                                    <div :style="{ backgroundColor: client.color_palette.primary }"
                                        class="w-6 h-6 rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333]">
                                    </div>
                                    <span
                                        class="text-xs text-[#666666] dark:text-[#999999] font-mono group-hover/color:text-black group-hover/color:dark:text-white transition-colors">
                                        {{ client.color_palette.primary }}
                                    </span>
                                </button>
                                <span v-else class="text-xs text-[#999999] uppercase font-mono flex items-center">
                                    <Palette class="w-4 h-4 mr-1" />
                                    Not set
                                </span>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div
                            class="px-6 py-4 bg-[#F5F5F5] dark:bg-[#111111] border-t-2 border-[#E8E8E8] dark:border-[#222222]">
                            <div class="flex justify-end space-x-2">
                                <button @click="openEditModal(client)"
                                    class="p-2 text-black dark:text-white hover:bg-white hover:dark:bg-black border-2 border-transparent hover:border-black hover:dark:border-white rounded-full transition-all duration-200 group/edit"
                                    title="Edit Client">
                                    <Pencil
                                        class="w-4 h-4 group-hover/edit:scale-110 transition-transform duration-200" />
                                </button>
                                <button @click="deleteClient(client.id, client.name)"
                                    class="p-2 text-[#D71921] hover:bg-red-100 dark:hover:bg-[#D71921]/20 border-2 border-transparent hover:border-[#D71921] rounded-full transition-all duration-200 group/delete"
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
                    class="bg-white dark:bg-black border-2 border-[#E8E8E8] dark:border-[#222222] rounded-lg p-12 text-center">
                    <div
                        class="w-20 h-20 mx-auto bg-[#F5F5F5] dark:bg-[#111111] border-2 border-[#E8E8E8] dark:border-[#222222] rounded-full flex items-center justify-center mb-6">
                        <Building2 class="w-10 h-10 text-[#666666] dark:text-[#999999]" />
                    </div>
                    <h3
                        class="text-xl font-semibold text-black dark:text-white mb-2 uppercase font-mono tracking-wider">
                        No clients found</h3>
                    <p class="text-[#666666] dark:text-[#999999] mb-6 max-w-md mx-auto">
                        {{ search ? 'Try adjusting your search terms' : 'Get started by adding your first client' }}
                    </p>
                    <button v-if="!search" @click="openCreateModal"
                        class="inline-flex items-center px-6 py-3 bg-black dark:bg-white text-white dark:text-black rounded-full hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white transition-colors uppercase font-mono tracking-wider text-sm">
                        <CirclePlus class="w-5 h-5 mr-2" />
                        Add Your First Client
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="clients?.links?.length > 3"
                    class="bg-white dark:bg-black border-2 border-[#E8E8E8] dark:border-[#222222] rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">
                            Showing {{ clients.from }} to {{ clients.to }} of {{ clients.total }} clients
                        </div>
                        <div class="flex space-x-1">
                            <template v-for="link in clients.links" :key="link.label">
                                <component :is="link.url ? 'a' : 'span'" v-html="link.label" :href="link.url"
                                    class="px-3 py-2 text-xs rounded-full uppercase font-mono tracking-wider transition-all duration-200"
                                    :class="{
                                        'bg-black dark:bg-white text-white dark:text-black border-2 border-black dark:border-white': link.active,
                                        'text-[#999999] cursor-not-allowed': !link.url,
                                        'text-black dark:text-white border-2 border-[#CCCCCC] dark:border-[#333333] hover:border-black hover:dark:border-white': link.url && !link.active,
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
                class="bg-white dark:bg-black border-2 border-[#E8E8E8] dark:border-[#222222] rounded-lg w-full max-w-2xl max-h-[90vh] flex flex-col">
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between p-6 border-b-2 border-[#E8E8E8] dark:border-[#222222] flex-shrink-0">
                    <h2 class="text-xl font-bold text-black dark:text-white uppercase font-mono tracking-wider">Add New
                        Client
                    </h2>
                    <button @click="closeCreateModal"
                        class="p-2 text-[#666666] dark:text-[#999999] hover:text-black hover:dark:text-white hover:bg-[#F5F5F5] hover:dark:bg-[#111111] rounded-full transition-all duration-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto flex-1 min-h-0">
                    <form @submit.prevent="handleCreateSubmit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="create-name"
                                class="block text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">Name</label>
                            <input id="create-name" v-model="createForm.name" required type="text"
                                placeholder="e.g. Acme Corp"
                                class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                        </div>

                        <!-- Website -->
                        <div>
                            <label for="create-website"
                                class="block text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">Website</label>
                            <input id="create-website" v-model="createForm.website" required type="url"
                                placeholder="e.g. https://example.com"
                                class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                        </div>

                        <!-- Preview URL -->
                        <div>
                            <label for="create-preview"
                                class="block text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">Preview
                                URL</label>
                            <input id="create-preview" v-model="createForm.preview_url" type="url"
                                class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                        </div>

                        <!-- Brand Color -->
                        <div>
                            <label for="create-color"
                                class="block text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">Brand
                                Color</label>
                            <select id="create-color" v-model="createForm.color_palette_id"
                                class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors"
                                required>
                                <option disabled value="">Select a color palette</option>
                                <option v-for="palette in colorPalettes" :key="palette.id" :value="palette.id">
                                    {{ palette.name }}
                                </option>
                            </select>

                            <!-- Color Preview -->
                            <div v-if="createForm.color_palette_id" class="mt-2 flex items-center gap-2">
                                <span
                                    class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">Preview:</span>
                                <div v-if="colorPalettes.find(p => p.id == createForm.color_palette_id)"
                                    :style="{ backgroundColor: colorPalettes.find(p => p.id == createForm.color_palette_id)?.primary }"
                                    class="h-5 w-10 rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333]"></div>
                                <span class="text-xs font-mono text-[#666666] dark:text-[#999999]">
                                    {{colorPalettes.find(p => p.id == createForm.color_palette_id)?.primary}}
                                </span>
                            </div>
                        </div>

                        <!-- Logo Upload -->
                        <div>
                            <label
                                class="block text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-2">Logo
                                (Image)</label>
                            <FilePond name="logo" :files="createFilePondFiles" @updatefiles="handleCreateFilePondUpdate"
                                :allowMultiple="false" :acceptedFileTypes="['image/*']"
                                :labelIdle="'Drag & Drop your logo or <span class=\'filepond--label-action\'>Browse</span>'"
                                :maxFiles="1" :imagePreviewHeight="170" class="mt-1" />
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex space-x-4 pt-4">
                            <button type="button" @click="closeCreateModal"
                                class="flex-1 rounded-full bg-[#D71921] hover:bg-red-700 px-6 py-3 text-white uppercase font-mono tracking-wider text-sm transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                :disabled="!createForm.name || !createForm.website || !createForm.color_palette_id"
                                class="flex-1 rounded-full bg-black dark:bg-white text-white dark:text-black px-6 py-3 hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white disabled:opacity-50 disabled:cursor-not-allowed uppercase font-mono tracking-wider text-sm transition-colors">
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
                class="bg-white dark:bg-black border-2 border-[#E8E8E8] dark:border-[#222222] rounded-lg w-full max-w-2xl max-h-[90vh] flex flex-col">
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between p-6 border-b-2 border-[#E8E8E8] dark:border-[#222222] flex-shrink-0">
                    <h2 class="text-xl font-bold text-black dark:text-white uppercase font-mono tracking-wider">Edit
                        Client</h2>
                    <button @click="closeEditModal"
                        class="p-2 text-[#666666] dark:text-[#999999] hover:text-black hover:dark:text-white hover:bg-[#F5F5F5] hover:dark:bg-[#111111] rounded-full transition-all duration-200">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto flex-1 min-h-0">
                    <form @submit.prevent="handleEditSubmit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="edit-name"
                                class="block text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">Name</label>
                            <input id="edit-name" v-model="editForm.name" required type="text"
                                class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                        </div>

                        <!-- Website -->
                        <div>
                            <label for="edit-website"
                                class="block text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">Website</label>
                            <input id="edit-website" v-model="editForm.website" required type="url"
                                class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                        </div>

                        <!-- Preview URL -->
                        <div>
                            <label for="edit-preview"
                                class="block text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">Preview
                                URL</label>
                            <input id="edit-preview" v-model="editForm.preview_url" type="url"
                                class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                        </div>

                        <!-- Brand Color -->
                        <div>
                            <label for="edit-color"
                                class="block text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">Brand
                                Color</label>
                            <select id="edit-color" v-model="editForm.color_palette_id"
                                class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors">
                                <option :value="null">Select a palette</option>
                                <option v-for="palette in colorPalettes" :key="palette.id" :value="palette.id">
                                    {{ palette.name }} ({{ palette.primary }})
                                </option>
                            </select>

                            <!-- Color Preview -->
                            <div v-if="editForm.color_palette_id" class="mt-2 flex items-center gap-2">
                                <span
                                    class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">Selected:</span>
                                <div v-if="colorPalettes.find(p => p.id == editForm.color_palette_id)"
                                    :style="{ backgroundColor: colorPalettes.find(p => p.id == editForm.color_palette_id)?.primary }"
                                    class="h-6 w-12 rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333]"></div>
                                <span class="text-xs font-mono text-[#666666] dark:text-[#999999]">
                                    {{colorPalettes.find(p => p.id == editForm.color_palette_id)?.primary}}
                                </span>
                            </div>
                        </div>

                        <!-- Current Logo Display -->
                        <div v-if="logoPreview && !editForm.logo">
                            <label
                                class="block text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-2">Current
                                Logo</label>
                            <div class="mb-4">
                                <img :src="logoPreview" alt="Current Logo"
                                    class="h-16 mx-auto rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]" />
                            </div>
                        </div>

                        <!-- Logo Upload -->
                        <div>
                            <label
                                class="block text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-2">
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
                                class="flex-1 rounded-full bg-[#D71921] hover:bg-red-700 px-6 py-3 text-white uppercase font-mono tracking-wider text-sm transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="!editForm.name || !editForm.website"
                                class="flex-1 rounded-full bg-black dark:bg-white text-white dark:text-black px-6 py-3 hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white disabled:opacity-50 disabled:cursor-not-allowed uppercase font-mono tracking-wider text-sm transition-colors">
                                Update Client
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Image Viewer Modal -->
        <div v-if="showImageModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-90 p-4"
            style="z-index: 999;" @click.self="closeImageModal">
            <div class="relative max-w-4xl w-full max-h-[90vh]">
                <button @click="closeImageModal"
                    class="absolute right-1 top-2 z-50 p-2 bg-black/60 hover:bg-black/80 rounded-full text-white border-2 border-white transition-colors">
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