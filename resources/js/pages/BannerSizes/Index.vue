<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Pencil, Trash2, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';

const page = usePage();
const bannerSizes = computed(() => page.props.bannerSizes);
const search = ref(page.props.search ?? ''); // Preserve search across pagination

watch(search, (value) => {
    router.get(route('banner-sizes-index'), { search: value }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
});

const editingId = ref<number | null>(null);
const editForm = ref({ width: 0, height: 0 });

const adding = ref(false);
const newForm = ref({ width: '', height: '' });

const startEditing = (size: any) => {
    editingId.value = size.id;
    editForm.value = { width: size.width, height: size.height };
};

const cancelEditing = () => {
    editingId.value = null;
};

const saveEdit = (id: number) => {
    router.put(route('banner-sizes-update', id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            Swal.fire({
                icon: 'info',
                title: 'Success!',
                text: 'Banner size updated successfully!',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                timerProgressBar: true,
            });
        },
        onError: () => {
            Swal.fire('Error!', 'Size Already Exists!', 'error');
        },
    });
};

const startAdding = () => {
    adding.value = true;
    newForm.value = { width: '', height: '' };
};

const cancelAdding = () => {
    adding.value = false;
};

const saveNew = () => {
    router.post(route('banner-sizes-create-post'), newForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            if (page.props.flash?.error) {
                Swal.fire('Error!', page.props.flash.error, 'error');
            } else {
                adding.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Banner size created successfully!',
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    timerProgressBar: true,
                });
            }
        },
    });
};

const deleteBannerSize = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        router.delete(route('banner-sizes-delete', id), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('Deleted!', 'Banner size deleted.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Could not delete.', 'error');
            },
        });
    }
};
</script>

<template>

    <Head title="Banner Sizes" />
    <AppLayout :breadcrumbs="[{ title: 'Banner Sizes', href: '/banner-sizes' }]">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <input v-model="search" placeholder="Search..."
                    class="w-full max-w-xs rounded-2xl border px-4 py-2 dark:bg-neutral-800 dark:text-white" />
                <button @click="startAdding"
                    class="ml-4 rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                    <CirclePlus class="mr-1 inline h-5 w-5" />
                    Add Size
                </button>
            </div>

            <div class="overflow-x-auto rounded-2xl shadow">
                <table class="w-full rounded bg-white dark:bg-neutral-800 border">
                    <thead class="bg-gray-100 text-gray-700 dark:bg-neutral-900 dark:text-gray-300">
                        <tr class="text-center text-sm uppercase">
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Width</th>
                            <th class="px-4 py-2">Height</th>
                            <th class="px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- New Row -->
                        <tr v-if="adding" class="border-t text-center text-sm dark:border-neutral-900">
                            <td class="px-4 py-2">#</td>
                            <td class="px-4 py-2">
                                <input v-model="newForm.width" type="number"
                                    class="w-20 rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                            </td>
                            <td class="px-4 py-2">
                                <input v-model="newForm.height" type="number"
                                    class="w-20 rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                            </td>
                            <td class="px-4 py-2 space-x-2">
                                <button @click="cancelAdding"
                                    class="text-gray-500 hover:underline text-sm">Cancel</button>
                                <button @click="saveNew" class="text-green-600 hover:underline text-sm">Save</button>
                            </td>
                        </tr>

                        <!-- Existing Rows -->
                        <tr v-for="(size, index) in bannerSizes.data" :key="size.id"
                            class="border-t text-center text-sm uppercase dark:border-neutral-900 dark:hover:bg-neutral-700">
                            <td class="px-4 py-2">{{ index + 1 }}</td>

                            <td class="px-4 py-2">
                                <div v-if="editingId !== size.id">{{ size.width }}</div>
                                <input v-else v-model="editForm.width" type="number"
                                    class="w-20 rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                            </td>

                            <td class="px-4 py-2">
                                <div v-if="editingId !== size.id">{{ size.height }}</div>
                                <input v-else v-model="editForm.height" type="number"
                                    class="w-20 rounded-2xl border px-2 py-1 dark:bg-neutral-800 dark:text-white" />
                            </td>

                            <td class="space-x-2 px-4 py-2">
                                <template v-if="editingId === size.id">
                                    <button @click="cancelEditing"
                                        class="text-red-500 hover:underline text-sm">Cancel</button>
                                    <button @click="saveEdit(size.id)"
                                        class="text-blue-600 hover:underline text-sm">Update</button>
                                </template>
                                <template v-else>
                                    <button @click="startEditing(size)" class="text-blue-600 hover:text-blue-800">
                                        <Pencil class="inline h-5 w-5" />
                                    </button>
                                    <button @click="deleteBannerSize(size.id)" class="text-red-600 hover:text-red-800">
                                        <Trash2 class="inline h-5 w-5" />
                                    </button>
                                </template>
                            </td>
                        </tr>

                        <tr v-if="bannerSizes.data.length === 0 && !adding">
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">No banner sizes found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="mt-6 flex justify-center space-x-2" v-if="bannerSizes.data.length && bannerSizes.links.length">
                <template v-for="link in bannerSizes.links" :key="link.label">
                    <component :is="link.url ? 'a' : 'span'" v-html="link.label" :href="link.url"
                        class="rounded-xl border px-4 py-2 text-sm" :class="{
                            'bg-indigo-600 text-white': link.active,
                            'cursor-not-allowed text-gray-400': !link.url,
                            'hover:bg-gray-200 dark:hover:bg-black': link.url && !link.active,
                        }" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>