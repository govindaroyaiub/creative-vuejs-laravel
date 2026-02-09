<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useNotifications } from '@/composables/useNotifications';
import { usePage } from '@inertiajs/vue3';
import type { SharedData } from '@/types';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { Bell, Trash2, Loader2, Inbox, Sparkles, Folder, MessageSquare, Package, Image } from 'lucide-vue-next';
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
    markAsRead,
    markAllAsRead,
    deleteNotification,
    deleteAllRead,
    handleNotificationClick,
    getRelativeTime,
    loadMore,
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
        case 'category_created':
            return { icon: Folder, color: 'text-blue-600', bgColor: 'bg-blue-50', borderColor: 'border-l-blue-500', label: 'Category' };
        case 'feedback_created':
            return { icon: MessageSquare, color: 'text-green-600', bgColor: 'bg-green-50', borderColor: 'border-l-green-500', label: 'Feedback' };
        case 'feedback_set_created':
            return { icon: Package, color: 'text-purple-600', bgColor: 'bg-purple-50', borderColor: 'border-l-purple-500', label: 'Set' };
        case 'version_created':
            return { icon: Sparkles, color: 'text-amber-600', bgColor: 'bg-amber-50', borderColor: 'border-l-amber-500', label: 'Version' };
        case 'asset_created':
            return { icon: Image, color: 'text-pink-600', bgColor: 'bg-pink-50', borderColor: 'border-l-pink-500', label: 'Asset' };
        default:
            return { icon: Bell, color: 'text-gray-600', bgColor: 'bg-gray-50', borderColor: 'border-l-gray-500', label: 'Update' };
    }
};

// Group notifications by date
const groupedNotifications = computed(() => {
    const groups: { [key: string]: typeof filteredNotifications.value } = {
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
            <Button variant="ghost" size="icon" class="relative h-9 w-9" aria-label="Notifications">
                <Bell class="h-5 w-5" />
                <span v-if="unreadCount > 0"
                    class="absolute -top-1 -right-1 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white ring-2 ring-background">
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-[90vw] sm:w-[440px] p-0" :side-offset="8">
            <!-- Header -->
            <div
                class="flex items-center justify-between px-4 py-3 border-b bg-gradient-to-r from-background to-accent/20">
                <div class="flex items-center gap-2">
                    <Bell class="h-4 w-4 text-muted-foreground" />
                    <h3 class="text-sm font-semibold">Preview Activity</h3>
                </div>
                <div class="flex items-center gap-1">
                    <Button variant="ghost" size="sm" :class="[
                        'h-6 px-2 text-[10px] font-medium',
                        showFilter === 'all' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'
                    ]" @click="handleFilterChange('all')">
                        All
                    </Button>
                    <Button variant="ghost" size="sm" :class="[
                        'h-6 px-2 text-[10px] font-medium',
                        showFilter === 'unread' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'
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
                    <div class="h-16 w-16 rounded-full bg-accent/50 flex items-center justify-center mb-3">
                        <Inbox class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">All caught up!</p>
                    <p class="text-xs text-muted-foreground mt-1">No new notifications</p>
                </div>

                <div v-else class="py-2">
                    <!-- Group by Date -->
                    <template v-for="(group, groupName) in groupedNotifications" :key="groupName">
                        <div v-if="group.length > 0" class="mb-3">
                            <!-- Date Header -->
                            <div class="px-4 py-1.5 sticky top-0 bg-background/95 backdrop-blur-sm z-10">
                                <h4 class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider">
                                    {{ groupName }}
                                </h4>
                            </div>

                            <!-- Notifications in Group -->
                            <div class="space-y-1 px-2">
                                <div v-for="notification in group" :key="notification.id"
                                    class="group relative rounded-lg border-l-2 mx-2 hover:shadow-sm transition-all duration-200"
                                    :class="[
                                        getNotificationStyle(notification.type).borderColor,
                                        recentlyRead.has(notification.id)
                                            ? 'bg-gradient-to-r from-gray-100/80 to-gray-50/40'
                                            : !notification.is_read
                                                ? 'bg-gradient-to-r from-blue-50/60 to-transparent'
                                                : 'bg-card hover:bg-accent/30'
                                    ]">
                                    <!-- Unread Dot -->
                                    <div v-if="!notification.is_read"
                                        class="absolute -left-1 top-3 h-2 w-2 rounded-full bg-blue-600 ring-2 ring-background" />

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
                                                    class="text-xs font-semibold text-foreground leading-tight line-clamp-1 flex-1">
                                                    {{ notification.title }}
                                                </p>
                                                <span
                                                    class="text-[10px] px-1.5 py-0.5 rounded font-medium flex-shrink-0"
                                                    :class="[getNotificationStyle(notification.type).bgColor, getNotificationStyle(notification.type).color]">
                                                    {{ getNotificationStyle(notification.type).label }}
                                                </span>
                                            </div>

                                            <!-- Message -->
                                            <p class="text-[11px] text-muted-foreground line-clamp-1 leading-tight">
                                                {{ notification.message }}
                                            </p>

                                            <!-- Footer: Preview & Time -->
                                            <div class="flex items-center gap-2 pt-0.5">
                                                <span v-if="notification.preview"
                                                    class="text-[10px] text-muted-foreground/70 font-medium truncate">
                                                    {{ notification.preview.name }}
                                                </span>
                                                <span class="text-[10px] text-muted-foreground/50 flex-shrink-0">
                                                    • {{ getRelativeTime(notification.created_at) }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Delete Button -->
                                        <div
                                            class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <Button variant="ghost" size="icon"
                                                class="h-6 w-6 text-muted-foreground hover:text-destructive"
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
                        <Button variant="outline" size="sm" class="w-full h-7 text-xs" @click="loadMore"
                            :disabled="isLoading">
                            <Loader2 v-if="isLoading" class="h-3 w-3 mr-1.5 animate-spin" />
                            Load more
                        </Button>
                    </div>
                </div>
            </ScrollArea>

            <!-- Footer -->
            <div v-if="notifications.length > 0" class="border-t bg-accent/10">
                <Button variant="ghost" size="sm"
                    class="w-full h-8 text-[11px] text-muted-foreground hover:text-destructive rounded-none"
                    @click="deleteAllRead">
                    <Trash2 class="h-3 w-3 mr-1.5" />
                    Clear all read
                </Button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
