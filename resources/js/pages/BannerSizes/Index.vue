<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Trash2, Pencil } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Banner Sizes',
        href: '/banner-sizes',
    },
];

const page = usePage();
const bannerSizes = computed(() => page.props.bannerSizes);
const search = ref('');

const filteredSizes = computed(() => {
    const query = search.value.toLowerCase();
    return bannerSizes.value.data.filter((size) => size.width.toString().includes(query) || size.height.toString().includes(query));
});

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
                Swal.fire('Deleted!', 'Banner size deleted successfully.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to delete banner size.', 'error');
            },
        });
    }
};
</script>

<style scoped>
/* Fade transition for flash messages */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 1s ease;
}
.fade-enter,
.fade-leave-to {
    opacity: 0;
}
</style>

<template>
    <Head title="Banner Sizes" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <input v-model="search" placeholder="Search..." class="w-full max-w-xs rounded border px-4 py-2 dark:bg-gray-700 dark:text-white" />
                <a :href="route('banner-sizes-create')" class="ml-4 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                    <CirclePlus class="mr-1 inline h-5 w-5" />
                    Add
                </a>
            </div>

            <table class="w-full rounded bg-white shadow dark:bg-gray-800">
                <thead class="bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr class="text-center text-sm uppercase">
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Width</th>
                        <th class="px-4 py-2">Height</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(size, index) in filteredSizes" :key="size.id" class="border-t text-center text-sm uppercase dark:border-gray-700">
                        <td class="px-4 py-2">{{ index + 1 }}</td>
                        <td class="px-4 py-2">{{ size.width }}</td>
                        <td class="px-4 py-2">{{ size.height }}</td>
                        <td class="px-4 py-2">
                            <button class="text-blue-600 hover:text-blue-800">
                                <Pencil class="inline h-6 w-6" />
                            </button>
                            <button @click="deleteBannerSize(size.id)" class="text-red-600 hover:text-red-800">
                                <Trash2 class="inline h-6 w-6" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="filteredSizes.length === 0">
                        <td colspan="4" class="px-4 py-4 text-center text-gray-500">No banner sizes found.</td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-6 flex justify-center space-x-2">
                <template v-for="link in bannerSizes.links" :key="link.label">
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
