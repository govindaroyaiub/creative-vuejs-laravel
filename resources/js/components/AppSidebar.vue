<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';
import {
    MAIN_NAV_ITEMS,
    FOOTER_NAV_ITEMS,
    applyNavPreferences,
} from '@/lib/sidebar-nav';

const page = usePage<SharedData>();

// Make user reactive
const user = computed(() => page.props.auth.user);

// User's saved sidebar customisation, shared from HandleInertiaRequests.
// `null` / undefined means "use the canonical order with everything visible".
const navPrefs = computed(() => (user.value as any)?.nav_preferences ?? null);

const visibleMainItems = computed(() =>
    applyNavPreferences(MAIN_NAV_ITEMS, navPrefs.value?.main).filter(hasPermission),
);
const visibleFooterItems = computed(() =>
    applyNavPreferences(FOOTER_NAV_ITEMS, navPrefs.value?.footer).filter(hasPermission),
);

// Permission check — preserved verbatim from the previous version so
// existing role grants keep working.
function hasPermission(item: { href: string }) {
    if (!user.value?.permissions) return false;
    if (user.value.permissions.includes('*')) return true;
    return user.value.permissions.some((permission: string) => item.href.startsWith(permission));
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
            <NavMain :items="visibleMainItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="visibleFooterItems" />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>