<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Plus, Trash2, Calculator, Receipt, User, FileText, Save, ArrowLeft } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bills', href: '/bills' },
    { title: 'Add Bill', href: '/bills-create' },
];

const form = ref({
    name: '',
    client: '',
    sub_bills: [
        { item: '', quantity: 1, unit_price: 0, amount: 0 },
    ],
});

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

    const payload = {
        ...form.value,
        total_amount: totalAmount.value,
    };

    router.post(route('bills-create-post'), payload, {
        onSuccess: () => {
            Swal.fire({
                title: 'Success!',
                text: 'Bill created successfully.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                customClass: { popup: 'rounded-2xl' }
            });
        },
        onError: (errors) => {
            console.log(errors);
            Swal.fire({
                title: 'Error!',
                text: 'Failed to create bill. Please check your input.',
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
</script>

<template>

    <Head title="Create Bill" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-black dark:via-gray-950 dark:to-black">
            <div class="p-6">
                <div class="max-w-6xl mx-auto space-y-6">
                    <!-- Bill Form -->
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Basic Information -->
                        <div
                            class="bg-white dark:bg-black rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                            <div class="flex items-center mb-6">
                                <FileText class="w-5 h-5 text-blue-600 mr-2" />
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Bill Information</h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        Bill Name <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.name" type="text" placeholder="Enter bill name..."
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-black text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
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
                                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-black text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                            required />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bill Statistics -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div
                                class="bg-white dark:bg-black rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
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
                                class="bg-white dark:bg-black rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
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
                            class="bg-white dark:bg-black rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                            <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <Calculator class="w-5 h-5 text-purple-600 mr-2" />
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Bill Items</h2>
                                    </div>
                                    <button type="button" @click="addRow"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 group">
                                        <Plus
                                            class="w-4 h-4 mr-2 group-hover:rotate-90 transition-transform duration-200" />
                                        Add Item
                                    </button>
                                </div>
                            </div>

                            <!-- Desktop Table -->
                            <div class="hidden md:block overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 dark:bg-gray-950">
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
                                        <tr v-for="(row, index) in form.sub_bills" :key="index"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-950 transition-colors duration-200">
                                            <td class="px-6 py-4">
                                                <input v-model="row.item" type="text"
                                                    placeholder="Enter item description..."
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-black text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                    required />
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <input v-model.number="row.quantity" @input="calculateAmount(index)"
                                                    type="number" min="1"
                                                    class="w-20 px-3 py-2 text-center border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-black text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                    required />
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <input v-model.number="row.unit_price" @input="calculateAmount(index)"
                                                    type="number" step="0.01" min="0" placeholder="0.00"
                                                    class="w-24 px-3 py-2 text-center border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-black text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
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
                            <div class="md:hidden p-6 space-y-4">
                                <div v-for="(row, index) in form.sub_bills" :key="index"
                                    class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 space-y-4">
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
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-black text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                required />
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                                                <input v-model.number="row.quantity" @input="calculateAmount(index)"
                                                    type="number" min="1"
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-black text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                    required />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit
                                                    Price</label>
                                                <input v-model.number="row.unit_price" @input="calculateAmount(index)"
                                                    type="number" step="0.01" min="0" placeholder="0.00"
                                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-black text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
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
                            class="bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-950 dark:to-black rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
                            <div class="flex justify-between items-center">
                                <div class="text-lg font-medium text-gray-700 dark:text-gray-300">
                                    Grand Total:
                                </div>
                                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                                    {{ formatCurrency(totalAmount) }}
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4">
                            <Link :href="route('bills')"
                                class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 bg-white dark:bg-black rounded-xl hover:bg-gray-50 dark:hover:bg-gray-950 transition-all duration-200">
                            Cancel
                            </Link>
                            <button type="submit" :disabled="isLoading || totalAmount <= 0"
                                class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                                <div v-if="isLoading"
                                    class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2">
                                </div>
                                <Save v-else class="w-4 h-4 mr-2" />
                                {{ isLoading ? 'Creating...' : 'Create Bill' }}
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