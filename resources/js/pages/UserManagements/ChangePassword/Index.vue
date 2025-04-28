<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';

// Setup form
const form = useForm({
    user_id: null,
    password: '',
    password_confirmation: '',
});

// Get user ID from URL when mounted
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('user');
    if (id) {
        form.user_id = parseInt(id);
    }
});

// Submit form
const submit = () => {
    if (!form.user_id) {
        alert('Invalid or expired reset link.');
        return;
    }

    form.post(route('change-password-post'), {
        preserveScroll: true,
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase title="Set New Password" description="Secure your account by setting a new password.">
        <Head title="Change Password" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <!-- Password Field -->
                <div class="grid gap-2">
                    <Label for="password">
                        New Password
                        <small class="block text-xs text-muted-foreground">(Minimum 8 characters)</small>
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        placeholder="Enter new password"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <!-- Confirm Password Field -->
                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm New Password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        v-model="form.password_confirmation"
                        required
                        placeholder="Re-enter new password"
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <!-- Submit Button -->
                <Button type="submit" class="mt-2 w-full" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <template v-else>Set Password</template>
                </Button>
            </div>
        </form>
    </AuthBase>
</template>