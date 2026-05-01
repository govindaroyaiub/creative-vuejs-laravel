<template>

    <Head title="Edit Preview" />
    <AppLayout
        :breadcrumbs="[{ title: 'Previews', href: '/previews' }, { title: 'Edit Preview', 'href': '/previews-edit/' + preview.id }]">
        <form @submit.prevent="submit"
            class="bg-[#FFFFFF] dark:bg-black font-mono p-4 max-w-7xl mx-auto w-full flex flex-col h-[calc(100vh-4rem)]">
            <!-- Top bar: title + ID + actions -->
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <h1 class="text-base font-semibold font-mono text-black dark:text-white truncate">
                        Edit preview
                    </h1>
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-widest border border-[#666666] dark:border-[#999999] text-[#666666] dark:text-[#999999]">
                        ID: {{ preview.id }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('previews.update.all', preview.id)"
                        class="inline-flex items-center gap-1.5 px-2 py-2 rounded-full border border-[#CCCCCC] dark:border-[#333333] text-xs font-mono tracking-wide text-[#1A1A1A] dark:text-[#E8E8E8] bg-white dark:bg-[#111111] hover:border-black dark:hover:border-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </Link>
                    <button type="button" @click="confirmDelete" :disabled="deleting"
                        class="inline-flex items-center gap-1.5 px-2 py-2 rounded-full border border-[#D71921] text-xs font-mono tracking-wide text-[#D71921] bg-white dark:bg-black hover:bg-[#D71921] hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg v-if="deleting" class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 px-2 py-2 rounded-full border-2 border-black dark:border-white bg-black dark:bg-white text-white dark:text-black text-xs font-mono tracking-wide hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ form.processing ? 'Updating...' : 'Update preview' }}
                    </button>
                </div>
            </div>

            <!-- Main grid: identification (left) + settings (right) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 flex-1 min-h-0">
                <!-- Left column: identification (2/3 width) -->
                <div
                    class="lg:col-span-2 bg-white dark:bg-[#0A0A0A] rounded-lg border border-[#E8E8E8] dark:border-[#222222] p-3 flex flex-col gap-3 min-h-0">
                    <!-- Name -->
                    <div>
                        <label for="preview-name"
                            class="block text-[10px] font-medium text-[#666666] dark:text-[#999999] mb-1 uppercase tracking-widest font-mono">
                            Preview name *
                        </label>
                        <input id="preview-name" v-model="form.name" type="text"
                            placeholder="e.g. Facebook Ad Campaign - June 2024"
                            class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm text-black dark:text-white bg-white dark:bg-[#111111] placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                            :class="{ 'border-[#D71921] focus:border-[#D71921]': form.errors.name }" required />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-[#D71921]">{{ form.errors.name }}</p>
                    </div>

                    <!-- Client / Logo / Theme -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label for="client-select"
                                class="block text-[10px] font-medium text-[#666666] dark:text-[#999999] mb-1 uppercase tracking-widest font-mono">
                                Client *
                            </label>
                            <select id="client-select" v-model="form.client_id"
                                class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm text-black dark:text-white bg-white dark:bg-[#111111] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                :class="{ 'border-[#D71921] focus:border-[#D71921]': form.errors.client_id }" required>
                                <option disabled value="">Select client</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.client_id" class="mt-1 text-xs text-[#D71921]">{{ form.errors.client_id }}</p>
                        </div>
                        <div>
                            <label for="header-logo-select"
                                class="block text-[10px] font-medium text-[#666666] dark:text-[#999999] mb-1 uppercase tracking-widest font-mono">
                                Header logo *
                            </label>
                            <select id="header-logo-select" v-model="form.header_logo_id"
                                class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm text-black dark:text-white bg-white dark:bg-[#111111] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                :class="{ 'border-[#D71921] focus:border-[#D71921]': form.errors.header_logo_id }"
                                required>
                                <option disabled value="">Select header logo</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.header_logo_id" class="mt-1 text-xs text-[#D71921]">{{ form.errors.header_logo_id }}</p>
                        </div>
                        <div>
                            <label for="color-palette-select"
                                class="block text-[10px] font-medium text-[#666666] dark:text-[#999999] mb-1 uppercase tracking-widest font-mono">
                                Theme *
                            </label>
                            <select id="color-palette-select" v-model="form.color_palette_id"
                                class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm text-black dark:text-white bg-white dark:bg-[#111111] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                :class="{ 'border-[#D71921] focus:border-[#D71921]': form.errors.color_palette_id }"
                                required>
                                <option disabled value="">Select theme</option>
                                <option v-for="palette in colorPalettes" :key="palette.id" :value="palette.id">{{
                                    palette.name }}</option>
                            </select>
                            <p v-if="form.errors.color_palette_id" class="mt-1 text-xs text-[#D71921]">{{ form.errors.color_palette_id }}</p>
                        </div>
                    </div>

                    <!-- Team Members (fills remaining vertical space) -->
                    <div class="flex-1 min-h-0 flex flex-col">
                        <div class="flex items-center justify-between mb-1">
                            <label for="team-search"
                                class="text-[10px] font-medium text-[#666666] dark:text-[#999999] uppercase tracking-widest font-mono">
                                Team members *
                            </label>
                            <span
                                class="text-[10px] font-mono text-[#999999]">{{ selectedUsers.length }} selected</span>
                        </div>
                        <input id="team-search" v-model="userSearch" type="text"
                            placeholder="Search team members..."
                            class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm text-black dark:text-white bg-white dark:bg-[#111111] placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white transition-colors mb-1.5"
                            :class="{ 'border-[#D71921] focus:border-[#D71921]': form.errors.team_ids }"
                            autocomplete="off" />

                        <!-- Always-visible user list, scroll inside -->
                        <div
                            class="flex-1 min-h-0 overflow-y-auto rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#111111] divide-y divide-[#E8E8E8] dark:divide-[#222222]">
                            <button v-for="user in displayUsers" :key="user.id" type="button"
                                @click="toggleUser(user)" :disabled="user.id === authUser.id"
                                class="w-full text-left px-2 py-2 transition-colors flex items-center gap-2 hover:bg-[#F5F5F5] dark:hover:bg-black focus:bg-[#F5F5F5] dark:focus:bg-black focus:outline-none disabled:cursor-not-allowed">
                                <span
                                    class="w-6 h-6 rounded-full grid place-items-center text-[10px] font-bold flex-shrink-0"
                                    :class="form.team_ids.includes(user.id)
                                        ? 'bg-black dark:bg-white text-white dark:text-black'
                                        : 'bg-[#F5F5F5] dark:bg-black text-[#666666] dark:text-[#999999] border border-[#E8E8E8] dark:border-[#222222]'">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </span>
                                <span class="text-sm flex-1 truncate"
                                    :class="form.team_ids.includes(user.id) ? 'text-black dark:text-white font-semibold' : 'text-[#666666] dark:text-[#999999]'">
                                    {{ user.name }}
                                </span>
                                <span v-if="user.id === authUser.id"
                                    class="text-[9px] font-mono uppercase tracking-widest text-[#999999]">You</span>
                                <span v-else-if="form.team_ids.includes(user.id)"
                                    class="text-black dark:text-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                            </button>
                            <div v-if="displayUsers.length === 0"
                                class="px-2 py-4 text-center text-xs font-mono text-[#999999]">
                                No users found
                            </div>
                        </div>
                        <p v-if="form.errors.team_ids" class="mt-1 text-xs text-[#D71921]">{{ form.errors.team_ids }}</p>
                    </div>
                </div>

                <!-- Right column: settings toggles (1/3 width, stacked, fills height) -->
                <div
                    class="bg-white dark:bg-[#0A0A0A] rounded-lg border border-[#E8E8E8] dark:border-[#222222] p-3 flex flex-col">
                    <h2
                        class="text-[10px] font-medium text-[#666666] dark:text-[#999999] uppercase tracking-widest font-mono mb-2">
                        Settings
                    </h2>
                    <div class="flex-1 flex flex-col justify-around">
                        <div v-for="toggle in toggleConfigs" :key="toggle.model"
                            class="flex items-center justify-between gap-3 py-2 border-b border-[#E8E8E8] dark:border-[#222222] last:border-b-0">
                            <div class="min-w-0">
                                <label :for="`toggle-${toggle.model}`"
                                    class="block text-xs font-semibold text-black dark:text-white cursor-pointer font-mono">
                                    {{ toggle.label }}
                                </label>
                                <p class="text-[10px] text-[#666666] dark:text-[#999999]">
                                    {{ toggle.description }}
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                <input :id="`toggle-${toggle.model}`" type="checkbox" v-model="form[toggle.model]"
                                    class="sr-only peer" />
                                <div
                                    class="w-9 h-5 bg-[#E8E8E8] dark:bg-[#222222] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-[#CCCCCC] after:border dark:after:bg-black after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black dark:peer-checked:bg-white">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </form>
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

// All users (filtered by search query if any), with selected ones first
const displayUsers = computed(() => {
    const all: any[] = (users.value as any[]) || [];
    const query = userSearch.value.toLowerCase().trim();
    const list: any[] = query
        ? all.filter((u: any) => u.name.toLowerCase().includes(query))
        : all.slice();
    return list.sort((a: any, b: any) => {
        const aSel = form.team_ids.includes(a.id) ? 0 : 1;
        const bSel = form.team_ids.includes(b.id) ? 0 : 1;
        if (aSel !== bSel) return aSel - bSel;
        return a.name.localeCompare(b.name);
    });
});

// Methods
const toggleUser = (user: any) => {
    if (user.id === authUser.value.id) return;
    if (form.team_ids.includes(user.id)) {
        form.team_ids = form.team_ids.filter((uid: number) => uid !== user.id);
    } else {
        form.team_ids.push(user.id);
    }
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