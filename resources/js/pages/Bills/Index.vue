<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Download, Pencil, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Bills',
        href: '/bills',
    },
];

const page = usePage();
const bills = computed(() => page.props.bills);
const search = ref('');

const filteredBills = computed(() => {
    const query = search.value.toLowerCase();
    return bills.value.data.filter((bill) => bill.name.toLowerCase().includes(query) || bill.client.toLowerCase().includes(query));
});

const deleteBill = async (id: number) => {
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
        router.delete(route('bills-delete', id), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('Deleted!', 'Bill deleted successfully.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to delete bill.', 'error');
            },
        });
    }
};

const goToEdit = (id: number) => {
    router.visit(route('bills-edit', id));
};
</script>

<template>
    <Head title="Bills" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <input v-model="search" placeholder="Search..." class="w-full max-w-xs rounded border px-4 py-2 dark:bg-gray-700 dark:text-white" />
                <a :href="route('bills-create')" class="ml-4 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                    <CirclePlus class="mr-1 inline h-5 w-5" />
                    Add
                </a>
            </div>

            <!-- Bills Table -->
            <table class="w-full rounded bg-white shadow dark:bg-gray-800">
                <thead class="bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr class="text-center text-sm uppercase">
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Client</th>
                        <th class="px-4 py-2">Total Amount</th>
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(bill, index) in filteredBills" :key="bill.id" class="border-t text-center text-sm dark:border-gray-700">
                        <td class="px-4 py-2">{{ index + 1 }}</td>
                        <td class="px-4 py-2">{{ bill.name }}</td>
                        <td class="px-4 py-2">{{ bill.client }}</td>
                        <td class="px-4 py-2">{{ bill.total_amount }}</td>
                        <td class="px-4 py-2">{{ new Date(bill.created_at).toLocaleDateString('en-GB') }}</td>
                        <td class="space-x-2 px-4 py-2">
                            <a :href="route('bills-download', bill.id)" target="_blank" class="text-green-600 hover:text-green-800">
                                <Download class="inline h-5 w-5" />
                            </a>
                            <button @click="goToEdit(bill.id)" class="text-blue-600 hover:text-blue-800">
                                <Pencil class="inline h-5 w-5" />
                            </button>
                            <button @click="deleteBill(bill.id)" class="text-red-600 hover:text-red-800">
                                <Trash2 class="inline h-5 w-5" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="filteredBills.length === 0">
                        <td colspan="6" class="px-4 py-4 text-center text-gray-500">No bills found.</td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-6 flex justify-center space-x-2" v-if="bills.data.length > 0 && bills.links.length">
                <template v-for="link in bills.links" :key="link.label">
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
