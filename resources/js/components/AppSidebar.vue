<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Paperclip, Clapperboard, MonitorCog, MonitorStop, ReceiptText, Users, Image, CircleDollarSign } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

// const { url } = usePage();

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Previews',
        href: '/previews',
        icon: MonitorStop,
    },
    {
        title: 'Banner Sizes',
        href: '/banner-sizes',
        icon: MonitorCog,
    },
    {
        title: 'Video Sizes',
        href: '/video-sizes',
        icon: Clapperboard,
    },
    {
        title: 'Social Image Formats',
        href: '/socials',
        icon: Image
    },
    {
        title: 'File Transfers',
        href: '/file-transfers',
        icon: Paperclip,
    },
    {
        title: 'Bills',
        href: '/bills',
        icon: ReceiptText,
    }
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits',
    //     icon: BookOpen,
    // },
    {
        title: 'Clients',
        href: '/clients',
        icon: CircleDollarSign,
    },
    {
        title: 'Users',
        href: '/users',
        icon: Users,
    }
];
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
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
<style>
.navbar-active {
    background-color: hsl(0 0% 98%);
    font-weight: bold;
}
</style>