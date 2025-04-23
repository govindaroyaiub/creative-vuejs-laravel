<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Pencil, Trash2, CirclePlus } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, onMounted } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
];

const page = usePage();
const clients = computed(() => page.props.clients);
const search = ref('');
const flash = computed(() => page.props.flash);

const filteredClients = computed(() => {
    const query = search.value.toLowerCase();
    return clients.value.data.filter((client: any) =>
        client.name.toLowerCase().includes(query) ||
        client.website.toLowerCase().includes(query)
    );
});

const deleteClient = async (id: number) => {
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
        router.delete(route('clients-delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire('Deleted!', 'Client deleted successfully.', 'success'),
            onError: () => Swal.fire('Error!', 'Failed to delete client.', 'error'),
        });
    }
};

const goToEdit = (id: number) => {
    router.visit(route('clients-edit', id));
};

onMounted(() => {
    if (flash.value?.success) {
        Swal.fire('Success!', flash.value.success, 'success');
    }
});
</script>

<template>
    <Head title="Clients" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <input v-model="search" placeholder="Search..." class="w-full max-w-xs rounded border px-4 py-2 dark:bg-gray-700 dark:text-white" />
                <a :href="route('clients-create')" class="ml-4 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                    <CirclePlus class="mr-1 inline h-5 w-5" />
                    Add
                </a>
            </div>

            <table class="w-full rounded bg-white shadow dark:bg-gray-800">
                <thead class="bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr class="text-center text-sm uppercase">
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Website<hr>Preview</th>
                        <th class="px-4 py-2">Logo<hr>Brand Color</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(client, index) in filteredClients" :key="client.id" class="border-t text-center text-sm dark:border-gray-700">
                        <td class="px-4 py-2">{{ index + 1 }}</td>
                        <td class="px-4 py-2">{{ client.name }}</td>
                        <td class="px-4 py-2">{{ client.website }}</td>
                        <td class="px-4 py-2">
                            <a v-if="client.preview_url" :href="client.preview_url" target="_blank" class="text-blue-600 hover:underline">Preview</a>
                            <span v-else>-</span>
                        </td>
                        <td class="px-4 py-2">
                            <img v-if="client.logo" :src="`/public/clients/${client.logo}`" alt="Logo" class="h-8 mx-auto" />
                        </td>
                        <td class="px-4 py-2">
                            <span :style="{ backgroundColor: client.brand_color }" class="inline-block h-4 w-8 rounded border"></span>
                        </td>
                        <td class="space-x-2 px-4 py-2">
                            <button @click="goToEdit(client.id)" class="text-blue-600 hover:text-blue-800">
                                <Pencil class="inline h-5 w-5" />
                            </button>
                            <button @click="deleteClient(client.id)" class="text-red-600 hover:text-red-800">
                                <Trash2 class="inline h-5 w-5" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="filteredClients.length === 0">
                        <td colspan="7" class="px-4 py-4 text-center text-gray-500">No clients found.</td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-6 flex justify-center space-x-2" v-if="clients.data.length > 0 && clients.links.length">
                <template v-for="link in clients.links" :key="link.label">
                    <component
                        :is="link.url ? 'a' : 'span'"
                        v-html="link.label"
                        :href="link.url"
                        class="rounded border px-4 py-2 text-sm"
                        :class="{
                            'bg-indigo-600 text-white': link.active,
                            'cursor-not-allowed text-gray-400': !link.url,
                            'hover:bg-gray-200 dark:hover:bg-gray-700': link.url && !link.active,
                        }"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>