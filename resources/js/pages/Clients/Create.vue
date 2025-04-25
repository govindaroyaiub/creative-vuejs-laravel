<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
    { title: 'Add Client', href: '/clients-create' },
];

const form = ref({
    name: '',
    website: '',
    preview_url: '',
    brand_color: '#000000',
    logo: null as File | null,
});

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target?.files?.[0]) {
        form.value.logo = target.files[0];
    }
};

const handleSubmit = () => {
    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('website', form.value.website);
    formData.append('preview_url', form.value.preview_url);
    formData.append('brand_color', form.value.brand_color);
    if (form.value.logo) {
        formData.append('logo', form.value.logo);
    }

    router.post('/clients-store', formData, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire('Success!', 'Client created successfully.', 'success');
        },
        onError: () => {
            Swal.fire('Error!', 'Failed to create client.', 'error');
        },
    });
};
</script>

<template>
    <Head title="Add Client" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-3xl w-3/4 mx-auto">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium">Name</label>
                    <input v-model="form.name" type="text" class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
                </div>

                <!-- Website -->
                <div>
                    <label class="block text-sm font-medium">Website</label>
                    <input v-model="form.website" type="url" class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
                </div>

                <!-- Preview URL -->
                <div>
                    <label class="block text-sm font-medium">Preview URL</label>
                    <input v-model="form.preview_url" type="url" class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" />
                </div>

                <!-- Brand Color -->
                <div>
                    <label class="block text-sm font-medium">Brand Color</label>
                    <input v-model="form.brand_color" type="color" class="w-16 h-10 p-1 rounded border" />
                    <span class="ml-2">{{ form.brand_color }}</span>
                </div>

                <!-- Logo -->
                <div>
                    <label class="block text-sm font-medium">Logo (Image)</label>
                    <input type="file" accept="image/*" @change="handleFileChange" class="block w-full text-sm" />
                </div>

                <!-- Submit Button -->
                <div class="flex space-x-4">
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-6 py-3 text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        Submit
                    </button>
                    <Link
                        type="button"
                        class="w-full text-center rounded-lg bg-red-600 px-6 py-3 text-white shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-red-500 dark:hover:bg-red-600"
                        :href="route('clients')"
                    >
                        Back
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>