<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Add Banner Size',
        href: '/banner-sizes-create',
    },
];

const page = usePage();
const flashMessage = computed(() => page.props.flash || '');
</script>

<template>
    <Head title="Add Banner Size" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <transition name="fade">
                <div v-if="flashMessage" class="mb-4 rounded-md bg-green-500 p-3 text-white">
                    {{ flashMessage }}
                </div>
            </transition>
            <form @submit.prevent="handleSubmit" class="mx-auto w-full max-w-2xl space-y-6">
                <!-- Name Field -->
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

                <!-- Client Field -->
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

                <!-- Submit and Back Buttons -->
                <div class="flex space-x-4">
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-6 py-3 text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        Save
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
<script lang="ts">
export default {
    data() {
        return {
            form: {
                width: '',
                height: '',
            },
        };
    },
    methods: {
        handleSubmit() {
            const formData = new FormData();
            formData.append('width', this.form.width);
            formData.append('height', this.form.height);

            // Send the form data using Inertia post
            this.$inertia.post('/banner-sizes-create-post', formData);
        },
        goBack() {
            // Handle back button click
            window.location.href = '/banner-sizes';
        },
    },
};
</script>

<style scoped>
.dark .bg-gray-700 {
    background-color: #2a2a2a;
}

.dark .text-gray-300 {
    color: #e0e0e0;
}

.dark .border-gray-600 {
    border-color: #4a4a4a;
}

.dark .focus\:ring-indigo-500 {
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.5);
}
</style>
