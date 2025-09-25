<script setup lang="ts">
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Swal from 'sweetalert2';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
const preview = computed(() => page.props.preview);
const clients = computed(() => page.props.clients ?? []);
const users = computed(() => page.props.users ?? []);
const colorPalettes = computed(() => page.props.palettes ?? []);
const teamUsers = computed(() => page.props.teamUsers ?? []);
const authUser = computed(() => page.props.auth?.user ?? {});

const form = useForm({
    name: preview.value.name,
    client_id: preview.value.client_id,
    team_ids: teamUsers.value.map(u => u.id), // Initialize with resolved team users
    color_palette_id: preview.value.color_palette_id ?? '',
    requires_login: !!preview.value.requires_login,
    show_planetnine_logo: !!preview.value.show_planetnine_logo,
    show_sidebar_logo: !!preview.value.show_sidebar_logo,
    show_footer: !!preview.value.show_footer,
});

const userSearch = ref('');
const selectedUsers = computed(() => users.value.filter((u) => form.team_ids.includes(u.id)));
const filteredUsers = computed(() => {
    const query = userSearch.value.toLowerCase();
    return users.value
        .filter((u) => !form.team_ids.includes(u.id))
        .filter((u) => u.name.toLowerCase().includes(query));
});
const addUser = (user: any) => {
    if (!form.team_ids.includes(user.id)) {
        form.team_ids.push(user.id);
    }
    userSearch.value = '';
};
const removeUser = (id: number) => {
    if (id === authUser.value.id) return;
    form.team_ids = form.team_ids.filter((uid: number) => uid !== id);
};

const submit = () => {
    form.put(route('previews-edit', preview.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Preview updated successfully!',
                timer: 2500,
                showConfirmButton: false,
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong. Please try again.',
            });
        },
    });
};
</script>

<template>

    <Head title="Edit Preview" />
    <AppLayout :breadcrumbs="[{ title: 'Previews', href: '/previews' }, { title: 'Edit Preview' }]">
        <div class="p-6 max-w-3xl w-3/4 mx-auto">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Preview Name -->
                <div>
                    <label class="block mb-1 font-medium">Preview Name</label>
                    <input v-model="form.name" type="text"
                        class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white" />
                </div>

                <!-- Client & Color Palette -->
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <label class="block mb-1 font-medium">Client</label>
                        <select v-model="form.client_id"
                            class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white">
                            <option disabled value="">Select Client</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                {{ client.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex-1">
                        <label class="block mb-1 font-medium">Color Palette</label>
                        <select v-model="form.color_palette_id"
                            class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white">
                            <option disabled value="">Select Theme</option>
                            <option v-for="palette in colorPalettes" :key="palette.id" :value="palette.id">
                                {{ palette.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Team Members -->
                <div>
                    <label class="block mb-1 font-medium">Team Members</label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <span v-for="user in selectedUsers" :key="user.id"
                            class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-2xl dark:bg-indigo-800 dark:text-white">
                            {{ user.name }}
                            <span v-if="user.id !== authUser.id" @click="removeUser(user.id)"
                                class="ml-1 cursor-pointer">×</span>
                        </span>
                    </div>
                    <input v-model="userSearch" type="text" placeholder="Search users..."
                        class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white" />
                    <ul v-if="userSearch.trim().length > 0 && filteredUsers.length > 0"
                        class="mt-1 max-h-32 overflow-y-auto rounded-2xl border dark:border-gray-700 bg-white dark:bg-black">
                        <li v-for="user in filteredUsers" :key="user.id"
                            class="cursor-pointer px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700"
                            @click="addUser(user)">
                            {{ user.name }}
                        </li>
                    </ul>
                </div>

                <!-- Toggle Switches -->
                <div class="space-y-4">
                    <div v-for="toggle in [
                        { label: 'Requires Login?', model: 'requires_login' },
                        { label: 'Show Planet Nine Logo?', model: 'show_planetnine_logo' },
                        { label: 'Show Sidebar Logo?', model: 'show_sidebar_logo' },
                        { label: 'Show Footer?', model: 'show_footer' },
                    ]" :key="toggle.model" class="flex items-center justify-between">
                        <label class="text-sm font-medium">{{ toggle.label }}</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form[toggle.model]" class="sr-only peer" />
                            <div
                                class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-green-600 dark:bg-gray-700 transition-colors">
                            </div>
                            <div
                                class="absolute left-0.5 top-0.5 w-5 h-5 bg-white border rounded-full transition-transform peer-checked:translate-x-full dark:border-gray-600">
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex space-x-4">
                    <Link :href="route('previews.update.all', preview.id)"
                        class="w-full rounded-2xl bg-red-600 px-6 py-3 text-center text-white shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-red-500 dark:hover:bg-red-600">
                    Back
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="w-full rounded-2xl bg-indigo-600 px-6 py-3 text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>