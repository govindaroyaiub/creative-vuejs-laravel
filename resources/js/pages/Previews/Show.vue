<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';

const page = usePage();
const preview = computed(() => page.props.preview);
const previewId = computed(() => page.props.preview_id);
const users = computed(() => page.props.users ?? []);

const isLoggedIn = ref(page.props.auth?.user !== null);
const loginEmail = ref('');
const loginPassword = ref('');
const loginError = ref('');

const versions = ref([]);
const subVersions = ref([]);
const banners = ref([]);

const activeVersion = ref(null);
const activeSubVersion = ref(null);
const activePalette = ref(preview.value.color_palette);

const formatDate = (date: string) => dayjs(date).format('DD-MM-YYYY');

const fetchVersions = async () => {
    const res = await axios.get(`/preview/getVersions/${previewId.value}`);
    versions.value = res.data;
    activeVersion.value = versions.value.find(v => v.isActive) || versions.value[0];
    if (activeVersion.value) fetchSubVersions(activeVersion.value.id);
};

const fetchSubVersions = async (versionId: number) => {
    const res = await axios.get(`/preview/getSubVersions/${versionId}`);
    subVersions.value = res.data;
    activeSubVersion.value = subVersions.value.find(sv => sv.isActive) || subVersions.value[0];
    if (activeSubVersion.value) fetchBanners(activeSubVersion.value.id);
};

const fetchBanners = async (subVersionId: number) => {
    const res = await axios.get(`/preview/getBanners/${subVersionId}`);
    banners.value = res.data;
};

const applyPalette = (palette) => {
    activePalette.value = palette;
};

const login = async () => {
    try {
        const res = await axios.post('/preview/login', {
            email: loginEmail.value,
            password: loginPassword.value,
            preview_id: previewId.value,
        });
        if (res.data.success) {
            isLoggedIn.value = true;
            fetchVersions();
        }
    } catch (err) {
        loginError.value = 'Invalid credentials';
    }
};

onMounted(() => {
    if (!preview.value.requires_login) fetchVersions();
});
</script>

<template>

    <Head :title="preview.name" />
    <div class="min-h-screen" :style="{ backgroundColor: activePalette?.secondary || '#f9fafb' }">
        <div v-if="preview.requires_login && !isLoggedIn" class="p-10 max-w-md mx-auto text-center">
            <h2 class="text-xl font-bold mb-4">Login to view preview</h2>
            <input v-model="loginEmail" type="email" placeholder="Email"
                class="w-full mb-3 px-4 py-2 rounded border dark:bg-gray-800 dark:text-white" />
            <input v-model="loginPassword" type="password" placeholder="Password"
                class="w-full mb-3 px-4 py-2 rounded border dark:bg-gray-800 dark:text-white" />
            <button @click="login" class="w-full bg-indigo-600 text-white py-2 rounded">Login</button>
            <div class="text-red-500 mt-2">{{ loginError }}</div>
        </div>

        <div v-else>
            <!-- Header -->
            <div class="text-center py-4">
                <img v-if="preview.show_planetnine_logo" :src="`/logos/${preview.client.logo}`"
                    class="mx-auto w-40 mb-2" />
                <h1 class="text-2xl font-bold">{{ preview.name }}</h1>
                <p>{{ preview.client.name }} • {{ formatDate(preview.created_at) }}</p>
            </div>

            <!-- Palette Switcher -->
            <div class="flex justify-center gap-2 my-4">
                <div v-for="palette in preview.client.color_palette.filter(p => p.status === 1)" :key="palette.id"
                    class="w-6 h-6 rounded-full border cursor-pointer" :style="{ backgroundColor: palette.primary }"
                    @click="applyPalette(palette)" />
            </div>

            <!-- Layout -->
            <div class="flex mx-4 border rounded-lg" :style="{ borderColor: activePalette?.tertiary }">
                <!-- Left: Versions -->
                <div class="w-[280px] p-3 border-r" :style="{ borderColor: activePalette?.tertiary }">
                    <h3 class="text-lg underline mb-2">Versions</h3>
                    <div v-for="v in versions" :key="v.id"
                        class="mb-1 px-3 py-2 rounded cursor-pointer text-center hover:opacity-80"
                        :class="{ 'bg-opacity-50': activeVersion?.id === v.id }" :style="{
                            backgroundColor: activeVersion?.id === v.id ? activePalette?.primary : 'transparent',
                            border: '1px solid ' + (activePalette?.tertiary || '#ccc')
                        }" @click="() => { activeVersion = v; fetchSubVersions(v.id) }">
                        {{ v.name }}
                    </div>
                </div>

                <!-- Right: SubVersions & Assets -->
                <div class="flex-1 p-3">
                    <div class="flex gap-2 mb-4">
                        <div v-for="sv in subVersions" :key="sv.id"
                            class="px-3 py-1 border rounded-full cursor-pointer text-sm"
                            :class="{ 'bg-opacity-60': activeSubVersion?.id === sv.id }" :style="{
                                backgroundColor: activeSubVersion?.id === sv.id ? activePalette?.secondary : 'transparent',
                                borderColor: activePalette?.tertiary || '#ccc'
                            }" @click="() => { activeSubVersion = sv; fetchBanners(sv.id) }">
                            {{ sv.name }}
                        </div>
                    </div>

                    <!-- Assets (Banners only for now) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="banner in banners" :key="banner.id" class="rounded border overflow-hidden shadow"
                            :style="{ borderColor: activePalette?.tertiary }">
                            <img :src="`/banners/${banner.path}`" class="w-full object-contain" />
                            <div class="p-2 text-center text-sm">{{ banner.size }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div v-if="preview.show_footer" class="text-center mt-6 py-4 text-sm text-gray-500">
                © 2025 - Planet Nine. All Rights Reserved.
            </div>
        </div>
    </div>
</template>
