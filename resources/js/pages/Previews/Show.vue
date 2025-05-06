<template>
    <div :style="{ backgroundColor: quaternary }" class="min-h-screen w-full">
        <!-- HEADER -->
        <header :style="{ backgroundColor: secondary }" class="py-6 text-center">
            <img v-if="preview.client?.logo" :src="`/logos/${preview.client.logo}`" alt="Client Logo" class="mx-auto h-16" />
            <h1 class="text-3xl font-bold text-white mt-2">{{ preview.name }}</h1>
            <p class="text-white text-sm">Created at: {{ formattedDate }}</p>
        </header>

        <!-- SUBVERSION TABS -->
        <div class="flex justify-center space-x-2 py-4 border-b border-gray-200">
            <button v-for="tab in activeVersion?.sub_versions || []" :key="tab.id" @click="activeSubVersionId = tab.id"
                :class="[
                    'px-4 py-2 rounded font-semibold text-sm',
                    tab.id === activeSubVersionId ? 'bg-white text-black' : 'bg-gray-100 text-gray-500'
                ]">
                {{ tab.name }}
            </button>
        </div>

        <!-- MIDDLE PART -->
        <main :style="{ backgroundColor: tertiary }" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- LEFT VERSION LIST -->
            <aside class="md:col-span-1 border p-4 rounded bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="font-bold">Versions</h2>
                    <button @click="openModal = true" class="text-indigo-600">+</button>
                </div>
                <ul class="space-y-2">
                    <li v-for="v in preview.versions" :key="v.id" @click="setActiveVersion(v)" :class="[
                        'block px-4 py-2 rounded cursor-pointer',
                        v.id === activeVersion?.id ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800'
                    ]">
                        {{ v.name }}
                    </li>
                </ul>
            </aside>

            <!-- RIGHT CREATIVE VIEWER -->
            <section class="md:col-span-3 border rounded p-4 bg-white">
                <!-- You can use BannerView/VideoView/etc here based on activeVersion.type -->
                <component :is="getViewerComponent" :version="activeVersion" :subVersionId="activeSubVersionId" />
            </section>
        </main>

        <!-- FOOTER -->
        <footer :style="{ backgroundColor: quaternary }" class="py-6 text-center text-white">
            <p>Planet Nine - Creative Preview Viewer</p>
        </footer>

        <!-- AddVersionModal -->
        <AddVersionModal v-if="openModal" @close="openModal = false" :preview-id="preview.id" />
    </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
// import AddVersionModal from './Modals/AddVersionModal.vue';
import BannerView from './Mains/BannerView.vue';
import VideoView from './Mains/VideoView.vue';
import SocialView from './Mains/SocialView.vue';
import GifView from './Mains/GifView.vue';
import { usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';

const page = usePage();
const preview = page.props.preview;

console.log(preview);

const openModal = ref(false);
const activeVersion = ref(null);
const activeSubVersionId = ref(null);

const formattedDate = computed(() => dayjs(preview.created_at).format('MMMM D, YYYY'));

const secondary = computed(() => preview.color_palette?.secondary || '#111827');
const tertiary = computed(() => preview.color_palette?.tertiary || '#F3F4F6');
const quaternary = computed(() => preview.color_palette?.quaternary || '#1F2937');

const setActiveVersion = (v) => {
    activeVersion.value = v;
    const activeSub = v.sub_versions.find((sv) => sv.is_active);
    activeSubVersionId.value = activeSub ? activeSub.id : v.sub_versions[0]?.id;
};

const getViewerComponent = computed(() => {
    switch (activeVersion.value?.type) {
        case 'banner':
            return BannerView;
        case 'video':
            return VideoView;
        case 'social':
            return SocialView;
        case 'gif':
            return GifView;
        default:
            return BannerView;
    }
});

onMounted(() => {
    const initial = preview.versions.find((v) => v.is_active) || preview.versions[0];
    if (initial) setActiveVersion(initial);
});
</script>

<style scoped>
/* Optional styling */
</style>