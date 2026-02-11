import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    external?: boolean; // For links outside Inertia (like Pulse, Horizon)
}

export interface Notification {
    id: number;
    user_id: number;
    type: 'category_created' | 'feedback_created' | 'feedback_set_created' | 'version_created' | 'asset_created';
    title: string;
    message: string | null;
    data: Record<string, any> | null;
    link: string | null;
    preview_id: number | null;
    actor_id: number | null;
    is_read: boolean;
    read_at: string | null;
    created_at: string;
    updated_at: string;
    preview?: {
        id: number;
        name: string;
        slug: string;
    };
    actor?: {
        id: number;
        name: string;
    };
}

export interface PaginatedNotifications {
    data: Notification[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    notifications?: {
        unread_count: number;
        recent: Notification[];
    };
    ziggy: Config & { location: string };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    permissions?: string[];
    client_id?: number;
}

export type BreadcrumbItemType = BreadcrumbItem;
