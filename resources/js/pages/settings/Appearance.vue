<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

// Persistent layout — keeps AppLayout (sidebar/backdrop) mounted across
// Inertia navigations instead of rebuilding it on every page change.
defineOptions({
    layout: (h: any, page: any) =>
        h(AppLayout, { breadcrumbs: [{ title: 'Appearance settings', href: '/settings/appearance' }] }, () => page),
});
</script>

<template>
    <Head title="Appearance settings" />

        <SettingsLayout>
            <div class="space-y-6 font-mono">
                <HeadingSmall title="Appearance settings" description="Update your account's appearance settings" />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
</template>
