<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Upload, Send, X } from 'lucide-vue-next';
import { ref } from 'vue';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Support Tickets', href: '/support-tickets' },
    { title: 'Create Ticket', href: '/support-tickets/create' },
];

const form = useForm({
    name: '',
    description: '',
    image: null as File | null,
    priority: 'medium' as 'low' | 'medium' | 'high' | 'urgent',
});

const imagePreview = ref<string | null>(null);

const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        // Check file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            Swal.fire('Error', 'Image size must be less than 10MB', 'error');
            return;
        }

        form.image = file;

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
};

const submitTicket = () => {
    if (!form.name.trim() || !form.description.trim()) {
        Swal.fire('Error', 'Please fill in all required fields', 'error');
        return;
    }

    form.post(route('support-tickets.store'), {
        onSuccess: () => {
            Swal.fire({
                title: 'Success!',
                text: 'Your support ticket has been created successfully.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                // Close this tab and focus back on the main window
                window.close();
            });
        },
        onError: (errors) => {
            Swal.fire('Error', 'Failed to create ticket. Please try again.', 'error');
            console.error(errors);
        },
    });
};
</script>

<template>

    <Head title="Create Support Ticket" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 sm:p-6 max-w-4xl mx-auto">
                <div
                    class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-6 sm:p-8">
                    <div class="mb-6">
                        <h1
                            class="text-2xl sm:text-3xl font-bold text-black dark:text-white uppercase font-mono tracking-wider mb-2">
                            Create Support Ticket
                        </h1>
                        <p class="text-sm text-[#666666] dark:text-[#999999]">
                            Describe the issue you're facing and we'll help you resolve it as soon as possible.
                        </p>
                    </div>

                    <form @submit.prevent="submitTicket" class="space-y-6">
                        <!-- Ticket Name -->
                        <div>
                            <label for="name"
                                class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white mb-2">
                                Issue Title <span class="text-[#D71921]">*</span>
                            </label>
                            <input id="name" v-model="form.name" type="text" required
                                placeholder="Brief description of the issue"
                                class="w-full px-4 py-3 border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg 
                                       bg-white dark:bg-[#111111] text-black dark:text-white 
                                       focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                        </div>

                        <!-- Priority -->
                        <div>
                            <label for="priority"
                                class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white mb-2">
                                Priority
                            </label>
                            <select id="priority" v-model="form.priority"
                                class="w-full px-4 py-3 border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg 
                                       bg-white dark:bg-[#111111] text-black dark:text-white 
                                       focus:border-black dark:focus:border-white focus:outline-none transition-colors">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            <p class="mt-1 text-xs text-[#666666] dark:text-[#999999]">
                                Select the urgency level of your issue
                            </p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description"
                                class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white mb-2">
                                Description <span class="text-[#D71921]">*</span>
                            </label>
                            <textarea id="description" v-model="form.description" required rows="8"
                                placeholder="Provide detailed information about the issue you're experiencing..."
                                class="w-full px-4 py-3 border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg 
                                       bg-white dark:bg-[#111111] text-black dark:text-white 
                                       focus:border-black dark:focus:border-white focus:outline-none transition-colors resize-none"></textarea>
                            <p class="mt-1 text-xs text-[#666666] dark:text-[#999999]">
                                Include steps to reproduce, error messages, or any relevant information
                            </p>
                        </div>

                        <!-- Image Upload -->
                        <div>
                            <label
                                class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white mb-2">
                                Screenshot (Optional)
                            </label>

                            <div v-if="!imagePreview"
                                class="border-2 border-dashed border-[#CCCCCC] dark:border-[#333333] rounded-lg p-8 text-center hover:border-black hover:dark:border-white transition-colors">
                                <Upload class="mx-auto h-12 w-12 text-[#666666] dark:text-[#999999]"
                                    stroke-width="1.5" />
                                <label for="image-upload" class="mt-2 block cursor-pointer">
                                    <span
                                        class="text-black dark:text-white font-semibold uppercase font-mono tracking-wider text-xs">Upload
                                        an image</span>
                                    <span class="text-[#666666] dark:text-[#999999] text-xs"> or drag and drop</span>
                                </label>
                                <p class="mt-1 text-xs text-[#666666] dark:text-[#999999]">
                                    PNG, JPG, GIF up to 10MB
                                </p>
                                <input id="image-upload" type="file" accept="image/*" @change="handleImageUpload"
                                    class="hidden" />
                            </div>

                            <!-- Image Preview -->
                            <div v-else class="relative">
                                <img :src="imagePreview" alt="Preview"
                                    class="max-h-96 rounded-lg mx-auto border-2 border-[#E8E8E8] dark:border-[#222222]" />
                                <button type="button" @click="removeImage"
                                    class="absolute top-2 right-2 bg-[#D71921] hover:bg-red-700 text-white rounded-full p-2 transition-colors">
                                    <X class="h-5 w-5" stroke-width="1.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div
                            class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-6 border-t-2 border-[#E8E8E8] dark:border-[#222222]">
                            <a href="/support-tickets" class="w-full sm:w-auto">
                                <button type="button" class="w-full rounded-full px-6 py-2 border-2 border-[#CCCCCC] dark:border-[#333333] 
                                           text-black dark:text-white hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] 
                                           uppercase font-mono tracking-wider text-sm transition-colors">
                                    Cancel
                                </button>
                            </a>
                            <button type="submit" :disabled="form.processing" class="w-full sm:w-auto rounded-full bg-black dark:bg-white text-white dark:text-black px-6 py-2 
                                       hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white 
                                       border-2 border-black dark:border-white disabled:opacity-50 disabled:cursor-not-allowed
                                       uppercase font-mono tracking-wider text-sm transition-colors">
                                <Send class="mr-2 inline h-4 w-4" stroke-width="1.5" />
                                {{ form.processing ? 'Submitting...' : 'Submit Ticket' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Info Box -->
                <div
                    class="mt-6 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-6">
                    <h3
                        class="text-sm font-semibold text-black dark:text-white uppercase font-mono tracking-wider mb-3">
                        📝 Tips for Better Support
                    </h3>
                    <ul class="space-y-2 text-xs text-[#666666] dark:text-[#999999]">
                        <li>• Be specific about the issue you're experiencing</li>
                        <li>• Include any error messages you see</li>
                        <li>• Describe the steps to reproduce the problem</li>
                        <li>• Attach screenshots if they help illustrate the issue</li>
                        <li>• Mention what you've already tried to resolve it</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
