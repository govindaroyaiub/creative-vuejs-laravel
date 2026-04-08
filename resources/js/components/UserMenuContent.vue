<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LogOut, Settings, LifeBuoy } from 'lucide-vue-next';

interface Props {
    user: User;
}

defineProps<Props>();

const openSupportTicket = () => {
    window.open(route('support-tickets.index'), '_blank');
};
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-2 py-2 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />

    <!-- Appearance Switcher -->
    <div class="py-1 px-2">
        <AppearanceTabs :icon-only="true"
            class="w-full justify-evenly bg-[#F5F5F5] dark:bg-[#0A0A0A] border-2 border-[#E8E8E8] dark:border-[#222222]" />
    </div>

    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <button class="flex w-full items-center font-mono uppercase tracking-wider text-xs cursor-pointer"
                @click="openSupportTicket" type="button">
                <LifeBuoy class="mr-2 h-4 w-4" />
                Support Ticket
            </button>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full font-mono uppercase tracking-wider text-xs" :href="route('profile.edit')"
                as="button">
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link class="block w-full font-mono uppercase tracking-wider text-xs" method="post" :href="route('logout')"
            as="button">
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
