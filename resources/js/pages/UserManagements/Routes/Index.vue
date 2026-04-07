<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/pages/UserManagements/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Routes', href: '/user-managements/routes' }];

const page = usePage();
const routes = ref(page.props.routes ?? []);
const search = ref('');
const authUser = computed(() => (page.props.auth as any)?.user);
const isSuperAdmin = computed(() => authUser.value?.role === 'super_admin');

const filteredRoutes = computed(() => {
    const query = search.value.toLowerCase();
    return routes.value.filter((r: any) =>
        r.title.toLowerCase().includes(query) || r.href.toLowerCase().includes(query)
    );
});

const adding = ref(false);
const newRoute = ref({ title: '', href: '' });
const editingId = ref<number | null>(null);
const editForm = ref({ title: '', href: '' });

const startAdding = () => {
    adding.value = true;
    newRoute.value = { title: '', href: '' };
};

const cancelAdding = () => {
    adding.value = false;
};

const saveRoute = () => {
    if (!newRoute.value.title.trim() || !newRoute.value.href.trim()) return;

    router.post(route('user-managements-routes-create-post'), newRoute.value, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            Swal.fire('Added!', 'Route created.', 'success');
            adding.value = false;
            newRoute.value = { title: '', href: '' };
        },
        onError: () => {
            Swal.fire('Error!', 'Failed to create route.', 'error');
        },
    });
};

const startEditing = (route: any) => {
    editingId.value = route.id;
    editForm.value = { title: route.title, href: route.href };
};

const cancelEditing = () => {
    editingId.value = null;
    editForm.value = { title: '', href: '' };
};

const updateRoute = (id: number) => {
    router.put(route('user-managements-routes-update', id), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            const index = routes.value.findIndex((r: any) => r.id === id);
            if (index !== -1) {
                routes.value[index] = { ...routes.value[index], ...editForm.value };
            }
            Swal.fire('Updated!', 'Route updated successfully.', 'success');
            editingId.value = null;
            editForm.value = { title: '', href: '' };
        },
        onError: () => {
            Swal.fire('Error!', 'Failed to update.', 'error');
        },
    });
};

const deleteRoute = async (id: number) => {
    const confirm = await Swal.fire({
        title: 'Are you sure?',
        text: 'This cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (confirm.isConfirmed) {
        router.delete(route('user-managements-routes-delete', id), {
            preserveScroll: true,
            onSuccess: () => {
                routes.value = routes.value.filter((r: any) => r.id !== id);
                Swal.fire('Deleted!', 'Route deleted successfully.', 'success');
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to delete.', 'error');
            },
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Routes" />
        <SettingsLayout>
            <!-- Nothing Design System -->
            <div class="space-y-6 font-mono">
                <!-- Header Section -->
                <div class="flex flex-col items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 bg-[#D71921] rounded-full animate-pulse"></div>
                        <h2 class="text-2xl font-light tracking-widest uppercase text-black dark:text-white">
                            ROUTES
                        </h2>
                    </div>
                    <p class="text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999] mt-2">
                        NAVIGATION · ACCESS · CONTROL
                    </p>
                </div>

                <!-- Search & Add Section -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <div class="relative flex-1 max-w-md">
                        <input v-model="search" placeholder="SEARCH ROUTES..."
                            class="w-full px-4 py-3 bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg text-sm tracking-wider uppercase placeholder:text-[#CCCCCC] dark:placeholder:text-[#333333] text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200" />
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 w-1.5 h-1.5 bg-[#D71921] rounded-full">
                        </div>
                    </div>
                    <Button v-if="!adding" @click="startAdding"
                        :disabled="!isSuperAdmin"
                        class="px-6 py-3 border-2 border-[#1A1A1A] dark:border-[#FFFFFF] text-[#1A1A1A] dark:text-[#FFFFFF] bg-transparent rounded-full transition-all duration-200 hover:bg-[#1A1A1A] hover:text-white dark:hover:bg-[#FFFFFF] dark:hover:text-black text-xs tracking-widest uppercase font-bold">
                        + ADD NEW
                    </Button>
                </div>

                <!-- Table Container -->
                <div
                    class="bg-white dark:bg-[#111111] border border-[#E8E8E8] dark:border-[#222222] rounded-lg overflow-hidden">
                    <table class="min-w-full">
                        <!-- Table Header -->
                        <thead class="bg-[#F5F5F5] dark:bg-[#0A0A0A] border-b border-[#E8E8E8] dark:border-[#222222]">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999] font-mono">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1 h-1 bg-[#D71921] rounded-full"></div>
                                        INDEX
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999] font-mono">
                                    ROUTE TITLE
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999] font-mono">
                                    PATH / HREF
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999] font-mono">
                                    ACTIONS
                                </th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody>
                            <!-- Add New Row -->
                            <tr v-if="adding"
                                class="border-b border-[#E8E8E8] dark:border-[#222222] bg-[#FAFAFA] dark:bg-[#0D0D0D] hover:bg-[#F5F5F5] dark:hover:bg-[#151515] transition-all duration-200">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 bg-[#D71921] rounded-full animate-pulse"></div>
                                        <span class="text-sm font-mono text-[#999999]">NEW</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <input v-model="newRoute.title" placeholder="ROUTE TITLE..."
                                        class="w-full px-3 py-2 bg-white dark:bg-[#111111] border border-[#CCCCCC] dark:border-[#333333] rounded text-sm tracking-wider uppercase placeholder:text-[#CCCCCC] dark:placeholder:text-[#333333] text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200"
                                        autofocus />
                                </td>
                                <td class="px-6 py-4">
                                    <input v-model="newRoute.href" placeholder="/PATH/TO/ROUTE..."
                                        class="w-full px-3 py-2 bg-white dark:bg-[#111111] border border-[#CCCCCC] dark:border-[#333333] rounded text-sm font-mono placeholder:text-[#CCCCCC] dark:placeholder:text-[#333333] text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200" />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-3">
                                        <Button @click="saveRoute"
                                            class="px-4 py-2 bg-[#1A1A1A] dark:bg-[#FFFFFF] text-white dark:text-black rounded-full text-xs tracking-widest uppercase font-bold hover:opacity-80 transition-all duration-200">
                                            SAVE
                                        </Button>
                                        <Button variant="outline" @click="cancelAdding"
                                            class="px-4 py-2 border border-[#CCCCCC] dark:border-[#333333] text-[#666666] dark:text-[#999999] rounded-full text-xs tracking-widest uppercase hover:border-[#1A1A1A] dark:hover:border-[#FFFFFF] hover:text-[#1A1A1A] dark:hover:text-[#FFFFFF] transition-all duration-200">
                                            CANCEL
                                        </Button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Data Rows -->
                            <tr v-for="(route, index) in filteredRoutes" :key="route.id"
                                class="border-b border-[#E8E8E8] dark:border-[#222222] hover:bg-[#FAFAFA] dark:hover:bg-[#0D0D0D] transition-all duration-200 group">

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-1 h-1 bg-[#666666] dark:bg-[#999999] rounded-full group-hover:bg-[#D71921] transition-all duration-200">
                                        </div>
                                        <span class="text-sm font-mono tabular-nums text-black dark:text-white">{{
                                            String(index + 1).padStart(2, '0') }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <template v-if="editingId !== route.id">
                                        <span
                                            class="text-sm tracking-wider uppercase font-medium text-black dark:text-white">
                                            {{ route.title }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        <input v-model="editForm.title"
                                            class="w-full px-3 py-2 bg-white dark:bg-[#111111] border border-[#CCCCCC] dark:border-[#333333] rounded text-sm tracking-wider uppercase text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200" />
                                    </template>
                                </td>

                                <td class="px-6 py-4">
                                    <template v-if="editingId !== route.id">
                                        <code
                                            class="text-sm font-mono text-[#666666] dark:text-[#999999] bg-[#F5F5F5] dark:bg-[#0A0A0A] px-2 py-1 rounded">
                                            {{ route.href }}
                                        </code>
                                    </template>
                                    <template v-else>
                                        <input v-model="editForm.href"
                                            class="w-full px-3 py-2 bg-white dark:bg-[#111111] border border-[#CCCCCC] dark:border-[#333333] rounded text-sm font-mono text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200" />
                                    </template>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <template v-if="editingId === route.id">
                                            <Button @click="updateRoute(route.id)"
                                                class="px-4 py-1.5 bg-[#1A1A1A] dark:bg-[#FFFFFF] text-white dark:text-black rounded-full text-xs tracking-widest uppercase font-bold hover:opacity-80 transition-all duration-200">
                                                UPDATE
                                            </Button>
                                            <Button variant="outline" @click="cancelEditing"
                                                class="px-4 py-1.5 border border-[#CCCCCC] dark:border-[#333333] text-[#666666] dark:text-[#999999] rounded-full text-xs tracking-widest uppercase hover:border-[#1A1A1A] dark:hover:border-[#FFFFFF] hover:text-[#1A1A1A] dark:hover:text-[#FFFFFF] transition-all duration-200">
                                                CANCEL
                                            </Button>
                                        </template>
                                        <template v-else>
                                            <Button variant="outline" @click="startEditing(route)"
                                                :disabled="!isSuperAdmin"
                                                class="px-4 py-1.5 border border-[#CCCCCC] dark:border-[#333333] text-[#666666] dark:text-[#999999] rounded-full text-xs tracking-widest uppercase hover:border-[#1A1A1A] dark:hover:border-[#FFFFFF] hover:text-[#1A1A1A] dark:hover:text-[#FFFFFF] transition-all duration-200">
                                                EDIT
                                            </Button>
                                            <Button variant="destructive" @click="deleteRoute(route.id)"
                                                :disabled="!isSuperAdmin"
                                                class="px-4 py-1.5 bg-[#D71921] text-white rounded-full text-xs tracking-widest uppercase font-bold hover:bg-[#B01419] transition-all duration-200">
                                                DELETE
                                            </Button>
                                        </template>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredRoutes.length === 0 && !adding">
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="flex items-center gap-1">
                                            <div class="w-1.5 h-1.5 bg-[#666666] dark:bg-[#999999] rounded-full"></div>
                                            <div class="w-1 h-1 bg-[#666666] dark:bg-[#999999] rounded-full"></div>
                                            <div class="w-1.5 h-1.5 bg-[#666666] dark:bg-[#999999] rounded-full"></div>
                                        </div>
                                        <p class="text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999]">
                                            NO ROUTES FOUND
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Info -->
                <div class="flex items-center justify-between pt-4 border-t border-[#E8E8E8] dark:border-[#222222]">
                    <div class="flex items-center gap-2">
                        <div class="w-1 h-1 bg-[#D71921] rounded-full"></div>
                        <span class="text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999]">
                            TOTAL: {{ filteredRoutes.length }} {{ filteredRoutes.length === 1 ? 'ROUTE' : 'ROUTES' }}
                        </span>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>