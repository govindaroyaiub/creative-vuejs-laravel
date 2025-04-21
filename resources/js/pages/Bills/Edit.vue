<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

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

const addRow = () => {
    form.value.sub_bills.push({ item: '', quantity: 1, unit_price: 0, amount: 0 });
};

const removeRow = (index: number) => {
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

const handleSubmit = () => {
    const payload = {
        ...form.value,
        total_amount: totalAmount.value,
    };

    router.put(route('bills-update', bill.id), payload);
};
</script>

<template>
    <Head title="Edit Bill" />
    <AppLayout :breadcrumbs="[{ title: 'Bills Edit', href: '/bills-edit' }]">
        <div class="mx-auto max-w-4xl p-6">
            <!-- Flash Message -->
            <transition name="fade">
                <div v-if="showFlash" class="relative mb-4 rounded-md bg-green-500 p-3 pr-10 text-white">
                    {{ flashMessage }}
                    <button @click="showFlash = false" class="absolute right-2 top-2 text-white hover:text-gray-200">
                        <X class="inline h-5 w-5" />
                    </button>
                </div>
            </transition>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium">Name</label>
                    <input v-model="form.name" type="text" class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
                </div>

                <!-- Client -->
                <div>
                    <label class="block text-sm font-medium">Client</label>
                    <input v-model="form.client" type="text" class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
                </div>

                <!-- Sub-Bills Table -->
                <div>
                    <label class="mb-2 block text-sm font-medium">Sub-Bills</label>
                    <table class="w-full border text-sm">
                        <thead class="bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
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
                                <td class="px-2 py-1">
                                    <input v-model="row.item" type="text" class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" required />
                                </td>
                                <td class="px-2 py-1">
                                    <input v-model.number="row.quantity" @input="calculateAmount(index)" type="number" min="1" class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" required />
                                </td>
                                <td class="px-2 py-1">
                                    <input v-model.number="row.unit_price" @input="calculateAmount(index)" type="number" step="0.01" class="w-full rounded border px-2 py-1 dark:bg-gray-700 dark:text-white" required />
                                </td>
                                <td class="px-2 py-1 text-right">
                                    {{ Number(row.amount || 0).toFixed(2) }}
                                </td>
                                <td class="px-2 py-1 text-center">
                                    <button type="button" @click="removeRow(index)" class="text-red-600 hover:underline" v-if="form.sub_bills.length > 1">Remove</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" @click="addRow" class="mt-2 text-sm text-blue-600 hover:underline">+ Add</button>
                </div>

                <!-- Total -->
                <div class="text-right font-semibold">{{ Number(totalAmount || 0).toFixed(2) }}</div>

                <!-- Submit -->
                <div class="flex justify-end space-x-4">
                    <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Update</button>
                    <a href="/bills" class="rounded bg-gray-300 px-4 py-2 hover:bg-gray-400 dark:bg-gray-600 dark:text-white">Cancel</a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>