<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Pencil, Trash2, LoaderCircle } from 'lucide-vue-next';
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
    });

    if (result.isConfirmed) {
        router.delete(route('video-sizes-delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire('Deleted!', 'Video size deleted successfully.', 'success'),
            onError: () => Swal.fire('Error!', 'Failed to delete video size.', 'error'),
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
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    timerProgressBar: true,
                });
                cancelEdit();
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to update video size.', 'error');
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
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    timerProgressBar: true,
                });
                newSize.value = { name: '', width: '', height: '' };
                adding.value = false;
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to add video size.', 'error');
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
        <div class="p-6">
            <!-- Search & Add -->
            <div class="mb-4 flex items-center justify-between">
                <input v-model="search" placeholder="Search..."
                    class="w-full max-w-xs rounded-2xl border px-4 py-2 dark:bg-neutral-800 dark:text-white" />
                <button @click="adding = true" v-if="!adding && editingId === null"
                    class="ml-4 rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                    <CirclePlus class="mr-1 inline h-5 w-5" /> Add Size
                </button>
            </div>

            <!-- Table -->
            <div class="rounded-2xl overflow-x-auto shadow">
                <table class="w-full bg-white dark:bg-neutral-800 border">
                    <thead class="bg-gray-100 text-gray-700 dark:bg-neutral-900 dark:text-gray-300">
                        <tr class="text-center text-sm uppercase">
                            <th class="border-b px-4 py-2">#</th>
                            <th class="border-b px-4 py-2">Name</th>
                            <th class="border-b px-2 py-2">Width</th>
                            <th class="border-b px-2 py-2">Height</th>
                            <th class="border-b px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Add Row -->
                        <tr v-if="adding" class="text-center">
                            <td class="border-b px-4 py-2">#</td>
                            <td class="border-b px-4 py-2">
                                <input v-model="newSize.name" placeholder="Name"
                                    class="w-full max-w-[200px] rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                            </td>
                            <td class="border-b px-2 py-2">
                                <input v-model="newSize.width" type="number" placeholder="Width"
                                    class="w-full max-w-[100px] rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                            </td>
                            <td class="border-b px-2 py-2">
                                <input v-model="newSize.height" type="number" placeholder="Height"
                                    class="w-full max-w-[100px] rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                            </td>
                            <td class="border-b px-4 py-2 space-x-2">
                                <button @click="adding = false" :disabled="saving"
                                    class="text-gray-500 hover:underline text-sm">Cancel</button>
                                <button @click="saveNewSize" :disabled="saving"
                                    class="text-green-600 hover:underline text-sm">
                                    <LoaderCircle v-if="saving" class="h-4 w-4 animate-spin" />
                                    <span v-else>Save</span>
                                </button>
                            </td>
                        </tr>

                        <!-- Existing Rows -->
                        <tr v-for="(size, index) in videoSizes.data" :key="size.id"
                            class="border-t text-center text-sm dark:border-neutral-700 dark:hover:bg-neutral-700">
                            <td class="px-4 py-2">{{ index + 1 }}</td>

                            <template v-if="editingId === size.id">
                                <td class="px-4 py-2">
                                    <input v-model="editedSize.name"
                                        class="w-full max-w-[200px] rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                                </td>
                                <td class="px-2 py-2">
                                    <input v-model="editedSize.width" type="number"
                                        class="w-full max-w-[100px] rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                                </td>
                                <td class="px-2 py-2">
                                    <input v-model="editedSize.height" type="number"
                                        class="w-full max-w-[100px] rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                                </td>
                                <td class="flex justify-center gap-2 px-4 py-2">
                                    <button @click="cancelEdit" :disabled="saving"
                                        class="text-red-500 hover:underline text-sm">Cancel</button>
                                    <button @click="saveEdit(size.id)" :disabled="saving"
                                        class="text-blue-600 hover:underline text-sm">Update</button>
                                </td>
                            </template>

                            <template v-else>
                                <td class="px-4 py-2">{{ size.name }}</td>
                                <td class="px-2 py-2">{{ size.width }}</td>
                                <td class="px-2 py-2">{{ size.height }}</td>
                                <td class="space-x-2 px-4 py-2">
                                    <button @click="startEdit(size)" class="text-blue-600 hover:text-blue-800">
                                        <Pencil class="inline h-5 w-5" />
                                    </button>
                                    <button @click="deleteVideoSize(size.id)" class="text-red-600 hover:text-red-800">
                                        <Trash2 class="inline h-5 w-5" />
                                    </button>
                                </td>
                            </template>
                        </tr>

                        <tr v-if="videoSizes.data.length === 0 && !adding">
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">No video sizes found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex justify-center space-x-2"
                v-if="videoSizes.data.length > 0 && videoSizes.links.length">
                <template v-for="link in videoSizes.links" :key="link.label">
                    <component :is="link.url ? 'a' : 'span'" v-html="link.label" :href="link.url"
                        class="rounded-xl border px-4 py-2 text-sm" :class="{
                            'bg-indigo-600 text-white': link.active,
                            'cursor-not-allowed text-gray-400': !link.url,
                            'hover:bg-gray-200 dark:hover:bg-black': link.url && !link.active,
                        }" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>