<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/pages/UserManagements/Layout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import Swal from 'sweetalert2';
import { ref, computed } from 'vue';
import { type BreadcrumbItem, type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Designations', href: '/user-managements/designations' },
];

const page = usePage<SharedData>();
const designations = ref<any[]>(page.props.designations ?? []);

const search = ref('');
const adding = ref(false);
const newForm = ref({ name: '' });

const editingId = ref<number | null>(null);
const editForm = ref({ name: '' });

const filteredDesignations = computed(() => {
    const query = search.value.toLowerCase();
    return designations.value.filter((d) => d.name.toLowerCase().includes(query));
});

const startAdding = () => {
    adding.value = true;
    newForm.value = { name: '' };
};

const cancelAdding = () => {
    adding.value = false;
};

const saveNew = () => {
    if (!newForm.value.name.trim()) return;

    router.post(route('user-managements-designations-create-post'), newForm.value, {
        preserveScroll: true,
        onSuccess: (page) => {
            designations.value.push(page.props.newDesignation);
            adding.value = false;
        },
    });
};

const startEditing = (designation: any) => {
    editingId.value = designation.id;
    editForm.value = { name: designation.name };
};

const cancelEditing = () => {
    editingId.value = null;
};

const saveEdit = (id: number) => {
    if (!editForm.value.name.trim()) return;

    router.put(route('user-managements-designations-update', id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            const index = designations.value.findIndex((d) => d.id === id);
            if (index !== -1) {
                designations.value[index].name = editForm.value.name;
            }
            editingId.value = null;
        },
    });
};

const deleteDesignation = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        router.delete(route('user-managements-designations-delete', id), {
            preserveScroll: true,
            onSuccess: () => {
                designations.value = designations.value.filter((d) => d.id !== id);
            },
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Designations" />

        <SettingsLayout>
            <div class="p-6">
                <!-- Top Bar -->
                <div class="mb-4 flex items-center justify-between">
                    <input v-model="search" placeholder="Search..." class="w-full max-w-xs rounded border px-4 py-2 dark:bg-gray-700 dark:text-white" />
                    <Button size="sm" class="ml-4" @click="startAdding" v-if="!adding">Add</Button>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-300">
                        <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                            <tr class="text-center">
                                <th class="px-6 py-3">#</th>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Add Row -->
                            <tr v-if="adding" class="border-b dark:border-gray-700 text-center">
                                <td class="px-6 py-4">#</td>
                                <td class="px-6 py-4">
                                    <input
                                        v-model="newForm.name"
                                        type="text"
                                        class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white"
                                        placeholder="Designation name"
                                    />
                                </td>
                                <td class="px-6 py-4 space-x-2">
                                    <button @click="saveNew" class="text-green-600 hover:underline text-sm">Save</button>
                                    <button @click="cancelAdding" class="text-gray-500 hover:underline text-sm">Cancel</button>
                                </td>
                            </tr>

                            <!-- Existing Rows -->
                            <tr v-for="(designation, index) in filteredDesignations" :key="designation.id" class="border-b dark:border-gray-700 text-center">
                                <td class="px-6 py-4">{{ index + 1 }}</td>
                                <td class="px-6 py-4">
                                    <template v-if="editingId !== designation.id">
                                        {{ designation.name }}
                                    </template>
                                    <template v-else>
                                        <input
                                            v-model="editForm.name"
                                            type="text"
                                            class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white"
                                        />
                                    </template>
                                </td>
                                <td class="px-6 py-4 space-x-2">
                                    <template v-if="editingId === designation.id">
                                        <button @click="saveEdit(designation.id)" class="text-blue-600 hover:underline text-sm">Update</button>
                                        <button @click="cancelEditing" class="text-red-500 hover:underline text-sm">Cancel</button>
                                    </template>
                                    <template v-else>
                                        <button @click="startEditing(designation)" class="text-blue-600 hover:underline text-sm">Edit</button>
                                        <button @click="deleteDesignation(designation.id)" class="text-red-600 hover:underline text-sm">Delete</button>
                                    </template>
                                </td>
                            </tr>

                            <tr v-if="filteredDesignations.length === 0 && !adding">
                                <td colspan="3" class="px-6 py-4 text-center text-gray-400">No designations found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>