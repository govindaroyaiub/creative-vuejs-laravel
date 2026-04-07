<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    user: User;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6 font-mono">
                <HeadingSmall title="Profile information" description="Update your name and email address" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name"
                            placeholder="Full name" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                            autocomplete="username" placeholder="Email address" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !props.user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link :href="route('verification.send')" method="post" as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:!decoration-current dark:decoration-neutral-500">
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>

                <!-- Additional Profile Details (Read-only) -->
                <div class="space-y-6 pt-6 border-t border-gray-200 dark:border-neutral-700">
                    <HeadingSmall title="Additional details" description="Your account information" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="props.user.role" class="grid gap-2">
                            <Label>Role</Label>
                            <div
                                class="px-3 py-2 bg-neutral-100 dark:bg-neutral-800 rounded-md text-sm text-neutral-900 dark:text-neutral-100">
                                {{ props.user.role }}
                            </div>
                        </div>

                        <div v-if="props.user.designation" class="grid gap-2">
                            <Label>Designation</Label>
                            <div
                                class="px-3 py-2 bg-neutral-100 dark:bg-neutral-800 rounded-md text-sm text-neutral-900 dark:text-neutral-100">
                                {{ props.user.designation.name }}
                            </div>
                        </div>

                        <div v-if="props.user.client" class="grid gap-2">
                            <Label>Client</Label>
                            <div
                                class="px-3 py-2 bg-neutral-100 dark:bg-neutral-800 rounded-md text-sm text-neutral-900 dark:text-neutral-100">
                                {{ props.user.client.name }}
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label>Account Created</Label>
                            <div
                                class="px-3 py-2 bg-neutral-100 dark:bg-neutral-800 rounded-md text-sm text-neutral-900 dark:text-neutral-100">
                                {{ new Date(props.user.created_at).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                }) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
