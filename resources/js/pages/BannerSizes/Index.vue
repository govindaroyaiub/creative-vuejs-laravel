<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Pencil, Trash2, X, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';

const page = usePage();
const bannerSizes = computed(() => page.props.bannerSizes);
const search = ref(page.props.search ?? ''); // Preserve search across pagination

watch(search, (value) => {
    router.get(route('banner-sizes-index'), { search: value }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
});

// Pagination function
const changePage = (url: string) => {
    if (url) {
        router.get(url, { search: search.value }, {
            preserveScroll: true,
            preserveState: true,
        });
    }
};

const editingId = ref<number | null>(null);
const editForm = ref({ width: 0, height: 0 });

const adding = ref(false);
const newForm = ref({ width: '', height: '' });

const startEditing = (size: any) => {
    editingId.value = size.id;
    editForm.value = { width: size.width, height: size.height };
};

const cancelEditing = () => {
    editingId.value = null;
};

const saveEdit = (id: number) => {
    router.put(route('banner-sizes-update', id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            Swal.fire({
                icon: 'info',
                title: 'Success!',
                text: 'Banner size updated successfully!',
                timer: 1000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                timerProgressBar: true,
            });
        },
        onError: () => {
            Swal.fire('Error!', 'Size Already Exists!', 'error');
        },
    });
};

const startAdding = () => {
    adding.value = true;
    newForm.value = { width: '', height: '' };
};

const cancelAdding = () => {
    adding.value = false;
};

const saveNew = () => {
    router.post(route('banner-sizes-create-post'), newForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            if (page.props.flash?.error) {
                Swal.fire('Error!', page.props.flash.error, 'error');
            } else {
                adding.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Banner size created successfully!',
                    timer: 1000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    timerProgressBar: true,
                });
            }
        },
    });
};

const deleteBannerSize = async (id: number) => {
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
        router.delete(route('banner-sizes-delete', id), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('Deleted!', 'Banner size deleted.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Could not delete.', 'error');
            },
        });
    }
};
</script>

<template>

    <Head title="Banner Sizes" />
    <AppLayout :breadcrumbs="[{ title: 'Banner Sizes', href: '/banner-sizes' }]">
        <div
            class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-black dark:via-black dark:to-black">
            <div class="p-4 sm:p-6">
                <div
                    class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-0 sm:justify-between">
                    <input v-model="search" placeholder="Search..."
                        class="w-full sm:max-w-xs rounded-2xl border px-4 py-2 dark:bg-neutral-800 dark:text-white" />
                    <button @click="startAdding"
                        class="rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700 group whitespace-nowrap">
                        <CirclePlus
                            class="mr-1 inline h-5 w-5 group-hover:rotate-90 transition-transform duration-200" />
                        Add Size
                    </button>
                </div>

                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto rounded-2xl shadow">
                    <table class="w-full rounded bg-white dark:bg-neutral-800 border">
                        <thead class="bg-gray-100 text-gray-700 dark:bg-neutral-900 dark:text-gray-300">
                            <tr class="text-center text-sm uppercase">
                                <th class="px-4 py-2">#</th>
                                <th class="px-4 py-2">Name</th>
                                <th class="px-4 py-2">Width</th>
                                <th class="px-4 py-2">Height</th>
                                <th class="px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- New Row -->
                            <tr v-if="adding" class="border-t text-center text-sm dark:border-neutral-900">
                                <td class="px-4 py-2">#</td>
                                <td class="px-4 py-2">
                                    <div v-if="adding">{{ newForm.width }}x{{ newForm.height }}</div>
                                </td>
                                <td class="px-4 py-2">
                                    <input v-model="newForm.width" type="number"
                                        class="w-20 rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                                </td>
                                <td class="px-4 py-2">
                                    <input v-model="newForm.height" type="number"
                                        class="w-20 rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                                </td>
                                <td class="px-4 py-2 space-x-2">
                                    <button @click="cancelAdding"
                                        class="text-gray-500 hover:underline text-sm">Cancel</button>
                                    <button @click="saveNew"
                                        class="text-green-600 hover:underline text-sm">Save</button>
                                </td>
                            </tr>

                            <!-- Existing Rows -->
                            <tr v-for="(size, index) in bannerSizes.data" :key="size.id"
                                class="border-t text-center text-sm uppercase dark:border-neutral-900 dark:hover:bg-neutral-700">
                                <td class="px-4 py-2">{{ index + 1 }}</td>
                                <td class="px-4 py-2">
                                    <div v-if="editingId !== size.id">{{ size.width }}x{{ size.height }}</div>
                                    <div v-else>{{ editForm.width }}x{{ editForm.height }}</div>
                                </td>

                                <td class="px-4 py-2">
                                    <div v-if="editingId !== size.id">{{ size.width }}</div>
                                    <input v-else v-model="editForm.width" type="number"
                                        class="w-20 rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                                </td>

                                <td class="px-4 py-2">
                                    <div v-if="editingId !== size.id">{{ size.height }}</div>
                                    <input v-else v-model="editForm.height" type="number"
                                        class="w-20 rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                                </td>

                                <td class="space-x-2 px-4 py-2">
                                    <template v-if="editingId === size.id">
                                        <button @click="cancelEditing"
                                            class="text-red-500 hover:underline text-sm">Cancel</button>
                                        <button @click="saveEdit(size.id)"
                                            class="text-blue-600 hover:underline text-sm">Update</button>
                                    </template>
                                    <template v-else>
                                        <button @click="startEditing(size)" class="text-blue-600 hover:text-blue-800">
                                            <Pencil class="inline h-5 w-5" />
                                        </button>
                                        <button @click="deleteBannerSize(size.id)"
                                            class="text-red-600 hover:text-red-800">
                                            <Trash2 class="inline h-5 w-5" />
                                        </button>
                                    </template>
                                </td>
                            </tr>

                            <tr v-if="bannerSizes.data.length === 0 && !adding">
                                <td colspan="5" class="px-4 py-4 text-center text-gray-500">No banner sizes found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden space-y-3">
                    <!-- Add New Card -->
                    <div v-if="adding"
                        class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-sm">New Banner Size</h3>
                            <button @click="cancelAdding"
                                class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Name</label>
                                <div class="text-sm font-medium">{{ newForm.width }}x{{ newForm.height }}</div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Width</label>
                                    <input v-model="newForm.width" type="number"
                                        class="w-full rounded-xl border px-3 py-2 text-sm dark:bg-neutral-800 dark:text-white" />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Height</label>
                                    <input v-model="newForm.height" type="number"
                                        class="w-full rounded-xl border px-3 py-2 text-sm dark:bg-neutral-800 dark:text-white" />
                                </div>
                            </div>
                            <div class="flex gap-2 pt-2">
                                <button @click="cancelAdding"
                                    class="flex-1 px-4 py-2 text-sm rounded-xl border border-gray-300 dark:border-neutral-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-neutral-700">
                                    Cancel
                                </button>
                                <button @click="saveNew"
                                    class="flex-1 px-4 py-2 text-sm rounded-xl bg-green-600 text-white hover:bg-green-700">
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Size Cards -->
                    <div v-for="(size, index) in bannerSizes.data" :key="size.id"
                        class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-4">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">#{{ index + 1
                                    }}</span>
                            </div>
                            <div v-if="editingId !== size.id" class="flex gap-2">
                                <button @click="startEditing(size)" class="text-blue-600 hover:text-blue-800 p-1">
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <button @click="deleteBannerSize(size.id)" class="text-red-600 hover:text-red-800 p-1">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <template v-if="editingId === size.id">
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Name</label>
                                    <div class="text-sm font-medium">{{ editForm.width }}x{{ editForm.height }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Width</label>
                                        <input v-model="editForm.width" type="number"
                                            class="w-full rounded-xl border px-3 py-2 text-sm dark:bg-neutral-800 dark:text-white" />
                                    </div>
                                    <div>
                                        <label
                                            class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Height</label>
                                        <input v-model="editForm.height" type="number"
                                            class="w-full rounded-xl border px-3 py-2 text-sm dark:bg-neutral-800 dark:text-white" />
                                    </div>
                                </div>
                                <div class="flex gap-2 pt-2">
                                    <button @click="cancelEditing"
                                        class="flex-1 px-4 py-2 text-sm rounded-xl border border-gray-300 dark:border-neutral-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-neutral-700">
                                        Cancel
                                    </button>
                                    <button @click="saveEdit(size.id)"
                                        class="flex-1 px-4 py-2 text-sm rounded-xl bg-blue-600 text-white hover:bg-blue-700">
                                        Update
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="space-y-2">
                                <div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Name</div>
                                    <div class="text-sm font-semibold uppercase">{{ size.width }}x{{ size.height }}
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 pt-2">
                                    <div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">Width</div>
                                        <div class="text-sm font-medium">{{ size.width }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">Height</div>
                                        <div class="text-sm font-medium">{{ size.height }}</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div v-if="bannerSizes.data.length === 0 && !adding"
                        class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-8 text-center">
                        <p class="text-gray-500">No banner sizes found.</p>
                    </div>
                </div>

                <!-- Pagination - Responsive -->
                <div v-if="bannerSizes.data.length && bannerSizes.links.length" class="mt-6 p-4">

                    <!-- Mobile/Tablet pagination (simplified) -->
                    <div class="lg:hidden">
                        <!-- Results Info -->
                        <div class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">
                            Showing {{ bannerSizes.from }} to {{ bannerSizes.to }} of {{ bannerSizes.total }} banner
                            sizes
                        </div>

                        <!-- Simple prev/next navigation -->
                        <div class="flex items-center justify-between gap-4">
                            <button @click="changePage(bannerSizes.prev_page_url)"
                                :disabled="!bannerSizes.prev_page_url"
                                class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2"
                                :class="bannerSizes.prev_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                <ChevronLeft class="w-4 h-4" />
                                Previous
                            </button>

                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                Page {{ bannerSizes.current_page }} of {{ bannerSizes.last_page }}
                            </span>

                            <button @click="changePage(bannerSizes.next_page_url)"
                                :disabled="!bannerSizes.next_page_url"
                                class="px-4 py-2 text-sm rounded-xl transition-all duration-200 flex items-center gap-2"
                                :class="bannerSizes.next_page_url
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
                            Showing {{ bannerSizes.from }} to {{ bannerSizes.to }} of {{ bannerSizes.total }} banner
                            sizes
                        </div>

                        <!-- Pagination Controls -->
                        <div class="flex items-center space-x-2">
                            <!-- Previous Button -->
                            <button @click="changePage(bannerSizes.prev_page_url)"
                                :disabled="!bannerSizes.prev_page_url"
                                class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center"
                                :class="bannerSizes.prev_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                <ChevronLeft class="w-4 h-4 mr-1" />
                                Previous
                            </button>

                            <!-- Page Numbers -->
                            <div class="flex items-center space-x-1">
                                <template v-for="link in bannerSizes.links.slice(1, -1)" :key="link.label">
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
                            <button @click="changePage(bannerSizes.next_page_url)"
                                :disabled="!bannerSizes.next_page_url"
                                class="px-3 py-2 text-sm rounded-lg transition-all duration-200 flex items-center"
                                :class="bannerSizes.next_page_url
                                    ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                    : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                                Next
                                <ChevronRight class="w-4 h-4 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>