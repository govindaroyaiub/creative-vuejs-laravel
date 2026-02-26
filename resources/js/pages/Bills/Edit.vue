<template>

    <Head title="Edit Bill" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-black dark:via-gray-950 dark:to-black">
            <div class="p-4">
                <div class="max-w-6xl mx-auto space-y-6">
                    <!-- Bill Form -->
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Basic Information -->
                        <div
                            class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-6">
                            <div class="flex items-center mb-6">
                                <FileText class="w-5 h-5 text-blue-600 mr-2" />
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Bill Information
                                </h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Bill Name <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.name" type="text" placeholder="Enter bill name..."
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                        required />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Client Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <User
                                            class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                        <input v-model="form.client" type="text" placeholder="Enter client name..."
                                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                            required />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bill Statistics -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div
                                class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Items</p>
                                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ totalItems }}
                                        </p>
                                    </div>
                                    <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
                                        <FileText class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Quantity
                                        </p>
                                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{
                                            totalQuantity }}</p>
                                    </div>
                                    <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
                                        <Calculator class="w-5 h-5 text-green-600 dark:text-green-400" />
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-gradient-to-r from-purple-600 to-purple-700 rounded-2xl shadow-sm p-6 text-white">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-purple-100">Total Amount</p>
                                        <p class="text-2xl font-bold">{{ formatCurrency(totalAmount) }}</p>
                                    </div>
                                    <div class="p-3 bg-white/20 rounded-xl">
                                        <Receipt class="w-5 h-5 text-white" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bill Items -->
                        <div
                            class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 overflow-hidden">
                            <div class="p-6 border-b border-gray-200 dark:border-neutral-700">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <Calculator class="w-5 h-5 text-purple-600 mr-2" />
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Bill Items</h2>
                                    </div>
                                    <button type="button" @click="addRow"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors duration-200 group">
                                        <Plus
                                            class="w-4 h-4 mr-2 group-hover:rotate-90 transition-transform duration-200" />
                                        Add Item
                                    </button>
                                </div>
                            </div>

                            <!-- Desktop Table -->
                            <div class="hidden md:block overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 dark:bg-neutral-900">
                                        <tr>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Item Description
                                            </th>
                                            <th
                                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Quantity
                                            </th>
                                            <th
                                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Unit Price
                                            </th>
                                            <th
                                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Amount
                                            </th>
                                            <th
                                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                        <tr v-for="(row, index) in form.sub_bills" :key="row.id || index"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-950 transition-colors duration-200">
                                            <td class="px-6 py-4">
                                                <input v-model="row.item" type="text"
                                                    placeholder="Enter item description..."
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                    required />
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <input v-model.number="row.quantity" @input="calculateAmount(index)"
                                                    type="number" min="1"
                                                    class="w-20 px-3 py-2 text-center border border-gray-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                    required />
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <input v-model.number="row.unit_price" @input="calculateAmount(index)"
                                                    type="number" step="0.01" min="0" placeholder="0.00"
                                                    class="w-24 px-3 py-2 text-center border border-gray-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                    required />
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <span
                                                    class="font-mono font-semibold text-lg text-gray-900 dark:text-white">
                                                    {{ formatCurrency(row.amount) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <button type="button" @click="removeRow(index)"
                                                    class="p-2 text-red-600 hover:text-red-800 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-lg transition-all duration-200 group/delete"
                                                    :class="{ 'opacity-50 cursor-not-allowed': form.sub_bills.length === 1 }">
                                                    <Trash2
                                                        class="w-4 h-4 group-hover/delete:scale-110 transition-transform duration-200" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Cards -->
                            <div class="md:hidden p-4 space-y-4">
                                <div v-for="(row, index) in form.sub_bills" :key="row.id || index"
                                    class="border border-gray-200 dark:border-neutral-700 rounded-xl p-4 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-medium text-gray-900 dark:text-white">Item #{{ index + 1 }}</h3>
                                        <button type="button" @click="removeRow(index)"
                                            class="p-1 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/50 rounded"
                                            :class="{ 'opacity-50 cursor-not-allowed': form.sub_bills.length === 1 }">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>

                                    <div class="space-y-3">
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Item
                                                Description</label>
                                            <input v-model="row.item" type="text"
                                                placeholder="Enter item description..."
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                required />
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                                                <input v-model.number="row.quantity" @input="calculateAmount(index)"
                                                    type="number" min="1"
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                    required />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit
                                                    Price</label>
                                                <input v-model.number="row.unit_price" @input="calculateAmount(index)"
                                                    type="number" step="0.01" min="0" placeholder="0.00"
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                    required />
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Amount: </span>
                                            <span class="font-mono font-semibold text-lg text-gray-900 dark:text-white">
                                                {{ formatCurrency(row.amount) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Summary -->
                        <div
                            class="bg-gradient-to-r from-gray-50 to-blue-50 dark:from-neutral-950 dark:to-neutral-950 rounded-2xl p-6 border border-gray-200 dark:border-neutral-700">
                            <div class="flex justify-between items-center">
                                <div class="text-lg font-medium text-gray-700 dark:text-gray-300">
                                    Grand Total:
                                </div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                                    {{ formatCurrency(totalAmount) }}
                                </div>
                            </div>
                        </div>

                        <!-- Document Management Section -->
                        <div
                            class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-6">
                            <div class="flex items-center mb-4">
                                <Upload class="w-5 h-5 text-blue-600 mr-2" />
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Supporting Documents
                                </h2>
                                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">(Optional)</span>
                            </div>

                            <div class="space-y-4">
                                <!-- Existing Documents -->
                                <div v-if="existingDocuments.length > 0" class="space-y-2">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Existing Documents
                                        ({{ existingDocuments.length }})</p>
                                    <div class="space-y-2">
                                        <div v-for="doc in existingDocuments" :key="doc.id"
                                            class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                            <div class="flex items-center space-x-3 flex-1 min-w-0">
                                                <FileText class="w-5 h-5 text-blue-600 flex-shrink-0" />
                                                <div class="flex-1 min-w-0">
                                                    <p
                                                        class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                        {{ doc.filename }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ formatFileSize(doc.file_size) }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2 flex-shrink-0">
                                                <a :href="route('bills-document-download', { billId: bill.id, documentId: doc.id })"
                                                    class="p-1.5 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200"
                                                    title="Download">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                                        <polyline points="7 10 12 15 17 10" />
                                                        <line x1="12" y1="15" x2="12" y2="3" />
                                                    </svg>
                                                </a>
                                                <button type="button" @click="deleteExistingDocument(doc.id)"
                                                    class="p-1.5 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-lg transition-all duration-200"
                                                    title="Delete">
                                                    <X class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- File Upload Area -->
                                <div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Add New
                                        Documents</p>
                                    <div class="flex items-center justify-center w-full">
                                        <label
                                            class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 dark:border-neutral-700 border-dashed rounded-xl cursor-pointer bg-gray-50 dark:bg-neutral-900 hover:bg-gray-100 dark:hover:bg-neutral-800 transition-colors duration-200">
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <Upload class="w-8 h-8 mb-2 text-gray-500 dark:text-gray-400" />
                                                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                                    <span class="font-semibold">Click to upload</span> or drag and drop
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (Max 10MB per file)
                                                </p>
                                            </div>
                                            <input type="file" class="hidden" @change="handleFileUpload"
                                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" multiple />
                                        </label>
                                    </div>
                                </div>

                                <!-- New Files to Upload List -->
                                <div v-if="documents.length > 0" class="space-y-2">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">New Files to Upload
                                        ({{ documents.length }})</p>
                                    <div class="space-y-2">
                                        <div v-for="(file, index) in documents" :key="index"
                                            class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                            <div class="flex items-center space-x-3 flex-1 min-w-0">
                                                <FileText class="w-5 h-5 text-green-600 flex-shrink-0" />
                                                <div class="flex-1 min-w-0">
                                                    <p
                                                        class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                        {{ file.name }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ formatFileSize(file.size) }}
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button" @click="removeDocument(index)"
                                                class="ml-3 p-1.5 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-lg transition-all duration-200 flex-shrink-0">
                                                <X class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4">
                            <Link :href="route('bills')"
                                class="inline-flex items-center justify-center px-6 py-3 border text-white bg-red-600 dark:bg-neutral-800 rounded-xl hover:bg-red-700 transition-all duration-200">
                                <ArrowLeft class="w-4 h-4 mr-2" />
                                Back to Bills
                            </Link>
                            <button type="submit" :disabled="isLoading || totalAmount <= 0"
                                class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-xl hover:from-indigo-700 hover:to-indigo-800 transition-all duration-200 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                                <div v-if="isLoading"
                                    class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2">
                                </div>
                                <Save v-else class="w-4 h-4 mr-2" />
                                {{ isLoading ? 'Updating...' : 'Update' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Plus, Trash2, Calculator, Receipt, User, FileText, Save, ArrowLeft, Upload, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bills', href: '/bills' },
    { title: 'Edit Bill', href: '/bills-edit' },
];

const page = usePage();
const bill = page.props.bill;

// ✅ Properly extract only the 'success' message
const flashMessage = computed(() => page.props.flash?.success || '');
const showFlash = ref(false);

// ✅ Make it reactive if message appears after page load
watch(flashMessage, (val) => {
    showFlash.value = !!val;
});

// Form
const form = ref({
    name: bill.name,
    client: bill.client,
    sub_bills: bill.sub_bills.map((sb: any) => {
        const quantity = Number(sb.quantity) || 0;
        const unit_price = Number(sb.unit_price) || 0;
        return {
            id: sb.id,
            item: sb.item,
            quantity,
            unit_price,
            amount: quantity * unit_price,
        };
    }),
});

const existingDocuments = ref(bill.documents || []);
const documents = ref<File[]>([]);
const isLoading = ref(false);

const addRow = () => {
    form.value.sub_bills.push({ item: '', quantity: 1, unit_price: 0, amount: 0 });
};

const removeRow = (index: number) => {
    if (form.value.sub_bills.length === 1) {
        Swal.fire({
            title: 'Cannot Remove',
            text: 'At least one item is required.',
            icon: 'warning',
            customClass: { popup: 'rounded-2xl' }
        });
        return;
    }
    form.value.sub_bills.splice(index, 1);
};

const calculateAmount = (index: number) => {
    const row = form.value.sub_bills[index];
    row.quantity = Number(row.quantity) || 0;
    row.unit_price = Number(row.unit_price) || 0;
    row.amount = row.quantity * row.unit_price;
};

const totalAmount = computed(() => {
    return form.value.sub_bills.reduce((sum, row) => {
        const current = Number(row.amount) || 0;
        return sum + current;
    }, 0);
});

const totalItems = computed(() => {
    return form.value.sub_bills.length;
});

const totalQuantity = computed(() => {
    return form.value.sub_bills.reduce((sum, row) => sum + (Number(row.quantity) || 0), 0);
});

const handleSubmit = async () => {
    // Validate form
    if (!form.value.name.trim()) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Please enter a bill name.',
            icon: 'error',
            customClass: { popup: 'rounded-2xl' }
        });
        return;
    }

    if (!form.value.client.trim()) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Please enter a client name.',
            icon: 'error',
            customClass: { popup: 'rounded-2xl' }
        });
        return;
    }

    // Check if all items are filled
    for (let i = 0; i < form.value.sub_bills.length; i++) {
        const row = form.value.sub_bills[i];
        if (!row.item.trim()) {
            Swal.fire({
                title: 'Validation Error',
                text: `Please enter an item name for row ${i + 1}.`,
                icon: 'error',
                customClass: { popup: 'rounded-2xl' }
            });
            return;
        }
    }

    if (totalAmount.value <= 0) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Total amount must be greater than 0.',
            icon: 'error',
            customClass: { popup: 'rounded-2xl' }
        });
        return;
    }

    isLoading.value = true;

    // Create FormData to handle file uploads
    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('client', form.value.client);
    formData.append('total_amount', totalAmount.value.toString());
    formData.append('_method', 'PUT'); // Laravel method spoofing

    // Append sub_bills
    form.value.sub_bills.forEach((sub, index) => {
        formData.append(`sub_bills[${index}][item]`, sub.item);
        formData.append(`sub_bills[${index}][quantity]`, sub.quantity.toString());
        formData.append(`sub_bills[${index}][unit_price]`, sub.unit_price.toString());
        formData.append(`sub_bills[${index}][amount]`, sub.amount.toString());
    });

    // Append new documents
    documents.value.forEach((file, index) => {
        formData.append(`documents[${index}]`, file);
    });

    router.post(route('bills-update', bill.id), formData, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Bill updated successfully!',
                timer: 1000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                timerProgressBar: true,
            });
        },
        onError: (errors) => {
            console.log(errors);
            Swal.fire({
                title: 'Error!',
                text: 'Failed to update bill. Please check your input.',
                icon: 'error',
                customClass: { popup: 'rounded-2xl' }
            });
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'BDT',
        currencyDisplay: 'code'
    }).format(amount).replace('BDT', '৳');
};

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const newFiles = Array.from(target.files);
        documents.value.push(...newFiles);
        target.value = ''; // Reset input
    }
};

const removeDocument = (index: number) => {
    documents.value.splice(index, 1);
};

const deleteExistingDocument = (documentId: number) => {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This document will be permanently deleted!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        customClass: { popup: 'rounded-2xl' }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('bills-document-delete', { billId: bill.id, documentId }), {
                preserveScroll: true,
                onSuccess: () => {
                    existingDocuments.value = existingDocuments.value.filter((doc: any) => doc.id !== documentId);
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Document deleted successfully!',
                        timer: 1500,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end',
                    });
                },
                onError: () => {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to delete document.',
                        icon: 'error',
                        customClass: { popup: 'rounded-2xl' }
                    });
                }
            });
        }
    });
};

const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
</script>

<style scoped>
/* Hide number input arrows */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"] {
    -moz-appearance: textfield;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>