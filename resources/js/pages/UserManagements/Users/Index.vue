<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/pages/UserManagements/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: '/user-managements/users' }];

const page = usePage();
const users = ref(page.props.users ?? []);
const routes = ref(page.props.routes ?? []);
const search = ref('');

// Modal State
const permissionsModalVisible = ref(false);
const permissionsModalUserId = ref<number | null>(null);
const selectedPermissions = ref<string[]>([]);
const savingPermissions = ref(false); // 🛠️ new

const filteredUsers = computed(() => {
    const query = search.value.toLowerCase();
    return users.value.filter(
        (u: any) =>
            u.name.toLowerCase().includes(query) || u.email.toLowerCase().includes(query) || (u.designation_name ?? '').toLowerCase().includes(query),
    );
});

// Open Permissions Modal
const openPermissionsModal = (user: any) => {
    permissionsModalVisible.value = true;
    permissionsModalUserId.value = user.id;
    selectedPermissions.value = user.permissions ? [...user.permissions] : [];
};

// Save Updated Permissions
const savePermissions = () => {
    if (!permissionsModalUserId.value) return;

    savingPermissions.value = true;

    router.put(
        route('user-managements-users-update-permissions', permissionsModalUserId.value),
        {
            permissions: selectedPermissions.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                const userIndex = users.value.findIndex((u: any) => u.id === permissionsModalUserId.value);
                if (userIndex !== -1) {
                    users.value[userIndex].permissions = [...selectedPermissions.value];
                }

                setTimeout(() => {
                    permissionsModalVisible.value = false;
                    permissionsModalUserId.value = null;
                    savingPermissions.value = false;

                    Swal.fire('Success!', 'Permissions updated.', 'success');
                }, 200);
            },
            onError: () => {
                savingPermissions.value = false;
                Swal.fire('Error!', 'Failed to update permissions.', 'error');
            },
        },
    );
};

// Select All Permissions
const selectAll = () => {
    selectedPermissions.value = ['*'];
};

// Clear All Permissions
const clearAll = () => {
    selectedPermissions.value = [];
};

// Toggle Single Permission
const togglePermission = (href: string) => {
    if (selectedPermissions.value.includes('*')) {
        selectedPermissions.value = routes.value.map((r) => r.href);
    }

    if (selectedPermissions.value.includes(href)) {
        selectedPermissions.value = selectedPermissions.value.filter((p) => p !== href);
    } else {
        selectedPermissions.value.push(href);
    }

    const allRoutes = routes.value.map((r) => r.href).sort();
    const currentPermissions = [...selectedPermissions.value].sort();

    if (JSON.stringify(allRoutes) === JSON.stringify(currentPermissions)) {
        selectedPermissions.value = ['*'];
    }
};

// Delete User
const deleteUser = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the user.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        router.delete(route('user-managements-users-delete', id), {
            preserveScroll: true,
            onSuccess: () => {
                users.value = users.value.filter((u: any) => u.id !== id);
                Swal.fire('Deleted!', 'User has been deleted.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to delete user.', 'error');
            },
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Users" />

        <SettingsLayout>
            <div class="mx-auto max-w-6xl space-y-6 px-4">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-bold">Users</h2>
                    <p class="text-sm text-muted-foreground">Manage all registered users and their routes permissions.</p>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search users..."
                        class="w-full max-w-sm rounded-md border px-4 py-2 dark:bg-gray-700 dark:text-white"
                    />
                    <Button size="sm" class="whitespace-nowrap">Add User</Button>
                </div>

                <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-100 text-xs uppercase dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-6 py-3 text-center font-semibold">#</th>
                                <th class="px-6 py-3 text-center font-semibold">Name / Email</th>
                                <th class="px-6 py-3 text-center font-semibold">Designation / Role</th>
                                <th class="px-6 py-3 text-center font-semibold">Permissions</th>
                                <th class="px-6 py-3 text-center font-semibold">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="(user, index) in filteredUsers" :key="user.id" class="text-center hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4">{{ index + 1 }}</td>

                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ user.name }}
                                    <hr />
                                    <small>{{ user.email }}</small>
                                </td>

                                <td class="px-6 py-4">
                                    {{ user.designation_name ?? '-' }}
                                    <hr />
                                    <small>{{ user.role ?? '-' }}</small>
                                </td>

                                <td class="px-6 py-4">
                                    <Button size="sm" variant="secondary" @click="openPermissionsModal(user)">Permissions</Button>
                                </td>

                                <td class="space-x-2 px-6 py-4">
                                    <Button size="sm" variant="destructive" @click="deleteUser(user.id)">Delete</Button>
                                </td>
                            </tr>

                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">No users found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Permissions Modal -->
            <div v-if="permissionsModalVisible" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
                    <h2 class="mb-4 text-center text-xl font-bold text-gray-800 dark:text-white">Manage Permissions</h2>

                    <div v-if="selectedPermissions.includes('*')" class="mb-4 rounded bg-green-100 p-2 text-center text-sm text-green-600">
                        All routes are enabled (Wildcard permission '*')
                    </div>

                    <!-- Select All / Clear All -->
                    <div class="mb-4 flex justify-center space-x-4">
                        <Button size="sm" variant="secondary" @click="selectAll">Select All</Button>
                        <Button size="sm" variant="outline" @click="clearAll">Clear All</Button>
                    </div>

                    <!-- Route Checkboxes -->
                    <div class="max-h-72 space-y-3 overflow-y-auto pr-2">
                        <div v-for="route in routes" :key="route.id" class="flex items-center justify-between rounded border-b pb-2">
                            <div>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ route.title }}</span>
                                <div class="text-xs text-gray-400">{{ route.href }}</div>
                            </div>

                            <div>
                                <input
                                    type="checkbox"
                                    :checked="selectedPermissions.includes('*') || selectedPermissions.includes(route.href)"
                                    @change="togglePermission(route.href)"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-2">
                        <Button size="sm" variant="outline" @click="permissionsModalVisible = false" :disabled="savingPermissions">Cancel</Button>
                        <Button size="sm" @click="savePermissions" :disabled="savingPermissions">
                            <template v-if="savingPermissions">Saving...</template>
                            <template v-else>Save</template>
                        </Button>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>