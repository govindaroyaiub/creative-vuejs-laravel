<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { Pencil, Trash2, CirclePlus, Search, ExternalLink, Globe, Eye, Palette, Building2, Users, Filter, SortAsc, SortDesc } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, computed, onMounted, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Clients', href: '/clients' }];

const page = usePage();
const clients = computed(() => page.props.clients);
const flash = computed(() => page.props.flash);
const search = ref(page.props.search ?? '');
const isLoading = ref(false);
const sortField = ref('name');
const sortDirection = ref('asc');

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

const sortBy = (field: string) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    // Implement sorting logic here if needed
};

const getStatusColor = (client: any) => {
    if (client.preview_url) return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
    return 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400';
};

const getStatusText = (client: any) => {
    return client.preview_url ? 'Active' : 'Setup Required';
};

onMounted(() => {
    if (flash.value?.success) {
        Swal.fire({
            title: 'Success!',
            text: flash.value.success,
            icon: 'success',
            timer: 3000,
            showConfirmButton: false,
            customClass: { popup: 'rounded-md' }
        });
    }
});

const totalClients = computed(() => clients.value?.total || 0);
const activeClients = computed(() =>
    clients.value?.data?.filter((client: any) => client.preview_url).length || 0
);
</script>

<template>

    <Head title="Clients Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-black dark:via-black dark:to-black">
            <div class="p-6 space-y-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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

                    <div
                        class="bg-white dark:bg-neutral-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Clients</p>
                                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ activeClients }}</p>
                            </div>
                            <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-md">
                                <Eye class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white dark:bg-neutral-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Setup Required</p>
                                <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                                    {{ totalClients - activeClients }}
                                </p>
                            </div>
                            <div class="p-3 bg-yellow-100 dark:bg-yellow-900/50 rounded-md">
                                <Globe class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="rounded-2xl shadow-sm flex items-center space-x-4">
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

                    <Link :href="route('clients-create')"
                        class="w-1/6 inline-flex justify-center items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-sm hover:shadow-md group">
                    <CirclePlus class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-200" />
                    Add Client
                    </Link>
                </div>

                <!-- Clients Grid -->
                <div v-if="clients?.data?.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="(client, index) in clients.data" :key="client.id"
                        class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg hover:border-blue-200 dark:hover:border-neutral-600 transition-all duration-200 overflow-hidden group">
                        <!-- Card Header -->
                        <div class="py-2 pb-4">
                            <div class="flex items-start justify-evenly">
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="w-48 h-12 bg-gradient-to-r from-transparent to-transparent rounded-md flex items-center justify-left">
                                        <img v-if="client.logo" :src="`/logos/${client.logo}`"
                                            :alt="client.name + ' logo'" class="w-20 object-contain rounded" />
                                        <Building2 v-else class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white text-base">{{ client.name
                                            }}</h3>
                                        <div class="flex items-center mt-1">
                                            <span :class="getStatusColor(client)"
                                                class="px-2 py-1 rounded-full text-xs font-medium">
                                                {{ getStatusText(client) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="px-6 pb-4 pt-2 space-y-4">
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
                                <Link :href="route('clients-edit', client.id)"
                                    class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group/edit"
                                    title="Edit Client">
                                <Pencil class="w-4 h-4 group-hover/edit:scale-110 transition-transform duration-200" />
                                </Link>
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
                    <Link v-if="!search" :href="route('clients-create')"
                        class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200">
                    <CirclePlus class="w-5 h-5 mr-2" />
                    Add Your First Client
                    </Link>
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