<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useNotifications } from '@/composables/useNotifications';
import { usePage } from '@inertiajs/vue3';
import type { SharedData } from '@/types';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { Bell, Trash2, Loader2, Inbox, Sparkles, Folder, MessageSquare, Package, Image, CheckCircle, XCircle } from 'lucide-vue-next';
import { ScrollArea } from '@/components/ui/scroll-area';

const page = usePage<SharedData>();

const {
    notifications,
    unreadCount,
    isLoading,
    hasMore,
    recentlyRead,
    fetchNotifications,
    fetchUnreadCount,
    markAllAsRead,
    deleteNotification,
    deleteAllRead,
    handleNotificationClick,
    getRelativeTime,
    loadMore,
    addNotification,
} = useNotifications();

const isOpen = ref(false);
const showFilter = ref<'all' | 'unread' | 'read'>('all');

// Check if user has notification permission
const hasNotificationPermission = computed(() => {
    const permissions = page.props.auth.user?.permissions;
    if (!permissions || !Array.isArray(permissions)) return false;
    return permissions.includes('*') || permissions.includes('/notifications');
});

// Initialize unread count from page props or fetch it
onMounted(() => {
    if (!hasNotificationPermission.value) return;

    if (page.props.notifications?.unread_count !== undefined) {
        unreadCount.value = page.props.notifications.unread_count;
    } else {
        fetchUnreadCount();
    }

    // Listen for real-time notifications via Laravel Echo
    const userId = page.props.auth.user?.id;
    if (userId) {
        window.Echo.private(`user.${userId}`)
            .listen('.notification.created', (data: any) => {
                // Add notification to the list
                addNotification(data);
                // Increment unread count
                unreadCount.value++;
            });
    }
});

// Cleanup WebSocket listener on component unmount
onUnmounted(() => {
    const userId = page.props.auth.user?.id;
    if (userId) {
        window.Echo.leave(`user.${userId}`);
    }
});

// Fetch notifications when dropdown opens and mark all as read (Facebook-style)
const handleOpenChange = async (open: boolean) => {
    isOpen.value = open;
    if (open) {
        if (notifications.value.length === 0) {
            await fetchNotifications(1, showFilter.value);
        }

        // Auto-mark all unread notifications as read when bell is clicked
        if (unreadCount.value > 0) {
            await markAllAsRead(true);
        }
    }
};

// Filter change handler
const handleFilterChange = (filterType: 'all' | 'unread' | 'read') => {
    showFilter.value = filterType;
    fetchNotifications(1, filterType);
};

// Get notification icon/color based on type
const getNotificationStyle = (type: string) => {
    switch (type) {
        case 'preview_created':
            return { icon: Sparkles, color: 'text-black dark:text-white', bgColor: 'bg-white dark:bg-black border border-[#CCCCCC] dark:border-[#333333]', borderColor: 'border-l-black dark:border-l-white', label: 'Preview' };
        case 'category_created':
            return { icon: Folder, color: 'text-black dark:text-white', bgColor: 'bg-white dark:bg-black border border-[#CCCCCC] dark:border-[#333333]', borderColor: 'border-l-black dark:border-l-white', label: 'Category' };
        case 'feedback_created':
            return { icon: MessageSquare, color: 'text-black dark:text-white', bgColor: 'bg-white dark:bg-black border border-[#CCCCCC] dark:border-[#333333]', borderColor: 'border-l-black dark:border-l-white', label: 'Feedback' };
        case 'feedback_approved':
            return { icon: CheckCircle, color: 'text-black dark:text-white', bgColor: 'bg-white dark:bg-black border border-[#CCCCCC] dark:border-[#333333]', borderColor: 'border-l-black dark:border-l-white', label: 'Approved' };
        case 'feedback_disapproved':
            return { icon: XCircle, color: 'text-[#D71921]', bgColor: 'bg-white dark:bg-black border border-[#D71921]', borderColor: 'border-l-[#D71921]', label: 'Disapproved' };
        case 'feedback_set_created':
            return { icon: Package, color: 'text-black dark:text-white', bgColor: 'bg-white dark:bg-black border border-[#CCCCCC] dark:border-[#333333]', borderColor: 'border-l-black dark:border-l-white', label: 'Set' };
        case 'version_created':
            return { icon: Sparkles, color: 'text-black dark:text-white', bgColor: 'bg-white dark:bg-black border border-[#CCCCCC] dark:border-[#333333]', borderColor: 'border-l-black dark:border-l-white', label: 'Version' };
        case 'asset_created':
            return { icon: Image, color: 'text-black dark:text-white', bgColor: 'bg-white dark:bg-black border border-[#CCCCCC] dark:border-[#333333]', borderColor: 'border-l-black dark:border-l-white', label: 'Asset' };
        default:
            return { icon: Bell, color: 'text-black dark:text-white', bgColor: 'bg-white dark:bg-black border border-[#CCCCCC] dark:border-[#333333]', borderColor: 'border-l-black dark:border-l-white', label: 'Update' };
    }
};

// Group notifications by date
const groupedNotifications = computed(() => {
    const groups: { Today: typeof filteredNotifications.value; Yesterday: typeof filteredNotifications.value; Earlier: typeof filteredNotifications.value } = {
        Today: [],
        Yesterday: [],
        Earlier: []
    };

    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    filteredNotifications.value.forEach(notification => {
        const notifDate = new Date(notification.created_at);
        const notifDay = new Date(notifDate.getFullYear(), notifDate.getMonth(), notifDate.getDate());

        if (notifDay.getTime() === today.getTime()) {
            groups.Today.push(notification);
        } else if (notifDay.getTime() === yesterday.getTime()) {
            groups.Yesterday.push(notification);
        } else {
            groups.Earlier.push(notification);
        }
    });

    return groups;
});

const filteredNotifications = computed(() => {
    switch (showFilter.value) {
        case 'unread':
            return notifications.value.filter(n => !n.is_read);
        case 'read':
            return notifications.value.filter(n => n.is_read);
        default:
            return notifications.value;
    }
});
</script>

<template>
    <DropdownMenu v-if="hasNotificationPermission" :open="isOpen" @update:open="handleOpenChange">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon"
                class="relative h-9 w-9 rounded-full border-2 border-[#CCCCCC] dark:border-[#333333] text-black dark:text-white hover:border-black dark:hover:border-white"
                aria-label="Notifications">
                <Bell class="h-5 w-5" />
                <span v-if="unreadCount > 0"
                    class="absolute -top-1 -right-1 flex h-5 min-w-[20px] items-center justify-center rounded-full border-2 border-[#D71921] bg-[#D71921] px-1 text-[10px] font-mono font-semibold text-white">
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end"
            class="w-[90vw] sm:w-[440px] p-0 rounded-lg border-2 border-[#CCCCCC] dark:border-[#222222] bg-white dark:bg-black"
            :side-offset="8">
            <!-- Header -->
            <div
                class="flex items-center justify-between px-4 py-3 border-b-2 border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-black">
                <div class="flex items-center gap-2">
                    <Bell class="h-4 w-4 text-[#666666] dark:text-[#999999]" />
                    <h3 class="text-xs font-mono uppercase tracking-wider text-black dark:text-white">Preview Activity
                    </h3>
                </div>
                <div class="flex items-center gap-1">
                    <Button variant="ghost" size="sm" :class="[
                        'h-7 px-2 text-[10px] rounded-full border-2 font-mono uppercase tracking-wider',
                        showFilter === 'all' ? 'bg-black dark:bg-white text-white dark:text-black border-black dark:border-white' : 'text-[#666666] dark:text-[#999999] border-[#CCCCCC] dark:border-[#333333]'
                    ]" @click="handleFilterChange('all')">
                        All
                    </Button>
                    <Button variant="ghost" size="sm" :class="[
                        'h-7 px-2 text-[10px] rounded-full border-2 font-mono uppercase tracking-wider',
                        showFilter === 'unread' ? 'bg-black dark:bg-white text-white dark:text-black border-black dark:border-white' : 'text-[#666666] dark:text-[#999999] border-[#CCCCCC] dark:border-[#333333]'
                    ]" @click="handleFilterChange('unread')">
                        Unread
                    </Button>
                </div>
            </div>

            <!-- Notifications List -->
            <ScrollArea class="h-[50vh] sm:h-[400px]">
                <div v-if="isLoading && notifications.length === 0"
                    class="flex items-center justify-center h-[50vh] sm:h-[400px]">
                    <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
                </div>

                <div v-else-if="filteredNotifications.length === 0"
                    class="flex flex-col items-center justify-center h-[50vh] sm:h-[400px] px-4 text-center">
                    <div
                        class="h-16 w-16 rounded-full border-2 border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-black flex items-center justify-center mb-3">
                        <Inbox class="h-8 w-8 text-[#666666] dark:text-[#999999]" />
                    </div>
                    <p class="text-sm font-medium text-black dark:text-white uppercase tracking-wide">All caught up!</p>
                    <p class="text-xs text-[#666666] dark:text-[#999999] mt-1 font-mono uppercase tracking-wider">No new
                        notifications</p>
                </div>

                <div v-else class="py-2">
                    <!-- Group by Date -->
                    <template v-for="(group, groupName) in groupedNotifications" :key="groupName">
                        <div v-if="group.length > 0" class="mb-3">
                            <!-- Date Header -->
                            <div class="px-4 py-1.5 sticky top-0 bg-white/95 dark:bg-black/95 backdrop-blur-sm z-10">
                                <h4
                                    class="text-[10px] font-semibold text-[#666666] dark:text-[#999999] uppercase tracking-wider font-mono">
                                    {{ groupName }}
                                </h4>
                            </div>

                            <!-- Notifications in Group -->
                            <div class="space-y-1 px-2">
                                <div v-for="notification in group" :key="notification.id"
                                    class="group relative rounded-lg border-l-2 border-t border-r border-b border-[#E8E8E8] dark:border-[#222222] mx-2 transition-colors duration-200"
                                    :class="[
                                        getNotificationStyle(notification.type).borderColor,
                                        recentlyRead.has(notification.id)
                                            ? 'bg-[#F5F5F5] dark:bg-[#0A0A0A]'
                                            : !notification.is_read
                                                ? 'bg-white dark:bg-black'
                                                : 'bg-white dark:bg-black hover:bg-[#F5F5F5] dark:hover:bg-[#0A0A0A]'
                                    ]">
                                    <!-- Unread Dot -->
                                    <div v-if="!notification.is_read"
                                        class="absolute -left-1 top-3 h-2 w-2 rounded-full bg-black dark:bg-white" />

                                    <div class="flex gap-3 p-3 cursor-pointer"
                                        @click="handleNotificationClick(notification)"
                                        @click.middle="handleNotificationClick(notification, true)"
                                        @auxclick.prevent="(e: MouseEvent) => e.button === 1 && handleNotificationClick(notification, true)">

                                        <!-- Icon -->
                                        <div class="flex-shrink-0 flex items-center justify-center">
                                            <div class="h-8 w-8 rounded-lg flex items-center justify-center"
                                                :class="getNotificationStyle(notification.type).bgColor">
                                                <component :is="getNotificationStyle(notification.type).icon"
                                                    class="h-4 w-4"
                                                    :class="getNotificationStyle(notification.type).color" />
                                            </div>
                                        </div>

                                        <!-- Content -->
                                        <div class="flex-1 min-w-0 space-y-1">
                                            <!-- Title & Badge -->
                                            <div class="flex items-start gap-2">
                                                <p
                                                    class="text-xs font-semibold text-black dark:text-white leading-tight line-clamp-1 flex-1">
                                                    {{ notification.title }}
                                                </p>
                                                <span
                                                    class="text-[10px] px-1.5 py-0.5 rounded-full border font-mono uppercase tracking-wider flex-shrink-0"
                                                    :class="[getNotificationStyle(notification.type).bgColor, getNotificationStyle(notification.type).color]">
                                                    {{ getNotificationStyle(notification.type).label }}
                                                </span>
                                            </div>

                                            <!-- Message -->
                                            <p
                                                class="text-[11px] text-[#666666] dark:text-[#999999] line-clamp-1 leading-tight">
                                                {{ notification.message }}
                                            </p>

                                            <!-- Footer: Preview & Time -->
                                            <div class="flex items-center gap-2 pt-0.5">
                                                <span v-if="notification.preview"
                                                    class="text-[10px] text-[#666666] dark:text-[#999999] font-medium truncate">
                                                    {{ notification.preview.name }}
                                                </span>
                                                <span
                                                    class="text-[10px] text-[#999999] dark:text-[#666666] flex-shrink-0">
                                                    • {{ getRelativeTime(notification.created_at) }}
                                                </span>
                                                <span v-if="notification.actor"
                                                    class="text-[10px] text-[#666666] dark:text-[#999999] font-medium flex-shrink-0">
                                                    • by {{ notification.actor.name }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Delete Button -->
                                        <div
                                            class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <Button variant="ghost" size="icon"
                                                class="h-6 w-6 text-[#666666] dark:text-[#999999] hover:text-[#D71921]"
                                                @click.stop="deleteNotification(notification.id)" title="Delete">
                                                <Trash2 class="h-3 w-3" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Load More -->
                    <div v-if="hasMore" class="px-4 pb-2 pt-3">
                        <Button variant="outline" size="sm"
                            class="w-full h-8 text-xs font-mono uppercase tracking-wider rounded-full border-2 border-[#CCCCCC] dark:border-[#333333]"
                            @click="loadMore" :disabled="isLoading">
                            <Loader2 v-if="isLoading" class="h-3 w-3 mr-1.5 animate-spin" />
                            Load more
                        </Button>
                    </div>
                </div>
            </ScrollArea>

            <!-- Footer -->
            <div v-if="notifications.length > 0"
                class="border-t-2 border-[#E8E8E8] dark:border-[#222222] bg-[#F5F5F5] dark:bg-[#0A0A0A]">
                <Button variant="ghost" size="sm"
                    class="w-full h-9 text-[11px] text-[#666666] dark:text-[#999999] hover:text-[#D71921] rounded-none font-mono uppercase tracking-wider"
                    @click="deleteAllRead">
                    <Trash2 class="h-3 w-3 mr-1.5" />
                    Clear all read
                </Button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
