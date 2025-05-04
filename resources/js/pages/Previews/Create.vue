<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Check, Plus, Minus } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Previews', href: '/previews' },
    { title: 'Add Preview', href: '/previews-create' },
];

const page = usePage();
const clients = computed(() => page.props.clients ?? []);
const users = computed(() => page.props.users ?? []);
const colorPalettes = computed(() => page.props.colorPalettes ?? []);
const authUser = computed(() => page.props.auth?.user ?? null);

const form = useForm({
    name: '',
    client_id: '',
    team_members: authUser.value ? [authUser.value.id] : [],
    color_palette_id: '',
});

const clientSearch = ref('');
const paletteSearch = ref('');
const userSearch = ref('');
const showUserDropdown = ref(false);
const showClientDropdown = ref(false);
const showThemeDropdown = ref(false);

const filteredClients = computed(() =>
    clients.value.filter(c => c.name.toLowerCase().includes(clientSearch.value.toLowerCase()))
);
const filteredPalettes = computed(() =>
    colorPalettes.value.filter(p => p.name.toLowerCase().includes(paletteSearch.value.toLowerCase()))
);
const filteredUsers = computed(() => {
    const q = userSearch.value.toLowerCase();
    return users.value.filter(u => u.name.toLowerCase().includes(q) && u.id !== authUser.value?.id);
});

const isSelected = (id: number) => form.team_members.includes(id);

const toggleUser = (id: number) => {
    if (id === authUser.value?.id) return;
    if (form.team_members.includes(id)) {
        form.team_members = form.team_members.filter(uid => uid !== id);
    } else {
        form.team_members.push(id);
    }
};

const resolveUserName = (id: number) => {
    if (authUser.value?.id === id) return authUser.value.name;
    const found = users.value.find(u => u.id === id);
    return found ? found.name : `User #${id}`;
};

const selectClient = (id: number) => {
    form.client_id = id;
    showClientDropdown.value = false;
};
const selectPalette = (id: number) => {
    form.color_palette_id = id;
    showThemeDropdown.value = false;
};

const submit = () => {
    form.post(route('previews-store'));
};
</script>

<template>

    <Head title="Create Preview" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-4xl bg-white dark:bg-gray-900 rounded-xl shadow p-8 space-y-8">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">Create Preview</h2>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input v-model="form.name" type="text"
                        class="w-full rounded-md border px-4 py-2 text-sm dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client</label>
                    <input v-model="clientSearch" @focus="showClientDropdown = true"
                        @blur="setTimeout(() => showClientDropdown = false, 150)" placeholder="Search client..."
                        class="w-full rounded-md border px-4 py-2 text-sm dark:bg-gray-800 dark:text-white" />
                    <div v-if="showClientDropdown"
                        class="border mt-1 rounded bg-white dark:bg-gray-800 shadow max-h-48 overflow-y-auto">
                        <div v-for="client in filteredClients" :key="client.id"
                            class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                            @click="selectClient(client.id)">
                            {{ client.name }}
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Team Members</label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <span v-for="uid in form.team_members" :key="uid"
                            class="flex items-center gap-1 rounded-full bg-indigo-100 dark:bg-indigo-800 text-indigo-700 dark:text-indigo-200 px-3 py-1 text-sm font-medium">
                            {{ resolveUserName(uid) }}
                            <button v-if="uid !== authUser.value?.id" @click="toggleUser(uid)"
                                class="hover:text-red-600">
                                <Minus class="inline h-4 w-4" />
                            </button>
                        </span>
                    </div>
                    <input v-model="userSearch" @focus="showUserDropdown = true"
                        @blur="setTimeout(() => showUserDropdown = false, 150)" placeholder="Add team members..."
                        class="w-full rounded-md border px-4 py-2 text-sm dark:bg-gray-800 dark:text-white" />
                    <div v-if="showUserDropdown && userSearch"
                        class="border mt-1 rounded bg-white dark:bg-gray-800 shadow max-h-48 overflow-y-auto">
                        <div v-for="user in filteredUsers" :key="user.id"
                            class="flex items-center justify-between px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                            @click="toggleUser(user.id)">
                            <span>{{ user.name }}</span>
                            <span class="text-indigo-600">
                                <Check class="h-4 w-4" v-if="isSelected(user.id)" />
                                <Plus class="h-4 w-4" v-else />
                            </span>
                        </div>
                        <div v-if="filteredUsers.length === 0" class="px-3 py-2 text-gray-500 text-sm">No users found
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Theme</label>
                    <input v-model="paletteSearch" @focus="showThemeDropdown = true"
                        @blur="setTimeout(() => showThemeDropdown = false, 150)" placeholder="Search theme..."
                        class="w-full rounded-md border px-4 py-2 text-sm dark:bg-gray-800 dark:text-white" />
                    <div v-if="showThemeDropdown"
                        class="border mt-1 rounded bg-white dark:bg-gray-800 shadow max-h-48 overflow-y-auto">
                        <div v-for="palette in filteredPalettes" :key="palette.id"
                            class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                            @click="selectPalette(palette.id)">
                            {{ palette.name }}
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button @click="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-6 py-2 text-white hover:bg-indigo-700 disabled:opacity-50">
                        Create
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
