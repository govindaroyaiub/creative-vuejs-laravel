<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { CirclePlus, CircleX, Settings } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Banner Sizes',
        href: '/banner-sizes',
    },
];

const editSize = (size) => {
    console.log('Edit clicked for', size);
    // Navigate to edit page or open modal
};

const deleteSize = (id) => {
    console.log('Delete clicked for ID:', id);
    // Confirm and delete logic here
};

const { bannerSizes } = usePage().props;

// bannerSizes.data is the array of sizes
const searchQuery = ref('');

const filteredBannerSizes = computed(() => {
    if (!searchQuery.value.trim()) return bannerSizes.data;

    return bannerSizes.data.filter((size: any) => {
        const query = searchQuery.value.toLowerCase();
        return size.width.toString().includes(query) || size.height.toString().includes(query);
    });
});

const page = usePage();
const flashMessage = computed(() => page.props.flash || '');
</script>

<style scoped>
/* Transition Effect */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 1s ease;
}

.fade-enter, .fade-leave-to /* .fade-leave-active in <2.1.8 */ {
    opacity: 0;
}
</style>

<template>
    <Head title="Banner Sizes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <transition name="fade">
                <div v-if="flashMessage" class="mb-4 rounded-md bg-green-500 p-3 text-white">
                    {{ flashMessage }}
                </div>
            </transition>
            <div class="mb-4 flex items-center justify-between">
                <!-- Search Bar -->
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search..."
                    class="w-full max-w-xs rounded-md border px-4 py-2 dark:bg-gray-700 dark:text-white"
                />

                <!-- Add Button -->
                <a
                    :href="route('banner-sizes-create')"
                    class="ml-4 rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600"
                >
                    <CirclePlus class="inline-block h-6 w-6" />
                </a>
            </div>

            <table class="min-w-full rounded-lg bg-white shadow dark:bg-gray-800">
                <thead>
                    <tr class="bg-gray-100 text-center text-sm uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Width</th>
                        <th class="px-4 py-3">Height</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="filteredBannerSizes.length === 0">
                        <td colspan="4" class="py-4 text-center text-gray-500 dark:text-gray-400">No banner sizes match your search.</td>
                    </tr>
                    <tr
                        v-for="(size, index) in filteredBannerSizes"
                        :key="size.id"
                        class="border-t border-gray-200 text-center text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                    >
                        <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ index + 1 }}</td>
                        <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ size.width }}</td>
                        <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ size.height }}</td>
                        <td class="space-x-2 px-4 py-2">
                            <button class="text-blue-600 hover:underline dark:text-blue-400">
                                <Settings class="inline-block h-8 w-8" />
                            </button>
                            <button class="text-red-600 hover:underline dark:text-red-400">
                                <CircleX class="inline-block h-8 w-8" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="mt-6 flex justify-center space-x-2">
                <template v-for="link in bannerSizes.links" :key="link.label">
                    <component
                        :is="link.url ? 'a' : 'span'"
                        :href="link.url"
                        v-html="link.label"
                        class="rounded border px-4 py-2 text-sm"
                        :class="{
                            'bg-indigo-600 text-white': link.active,
                            'cursor-not-allowed text-gray-500 dark:text-gray-400': !link.url,
                            'hover:bg-gray-200 dark:hover:bg-gray-700': link.url && !link.active,
                        }"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
