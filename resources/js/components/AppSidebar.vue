<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { useAppearance } from '@/composables/useAppearance';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, Paperclip, Clapperboard, MonitorCog, MonitorStop, ReceiptText, Users, History, Type, CircleDollarSign, Paintbrush, ImagePlay, ImageMinus } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

import { computed } from 'vue'; // ✅ important to make reactive

const page = usePage<SharedData>();

// Make user reactive
const user = computed(() => page.props.auth.user);

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { title: 'Previews', href: '/previews', icon: MonitorStop },
    { title: 'Color Palettes', href: '/color-palettes', icon: Paintbrush },
    { title: 'Banner Sizes', href: '/banner-sizes', icon: MonitorCog },
    { title: 'Video Sizes', href: '/video-sizes', icon: Clapperboard },
    { title: 'Bills', href: '/bills', icon: ReceiptText },
    { title: 'File Transfers', href: '/file-transfers', icon: Paperclip },
    { title: 'Media Library', 'href': '/medias', icon: ImagePlay },
    { title: 'TinyPNG', 'href': '/tinypng', icon: ImageMinus },
    { title: 'Tetris', href: '/play/tetris', icon: Type },
];

const footerNavItems: NavItem[] = [
    { title: 'Clients', href: '/clients', icon: CircleDollarSign },
    { title: 'Access Manager', href: '/user-managements/designations', icon: Users },
    { title: 'Activity Logs', 'href': '/activity-logs', icon: History }
];

// Permission check function
function hasPermission(href: string) {
    if (!user.value?.permissions) return false;
    if (user.value.permissions.includes('*')) return true;

    // ✅ Allow if any parent path matches
    return user.value.permissions.some(permission => href.startsWith(permission));
}

const { appearance, updateAppearance } = useAppearance();
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                        <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems.filter(item => hasPermission(item.href))" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems.filter(item => hasPermission(item.href))" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>