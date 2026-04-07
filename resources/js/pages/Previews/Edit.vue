<template>

    <Head title="Edit Preview" />
    <AppLayout
        :breadcrumbs="[{ title: 'Previews', href: '/previews' }, { title: 'Edit Preview', 'href': '/previews-edit/' + preview.id }]">
        <div class="min-h-screen py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-black dark:text-white uppercase tracking-wide">EDIT
                                PREVIEW</h1>
                            <p
                                class="mt-2 text-sm text-[#666666] dark:text-[#999999] uppercase tracking-wider font-mono">
                                UPDATE YOUR PREVIEW SETTINGS AND CONFIGURATION
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border-2 border-black dark:border-white text-black dark:text-white uppercase tracking-wider font-mono">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                PREVIEW ID: {{ preview.id }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Main Form Card -->
                <div
                    class="bg-[#F5F5F5] dark:bg-black rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] overflow-hidden">
                    <form @submit.prevent="submit" class="p-2 space-y-8">
                        <!-- Preview Name Section -->
                        <div class="space-y-2">
                            <div>
                                <label for="preview-name"
                                    class="block text-sm font-medium text-[#666666] dark:text-[#999999] mb-2 uppercase tracking-widest font-mono">
                                    PREVIEW NAME *
                                </label>
                                <input id="preview-name" v-model="form.name" type="text"
                                    placeholder="E.G. FACEBOOK AD CAMPAIGN - JUNE 2024"
                                    class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-4 py-3 text-black dark:text-white bg-white dark:bg-[#111111] placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                    :class="{ 'border-[#D71921] focus:border-[#D71921]': form.errors.name }" required />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-[#D71921]">
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
                                        class="block text-sm font-medium text-[#666666] dark:text-[#999999] mb-2 uppercase tracking-widest font-mono">
                                        CLIENT *
                                    </label>
                                    <div class="relative">
                                        <select id="client-select" v-model="form.client_id"
                                            class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-4 py-3 text-black dark:text-white bg-white dark:bg-[#111111] focus:outline-none focus:border-black dark:focus:border-white transition-colors appearance-none"
                                            :class="{ 'border-[#D71921] focus:border-[#D71921]': form.errors.client_id }"
                                            required>
                                            <option disabled value="">SELECT CLIENT</option>
                                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                                {{ client.name }}
                                            </option>
                                        </select>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-[#666666] dark:text-[#999999]" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.client_id" class="mt-1 text-sm text-[#D71921]">
                                        {{ form.errors.client_id }}
                                    </p>
                                </div>

                                <!-- Header Logo Dropdown -->
                                <div>
                                    <label for="header-logo-select"
                                        class="block text-sm font-medium text-[#666666] dark:text-[#999999] mb-2 uppercase tracking-widest font-mono">
                                        HEADER LOGO *
                                    </label>
                                    <div class="relative">
                                        <select id="header-logo-select" v-model="form.header_logo_id"
                                            class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-4 py-3 text-black dark:text-white bg-white dark:bg-[#111111] focus:outline-none focus:border-black dark:focus:border-white transition-colors appearance-none"
                                            :class="{ 'border-[#D71921] focus:border-[#D71921]': form.errors.header_logo_id }"
                                            required>
                                            <option disabled value="">SELECT HEADER LOGO</option>
                                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                                {{ client.name }}
                                            </option>
                                        </select>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-[#666666] dark:text-[#999999]" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.header_logo_id" class="mt-1 text-sm text-[#D71921]">
                                        {{ form.errors.header_logo_id }}
                                    </p>
                                </div>

                                <!-- Color Palette Dropdown -->
                                <div>
                                    <label for="color-palette-select"
                                        class="block text-sm font-medium text-[#666666] dark:text-[#999999] mb-2 uppercase tracking-widest font-mono">
                                        THEME *
                                    </label>
                                    <div class="relative">
                                        <select id="color-palette-select" v-model="form.color_palette_id"
                                            class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-4 py-3 text-black dark:text-white bg-white dark:bg-[#111111] focus:outline-none focus:border-black dark:focus:border-white transition-colors appearance-none"
                                            :class="{ 'border-[#D71921] focus:border-[#D71921]': form.errors.color_palette_id }"
                                            required>
                                            <option disabled value="">SELECT THEME</option>
                                            <option v-for="palette in colorPalettes" :key="palette.id"
                                                :value="palette.id">
                                                {{ palette.name }}
                                            </option>
                                        </select>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-[#666666] dark:text-[#999999]" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.color_palette_id" class="mt-1 text-sm text-[#D71921]">
                                        {{ form.errors.color_palette_id }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Team Members Section -->
                        <div class="space-y-2">
                            <div>
                                <label for="team-search"
                                    class="block text-sm font-medium text-[#666666] dark:text-[#999999] mb-2 uppercase tracking-widest font-mono">
                                    ADD TEAM MEMBERS *
                                </label>

                                <!-- Selected Users Display -->
                                <div v-if="selectedUsers.length > 0"
                                    class="flex flex-wrap gap-2 mb-4 p-4 bg-white dark:bg-[#111111] rounded-lg border border-[#E8E8E8] dark:border-[#222222]">
                                    <div v-for="user in selectedUsers" :key="user.id"
                                        class="inline-flex items-center px-3 py-2 border-2 border-black dark:border-white text-black dark:text-white text-xs font-medium rounded-full uppercase tracking-wider font-mono transition-all duration-200">
                                        <div class="flex items-center">
                                            <div
                                                class="w-6 h-6 bg-black dark:bg-white rounded-full flex items-center justify-center mr-2">
                                                <span class="text-xs font-bold text-white dark:text-black">{{
                                                    user.name.charAt(0).toUpperCase() }}</span>
                                            </div>
                                            <span>{{ user.name }}</span>
                                            <button v-if="user.id !== authUser.id" @click="removeUser(user.id)"
                                                type="button"
                                                class="ml-2 text-black dark:text-white hover:text-[#D71921] dark:hover:text-[#D71921] focus:outline-none transition-colors duration-200"
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
                                        placeholder="SEARCH AND ADD TEAM MEMBERS..."
                                        class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-4 py-3 text-black dark:text-white bg-white dark:bg-[#111111] placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                        :class="{ 'border-[#D71921] focus:border-[#D71921]': form.errors.team_ids }"
                                        autocomplete="off" />

                                    <!-- Search Results Dropdown -->
                                    <div v-if="userSearch.trim().length > 0 && filteredUsers.length > 0"
                                        class="absolute z-10 w-full mt-2 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-neutral-700 rounded-xl shadow-lg max-h-48 overflow-y-auto">
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
                                        class="absolute z-10 w-full mt-2 bg-white dark:bg-[#111111] border-2 border-[#E8E8E8] dark:border-[#222222] rounded-lg px-4 py-3 text-[#666666] dark:text-[#999999] text-sm uppercase tracking-wider font-mono">
                                        NO USERS FOUND MATCHING "{{ userSearch }}"
                                    </div>
                                </div>

                                <p v-if="form.errors.team_ids" class="mt-2 text-sm text-[#D71921]">
                                    {{ form.errors.team_ids }}
                                </p>
                                <p
                                    class="mt-2 text-xs text-[#666666] dark:text-[#999999] uppercase tracking-wider font-mono">
                                    {{ selectedUsers.length }} MEMBER{{ selectedUsers.length !== 1 ? 'S' : '' }}
                                    SELECTED
                                </p>
                            </div>
                        </div>

                        <!-- Preview Settings Section -->
                        <div class="space-y-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="toggle in toggleConfigs" :key="toggle.model"
                                    class="bg-white dark:bg-[#111111] rounded-lg p-6 border-2 border-[#E8E8E8] dark:border-[#222222] transition-all duration-200 hover:border-black dark:hover:border-white">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <label :for="`toggle-${toggle.model}`"
                                                class="text-sm font-semibold text-black dark:text-white cursor-pointer uppercase tracking-wider font-mono">
                                                {{ toggle.label }}
                                            </label>
                                            <p class="text-xs text-[#666666] dark:text-[#999999] mt-1">
                                                {{ toggle.description }}
                                            </p>
                                        </div>

                                        <!-- Toggle Switch -->
                                        <label class="relative inline-flex items-center cursor-pointer ml-4">
                                            <input :id="`toggle-${toggle.model}`" type="checkbox"
                                                v-model="form[toggle.model]" class="sr-only peer" />
                                            <div
                                                class="w-11 h-6 bg-[#E8E8E8] dark:bg-[#222222] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-black dark:peer-focus:ring-white rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-[#CCCCCC] after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-black dark:peer-checked:bg-white">
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Status Indicator -->
                                    <div class="mt-3 text-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium uppercase tracking-wider font-mono"
                                            :class="form[toggle.model]
                                                ? 'bg-black text-white dark:bg-white dark:text-black'
                                                : 'bg-[#E8E8E8] text-[#666666] dark:bg-[#222222] dark:text-[#999999]'">
                                            <svg class="w-3 h-3 mr-1"
                                                :class="form[toggle.model] ? 'text-white dark:text-black' : 'text-[#666666] dark:text-[#999999]'"
                                                fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="3" />
                                            </svg>
                                            {{ form[toggle.model] ? 'ENABLED' : 'DISABLED' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div
                            class="flex flex-col sm:flex-row gap-4 pt-8 border-t border-[#E8E8E8] dark:border-[#222222]">
                            <Link :href="route('previews.update.all', preview.id)"
                                class="w-full inline-flex items-center justify-center px-6 py-3 border-2 border-black dark:border-white text-base font-medium rounded-full text-black dark:text-white bg-white dark:bg-black hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white transition-all duration-200 uppercase tracking-wider font-mono">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                BACK TO PREVIEW
                            </Link>

                            <button type="submit" :disabled="form.processing"
                                class="w-full inline-flex items-center justify-center px-6 py-3 border-2 border-transparent text-base font-medium rounded-full text-white bg-black dark:bg-white dark:text-black hover:bg-white hover:text-black hover:border-black dark:hover:bg-black dark:hover:text-white dark:hover:border-white focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 uppercase tracking-wider font-mono">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-current"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                {{ form.processing ? 'UPDATING...' : 'UPDATE PREVIEW' }}
                            </button>

                            <button type="button" @click="confirmDelete" :disabled="deleting"
                                class="w-full inline-flex items-center justify-center px-6 py-3 border-2 border-[#D71921] text-base font-medium rounded-full text-[#D71921] bg-white dark:bg-black hover:bg-[#D71921] hover:text-white dark:hover:bg-[#D71921] focus:outline-none focus:ring-2 focus:ring-[#D71921] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 uppercase tracking-wider font-mono">
                                <svg v-if="deleting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-current" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                {{ deleting ? 'DELETING...' : 'DELETE PREVIEW' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3';
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
        label: 'Show Header Logo?',
        model: 'show_planetnine_logo',
        description: 'Display Planet Nine logo in the header'
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
                timer: 1000,
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

// Delete preview
import { ref as vueRef } from 'vue';
const deleting = vueRef(false);

const confirmDelete = async () => {
    const result = await Swal.fire({
        title: 'Delete preview?',
        text: 'This will permanently delete the preview. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it',
    });

    if (!result.isConfirmed) return;

    deleting.value = true;

    router.delete(route('previews-delete', preview.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Deleted', text: 'Preview deleted.', timer: 900, showConfirmButton: false, toast: true, position: 'top-end' });
            // Navigate back to previews index
            router.get(route('previews-index'));
        },
        onError: () => {
            deleting.value = false;
            Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete preview.' });
        }
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