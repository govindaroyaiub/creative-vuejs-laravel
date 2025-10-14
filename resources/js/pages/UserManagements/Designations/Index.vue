<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/pages/UserManagements/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Designations', href: '/user-managements/designations' }];

const page = usePage();
const designations = ref(page.props.designations ?? []);
const search = ref('');

const filteredDesignations = computed(() => {
    const query = search.value.toLowerCase();
    return designations.value.filter((d: any) => d.name.toLowerCase().includes(query));
});

const adding = ref(false);
const newDesignation = ref('');
const editingId = ref<number | null>(null);
const editName = ref('');

const startAdding = () => {
    adding.value = true;
    newDesignation.value = '';
};

const cancelAdding = () => {
    adding.value = false;
};

const saveDesignation = () => {
    if (!newDesignation.value.trim()) return;

    router.post(route('user-managements-designations-create-post'), {
        name: newDesignation.value
    }, {
        preserveScroll: true,
        preserveState: false,  // 🛠 important here
        onSuccess: () => {
            Swal.fire('Added!', 'Designation created.', 'success');
            adding.value = false;
            newDesignation.value = '';
        },
        onError: (errors) => {
            if (errors.name) {
                Swal.fire('Error!', errors.name, 'error');
            } else {
                Swal.fire('Error!', 'Something went wrong.', 'error');
            }
        },
    });
};

const startEditing = (designation: any) => {
    editingId.value = designation.id;
    editName.value = designation.name;
};

const cancelEditing = () => {
    editingId.value = null;
    editName.value = '';
};

const updateDesignation = (id: number) => {
    if (!editName.value.trim()) return;

    router.put(
        route('user-managements-designations-update', id),
        { name: editName.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                const index = designations.value.findIndex((d: any) => d.id === id);
                if (index !== -1) {
                    designations.value[index].name = editName.value;
                }
                Swal.fire('Updated!', 'Designation updated.', 'success');
                editingId.value = null;
                editName.value = '';
            },
            onError: (errors) => {
                if (errors.name) {
                    Swal.fire('Error!', errors.name, 'error');
                } else {
                    Swal.fire('Error!', 'Something went wrong.', 'error');
                }
            },
        },
    );
};

const deleteDesignation = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This cannot be undone!',
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
                designations.value = designations.value.filter((d: any) => d.id !== id);
                Swal.fire('Deleted!', 'Designation deleted.', 'success');
            },
            onError: () => Swal.fire('Error!', 'Failed to delete.', 'error'),
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Designations" />
        <SettingsLayout>
            <div class="space-y-6">
                <div class="flex flex-col items-start justify-between">
                    <h2 class="text-lg font-bold">Designations</h2>
                    <small>List of Designations to manage</small>
                </div>

                <div class="mb-6 flex items-center justify-between space-x-4">
                    <input v-model="search" placeholder="Search designations..."
                        class="w-full max-w-xs rounded-2xl border px-3 py-2 dark:bg-neutral-800 dark:text-white" />
                    <Button class="rounded-xl" size="sm" @click="startAdding" v-if="!adding"> Add </Button>
                </div>

                <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-neutral-800">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-black">
                        <thead class="bg-gray-100 text-xs uppercase dark:bg-neutral-900 dark:text-gray-300">
                            <tr>
                                <th class="px-6 py-3">#</th>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="adding" class="border-b dark:border-gray-700">
                                <td class="px-6 py-4">#</td>
                                <td class="px-6 py-4">
                                    <input v-model="newDesignation"
                                        class="w-full rounded border px-2 py-1 dark:bg-neutral-700 dark:text-white"
                                        placeholder="New designation" />
                                </td>
                                <td class="space-x-2 px-6 py-4 text-center">
                                    <Button size="sm" @click="saveDesignation">Save</Button>
                                    <Button size="sm" variant="outline" @click="cancelAdding">Cancel</Button>
                                </td>
                            </tr>

                            <tr v-for="(designation, index) in filteredDesignations" :key="designation.id"
                                class="border-b dark:border-gray-700">
                                <td class="px-6 py-4 text-center">{{ index + 1 }}</td>
                                <td class="px-6 py-4 text-center">
                                    <template v-if="editingId !== designation.id">{{ designation.name }}</template>
                                    <template v-else>
                                        <input v-model="editName"
                                            class="w-full rounded border px-2 py-1 dark:bg-neutral-700 dark:text-white" />
                                    </template>
                                </td>
                                <td class="space-x-2 px-6 py-4 text-center">
                                    <template v-if="editingId === designation.id">
                                        <Button size="sm" variant="outline"
                                            @click="updateDesignation(designation.id)">Update</Button>
                                        <Button size="sm" variant="outline" @click="cancelEditing">Cancel</Button>
                                    </template>
                                    <template v-else>
                                        <Button size="sm" variant="outline"
                                            @click="startEditing(designation)">Edit</Button>
                                        <Button size="sm" variant="destructive"
                                            @click="deleteDesignation(designation.id)">Delete</Button>
                                    </template>
                                </td>
                            </tr>

                            <tr v-if="filteredDesignations.length === 0 && !adding">
                                <td colspan="3" class="py-6 text-center text-gray-400">No designations found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
