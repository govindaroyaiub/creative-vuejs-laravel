<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import type { BreadcrumbItem } from '@/types';
import { computed } from 'vue';

const page = usePage();
const feedback = computed(() => page.props.feedback);
const preview = computed(() => page.props.preview);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.id}` },
    { title: 'Edit feedback', href: '#' },
];

const form = useForm({
    name: feedback.value.name,
    description: feedback.value.description ?? '',
});

const handleSubmit = () => {
    form.put(route('previews-update-feedback', feedback.value.id), {
        preserveScroll: true,
        onSuccess: () => Swal.fire('Success!', 'feedback updated successfully.', 'success'),
        onError: () => Swal.fire('Error!', 'Failed to update feedback.', 'error'),
    });
};
</script>

<template>

    <Head title="Edit feedback" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-3xl w-3/4 mx-auto">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium">Feedback Name</label>
                    <input v-model="form.name" type="text"
                        class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium">Description</label>
                    <textarea v-model="form.description" rows="4"
                        class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white"
                        placeholder="Enter feedback details or notes"></textarea>
                </div>

                <!-- Submit Buttons -->
                <div class="flex space-x-4">
                    <button type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-6 py-3 text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                        Update
                    </button>
                    <a :href="`/previews/show/${preview.id}`"
                        class="w-full text-center rounded-lg bg-red-600 px-6 py-3 text-white shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Back
                    </a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>