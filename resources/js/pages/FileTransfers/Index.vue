<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { CirclePlus, Pencil, Share2, Trash2, Eye } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'File Transfers', href: '/file-transfers' }];

const page = usePage();
const search = ref(page.props.search ?? '');
const fileTransfers = computed(() => page.props.fileTransfers ?? { data: [], links: [] });

watch(search, (value) => {
    router.get(route('file-transfers'), { search: value }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
});

const deleteFileTransfer = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        router.delete(route('file-transfers-delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire('Deleted!', 'File transfer deleted successfully.', 'success'),
            onError: () => Swal.fire('Error!', 'Failed to delete file transfer.', 'error'),
        });
    }
};

const getTransferLink = (id: number) => {
    const url = `${window.location.origin}/file-transfers-view/${id}`;
    navigator.clipboard.writeText(url);
    Swal.fire('Link Copied!', 'The link has been copied to your clipboard.', 'success');
};
</script>

<template>

    <Head title="File Transfers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Search & Add -->
            <div class="mb-4 flex items-center justify-between">
                <input v-model="search" placeholder="Search..."
                    class="w-full max-w-xs rounded border px-4 py-2 dark:bg-black dark:text-white" />
                <Link :href="route('file-transfers-add')"
                    class="ml-4 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                <CirclePlus class="mr-1 inline h-5 w-5" />
                Add
                </Link>
            </div>

            <!-- Table -->
            <div class="rounded overflow-x-auto shadow">
                <table class="w-full rounded bg-white dark:bg-black dark:border border">
                    <thead class="bg-gray-100 text-gray-700 dark:bg-black dark:text-gray-300">
                        <tr class="text-center text-sm uppercase">
                            <th class="px-4 py-2 border-b">#</th>
                            <th class="px-4 py-2 border-b">Name</th>
                            <th class="px-4 py-2 border-b">Client</th>
                            <th class="px-4 py-2 border-b">Uploader</th>
                            <th class="px-4 py-2 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(transfer, index) in fileTransfers.data" :key="transfer.id"
                            class="border-t text-center text-sm dark:border-gray-700">
                            <td class="px-4 py-2 border-b">{{ index + 1 }}</td>
                            <td class="px-4 py-2 border-b">{{ transfer.name }}</td>
                            <td class="px-4 py-2 border-b">{{ transfer.client }}</td>
                            <td class="px-4 py-2 border-b">
                                {{ transfer.user.name }}
                                <hr />
                                {{ new Date(transfer.created_at).toLocaleDateString('en-GB', {
                                    day: '2-digit',
                                    month: 'long',
                                    year: 'numeric',
                                }) }}
                            </td>
                            <td class="space-x-2 px-4 py-2 border-b">
                                <a :href="`/file-transfers-view/${transfer.slug}`" target="_blank"
                                    class="text-green-600 hover:text-green-800">
                                    <Eye class="inline h-6 w-6" />
                                </a>
                                <button @click="getTransferLink(transfer.id)"
                                    class="text-purple-600 hover:text-purple-800">
                                    <Share2 class="inline h-6 w-6" />
                                </button>
                                <Link :href="route('file-transfers-edit', transfer.id)"
                                    class="text-blue-600 hover:text-blue-800">
                                <Pencil class="inline h-5 w-5" />
                                </Link>
                                <button @click="deleteFileTransfer(transfer.id)"
                                    class="text-red-600 hover:text-red-800">
                                    <Trash2 class="inline h-5 w-5" />
                                </button>
                            </td>
                        </tr>

                        <tr v-if="fileTransfers.data.length === 0">
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">No file transfers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="fileTransfers.data.length && fileTransfers.links?.length"
                class="mt-6 flex justify-center space-x-2"> <template v-for="link in fileTransfers.links"
                    :key="link.label">
                    <component :is="link.url ? 'a' : 'span'" v-html="link.label" :href="link.url"
                        class="rounded border px-4 py-2 text-sm" :class="{
                            'bg-indigo-600 text-white': link.active,
                            'cursor-not-allowed text-gray-400': !link.url,
                            'hover:bg-gray-200 dark:hover:bg-black': link.url && !link.active,
                        }" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>