<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Pencil, Trash2, CirclePlus, Target, PanelTopDashed, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';
import PreviewStepBasicInfo from './Partials/PreviewStepBasicInfo.vue';
import PreviewStepProjectType from './Partials/PreviewStepProjectType.vue';
import CreateBannerForm from './Partials/CreateBannerForm.vue';
import CreateSocialForm from './Partials/CreateSocialForm.vue';
import CreateVideoForm from './Partials/CreateVideoForm.vue';
import CreateGifForm from './Partials/CreateGifForm.vue';

const loading = ref(false);

const page = usePage();
const search = ref('');
const showModal = ref(false);
const step = ref(1);
const transitionDirection = ref<'forward' | 'backward'>('forward');

const previews = computed(() => page.props.previews ?? { data: [], links: [] });
const clients = computed(() => page.props.clients ?? []);
const users = computed(() => page.props.users ?? []);
const colorPalettes = computed(() => page.props.colorPalettes ?? []);
const authUser = computed(() => page.props.auth?.user ?? {});
const bannerSizes = computed(() => page.props.bannerSizes ?? []);
const videoSizes = computed(() => page.props.videoSizes ?? []);

const filteredPreviews = computed(() => {
    const q = search.value.toLowerCase();
    return previews.value.data.filter(
        (p: any) =>
            p.name.toLowerCase().includes(q) ||
            p.client?.name?.toLowerCase().includes(q) ||
            p.uploader?.name?.toLowerCase().includes(q)
    );
});

watch(search, (val) => {
    router.get(route('previews-index'), { search: val }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
});

const formData = ref({
    name: '',
    client_id: '',
    team_ids: [authUser.value.id],
    color_palette_id: '5',
    requires_login: false,
    show_planetnine_logo: true,
    show_sidebar_logo: true,
    show_footer: true,
    type: '',
    version_name: 'Master',
    version_description: 'Master Started',
    subversion_name: 'Version 1',
    subversion_active: true,
    banners: [],
    videos: [],
    socials: [],
    gifs: []
});

const deletePreview = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the preview.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        router.delete(route('previews-delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire('Deleted!', 'Preview deleted successfully.', 'success'),
            onError: () => Swal.fire('Error!', 'Failed to delete preview.', 'error'),
        });
    }
};

const getTypes = (preview: any) => {
    const types = new Set();
    preview.versions?.forEach((v: any) => types.add(v.type));
    return Array.from(types).join(', ');
};

const closeModal = () => {
    showModal.value = false;
    step.value = 1;
    transitionDirection.value = 'forward';
    formData.value = {
        name: '',
        client_id: '',
        team_ids: [authUser.value.id],
        color_palette_id: '5',
        requires_login: false,
        show_planetnine_logo: true,
        show_sidebar_logo: true,
        show_footer: true,
        type: '',
        version_name: 'Master',
        version_description: 'Master Started',
        subversion_name: 'Version 1',
        subversion_active: true,
        banners: [],
        videos: [],
        socials: [],
        gifs: []
    };
};

const submitForm = () => {
    const payload = new FormData();
    payload.append('name', formData.value.name);
    payload.append('client_id', formData.value.client_id);
    payload.append('color_palette_id', formData.value.color_palette_id ?? '');
    payload.append('requires_login', formData.value.requires_login ? '1' : '0');
    payload.append('show_planetnine_logo', formData.value.show_planetnine_logo ? '1' : '0');
    payload.append('show_sidebar_logo', formData.value.show_sidebar_logo ? '1' : '0');
    payload.append('show_footer', formData.value.show_footer ? '1' : '0');
    payload.append('type', formData.value.type);
    payload.append('version_name', formData.value.version_name);
    payload.append('version_description', formData.value.version_description);
    payload.append('subversion_name', formData.value.subversion_name);
    payload.append('subversion_active', formData.value.subversion_active ? '1' : '0');

    formData.value.team_ids.forEach(id => payload.append('team_ids[]', id));

    if (formData.value.type === 'Banner') {
        formData.value.banners.forEach((banner, i) => {
            payload.append(`banners[${i}][file]`, banner.file);
            payload.append(`banners[${i}][size_id]`, banner.size_id);
            payload.append(`banners[${i}][position]`, i);
        });
    }
     if (formData.value.type === 'Social') {
        formData.value.socials.forEach((social, i) => {
            payload.append(`socials[${i}][file]`, social.file);
            payload.append(`socials[${i}][name]`, social.name);
            payload.append(`socials[${i}][position]`, i);
        });
    }
    if (formData.value.type === 'Video') {
        formData.value.videos.forEach((video, i) => {
            payload.append(`videos[${i}][path]`, video.path);
            if (video.companion_banner_path) {
                payload.append(`videos[${i}][companion_banner_path]`, video.companion_banner_path);
            }
            payload.append(`videos[${i}][size_id]`, video.size_id);
            payload.append(`videos[${i}][name]`, video.name);
            payload.append(`videos[${i}][codec]`, video.codec);
            payload.append(`videos[${i}][fps]`, video.fps);
            payload.append(`videos[${i}][position]`, i);
        });
    }
    if (formData.value.type === 'Gif') {
        formData.value.gifs.forEach((gif, i) => {
            payload.append(`gifs[${i}][file]`, gif.file);
            payload.append(`gifs[${i}][size_id]`, gif.size_id);
            payload.append(`gifs[${i}][position]`, i);
        });
    }

    router.post(route('previews-store'), payload, {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire('Success!', 'Preview created successfully.', 'success');
            closeModal();
        },
        onError: (error) => {
            console.log(error);
            Swal.fire('Error!', 'Failed to create preview.', 'error');
        }
    });
};

const handleNextStep = () => {
    transitionDirection.value = 'forward';
    if (step.value === 3) {
        submitForm();
    } else {
        step.value++;
    }
};

const handlePreviousStep = () => {
    transitionDirection.value = 'backward';
    step.value--;
};

const getStepComponent = (step: number) => {
    switch (step) {
        case 1: return PreviewStepBasicInfo;
        case 2: return PreviewStepProjectType;
        case 3:
            if (formData.value.type === 'Banner') return CreateBannerForm;
            if (formData.value.type === 'Video') return CreateVideoForm;
            if (formData.value.type === 'Social') return CreateSocialForm;
            if (formData.value.type === 'Gif') return CreateGifForm;
            return { template: '<div class="text-center text-gray-500">Submitting...</div>' };
        default: return { template: '<div class="text-center text-gray-500">Submitting...</div>' };
    }
};

const stepProps = computed(() => ({
    form: formData.value,
    ...(step.value === 1 && {
        users: users.value,
        clients: clients.value,
        colorPalettes: colorPalettes.value,
        authUser: authUser.value,
    }),
    ...(step.value === 3 && formData.value.type === 'Banner' && {
        bannerSizes: bannerSizes.value,
    }),
    ...(step.value === 3 && formData.value.type === 'Video' && {
        videoSizes: videoSizes.value,
    }),
    ...(step.value === 3 && formData.value.type === 'Gif' && {
        bannerSizes: bannerSizes.value,
    }),
}));
</script>

<template>
    <Head title="Previews" />
    <AppLayout :breadcrumbs="[{ title: 'Previews', href: '/previews' }]">
        <div class="p-6 space-y-6">
            <!-- Search & Create -->
            <div class="flex items-center justify-between">
                <input v-model="search" placeholder="Search..."
                    class="w-full max-w-sm rounded border px-4 py-2 dark:bg-gray-700 dark:text-white" />
                <button @click="showModal = true"
                    class="ml-4 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                    <CirclePlus class="mr-1 inline h-5 w-5" /> Add
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-lg shadow">
                <table class="min-w-full table-auto text-sm text-gray-700 dark:text-gray-200">
                    <thead class="bg-gray-100 dark:bg-gray-700 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-center">#</th>
                            <th class="px-4 py-3 text-left">Name & Client</th>
                            <th class="px-4 py-3 text-center">Team</th>
                            <th class="px-4 py-3 text-center">Uploader</th>
                            <th class="px-4 py-3 text-center">Theme & Types</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="(preview, index) in previews.data" :key="preview.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="text-center px-4 py-3 font-medium">{{ index + 1 }}</td>
                            <td class="px-4 py-3 text-left">
                                <div class="font-semibold capitalize">{{ preview.name }}</div>
                                <div class="text-xs text-gray-500">{{ preview.client?.name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center flex-wrap gap-1">
                                    <span v-for="u in preview.team_users" :key="u.id"
                                        class="inline-block rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700 dark:bg-indigo-800 dark:text-white">
                                        {{ u.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="text-sm">{{ preview.uploader?.name ?? '-' }}</div>
                                <div class="text-xs text-gray-400">{{ new
                                    Date(preview.created_at).toLocaleDateString('en-GB', {
                                        day: '2-digit', month:
                                            'short', year: 'numeric'
                                    }) }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="h-4 w-4 rounded-full border"
                                        :style="{ backgroundColor: preview.color_palette?.primary ?? '#ccc' }"
                                        title="Primary Color"></div>
                                    <span class="text-sm">{{ preview.color_palette?.name ?? '-' }}</span>
                                </div>
                                <div class="text-xs text-gray-400">{{ getTypes(preview) || '-' }}</div>
                            </td>
                            <td class="text-center px-4 py-3 space-x-2">
                                <a :href="route('previews-show', preview.id)"
                                    class="text-green-600 hover:text-green-800" target="_blank" rel="noopener">
                                    <Eye class="inline h-5 w-5" />
                                </a>
                                <Link :href="route('previews-edit', preview.id)"
                                    class="text-blue-600 hover:text-blue-800">
                                <Pencil class="inline h-5 w-5" />
                                </Link>
                                <button @click="deletePreview(preview.id)" class="text-red-600 hover:text-red-800">
                                    <Trash2 class="inline h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filteredPreviews.length === 0">
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No previews
                                found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="previews.data.length > 0 && previews.links.length > 1" class="flex justify-center space-x-2">
                <template v-for="(link, i) in previews.links" :key="i">
                    <a v-if="link.url" :href="link.url" class="rounded border px-4 py-2 text-sm" :class="{
                        'bg-indigo-600 text-white': link.active,
                        'hover:bg-gray-200 dark:hover:bg-gray-700': !link.active
                    }" v-html="link.label" />
                    <span v-else class="rounded border px-4 py-2 text-sm text-gray-400 cursor-not-allowed"
                        v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg w-full max-w-4xl relative overflow-hidden">
                <button class="absolute top-2 right-2 text-gray-400 hover:text-red-500" @click="closeModal">
                    <X class="h-6 w-6" />
                </button>
                <Transition :name="transitionDirection === 'forward' ? 'slide-left' : 'slide-right'" mode="out-in">
                    <component :is="getStepComponent(step)" :key="step" v-bind="stepProps" @next="handleNextStep"
                        @previous="handlePreviousStep" @submit="submitForm" @close="closeModal" />
                </Transition>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
    transition: all 0.3s ease;
}

.slide-left-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.slide-left-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

.slide-right-enter-from {
    opacity: 0;
    transform: translateX(-20px);
}

.slide-right-leave-to {
    opacity: 0;
    transform: translateX(20px);
}
</style>