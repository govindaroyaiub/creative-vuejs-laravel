<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

// Load designations from backend
const page = usePage();
const designations = ref(page.props.designations ?? []);

const form = useForm({
    designation: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase title="Create an account" description="Select your designation and set password to create your account">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">

                <!-- Designation Select -->
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
                        <small class="block text-xs text-muted-foreground">
                            (Minimum 8 characters)
                        </small>
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="1"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <!-- Confirm Password -->
                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <!-- Submit -->
                <Button type="submit" class="mt-2 w-full" tabindex="3" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Create account
                </Button>
            </div>

            <!-- Already have account -->
            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="4">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>