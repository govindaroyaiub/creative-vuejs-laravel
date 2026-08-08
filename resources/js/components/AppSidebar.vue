<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';
import { computed, nextTick, onMounted, watch } from 'vue';
import { navItemsForSection } from '@/lib/sidebar-nav';

const page = usePage<SharedData>();
const { state } = useSidebar();

// Make user reactive
const user = computed(() => page.props.auth.user);

// User's saved sidebar customisation, shared from HandleInertiaRequests.
// `null` / undefined means "use the canonical order with everything visible".
const navPrefs = computed(() => (user.value as any)?.nav_preferences ?? null);

const visibleMainItems = computed(() =>
    navItemsForSection('main', navPrefs.value).filter(hasPermission),
);
const visibleFooterItems = computed(() =>
    navItemsForSection('footer', navPrefs.value).filter(hasPermission),
);

// Permission check — preserved verbatim from the previous version so
// existing role grants keep working.
function hasPermission(item: { href: string }) {
    if (!user.value?.permissions) return false;
    if (user.value.permissions.includes('*')) return true;
    return user.value.permissions.some((permission: string) => item.href.startsWith(permission));
}

/**
 * Scroll the selected nav item into view.
 *
 * The list scrolls once it outgrows the viewport, which happens easily on a
 * 13" screen. The active row is highlighted, but if it sits below the fold you
 * open the app with no on-screen clue where you are — you have to scroll the
 * sidebar to find the highlight.
 *
 * `container.scrollTop` is adjusted directly rather than calling
 * `scrollIntoView()`, which also scrolls ancestor scrollers — including the
 * page — and would yank the main content around on every navigation.
 */
function revealActiveItem() {
    // Collapsed to icons the container is overflow-hidden, so the user has no
    // way to scroll back; leave it alone.
    if (state.value === 'collapsed') return;

    const container = document.querySelector<HTMLElement>('[data-sidebar="content"]');
    const active = container?.querySelector<HTMLElement>('[data-sidebar="menu-button"][data-active="true"]');
    if (!container || !active) return;
    if (container.scrollHeight <= container.clientHeight) return;

    const item = active.getBoundingClientRect();
    const view = container.getBoundingClientRect();
    const margin = 12; // a little breathing room so the row never hugs the edge

    if (item.top < view.top + margin) {
        container.scrollTop -= view.top + margin - item.top;
    } else if (item.bottom > view.bottom - margin) {
        container.scrollTop += item.bottom - (view.bottom - margin);
    }
}

onMounted(() => nextTick(revealActiveItem));

// Inertia swaps the page without remounting the sidebar, so the active row
// changes without any lifecycle hook firing.
watch(() => page.url, () => nextTick(revealActiveItem));
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