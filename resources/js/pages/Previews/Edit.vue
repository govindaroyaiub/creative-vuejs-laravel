<template>

    <Head title="Edit Preview" />
    <AppLayout :breadcrumbs="[{ title: 'Previews', href: '/previews' }, { title: 'Edit Preview' }]">
        <div class="min-h-screen dark:bg-gray-900 py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Preview</h1>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Update your preview settings and configuration
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                Preview ID: {{ preview.id }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Main Form Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden">
                    <form @submit.prevent="submit" class="p-2 space-y-8">
                        <!-- Preview Name Section -->
                        <div class="space-y-2">
                            <div>
                                <label for="preview-name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Preview Name *
                                </label>
                                <input id="preview-name" v-model="form.name" type="text"
                                    placeholder="e.g. Facebook Ad Campaign - June 2024"
                                    class="w-full rounded-xl border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                    :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.name }"
                                    required />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.name }}
                                </p>
                            </div>
                        </div>

                        <!-- Configuration Section -->
                        <div class="space-y-2">
                            <!-- Client, Header Logo, and Color Palette -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Client Dropdown -->
                                <div>
                                    <label for="client-select"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Client *
                                    </label>
                                    <div class="relative">
                                        <select id="client-select" v-model="form.client_id"
                                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 appearance-none"
                                            :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.client_id }"
                                            required>
                                            <option disabled value="">Select Client</option>
                                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                                {{ client.name }}
                                            </option>
                                        </select>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.client_id" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.client_id }}
                                    </p>
                                </div>

                                <!-- Header Logo Dropdown -->
                                <div>
                                    <label for="header-logo-select"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Header Logo *
                                    </label>
                                    <div class="relative">
                                        <select id="header-logo-select" v-model="form.header_logo_id"
                                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 appearance-none"
                                            :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.header_logo_id }"
                                            required>
                                            <option disabled value="">Select Header Logo</option>
                                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                                {{ client.name }}
                                            </option>
                                        </select>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.header_logo_id"
                                        class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.header_logo_id }}
                                    </p>
                                </div>

                                <!-- Color Palette Dropdown -->
                                <div>
                                    <label for="color-palette-select"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Theme *
                                    </label>
                                    <div class="relative">
                                        <select id="color-palette-select" v-model="form.color_palette_id"
                                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 appearance-none"
                                            :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.color_palette_id }"
                                            required>
                                            <option disabled value="">Select Theme</option>
                                            <option v-for="palette in colorPalettes" :key="palette.id"
                                                :value="palette.id">
                                                {{ palette.name }}
                                            </option>
                                        </select>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.color_palette_id"
                                        class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.color_palette_id }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Team Members Section -->
                        <div class="space-y-2">
                            <div>
                                <label for="team-search"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Add Team Members *
                                </label>

                                <!-- Selected Users Display -->
                                <div v-if="selectedUsers.length > 0"
                                    class="flex flex-wrap gap-2 mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                    <div v-for="user in selectedUsers" :key="user.id"
                                        class="inline-flex items-center px-3 py-2 bg-indigo-100 text-indigo-800 text-sm font-medium rounded-lg dark:bg-indigo-800 dark:text-indigo-200 transition-all duration-200">
                                        <div class="flex items-center">
                                            <div
                                                class="w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center mr-2">
                                                <span class="text-xs font-bold text-white">{{
                                                    user.name.charAt(0).toUpperCase() }}</span>
                                            </div>
                                            <span>{{ user.name }}</span>
                                            <button v-if="user.id !== authUser.id" @click="removeUser(user.id)"
                                                type="button"
                                                class="ml-2 text-indigo-600 hover:text-red-600 focus:outline-none transition-colors duration-200"
                                                :aria-label="`Remove ${user.name} from team`">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- User Search -->
                                <div class="relative">
                                    <input id="team-search" v-model="userSearch" type="text"
                                        placeholder="Search and add team members..."
                                        class="w-full rounded-xl border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                        :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.team_ids }"
                                        autocomplete="off" />

                                    <!-- Search Results Dropdown -->
                                    <div v-if="userSearch.trim().length > 0 && filteredUsers.length > 0"
                                        class="absolute z-10 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                        <button v-for="user in filteredUsers" :key="user.id" type="button"
                                            class="w-full text-left px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 focus:outline-none transition-colors duration-200 first:rounded-t-xl last:rounded-b-xl flex items-center"
                                            @click="addUser(user)">
                                            <div
                                                class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center mr-3">
                                                <span class="text-sm font-bold text-white">{{
                                                    user.name.charAt(0).toUpperCase() }}</span>
                                            </div>
                                            <span class="text-gray-900 dark:text-white">{{ user.name }}</span>
                                        </button>
                                    </div>

                                    <!-- No Results Message -->
                                    <div v-else-if="userSearch.trim().length > 0 && filteredUsers.length === 0"
                                        class="absolute z-10 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl shadow-lg px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">
                                        No users found matching "{{ userSearch }}"
                                    </div>
                                </div>

                                <p v-if="form.errors.team_ids" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.team_ids }}
                                </p>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ selectedUsers.length }} member{{ selectedUsers.length !== 1 ? 's' : '' }}
                                    selected
                                </p>
                            </div>
                        </div>

                        <!-- Preview Settings Section -->
                        <div class="space-y-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="toggle in toggleConfigs" :key="toggle.model"
                                    class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6 border border-gray-200 dark:border-gray-600 transition-all duration-200 hover:shadow-md">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <label :for="`toggle-${toggle.model}`"
                                                class="text-sm font-semibold text-gray-900 dark:text-white cursor-pointer">
                                                {{ toggle.label }}
                                            </label>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ toggle.description }}
                                            </p>
                                        </div>

                                        <!-- Toggle Switch -->
                                        <label class="relative inline-flex items-center cursor-pointer ml-4">
                                            <input :id="`toggle-${toggle.model}`" type="checkbox"
                                                v-model="form[toggle.model]" class="sr-only peer" />
                                            <div
                                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500 dark:peer-focus:ring-indigo-600 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600">
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Status Indicator -->
                                    <div class="mt-3 text-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="form[toggle.model]
                                                ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200'
                                                : 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200'">
                                            <svg class="w-3 h-3 mr-1"
                                                :class="form[toggle.model] ? 'text-green-400' : 'text-gray-400'"
                                                fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="3" />
                                            </svg>
                                            {{ form[toggle.model] ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-8 border-t border-gray-200 dark:border-gray-700">
                            <Link :href="route('previews.update.all', preview.id)"
                                class="w-full inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-xl text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200 shadow-sm">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Preview
                            </Link>

                            <button type="submit" :disabled="form.processing"
                                class="w-full inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                {{ form.processing ? 'Updating...' : 'Update Preview' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Swal from 'sweetalert2';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
const preview = computed(() => page.props.preview);
const clients = computed(() => page.props.clients ?? []);
const headerLogos = computed(() => page.props.headerLogos ?? []);
const users = computed(() => page.props.users ?? []);
const colorPalettes = computed(() => page.props.palettes ?? []);
const teamUsers = computed(() => page.props.teamUsers ?? []);
const authUser = computed(() => page.props.auth?.user ?? {});

const form = useForm({
    name: preview.value.name,
    client_id: preview.value.client_id,
    header_logo_id: preview.value.header_logo_id,
    team_ids: teamUsers.value.map(u => u.id),
    color_palette_id: preview.value.color_palette_id ?? '',
    requires_login: !!preview.value.requires_login,
    show_planetnine_logo: !!preview.value.show_planetnine_logo,
    show_sidebar_logo: !!preview.value.show_sidebar_logo,
    show_footer: !!preview.value.show_footer,
});

const userSearch = ref('');

// Toggle configuration with descriptions
const toggleConfigs = [
    {
        label: 'Requires Login?',
        model: 'requires_login',
        description: 'Users must authenticate to view this preview'
    },
    {
        label: 'Show Planet Nine Logo?',
        model: 'show_planetnine_logo',
        description: 'Display company branding in the header'
    },
    {
        label: 'Show Sidebar Logo?',
        model: 'show_sidebar_logo',
        description: 'Show logo in the navigation sidebar'
    },
    {
        label: 'Show Footer?',
        model: 'show_footer',
        description: 'Display footer section on preview pages'
    },
] as const;

// Computed properties
const selectedUsers = computed(() =>
    users.value.filter((u) => form.team_ids.includes(u.id))
);

const filteredUsers = computed(() => {
    const query = userSearch.value.toLowerCase().trim();
    if (!query) return [];

    return users.value
        .filter((u) => !form.team_ids.includes(u.id))
        .filter((u) => u.name.toLowerCase().includes(query))
        .slice(0, 5); // Limit results for performance
});

// Methods
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
                title: 'Success!',
                text: 'Preview updated successfully!',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                timerProgressBar: true,
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Something went wrong. Please check your inputs and try again.',
                confirmButtonColor: '#dc2626',
            });
        },
    });
};

// Clear search when clicking outside
const handleClickOutside = (event: Event) => {
    const target = event.target as HTMLElement;
    if (!target.closest('#team-search') && !target.closest('.absolute')) {
        userSearch.value = '';
    }
};

// Lifecycle hooks
import { onMounted, onUnmounted } from 'vue';

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>