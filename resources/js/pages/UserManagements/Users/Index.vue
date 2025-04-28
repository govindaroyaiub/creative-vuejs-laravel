<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/pages/UserManagements/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { LoaderCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: '/user-managements/users' }];

const page = usePage();
const users = ref(page.props.users ?? []);
const routes = ref(page.props.routes ?? []);

const search = ref('');
const addingUser = ref(false);
const newUserName = ref('');
const newUserEmail = ref('');
const newUserRole = ref('user');
const newUserPermissions = ref<string[]>(['/dashboard', '/previews']);
const savingUser = ref(false);
const resettingPasswordUserId = ref<number | null>(null); // ✅ for individual reset password loading

const permissionsModalVisible = ref(false);
const permissionsModalUserId = ref<number | null>(null);
const selectedPermissions = ref<string[]>([]);
const savingPermissions = ref(false);

const filteredUsers = computed(() => {
    const query = search.value.toLowerCase();
    return users.value.filter(
        (u: any) =>
            u.name.toLowerCase().includes(query) || u.email.toLowerCase().includes(query) || (u.designation_name ?? '').toLowerCase().includes(query),
    );
});

const openPermissionsModal = (user: any) => {
    permissionsModalVisible.value = true;
    permissionsModalUserId.value = user.id;
    selectedPermissions.value = user.permissions ? [...user.permissions] : [];
};

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

const saveUser = async () => {
    if (!newUserName.value.trim() || !newUserEmail.value.trim() || !newUserRole.value) return;

    savingUser.value = true;

    try {
        const response = await axios.post(route('user-managements-users-create'), {
            name: newUserName.value,
            email: newUserEmail.value,
            role: newUserRole.value,
            permissions: newUserPermissions.value.length > 0 ? newUserPermissions.value : ['/welcome-to-planetnine/register'],
        });

        if (response.data?.newUser) {
            users.value.push(response.data.newUser);
            users.value.sort((a, b) => a.name.localeCompare(b.name));
        }

        addingUser.value = false;
        newUserName.value = '';
        newUserEmail.value = '';
        newUserRole.value = 'user';
        newUserPermissions.value = [];

        Swal.fire('Success!', 'User created successfully.', 'success');
    } catch (error) {
        Swal.fire('Error!', 'Failed to create user.', 'error');
    } finally {
        savingUser.value = false;
    }
};

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

const resetPassword = async (id: number) => {
    const result = await Swal.fire({
        title: 'Reset Password?',
        text: "This will reset the user's password to a temporary one.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, reset it!',
    });

    if (result.isConfirmed) {
        resettingPasswordUserId.value = id;

        router.post(
            route('user-managements-users-update-password', id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    // ✅ Update permissions manually on frontend
                    const userIndex = users.value.findIndex((u: any) => u.id === id);
                    if (userIndex !== -1) {
                        const user = users.value[userIndex];
                        if (!user.permissions.includes('/change-password')) {
                            user.permissions.push('/change-password');
                        }
                    }

                    Swal.fire('Success!', 'Password reset successfully.', 'success');
                },
                onError: () => {
                    Swal.fire('Error!', 'Failed to reset password.', 'error');
                },
                onFinish: () => {
                    resettingPasswordUserId.value = null;
                },
            },
        );
    }
};

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

const selectAll = () => (selectedPermissions.value = ['*']);
const clearAll = () => (selectedPermissions.value = []);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Users" />
        <SettingsLayout>
            <div class="mx-auto max-w-6xl space-y-6 px-4">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-bold">Users</h2>
                    <p class="text-sm text-muted-foreground">Manage all users and their permissions.</p>
                </div>

                <div class="flex items-center justify-between gap-4">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search users..."
                        class="w-full max-w-sm rounded-md border px-4 py-2 dark:bg-gray-700 dark:text-white"
                    />
                    <Button size="sm" class="whitespace-nowrap" @click="addingUser = true">Add</Button>
                </div>

                <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-100 text-xs uppercase dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-6 py-3 text-center font-semibold">#</th>
                                <th class="px-6 py-3 text-center font-semibold">Name / Email</th>
                                <th class="px-6 py-3 text-center font-semibold">Role</th>
                                <th class="px-6 py-3 text-center font-semibold">Permissions</th>
                                <th class="px-6 py-3 text-center font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-if="addingUser" class="text-center">
                                <td class="px-6 py-4">#</td>
                                <td class="px-6 py-4">
                                    <input
                                        v-model="newUserName"
                                        placeholder="Name"
                                        class="w-full rounded-md border px-2 py-1 dark:bg-gray-700 dark:text-white"
                                    />
                                    <input
                                        v-model="newUserEmail"
                                        placeholder="Email"
                                        class="mt-2 w-full rounded-md border px-2 py-1 dark:bg-gray-700 dark:text-white"
                                    />
                                </td>
                                <td class="px-6 py-4">
                                    <select v-model="newUserRole" class="w-full rounded-md border px-2 py-1 dark:bg-gray-700 dark:text-white">
                                        <option disabled value="">Select role</option>
                                        <option value="super_admin">Super Admin</option>
                                        <option value="admin">Admin</option>
                                        <option value="user">User</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-h-36 space-y-1 overflow-y-auto rounded-md border bg-gray-50 p-2 dark:bg-gray-900">
                                        <div v-for="route in routes" :key="route.id" class="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                v-model="newUserPermissions"
                                                :value="route.href"
                                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <label class="text-sm">{{ route.title }}</label>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Button size="sm" class="w-20" @click="saveUser" :disabled="savingUser">
                                            <template v-if="savingUser">
                                                <LoaderCircle class="mx-auto h-4 w-4 animate-spin" />
                                            </template>
                                            <template v-else>Save</template>
                                        </Button>
                                        <Button size="sm" variant="outline" class="w-20" @click="addingUser = false" :disabled="savingUser"
                                            >Cancel</Button
                                        >
                                    </div>
                                </td>
                            </tr>

                            <tr v-for="(user, index) in filteredUsers" :key="user.id" class="text-center hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4">{{ index + 1 }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ user.name }}
                                    <hr />
                                    <small>{{ user.email }}</small>
                                </td>
                                <td class="px-6 py-4 capitalize">{{ user.role ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    <Button size="sm" variant="secondary" @click="openPermissionsModal(user)">Permissions</Button>
                                </td>
                                <td class="space-x-2 px-6 py-4">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="w-28"
                                        @click="resetPassword(user.id)"
                                        :disabled="resettingPasswordUserId === user.id"
                                    >
                                        <template v-if="resettingPasswordUserId === user.id">
                                            <LoaderCircle class="mx-auto h-4 w-4 animate-spin" />
                                        </template>
                                        <template v-else> Reset Password </template>
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deleteUser(user.id)">Delete</Button>
                                </td>
                            </tr>

                            <tr v-if="filteredUsers.length === 0 && !addingUser">
                                <td colspan="5" class="py-8 text-center text-gray-400">No users found.</td>
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
                        All routes are enabled (Wildcard '*')
                    </div>

                    <div class="mb-4 flex justify-center space-x-4">
                        <Button size="sm" variant="secondary" @click="selectAll">Select All</Button>
                        <Button size="sm" variant="outline" @click="clearAll">Clear All</Button>
                    </div>

                    <div class="max-h-72 space-y-3 overflow-y-auto rounded-md border bg-gray-50 p-3 dark:bg-gray-900">
                        <div v-for="route in routes" :key="route.id" class="flex items-center justify-between rounded border-b pb-2">
                            <div>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ route.title }}</span>
                                <div class="text-xs text-gray-400">{{ route.href }}</div>
                            </div>
                            <input
                                type="checkbox"
                                :checked="selectedPermissions.includes('*') || selectedPermissions.includes(route.href)"
                                @change="togglePermission(route.href)"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
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
