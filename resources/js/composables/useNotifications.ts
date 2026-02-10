import type { Notification, PaginatedNotifications } from '@/types';
import { computed, ref } from 'vue';

const unreadCount = ref(0);
const notifications = ref<Notification[]>([]);
const currentPage = ref(1);
const lastPage = ref(1);
const isLoading = ref(false);
const filter = ref<'all' | 'unread' | 'read'>('all');
const recentlyRead = ref<Set<number>>(new Set());

export function useNotifications() {
    /**
     * Fetch notifications
     */
    const fetchNotifications = async (page = 1, filterType: 'all' | 'unread' | 'read' = 'all') => {
        isLoading.value = true;
        filter.value = filterType;

        try {
            const params = new URLSearchParams();
            params.append('page', page.toString());
            if (filterType !== 'all') {
                params.append('filter', filterType);
            }

            const response = await fetch(`/api/notifications?${params.toString()}`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch notifications: ${response.status}`);
            }

            const data: PaginatedNotifications = await response.json();

            if (page === 1) {
                notifications.value = data.data;
            } else {
                notifications.value.push(...data.data);
            }

            currentPage.value = data.current_page;
            lastPage.value = data.last_page;
        } catch (error) {
            console.error('Error fetching notifications:', error);
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Fetch unread count
     */
    const fetchUnreadCount = async () => {
        try {
            const response = await fetch('/api/notifications/unread-count', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch unread count: ${response.status}`);
            }

            const data = await response.json();
            unreadCount.value = data.count;
        } catch (error) {
            console.error('Error fetching unread count:', error);
        }
    };

    /**
     * Mark notification as read
     */
    const markAsRead = async (id: number) => {
        try {
            const response = await fetch(`/api/notifications/${id}/mark-as-read`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) throw new Error('Failed to mark as read');

            // Update local state
            const notification = notifications.value.find((n) => n.id === id);
            if (notification) {
                notification.is_read = true;
                notification.read_at = new Date().toISOString();
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    };

    /**
     * Mark notification as unread
     */
    const markAsUnread = async (id: number) => {
        try {
            const response = await fetch(`/api/notifications/${id}/mark-as-unread`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) throw new Error('Failed to mark as unread');

            // Update local state
            const notification = notifications.value.find((n) => n.id === id);
            if (notification) {
                notification.is_read = false;
                notification.read_at = null;
                unreadCount.value++;
            }
        } catch (error) {
            console.error('Error marking notification as unread:', error);
        }
    };

    /**
     * Mark all notifications as read (Facebook-style)
     */
    const markAllAsRead = async (trackAsRecent = false) => {
        try {
            const response = await fetch('/api/notifications/mark-all-as-read', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) throw new Error('Failed to mark all as read');

            // Track which notifications were just marked as read for gray background effect
            if (trackAsRecent) {
                notifications.value.forEach((notification) => {
                    if (!notification.is_read) {
                        recentlyRead.value.add(notification.id);
                    }
                });
            }

            // Update local state
            notifications.value.forEach((notification) => {
                notification.is_read = true;
                notification.read_at = new Date().toISOString();
            });
            unreadCount.value = 0;
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    };

    /**
     * Delete notification
     */
    const deleteNotification = async (id: number) => {
        try {
            const response = await fetch(`/api/notifications/${id}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) throw new Error('Failed to delete notification');

            // Update local state
            const notification = notifications.value.find((n) => n.id === id);
            if (notification && !notification.is_read) {
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }

            notifications.value = notifications.value.filter((n) => n.id !== id);
        } catch (error) {
            console.error('Error deleting notification:', error);
        }
    };

    /**
     * Delete all read notifications
     */
    const deleteAllRead = async () => {
        try {
            const response = await fetch('/api/notifications/all/read', {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) throw new Error('Failed to delete read notifications');

            // Update local state
            notifications.value = notifications.value.filter((n) => !n.is_read);
        } catch (error) {
            console.error('Error deleting read notifications:', error);
        }
    };

    /**
     * Handle notification click
     */
    const handleNotificationClick = async (notification: Notification, openInNewTab = false) => {
        // Mark as read if unread
        if (!notification.is_read) {
            await markAsRead(notification.id);
        }

        // Navigate to the link if it exists
        if (notification.link) {
            if (openInNewTab) {
                window.open(notification.link, '_blank');
            } else {
                // Use regular navigation instead of Inertia router for blade views
                window.location.href = notification.link;
            }
        }
    };

    /**
     * Get relative time (e.g., "2 hours ago")
     */
    const getRelativeTime = (date: string): string => {
        const now = new Date();
        const then = new Date(date);
        const seconds = Math.floor((now.getTime() - then.getTime()) / 1000);

        if (seconds < 60) return 'Just now';
        if (seconds < 3600) {
            const minutes = Math.floor(seconds / 60);
            return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'} ago`;
        }
        if (seconds < 86400) {
            const hours = Math.floor(seconds / 3600);
            return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
        }
        if (seconds < 604800) {
            const days = Math.floor(seconds / 86400);
            return `${days} ${days === 1 ? 'day' : 'days'} ago`;
        }
        if (seconds < 2592000) {
            const weeks = Math.floor(seconds / 604800);
            return `${weeks} ${weeks === 1 ? 'week' : 'weeks'} ago`;
        }
        if (seconds < 31536000) {
            const months = Math.floor(seconds / 2592000);
            return `${months} ${months === 1 ? 'month' : 'months'} ago`;
        }

        const years = Math.floor(seconds / 31536000);
        return `${years} ${years === 1 ? 'year' : 'years'} ago`;
    };

    /**
     * Load more notifications
     */
    const loadMore = () => {
        if (currentPage.value < lastPage.value && !isLoading.value) {
            fetchNotifications(currentPage.value + 1, filter.value);
        }
    };

    /**
     * Add a new notification (for real-time updates)
     */
    const addNotification = (notification: Notification) => {
        // Add to the beginning of the list
        notifications.value.unshift(notification);
    };

    const hasMore = computed(() => currentPage.value < lastPage.value);
    const unreadNotifications = computed(() => notifications.value.filter((n) => !n.is_read));
    const readNotifications = computed(() => notifications.value.filter((n) => n.is_read));

    return {
        notifications,
        unreadCount,
        isLoading,
        hasMore,
        unreadNotifications,
        readNotifications,
        filter,
        recentlyRead,
        fetchNotifications,
        fetchUnreadCount,
        markAsRead,
        markAsUnread,
        markAllAsRead,
        deleteNotification,
        deleteAllRead,
        handleNotificationClick,
        getRelativeTime,
        loadMore,
        addNotification,
    };
}
