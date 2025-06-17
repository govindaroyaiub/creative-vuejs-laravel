<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { ref, computed } from 'vue';
import draggable from 'vuedraggable';

const page = usePage();
const subVersion = page.props.subVersion;
const banners = ref([...page.props.banners]);
const preview = computed(() => page.props.preview);

const breadcrumbs = [
    { title: 'Previews', href: '/previews' },
    { title: preview.value.name, href: `/previews/show/${preview.value.id}` },
    { title: 'Re-arrange Banner Positions', href: '#' },
];

const form = useForm({
    banners: banners.value.map(b => ({ id: b.id, position: b.position }))
});

function handleUpdate() {
    // Assign new positions based on order
    form.banners = banners.value.map((b, i) => ({
        id: b.id,
        position: i
    }));
    form.post(`/previews/version/banner/edit/subVersion/position/${subVersion.id}`, {
        onSuccess: () => {
            Swal.fire('Success', 'Banner positions updated!', 'success');
        },
        onError: () => {
            Swal.fire('Error', 'Something went wrong', 'error');
        }
    });
}
</script>

<template>

    <Head :title="`Rearrange Banners - ${subVersion.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl mx-auto p-6 w-full">
            <h2 class="text-xl font-bold mb-6">Rearrange Banner Positions</h2>
            <draggable v-model="banners" item-key="id" handle=".handle" class="space-y-3" ghost-class="ghost">
                <template #item="{ element: banner, index }">
                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded shadow-sm">
                        <!-- Left: Handle and Index -->
                        <div class="flex items-center gap-2 min-w-[70px]">
                            <span class="handle cursor-move text-xl">☰</span>
                            <span class="font-mono w-8 text-right">{{ index + 1 }}.</span>
                        </div>
                        <!-- Center: Name and Size, spaced out -->
                        <div class="flex-1 flex justify-center gap-8">
                            <span class="truncate font-medium">{{ banner.name }}</span>
                            <span class="w-32 text-gray-500 text-sm text-center">{{ banner.size_label }}</span>
                        </div>
                        <!-- Right: Spacer for symmetry -->
                        <div class="min-w-[70px]"></div>
                    </div>
                </template>
            </draggable>
            <div class="pt-4 flex space-x-2">
                <button @click="handleUpdate" :disabled="form.processing"
                    class="w-full bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 focus:outline-none">
                    <span v-if="!form.processing">Update</span>
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