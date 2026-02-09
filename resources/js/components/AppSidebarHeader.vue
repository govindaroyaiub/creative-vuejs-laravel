<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import NotificationCenter from '@/components/NotificationCenter.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useInitials } from '@/composables/useInitials';
import type { BreadcrumbItemType, SharedData, User } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

const page = usePage<SharedData>();
const user = page.props.auth.user as User;
const { getInitials } = useInitials();

const showAvatar = computed(() => user.avatar && user.avatar !== '');
const firstName = computed(() => user.name.split(' ')[0]);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 md:px-4">
        <div class="flex items-center gap-2 flex-1">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Notification Center & User -->
        <div class="flex items-center gap-2">
            <NotificationCenter />

            <!-- User Dropdown -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon"
                        class="h-9 w-9 rounded-full data-[state=open]:ring-2 data-[state=open]:ring-primary/20"
                        :title="user.name">
                        <Avatar class="h-8 w-8">
                            <AvatarImage v-if="showAvatar" :src="user.avatar" :alt="user.name" />
                            <AvatarFallback class="text-xs font-semibold text-primary">
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-56 rounded-lg" align="end" :side-offset="8">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
