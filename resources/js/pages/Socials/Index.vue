<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Pencil, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Socials', href: '/socials' },
];

const page = usePage();
const socials = computed(() => page.props.socials);
const search = ref(page.props.search ?? '');

// Watch for input changes and query backend
watch(search, (value) => {
    router.get(route('socials'), { search: value }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
});

const editingId = ref<number | null>(null);
const editName = ref('');
const adding = ref(false);
const newName = ref('');

const startEditing = (social: any) => {
    editingId.value = social.id;
    editName.value = social.name;
};

const saveEdit = (id: number) => {
    if (!editName.value.trim()) return;

    router.put(
        route('socials-update', id),
        { name: editName.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                editingId.value = null;
                Swal.fire('Updated!', 'Social updated successfully.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to update social.', 'error');
            },
        },
    );
};

const deleteSocial = async (id: number) => {
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
        router.delete(route('socials-delete', id), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('Deleted!', 'Social deleted successfully.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to delete social.', 'error');
            },
        });
    }
};

const startAdding = () => {
    adding.value = true;
    newName.value = '';
};

const cancelAdding = () => {
    adding.value = false;
    newName.value = '';
};

const saveNewSocial = () => {
    if (!newName.value.trim()) return;

    router.post(
        route('socials-create-post'),
        { name: newName.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                adding.value = false;
                Swal.fire('Added!', 'Social created successfully.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to create social.', 'error');
            },
        },
    );
};
</script>

<template>

    <Head title="Socials" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <input v-model="search" placeholder="Search..."
                    class="w-full max-w-xs rounded border px-4 py-2 dark:bg-gray-700 dark:text-white" />
                <button @click="startAdding" class="ml-4 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                    <CirclePlus class="mr-1 inline h-5 w-5" />
                    Add
                </button>
            </div>

            <!-- Socials Table -->
            <table class="w-full rounded bg-white shadow dark:bg-gray-800">
                <thead class="bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr class="text-center text-sm uppercase">
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Inline Add Row -->
                    <tr v-if="adding" class="border-t text-center text-sm dark:border-gray-700">
                        <td class="px-4 py-2">#</td>
                        <td class="px-4 py-2">
                            <input v-model="newName" type="text" placeholder="Enter name"
                                class="w-1/2 rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" />
                        </td>
                        <td class="space-x-2 px-4 py-2">
                            <button @click="saveNewSocial" class="text-sm text-green-600 hover:underline">Save</button>
                            <button @click="cancelAdding" class="text-sm text-gray-500 hover:underline">Cancel</button>
                        </td>
                    </tr>

                    <!-- Existing Data Rows -->
                    <tr v-for="(social, index) in socials.data" :key="social.id"
                        class="border-t text-center text-sm dark:border-gray-700">
                        <td class="px-4 py-2">{{ index + 1 }}</td>
                        <td class="px-4 py-2">
                            <div v-if="editingId !== social.id">
                                {{ social.name }}
                            </div>
                            <div v-else>
                                <input v-model="editName" type="text"
                                    class="w-1/2 rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" />
                            </div>
                        </td>
                        <td class="space-x-2 px-4 py-2">
                            <template v-if="editingId === social.id">
                                <button @click="saveEdit(social.id)"
                                    class="text-sm text-blue-600 hover:underline">Update</button>
                                <button @click="editingId = null"
                                    class="text-sm text-red-500 hover:underline">Cancel</button>
                            </template>
                            <template v-else>
                                <button @click="startEditing(social)" class="text-blue-600 hover:text-blue-800">
                                    <Pencil class="inline h-5 w-5" />
                                </button>
                                <button @click="deleteSocial(social.id)" class="text-red-600 hover:text-red-800">
                                    <Trash2 class="inline h-5 w-5" />
                                </button>
                            </template>
                        </td>
                    </tr>

                    <tr v-if="socials.data.length === 0 && !adding">
                        <td colspan="3" class="px-4 py-4 text-center text-gray-500">No socials found.</td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-6 flex justify-center space-x-2" v-if="socials.data.length > 0 && socials.links.length">
                <template v-for="link in socials.links" :key="link.label">
                    <component :is="link.url ? 'a' : 'span'" v-html="link.label" :href="link.url"
                        class="rounded border px-4 py-2 text-sm" :class="{
                            'bg-indigo-600 text-white': link.active,
                            'cursor-not-allowed text-gray-400': !link.url,
                            'hover:bg-gray-200 dark:hover:bg-gray-700': link.url && !link.active,
                        }" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>