<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import dayjs from 'dayjs';

const page = usePage();

const preview = computed(() => page.props.preview);
const versions = computed(() => page.props.versions ?? []);
const subVersions = computed(() => page.props.subVersions ?? []);
const users = computed(() => page.props.users ?? []); // optional preload
const formatDate = (date: string) => dayjs(date).format('DD-MM-YYYY');

const getUserName = (id) => {
    const user = users.value.find(u => u.id === id);
    return user ? user.name : `User ID ${id}`;
};
</script>

<style>
.outer-border {
    margin: 0 1rem;
    box-shadow: inset 0 0 10px 10px #ffffff;
    border-radius: 90px;
}

.brush-text {
    position: relative;
    z-index: 1;
    font-family: sans-serif;
    color: #1a1a1a;
    font-weight: 600;
    background-image: url('/preview_images/green.png');
    background-repeat: no-repeat;
    background-size: contain;
    background-position: center;
    padding-top: 2rem;
    padding-bottom: 2rem;
    max-width: 30vw;
    margin: auto;
    word-wrap: break-word;
    overflow-wrap: break-word;
    text-align: center;
}

.preview-top-logo {
    width: 200px;
    height: auto;
}

.sidebar-logo {
    width: 180px;
    height: auto;
}

.footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    margin: 0 auto;
}

.tab-container {
    display: flex;
    gap: 0.5rem;
}

.tab {
    position: relative;
    padding: 0.75rem 2rem;
    background-color: #a7c09c;
    color: black;
    font-weight: bold;
    z-index: 0;
    overflow: hidden;
}

.tab::before,
.tab::after {
    content: '';
    position: absolute;
    bottom: 0;
    width: 1.25rem;
    height: 1.25rem;
    background: #eaf5e5;
    z-index: -1;
}

.tab::before {

}

.tab::after {
}

.active-tab {
    background-color: #6a7f56;
    color: #fff;
    z-index: 1;
}
</style>

<template>

    <Head title="Previews" />
    <div class="min-h-screen outer-border" :style="{ backgroundColor: preview.color_palette?.secondary ?? '#f9fafb' }">
        <div class="top-part">
            <div class="brush-text text-center">
                <img :src="`/logos/${preview.client.logo}`" class="mx-auto preview-top-logo mb-2" />
                <p><strong>Client Name:</strong> {{ preview.client.name }}</p>
                <p><strong>Project Name:</strong> {{ preview.name }}</p>
                <p><strong>Date:</strong> {{ formatDate(preview.created_at) }}</p>
            </div>
        </div>

        <br>

        <div class="middle-part mt-4 flex flex-col">
            <div class="subVersion-section tab-container justify-center mx-4">
                <div class="tab active-tab">Master</div>
                <div class="tab">Feedback 1</div>
            </div>
            <div class="version-and-preview-section rounded-lg flex flex-row gap-4 border-2 mx-4"
                :style="{ borderColor: preview.color_palette.tertiary }">
                <div class="version-section flex flex-col justify-center items-center gap-2 border-r-2 text-center p-2 min-w-[300px]"
                    :style="{ borderColor: preview.color_palette.tertiary }">
                    <div class="sidebar-logo flex justify-center">
                        <img :src="`/logos/${preview.client.logo}`" class="mb-2" />
                    </div>
                    <div class="version-list flex flex-col gap-2">
                        <label class="underline text-xl">Creative Showcase</label>
                        <div class="version-name">Version 1</div>
                        <div class="version-name">Version 2</div>
                    </div>
                </div>
                <div class="preview-section">
                    this where the creatives will be shown
                </div>
            </div>
        </div>

        <div class="footer text-center py-4 px-2">
            @2025 - All Rights Reserved to Planet Nine
        </div>
    </div>
</template>
