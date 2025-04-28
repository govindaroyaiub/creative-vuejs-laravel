<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, Paperclip, Clapperboard, MonitorCog, MonitorStop, ReceiptText, Users, Image, CircleDollarSign } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

import { computed } from 'vue'; // ✅ important to make reactive

const page = usePage<SharedData>();

// Make user reactive
const user = computed(() => page.props.auth.user);

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { title: 'Previews', href: '/previews', icon: MonitorStop },
    { title: 'Banner Sizes', href: '/banner-sizes', icon: MonitorCog },
    { title: 'Video Sizes', href: '/video-sizes', icon: Clapperboard },
    { title: 'Social Image Formats', href: '/socials', icon: Image },
    { title: 'File Transfers', href: '/file-transfers', icon: Paperclip },
    { title: 'Bills', href: '/bills', icon: ReceiptText },
];

const footerNavItems: NavItem[] = [
    { title: 'Clients', href: '/clients', icon: CircleDollarSign },
    { title: 'Access Manager', href: '/user-managements/designations', icon: Users },
];

// Permission check function
function hasPermission(href: string) {
    if (!user.value?.permissions) return false;
    if (user.value.permissions.includes('*')) return true;
    return user.value.permissions.includes(href);
}
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