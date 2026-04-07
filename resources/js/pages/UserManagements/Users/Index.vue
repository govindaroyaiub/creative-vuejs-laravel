<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/pages/UserManagements/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';
import { LoaderCircle } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: '/user-managements/users' }];

const page = usePage();
const users = ref<any[]>((page.props.users as any[]) ?? []);
const routes = ref<any[]>((page.props.routes as any[]) ?? []);
const clients = ref<any[]>((page.props.clients as any[]) ?? []);

const search = ref('');
const addingUser = ref(false);
const newUserName = ref('');
const newUserEmail = ref('');
const newUserRole = ref('user');
const newUserSendMail = ref(false);
const newUserClientId = ref<number | null>(null);
const newUserPermissions = ref<string[]>(['/dashboard', '/previews']);
const savingUser = ref(false);
const resettingPasswordUserId = ref<number | null>(null);
const updatingRoleUserId = ref<number | null>(null);

const permissionsModalVisible = ref(false);
const permissionsModalUserId = ref<number | null>(null);
const selectedPermissions = ref<string[]>([]);
const savingPermissions = ref(false);

const filteredUsers = computed(() => {
    const query = search.value.toLowerCase();
    return users.value.filter(
        (u: any) =>
            u.name.toLowerCase().includes(query) ||
            u.email.toLowerCase().includes(query) ||
            (u.designation_name ?? '').toLowerCase().includes(query),
    );
});

const updateUserRole = async (userId: number, newRole: string) => {
    updatingRoleUserId.value = userId;
    try {
        await axios.put(route('user-managements-users-update-role', userId), { role: newRole });
        const user = users.value.find((u: any) => u.id === userId);
        if (user) user.role = newRole;
        Swal.fire('Success!', 'User role updated.', 'success');
    } catch {
        Swal.fire('Error!', 'Failed to update role.', 'error');
    } finally {
        updatingRoleUserId.value = null;
    }
};

const updateUserClient = async (userId: number, clientId: number | null) => {
    try {
        await axios.put(route('user-managements-users-update-client', userId), { client_id: clientId });
        const user = users.value.find((u: any) => u.id === userId);
        if (user) user.client_id = clientId;
        Swal.fire('Success!', 'Client updated.', 'success');
    } catch {
        Swal.fire('Error!', 'Failed to update client.', 'error');
    }
};

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
        { permissions: selectedPermissions.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                const index = users.value.findIndex((u: any) => u.id === permissionsModalUserId.value);
                if (index !== -1) users.value[index].permissions = [...selectedPermissions.value];
                permissionsModalVisible.value = false;
                permissionsModalUserId.value = null;
                Swal.fire('Success!', 'Permissions updated.', 'success');
            },
            onError: () => Swal.fire('Error!', 'Failed to update permissions.', 'error'),
            onFinish: () => (savingPermissions.value = false),
        },
    );
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

const saveUser = async () => {
    if (!newUserName.value.trim() || !newUserEmail.value.trim() || !newUserRole.value) return;
    savingUser.value = true;

    try {
        const response = await axios.post(route('user-managements-users-create'), {
            name: newUserName.value,
            email: newUserEmail.value,
            role: newUserRole.value,
            permissions: newUserPermissions.value.length > 0 ? newUserPermissions.value : ['/welcome-to-planetnine/register'],
            client_id: newUserClientId.value,
            send_mail: newUserSendMail.value,
        });

        if (response.data?.newUser) {
            users.value.push(response.data.newUser);
            users.value.sort((a, b) => a.name.localeCompare(b.name));
        }

        addingUser.value = false;
        newUserName.value = '';
        newUserEmail.value = '';
        newUserRole.value = 'user';
        newUserClientId.value = null;
        newUserPermissions.value = [];

        Swal.fire('Success!', 'User created successfully.', 'success');
    } catch {
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

const resetPassword = async (userId: number) => {
    const result = await Swal.fire({
        title: 'Reset Password?',
        text: 'This will reset the user\'s password and send them a notification.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, reset it!',
    });

    if (result.isConfirmed) {
        resettingPasswordUserId.value = userId;

        try {
            const apiUrl = route('user-managements-users-update-password', userId);

            const response = await axios.post(apiUrl);

            Swal.fire('Success!', 'Password has been reset successfully to <b>!password</b>', 'success');
        } catch (error: any) {
            Swal.fire('Error!', 'Failed to reset password.', 'error');
        } finally {
            resettingPasswordUserId.value = null;
        }
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Users" />
        <SettingsLayout>
            <!-- Nothing Design System -->
            <div class="space-y-6 font-mono">
                <!-- Header Section -->
                <div class="flex flex-col items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 bg-[#D71921] rounded-full animate-pulse"></div>
                        <h2 class="text-2xl font-light tracking-widest uppercase text-black dark:text-white">
                            USERS
                        </h2>
                    </div>
                    <p class="text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999] mt-2">
                        MANAGEMENT · PERMISSIONS · ACCESS
                    </p>
                </div>

                <!-- Search & Add Section -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <div class="relative flex-1 max-w-md">
                        <input v-model="search" placeholder="SEARCH USERS..."
                            class="w-full px-4 py-3 bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg text-sm tracking-wider uppercase placeholder:text-[#CCCCCC] dark:placeholder:text-[#333333] text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200" />
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 w-1.5 h-1.5 bg-[#D71921] rounded-full">
                        </div>
                    </div>
                    <Button v-if="!addingUser" @click="addingUser = true"
                        class="px-6 py-3 border-2 border-[#1A1A1A] dark:border-[#FFFFFF] text-[#1A1A1A] dark:text-[#FFFFFF] bg-transparent rounded-full transition-all duration-200 hover:bg-[#1A1A1A] hover:text-white dark:hover:bg-[#FFFFFF] dark:hover:text-black text-xs tracking-widest uppercase font-bold">
                        + ADD USER
                    </Button>
                </div>

                <!-- Table Container -->
                <div
                    class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg overflow-hidden">
                    <table class="min-w-full">
                        <!-- Table Header -->
                        <thead class="bg-[#F5F5F5] dark:bg-[#0A0A0A] border-b border-[#E8E8E8] dark:border-[#222222]">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999] font-mono">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1 h-1 bg-[#D71921] rounded-full"></div>
                                        INDEX
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999] font-mono">
                                    USER INFO
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999] font-mono">
                                    ROLE
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999] font-mono">
                                    ACCESS
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999] font-mono">
                                    ACTIONS
                                </th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody>
                            <!-- Add New User Row -->
                            <tr v-if="addingUser"
                                class="border-b border-[#E8E8E8] dark:border-[#222222] bg-[#FAFAFA] dark:bg-[#0D0D0D]">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 bg-[#D71921] rounded-full animate-pulse"></div>
                                        <span class="text-sm font-mono text-[#999999]">NEW</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-3">
                                        <input v-model="newUserName" placeholder="FULL NAME..."
                                            class="w-full px-3 py-2 bg-white dark:bg-[#111111] border border-[#CCCCCC] dark:border-[#333333] rounded text-sm tracking-wider uppercase placeholder:text-[#CCCCCC] dark:placeholder:text-[#333333] text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200" />
                                        <input v-model="newUserEmail" type="email" placeholder="EMAIL@EXAMPLE.COM..."
                                            class="w-full px-3 py-2 bg-white dark:bg-[#111111] border border-[#CCCCCC] dark:border-[#333333] rounded text-sm font-mono placeholder:text-[#CCCCCC] dark:placeholder:text-[#333333] text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200" />
                                        <select v-model="newUserClientId"
                                            class="w-full px-3 py-2 bg-white dark:bg-[#111111] border border-[#CCCCCC] dark:border-[#333333] rounded text-sm tracking-wider uppercase text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200">
                                            <option disabled value="">SELECT CLIENT</option>
                                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                                {{ client.name }}
                                            </option>
                                        </select>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-3">
                                        <select v-model="newUserRole"
                                            class="w-full px-3 py-2 bg-white dark:bg-[#111111] border border-[#CCCCCC] dark:border-[#333333] rounded text-sm tracking-wider uppercase text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200">
                                            <option disabled value="">SELECT ROLE</option>
                                            <option value="super_admin">SUPER ADMIN</option>
                                            <option value="admin">ADMIN</option>
                                            <option value="user">USER</option>
                                        </select>
                                        <div
                                            class="flex items-center justify-between p-2 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded">
                                            <span
                                                class="text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999]">SEND
                                                MAIL</span>
                                            <button @click="newUserSendMail = !newUserSendMail" type="button" :class="[
                                                newUserSendMail ? 'bg-[#D71921]' : 'bg-[#CCCCCC] dark:bg-[#333333]',
                                                'relative inline-flex h-6 w-11 items-center rounded-full transition-all duration-200'
                                            ]">
                                                <span :class="[
                                                    newUserSendMail ? 'translate-x-6' : 'translate-x-1',
                                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform'
                                                ]" />
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="max-h-36 space-y-1 overflow-y-auto rounded border border-[#E8E8E8] dark:border-[#222222] bg-[#FAFAFA] dark:bg-[#0D0D0D] p-2">
                                        <div v-for="route in routes" :key="route.id"
                                            class="flex items-center gap-2 p-1 hover:bg-white dark:hover:bg-[#111111] rounded transition-all duration-200">
                                            <input type="checkbox" v-model="newUserPermissions" :value="route.href"
                                                class="w-4 h-4 rounded border-[#CCCCCC] dark:border-[#333333] text-[#D71921] focus:ring-[#D71921]" />
                                            <label
                                                class="text-xs tracking-wider uppercase text-black dark:text-white cursor-pointer">{{
                                                route.title }}</label>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-3">
                                        <Button @click="saveUser" :disabled="savingUser"
                                            class="px-4 py-2 bg-[#1A1A1A] dark:bg-[#FFFFFF] text-white dark:text-black rounded-full text-xs tracking-widest uppercase font-bold hover:opacity-80 transition-all duration-200 disabled:opacity-50">
                                            <LoaderCircle v-if="savingUser" class="h-4 w-4 animate-spin mx-auto" />
                                            <span v-else>SAVE</span>
                                        </Button>
                                        <Button variant="outline" @click="addingUser = false"
                                            class="px-4 py-2 border border-[#CCCCCC] dark:border-[#333333] text-[#666666] dark:text-[#999999] rounded-full text-xs tracking-widest uppercase hover:border-[#1A1A1A] dark:hover:border-[#FFFFFF] hover:text-[#1A1A1A] dark:hover:text-[#FFFFFF] transition-all duration-200">
                                            CANCEL
                                        </Button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Existing Users -->
                            <tr v-for="(user, index) in filteredUsers" :key="user.id"
                                class="border-b border-[#E8E8E8] dark:border-[#222222] hover:bg-[#FAFAFA] dark:hover:bg-[#0D0D0D] transition-all duration-200 group">

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-1 h-1 bg-[#666666] dark:bg-[#999999] rounded-full group-hover:bg-[#D71921] transition-all duration-200">
                                        </div>
                                        <span class="text-sm font-mono tabular-nums text-black dark:text-white">{{
                                            String(index + 1).padStart(2, '0') }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="space-y-2">
                                        <div
                                            class="text-sm tracking-wider uppercase font-medium text-black dark:text-white">
                                            {{ user.name }}
                                        </div>
                                        <div class="text-xs font-mono text-[#666666] dark:text-[#999999]">
                                            {{ user.email }}
                                        </div>
                                        <select :value="user.client_id"
                                            @change="updateUserClient(user.id, Number(($event.target as HTMLSelectElement)?.value))"
                                            class="w-full px-2 py-1 bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded text-xs tracking-wider uppercase text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200">
                                            <option disabled value="">SELECT CLIENT</option>
                                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                                {{ client.name }}
                                            </option>
                                        </select>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="space-y-2">
                                        <select v-model="user.role" @change="updateUserRole(user.id, user.role)"
                                            :disabled="updatingRoleUserId === user.id"
                                            class="w-full px-3 py-2 bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded text-xs tracking-wider uppercase text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200 disabled:opacity-50">
                                            <option value="super_admin">SUPER ADMIN</option>
                                            <option value="admin">ADMIN</option>
                                            <option value="user">USER</option>
                                        </select>
                                        <div
                                            class="text-xs tracking-wider uppercase text-[#666666] dark:text-[#999999] px-2 py-1 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded text-center">
                                            {{ user.designation_name ?? 'NO DESIGNATION' }}
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <Button variant="secondary" @click="openPermissionsModal(user)"
                                        class="px-4 py-2 bg-[#F5F5F5] dark:bg-[#0A0A0A] border border-[#E8E8E8] dark:border-[#222222] text-[#1A1A1A] dark:text-[#FFFFFF] rounded-full text-xs tracking-widest uppercase font-bold hover:border-[#1A1A1A] dark:hover:border-[#FFFFFF] transition-all duration-200">
                                        PERMISSIONS
                                    </Button>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Button variant="outline" @click="resetPassword(user.id)"
                                            :disabled="resettingPasswordUserId === user.id"
                                            class="px-3 py-1.5 border border-[#CCCCCC] dark:border-[#333333] text-[#666666] dark:text-[#999999] rounded-full text-xs tracking-widest uppercase hover:border-[#1A1A1A] dark:hover:border-[#FFFFFF] hover:text-[#1A1A1A] dark:hover:text-[#FFFFFF] transition-all duration-200 disabled:opacity-50">
                                            <LoaderCircle v-if="resettingPasswordUserId === user.id"
                                                class="h-3 w-3 animate-spin" />
                                            <span v-else>RESET</span>
                                        </Button>
                                        <Button variant="destructive" @click="deleteUser(user.id)"
                                            class="px-3 py-1.5 bg-[#D71921] text-white rounded-full text-xs tracking-widest uppercase font-bold hover:bg-[#B01419] transition-all duration-200">
                                            DELETE
                                        </Button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredUsers.length === 0 && !addingUser">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="flex items-center gap-1">
                                            <div class="w-1.5 h-1.5 bg-[#666666] dark:bg-[#999999] rounded-full"></div>
                                            <div class="w-1 h-1 bg-[#666666] dark:bg-[#999999] rounded-full"></div>
                                            <div class="w-1.5 h-1.5 bg-[#666666] dark:bg-[#999999] rounded-full"></div>
                                        </div>
                                        <p class="text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999]">
                                            NO USERS FOUND
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Info -->
                <div class="flex items-center justify-between pt-4 border-t border-[#E8E8E8] dark:border-[#222222]">
                    <div class="flex items-center gap-2">
                        <div class="w-1 h-1 bg-[#D71921] rounded-full"></div>
                        <span class="text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999]">
                            TOTAL: {{ filteredUsers.length }} {{ filteredUsers.length === 1 ? 'USER' : 'USERS' }}
                        </span>
                    </div>
                    <span class="text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999]">
                        NOTHING · SYSTEM
                    </span>
                </div>
            </div>

        </SettingsLayout>

        <!-- Permissions Modal - Nothing Design -->
        <div v-if="permissionsModalVisible"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80 backdrop-blur-sm font-mono">
            <div
                class="w-full max-w-2xl mx-4 bg-white dark:bg-[#111111] border-2 border-[#E8E8E8] dark:border-[#222222] rounded-2xl shadow-2xl overflow-hidden animate-fade-in">
                <!-- Modal Header -->
                <div class="bg-[#F5F5F5] dark:bg-[#0A0A0A] border-b border-[#E8E8E8] dark:border-[#222222] px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 bg-[#D71921] rounded-full animate-pulse"></div>
                        <h2 class="text-xl font-light tracking-widest uppercase text-black dark:text-white">
                            MANAGE PERMISSIONS
                        </h2>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <!-- Wildcard Notice -->
                    <div v-if="selectedPermissions.includes('*')"
                        class="flex items-center gap-3 p-3 bg-[#D71921] bg-opacity-10 border border-[#D71921] rounded-lg">
                        <div class="w-1.5 h-1.5 bg-[#D71921] rounded-full"></div>
                        <span class="text-xs tracking-widest uppercase text-[#D71921]">
                            ALL ROUTES ENABLED · WILDCARD ACTIVE
                        </span>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex items-center justify-center gap-4">
                        <Button variant="secondary" @click="selectAll"
                            class="px-4 py-2 bg-[#1A1A1A] dark:bg-[#FFFFFF] text-white dark:text-black rounded-full text-xs tracking-widest uppercase font-bold hover:opacity-80 transition-all duration-200">
                            SELECT ALL
                        </Button>
                        <Button variant="outline" @click="clearAll"
                            class="px-4 py-2 border border-[#CCCCCC] dark:border-[#333333] text-[#666666] dark:text-[#999999] rounded-full text-xs tracking-widest uppercase hover:border-[#1A1A1A] dark:hover:border-[#FFFFFF] hover:text-[#1A1A1A] dark:hover:text-[#FFFFFF] transition-all duration-200">
                            CLEAR ALL
                        </Button>
                    </div>

                    <!-- Permissions List -->
                    <div
                        class="max-h-96 space-y-2 overflow-y-auto rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-[#FAFAFA] dark:bg-[#0D0D0D] p-4">
                        <div v-for="route in routes" :key="route.id"
                            class="flex items-center justify-between p-3 bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg hover:border-[#CCCCCC] dark:hover:border-[#333333] transition-all duration-200 group">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-1 h-1 bg-[#666666] dark:bg-[#999999] rounded-full group-hover:bg-[#D71921] transition-all duration-200">
                                    </div>
                                    <span
                                        class="text-sm tracking-wider uppercase font-medium text-black dark:text-white">{{
                                        route.title }}</span>
                                </div>
                                <code
                                    class="text-xs font-mono text-[#666666] dark:text-[#999999] ml-3 mt-1 block">{{ route.href }}</code>
                            </div>
                            <input type="checkbox"
                                :checked="selectedPermissions.includes('*') || selectedPermissions.includes(route.href)"
                                @change="togglePermission(route.href)"
                                class="w-5 h-5 rounded border-[#CCCCCC] dark:border-[#333333] text-[#D71921] focus:ring-[#D71921] cursor-pointer" />
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-[#F5F5F5] dark:bg-[#0A0A0A] border-t border-[#E8E8E8] dark:border-[#222222] px-6 py-4">
                    <div class="flex items-center justify-end gap-3">
                        <Button @click="savePermissions" :disabled="savingPermissions"
                            class="px-6 py-2 bg-[#1A1A1A] dark:bg-[#FFFFFF] text-white dark:text-black rounded-full text-xs tracking-widest uppercase font-bold hover:opacity-80 transition-all duration-200 disabled:opacity-50">
                            <span v-if="savingPermissions">SAVING...</span>
                            <span v-else>SAVE CHANGES</span>
                        </Button>
                        <Button variant="outline" @click="permissionsModalVisible = false" :disabled="savingPermissions"
                            class="px-6 py-2 border border-[#CCCCCC] dark:border-[#333333] text-[#666666] dark:text-[#999999] rounded-full text-xs tracking-widest uppercase hover:border-[#1A1A1A] dark:hover:border-[#FFFFFF] hover:text-[#1A1A1A] dark:hover:text-[#FFFFFF] transition-all duration-200 disabled:opacity-50">
                            CANCEL
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: scale(0.95);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>