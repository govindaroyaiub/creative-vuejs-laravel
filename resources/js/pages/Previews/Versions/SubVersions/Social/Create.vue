<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const version = computed(() => page.props.version);
const preview = computed(() => version.value.preview);

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.id}` },
    { title: 'Create Social Sub Version', href: '#' },
];

const form = useForm({
    name: '',
    socials: [] as {
        file: File;
        name: string;
        preview: string;
    }[],
});

const socialFileInput = ref<HTMLInputElement | null>(null);
const isSocialDragging = ref(false);

const triggerSocialInput = () => socialFileInput.value?.click();

const handleSocialFileUpload = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const files = input.files;
    if (!files) return;

    const images = Array.from(files).filter(f => /\.(jpe?g|png)$/i.test(f.name));
    form.socials = images.map(file => ({
        file,
        name: '',
        preview: URL.createObjectURL(file),
    }));

    input.value = '';
};

const handleSocialDrop = (e: DragEvent) => {
    isSocialDragging.value = false;
    const files = e.dataTransfer?.files;
    if (!files) return;

    const images = Array.from(files).filter(f => /\.(jpe?g|png)$/i.test(f.name));
    form.socials = images.map(file => ({
        file,
        name: '',
        preview: URL.createObjectURL(file),
    }));
};

const removeSocial = (i: number) => {
    URL.revokeObjectURL(form.socials[i].preview);
    form.socials.splice(i, 1);
};

const allSocialNamed = computed(() =>
    form.socials.length > 0 && form.socials.every(s => s.name.trim() !== '')
);

const handleSubmit = () => {
    const payload = new FormData();
    payload.append('name', form.name);
    form.socials.forEach((s, i) => {
        payload.append(`socials[${i}][file]`, s.file);
        payload.append(`socials[${i}][name]`, s.name);
        payload.append(`socials[${i}][position]`, String(i));
    });

    axios.post(`/previews/version/${version.value.id}/social/add/subVersion`, payload)
        .then((response) => {
            if (response.data?.redirect_to) {
                window.location.href = response.data.redirect_to;
            }
        })
        .catch(() => {
            Swal.fire('Error', 'Something went wrong.', 'error');
        });
};
</script>

<template>

    <Head title="Create Social Sub Version" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-4xl mx-auto space-y-6 w-full">
            <!-- Name -->
            <div>
                <label class="block text-sm font-medium mb-1">Sub Version Name</label>
                <input v-model="form.name" type="text"
                    class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white"
                    placeholder="e.g. Version 1" required />
            </div>

            <!-- Upload Images -->
            <div class="cursor-pointer border-2 border-dashed border-gray-300 p-6 text-center rounded-lg dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800"
                @click="triggerSocialInput" @dragover.prevent="isSocialDragging = true"
                @dragleave.prevent="isSocialDragging = false" @drop.prevent="handleSocialDrop"
                :class="{ 'border-green-500 bg-green-50 dark:bg-green-900': isSocialDragging }">
                <span class="text-sm text-gray-600 dark:text-gray-300">Click or drag PNG/JPG images here to
                    upload</span>
                <input ref="socialFileInput" type="file" class="hidden" multiple accept=".png,.jpg,.jpeg"
                    @change="handleSocialFileUpload" />
            </div>

            <p class="text-sm text-red-500 dark:text-red-400">
                Drag to sort. Click ❌ to remove. New upload replaces previous files.
            </p>

            <!-- Social List -->
            <draggable v-model="form.socials" item-key="file.name" handle=".handle" class="space-y-3"
                ghost-class="ghost">
                <template #item="{ element: social, index }">
                    <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
                        <div class="flex items-center gap-2 w-10 text-gray-500 dark:text-gray-300">
                            <span class="font-mono text-sm w-4 text-right">{{ index + 1 }}.</span>
                            <span class="handle cursor-move">☰</span>
                        </div>
                        <img :src="social.preview" alt="preview" class="w-12 h-12 object-contain rounded border" />
                        <input v-model="social.name" type="text" placeholder="Name"
                            class="w-1/2 rounded border px-3 py-1 text-sm dark:bg-gray-800 dark:text-white" />
                        <span class="truncate w-full text-xs text-gray-800 dark:text-white">
                            {{ social.file.name }}
                        </span>
                        <button class="ml-2 text-red-500 hover:text-red-700" @click="removeSocial(index)"
                            title="Remove">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </template>
            </draggable>

            <!-- Submit Button -->
            <div class="flex space-x-4">
                <button type="submit" :disabled="!allSocialNamed || form.processing" @click="handleSubmit"
                    class="w-full rounded-lg bg-green-600 px-6 py-3 text-white shadow-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 dark:bg-green-500 dark:hover:bg-green-600">
                    Save
                </button>
                <a :href="`/previews/show/${preview.id}`"
                    class="w-full text-center rounded-lg bg-red-600 px-6 py-3 text-white shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
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