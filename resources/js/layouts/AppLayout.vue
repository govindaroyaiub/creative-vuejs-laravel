<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import WelcomeBanner from '@/components/WelcomeBanner.vue';
import ThemeBackdrop from '@/components/ThemeBackdrop.vue';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    // Apply the shared P9 theme (JetBrains Mono, glass, ambient/starfield).
    // Set false to opt a page out — e.g. Previews/Update.vue, the theme origin.
    themed?: boolean;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    themed: true,
});

const page = usePage<SharedData>();
const showWelcome = computed(() => Boolean((page.props as any).flash?.welcome_back) && Boolean(page.props.auth?.user));
const userName = computed(() => (page.props.auth?.user as any)?.name ?? '');
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs" class="font-mono">
        <WelcomeBanner v-if="showWelcome" :name="userName" />
        <div v-if="themed" class="p9-theme relative min-h-screen">
            <ThemeBackdrop />
            <!--
                `relative` without a z-index on purpose. A positioned element
                WITH a z-index creates a stacking context, which trapped every
                modal rendered inside a page: a `fixed inset-0 z-50` overlay was
                clamped to this wrapper's level and painted below the sidebar
                (`fixed inset-y-0 z-10`), which lives outside this subtree.
                With z-index left as `auto` no stacking context is created, and
                the backdrop still sits behind: it is `absolute; z-index: 0`
                and this wrapper comes later in tree order, so both paint in
                the same layer and the content wins.
            -->
            <div class="relative">
                <slot />
            </div>
        </div>
        <slot v-else />
    </AppSidebarLayout>
</template>
