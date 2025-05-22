<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { X } from 'lucide-vue-next';
import draggable from 'vuedraggable';
import Swal from 'sweetalert2';

const page = usePage();
const subVersion = computed(() => page.props.subVersion);
const version = computed(() => page.props.version);
const preview = computed(() => page.props.preview);
const bannerSizes = computed(() =>
    page.props.bannerSizes.map((s: any) => ({
        id: s.id,
        label: `${s.width}x${s.height}`,
    }))
);

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.id}` },
    { title: 'Edit Sub Version', href: '#' },
];

const form = useForm({
    name: subVersion.value.name,
    banners: [] as {
        file: File;
        size_id: number | string;
        search?: string;
        dropdownOpen?: boolean;
        highlightIndex?: number;
    }[],
});

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const triggerInput = () => fileInput.value?.click();

const handleFileUpload = (e: Event) => {
    const files = (e.target as HTMLInputElement)?.files;
    if (!files) return;

    const zips = Array.from(files).filter(f => f.name.endsWith('.zip'));
    form.banners = zips.map(file => ({
        file,
        size_id: '',
        search: '',
        dropdownOpen: false,
        highlightIndex: 0,
    }));

    (e.target as HTMLInputElement).value = '';
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
    const banner = form.banners[i];
    banner.size_id = size.id;
    banner.search = size.label;
    banner.dropdownOpen = false;
    banner.highlightIndex = 0;
};

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

const getSizeLabel = (id: number | string) =>
    bannerSizes.value.find(s => s.id === id)?.label ?? '';

const allAssigned = computed(() =>
    form.banners.length > 0 && form.banners.every(b => b.size_id)
);

const handleSubmit = () => {
    const payload = new FormData();
    payload.append('name', form.name);

    form.banners.forEach((b, i) => {
        payload.append(`banners[${i}][file]`, b.file);
        payload.append(`banners[${i}][size_id]`, String(b.size_id));
        payload.append(`banners[${i}][position]`, String(i));
    });

    router.post(`/previews/version/banner/edit/subVersion/${subVersion.value.id}`, payload, {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire('Success', 'SubVersion updated successfully.', 'success');
            form.banners = [];
        },
        onError: () => {
            Swal.fire('Error', 'Failed to update SubVersion.', 'error');
        },
    });
};
</script>

<template>

    <Head title="Edit Sub Version" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-4xl mx-auto space-y-6 w-full">
            <!-- Name -->
            <div>
                <label class="block text-sm font-medium mb-1">Sub Version Name</label>
                <input v-model="form.name" type="text"
                    class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
            </div>

            <!-- Upload ZIP -->
            <div class="cursor-pointer border-2 border-dashed border-gray-300 p-6 text-center rounded-lg dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800"
                @click="triggerInput" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop" :class="{ 'border-green-500 bg-green-50 dark:bg-green-900': isDragging }">
                <span class="text-sm text-gray-600 dark:text-gray-300">Click or drag ZIP files here to upload</span>
                <input ref="fileInput" type="file" class="hidden" multiple accept=".zip" @change="handleFileUpload" />
            </div>

            <p class="text-sm text-red-500 dark:text-red-400">
                New ZIP uploads will replace all previous banners.
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

            <!-- Submit -->
            <div class="flex space-x-4">
                <button type="submit" :disabled="form.processing || (!form.name.trim() && !allAssigned)"
                    @click="handleSubmit"
                    class="w-full rounded-lg bg-indigo-600 px-6 py-3 text-white shadow-md hover:bg-indigo-700">
                    Update
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