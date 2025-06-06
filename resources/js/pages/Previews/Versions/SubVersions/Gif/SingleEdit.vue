<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { ref, computed } from 'vue';

const page = usePage();
const subGif = computed(() => page.props.subGif);
const preview = computed(() => page.props.preview);
const bannerSizes = computed(() => page.props.bannerSizes);

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.id}` },
    { title: 'Edit GIF', href: '#' },
];

const form = useForm({
    size_id: subGif.value.size_id,
    file: null,
});

const fileUrl = ref('/' + subGif.value.path);
const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

function handleFileChange(e: Event) {
    isDragging.value = false;
    const files = (e.target as HTMLInputElement).files;
    if (files && files[0]) {
        form.file = files[0];
        fileUrl.value = URL.createObjectURL(files[0]);
    }
}

function onDrop(e: DragEvent) {
    isDragging.value = false;
    if (!e.dataTransfer?.files) return;
    const files = e.dataTransfer.files;
    if (files && files[0]) {
        form.file = files[0];
        fileUrl.value = URL.createObjectURL(files[0]);
    }
}

const handleSubmit = () => {
    form.post(`/previews/gif/single/edit/${subGif.value.id}`, {
        onSuccess: () => {
            Swal.fire('Success', 'GIF updated!', 'success');
        },
        onError: (error) => {
            console.error('Error updating GIF:', error);
            Swal.fire('Error', 'Something went wrong', 'error');
        }
    });
};
</script>

<template>

    <Head title="Edit GIF" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-lg mx-auto space-y-6 w-full">
            <div>
                <label class="block mb-1 font-medium">Banner Size</label>
                <select v-model="form.size_id" class="w-full border rounded px-3 py-2">
                    <option v-for="size in bannerSizes" :key="size.id" :value="size.id">
                        {{ size.label }}
                    </option>
                </select>
            </div>
            <div>
                <label class="block mb-1 font-medium">GIF File</label>
                <div class="mb-2 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded cursor-pointer p-6 hover:bg-gray-50"
                    @click="fileInput && fileInput.value && fileInput.value.click()"
                    @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false" @drop.prevent="onDrop"
                    :class="{ 'border-green-500 bg-green-50': isDragging }">
                    <img :src="fileUrl" alt="Current GIF" class="w-40 h-40 object-contain border rounded mb-2" />
                    <span class="text-gray-500 text-sm">Click or drag GIF file here to upload</span>
                    <input ref="fileInput" type="file" accept=".gif" class="hidden" @change="handleFileChange" />
                </div>
            </div>
            <div class="flex space-x-4 mt-4">
                <button type="button" :disabled="form.processing" @click="handleSubmit"
                    class="w-full cursor-pointer rounded-lg bg-indigo-600 px-6 py-3 text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    Update
                </button>
                <a :href="`/previews/show/${preview.id}`"
                    class="w-full text-center rounded-lg bg-red-600 px-6 py-3 text-white shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Back
                </a>
            </div>
        </div>
    </AppLayout>
</template>