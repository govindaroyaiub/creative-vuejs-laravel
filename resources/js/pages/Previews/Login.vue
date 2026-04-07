<script setup lang="ts">
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

    <div class="min-h-screen flex items-center justify-center bg-[#F5F5F5] dark:bg-black px-4 font-mono">
        <div
            class="w-full max-w-md bg-white dark:bg-[#111111] rounded-lg border-2 border-black dark:border-white p-8 space-y-6">
            <div class="text-center">
                <h2 class="text-2xl font-light uppercase tracking-wide text-black dark:text-white mb-2">SIGN IN</h2>
                <p class="text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">RESTRICTED
                    PREVIEW ACCESS</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label
                        class="block text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">EMAIL</label>
                    <input type="email" v-model="form.email" required autofocus
                        class="w-full px-4 py-2 border border-[#CCCCCC] dark:border-[#333333] rounded bg-[#F5F5F5] dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] focus:outline-none focus:border-black dark:focus:border-white" />
                </div>

                <div>
                    <label
                        class="block text-xs font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">PASSWORD</label>
                    <input type="password" v-model="form.password" required
                        class="w-full px-4 py-2 border border-[#CCCCCC] dark:border-[#333333] rounded bg-[#F5F5F5] dark:bg-black text-[#1A1A1A] dark:text-[#E8E8E8] focus:outline-none focus:border-black dark:focus:border-white" />
                </div>

                <div
                    class="flex items-center text-xs text-[#666666] dark:text-[#999999] font-mono uppercase tracking-wider">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" v-model="form.remember"
                            class="rounded border-[#CCCCCC] dark:border-[#333333]">
                        <span>REMEMBER ME</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full bg-black dark:bg-white text-white dark:text-black border-2 border-black dark:border-white font-mono uppercase tracking-wider py-2 rounded-full hover:bg-white dark:hover:bg-black hover:text-black dark:hover:text-white transition-colors text-xs">
                    LOGIN
                </button>
            </form>
        </div>
    </div>
</template>