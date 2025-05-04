<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Eye, Pencil, Trash2, CirclePlus } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const page = usePage();
const search = ref('');

const previews = computed(() => page.props.previews ?? { data: [], links: [] });

const filteredPreviews = computed(() => {
    const q = search.value.toLowerCase();
    const list = previews.value?.data ?? [];
    return list.filter(
        (p: any) =>
            p.name.toLowerCase().includes(q) ||
            p.client?.name.toLowerCase().includes(q) ||
            p.uploader?.name.toLowerCase().includes(q),
    );
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
            onSuccess: () => {
                Swal.fire('Deleted!', 'Preview deleted successfully.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to delete preview.', 'error');
            },
        });
    }
};

const getTypes = (preview: any) => {
    const types = new Set();
    preview.versions?.forEach((v: any) => types.add(v.type));
    return Array.from(types).join(', ');
};
</script>

<template>

    <Head title="Previews" />
    <AppLayout :breadcrumbs="[{ title: 'Previews', href: '/previews' }]">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <input v-model="search" placeholder="Search..."
                    class="w-full max-w-sm rounded border px-4 py-2 dark:bg-gray-700 dark:text-white" />
                <Link :href="route('previews-create')"
                    class="ml-4 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                <CirclePlus class="mr-1 inline h-5 w-5" /> Add
                </Link>
            </div>

            <div class="overflow-x-auto rounded-lg shadow">
                <table class="min-w-full table-auto text-sm text-gray-700 dark:text-gray-200">
                    <thead class="bg-gray-100 dark:bg-gray-700 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-center">#</th>
                            <th class="px-4 py-3 text-left">Name & Client</th>
                            <th class="px-4 py-3 text-center">Team</th>
                            <th class="px-4 py-3 text-center">Uploader</th>
                            <th class="px-4 py-3 text-center">Theme & Types</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="(preview, index) in filteredPreviews" :key="preview.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="text-center px-4 py-3 font-medium">{{ index + 1 }}</td>

                            <!-- Name / Client (left-aligned) -->
                            <td class="px-4 py-3 text-left">
                                <div class="font-semibold">{{ preview.name }}</div>
                                <div class="text-xs text-gray-500">{{ preview.client?.name ?? '-' }}</div>
                            </td>

                            <!-- Team Members (center-aligned) -->
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center flex-wrap gap-1">
                                    <span v-for="u in preview.team_users" :key="u.id"
                                        class="inline-block rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700 dark:bg-indigo-800 dark:text-white">
                                        {{ u.name }}
                                    </span>
                                </div>
                            </td>

                            <!-- Uploader / Created (center-aligned) -->
                            <td class="px-4 py-3 text-center">
                                <div class="text-sm">{{ preview.uploader?.name ?? '-' }}</div>
                                <div class="text-xs text-gray-400">
                                    {{ new Date(preview.created_at).toLocaleDateString('en-GB', {
                                        day: '2-digit',
                                        month: 'short',
                                        year: 'numeric'
                                    }) }}
                                </div>
                            </td>

                            <!-- Theme / Types (center-aligned) -->
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="h-4 w-4 rounded-full border"
                                        :style="{ backgroundColor: preview.color_palette?.primary ?? '#ccc' }"
                                        title="Primary Color"></div>
                                    <span class="text-sm">{{ preview.color_palette?.name ?? '-' }}</span>
                                </div>
                                <div class="text-xs text-gray-400">{{ getTypes(preview) || '-' }}</div>
                            </td>

                            <!-- Actions (center-aligned) -->
                            <td class="text-center px-4 py-3 space-x-2">
                                <Link :href="`/previews/${preview.id}`" class="text-green-600 hover:text-green-800">
                                <Eye class="inline h-5 w-5" />
                                </Link>
                                <Link :href="route('previews-edit', preview.id)"
                                    class="text-blue-600 hover:text-blue-800">
                                <Pencil class="inline h-5 w-5" />
                                </Link>
                                <button @click="deletePreview(preview.id)" class="text-red-600 hover:text-red-800">
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
            <div v-if="previews.value?.links?.length" class="flex justify-center space-x-2">
                <template v-for="link in previews.value.links" :key="link.label">
                    <component :is="link.url ? 'a' : 'span'" v-html="link.label" :href="link.url"
                        class="rounded border px-4 py-2 text-sm" :class="{
                            'bg-indigo-600 text-white': link.active,
                            'cursor-not-allowed text-gray-400': !link.url,
                            'hover:bg-gray-200 dark:hover:bg-gray-700': link.url && !link.active,
                        }" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>