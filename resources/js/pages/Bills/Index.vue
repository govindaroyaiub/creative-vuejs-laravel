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
        customClass: { popup: 'rounded-lg' }
    });

    if (result.isConfirmed) {
        router.delete(route('bills-delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire({
                title: 'Deleted!',
                text: 'Bill deleted successfully.',
                icon: 'success',
                customClass: { popup: 'rounded-lg' }
            }),
            onError: () => Swal.fire({
                title: 'Error!',
                text: 'Failed to delete bill.',
                icon: 'error',
                customClass: { popup: 'rounded-lg' }
            }),
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



onMounted(() => {
    if (flash.value?.success) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Bill created successfully!',
            timer: 1000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
            timerProgressBar: true,
            customClass: { popup: 'rounded-lg' }
        });
    }
});
</script>

<template>

    <Head title="Bills" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 md:p-6">
                <!-- Search & Add -->
                <div class="mb-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                    <input v-model="search" placeholder="Search..."
                        class="w-full sm:max-w-xs rounded-full border-2 border-[#CCCCCC] dark:border-[#333333] px-4 py-2 bg-white dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                    <Link :href="route('bills-create')"
                        class="rounded-full bg-black dark:bg-white text-white dark:text-black px-4 py-2 hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white flex items-center justify-center whitespace-nowrap group font-mono tracking-wide text-sm transition-colors">
                        <CirclePlus class="mr-2 h-5 w-5 group-hover:rotate-90 transition-transform duration-200"
                            stroke-width="1.5" />
                        Add Bill
                    </Link>
                </div>

                <!-- Desktop Table -->
                <div class="hidden lg:block rounded-lg overflow-x-auto border-2 border-[#E8E8E8] dark:border-[#222222]">
                    <table class="w-full rounded bg-white dark:bg-[#111111]">
                        <thead class="bg-[#F5F5F5] dark:bg-black text-black dark:text-white">
                            <tr class="text-center text-xs font-mono tracking-wide">
                                <th class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222]">#</th>
                                <th class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222]">Name</th>
                                <th class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222]">Client</th>
                                <th class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222]">Total Amount</th>
                                <th class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222]">Date</th>
                                <th class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222]">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(bill, index) in bills.data" :key="bill.id"
                                class="border-t border-[#E8E8E8] dark:border-[#222222] text-center text-sm hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] transition-colors">
                                <td
                                    class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222] text-[#666666] dark:text-[#999999] tabular-nums">
                                    {{ index + 1 }}</td>
                                <td
                                    class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222] font-medium uppercase font-mono">
                                    {{ bill.name }}</td>
                                <td class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222]">{{ bill.client }}
                                </td>
                                <td
                                    class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222] font-semibold tabular-nums">
                                    {{ bill.total_amount }}</td>
                                <td
                                    class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222] text-[#666666] dark:text-[#999999] text-xs">
                                    {{ new Date(bill.created_at).toLocaleDateString('en-GB', {
                                        day: '2-digit',
                                        month: 'long',
                                        year: 'numeric',
                                    }) }}
                                </td>
                                <td class="space-x-2 px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222]">
                                    <a :href="route('bills-download', bill.id)" target="_blank"
                                        class="text-black dark:text-white hover:text-[#666666] dark:hover:text-[#999999] p-1 transition-colors inline-block"
                                        aria-label="Download Bill">
                                        <Download class="inline h-5 w-5" stroke-width="1.5" />
                                    </a>
                                    <button @click="goToEdit(bill.id)"
                                        class="text-black dark:text-white hover:text-[#666666] dark:hover:text-[#999999] p-1 transition-colors"
                                        aria-label="Edit Bill">
                                        <Pencil class="inline h-5 w-5" stroke-width="1.5" />
                                    </button>
                                    <button @click="deleteBill(bill.id)"
                                        class="text-[#D71921] hover:text-red-700 p-1 transition-colors"
                                        aria-label="Delete Bill">
                                        <Trash2 class="inline h-5 w-5" stroke-width="1.5" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="bills.data.length === 0">
                                <td colspan="6"
                                    class="px-4 py-8 text-center text-[#666666] dark:text-[#999999] font-mono tracking-wide text-sm">
                                    No bills
                                    found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile/Tablet Cards -->
                <div class="lg:hidden space-y-4">
                    <div v-for="(bill, index) in bills.data" :key="bill.id"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] hover:border-black hover:dark:border-white p-4 transition-colors">

                        <!-- Header: Number + Name -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0">
                                <div
                                    class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] mb-1 tabular-nums">
                                    #{{ index + 1 }}
                                </div>
                                <h3 class="font-semibold text-sm break-words font-mono">
                                    {{ bill.name }}
                                </h3>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 ml-3">
                                <a :href="route('bills-download', bill.id)" target="_blank"
                                    class="text-black dark:text-white hover:text-[#666666] dark:hover:text-[#999999] p-2 rounded-lg transition-colors"
                                    aria-label="Download Bill">
                                    <Download class="h-5 w-5" stroke-width="1.5" />
                                </a>
                                <button @click="goToEdit(bill.id)"
                                    class="text-black dark:text-white hover:text-[#666666] dark:hover:text-[#999999] p-2 rounded-lg transition-colors"
                                    aria-label="Edit Bill">
                                    <Pencil class="h-5 w-5" stroke-width="1.5" />
                                </button>
                                <button @click="deleteBill(bill.id)"
                                    class="text-[#D71921] hover:text-red-700 p-2 rounded-lg transition-colors"
                                    aria-label="Delete Bill">
                                    <Trash2 class="h-5 w-5" stroke-width="1.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Client & Amount -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                            <div class="text-sm">
                                <span
                                    class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999]">Client:</span>
                                <span class="ml-1">{{ bill.client || 'No client' }}</span>
                            </div>
                            <div class="text-sm">
                                <span
                                    class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999]">Amount:</span>
                                <span class="font-bold ml-1 tabular-nums">{{ bill.total_amount }}</span>
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="text-sm text-[#666666] dark:text-[#999999]">
                            <span class="text-xs font-mono tracking-wide">Created:</span>
                            <span class="ml-1">{{ new Date(bill.created_at).toLocaleDateString('en-GB', {
                                day: '2-digit',
                                month: 'long',
                                year: 'numeric',
                            }) }}</span>
                        </div>
                    </div>

                    <!-- No results card -->
                    <div v-if="bills.data.length === 0"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-8 text-center">
                        <div class="text-[#666666] dark:text-[#999999] font-mono tracking-wide text-sm">No
                            bills found.</div>
                    </div>
                </div>

                <!-- Pagination - Responsive -->
                <div v-if="bills.data.length && bills.links?.length" class="mt-6 p-4">

                    <!-- Mobile/Tablet pagination (simplified) -->
                    <div class="lg:hidden">
                        <!-- Results Info -->
                        <div
                            class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] text-center mb-3">
                            Showing <span class="tabular-nums">{{ bills.from }}</span> to <span class="tabular-nums">{{
                                bills.to }}</span> of <span class="tabular-nums">{{ bills.total }}</span> bills
                        </div>

                        <!-- Simple prev/next navigation -->
                        <div class="flex items-center justify-between gap-4">
                            <button @click="changePage(bills.prev_page_url)" :disabled="!bills.prev_page_url"
                                class="px-4 py-2 text-xs rounded-full transition-colors font-mono tracking-wide flex items-center gap-2"
                                :class="bills.prev_page_url
                                    ? 'text-black dark:text-white hover:bg-black hover:dark:bg-white hover:text-white hover:dark:text-black border-2 border-black dark:border-white'
                                    : 'text-[#CCCCCC] dark:text-[#333333] cursor-not-allowed border-2 border-[#CCCCCC] dark:border-[#333333]'">
                                <ChevronLeft class="w-4 h-4" stroke-width="1.5" />
                                Previous
                            </button>

                            <span class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999]">
                                Page <span class="tabular-nums">{{ bills.current_page }}</span> of <span
                                    class="tabular-nums">{{ bills.last_page }}</span>
                            </span>

                            <button @click="changePage(bills.next_page_url)" :disabled="!bills.next_page_url"
                                class="px-4 py-2 text-xs rounded-full transition-colors font-mono tracking-wide flex items-center gap-2"
                                :class="bills.next_page_url
                                    ? 'text-black dark:text-white hover:bg-black hover:dark:bg-white hover:text-white hover:dark:text-black border-2 border-black dark:border-white'
                                    : 'text-[#CCCCCC] dark:text-[#333333] cursor-not-allowed border-2 border-[#CCCCCC] dark:border-[#333333]'">
                                Next
                                <ChevronRight class="w-4 h-4" stroke-width="1.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Desktop pagination (full features) -->
                    <div class="hidden lg:flex items-center justify-between">
                        <!-- Results Info -->
                        <div class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999]">
                            Showing <span class="tabular-nums">{{ bills.from }}</span> to <span class="tabular-nums">{{
                                bills.to }}</span> of <span class="tabular-nums">{{ bills.total }}</span> bills
                        </div>

                        <!-- Pagination Controls -->
                        <div class="flex items-center space-x-2">
                            <!-- Previous Button -->
                            <button @click="changePage(bills.prev_page_url)" :disabled="!bills.prev_page_url"
                                class="px-4 py-2 text-xs rounded-full transition-colors flex items-center font-mono tracking-wide"
                                :class="bills.prev_page_url
                                    ? 'text-black dark:text-white hover:bg-black hover:dark:bg-white hover:text-white hover:dark:text-black border-2 border-black dark:border-white'
                                    : 'text-[#CCCCCC] dark:text-[#333333] cursor-not-allowed border-2 border-[#CCCCCC] dark:border-[#333333]'">
                                <ChevronLeft class="w-4 h-4 mr-1" stroke-width="1.5" />
                                Previous
                            </button>

                            <!-- Page Numbers -->
                            <div class="flex items-center space-x-1">
                                <template v-for="link in bills.links.slice(1, -1)" :key="link.label">
                                    <button v-if="link.url" @click="changePage(link.url)"
                                        class="px-3 py-2 text-xs rounded-full transition-colors font-mono tabular-nums"
                                        :class="link.active
                                            ? 'bg-black dark:bg-white text-white dark:text-black border-2 border-black dark:border-white'
                                            : 'text-[#666666] dark:text-[#999999] hover:bg-black hover:dark:bg-white hover:text-white hover:dark:text-black border-2 border-[#CCCCCC] dark:border-[#333333]'"
                                        v-html="link.label" />
                                    <span v-else
                                        class="px-3 py-2 text-xs text-[#CCCCCC] dark:text-[#333333] cursor-not-allowed font-mono"
                                        v-html="link.label" />
                                </template>
                            </div>

                            <!-- Next Button -->
                            <button @click="changePage(bills.next_page_url)" :disabled="!bills.next_page_url"
                                class="px-4 py-2 text-xs rounded-full transition-colors flex items-center font-mono tracking-wide"
                                :class="bills.next_page_url
                                    ? 'text-black dark:text-white hover:bg-black hover:dark:bg-white hover:text-white hover:dark:text-black border-2 border-black dark:border-white'
                                    : 'text-[#CCCCCC] dark:text-[#333333] cursor-not-allowed border-2 border-[#CCCCCC] dark:border-[#333333]'">
                                Next
                                <ChevronRight class="w-4 h-4 ml-1" stroke-width="1.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>