<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, Clock, CheckCircle, Calendar, User, Tag, Image as ImageIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps<{
    ticket: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Support Tickets', href: '/support-tickets' },
    { title: `Ticket #${props.ticket.id}`, href: `/support-tickets/${props.ticket.id}` },
];

const page = usePage();
const user = computed(() => (page.props as any).auth?.user);

const isAdmin = computed(() => {
    return user.value?.role === 'superadmin' || user.value?.role === 'admin';
});

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'pending': return AlertCircle;
        case 'in_progress': return Clock;
        case 'done': return CheckCircle;
        default: return AlertCircle;
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending': return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
        case 'in_progress': return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300';
        case 'done': return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300';
        default: return 'bg-[#F5F5F5] dark:bg-[#0A0A0A] text-[#666666] dark:text-[#999999]';
    }
};

const getPriorityColor = (priority: string) => {
    switch (priority) {
        case 'low': return 'bg-[#F5F5F5] dark:bg-[#0A0A0A] text-[#666666] dark:text-[#999999] border border-[#CCCCCC] dark:border-[#333333]';
        case 'medium': return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300';
        case 'high': return 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300';
        case 'urgent': return 'bg-[#D71921]/10 dark:bg-[#D71921]/20 text-[#D71921]';
        default: return 'bg-[#F5F5F5] dark:bg-[#0A0A0A] text-[#666666] dark:text-[#999999]';
    }
};

const updateStatus = async (newStatus: string) => {
    const result = await Swal.fire({
        title: 'Update Status?',
        text: `Change ticket status to "${newStatus.replace('_', ' ')}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Yes, update it!',
    });

    if (result.isConfirmed) {
        router.put(
            route('support-tickets.update-status', props.ticket.id),
            { status: newStatus },
            {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Updated!', 'Ticket status has been updated.', 'success');
                },
                onError: () => {
                    Swal.fire('Error!', 'Failed to update status.', 'error');
                },
            }
        );
    }
};

const updatePriority = async (newPriority: string) => {
    const result = await Swal.fire({
        title: 'Update Priority?',
        text: `Change ticket priority to "${newPriority}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Yes, update it!',
    });

    if (result.isConfirmed) {
        router.put(
            route('support-tickets.update-priority', props.ticket.id),
            { priority: newPriority },
            {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Updated!', 'Ticket priority has been updated.', 'success');
                },
                onError: () => {
                    Swal.fire('Error!', 'Failed to update priority.', 'error');
                },
            }
        );
    }
};
</script>

<template>

    <Head :title="`Ticket #${ticket.id}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 sm:p-6 max-w-5xl mx-auto">
                <!-- Header -->
                <div
                    class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-6 mb-6">
                    <div class="flex flex-col lg:flex-row items-start justify-between gap-4">
                        <div class="flex-1 w-full">
                            <div class="flex items-center gap-3 mb-2">
                                <h1
                                    class="text-xl sm:text-2xl font-bold text-black dark:text-white uppercase font-mono tracking-wider">
                                    {{ ticket.name }}
                                </h1>
                                <span class="text-xs font-mono text-[#666666] dark:text-[#999999]">
                                    #{{ ticket.id }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3 flex-wrap mt-4">
                                <!-- Status Badge -->
                                <div class="flex items-center gap-2">
                                    <component :is="getStatusIcon(ticket.status)" class="h-4 w-4" stroke-width="1.5" />
                                    <span :class="getStatusColor(ticket.status)"
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase font-mono">
                                        {{ ticket.status.replace('_', ' ') }}
                                    </span>
                                </div>

                                <!-- Priority Badge -->
                                <span :class="getPriorityColor(ticket.priority)"
                                    class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold uppercase font-mono">
                                    <Tag class="h-3 w-3" stroke-width="1.5" />
                                    {{ ticket.priority }}
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons (Admin Only) -->
                        <div v-if="isAdmin" class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                            <select @change="updateStatus(($event.target as HTMLSelectElement).value)"
                                :value="ticket.status"
                                class="px-4 py-2 border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg 
                                       bg-white dark:bg-[#111111] text-black dark:text-white text-xs uppercase font-mono
                                       focus:border-black dark:focus:border-white focus:outline-none transition-colors">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="done">Done</option>
                            </select>

                            <select @change="updatePriority(($event.target as HTMLSelectElement).value)"
                                :value="ticket.priority"
                                class="px-4 py-2 border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg 
                                       bg-white dark:bg-[#111111] text-black dark:text-white text-xs uppercase font-mono
                                       focus:border-black dark:focus:border-white focus:outline-none transition-colors">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Description -->
                        <div
                            class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-6">
                            <h2
                                class="text-sm font-semibold text-black dark:text-white uppercase font-mono tracking-wider mb-4">
                                Description
                            </h2>
                            <div class="text-sm text-black dark:text-white whitespace-pre-wrap leading-relaxed">
                                {{ ticket.description }}
                            </div>
                        </div>

                        <!-- Screenshot -->
                        <div v-if="ticket.image"
                            class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-6">
                            <h2
                                class="text-sm font-semibold text-black dark:text-white uppercase font-mono tracking-wider mb-4 flex items-center gap-2">
                                <ImageIcon class="h-4 w-4" stroke-width="1.5" />
                                Screenshot
                            </h2>
                            <img :src="`/${ticket.image}`" :alt="ticket.name"
                                class="max-w-full rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]" />
                        </div>
                    </div>

                    <!-- Sidebar Info -->
                    <div class="space-y-6">
                        <!-- Ticket Info -->
                        <div
                            class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-6">
                            <h2
                                class="text-sm font-semibold text-black dark:text-white uppercase font-mono tracking-wider mb-4">
                                Ticket Information
                            </h2>

                            <div class="space-y-4">
                                <!-- Created By -->
                                <div>
                                    <div
                                        class="flex items-center gap-2 text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">
                                        <User class="h-3 w-3" stroke-width="1.5" />
                                        Created By
                                    </div>
                                    <div class="text-sm text-black dark:text-white font-semibold">
                                        {{ ticket.creator?.name }}
                                    </div>
                                    <div class="text-xs text-[#666666] dark:text-[#999999]">
                                        {{ ticket.creator?.email }}
                                    </div>
                                </div>

                                <!-- Created Date -->
                                <div>
                                    <div
                                        class="flex items-center gap-2 text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">
                                        <Calendar class="h-3 w-3" stroke-width="1.5" />
                                        Created
                                    </div>
                                    <div class="text-sm text-black dark:text-white font-mono">
                                        {{ new Date(ticket.created_at).toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        }) }}
                                    </div>
                                </div>

                                <!-- Last Updated -->
                                <div>
                                    <div
                                        class="flex items-center gap-2 text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">
                                        <Clock class="h-3 w-3" stroke-width="1.5" />
                                        Last Updated
                                    </div>
                                    <div class="text-sm text-black dark:text-white font-mono">
                                        {{ new Date(ticket.updated_at).toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        }) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div v-if="isAdmin"
                            class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-6">
                            <h2
                                class="text-sm font-semibold text-black dark:text-white uppercase font-mono tracking-wider mb-4">
                                Quick Actions
                            </h2>

                            <div class="space-y-2">
                                <button v-if="ticket.status !== 'in_progress'" @click="updateStatus('in_progress')"
                                    class="w-full flex items-center justify-start px-4 py-2 rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] 
                                           text-black dark:text-white hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] 
                                           transition-colors uppercase font-mono tracking-wider text-xs">
                                    <Clock class="mr-2 h-4 w-4" stroke-width="1.5" />
                                    Mark In Progress
                                </button>

                                <button v-if="ticket.status !== 'done'" @click="updateStatus('done')" class="w-full flex items-center justify-start px-4 py-2 rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] 
                                           text-black dark:text-white hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] 
                                           transition-colors uppercase font-mono tracking-wider text-xs">
                                    <CheckCircle class="mr-2 h-4 w-4" stroke-width="1.5" />
                                    Mark as Done
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
