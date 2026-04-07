<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/pages/UserManagements/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: '/user-managements/users' }];

const page = usePage();
const tourGuide = computed(() => page.props.tourGuide);
const isActive = ref(tourGuide.value?.is_active ?? true);
const isUpdating = ref(false);

const toggleStatus = async () => {
    const newStatus = !isActive.value;

    const result = await Swal.fire({
        title: 'Update Tour Guide Status?',
        text: `Tour guide will be ${newStatus ? 'activated' : 'deactivated'} for all preview pages.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: `Yes, ${newStatus ? 'activate' : 'deactivate'} it!`
    });

    if (result.isConfirmed) {
        isUpdating.value = true;

        router.put(route('preview-tour-guide.update'), {
            is_active: newStatus
        }, {
            preserveScroll: true,
            onSuccess: () => {
                isActive.value = newStatus;
                Swal.fire({
                    title: 'Updated!',
                    text: `Tour guide has been ${newStatus ? 'activated' : 'deactivated'}.`,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            onError: () => {
                Swal.fire('Error!', 'Failed to update tour guide status.', 'error');
            },
            onFinish: () => {
                isUpdating.value = false;
            }
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Preview Tour Guide" />
        <SettingsLayout>
            <div class="space-y-6 font-mono">
                <div class="flex flex-col items-start">
                    <h2 class="text-lg font-bold">Preview Tour Guide Permission</h2>
                    <small>Set Active/Inactive status in the Preview page if you want to show the Preview tour guide
                        system.</small>
                </div>

                <!-- Modern Toggle Card -->
                <div
                    class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                                Tour Guide Status
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ isActive ? 'Tour guide is currently enabled for all preview pages' : 'Tour guide is currently disabled for all preview pages' }}
                            </p>
                        </div>

                        <!-- Modern Toggle Switch -->
                        <button @click="toggleStatus" :disabled="isUpdating" type="button" :class="[
                            'relative inline-flex h-10 w-20 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                            isActive ? 'bg-green-600 focus:ring-green-500' : 'bg-gray-300 dark:bg-gray-600 focus:ring-gray-400',
                            isUpdating ? 'opacity-50 cursor-not-allowed' : ''
                        ]" :aria-checked="isActive" role="switch">
                            <span class="sr-only">Toggle tour guide</span>
                            <span :class="[
                                'pointer-events-none relative inline-block h-9 w-9 transform rounded-full bg-white shadow ring-0 transition duration-300 ease-in-out',
                                isActive ? 'translate-x-10' : 'translate-x-0'
                            ]">
                                <!-- Loading spinner when updating -->
                                <span v-if="isUpdating"
                                    class="absolute inset-0 flex h-full w-full items-center justify-center">
                                    <svg class="h-4 w-4 animate-spin text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </span>
                                <!-- Icon when not updating -->
                                <span v-else :class="[
                                    'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity duration-300',
                                    isActive ? 'opacity-0' : 'opacity-100'
                                ]">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 12 12">
                                        <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <span :class="[
                                    'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity duration-300',
                                    isActive ? 'opacity-100' : 'opacity-0'
                                ]">
                                    <svg class="h-4 w-4 text-green-600" fill="currentColor" viewBox="0 0 12 12">
                                        <path
                                            d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                                    </svg>
                                </span>
                            </span>
                        </button>
                    </div>

                    <!-- Status Badge -->
                    <div class="mt-4 flex items-center">
                        <span :class="[
                            'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                            isActive ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                        ]">
                            <span :class="[
                                'mr-1.5 h-2 w-2 rounded-full',
                                isActive ? 'bg-green-500 animate-pulse' : 'bg-gray-500'
                            ]"></span>
                            {{ isActive ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>