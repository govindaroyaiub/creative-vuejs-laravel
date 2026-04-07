<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Designations',
        href: '/user-managements/designations',
    },
    {
        title: 'Routes',
        href: '/user-managements/routes',
    },
    {
        title: 'Users',
        href: '/user-managements/users',
    },
    {
        title: 'Tour Guide',
        href: '/preview-tour-guide',
    }
];

const page = usePage();

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';
</script>

<template>
    <div class="px-4 py-6 font-mono">
        <Heading title="Access Management" description="Manage users, routes and permissions" />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-12 lg:space-y-0">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col space-x-0 space-y-1 rounded-lg border-2 border-[#CCCCCC] dark:border-[#222222] p-2 bg-white dark:bg-black">
                    <Link v-for="item in sidebarNavItems" :key="item.href" :href="item.href" :class="[
                        'flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-xs uppercase font-mono tracking-wider outline-none transition-colors',
                        currentPath === item.href
                            ? 'bg-black text-white dark:bg-white dark:text-black'
                            : 'text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black'
                    ]">
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="flex-1 max-w-4xl">
                <section class="w-full space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
