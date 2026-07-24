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
            <div class="relative z-[1]">
                <slot />
            </div>
        </div>
        <slot v-else />
    </AppSidebarLayout>
</template>
