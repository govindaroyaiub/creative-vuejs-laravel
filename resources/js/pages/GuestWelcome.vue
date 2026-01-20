<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const breadcrumbs: any[] = [];
const page = usePage();
const username = computed(() => page.props.value?.username ?? page.props.value?.auth?.user?.name ?? 'Guest');
</script>

<template>

    <Head title="Welcome" />

    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }]">
        <div
            class="min-h-[70vh] flex items-center justify-center bg-gradient-to-b dark:from-slate-900 dark:via-slate-800 dark:to-black py-16">
            <div
                class="relative max-w-2xl w-full mx-4 bg-white/85 dark:bg-slate-900/80 backdrop-blur-sm border border-slate-100 dark:border-slate-700 rounded-3xl shadow-2xl p-12 text-center overflow-hidden">

                <div
                    class="absolute -right-20 -top-20 w-64 h-64 rounded-full bg-gradient-to-tr from-indigo-300 to-sky-200 opacity-30 blur-3xl">
                </div>
                <div
                    class="absolute -left-24 -bottom-24 w-72 h-72 rounded-full bg-gradient-to-br from-rose-100 to-indigo-100 opacity-25 blur-2xl">
                </div>

                <div class="flex items-center justify-center mb-6">
                    <svg class="w-20 h-20 text-indigo-600 drop-shadow-md" viewBox="0 0 64 64"
                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <defs>
                            <linearGradient id="g" x1="0" x2="1">
                                <stop offset="0" stop-color="#6366F1" />
                                <stop offset="1" stop-color="#06B6D4" />
                            </linearGradient>
                        </defs>
                        <path d="M32 4l18 6v12c0 16-10 28-18 32-8-4-18-16-18-32V10L32 4z" fill="url(#g)" />
                        <circle cx="32" cy="26" r="6" fill="#ffffff" opacity="0.95" />
                        <path d="M22 44c2-6 8-8 10-8s8 2 10 8v2H22v-2z" fill="#ffffff" opacity="0.95" />
                    </svg>
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-slate-100 mb-3">Welcome, {{
                    username }}</h1>
                <p class="text-lg text-slate-600 dark:text-slate-300 mb-6">We're happy to have you. For additional
                    access, please contact
                    the Planet Nine team:</p>

                <p class="mt-2 text-sm sm:text-base text-slate-700 dark:text-slate-200 font-medium">taco@planetnine.com
                </p>
                <h2 class="mt-2 mb-2 text-lg text-slate-700 dark:text-slate-200 font-medium">OR</h2>
                <p class="mt-2 text-sm sm:text-base text-slate-700 dark:text-slate-200 font-medium">chris@planetnine.com
                </p>

                <p class="text-sm text-slate-400 dark:text-slate-500 mt-8">Thank you.</p>

                <div class="mt-6 flex items-center justify-center">
                    <button @click="$inertia.post('/logout')"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">Logout</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>