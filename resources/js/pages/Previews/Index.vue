<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Pencil, Trash2, CirclePlus, X, Share2, Settings2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';
import PreviewStepBasicInfo from './Partials/PreviewStepBasicInfo.vue';

const loading = ref(false);
const page = usePage();
const search = ref('');
const showModal = ref(false);

const previews = computed(() => page.props.previews ?? { data: [], links: [] });
const clients = computed(() => page.props.clients ?? []);
const users = computed(() => page.props.users ?? []);
const colorPalettes = computed(() => page.props.colorPalettes ?? []);
const authUser = computed(() => page.props.auth?.user ?? {});

function getDefaultFormData() {
    return {
        name: '',
        client_id: '',
        team_ids: [authUser.value.id],
        color_palette_id: '5',
        requires_login: false,
        show_planetnine_logo: true,
        show_sidebar_logo: true,
        show_footer: true,
    };
}

const formData = ref(getDefaultFormData());

const filteredPreviews = computed(() => {
    const q = search.value.toLowerCase();
    return previews.value.data.filter(
        (p: any) =>
            p.name.toLowerCase().includes(q) ||
            p.client?.name?.toLowerCase().includes(q) ||
            p.uploader?.name?.toLowerCase().includes(q)
    );
});

watch(search, (val) => {
    router.get(route('previews-index'), { search: val }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
});

const deletePreview = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the preview.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        router.delete(route('previews-delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire('Deleted!', 'Preview deleted successfully.', 'success'),
            onError: () => Swal.fire('Error!', 'Failed to delete preview.', 'error'),
        });
    }
};

const getTypes = (preview: any) => {
    const types = new Set();
    preview.categories?.forEach((v: any) => types.add((v.type || '').toLowerCase()));
    return Array.from(types).join(', ');
};

const closeModal = () => {
    showModal.value = false;
    formData.value = getDefaultFormData();
};

const submitForm = () => {
    const payload = new FormData();
    payload.append('name', formData.value.name);
    payload.append('client_id', formData.value.client_id);
    payload.append('color_palette_id', formData.value.color_palette_id ?? '');
    payload.append('requires_login', formData.value.requires_login ? '1' : '0');
    payload.append('show_planetnine_logo', formData.value.show_planetnine_logo ? '1' : '0');
    payload.append('show_sidebar_logo', formData.value.show_sidebar_logo ? '1' : '0');
    payload.append('show_footer', formData.value.show_footer ? '1' : '0');

    formData.value.team_ids.forEach(id => payload.append('team_ids[]', id));

    router.post(route('previews-store'), payload, {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire('Success!', 'Preview created successfully.', 'success');
            closeModal();
        },
        onError: (error) => {
            console.log(error);
            Swal.fire('Error!', 'Failed to create preview.', 'error');
        }
    });
};
</script>

<template>

    <Head title="Previews" />
    <AppLayout :breadcrumbs="[{ title: 'Previews', href: '/previews' }]">
        <div class="p-6 space-y-4">
            <!-- Search & Create -->
            <div class="flex items-center justify-between">
                <input v-model="search" placeholder="Search..." aria-label="Search previews"
                    class="w-full max-w-xs rounded border px-4 py-2 dark:bg-black dark:text-white" />
                <button @click="showModal = true"
                    class="ml-4 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700" aria-label="Add Preview">
                    <CirclePlus class="mr-1 inline h-5 w-5" /> Add
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded shadow">
                <table class="w-full rounded bg-white shadow dark:bg-black dark:border border">
                    <thead class="bg-gray-100 dark:bg-black text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-center border-b">#</th>
                            <th class="px-4 py-3 text-left border-b">Name & Client</th>
                            <th class="px-4 py-3 text-center border-b">Team</th>
                            <th class="px-4 py-3 text-center border-b">Uploader</th>
                            <th class="px-4 py-3 text-center border-b">Theme & Types</th>
                            <th class="px-4 py-3 text-center border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-black">
                        <tr v-for="(preview, index) in filteredPreviews" :key="preview.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800 border-b">
                            <td class="text-center px-4 py-3 font-medium border-b">{{ index + 1 }}</td>
                            <td class="px-4 py-3 text-left border-b">
                                <div class="font-semibold capitalize">{{ preview.name }}</div>
                                <div class="text-xs text-gray-500">{{ preview.client?.name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center border-b">
                                <div class="flex justify-center flex-wrap gap-1">
                                    <span v-for="u in preview.team_users" :key="u.id"
                                        class="inline-block rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700 dark:bg-indigo-800 dark:text-white">
                                        {{ u.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center border-b">
                                <div class="text-sm">{{ preview.uploader?.name ?? '-' }}</div>
                                <div class="text-xs text-gray-400">{{ new
                                    Date(preview.created_at).toLocaleDateString('en-GB', {
                                        day: '2-digit', month:
                                            'short', year: 'numeric'
                                    }) }}</div>
                            </td>
                            <td class="px-4 py-3 text-center border-b">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="h-4 w-4 rounded-full border"
                                        :style="{ backgroundColor: preview.color_palette?.tertiary ?? '#ccc' }"
                                        title="Primary Color"></div>
                                    <span class="text-sm">{{ preview.color_palette?.name ?? '-' }}</span>
                                </div>
                                <div class="text-xs text-gray-400">{{ getTypes(preview) || '-' }}</div>
                            </td>
                            <td class="text-center px-4 py-3 space-x-2 border-b">
                                <a :href="route('previews-show', preview.slug)"
                                    class="text-green-600 hover:text-green-800" target="_blank" rel="noopener"
                                    aria-label="View Preview">
                                    <Eye class="inline h-5 w-5" />
                                </a>
                                <a :href="`${preview.client?.preview_url}/previews/show/${preview.slug}`"
                                    class="text-yellow-600 hover:text-yellow-800" target="_blank" rel="noopener"
                                    aria-label="View Preview">
                                    <Share2 class="inline h-5 w-5" />
                                </a>
                                <Link :href="route('previews.update.all', preview.id)"
                                    class="text-indigo-600 hover:text-indigo-800" aria-label="Edit Preview">
                                <Settings2 class="inline h-5 w-5" />
                                </Link>

                                <button @click="deletePreview(preview.id)" class="text-red-600 hover:text-red-800"
                                    aria-label="Delete Preview">
                                    <Trash2 class="inline h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filteredPreviews.length === 0">
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No previews
                                found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="previews.data.length > 0 && previews.links.length > 1" class="flex justify-center space-x-2">
                <template v-for="(link, i) in previews.links" :key="i">
                    <a v-if="link.url" :href="link.url" class="rounded border px-4 py-2 text-sm" :class="{
                        'bg-indigo-600 text-white': link.active,
                        'hover:bg-gray-200 dark:hover:bg-gray-700': !link.active
                    }" v-html="link.label" />
                    <span v-else class="rounded border px-4 py-2 text-sm text-gray-400 cursor-not-allowed"
                        v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white dark:bg-black p-6 rounded-lg w-full max-w-6xl relative overflow-hidden">
                <button class="absolute top-2 right-2 text-gray-400 hover:text-red-500" @click="closeModal"
                    aria-label="Close Modal">
                    <X class="h-6 w-6" />
                </button>
                <!-- Directly show the form component -->
                <PreviewStepBasicInfo v-bind="{
                    form: formData,
                    users: users,
                    clients: clients,
                    colorPalettes: colorPalettes,
                    authUser: authUser
                }" @submit="submitForm" @close="closeModal" />
            </div>
        </div>
    </AppLayout>
</template>