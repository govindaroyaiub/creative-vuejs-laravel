<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { ref, computed } from 'vue';

const saving = ref(false);
const page = usePage();
const subBanner = computed(() => page.props.subBanner);
const bannerSizes = computed(() => page.props.bannerSizes);
const preview = computed(() => page.props.preview);

const sizeOptions = computed(() =>
    bannerSizes.value.map((s: any) => ({
        id: s.id,
        label: s.label, // Use the label provided by the backend
    }))
);

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.id}` },
    { title: 'Edit Banner', href: '#' },
];

const form = useForm({
    size_id: subBanner.value.size_id,
    zip: null,
});

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const triggerInput = () => fileInput.value?.click();

const handleFileUpload = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    if (file && file.name.endsWith('.zip')) {
        form.zip = file;
    }
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    const files = e.dataTransfer?.files;
    const file = files?.[0];
    if (file && file.name.endsWith('.zip')) {
        form.zip = file;
    }
};

const handleSubmit = () => {
    if (saving.value) return;
    saving.value = true;
    const payload = new FormData();
    payload.append('size_id', form.size_id);
    if (form.zip) {
        payload.append('zip', form.zip);
    }

    router.post(`/previews/banner/single/edit/${subBanner.value.id}`, payload, {
        forceFormData: true,
        onSuccess: () => {
            saving.value = false;
            Swal.fire('Success', 'Banner updated successfully.', 'success');
        },
        onError: () => {
            saving.value = false;
            Swal.fire('Error', 'Something went wrong.', 'error');
        },
    });
};
</script>

<template>

    <Head title="Edit Banner" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-4xl mx-auto space-y-6 w-full">
            <div>
                <label class="block font-medium mb-1">Select Banner Size</label>
                <select v-model="form.size_id" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-white">
                    <option disabled value="">Select size</option>
                    <option v-for="size in sizeOptions" :key="size.id" :value="size.id">
                        {{ size.label }}
                    </option>
                </select>
            </div>

            <div class="space-y-2">
                <div class="border-2 border-dashed p-6 text-center rounded-lg cursor-pointer" @click="triggerInput"
                    @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop" :class="{ 'border-green-500 bg-green-50': isDragging }">
                    <span class="text-sm text-gray-600">Click or drag ZIP file here to upload (optional)</span>
                    <input ref="fileInput" type="file" class="hidden" accept=".zip" @change="handleFileUpload" />
                </div>

                <div v-if="form.zip" class="text-sm text-gray-600 dark:text-white">
                    Selected ZIP: {{ form.zip.name }}
                </div>
            </div>

            <div class="pt-4 flex space-x-2">
                <button @click="handleSubmit" :disabled="saving"
                    class="w-full bg-indigo-600 text-white py-3 rounded hover:bg-indigo-700 focus:outline-none">
                    <span v-if="!saving">Update</span>
                    <span v-else class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Saving...
                    </span>
                </button>
                <a :href="`/previews/show/${preview.id}`"
                    class="w-full text-center rounded-lg bg-red-600 px-6 py-3 text-white shadow-md hover:bg-red-700">
                    Back
                </a>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.ghost {
    opacity: 0.5;
}
</style>
