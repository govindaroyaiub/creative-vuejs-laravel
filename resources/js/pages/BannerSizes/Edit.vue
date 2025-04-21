<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Edit Banner Size',
        href: '/banner-sizes',
    },
];

const page = usePage();
const bannerSize = page.props.bannerSize;
const flashMessage = computed(() => page.props.flash || '');
const showFlash = ref(!!flashMessage.value);

// Form state
const form = useForm({
    width: bannerSize.width,
    height: bannerSize.height,
});

// Submit handler
const handleSubmit = () => {
    form.put(`/banner-sizes-edit/${bannerSize.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showFlash.value = true;
        },
    });
};

// Back navigation
const goBack = () => {
    window.location.href = '/banner-sizes';
};
</script>

<template>
    <Head title="Edit Banner Size" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <transition name="fade">
                <div v-if="showFlash" class="relative mb-4 rounded-md bg-green-500 p-3 pr-10 text-white">
                    {{ flashMessage }}
                    <button @click="showFlash = false" class="absolute right-2 top-2 text-white hover:text-gray-200">
                        <X class="inline h-5 w-5" />
                    </button>
                </div>
            </transition>

            <form @submit.prevent="handleSubmit" class="mx-auto w-full max-w-2xl space-y-6">
                <!-- Width -->
                <div>
                    <label for="width" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Width</label>
                    <input
                        type="number"
                        name="width"
                        id="width"
                        v-model="form.width"
                        required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:ring-indigo-400"
                    />
                </div>

                <!-- Height -->
                <div>
                    <label for="height" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Height</label>
                    <input
                        type="number"
                        name="height"
                        id="height"
                        v-model="form.height"
                        required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:ring-indigo-400"
                    />
                </div>

                <!-- Buttons -->
                <div class="flex space-x-4">
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-6 py-3 text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        Update
                    </button>
                    <button
                        type="button"
                        class="w-full rounded-lg bg-red-600 px-6 py-3 text-white shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-red-500 dark:hover:bg-red-600"
                        @click="goBack"
                    >
                        Back
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
