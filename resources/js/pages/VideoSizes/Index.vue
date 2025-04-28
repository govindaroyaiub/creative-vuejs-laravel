<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Pencil, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Video Sizes',
        href: '/video-sizes',
    },
];

const page = usePage();
const videoSizes = computed(() => page.props.videoSizes ?? { data: [], links: [] });
const search = ref('');

const filteredSizes = computed(() => {
    const query = search.value.toLowerCase();
    return videoSizes.value.data.filter(
        (size) =>
            size.name.toLowerCase().includes(query) ||
            size.width.toString().includes(query) ||
            size.height.toString().includes(query)
    );
});

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
                Swal.fire('Deleted!', 'Video size deleted successfully.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to delete video size.', 'error');
            },
        });
    }
};

const goToEdit = (id: number) => {
    router.visit(route('video-sizes-edit', id));
};
</script>

<template>
    <Head title="Video Sizes" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Search & Add -->
            <div class="mb-4 flex items-center justify-between">
                <input v-model="search" placeholder="Search..." class="w-full max-w-xs rounded border px-4 py-2 dark:bg-gray-700 dark:text-white" />
                <a :href="route('video-sizes-create-post')" class="ml-4 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                    <CirclePlus class="mr-1 inline h-5 w-5" />
                    Add
                </a>
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
                    <tr v-for="(size, index) in filteredSizes" :key="size.id" class="border-t text-center text-sm uppercase dark:border-gray-700">
                        <td class="px-4 py-2">{{ index + 1 }}</td>
                        <td class="px-4 py-2">{{ size.name }}</td>
                        <td class="px-4 py-2">{{ size.width }}</td>
                        <td class="px-4 py-2">{{ size.height }}</td>
                        <td class="space-x-2 px-4 py-2">
                            <button @click="goToEdit(size.id)" class="text-blue-600 hover:text-blue-800">
                                <Pencil class="inline h-5 w-5" />
                            </button>
                            <button @click="deleteVideoSize(size.id)" class="text-red-600 hover:text-red-800">
                                <Trash2 class="inline h-5 w-5" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="filteredSizes.length === 0">
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500">No video sizes found.</td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-6 flex justify-center space-x-2" v-if="videoSizes.data.length > 0 && videoSizes.links.length">
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