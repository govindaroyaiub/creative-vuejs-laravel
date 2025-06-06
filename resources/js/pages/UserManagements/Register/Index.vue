<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';

// Load designations
const page = usePage();
const designations = ref(page.props.designations ?? []);

// Reactive form
const form = useForm({
    user_id: null,   // ✅ initialize user_id inside the form
    designation: '',
    password: '',
    password_confirmation: '',
});

// On page load, get user_id from URL
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('user');
    if (id) {
        form.user_id = parseInt(id);
    }
});

// Submit registration form
const submit = () => {
    if (!form.user_id) {
        alert('Invalid registration link.');
        return;
    }

    form.post(route('welcome-to-planetnine-register-post'), {
        preserveScroll: true,
        onFinish: () => form.reset('password', 'password_confirmation'),  // reset password fields only
    });
};
</script>

<template>
    <AuthBase title="Create an account" description="Select your designation and set a password to complete your registration.">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">

                <!-- Designation Selection -->
                <div class="grid gap-2">
                    <Label for="designation">Designation</Label>
                    <select
                        id="designation"
                        v-model="form.designation"
                        required
                        class="w-full rounded-md border px-3 py-2 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="" disabled>Select your designation</option>
                        <option v-for="designation in designations" :key="designation.id" :value="designation.id">
                            {{ designation.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.designation" />
                </div>

                <!-- Password -->
                <div class="grid gap-2">
                    <Label for="password">
                        Password
                        <small class="block text-xs text-muted-foreground">(Minimum 8 characters)</small>
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        placeholder="Password"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <!-- Confirm Password -->
                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm Password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        v-model="form.password_confirmation"
                        required
                        placeholder="Confirm Password"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <!-- Submit Button -->
                <Button type="submit" class="mt-2 w-full" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <template v-else>Complete Registration</template>
                </Button>
            </div>
        </form>
    </AuthBase>
</template>