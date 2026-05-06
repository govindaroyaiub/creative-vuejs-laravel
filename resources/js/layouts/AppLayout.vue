<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import WelcomeBanner from '@/components/WelcomeBanner.vue';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<SharedData>();
const showWelcome = computed(() => Boolean((page.props as any).flash?.welcome_back) && Boolean(page.props.auth?.user));
const userName = computed(() => (page.props.auth?.user as any)?.name ?? '');
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs" class="font-mono">
        <WelcomeBanner v-if="showWelcome" :name="userName" />
        <slot />
    </AppSidebarLayout>
</template>
