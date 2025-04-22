<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Pencil, Trash2, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, onMounted } from 'vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Video Sizes', href: '/video-sizes' },
];

const page = usePage();
const videoSizes = computed(() => page.props.videoSizes ?? { data: [], links: [] });
const search = ref('');

const adding = ref(false);
const newForm = ref({ name: '', width: '', height: '' });

const editingId = ref<number | null>(null);
const editForm = ref({ name: '', width: '', height: '' });

const filteredSizes = computed(() => {
    const query = search.value.toLowerCase();
    return videoSizes.value.data.filter(
        (size) =>
            size.name.toLowerCase().includes(query) ||
            size.width.toString().includes(query) ||
            size.height.toString().includes(query)
    );
});

const startAdding = () => {
    adding.value = true;
    newForm.value = { name: '', width: '', height: '' };
};

const cancelAdding = () => {
    adding.value = false;
};

const saveNew = () => {
    router.post(route('video-sizes-create-post'), newForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            adding.value = false;
            Swal.fire('Added!', 'Video size added.', 'success');
        },
        onError: () => {
            Swal.fire('Error!', 'Failed to add. Possibly duplicate or invalid.', 'error');
        },
    });
};

const startEditing = (size: any) => {
    editingId.value = size.id;
    editForm.value = { name: size.name, width: size.width, height: size.height };
};

const cancelEditing = () => {
    editingId.value = null;
};

const saveEdit = (id: number) => {
    router.put(route('video-sizes-update', id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            Swal.fire('Updated!', 'Video size updated.', 'success');
        },
        onError: () => {
            Swal.fire('Error!', 'Failed to update. Possibly duplicate.', 'error');
        },
    });
};

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
            onSuccess: () => {
                Swal.fire('Deleted!', 'Video size deleted.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to delete.', 'error');
            },
        });
    }
};
</script>

<template>
    <Head title="Video Sizes" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Search & Add -->
            <div class="mb-4 flex items-center justify-between">
                <input v-model="search" placeholder="Search..." class="w-full max-w-xs rounded border px-4 py-2 dark:bg-gray-700 dark:text-white" />
                <button @click="startAdding" class="ml-4 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                    <CirclePlus class="mr-1 inline h-5 w-5" />
                    Add
                </button>
            </div>

            <!-- Table -->
            <table class="w-full rounded bg-white shadow dark:bg-gray-800">
                <thead class="bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr class="text-center text-sm uppercase">
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Width</th>
                        <th class="px-4 py-2">Height</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Add Row -->
                    <tr v-if="adding" class="border-t text-center text-sm dark:border-gray-700">
                        <td class="px-4 py-2">#</td>
                        <td class="px-4 py-2"><input v-model="newForm.name" type="text" class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" /></td>
                        <td class="px-4 py-2"><input v-model="newForm.width" type="number" class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" /></td>
                        <td class="px-4 py-2"><input v-model="newForm.height" type="number" class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" /></td>
                        <td class="px-4 py-2 space-x-2">
                            <button @click="saveNew" class="text-green-600 hover:underline text-sm">Save</button>
                            <button @click="cancelAdding" class="text-gray-500 hover:underline text-sm">Cancel</button>
                        </td>
                    </tr>

                    <!-- Data Rows -->
                    <tr v-for="(size, index) in filteredSizes" :key="size.id" class="border-t text-center text-sm uppercase dark:border-gray-700">
                        <td class="px-4 py-2">{{ index + 1 }}</td>

                        <td class="px-4 py-2">
                            <template v-if="editingId !== size.id">{{ size.name }}</template>
                            <template v-else>
                                <input v-model="editForm.name" type="text" class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" />
                            </template>
                        </td>
                        <td class="px-4 py-2">
                            <template v-if="editingId !== size.id">{{ size.width }}</template>
                            <template v-else>
                                <input v-model="editForm.width" type="number" class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" />
                            </template>
                        </td>
                        <td class="px-4 py-2">
                            <template v-if="editingId !== size.id">{{ size.height }}</template>
                            <template v-else>
                                <input v-model="editForm.height" type="number" class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" />
                            </template>
                        </td>

                        <td class="space-x-2 px-4 py-2">
                            <template v-if="editingId === size.id">
                                <button @click="saveEdit(size.id)" class="text-blue-600 hover:underline text-sm">Update</button>
                                <button @click="cancelEditing" class="text-red-500 hover:underline text-sm">Cancel</button>
                            </template>
                            <template v-else>
                                <button @click="startEditing(size)" class="text-blue-600 hover:text-blue-800">
                                    <Pencil class="inline h-5 w-5" />
                                </button>
                                <button @click="deleteVideoSize(size.id)" class="text-red-600 hover:text-red-800">
                                    <Trash2 class="inline h-5 w-5" />
                                </button>
                            </template>
                        </td>
                    </tr>

                    <tr v-if="filteredSizes.length === 0 && !adding">
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500">No video sizes found.</td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-6 flex justify-center space-x-2" v-if="videoSizes.data.length && videoSizes.links.length">
                <template v-for="link in videoSizes.links" :key="link.label">
                    <component
                        :is="link.url ? 'a' : 'span'"
                        v-html="link.label"
                        :href="link.url"
                        class="rounded border px-4 py-2 text-sm"
                        :class="{
                            'bg-indigo-600 text-white': link.active,
                            'cursor-not-allowed text-gray-400': !link.url,
                            'hover:bg-gray-200 dark:hover:bg-gray-700': link.url && !link.active,
                        }"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>