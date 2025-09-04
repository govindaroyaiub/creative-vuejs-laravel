<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';
import draggable from 'vuedraggable';
import { X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import axios from 'axios';

const page = usePage();
const preview = computed(() => page.props.preview);
const category = page.props.category as { id: number; name: string; };
const name = ref(category.name);

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.slug}` },
    { title: 'Create New Category', href: '#' },
];

const form = useForm({
    name: name,
});

const handleSubmit = () => {
    form.put(route('previews-update-category', category.id), {
        preserveScroll: true,
        onSuccess: () => Swal.fire('Success!', 'Category updated successfully.', 'success'),
        onError: () => Swal.fire('Error!', 'Failed to update category.', 'error'),
    });
};

</script>

<template>

    <Head title="Create New Category" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-4xl mx-auto space-y-6 w-full">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-1">Category Name</label>
                    <input v-model="name" type="text"
                        class="w-full rounded border px-3 py-2 dark:bg-gray-800 dark:text-white" />
                </div>
                <div class="flex justify-center space-x-4">
                    <a :href="`/previews/show/${preview.slug}`"
                        class="w-full text-center rounded-lg bg-red-600 px-6 py-3 text-white shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Back
                    </a>
                    <button type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-6 py-3 text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>