<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
    { title: 'Edit Client', href: '/clients-edit' },
];

const props = defineProps<{
    client: {
        id: number;
        name: string;
        website: string;
        preview_url: string | null;
        logo: string;
        color_palette_id: number | null;
    };
    palettes: Array<{
        id: number;
        name: string;
        primary: string;
    }>;
}>();

const form = ref({
    name: '',
    website: '',
    preview_url: '',
    color_palette_id: null as number | null,
    logo: null as File | null,
});

const logoPreview = ref<string>(`/logos/${props.client.logo}`);
const selectedColorHex = ref<string>('');

onMounted(() => {
    form.value.name = props.client.name;
    form.value.website = props.client.website;
    form.value.preview_url = props.client.preview_url || '';
    form.value.color_palette_id = props.client.color_palette_id;

    const match = props.palettes.find(p => p.id === props.client.color_palette_id);
    selectedColorHex.value = match ? match.primary : '#000000';
});

watch(() => form.value.color_palette_id, (id) => {
    const match = props.palettes.find(p => p.id === id);
    selectedColorHex.value = match ? match.primary : '#000000';
});

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target?.files?.[0]) {
        form.value.logo = target.files[0];
        logoPreview.value = URL.createObjectURL(target.files[0]);
    }
};

const handleSubmit = () => {
    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('website', form.value.website);
    formData.append('preview_url', form.value.preview_url);
    formData.append('color_palette_id', form.value.color_palette_id?.toString() || '');
    if (form.value.logo) {
        formData.append('logo', form.value.logo);
    }

    router.post(`/clients-update/${props.client.id}`, formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => Swal.fire('Updated!', 'Client updated successfully.', 'success'),
        onError: () => Swal.fire('Error!', 'Failed to update client.', 'error'),
    });
};
</script>

<template>

    <Head title="Edit Client" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-3xl w-3/4 mx-auto">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium">Name</label>
                    <input v-model="form.name" type="text"
                        class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
                </div>

                <!-- Website -->
                <div>
                    <label class="block text-sm font-medium">Website</label>
                    <input v-model="form.website" type="url"
                        class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
                </div>

                <!-- Preview URL -->
                <div>
                    <label class="block text-sm font-medium">Preview URL</label>
                    <input v-model="form.preview_url" type="url"
                        class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" />
                </div>

                <!-- Color Palette -->
                <div>
                    <label class="block text-sm font-medium">Color Palette</label>
                    <select v-model="form.color_palette_id"
                        class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white">
                        <option :value="null">Select a palette</option>
                        <option v-for="palette in props.palettes" :key="palette.id" :value="palette.id">
                            {{ palette.name }} ({{ palette.primary }})
                        </option>
                    </select>
                    <div class="mt-2 flex items-center space-x-2">
                        <span class="text-sm">Selected:</span>
                        <span :style="{ backgroundColor: selectedColorHex }"
                            class="inline-block h-6 w-12 rounded-lg border"></span>
                        <span class="text-sm">{{ selectedColorHex }}</span>
                    </div>
                </div>

                <!-- Logo Upload -->
                <div>
                    <label class="block text-sm font-medium">Logo (Image)</label>
                    <div v-if="logoPreview" class="mb-4">
                        <img :src="`${logoPreview}`" alt="Logo Preview" class="h-16 mx-auto rounded-lg" />
                    </div>
                    <input type="file" accept="image/*" @change="handleFileChange" class="block w-full text-sm" />
                </div>

                <!-- Submit and Back Buttons -->
                <div class="flex space-x-4">
                    <Link :href="route('clients')"
                        class="w-full text-center rounded-lg bg-red-600 px-6 py-3 text-white shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-red-500 dark:hover:bg-red-600">
                    Back
                    </Link>
                    <button type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-6 py-3 text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
