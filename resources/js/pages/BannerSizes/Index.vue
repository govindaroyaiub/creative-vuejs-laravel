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
                customClass: { popup: 'rounded-lg' }
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
                    customClass: { popup: 'rounded-lg' }
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
        customClass: { popup: 'rounded-lg' }
    });

    if (result.isConfirmed) {
        router.delete(route('banner-sizes-delete', id), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    title: 'Deleted!',
                    text: 'Banner size deleted.',
                    icon: 'success',
                    customClass: { popup: 'rounded-lg' }
                });
            },
            onError: () => {
                Swal.fire({
                    title: 'Error!',
                    text: 'Could not delete.',
                    icon: 'error',
                    customClass: { popup: 'rounded-lg' }
                });
            },
        });
    }
};
</script>

<template>

    <Head title="Banner Sizes" />
    <AppLayout :breadcrumbs="[{ title: 'Banner Sizes', href: '/banner-sizes' }]">
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 sm:p-6">
                <div
                    class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-0 sm:justify-between">
                    <input v-model="search" placeholder="Search..."
                        class="w-full sm:max-w-xs rounded-full border-2 border-[#CCCCCC] dark:border-[#333333] px-4 py-2 bg-white dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                    <button @click="startAdding"
                        class="rounded-full bg-black dark:bg-white text-white dark:text-black px-4 py-2 hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white group whitespace-nowrap font-mono tracking-wide text-sm transition-colors">
                        <CirclePlus
                            class="mr-1 inline h-5 w-5 group-hover:rotate-90 transition-transform duration-200" />
                        Add Size
                    </button>
                </div>

                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                    <table class="w-full rounded bg-white dark:bg-[#111111]">
                        <thead class="bg-[#F5F5F5] dark:bg-[#0A0A0A] text-black dark:text-white">
                            <tr class="text-center text-xs font-mono tracking-wide">
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Width</th>
                                <th class="px-4 py-3">Height</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- New Row -->
                            <tr v-if="adding"
                                class="border-t border-[#E8E8E8] dark:border-[#222222] text-center text-sm">
                                <td class="px-4 py-3">#</td>
                                <td class="px-4 py-3">
                                    <div v-if="adding" class="uppercase font-mono tabular-nums">{{ newForm.width }}x{{
                                        newForm.height }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <input v-model="newForm.width" type="number"
                                        class="w-24 rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-2 py-1 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </td>
                                <td class="px-4 py-3">
                                    <input v-model="newForm.height" type="number"
                                        class="w-24 rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-2 py-1 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </td>
                                <td class="px-4 py-3 space-x-2">
                                    <button @click="cancelAdding"
                                        class="rounded-full px-4 py-1 text-xs bg-[#D71921] hover:bg-red-700 text-white font-mono tracking-wide transition-colors">Cancel</button>
                                    <button @click="saveNew"
                                        class="rounded-full px-4 py-1 text-xs bg-black dark:bg-white text-white dark:text-black hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white font-mono tracking-wide transition-colors">Save</button>
                                </td>
                            </tr>

                            <!-- Existing Rows -->
                            <tr v-for="(size, index) in bannerSizes.data" :key="size.id"
                                class="border-t border-[#E8E8E8] dark:border-[#222222] text-center text-sm hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] transition-colors">
                                <td class="px-4 py-3 text-[#666666] dark:text-[#999999]">{{ index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div v-if="editingId !== size.id"
                                        class="uppercase font-mono tabular-nums font-semibold">{{ size.width }}x{{
                                        size.height }}</div>
                                    <div v-else class="uppercase font-mono tabular-nums font-semibold">{{ editForm.width
                                        }}x{{ editForm.height }}</div>
                                </td>

                                <td class="px-4 py-3">
                                    <div v-if="editingId !== size.id" class="tabular-nums">{{ size.width }}</div>
                                    <input v-else v-model="editForm.width" type="number"
                                        class="w-24 rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-2 py-1 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </td>

                                <td class="px-4 py-3">
                                    <div v-if="editingId !== size.id" class="tabular-nums">{{ size.height }}</div>
                                    <input v-else v-model="editForm.height" type="number"
                                        class="w-24 rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-2 py-1 bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </td>

                                <td class="space-x-2 px-4 py-3">
                                    <template v-if="editingId === size.id">
                                        <button @click="cancelEditing"
                                            class="rounded-full px-4 py-1 text-xs bg-[#D71921] hover:bg-red-700 text-white font-mono tracking-wide transition-colors">Cancel</button>
                                        <button @click="saveEdit(size.id)"
                                            class="rounded-full px-4 py-1 text-xs bg-black dark:bg-white text-white dark:text-black hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white font-mono tracking-wide transition-colors">Update</button>
                                    </template>
                                    <template v-else>
                                        <button @click="startEditing(size)"
                                            class="text-black dark:text-white hover:text-[#666666] dark:hover:text-[#999999] transition-colors">
                                            <Pencil class="inline h-5 w-5" stroke-width="1.5" />
                                        </button>
                                        <button @click="deleteBannerSize(size.id)"
                                            class="text-[#D71921] hover:text-red-700 transition-colors">
                                            <Trash2 class="inline h-5 w-5" stroke-width="1.5" />
                                        </button>
                                    </template>
                                </td>
                            </tr>

                            <tr v-if="bannerSizes.data.length === 0 && !adding">
                                <td colspan="5"
                                    class="px-4 py-8 text-center text-[#666666] dark:text-[#999999] font-mono tracking-wide text-sm">
                                    No
                                    banner sizes found.</td>
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
                            <h3 class="font-semibold text-sm font-mono">New banner size</h3>
                            <button @click="cancelAdding"
                                class="text-[#666666] dark:text-[#999999] hover:text-black hover:dark:text-white transition-colors">
                                <X class="h-5 w-5" stroke-width="1.5" />
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label
                                    class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] mb-1 block">Name</label>
                                <div class="text-sm font-medium uppercase font-mono tabular-nums">{{ newForm.width }}x{{
                                    newForm.height
                                    }}</div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] mb-1 block">Width</label>
                                    <input v-model="newForm.width" type="number"
                                        class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </div>
                                <div>
                                    <label
                                        class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] mb-1 block">Height</label>
                                    <input v-model="newForm.height" type="number"
                                        class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </div>
                            </div>
                            <div class="flex gap-2 pt-2">
                                <button @click="cancelAdding"
                                    class="flex-1 px-4 py-2 text-sm rounded-full bg-[#D71921] hover:bg-red-700 text-white font-mono tracking-wide transition-colors">
                                    Cancel
                                </button>
                                <button @click="saveNew"
                                    class="flex-1 px-4 py-2 text-sm rounded-full bg-black dark:bg-white text-white dark:text-black hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white font-mono tracking-wide transition-colors">
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Size Cards -->
                    <div v-for="(size, index) in bannerSizes.data" :key="size.id"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] hover:border-black hover:dark:border-white p-4 transition-colors">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-[#666666] dark:text-[#999999] tabular-nums">#{{
                                    index + 1
                                    }}</span>
                            </div>
                            <div v-if="editingId !== size.id" class="flex gap-2">
                                <button @click="startEditing(size)"
                                    class="text-black dark:text-white hover:text-[#666666] dark:hover:text-[#999999] p-1 transition-colors">
                                    <Pencil class="h-4 w-4" stroke-width="1.5" />
                                </button>
                                <button @click="deleteBannerSize(size.id)"
                                    class="text-[#D71921] hover:text-red-700 p-1 transition-colors">
                                    <Trash2 class="h-4 w-4" stroke-width="1.5" />
                                </button>
                            </div>
                        </div>

                        <template v-if="editingId === size.id">
                            <div class="space-y-3">
                                <div>
                                    <label
                                        class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] mb-1 block">Name</label>
                                    <div class="text-sm font-medium uppercase font-mono tabular-nums">{{ editForm.width
                                        }}x{{ editForm.height }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label
                                            class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] mb-1 block">Width</label>
                                        <input v-model="editForm.width" type="number"
                                            class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                    </div>
                                    <div>
                                        <label
                                            class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] mb-1 block">Height</label>
                                        <input v-model="editForm.height" type="number"
                                            class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                    </div>
                                </div>
                                <div class="flex gap-2 pt-2">
                                    <button @click="cancelEditing"
                                        class="flex-1 px-4 py-2 text-sm rounded-full bg-[#D71921] hover:bg-red-700 text-white font-mono tracking-wide transition-colors">
                                        Cancel
                                    </button>
                                    <button @click="saveEdit(size.id)"
                                        class="flex-1 px-4 py-2 text-sm rounded-full bg-black dark:bg-white text-white dark:text-black hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white font-mono tracking-wide transition-colors">
                                        Update
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="space-y-2">
                                <div>
                                    <div
                                        class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999]">
                                        Name</div>
                                    <div class="text-sm font-semibold uppercase font-mono tabular-nums">{{ size.width
                                        }}x{{ size.height }}
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 pt-2">
                                    <div>
                                        <div
                                            class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999]">
                                            Width</div>
                                        <div class="text-sm font-medium tabular-nums">{{ size.width }}</div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999]">
                                            Height</div>
                                        <div class="text-sm font-medium tabular-nums">{{ size.height }}</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div v-if="bannerSizes.data.length === 0 && !adding"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-8 text-center">
                        <p class="text-[#666666] dark:text-[#999999] font-mono tracking-wide text-sm">No
                            banner sizes found.
                        </p>
                    </div>
                </div>

                <!-- Pagination - Responsive -->
                <div v-if="bannerSizes.data.length && bannerSizes.links.length" class="mt-6 p-4">

                    <!-- Mobile/Tablet pagination (simplified) -->
                    <div class="lg:hidden">
                        <!-- Results Info -->
                        <div
                            class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] text-center mb-3">
                            Showing <span class="tabular-nums">{{ bannerSizes.from }}</span> to <span
                                class="tabular-nums">{{
                                bannerSizes.to }}</span> of <span class="tabular-nums">{{ bannerSizes.total }}</span>
                            banner
                            sizes
                        </div>

                        <!-- Simple prev/next navigation -->
                        <div class="flex items-center justify-between gap-4">
                            <button @click="changePage(bannerSizes.prev_page_url)"
                                :disabled="!bannerSizes.prev_page_url"
                                class="px-4 py-2 text-xs rounded-full transition-colors font-mono tracking-wide flex items-center gap-2"
                                :class="bannerSizes.prev_page_url
                                    ? 'text-black dark:text-white hover:bg-black hover:dark:bg-white hover:text-white hover:dark:text-black border-2 border-black dark:border-white'
                                    : 'text-[#CCCCCC] dark:text-[#333333] cursor-not-allowed border-2 border-[#CCCCCC] dark:border-[#333333]'">
                                <ChevronLeft class="w-4 h-4" stroke-width="1.5" />
                                Previous
                            </button>

                            <span class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999]">
                                Page <span class="tabular-nums">{{ bannerSizes.current_page }}</span> of <span
                                    class="tabular-nums">{{
                                    bannerSizes.last_page }}</span>
                            </span>

                            <button @click="changePage(bannerSizes.next_page_url)"
                                :disabled="!bannerSizes.next_page_url"
                                class="px-4 py-2 text-xs rounded-full transition-colors font-mono tracking-wide flex items-center gap-2"
                                :class="bannerSizes.next_page_url
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
                        <div class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999]">
                            Showing <span class="tabular-nums">{{ bannerSizes.from }}</span> to <span
                                class="tabular-nums">{{
                                bannerSizes.to }}</span> of <span class="tabular-nums">{{ bannerSizes.total }}</span>
                            banner
                            sizes
                        </div>

                        <!-- Pagination Controls -->
                        <div class="flex items-center space-x-2">
                            <!-- Previous Button -->
                            <button @click="changePage(bannerSizes.prev_page_url)"
                                :disabled="!bannerSizes.prev_page_url"
                                class="px-4 py-2 text-xs rounded-full transition-colors flex items-center font-mono tracking-wide"
                                :class="bannerSizes.prev_page_url
                                    ? 'text-black dark:text-white hover:bg-black hover:dark:bg-white hover:text-white hover:dark:text-black border-2 border-black dark:border-white'
                                    : 'text-[#CCCCCC] dark:text-[#333333] cursor-not-allowed border-2 border-[#CCCCCC] dark:border-[#333333]'">
                                <ChevronLeft class="w-4 h-4 mr-1" stroke-width="1.5" />
                                Previous
                            </button>

                            <!-- Page Numbers -->
                            <div class="flex items-center space-x-1">
                                <template v-for="link in bannerSizes.links.slice(1, -1)" :key="link.label">
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
                            <button @click="changePage(bannerSizes.next_page_url)"
                                :disabled="!bannerSizes.next_page_url"
                                class="px-4 py-2 text-xs rounded-full transition-colors flex items-center font-mono tracking-wide"
                                :class="bannerSizes.next_page_url
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