<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const form = ref({
    name: '',
    client: '',
    sub_bills: [
        { item: '', quantity: 1, unit_price: 0, amount: 0 },
    ],
});

const addRow = () => {
    form.value.sub_bills.push({ item: '', quantity: 1, unit_price: 0, amount: 0 });
};

const removeRow = (index: number) => {
    form.value.sub_bills.splice(index, 1);
};

const calculateAmount = (index: number) => {
    const row = form.value.sub_bills[index];
    row.amount = row.quantity * row.unit_price;
};

const totalAmount = computed(() => {
    return form.value.sub_bills.reduce((sum, row) => sum + row.amount, 0);
});

const handleSubmit = () => {
    const payload = {
        ...form.value,
        total_amount: totalAmount.value,
    };

    router.post(route('bills-create-post'), payload);
};
</script>

<template>
    <Head title="Create Bill" />
    <AppLayout :breadcrumbs="[{ title: 'Add Bills', href: '/bills-create' }]">
        <div class="p-6 max-w-4xl mx-auto">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <div>
                    <label class="block font-medium text-sm">Name</label>
                    <input v-model="form.name" type="text" class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
                </div>
                <div>
                    <label class="block font-medium text-sm">Client</label>
                    <input v-model="form.client" type="text" class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
                </div>

                <!-- Sub-Bills Table -->
                <div>
                    <table class="w-full border text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            <tr class="text-left">
                                <th class="px-2 py-1">Item</th>
                                <th class="px-2 py-1">Qty</th>
                                <th class="px-2 py-1">Unit Price</th>
                                <th class="px-2 py-1">Amount</th>
                                <th class="px-2 py-1">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in form.sub_bills" :key="index" class="border-t">
                                <td class="px-2 py-1"><input v-model="row.item" type="text" class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" required /></td>
                                <td class="px-2 py-1"><input v-model.number="row.quantity" @input="calculateAmount(index)" type="number" min="1" class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" required /></td>
                                <td class="px-2 py-1"><input v-model.number="row.unit_price" @input="calculateAmount(index)" type="number" step="0.01" class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" required /></td>
                                <td class="px-2 py-1 text-right">{{ row.amount.toFixed(2) }}</td>
                                <td class="px-2 py-1 text-center">
                                    <button type="button" @click="removeRow(index)" class="text-red-600 hover:underline" v-if="form.sub_bills.length > 1">Remove</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" @click="addRow" class="mt-2 text-sm text-blue-600 hover:underline">+ Add</button>
                </div>

                <div class="text-right font-semibold">
                    Total: {{ totalAmount.toFixed(2) }}
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Save</button>
                    <a href="/bills" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400 dark:bg-gray-600 dark:text-white">Cancel</a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>