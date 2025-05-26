<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, computed } from 'vue';
import axios from 'axios'; // 🔁 use this only when leaving Inertia

const page = usePage();
const version = computed(() => page.props.version);
const preview = computed(() => version.value.preview);
const bannerSizes = computed(() => page.props.bannerSizes.map((s: any) => ({
    id: s.id,
    label: `${s.width}x${s.height}`,
    width: s.width,
    height: s.height,
})));

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.id}` },
    { title: 'Create Sub Version', href: '#' },
];

const form = useForm({
    name: '',
    banners: [] as {
        file: File;
        size_id: string | number;
        search?: string;
        dropdownOpen?: boolean;
        highlightIndex?: number;
    }[],
});

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const triggerInput = () => fileInput.value?.click();

const handleFileUpload = (e: Event) => {
    const input = e.target as HTMLInputElement;
    const files = input.files;
    if (!files) return;

    const zips = Array.from(files).filter(f => f.name.endsWith('.zip'));
    form.banners = zips.map(file => ({
        file,
        size_id: '',
        search: '',
        dropdownOpen: false,
        highlightIndex: 0,
    }));

    input.value = '';
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    const files = e.dataTransfer?.files;
    if (!files) return;

    const zips = Array.from(files).filter(f => f.name.endsWith('.zip'));
    form.banners = zips.map(file => ({
        file,
        size_id: '',
        search: '',
        dropdownOpen: false,
        highlightIndex: 0,
    }));
};

const removeBanner = (i: number) => form.banners.splice(i, 1);

const filteredSizes = (query: string) => {
    const q = query.toLowerCase();
    return bannerSizes.value.filter(s => s.label.toLowerCase().includes(q));
};

const selectSize = (i: number, size: any) => {
    const b = form.banners[i];
    b.size_id = size.id;
    b.search = size.label;
    b.dropdownOpen = false;
    b.highlightIndex = 0;
};

const getSizeLabel = (id: number | string) =>
    bannerSizes.value.find(s => s.id === id)?.label ?? '';

const handleBlur = (banner: any) => {
    setTimeout(() => (banner.dropdownOpen = false), 150);
};

const handleInputChange = (banner: any) => {
    if (!banner.search?.trim()) banner.size_id = '';
    banner.highlightIndex = 0;
};

const openDropdown = (banner: any) => {
    banner.dropdownOpen = true;
    banner.highlightIndex = 0;
};

const moveDown = (banner: any) => {
    const list = filteredSizes(banner.search);
    if (banner.highlightIndex < list.length - 1) banner.highlightIndex++;
};

const moveUp = (banner: any) => {
    if (banner.highlightIndex > 0) banner.highlightIndex--;
};

const selectHighlighted = (i: number, banner: any) => {
    const list = filteredSizes(banner.search);
    const selected = list[banner.highlightIndex];
    if (selected) selectSize(i, selected);
};

const allAssigned = computed(() =>
    form.banners.length > 0 && form.banners.every(b => b.size_id !== '')
);

const handleSubmit = () => {
    const payload = new FormData();
    payload.append('name', form.name);
    form.banners.forEach((b, i) => {
        payload.append(`banners[${i}][file]`, b.file);
        payload.append(`banners[${i}][size_id]`, String(b.size_id));
        payload.append(`banners[${i}][position]`, String(i));
    });

    axios.post(route('store-banner-subVersion-post', version.value.id), payload)
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

    <Head title="Create Sub Version" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-4xl mx-auto space-y-6 w-full">
            <!-- Name -->
            <div>
                <label class="block text-sm font-medium mb-1">Sub Version Name</label>
                <input v-model="form.name" type="text"
                    class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white"
                    placeholder="e.g. Version 1" required />
            </div>

            <!-- Upload ZIP -->
            <div class="cursor-pointer border-2 border-dashed border-gray-300 p-6 text-center rounded-lg dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800"
                @click="triggerInput" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop" :class="{ 'border-green-500 bg-green-50 dark:bg-green-900': isDragging }">
                <span class="text-sm text-gray-600 dark:text-gray-300">Click or drag ZIP files here to upload</span>
                <input ref="fileInput" type="file" class="hidden" multiple accept=".zip" @change="handleFileUpload" />
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Drag to sort. Click ❌ to remove. New upload replaces previous files.
            </p>

            <!-- Banner List -->
            <draggable v-model="form.banners" item-key="file.name" handle=".handle" class="space-y-3"
                ghost-class="ghost">
                <template #item="{ element: banner, index }">
                    <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-800 p-3 rounded shadow-sm">
                        <div class="flex items-center gap-2 w-10 text-gray-500 dark:text-gray-300">
                            <span class="font-mono text-sm w-4 text-right">{{ index + 1 }}.</span>
                            <span class="handle cursor-move">☰</span>
                        </div>

                        <span class="truncate w-full text-sm text-gray-800 dark:text-white">
                            📁 {{ banner.file.name }}
                        </span>

                        <!-- Size Search Dropdown -->
                        <div class="relative w-1/2">
                            <input v-model="banner.search" type="text" placeholder="Search size..."
                                @focus="openDropdown(banner)" @blur="handleBlur(banner)"
                                @keydown.down.prevent="moveDown(banner)" @keydown.up.prevent="moveUp(banner)"
                                @keydown.enter.prevent="selectHighlighted(index, banner)"
                                @input="handleInputChange(banner)"
                                class="w-full mb-1 rounded border px-3 py-1 text-sm dark:bg-gray-800 dark:text-white" />

                            <ul v-if="banner.dropdownOpen && filteredSizes(banner.search).length"
                                class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded border bg-white dark:bg-gray-800 shadow-lg">
                                <li v-for="(size, sIndex) in filteredSizes(banner.search)" :key="size.id"
                                    @mousedown.prevent="selectSize(index, size)" :class="[
                                        'px-3 py-1 text-sm cursor-pointer dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700',
                                        sIndex === banner.highlightIndex ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : '',
                                    ]">
                                    {{ size.label }}
                                </li>
                            </ul>

                            <div v-if="banner.size_id" class="text-xs text-gray-500 dark:text-gray-300">
                                Selected: {{ getSizeLabel(banner.size_id) }}
                            </div>
                        </div>

                        <button class="ml-2 text-red-500 hover:text-red-700" @click="removeBanner(index)"
                            title="Remove">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </template>
            </draggable>

            <!-- Submit Button -->
            <div class="flex space-x-4">
                <button type="submit" :disabled="!allAssigned || form.processing" @click="handleSubmit"
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