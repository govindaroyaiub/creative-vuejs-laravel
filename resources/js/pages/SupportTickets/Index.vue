<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Eye, Trash2, AlertCircle, Clock, CheckCircle, X, Calendar, User, Tag, Image as ImageIcon } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Support Tickets', href: '/support-tickets' },
];

const page = usePage();
const tickets = computed(() => page.props.tickets as any);
const user = computed(() => (page.props as any).auth?.user);
const authUser = computed(() => (page.props.auth as any)?.user);
const isSuperAdmin = computed(() => authUser.value?.role === 'super_admin');

const isAdmin = computed(() => {
    return user.value?.role === 'super_admin' || user.value?.role === 'admin';
});

const showModal = ref(false);
const selectedTicket = ref<any>(null);

const openTicketModal = (ticket: any) => {
    selectedTicket.value = ticket;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedTicket.value = null;
};

const updateStatus = (ticketId: number, newStatus: string) => {
    router.put(
        route('support-tickets.update-status', ticketId),
        { status: newStatus },
        {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    title: 'Updated!',
                    text: 'Ticket status has been updated.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                });
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to update status.', 'error');
            },
        }
    );
};

const updatePriority = async (ticketId: number, newPriority: string) => {
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
            route('support-tickets.update-priority', ticketId),
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

const updateStatusFromModal = async (newStatus: string) => {
    if (!selectedTicket.value) return;

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
            route('support-tickets.update-status', selectedTicket.value.id),
            { status: newStatus },
            {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Updated!', 'Ticket status has been updated.', 'success');
                    selectedTicket.value.status = newStatus;
                },
                onError: () => {
                    Swal.fire('Error!', 'Failed to update status.', 'error');
                },
            }
        );
    }
};

const deleteTicket = async (id: number) => {
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
        router.delete(route('support-tickets.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('Deleted!', 'Ticket deleted successfully.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to delete ticket.', 'error');
            },
        });
    }
};

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
        case 'pending': return 'text-blue-600 dark:text-blue-400';
        case 'in_progress': return 'text-yellow-600 dark:text-yellow-400';
        case 'done': return 'text-green-600 dark:text-green-400';
        default: return 'text-[#666666] dark:text-[#999999]';
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
</script>

<template>

    <Head title="Support Tickets" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 sm:p-6">
                <div
                    class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-0 sm:justify-between">
                    <h1 class="text-2xl font-bold text-black dark:text-white uppercase font-mono tracking-wider">Support
                        Tickets</h1>
                    <a href="/support-tickets/create">
                        <button
                            class="w-full sm:w-auto rounded-full bg-black dark:bg-white text-white dark:text-black px-4 py-2 hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white group whitespace-nowrap uppercase font-mono tracking-wider text-sm transition-colors">
                            <CirclePlus
                                class="mr-1 inline h-5 w-5 group-hover:rotate-90 transition-transform duration-200" />
                            Create Ticket
                        </button>
                    </a>
                </div>

                <!-- Tickets Table -->
                <div class="overflow-x-auto rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                    <table class="w-full rounded bg-white dark:bg-[#111111]">
                        <thead class="bg-[#F5F5F5] dark:bg-[#0A0A0A] text-black dark:text-white">
                            <tr class="text-left text-xs uppercase font-mono tracking-wider">
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Priority</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Created By</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ticket in tickets.data" :key="ticket.id"
                                class="border-t border-[#E8E8E8] dark:border-[#222222] text-sm hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] transition-colors">
                                <td class="px-4 py-3">
                                    <span class="font-mono text-[#666666] dark:text-[#999999]">#{{ ticket.id }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-black dark:text-white">{{ ticket.name }}</div>
                                    <div class="text-xs text-[#666666] dark:text-[#999999] line-clamp-1">
                                        {{ ticket.description }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="getPriorityColor(ticket.priority)"
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase font-mono">
                                        {{ ticket.priority }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <!-- Admin: Dropdown to change status -->
                                    <select v-if="isAdmin" :value="ticket.status"
                                        @change="updateStatus(ticket.id, ($event.target as HTMLSelectElement).value)"
                                        class="px-3 py-1.5 text-xs border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg 
                                               bg-white dark:bg-[#111111] text-black dark:text-white 
                                               focus:border-black dark:focus:border-white focus:outline-none 
                                               transition-colors uppercase font-mono">
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="done">Done</option>
                                    </select>
                                    <!-- Regular User: Just display status -->
                                    <div v-else class="flex items-center gap-2">
                                        <component :is="getStatusIcon(ticket.status)"
                                            :class="getStatusColor(ticket.status)" class="h-4 w-4" stroke-width="1.5" />
                                        <span class="capitalize text-sm">{{ ticket.status.replace('_', ' ') }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-black dark:text-white font-medium">{{ ticket.creator?.name }}</div>
                                    <div class="text-xs text-[#666666] dark:text-[#999999]">{{ ticket.creator?.email }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-[#666666] dark:text-[#999999] font-mono text-xs">
                                    {{ new Date(ticket.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-3">
                                        <button @click="openTicketModal(ticket)"
                                            class="text-black dark:text-white hover:text-[#666666] dark:hover:text-[#999999] transition-colors">
                                            <Eye class="h-5 w-5" stroke-width="1.5" />
                                        </button>
                                        <button @click="deleteTicket(ticket.id)"
                                            :disabled="!isSuperAdmin"
                                            class="text-[#D71921] hover:text-red-700 transition-colors">
                                            <Trash2 class="h-5 w-5" stroke-width="1.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="tickets.data.length === 0">
                                <td colspan="7"
                                    class="px-4 py-8 text-center text-[#666666] dark:text-[#999999] uppercase font-mono tracking-wider text-sm">
                                    No support tickets found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="border-t-2 border-[#E8E8E8] dark:border-[#222222] p-4"
                    v-if="tickets.data.length > 0 && tickets.links.length">
                    <div class="flex justify-center gap-2">
                        <template v-for="link in tickets.links" :key="link.label">
                            <component :is="link.url ? 'a' : 'span'" v-html="link.label" :href="link.url"
                                class="rounded-lg px-4 py-2 text-xs uppercase font-mono tracking-wider border-2 transition-colors"
                                :class="{
                                    'bg-black dark:bg-white text-white dark:text-black border-black dark:border-white': link.active,
                                    'cursor-not-allowed text-[#999999] border-[#E8E8E8] dark:border-[#222222]': !link.url,
                                    'hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] border-[#CCCCCC] dark:border-[#333333] text-black dark:text-white': link.url && !link.active,
                                }" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticket Details Modal -->
        <div v-if="showModal && selectedTicket"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
            @click.self="closeModal">
            <div
                class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div
                    class="sticky top-0 bg-white dark:bg-[#111111] border-b-2 border-[#E8E8E8] dark:border-[#222222] p-4 flex items-start justify-between gap-4 z-10">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h2 class="text-lg font-bold text-black dark:text-white uppercase font-mono tracking-wider">
                                {{ selectedTicket.name }}
                            </h2>
                            <span class="text-xs font-mono text-[#666666] dark:text-[#999999]">
                                #{{ selectedTicket.id }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Status Badge -->
                            <div class="flex items-center gap-2">
                                <component :is="getStatusIcon(selectedTicket.status)" class="h-4 w-4"
                                    stroke-width="1.5" />
                                <span :class="getStatusColor(selectedTicket.status)"
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase font-mono">
                                    {{ selectedTicket.status.replace('_', ' ') }}
                                </span>
                            </div>

                            <!-- Priority Badge -->
                            <span :class="getPriorityColor(selectedTicket.priority)"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold uppercase font-mono">
                                <Tag class="h-3 w-3" stroke-width="1.5" />
                                {{ selectedTicket.priority }}
                            </span>
                        </div>
                    </div>
                    <button @click="closeModal"
                        class="p-2 rounded-lg hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] transition-colors">
                        <X class="h-5 w-5 text-black dark:text-white" stroke-width="1.5" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-4 space-y-4">
                    <!-- Admin Controls -->
                    <div v-if="isAdmin"
                        class="flex flex-col sm:flex-row gap-2 p-4 bg-[#F5F5F5] dark:bg-[#0A0A0A] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                        <select @change="updateStatusFromModal(($event.target as HTMLSelectElement).value)"
                            :value="selectedTicket.status" class="flex-1 px-4 py-2 border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg 
                                   bg-white dark:bg-[#111111] text-black dark:text-white text-xs uppercase font-mono
                                   focus:border-black dark:focus:border-white focus:outline-none transition-colors">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="done">Done</option>
                        </select>

                        <select @change="updatePriority(selectedTicket.id, ($event.target as HTMLSelectElement).value)"
                            :value="selectedTicket.priority" class="flex-1 px-4 py-2 border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg 
                                   bg-white dark:bg-[#111111] text-black dark:text-white text-xs uppercase font-mono
                                   focus:border-black dark:focus:border-white focus:outline-none transition-colors">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <!-- Description -->
                    <div
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4">
                        <h3
                            class="text-xs font-semibold text-black dark:text-white uppercase font-mono tracking-wider mb-3">
                            Description
                        </h3>
                        <div class="text-sm text-black dark:text-white whitespace-pre-wrap leading-relaxed">
                            {{ selectedTicket.description }}
                        </div>
                    </div>

                    <!-- Screenshot -->
                    <div v-if="selectedTicket.image"
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4">
                        <h3
                            class="text-xs font-semibold text-black dark:text-white uppercase font-mono tracking-wider mb-3 flex items-center gap-2">
                            <ImageIcon class="h-4 w-4" stroke-width="1.5" />
                            Screenshot
                        </h3>
                        <img :src="`/${selectedTicket.image}`" :alt="selectedTicket.name"
                            class="max-w-full rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]" />
                    </div>

                    <!-- Ticket Info -->
                    <div
                        class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-4">
                        <h3
                            class="text-xs font-semibold text-black dark:text-white uppercase font-mono tracking-wider mb-3">
                            Ticket Information
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Created By -->
                            <div>
                                <div
                                    class="flex items-center gap-2 text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">
                                    <User class="h-3 w-3" stroke-width="1.5" />
                                    Created By
                                </div>
                                <div class="text-sm text-black dark:text-white font-semibold">
                                    {{ selectedTicket.creator?.name }}
                                </div>
                                <div class="text-xs text-[#666666] dark:text-[#999999]">
                                    {{ selectedTicket.creator?.email }}
                                </div>
                            </div>

                            <!-- Created Date -->
                            <div>
                                <div
                                    class="flex items-center gap-2 text-xs uppercase font-mono tracking-wider text-[#666666] dark:text-[#999999] mb-1">
                                    <Calendar class="h-3 w-3" stroke-width="1.5" />
                                    Created
                                </div>
                                <div class="text-xs text-black dark:text-white font-mono">
                                    {{ new Date(selectedTicket.created_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'short',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
