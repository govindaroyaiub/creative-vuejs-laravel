<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Download, FileText, Pencil, Receipt, User } from 'lucide-vue-next';
import { computed } from 'vue';

// Persistent layout — keeps AppLayout (sidebar/backdrop) mounted across
// Inertia navigations instead of rebuilding it on every page change.
defineOptions({
    layout: (h: any, page: any) =>
        h(AppLayout, { breadcrumbs: [
            { title: 'Bills', href: '/bills' },
            { title: 'Bill Details', href: '#' },
        ] }, () => page),
});

const page = usePage<any>();
const bill = computed<any>(() => page.props.bill);
const amountInWords = computed<string>(() => String(page.props.amountInWords ?? ''));

const subBills = computed<any[]>(() => bill.value?.sub_bills ?? []);
const documents = computed<any[]>(() => bill.value?.documents ?? []);

const money = (value: number | string) =>
    new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value) || 0);

const quantity = (value: number | string) => new Intl.NumberFormat('en-US').format(Number(value) || 0);

const issueDate = computed(() =>
    bill.value?.created_at
        ? new Date(bill.value.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' })
        : '—',
);

const formatFileSize = (bytes: number) => {
    if (!bytes) return '0 bytes';
    if (bytes >= 1073741824) return `${(bytes / 1073741824).toFixed(2)} GB`;
    if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(2)} MB`;
    if (bytes >= 1024) return `${(bytes / 1024).toFixed(2)} KB`;
    return `${bytes} bytes`;
};
</script>

<template>

    <Head :title="`Bill #${bill.id}`" />
    <div class="min-h-screen bg-white dark:bg-black font-mono">
            <div class="p-4 md:p-6">
                <div class="max-w-5xl mx-auto space-y-6">

                    <!-- Header -->
                    <div
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#CCCCCC] dark:border-[#222222] p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <Receipt :stroke-width="1.5" class="w-5 h-5 text-black dark:text-white" />
                                    <span
                                        class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] tabular-nums">
                                        Bill #{{ bill.id }}
                                    </span>
                                </div>
                                <h1 class="text-lg font-semibold uppercase text-black dark:text-white break-words">
                                    {{ bill.name }}
                                </h1>
                                <div
                                    class="mt-3 flex flex-col gap-1 text-xs text-[#666666] dark:text-[#999999] sm:flex-row sm:gap-6">
                                    <span class="inline-flex items-center gap-1.5">
                                        <User :stroke-width="1.5" class="w-3.5 h-3.5" />
                                        {{ bill.client }}
                                    </span>
                                    <span>Issued {{ issueDate }}</span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-2 sm:flex-shrink-0">
                                <a :href="route('bills-download', bill.id)" target="_blank"
                                    class="inline-flex items-center justify-center px-4 py-2 bg-black dark:bg-white text-white dark:text-black border-2 border-black dark:border-white rounded-full hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white transition-colors duration-200">
                                    <Download :stroke-width="1.5" class="w-4 h-4 mr-2" />
                                    <span class="text-xs tracking-wide">PDF</span>
                                </a>
                                <Link :href="route('bills-edit', bill.id)"
                                    class="inline-flex items-center justify-center px-4 py-2 border-2 border-black dark:border-white text-black dark:text-white bg-white dark:bg-black rounded-full hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-colors duration-200">
                                <Pencil :stroke-width="1.5" class="w-4 h-4 mr-2" />
                                <span class="text-xs tracking-wide">Edit</span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Line items -->
                    <div
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#CCCCCC] dark:border-[#222222] p-6">
                        <div class="flex items-center mb-6">
                            <FileText :stroke-width="1.5" class="w-5 h-5 text-black dark:text-white mr-2" />
                            <h2 class="text-sm font-semibold text-black dark:text-white">
                                Line items ({{ subBills.length }})
                            </h2>
                        </div>

                        <!-- Desktop table -->
                        <div
                            class="hidden sm:block rounded-lg overflow-x-auto border-2 border-[#E8E8E8] dark:border-[#222222]">
                            <table class="w-full bg-white dark:bg-[#111111]">
                                <thead class="bg-[#F5F5F5] dark:bg-black text-black dark:text-white">
                                    <tr class="text-xs tracking-wide">
                                        <th
                                            class="px-4 py-3 text-left border-b border-[#E8E8E8] dark:border-[#222222]">
                                            Description</th>
                                        <th
                                            class="px-4 py-3 text-right border-b border-[#E8E8E8] dark:border-[#222222]">
                                            Qty</th>
                                        <th
                                            class="px-4 py-3 text-right border-b border-[#E8E8E8] dark:border-[#222222]">
                                            Unit Price (BDT)</th>
                                        <th
                                            class="px-4 py-3 text-right border-b border-[#E8E8E8] dark:border-[#222222]">
                                            Amount (BDT)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in subBills" :key="row.id"
                                        class="border-t border-[#E8E8E8] dark:border-[#222222] text-sm hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] transition-colors">
                                        <td
                                            class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222] text-black dark:text-white break-words">
                                            {{ row.item }}</td>
                                        <td
                                            class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222] text-right tabular-nums text-[#666666] dark:text-[#999999]">
                                            {{ quantity(row.quantity) }}</td>
                                        <td
                                            class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222] text-right tabular-nums text-[#666666] dark:text-[#999999]">
                                            {{ money(row.unit_price) }}</td>
                                        <td
                                            class="px-4 py-3 border-b border-[#E8E8E8] dark:border-[#222222] text-right tabular-nums font-semibold text-black dark:text-white">
                                            {{ money(row.amount) }}</td>
                                    </tr>
                                    <tr v-if="!subBills.length">
                                        <td colspan="4"
                                            class="px-4 py-8 text-center text-xs text-[#666666] dark:text-[#999999] tracking-wide">
                                            No line items on this bill.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile cards -->
                        <div class="sm:hidden space-y-3">
                            <div v-for="row in subBills" :key="row.id"
                                class="p-3 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                                <p class="text-sm text-black dark:text-white break-words mb-2">{{ row.item }}</p>
                                <div class="flex justify-between text-xs text-[#666666] dark:text-[#999999]">
                                    <span class="tabular-nums">{{ quantity(row.quantity) }} × {{ money(row.unit_price)
                                        }}</span>
                                    <span class="tabular-nums font-semibold text-black dark:text-white">{{
                                        money(row.amount) }}</span>
                                </div>
                            </div>
                            <p v-if="!subBills.length"
                                class="py-6 text-center text-xs text-[#666666] dark:text-[#999999] tracking-wide">
                                No line items on this bill.
                            </p>
                        </div>

                        <!-- Total -->
                        <div class="mt-6 pt-4 border-t-2 border-[#E8E8E8] dark:border-[#222222]">
                            <div class="flex items-baseline justify-between gap-4">
                                <span class="text-xs uppercase tracking-wide text-[#666666] dark:text-[#999999]">
                                    Total Amount
                                </span>
                                <span class="text-xl font-semibold tabular-nums text-black dark:text-white">
                                    {{ money(bill.total_amount) }} <span class="text-xs">BDT</span>
                                </span>
                            </div>
                            <p v-if="amountInWords"
                                class="mt-2 text-xs text-[#666666] dark:text-[#999999] break-words">
                                In words: {{ amountInWords }}
                            </p>
                        </div>
                    </div>

                    <!-- Supporting documents -->
                    <div v-if="documents.length"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#CCCCCC] dark:border-[#222222] p-6">
                        <div class="flex items-center mb-6">
                            <FileText :stroke-width="1.5" class="w-5 h-5 text-black dark:text-white mr-2" />
                            <h2 class="text-sm font-semibold text-black dark:text-white">
                                Supporting documents ({{ documents.length }})
                            </h2>
                        </div>

                        <div class="space-y-2">
                            <div v-for="doc in documents" :key="doc.id"
                                class="flex items-center justify-between p-3 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                                <div class="flex items-center space-x-3 flex-1 min-w-0">
                                    <FileText :stroke-width="1.5"
                                        class="w-5 h-5 text-black dark:text-white flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-black dark:text-white truncate">{{ doc.filename }}</p>
                                        <p class="text-xs text-[#666666] dark:text-[#999999]">
                                            {{ formatFileSize(doc.file_size) }}
                                            <template v-if="doc.uploader"> · {{ doc.uploader.name }}</template>
                                        </p>
                                    </div>
                                </div>
                                <a :href="route('bills-document-download', { billId: bill.id, documentId: doc.id })"
                                    class="ml-3 p-1.5 text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black border-2 border-black dark:border-white rounded-full transition-colors duration-200 flex-shrink-0"
                                    title="Download">
                                    <Download :stroke-width="1.5" class="w-4 h-4" />
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Back -->
                    <div class="flex justify-end">
                        <Link :href="route('bills')"
                            class="inline-flex items-center justify-center px-6 py-3 border-2 border-[#D71921] text-[#D71921] bg-white dark:bg-black rounded-full hover:bg-[#D71921] hover:text-white dark:hover:bg-[#D71921] transition-colors duration-200">
                        <ArrowLeft :stroke-width="1.5" class="w-4 h-4 mr-2" />
                        <span class="text-xs tracking-wide">Back to Bills</span>
                        </Link>
                    </div>

                </div>
            </div>
        </div>
</template>
