<template>
    <div class="min-h-screen" :style="{ backgroundColor: preview.color_palette?.primary ?? '#f9fafb' }">
        <div class="container mx-auto py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <img :src="preview.client.logo" alt="Client Logo" class="h-12 w-12 rounded-full object-contain" />
                    <div>
                        <h1 class="text-2xl font-bold" :style="{ color: preview.color_palette?.secondary ?? '#000' }">
                            {{ preview.name }}
                        </h1>
                        <p class="text-sm text-gray-600">
                            Client: {{ preview.client.name }}
                        </p>
                        <a v-if="preview.client.website" :href="preview.client.website"
                            class="text-sm underline text-blue-500" target="_blank">
                            {{ preview.client.website }}
                        </a>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-sm text-gray-500">Uploaded by</p>
                    <p class="font-semibold">{{ preview.uploader.name }}</p>
                </div>
            </div>

            <!-- Color Palette Swatches -->
            <div class="flex items-center space-x-2 mb-6">
                <span v-for="(color, name) in preview.client.color_palette" :key="name"
                    class="w-6 h-6 rounded shadow border" :title="name" :style="{ backgroundColor: color }"></span>
            </div>

            <!-- Team Members -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">Team Members</h2>
                <ul class="list-disc list-inside text-sm text-gray-700">
                    <li v-for="id in preview.team_members" :key="id">
                        {{ getUserName(id) }}
                    </li>
                </ul>
            </div>

            <!-- Versions and SubVersions -->
            <div class="bg-white shadow-md rounded p-6">
                <h2 class="text-xl font-semibold mb-4">Versions</h2>
                <ul>
                    <li v-for="version in versions" :key="version.id" class="mb-2 p-2 rounded border hover:bg-gray-100">
                        <div class="font-medium">{{ version.name }}</div>
                        <div class="text-sm text-gray-600">{{ version.description }}</div>

                        <!-- Show associated subVersions -->
                        <div class="ml-4 mt-2 space-y-1">
                            <div v-for="sub in subVersions.filter(sv => sv.version_id === version.id)" :key="sub.id"
                                class="text-sm">
                                - {{ sub.name }}
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const preview = computed(() => page.props.preview);
const versions = computed(() => page.props.versions ?? []);
const subVersions = computed(() => page.props.subVersions ?? []);
const users = computed(() => page.props.users ?? []); // optional preload

const getUserName = (id) => {
    const user = users.value.find(u => u.id === id);
    return user ? user.name : `User ID ${id}`;
};
</script>