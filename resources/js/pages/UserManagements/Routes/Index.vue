<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/pages/UserManagements/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Routes', href: '/user-managements/routes' }];

const page = usePage();
const routes = ref(page.props.routes ?? []);
const search = ref('');

const filteredRoutes = computed(() => {
    const query = search.value.toLowerCase();
    return routes.value.filter((r: any) =>
        r.title.toLowerCase().includes(query) || r.href.toLowerCase().includes(query)
    );
});

const adding = ref(false);
const newRoute = ref({ title: '', href: '' });
const editingId = ref<number | null>(null);
const editForm = ref({ title: '', href: '' });

const startAdding = () => {
    adding.value = true;
    newRoute.value = { title: '', href: '' };
};

const cancelAdding = () => {
    adding.value = false;
};

const saveRoute = () => {
    if (!newRoute.value.title.trim() || !newRoute.value.href.trim()) return;

    router.post(route('user-managements-routes-create-post'), newRoute.value, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            Swal.fire('Added!', 'Route created.', 'success');
            adding.value = false;
            newRoute.value = { title: '', href: '' };
        },
        onError: () => {
            Swal.fire('Error!', 'Failed to create route.', 'error');
        },
    });
};

const startEditing = (route: any) => {
    editingId.value = route.id;
    editForm.value = { title: route.title, href: route.href };
};

const cancelEditing = () => {
    editingId.value = null;
    editForm.value = { title: '', href: '' };
};

const updateRoute = (id: number) => {
    router.put(route('user-managements-routes-update', id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            const index = routes.value.findIndex((r: any) => r.id === id);
            if (index !== -1) {
                routes.value[index] = { ...routes.value[index], ...editForm.value };
            }
            Swal.fire('Updated!', 'Route updated successfully.', 'success');
            editingId.value = null;
            editForm.value = { title: '', href: '' };
        },
        onError: () => {
            Swal.fire('Error!', 'Failed to update.', 'error');
        },
    });
};

const deleteRoute = async (id: number) => {
    const confirm = await Swal.fire({
        title: 'Are you sure?',
        text: 'This cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (confirm.isConfirmed) {
        router.delete(route('user-managements-routes-delete', id), {
            preserveScroll: true,
            onSuccess: () => {
                routes.value = routes.value.filter((r: any) => r.id !== id);
                Swal.fire('Deleted!', 'Route deleted successfully.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to delete.', 'error');
            },
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Routes" />
        <SettingsLayout>
            <div class="space-y-6">
                <div class="flex flex-col items-start">
                    <h2 class="text-lg font-bold">Routes</h2>
                    <small>Manage all available routes for user permissions</small>
                </div>

                <div class="mb-6 flex items-center justify-between space-x-4">
                    <input v-model="search" placeholder="Search routes..."
                        class="w-full max-w-xs rounded-2xl border px-3 py-2 text-left dark:bg-neutral-800 dark:text-white" />
                    <Button size="sm" class="rounded-xl" @click="startAdding" v-if="!adding"> Add </Button>
                </div>

                <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-neutral-800">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-100 text-xs uppercase dark:bg-neutral-900 dark:text-gray-300 text-center">
                            <tr>
                                <th class="px-6 py-3">#</th>
                                <th class="px-6 py-3">Title</th>
                                <th class="px-6 py-3">Href</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-center">
                            <tr v-if="adding" class="border-b dark:border-gray-700">
                                <td class="px-6 py-4">#</td>
                                <td class="px-6 py-4">
                                    <input v-model="newRoute.title" placeholder="Route Title"
                                        class="w-full rounded border px-2 py-1 text-center dark:bg-neutral-800 dark:text-white" />
                                </td>
                                <td class="px-6 py-4">
                                    <input v-model="newRoute.href" placeholder="Route Href"
                                        class="w-full rounded border px-2 py-1 text-center dark:bg-neutral-800 dark:text-white" />
                                </td>
                                <td class="px-6 py-4 space-x-2">
                                    <Button size="sm" @click="saveRoute">Save</Button>
                                    <Button size="sm" variant="outline" @click="cancelAdding">Cancel</Button>
                                </td>
                            </tr>

                            <tr v-for="(route, index) in filteredRoutes" :key="route.id"
                                class="border-b dark:border-gray-700">
                                <td class="px-6 py-4">{{ index + 1 }}</td>
                                <td class="px-6 py-4">
                                    <template v-if="editingId !== route.id">{{ route.title }}</template>
                                    <template v-else>
                                        <input v-model="editForm.title"
                                            class="w-full rounded border px-2 py-1 text-center dark:bg-neutral-700 dark:text-white" />
                                    </template>
                                </td>
                                <td class="px-6 py-4">
                                    <template v-if="editingId !== route.id">{{ route.href }}</template>
                                    <template v-else>
                                        <input v-model="editForm.href"
                                            class="w-full rounded border px-2 py-1 text-center dark:bg-neutral-700 dark:text-white" />
                                    </template>
                                </td>
                                <td class="space-x-2 px-6 py-4">
                                    <template v-if="editingId === route.id">
                                        <Button size="sm" @click="updateRoute(route.id)">Update</Button>
                                        <Button size="sm" variant="outline" @click="cancelEditing">Cancel</Button>
                                    </template>
                                    <template v-else>
                                        <Button size="sm" variant="outline" @click="startEditing(route)">Edit</Button>
                                        <Button size="sm" variant="destructive"
                                            @click="deleteRoute(route.id)">Delete</Button>
                                    </template>
                                </td>
                            </tr>

                            <tr v-if="filteredRoutes.length === 0 && !adding">
                                <td colspan="4" class="py-6 text-center text-gray-400">No routes found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>