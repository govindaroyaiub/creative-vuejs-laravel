<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { CirclePlus, Download, Pencil, Trash2, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, onMounted, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Bills', href: '/bills' }];

const page = usePage();
const bills = computed(() => page.props.bills);
const flash = computed(() => page.props.flash);
const search = ref(page.props.search ?? '');

// Watch input and trigger backend search
watch(search, (value) => {
    router.get(route('bills'), { search: value }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
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
            onSuccess: () => Swal.fire('Deleted!', 'Bill deleted successfully.', 'success'),
            onError: () => Swal.fire('Error!', 'Failed to delete bill.', 'error'),
        });
    }
};

const goToEdit = (id: number) => {
    router.visit(route('bills-edit', id));
};

// Pagination functions
const changePage = (url: string) => {
    if (url) {
        router.get(url, { search: search.value }, {
            preserveScroll: true,
            preserveState: true,
        });
    }
};

const goToPage = (pageNumber: number) => {
    router.get(route('bills'), {
        page: pageNumber,
        search: search.value
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

onMounted(() => {
    if (flash.value?.success) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Bill created successfully!',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
            timerProgressBar: true,
        });
    }
});
</script>

<template>

    <Head title="Bills" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-6">
            <!-- Search & Add -->
            <div class="mb-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                <input v-model="search" placeholder="Search..."
                    class="w-full sm:max-w-xs rounded-2xl border px-4 py-2 dark:bg-neutral-800 dark:text-white" />
                <Link :href="route('bills-create')"
                    class="rounded-xl bg-green-600 px-4 py-2 text-white hover:bg-green-700 flex items-center justify-center whitespace-nowrap">
                <CirclePlus class="mr-2 h-5 w-5" />
                Add Bill
                </Link>
            </div>

            <!-- Desktop Table -->
            <div class="hidden lg:block rounded-2xl overflow-x-auto shadow">
                <table class="w-full rounded bg-white dark:bg-neutral-800 dark:border border">
                    <thead class="bg-gray-100 text-gray-700 dark:bg-neutral-900 dark:text-gray-300 border">
                        <tr class="text-center text-sm uppercase">
                            <th class="px-4 py-2 border-b">#</th>
                            <th class="px-4 py-2 border-b">Name</th>
                            <th class="px-4 py-2 border-b">Client</th>
                            <th class="px-4 py-2 border-b">Total Amount</th>
                            <th class="px-4 py-2 border-b">Date</th>
                            <th class="px-4 py-2 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(bill, index) in bills.data" :key="bill.id"
                            class="border-t text-center text-sm dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700">
                            <td class="px-4 py-2 border-b">{{ index + 1 }}</td>
                            <td class="px-4 py-2 border-b font-medium">{{ bill.name }}</td>
                            <td class="px-4 py-2 border-b">{{ bill.client }}</td>
                            <td class="px-4 py-2 border-b font-semibold">{{ bill.total_amount }}</td>
                            <td class="px-4 py-2 border-b">
                                {{ new Date(bill.created_at).toLocaleDateString('en-GB', {
                                    day: '2-digit',
                                    month: 'long',
                                    year: 'numeric',
                                }) }}
                            </td>
                            <td class="space-x-2 px-4 py-2 border-b">
                                <a :href="route('bills-download', bill.id)" target="_blank"
                                    class="text-green-600 hover:text-green-800 p-1" aria-label="Download Bill">
                                    <Download class="inline h-5 w-5" />
                                </a>
                                <button @click="goToEdit(bill.id)" class="text-blue-600 hover:text-blue-800 p-1"
                                    aria-label="Edit Bill">
                                    <Pencil class="inline h-5 w-5" />
                                </button>
                                <button @click="deleteBill(bill.id)" class="text-red-600 hover:text-red-800 p-1"
                                    aria-label="Delete Bill">
                                    <Trash2 class="inline h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="bills.data.length === 0">
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No bills
                                found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile/Tablet Cards -->
            <div class="lg:hidden space-y-4">
                <div v-for="(bill, index) in bills.data" :key="bill.id"
                    class="bg-white dark:bg-neutral-800 rounded-2xl shadow border border-gray-200 dark:border-neutral-700 p-4">

                    <!-- Header: Number + Name -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                #{{ index + 1 }}
                            </div>
                            <h3 class="font-semibold text-lg break-words text-gray-900 dark:text-white">
                                {{ bill.name }}
                            </h3>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 ml-3">
                            <a :href="route('bills-download', bill.id)" target="_blank"
                                class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20"
                                aria-label="Download Bill">
                                <Download class="h-5 w-5" />
                            </a>
                            <button @click="goToEdit(bill.id)"
                                class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                aria-label="Edit Bill">
                                <Pencil class="h-5 w-5" />
                            </button>
                            <button @click="deleteBill(bill.id)"
                                class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20"
                                aria-label="Delete Bill">
                                <Trash2 class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Client & Amount -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Client:</span> {{ bill.client || 'No client' }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Amount:</span>
                            <span class="font-bold text-green-600 dark:text-green-400">{{ bill.total_amount }}</span>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-medium">Created:</span>
                        {{ new Date(bill.created_at).toLocaleDateString('en-GB', {
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric',
                        }) }}
                    </div>
                </div>

                <!-- No results card -->
                <div v-if="bills.data.length === 0"
                    class="bg-white dark:bg-neutral-800 rounded-2xl shadow border border-gray-200 dark:border-neutral-700 p-8 text-center">
                    <div class="text-gray-500 dark:text-gray-400">No bills found.</div>
                </div>
            </div>

            <!-- Pagination - responsive -->
            <div v-if="bills.data.length && bills.links?.length"
                class="mt-6 p-4">

                <!-- Mobile pagination (simplified) -->
                <div class="lg:hidden">
                    <!-- Results Info -->
                    <div class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">
                        Showing {{ bills.from }} to {{ bills.to }} of {{ bills.total }} bills
                    </div>

                    <!-- Simple prev/next + page selector -->
                    <div class="flex items-center justify-between gap-2">
                        <button @click="changePage(bills.prev_page_url)" :disabled="!bills.prev_page_url"
                            class="px-3 py-2 text-sm rounded-xl transition-all duration-200 flex items-center flex-1 justify-center"
                            :class="bills.prev_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                            <ChevronLeft class="w-4 h-4 mr-1" />
                            Previous
                        </button>

                        <select :value="bills.current_page" @change="goToPage(parseInt($event.target.value))"
                            class="px-3 py-2 border border-gray-300 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-gray-900 dark:text-white text-sm min-w-0">
                            <option v-for="pageNum in bills.last_page" :key="pageNum" :value="pageNum">
                                {{ pageNum }}
                            </option>
                        </select>

                        <button @click="changePage(bills.next_page_url)" :disabled="!bills.next_page_url"
                            class="px-3 py-2 text-sm rounded-xl transition-all duration-200 flex items-center flex-1 justify-center"
                            :class="bills.next_page_url
                                ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-300 dark:border-neutral-700'
                                : 'text-gray-400 cursor-not-allowed border border-gray-200 dark:border-neutral-700'">
                            Next
                            <ChevronRight class="w-4 h-4 ml-1" />
                        </button>
                    </div>
                </div>

                <!-- Desktop pagination (full features) -->
                <div class="hidden lg:flex justify-center space-x-2">
                    <template v-for="link in bills.links" :key="link.label">
                        <component :is="link.url ? 'a' : 'span'" v-html="link.label" :href="link.url"
                            class="rounded-xl border px-4 py-2 text-sm transition-all duration-200" :class="{
                                'bg-indigo-600 text-white': link.active,
                                'cursor-not-allowed text-gray-400': !link.url,
                                'hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300': link.url && !link.active,
                            }" />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>