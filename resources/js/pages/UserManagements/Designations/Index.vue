<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/pages/UserManagements/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Designations', href: '/user-managements/designations' }];

const page = usePage();
const designations = ref(page.props.designations ?? []);
const search = ref('');
const authUser = computed(() => (page.props.auth as any)?.user);
const isSuperAdmin = computed(() => authUser.value?.role === 'super_admin');

const filteredDesignations = computed(() => {
    const query = search.value.toLowerCase();
    return designations.value.filter((d: any) => d.name.toLowerCase().includes(query));
});

const adding = ref(false);
const newDesignation = ref('');
const editingId = ref<number | null>(null);
const editName = ref('');

const startAdding = () => {
    adding.value = true;
    newDesignation.value = '';
};

const cancelAdding = () => {
    adding.value = false;
};

const saveDesignation = () => {
    if (!newDesignation.value.trim()) return;

    router.post(route('user-managements-designations-create-post'), {
        name: newDesignation.value
    }, {
        preserveScroll: true,
        preserveState: false,  // 🛠 important here
        onSuccess: () => {
            Swal.fire('Added!', 'Designation created.', 'success');
            adding.value = false;
            newDesignation.value = '';
        },
        onError: (errors) => {
            if (errors.name) {
                Swal.fire('Error!', errors.name, 'error');
            } else {
                Swal.fire('Error!', 'Something went wrong.', 'error');
            }
        },
    });
};

const startEditing = (designation: any) => {
    editingId.value = designation.id;
    editName.value = designation.name;
};

const cancelEditing = () => {
    editingId.value = null;
    editName.value = '';
};

const updateDesignation = (id: number) => {
    if (!editName.value.trim()) return;

    router.put(
        route('user-managements-designations-update', id),
        { name: editName.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                const index = designations.value.findIndex((d: any) => d.id === id);
                if (index !== -1) {
                    designations.value[index].name = editName.value;
                }
                Swal.fire('Updated!', 'Designation updated.', 'success');
                editingId.value = null;
                editName.value = '';
            },
            onError: (errors) => {
                if (errors.name) {
                    Swal.fire('Error!', errors.name, 'error');
                } else {
                    Swal.fire('Error!', 'Something went wrong.', 'error');
                }
            },
        },
    );
};

const deleteDesignation = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        router.delete(route('user-managements-designations-delete', id), {
            preserveScroll: true,
            onSuccess: () => {
                designations.value = designations.value.filter((d: any) => d.id !== id);
                Swal.fire('Deleted!', 'Designation deleted.', 'success');
            },
            onError: () => Swal.fire('Error!', 'Failed to delete.', 'error'),
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Designations" />
        <SettingsLayout>
            <!-- Nothing Design System -->
            <div class="space-y-6 font-mono">
                <!-- Header Section -->
                <div class="flex flex-col items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 bg-[#D71921] rounded-full animate-pulse"></div>
                        <h2 class="text-2xl font-light tracking-widest uppercase text-black dark:text-white">
                            DESIGNATIONS
                        </h2>
                    </div>
                    <p class="text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999] mt-2">
                        MANAGE · SYSTEM · ROLES
                    </p>
                </div>

                <!-- Search & Add Section -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <div class="relative flex-1 max-w-md">
                        <input v-model="search" placeholder="SEARCH..."
                            class="w-full px-4 py-3 bg-white dark:bg-white border border-[#E8E8E8] dark:border-[#222222] rounded-full text-sm tracking-wider uppercase placeholder:text-[#CCCCCC] dark:placeholder:text-[#333333] text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200" />
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 w-1.5 h-1.5 bg-[#D71921] rounded-full">
                        </div>
                    </div>
                    <Button v-if="!adding" @click="startAdding" :disabled="!isSuperAdmin"
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
                                    DESIGNATION NAME
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
                                    <input v-model="newDesignation" placeholder="ENTER DESIGNATION NAME..."
                                        class="w-full px-3 py-2 bg-white dark:bg-[#111111] border border-[#CCCCCC] dark:border-[#333333] rounded text-sm tracking-wider uppercase placeholder:text-[#CCCCCC] dark:placeholder:text-[#333333] text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200"
                                        autofocus />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-3">
                                        <Button @click="saveDesignation"
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
                            <tr v-for="(designation, index) in filteredDesignations" :key="designation.id"
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
                                    <template v-if="editingId !== designation.id">
                                        <span
                                            class="text-sm tracking-wider uppercase font-medium text-black dark:text-white">
                                            {{ designation.name }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        <input v-model="editName"
                                            class="w-full px-3 py-2 bg-white dark:bg-[#111111] border border-[#CCCCCC] dark:border-[#333333] rounded text-sm tracking-wider uppercase text-black dark:text-white focus:outline-none focus:border-[#1A1A1A] dark:focus:border-[#FFFFFF] transition-all duration-200" />
                                    </template>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <template v-if="editingId === designation.id">
                                            <Button @click="updateDesignation(designation.id)"
                                                class="px-4 py-1.5 bg-[#1A1A1A] dark:bg-[#FFFFFF] text-white dark:text-black rounded-full text-xs tracking-widest uppercase font-bold hover:opacity-80 transition-all duration-200">
                                                UPDATE
                                            </Button>
                                            <Button variant="outline" @click="cancelEditing"
                                                class="px-4 py-1.5 border border-[#CCCCCC] dark:border-[#333333] text-[#666666] dark:text-[#999999] rounded-full text-xs tracking-widest uppercase hover:border-[#1A1A1A] dark:hover:border-[#FFFFFF] hover:text-[#1A1A1A] dark:hover:text-[#FFFFFF] transition-all duration-200">
                                                CANCEL
                                            </Button>
                                        </template>
                                        <template v-else>
                                            <Button variant="outline" @click="startEditing(designation)"
                                                :disabled="!isSuperAdmin"
                                                class="px-4 py-1.5 border border-[#CCCCCC] dark:border-[#333333] text-[#666666] dark:text-[#999999] rounded-full text-xs tracking-widest uppercase hover:border-[#1A1A1A] dark:hover:border-[#FFFFFF] hover:text-[#1A1A1A] dark:hover:text-[#FFFFFF] transition-all duration-200">
                                                EDIT
                                            </Button>
                                            <Button variant="destructive" @click="deleteDesignation(designation.id)"
                                                :disabled="!isSuperAdmin"
                                                class="px-4 py-1.5 bg-[#D71921] text-white rounded-full text-xs tracking-widest uppercase font-bold hover:bg-[#B01419] transition-all duration-200">
                                                DELETE
                                            </Button>
                                        </template>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredDesignations.length === 0 && !adding">
                                <td colspan="3" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="flex items-center gap-1">
                                            <div class="w-1.5 h-1.5 bg-[#666666] dark:bg-[#999999] rounded-full"></div>
                                            <div class="w-1 h-1 bg-[#666666] dark:bg-[#999999] rounded-full"></div>
                                            <div class="w-1.5 h-1.5 bg-[#666666] dark:bg-[#999999] rounded-full"></div>
                                        </div>
                                        <p class="text-xs tracking-widest uppercase text-[#666666] dark:text-[#999999]">
                                            NO DESIGNATIONS FOUND
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
                            TOTAL: {{ filteredDesignations.length }} {{ filteredDesignations.length === 1 ? 'ITEM' :
                                'ITEMS' }}
                        </span>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
