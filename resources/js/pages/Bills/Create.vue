<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Plus, Trash2, Calculator, Receipt, User, FileText, Save, Upload, X, Calendar } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bills', href: '/bills' },
    { title: 'Add Bill', href: '/bills-create' },
];

const form = ref({
    name: '',
    client: '',
    bill_date: new Date().toISOString().split('T')[0], // Today's date in YYYY-MM-DD format
    sub_bills: [
        { item: '', quantity: 1, unit_price: 0, amount: 0 },
    ],
});

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
            customClass: { popup: 'rounded-lg' }
        });
        return;
    }
    form.value.sub_bills.splice(index, 1);
};

const calculateAmount = (index: number) => {
    const row = form.value.sub_bills[index];
    row.amount = row.quantity * row.unit_price;
};

const totalAmount = computed(() => {
    return form.value.sub_bills.reduce((sum, row) => sum + row.amount, 0);
});

const totalItems = computed(() => {
    return form.value.sub_bills.length;
});

const totalQuantity = computed(() => {
    return form.value.sub_bills.reduce((sum, row) => sum + row.quantity, 0);
});

const handleSubmit = async () => {
    // Validate form
    if (!form.value.name.trim()) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Please enter a bill name.',
            icon: 'error',
            customClass: { popup: 'rounded-lg' }
        });
        return;
    }

    if (!form.value.client.trim()) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Please enter a client name.',
            icon: 'error',
            customClass: { popup: 'rounded-lg' }
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
                customClass: { popup: 'rounded-lg' }
            });
            return;
        }
    }

    if (totalAmount.value <= 0) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Total amount must be greater than 0.',
            icon: 'error',
            customClass: { popup: 'rounded-lg' }
        });
        return;
    }

    isLoading.value = true;

    // Create FormData to handle file uploads
    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('client', form.value.client);
    formData.append('bill_date', form.value.bill_date);
    formData.append('total_amount', totalAmount.value.toString());

    // Append sub_bills
    form.value.sub_bills.forEach((sub, index) => {
        formData.append(`sub_bills[${index}][item]`, sub.item);
        formData.append(`sub_bills[${index}][quantity]`, sub.quantity.toString());
        formData.append(`sub_bills[${index}][unit_price]`, sub.unit_price.toString());
        formData.append(`sub_bills[${index}][amount]`, sub.amount.toString());
    });

    // Append documents
    documents.value.forEach((file, index) => {
        formData.append(`documents[${index}]`, file);
    });

    router.post(route('bills-create-post'), formData, {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Bill created successfully!',
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
                text: 'Failed to create bill. Please check your input.',
                icon: 'error',
                customClass: { popup: 'rounded-lg' }
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

const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
</script>

<template>

    <Head title="Create Bill" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black font-mono">
            <div class="p-4">
                <div class="max-w-6xl mx-auto space-y-6">
                    <!-- Bill Form -->
                    <form @submit.prevent="handleSubmit" class="space-y-2">
                        <!-- Basic Information -->
                        <div
                            class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4">
                            <div class="flex items-center mb-4">
                                <FileText class="w-4 h-4 mr-2 text-black dark:text-white" stroke-width="1.5" />
                                <h2 class="text-sm font-semibold font-mono text-black dark:text-white">Bill information</h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label
                                        class="block text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] mb-2">
                                        Bill Name <span class="text-[#D71921]">*</span>
                                    </label>
                                    <input v-model="form.name" type="text" placeholder="Enter bill name..."
                                        class="w-full px-3 py-2 border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg bg-white dark:bg-[#111111] text-black dark:text-white placeholder-[#999999] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                        required />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] mb-2">
                                        Client Name <span class="text-[#D71921]">*</span>
                                    </label>
                                    <div class="relative">
                                        <User
                                            class="absolute left-3 top-1/2 transform -translate-y-1/2 text-[#999999] w-4 h-4"
                                            stroke-width="1.5" />
                                        <input v-model="form.client" type="text" placeholder="Enter client name..."
                                            class="w-full pl-10 pr-3 py-2 border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg bg-white dark:bg-[#111111] text-black dark:text-white placeholder-[#999999] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                            required />
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999] mb-2">
                                        Bill Date <span class="text-[#D71921]">*</span>
                                    </label>
                                    <div class="relative">
                                        <Calendar
                                            class="absolute left-3 top-1/2 transform -translate-y-1/2 text-[#999999] w-4 h-4 pointer-events-none"
                                            stroke-width="1.5" />
                                        <input v-model="form.bill_date" type="date"
                                            class="w-full pl-10 pr-3 py-2 border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg bg-white dark:bg-[#111111] text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors [&::-webkit-calendar-picker-indicator]:dark:invert"
                                            required />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bill Statistics -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div
                                class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p
                                            class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999]">
                                            Total Items</p>
                                        <p class="text-lg font-mono font-bold tabular-nums text-black dark:text-white">{{ totalItems }}
                                        </p>
                                    </div>
                                    <div
                                        class="p-3 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                                        <FileText class="w-5 h-5" stroke-width="1.5" />
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p
                                            class="text-xs font-mono tracking-wide text-[#666666] dark:text-[#999999]">
                                            Total Quantity
                                        </p>
                                        <p class="text-lg font-mono font-bold tabular-nums text-black dark:text-white">{{
                                            totalQuantity }}</p>
                                    </div>
                                    <div
                                        class="p-3 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                                        <Calculator class="w-5 h-5" stroke-width="1.5" />
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-black dark:bg-white rounded-lg border-2 border-black dark:border-white p-4 text-white dark:text-black">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-mono tracking-wide opacity-80">Total Amount
                                        </p>
                                        <p class="text-lg font-mono font-bold tabular-nums text-white dark:text-black">{{ formatCurrency(totalAmount) }}</p>
                                    </div>
                                    <div class="p-3 bg-white/20 dark:bg-black/20 rounded-lg">
                                        <Receipt class="w-5 h-5" stroke-width="1.5" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bill items -->
                        <div
                            class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] overflow-hidden">
                            <div class="p-4 border-b-2 border-[#E8E8E8] dark:border-[#222222]">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <Calculator class="w-4 h-4 mr-2 text-black dark:text-white" stroke-width="1.5" />
                                        <h2
                                            class="text-sm font-semibold font-mono text-black dark:text-white">
                                            Bill items</h2>
                                    </div>
                                    <button type="button" @click="addRow"
                                        class="inline-flex items-center px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-full hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white transition-colors font-mono tracking-wide text-xs group">
                                        <Plus
                                            class="w-4 h-4 mr-2 group-hover:rotate-90 transition-transform duration-200"
                                            stroke-width="1.5" />
                                        Add Item
                                    </button>
                                </div>
                            </div>

                            <!-- Desktop Table -->
                            <div class="hidden md:block overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-[#F5F5F5] dark:bg-black">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-[10px] font-mono tracking-widest uppercase text-[#666666] dark:text-[#999999] border-b border-[#E8E8E8] dark:border-[#222222]">
                                                Item Description
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center text-[10px] font-mono tracking-widest uppercase text-[#666666] dark:text-[#999999] border-b border-[#E8E8E8] dark:border-[#222222]">
                                                Quantity
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center text-[10px] font-mono tracking-widest uppercase text-[#666666] dark:text-[#999999] border-b border-[#E8E8E8] dark:border-[#222222]">
                                                Unit Price
                                            </th>
                                            <th
                                                class="px-4 py-3 text-right text-[10px] font-mono tracking-widest uppercase text-[#666666] dark:text-[#999999] border-b border-[#E8E8E8] dark:border-[#222222]">
                                                Amount
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center text-[10px] font-mono tracking-widest uppercase text-[#666666] dark:text-[#999999] border-b border-[#E8E8E8] dark:border-[#222222]">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#E8E8E8] dark:divide-[#222222]">
                                        <tr v-for="(row, index) in form.sub_bills" :key="index"
                                            class="hover:bg-[#F5F5F5] dark:hover:bg-black/40 transition-colors">
                                            <td class="px-4 py-3">
                                                <input v-model="row.item" type="text"
                                                    placeholder="Enter item description..."
                                                    class="w-full px-3 py-2 border border-[#CCCCCC] dark:border-[#333333] rounded-lg bg-white dark:bg-black text-sm text-black dark:text-white placeholder-[#999999] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                                    required />
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <input v-model.number="row.quantity" @input="calculateAmount(index)"
                                                    type="number" min="1"
                                                    class="w-20 px-3 py-2 text-center border border-[#CCCCCC] dark:border-[#333333] rounded-lg bg-white dark:bg-black text-sm text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                                    required />
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <input v-model.number="row.unit_price" @input="calculateAmount(index)"
                                                    type="number" step="0.01" min="0" placeholder="0.00"
                                                    class="w-24 px-3 py-2 text-center border border-[#CCCCCC] dark:border-[#333333] rounded-lg bg-white dark:bg-black text-sm text-black dark:text-white placeholder-[#999999] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                                    required />
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <span
                                                    class="font-mono tabular-nums text-sm font-semibold text-black dark:text-white">
                                                    {{ formatCurrency(row.amount) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button type="button" @click="removeRow(index)"
                                                    class="p-2 text-[#D71921] hover:bg-[#D71921] hover:text-white rounded-full border border-[#D71921] transition-all"
                                                    :class="{ 'opacity-50 cursor-not-allowed': form.sub_bills.length === 1 }">
                                                    <Trash2 class="w-4 h-4" stroke-width="1.5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Cards -->
                            <div class="md:hidden p-4 space-y-4">
                                <div v-for="(row, index) in form.sub_bills" :key="index"
                                    class="border border-[#E8E8E8] dark:border-[#222222] rounded-lg p-4 space-y-4 bg-white dark:bg-[#0A0A0A]">
                                    <div class="flex items-center justify-between">
                                        <h3
                                            class="text-sm font-semibold font-mono text-black dark:text-white">
                                            Item #{{ index + 1 }}</h3>
                                        <button type="button" @click="removeRow(index)"
                                            class="p-1.5 text-[#D71921] hover:bg-[#D71921] hover:text-white rounded-full border border-[#D71921] transition-all"
                                            :class="{ 'opacity-50 cursor-not-allowed': form.sub_bills.length === 1 }">
                                            <Trash2 class="w-4 h-4" stroke-width="1.5" />
                                        </button>
                                    </div>

                                    <div class="space-y-3">
                                        <div>
                                            <label
                                                class="block text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">Item
                                                Description</label>
                                            <input v-model="row.item" type="text"
                                                placeholder="Enter item description..."
                                                class="w-full px-3 py-2 border border-[#CCCCCC] dark:border-[#333333] rounded-lg bg-white dark:bg-black text-sm text-black dark:text-white placeholder-[#999999] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                                required />
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label
                                                    class="block text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">Quantity</label>
                                                <input v-model.number="row.quantity" @input="calculateAmount(index)"
                                                    type="number" min="1"
                                                    class="w-full px-3 py-2 border border-[#CCCCCC] dark:border-[#333333] rounded-lg bg-white dark:bg-black text-sm text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                                    required />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">Unit
                                                    Price</label>
                                                <input v-model.number="row.unit_price" @input="calculateAmount(index)"
                                                    type="number" step="0.01" min="0" placeholder="0.00"
                                                    class="w-full px-3 py-2 border border-[#CCCCCC] dark:border-[#333333] rounded-lg bg-white dark:bg-black text-sm text-black dark:text-white placeholder-[#999999] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                                                    required />
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <span
                                                class="text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">Amount:
                                            </span>
                                            <span
                                                class="font-mono tabular-nums text-sm font-semibold text-black dark:text-white">
                                                {{ formatCurrency(row.amount) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Summary -->
                        <div
                            class="bg-black dark:bg-white rounded-lg border-2 border-black dark:border-white p-4">
                            <div class="flex justify-between items-center">
                                <div
                                    class="text-xs font-mono uppercase tracking-widest text-[#CCCCCC] dark:text-[#666666]">
                                    Grand Total
                                </div>
                                <div
                                    class="text-lg font-mono tabular-nums font-bold text-white dark:text-black">
                                    {{ formatCurrency(totalAmount) }}
                                </div>
                            </div>
                        </div>

                        <!-- Document Upload Section -->
                        <div
                            class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4">
                            <div class="flex items-center mb-4">
                                <Upload class="w-4 h-4 mr-2 text-black dark:text-white" stroke-width="1.5" />
                                <h2 class="text-sm font-semibold font-mono text-black dark:text-white">
                                    Supporting documents
                                </h2>
                                <span
                                    class="ml-2 text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">(Optional)</span>
                            </div>

                            <div class="space-y-4">
                                <!-- File Upload Area -->
                                <div class="flex items-center justify-center w-full">
                                    <label
                                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-[#CCCCCC] dark:border-[#333333] border-dashed rounded-lg cursor-pointer bg-[#F5F5F5] dark:bg-[#0A0A0A] hover:border-black dark:hover:border-white transition-colors">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <Upload class="w-6 h-6 mb-2 text-[#666666] dark:text-[#999999]"
                                                stroke-width="1.5" />
                                            <p
                                                class="mb-1 text-xs font-mono text-[#666666] dark:text-[#999999]">
                                                <span class="font-semibold uppercase tracking-wider text-black dark:text-white">Click
                                                    to upload</span> or drag and drop
                                            </p>
                                            <p
                                                class="text-[10px] font-mono uppercase tracking-widest text-[#999999]">
                                                PDF, DOC, DOCX, XLS, XLSX, JPG, PNG · Max 10MB
                                            </p>
                                        </div>
                                        <input type="file" class="hidden" @change="handleFileUpload"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" multiple />
                                    </label>
                                </div>

                                <!-- Uploaded Files List -->
                                <div v-if="documents.length > 0" class="space-y-2">
                                    <p
                                        class="text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">
                                        Uploaded Files ({{ documents.length }})</p>
                                    <div class="space-y-2">
                                        <div v-for="(file, index) in documents" :key="index"
                                            class="flex items-center justify-between p-3 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border border-[#E8E8E8] dark:border-[#222222]">
                                            <div class="flex items-center space-x-3 flex-1 min-w-0">
                                                <FileText class="w-4 h-4 text-black dark:text-white flex-shrink-0"
                                                    stroke-width="1.5" />
                                                <div class="flex-1 min-w-0">
                                                    <p
                                                        class="text-sm text-black dark:text-white truncate">
                                                        {{ file.name }}
                                                    </p>
                                                    <p
                                                        class="text-[10px] font-mono text-[#666666] dark:text-[#999999] tabular-nums">
                                                        {{ formatFileSize(file.size) }}
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button" @click="removeDocument(index)"
                                                class="ml-3 p-1.5 text-[#D71921] hover:bg-[#D71921] hover:text-white rounded-full border border-[#D71921] transition-all flex-shrink-0">
                                                <X class="w-4 h-4" stroke-width="1.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
                            <Link :href="route('bills')"
                                class="inline-flex items-center justify-center px-6 py-2 rounded-full border border-[#CCCCCC] dark:border-[#333333] text-xs font-mono tracking-wide text-[#1A1A1A] dark:text-[#E8E8E8] bg-white dark:bg-[#111111] hover:border-black dark:hover:border-white transition-colors">
                                Cancel
                            </Link>
                            <button type="submit" :disabled="isLoading || totalAmount <= 0"
                                class="inline-flex items-center justify-center px-6 py-2 rounded-full border-2 border-black dark:border-white bg-black dark:bg-white text-white dark:text-black text-xs font-mono tracking-wide hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <div v-if="isLoading"
                                    class="animate-spin rounded-full h-4 w-4 border-2 border-current border-t-transparent mr-2">
                                </div>
                                <Save v-else class="w-4 h-4 mr-2" stroke-width="1.5" />
                                {{ isLoading ? 'Saving...' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

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