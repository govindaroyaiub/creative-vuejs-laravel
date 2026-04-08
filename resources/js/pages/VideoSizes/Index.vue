<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Pencil, Trash2, LoaderCircle, ChevronLeft, ChevronRight, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Video Sizes', href: '/video-sizes' }];

const page = usePage();
const videoSizes = computed(() => page.props.videoSizes ?? { data: [], links: [] });
const search = ref(page.props.search ?? ''); // sync from backend

watch(search, (value) => {
    router.get(route('video-sizes-index'), { search: value }, {
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

const adding = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);
const newSize = ref({ name: '', width: '', height: '' });
const editedSize = ref({ name: '', width: '', height: '' });

const deleteVideoSize = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        customClass: { popup: 'rounded-lg' }
    });

    if (result.isConfirmed) {
        router.delete(route('video-sizes-delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire({
                title: 'Deleted!',
                text: 'Video size deleted successfully.',
                icon: 'success',
                customClass: { popup: 'rounded-lg' }
            }),
            onError: () => Swal.fire({
                title: 'Error!',
                text: 'Failed to delete video size.',
                icon: 'error',
                customClass: { popup: 'rounded-lg' }
            }),
        });
    }
};

const startEdit = (size: any) => {
    editingId.value = size.id;
    editedSize.value = { name: size.name, width: size.width, height: size.height };
};

const cancelEdit = () => {
    editingId.value = null;
    editedSize.value = { name: '', width: '', height: '' };
};

const saveEdit = async (id: number) => {
    saving.value = true;
    router.put(
        route('video-sizes-update', id),
        { ...editedSize.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    icon: 'info',
                    title: 'Success!',
                    text: 'Video Size created successfully!',
                    timer: 1000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    timerProgressBar: true,
                    customClass: { popup: 'rounded-lg' }
                });
                cancelEdit();
            },
            onError: () => {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to update video size.',
                    icon: 'error',
                    customClass: { popup: 'rounded-lg' }
                });
            },
            onFinish: () => {
                saving.value = false;
            },
        }
    );
};

const saveNewSize = async () => {
    if (!newSize.value.name || !newSize.value.width || !newSize.value.height) return;

    saving.value = true;

    router.post(
        route('video-sizes-create-post'),
        { ...newSize.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Video Size created successfully!',
                    timer: 1000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    timerProgressBar: true,
                });
                newSize.value = { name: '', width: '', height: '' };
                adding.value = false;
            },
            onError: () => {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to add video size.',
                    icon: 'error',
                    customClass: { popup: 'rounded-lg' }
                });
            },
            onFinish: () => {
                saving.value = false;
            },
        }
    );
};
</script>

<template>

    <Head title="Video Sizes" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 sm:p-6">
                <!-- Search & Add -->
                <div
                    class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-0 sm:justify-between">
                    <input v-model="search" placeholder="Search..."
                        class="w-full sm:max-w-xs rounded-full border-2 border-[#CCCCCC] dark:border-[#333333] px-4 py-2 bg-white dark:bg-white text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                    <button @click="adding = true" v-if="!adding && editingId === null"
                        class="rounded-full bg-black dark:bg-white text-white dark:text-black px-4 py-2 hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white group whitespace-nowrap uppercase font-mono tracking-wider text-sm transition-colors">
                        <CirclePlus
                            class="mr-1 inline h-5 w-5 group-hover:rotate-90 transition-transform duration-200" />
                        Add Size
                    </button>
                </div>

                <!-- Desktop Table -->
                <div class="hidden md:block rounded-lg overflow-x-auto border-2 border-[#E8E8E8] dark:border-[#222222]">
                    <table class="w-full bg-white dark:bg-[#111111]">
                        <thead class="bg-[#F5F5F5] dark:bg-[#0A0A0A] text-black dark:text-white">
                            <tr class="text-center text-xs uppercase font-mono tracking-wider">
                                <th class="border-b border-[#E8E8E8] dark:border-[#222222] px-4 py-3">#</th>
                                <th class="border-b border-[#E8E8E8] dark:border-[#222222] px-4 py-3">Name</th>
                                <th class="border-b border-[#E8E8E8] dark:border-[#222222] px-2 py-3">Width</th>
                                <th class="border-b border-[#E8E8E8] dark:border-[#222222] px-2 py-3">Height</th>
                                <th class="border-b border-[#E8E8E8] dark:border-[#222222] px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Add Row -->
                            <tr v-if="adding" class="text-center">
                                <td class="border-b border-[#E8E8E8] dark:border-[#222222] px-4 py-3">#</td>
                                <td class="border-b border-[#E8E8E8] dark:border-[#222222] px-4 py-3">
                                    <input v-model="newSize.name" placeholder="Name"
                                        class="w-full max-w-[200px] rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-2 py-1 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                                </td>
                                <td class="border-b border-[#E8E8E8] dark:border-[#222222] px-2 py-3">
                                    <input v-model="newSize.width" type="number" placeholder="Width"
                                        class="w-full max-w-[100px] rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-2 py-1 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </td>
                                <td class="border-b border-[#E8E8E8] dark:border-[#222222] px-2 py-3">
                                    <input v-model="newSize.height" type="number" placeholder="Height"
                                        class="w-full max-w-[100px] rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-2 py-1 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </td>
                                <td class="border-b border-[#E8E8E8] dark:border-[#222222] px-4 py-3 space-x-2">
                                    <button @click="adding = false" :disabled="saving"
                                        class="rounded-full px-4 py-1 text-xs bg-[#D71921] hover:bg-red-700 text-white uppercase font-mono tracking-wider transition-colors">Cancel</button>
                                    <button @click="saveNewSize" :disabled="saving"
                                        class="rounded-full px-4 py-1 text-xs bg-black dark:bg-white text-white dark:text-black hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white uppercase font-mono tracking-wider transition-colors inline-flex items-center gap-1">
                                        <LoaderCircle v-if="saving" class="h-3 w-3 animate-spin" />
                                        <span v-else>Save</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Existing Rows -->
                            <tr v-for="(size, index) in videoSizes.data" :key="size.id"
                                class="border-t border-[#E8E8E8] dark:border-[#222222] text-center text-sm hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] transition-colors">
                                <td class="px-4 py-3 text-[#666666] dark:text-[#999999] tabular-nums">{{ index + 1 }}
                                </td>

                                <template v-if="editingId === size.id">
                                    <td class="px-4 py-3">
                                        <input v-model="editedSize.name"
                                            class="w-full max-w-[200px] rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-2 py-1 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                                    </td>
                                    <td class="px-2 py-3">
                                        <input v-model="editedSize.width" type="number"
                                            class="w-full max-w-[100px] rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-2 py-1 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                    </td>
                                    <td class="px-2 py-3">
                                        <input v-model="editedSize.height" type="number"
                                            class="w-full max-w-[100px] rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-2 py-1 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                    </td>
                                    <td class="flex justify-center gap-2 px-4 py-3">
                                        <button @click="cancelEdit" :disabled="saving"
                                            class="rounded-full px-4 py-1 text-xs bg-[#D71921] hover:bg-red-700 text-white uppercase font-mono tracking-wider transition-colors">Cancel</button>
                                        <button @click="saveEdit(size.id)" :disabled="saving"
                                            class="rounded-full px-4 py-1 text-xs bg-black dark:bg-white text-white dark:text-black hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white uppercase font-mono tracking-wider transition-colors">Update</button>
                                    </td>
                                </template>

                                <template v-else>
                                    <td class="px-4 py-3 uppercase font-mono font-semibold">{{ size.name }}</td>
                                    <td class="px-2 py-3 tabular-nums">{{ size.width }}</td>
                                    <td class="px-2 py-3 tabular-nums">{{ size.height }}</td>
                                    <td class="space-x-2 px-4 py-3">
                                        <button @click="startEdit(size)"
                                            class="text-black dark:text-white hover:text-[#666666] dark:hover:text-[#999999] transition-colors">
                                            <Pencil class="inline h-5 w-5" stroke-width="1.5" />
                                        </button>
                                        <button @click="deleteVideoSize(size.id)"
                                            class="text-[#D71921] hover:text-red-700 transition-colors">
                                            <Trash2 class="inline h-5 w-5" stroke-width="1.5" />
                                        </button>
                                    </td>
                                </template>
                            </tr>

                            <tr v-if="videoSizes.data.length === 0 && !adding">
                                <td colspan="5"
                                    class="px-4 py-8 text-center text-[#666666] dark:text-[#999999] uppercase font-mono tracking-wider text-sm">
                                    No
                                    video sizes found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden space-y-3">
                    <!-- Add New Card -->
                    <div v-if="adding"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-sm uppercase font-mono tracking-wider">New Video Size</h3>
                            <button @click="adding = false" :disabled="saving"
                                class="text-[#666666] dark:text-[#999999] hover:text-black hover:dark:text-white transition-colors">
                                <X class="h-5 w-5" stroke-width="1.5" />
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label
                                    class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1 block">Name</label>
                                <input v-model="newSize.name" placeholder="Name"
                                    class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1 block">Width</label>
                                    <input v-model="newSize.width" type="number" placeholder="Width"
                                        class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </div>
                                <div>
                                    <label
                                        class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1 block">Height</label>
                                    <input v-model="newSize.height" type="number" placeholder="Height"
                                        class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </div>
                            </div>
                            <div class="flex gap-2 pt-2">
                                <button @click="adding = false" :disabled="saving"
                                    class="flex-1 px-4 py-2 text-sm rounded-full bg-[#D71921] hover:bg-red-700 text-white uppercase font-mono tracking-wider transition-colors">
                                    Cancel
                                </button>
                                <button @click="saveNewSize" :disabled="saving"
                                    class="flex-1 px-4 py-2 text-sm rounded-full bg-black dark:bg-white text-white dark:text-black hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white uppercase font-mono tracking-wider transition-colors flex items-center justify-center">
                                    <LoaderCircle v-if="saving" class="h-4 w-4 animate-spin" />
                                    <span v-else>Save</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Size Cards -->
                    <div v-for="(size, index) in videoSizes.data" :key="size.id"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] hover:border-black hover:dark:border-white p-4 transition-colors">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-[#666666] dark:text-[#999999] tabular-nums">#{{
                                    index + 1
                                }}</span>
                            </div>
                            <div v-if="editingId !== size.id" class="flex gap-2">
                                <button @click="startEdit(size)"
                                    class="text-black dark:text-white hover:text-[#666666] dark:hover:text-[#999999] p-1 transition-colors">
                                    <Pencil class="h-4 w-4" stroke-width="1.5" />
                                </button>
                                <button @click="deleteVideoSize(size.id)"
                                    class="text-[#D71921] hover:text-red-700 p-1 transition-colors">
                                    <Trash2 class="h-4 w-4" stroke-width="1.5" />
                                </button>
                            </div>
                        </div>

                        <template v-if="editingId === size.id">
                            <div class="space-y-3">
                                <div>
                                    <label
                                        class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1 block">Name</label>
                                    <input v-model="editedSize.name"
                                        class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label
                                            class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1 block">Width</label>
                                        <input v-model="editedSize.width" type="number"
                                            class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                    </div>
                                    <div>
                                        <label
                                            class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1 block">Height</label>
                                        <input v-model="editedSize.height" type="number"
                                            class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                    </div>
                                </div>
                                <div class="flex gap-2 pt-2">
                                    <button @click="cancelEdit" :disabled="saving"
                                        class="flex-1 px-4 py-2 text-sm rounded-full bg-[#D71921] hover:bg-red-700 text-white uppercase font-mono tracking-wider transition-colors">
                                        Cancel
                                    </button>
                                    <button @click="saveEdit(size.id)" :disabled="saving"
                                        class="flex-1 px-4 py-2 text-sm rounded-full bg-black dark:bg-white text-white dark:text-black hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white uppercase font-mono tracking-wider transition-colors">
                                        Update
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="space-y-2">
                                <div>
                                    <div
                                        class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">
                                        Name</div>
                                    <div class="text-sm font-semibold uppercase font-mono">{{ size.name }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 pt-2">
                                    <div>
                                        <div
                                            class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">
                                            Width</div>
                                        <div class="text-sm font-medium tabular-nums">{{ size.width }}</div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">
                                            Height</div>
                                        <div class="text-sm font-medium tabular-nums">{{ size.height }}</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div v-if="videoSizes.data.length === 0 && !adding"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-8 text-center">
                        <p class="text-[#666666] dark:text-[#999999] uppercase font-mono tracking-wider text-sm">No
                            video sizes found.
                        </p>
                    </div>
                </div>

                <!-- Pagination - Responsive -->
                <div v-if="videoSizes.data.length > 0 && videoSizes.links.length" class="mt-6 p-4">

                    <!-- Mobile/Tablet pagination (simplified) -->
                    <div class="lg:hidden">
                        <!-- Results Info -->
                        <div
                            class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] text-center mb-3">
                            Showing <span class="tabular-nums">{{ videoSizes.from }}</span> to <span
                                class="tabular-nums">{{
                                    videoSizes.to }}</span> of <span class="tabular-nums">{{ videoSizes.total }}</span>
                            video sizes
                        </div>

                        <!-- Simple prev/next navigation -->
                        <div class="flex items-center justify-between gap-4">
                            <button @click="changePage(videoSizes.prev_page_url)" :disabled="!videoSizes.prev_page_url"
                                class="px-4 py-2 text-xs rounded-full transition-colors uppercase font-mono tracking-wider flex items-center gap-2"
                                :class="videoSizes.prev_page_url
                                    ? 'text-black dark:text-white hover:bg-black hover:dark:bg-white hover:text-white hover:dark:text-black border-2 border-black dark:border-white'
                                    : 'text-[#CCCCCC] dark:text-[#333333] cursor-not-allowed border-2 border-[#CCCCCC] dark:border-[#333333]'">
                                <ChevronLeft class="w-4 h-4" stroke-width="1.5" />
                                Previous
                            </button>

                            <span class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">
                                Page <span class="tabular-nums">{{ videoSizes.current_page }}</span> of <span
                                    class="tabular-nums">{{
                                        videoSizes.last_page }}</span>
                            </span>

                            <button @click="changePage(videoSizes.next_page_url)" :disabled="!videoSizes.next_page_url"
                                class="px-4 py-2 text-xs rounded-full transition-colors uppercase font-mono tracking-wider flex items-center gap-2"
                                :class="videoSizes.next_page_url
                                    ? 'text-black dark:text-white hover:bg-black hover:dark:bg-white hover:text-white hover:dark:text-black border-2 border-black dark:border-white'
                                    : 'text-[#CCCCCC] dark:text-[#333333] cursor-not-allowed border-2 border-[#CCCCCC] dark:border-[#333333]'">
                                Next
                                <ChevronRight class="w-4 h-4" stroke-width="1.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Desktop pagination (full features) -->
                    <div class="hidden lg:flex items-center justify-between">
                        <!-- Results Info -->
                        <div class="text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999]">
                            Showing <span class="tabular-nums">{{ videoSizes.from }}</span> to <span
                                class="tabular-nums">{{
                                    videoSizes.to }}</span> of <span class="tabular-nums">{{ videoSizes.total }}</span>
                            video sizes
                        </div>

                        <!-- Pagination Controls -->
                        <div class="flex items-center space-x-2">
                            <!-- Previous Button -->
                            <button @click="changePage(videoSizes.prev_page_url)" :disabled="!videoSizes.prev_page_url"
                                class="px-4 py-2 text-xs rounded-full transition-colors flex items-center uppercase font-mono tracking-wider"
                                :class="videoSizes.prev_page_url
                                    ? 'text-black dark:text-white hover:bg-black hover:dark:bg-white hover:text-white hover:dark:text-black border-2 border-black dark:border-white'
                                    : 'text-[#CCCCCC] dark:text-[#333333] cursor-not-allowed border-2 border-[#CCCCCC] dark:border-[#333333]'">
                                <ChevronLeft class="w-4 h-4 mr-1" stroke-width="1.5" />
                                Previous
                            </button>

                            <!-- Page Numbers -->
                            <div class="flex items-center space-x-1">
                                <template v-for="link in videoSizes.links.slice(1, -1)" :key="link.label">
                                    <button v-if="link.url" @click="changePage(link.url)"
                                        class="px-3 py-2 text-xs rounded-full transition-colors font-mono tabular-nums"
                                        :class="link.active
                                            ? 'bg-black dark:bg-white text-white dark:text-black border-2 border-black dark:border-white'
                                            : 'text-[#666666] dark:text-[#999999] hover:bg-black hover:dark:bg-white hover:text-white hover:dark:text-black border-2 border-[#CCCCCC] dark:border-[#333333]'"
                                        v-html="link.label" />
                                    <span v-else
                                        class="px-3 py-2 text-xs text-[#CCCCCC] dark:text-[#333333] cursor-not-allowed font-mono"
                                        v-html="link.label" />
                                </template>
                            </div>

                            <!-- Next Button -->
                            <button @click="changePage(videoSizes.next_page_url)" :disabled="!videoSizes.next_page_url"
                                class="px-4 py-2 text-xs rounded-full transition-colors flex items-center uppercase font-mono tracking-wider"
                                :class="videoSizes.next_page_url
                                    ? 'text-black dark:text-white hover:bg-black hover:dark:bg-white hover:text-white hover:dark:text-black border-2 border-black dark:border-white'
                                    : 'text-[#CCCCCC] dark:text-[#333333] cursor-not-allowed border-2 border-[#CCCCCC] dark:border-[#333333]'">
                                Next
                                <ChevronRight class="w-4 h-4 ml-1" stroke-width="1.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>