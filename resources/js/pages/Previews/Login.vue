<script setup>
import axios from 'axios';
import { ref } from 'vue';
import Swal from 'sweetalert2';
import { Head } from '@inertiajs/vue3';

// Form data using Vue's reactivity
const form = ref({
    email: '',
    password: '',
    remember: true,
});

const submit = async () => {
    try {
        const response = await axios.post('/preview-login', form.value);
        const redirectTo = response.data.redirect_to || '/';
        window.location.href = redirectTo; // 🔁 Hard redirect to Blade page
    } catch (error) {
        Swal.fire('Login Failed', 'Please check your credentials.', 'error');
    }
};
</script>

<template>

    <Head title="Login to View Preview" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-neutral-100 to-indigo-200 px-4">
        <div class="w-full max-w-md bg-white rounded-xl shadow-xl p-8 space-y-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-800">Sign in</h2>
                <p class="text-sm text-gray-500">Access restricted preview content</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" v-model="form.email" required autofocus
                        class="w-full px-4 py-2 border rounded-2xl text-gray-800 focus:outline-none focus:ring-1 focus:ring-black" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" v-model="form.password" required
                        class="w-full px-4 py-2 border rounded-2xl text-gray-800 focus:outline-none focus:ring-1 focus:ring-black" />
                </div>

                <div class="flex items-center text-sm text-gray-600">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" v-model="form.remember" class="rounded border-gray-300">
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full bg-black text-white border border-gray-900 font-semibold py-2 rounded-xl hover:bg-white hover:border-gray-900 hover:text-black transition-all duration-200 shadow">
                    Login
                </button>
            </form>
        </div>
    </div>
</template>