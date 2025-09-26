<template>

    <Head title="Q/A Documentation" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-black dark:via-black dark:to-black">
            <div class="container mx-auto px-4 py-8 max-w-5xl">
                <!-- Header Section -->
                <div class="text-center mb-6">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                            </path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-1">
                        Creative Planet Nine
                        <span
                            class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Documentation</span>
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                        Everything you need to know about our preview system, from basics to advanced features. <span
                            class="font-bold bg-gradient-to-r from-blue-600 to-indigo-200 bg-clip-text text-transparent">(At
                            least some of it)-ish</span>
                    </p>
                </div>

                <!-- Search Section -->
                <div class="relative mb-4">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input v-model="search" type="text" placeholder="Search documentation..."
                        class="block w-full pl-10 pr-4 py-4 text-lg border-2 border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 shadow-sm hover:shadow-md" />
                    <div v-if="search" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button @click="search = ''" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Q/A Cards -->
                <div class="space-y-4">
                    <div v-for="(qa, idx) in filteredQA" :key="idx" :id="'qa-' + idx"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-200 hover:shadow-lg hover:border-blue-200 dark:hover:border-blue-700">
                        <button @click="toggle(idx)"
                            class="w-full text-left px-6 py-5 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:ring-inset group">
                            <div class="flex justify-between items-center">
                                <div class="flex items-start space-x-4">
                                    <div
                                        class="flex-shrink-0 w-10 h-10 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 rounded-lg flex items-center justify-center group-hover:from-blue-200 group-hover:to-indigo-200 dark:group-hover:from-blue-800 dark:group-hover:to-indigo-800 transition-colors">
                                        <span class="text-blue-600 dark:text-blue-400 font-bold text-lg">{{ idx + 1
                                        }}</span>
                                    </div>
                                    <div>
                                        <h3
                                            class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                            {{ qa.question }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Click to {{ openIdx ===
                                            idx ? 'collapse' : 'expand' }} answer</p>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center group-hover:bg-blue-100 dark:group-hover:bg-blue-900 transition-colors">
                                        <svg :class="['w-4 h-4 text-gray-600 dark:text-gray-400 transition-transform duration-200', openIdx === idx ? 'rotate-180' : '']"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </button>

                        <transition name="slide-fade">
                            <div v-if="openIdx === idx" class="border-t border-gray-200 dark:border-gray-700">
                                <div class="px-6 py-6 bg-gray-50 dark:bg-gray-900/50">
                                    <div class="prose prose-gray dark:prose-invert max-w-none">
                                        <div v-html="qa.answer"
                                            class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4"></div>
                                        <div v-if="qa.additionalInfo"
                                            class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border-l-4 shadow border-blue-400">
                                            <h4
                                                class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                    </path>
                                                </svg>
                                                Additional Information
                                            </h4>
                                            <div v-html="qa.additionalInfo"
                                                class="text-gray-700 dark:text-gray-300 text-sm"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <!-- No Results -->
                <div v-if="filteredQA.length === 0" class="text-center py-16">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 20a7.962 7.962 0 01-5.207-1.709l-4.086 4.086a1 1 0 01-1.414-1.414l4.086-4.086A7.962 7.962 0 014 12a8 8 0 018-8c.322 0 .64.019.954.057">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No questions found</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Try searching with different keywords</p>
                    <button @click="search = ''"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        Clear Search
                    </button>
                </div>

                <!-- Footer -->
                <div class="mt-16 text-center">
                    <div
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full text-sm font-medium">
                        Made with ❤️ by Govinda Roy
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import type { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Q/A Documentation', href: '/lazyDoc' }];

const search = ref('')
const openIdx = ref<number | null>(null)

const quickNav = ref([
    { name: 'Overview', icon: '🏠', id: 'qa-0' },
    { name: 'Clients', icon: '👥', id: 'qa-1' },
    { name: 'Colors', icon: '🎨', id: 'qa-2' },
    { name: 'Previews', icon: '👁️', id: 'qa-8' }
])

const qaList = ref([
    {
        question: 'What is Creative Planet Nine?',
        answer: 'Creative Planet Nine is a platform designed to generate preview links for clients. It allows uploading Banners, Videos, GIFs, and Social Images (collectively called Creatives). This system is called the Preview System. To manage it, there is a hierarchy of users, models, and permissions.',
        additionalInfo: `
        The system offers multiple features. Please refer to the left sidebar while reading the points:
        <ul class="list-disc list-inside space-y-3 mt-4">
            <li><span class="font-bold text-blue-600">Color Palettes:</span> Manage colors/themes for clients. Supports adding/editing colors and uploading preview images.</li>
            <li><span class="font-bold text-green-600">Client Handling:</span> Each client has a unique color theme (derived from Color Palettes), logo, and preview URL.</li>
            <li><span class="font-bold text-purple-600">Banner and Video Sizes:</span> Creatives (Banners, Videos, GIFs, Socials) require predefined sizes. These sizes are maintained to display previews dynamically.</li>
            <li><span class="font-bold text-orange-600">File Transfers:</span> Generate secure transfer links so clients can download their creatives. Transfers are auto-deleted after a specific period.</li>
            <li><span class="font-bold text-red-600">Bills:</span> Module for managing office bills.</li>
            <li><span class="font-bold text-indigo-600">Media Library:</span> Central place for uploading and referencing assets (images, videos, external links).</li>
            <li><span class="font-bold text-teal-600">TinyPNG:</span> API integration for image compression (500 free compressions/month).</li>
            <li><span class="font-bold text-pink-600">Preview System:</span> The core feature. Enables creation of preview links and uploading creatives. Evolved over time, but core remains the same.</li>
        </ul>

        <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/30 rounded-lg border-l-4 border-green-400 shadow">
            <h5 class="font-bold text-green-800 dark:text-green-300 mb-2">Key Points</h5>
            <ul class="list-disc list-inside space-y-1 text-sm text-green-700 dark:text-green-300">
                <li>Backend: Laravel 12</li>
                <li>Frontend: Vue.js 3</li>
                <li>Scaffolding: Vite.js</li>
                <li>SPA Rendering: Inertia.js</li>
                <li>Creative Assets stored in: <code>public/uploads</code></li>
                <li>File Transfers stored in: <code>public/Transfer Files</code></li>
            </ul>
        </div>
    `
    },
    {
        question: 'What is a Client?',
        answer: 'Clients are the organizations for whom creatives are developed/designed. Each client has its own logo, brand colors, URL, and optionally a dedicated preview URL (e.g., creative.cmn.com or creative.merkle.com).',
        additionalInfo: `
        Technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Model:</strong> <code>Client.php</code></li>
            <li>🗃️ <strong>Migration:</strong> <code>2025_05_02_135230_create_clients_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code>ClientController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code>Pages/Clients</code></li>
        </ul>
    `
    },
    {
        question: 'What are the Color Palettes?',
        answer: 'Color Palettes define themes for the preview system. A theme can include multiple colors and improves the overall interface design. Each Color Palette has a name and color set.',
        additionalInfo: `
        Technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Model:</strong> <code>ColorPalette.php</code></li>
            <li>🗃️ <strong>Migration:</strong> <code>2025_05_01_082516_create_color_palettes_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code>ColorPaletteController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code>Pages/ColorPalettes</code></li>
        </ul>
    `
    },
    {
        question: 'What are Banner Sizes and Video Sizes?',
        answer: 'These define the dimensions of creatives. Banner Sizes are for banners only; Video Sizes are for videos only. Each has a name, width, and height.',
        additionalInfo: `
        Technical details:
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2">Banner Sizes</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>BannerSize.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_04_15_070726_create_banner_sizes_table.php</code></li>
                    <li><strong>Controller:</strong> <code>BannerSizeController.php</code></li>
                    <li><strong>Vue Files:</strong> <code>Pages/BannerSizes</code></li>
                </ul>
            </div>
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-green-600 mb-2">Video Sizes</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>VideoSize.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_04_17_173143_create_video_sizes_table.php</code></li>
                    <li><strong>Controller:</strong> <code>VideoSizeController.php</code></li>
                    <li><strong>Vue Files:</strong> <code>Pages/VideoSizes</code></li>
                </ul>
            </div>
        </div>
    `
    },
    {
        question: 'What are File Transfers?',
        answer: 'File Transfers allow clients to download creatives via secure links. Each transfer has a unique URL and is must be deleted after 1 year and 1 month. Files are to be stored on the <span class="font-bold">DIVANAS</span> server.',
        additionalInfo: `
        Technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Model:</strong> <code>FileTransfer.php</code></li>
            <li>🗃️ <strong>Migration:</strong> <code>2025_05_01_134500_create_file_transfers_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code>FileTransferController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code>Pages/FileTransfers</code></li>
        </ul>
    `
    },
    {
        question: 'What is Media Library?',
        answer: 'The Media Library is a central storage for assets used in previews. Each file has a name, type (image, video, or other), and URL.',
        additionalInfo: `
        Technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Model:</strong> <code>Media.php</code></li>
            <li>🗃️ <strong>Migration:</strong> <code>2025_05_03_114912_create_media_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code>MediaController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code>Pages/Medias</code></li>
        </ul>
    `
    },
    {
        question: 'What is TinyPNG?',
        answer: 'TinyPNG is integrated for image compression (500 free compressions/month). Additional compressions require a paid key. Configure your API key in the .env file (TINYPNG_API_KEY).',
        additionalInfo: `
        Technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>🎛️ <strong>Controller:</strong> <code>TinyPngController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code>Pages/TinyPng</code></li>
            <li>🔑 <strong>ENV Variable:</strong> <code>TINYPNG_API_KEY</code></li>
        </ul>
    `
    },
    {
        question: 'What is Bills?',
        answer: 'Bills module helps manage office expenses. Each Bill can contain multiple Sub Bills, and totals are auto-calculated.',
        additionalInfo: `
        Technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Models:</strong> <code>Bill.php</code>, <code>SubBill.php</code></li>
            <li>🗃️ <strong>Migrations:</strong> <code>2025_04_21_123650_create_bills_table.php</code>, <code>2025_04_21_123714_create_sub_bills_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code>BillController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code>Pages/Bills</code></li>
        </ul>
    `
    },
    {
        question: 'How does the Preview System work and what are the relations?',
        answer: 'The Preview System is the core of Creative Planet Nine. It allows creating preview links, uploading creatives, and managing feedbacks. It follows a hierarchical relationship between entities.',
        additionalInfo: `
        <h5 class="font-bold mb-2">System Hierarchy</h5>
        <ol class="list-decimal list-inside space-y-2">
            <li>Each <strong>Preview</strong> is linked to a <strong>Client</strong>, <strong>Color Palette</strong>, and <strong>User</strong>.</li>
            <li>Each Preview contains multiple <strong>Categories</strong> (Banner, Video, Gif, Social).</li>
            <li>Each Category can have multiple <strong>Feedbacks</strong>.</li>
            <li>Each Feedback can contain multiple <strong>Feedback Sets</strong>.</li>
            <li>Each Feedback Set can contain multiple <strong>Versions</strong>.</li>
            <li>Each Version can contain multiple <strong>Creatives</strong> (Banner, Video, Gif, Social Image).</li>
        </ol>

        <div class="mt-6 p-4 bg-green-50 rounded-lg border-l-4 border-green-400 shadow">
            <h5 class="font-bold mb-2">Key Features</h5>
            <ul class="list-disc list-inside text-sm">
                <li>Dynamic previews adapt to creative type.</li>
                <li>Feedback system supports multiple iterations.</li>
                <li>Color themes can be changed per Preview.</li>
            </ul>
        </div>
    `
    },
    {
        question: 'What are the files related to Preview System?',
        answer: 'The Preview System uses multiple interconnected models, controllers, and migrations. The main Preview has its own Vue page, while related entities are handled inside Update.vue. For better understanding I have serialized each component below.',
        additionalInfo: `
        There are total 6 <span class="font-bold">LAYERS:</span>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <!-- Previews -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">1</span> Preview</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newPreview.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_12_123503_create_new_previews_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newPreviewController.php</code></li>
                    <li><strong>Vue Files:</strong> <code>Pages/Previews</code></li>
                </ul>
            </div>
            <!-- Categories -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">2</span> Categories</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newCategory.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_12_123522_create_new_categories_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newCategoryController.php</code></li>
                </ul>
            </div>
            <!-- Feedback -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">3</span> Feedback</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newFeedback.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_12_123537_create_new_feedback_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newFeedbackController.php</code></li>
                </ul>
            </div>
            <!-- Feedback Set -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">4</span> Feedback Set</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newFeedbackSet.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_12_123556_create_new_feedback_sets_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newFeedbackSetController.php</code></li>
                </ul>
            </div>
            <!-- Versions -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">5</span> Versions</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newVersion.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_12_123611_create_new_versions_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newVersionController.php</code></li>
                </ul>
            </div>
            <!-- Banners -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">6A</span> Banners</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newBanner.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_12_123653_create_new_banners_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newBannerController.php</code></li>
                </ul>
            </div>
            <!-- Videos -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">6B</span> Videos</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newVideo.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_15_120554_create_new_videos_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newVideoController.php</code></li>
                </ul>
            </div>
            <!-- Gifs -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">6C</span> Gifs</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newGif.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_15_120605_create_new_gifs_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newGifController.php</code></li>
                </ul>
            </div>
            <!-- Socials -->
            <div class="p-2 rounded-lg border bg-white text-black dark:bg-black dark:text-white">
                <h5 class="font-bold text-blue-600 mb-2 mt-2"><span class="rounded-full border bg-purple-500 text-white px-3 py-1 dark:bg-white">6D</span> Socials</h5>
                <ul class="list-disc list-inside text-sm">
                    <li><strong>Model:</strong> <code>newSocial.php</code></li>
                    <li><strong>Migration:</strong> <code>2025_08_15_120601_create_new_socials_table.php</code></li>
                    <li><strong>Controller:</strong> <code>newSocialController.php</code></li>
                </ul>
            </div>
        </div>
        <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/30 rounded-lg border-l-4 border-green-400 shadow">
            <h5 class="font-bold text-green-800 dark:text-green-300 mb-2">Key Points</h5>
            <ul class="list-disc list-inside space-y-1 text-sm text-green-700 dark:text-green-300">
                <li>Only Previews has a dedicated Vue view file.</li>
                <li>Other entities are managed within <code>Pages/Previews/Update.vue</code>.</li>
                <li>The Update.vue file allows creating, editing, and deleting all related entities.</li>
                <li>The codebase is complex; consider using AI/code analysis tools for better understanding.</li>
                <li>The reason there are 6 Layers is to show the client a robust preview to differentiate their contents and requirements.</li>
            </ul>
        </div>
    `
    }

])

const filteredQA = computed(() => {
    if (!search.value) return qaList.value
    const s = search.value.toLowerCase()
    return qaList.value.filter(
        qa => qa.question.toLowerCase().includes(s) || qa.answer.toLowerCase().includes(s) || qa.additionalInfo?.toLowerCase().includes(s)
    )
})

function toggle(idx: number) {
    openIdx.value = openIdx.value === idx ? null : idx
}

function scrollToSection(id: string) {
    const element = document.getElementById(id)
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' })
        // Open the section if it's collapsed
        const qaIndex = parseInt(id.split('-')[1])
        if (openIdx.value !== qaIndex) {
            openIdx.value = qaIndex
        }
    }
}
</script>

<style scoped>
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.2s ease-in;
}

.slide-fade-enter-from {
    transform: translateY(-10px);
    opacity: 0;
}

.slide-fade-leave-to {
    transform: translateY(-5px);
    opacity: 0;
}

/* Custom styles for lists */
:deep(ul) {
    list-style-type: disc;
    padding-left: .5rem;
}

:deep(li) {
    margin: 0.5rem 0;
}

:deep(code) {
    background-color: rgba(156, 163, 175, 0.2);
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
}

:deep(.dark code) {
    background-color: rgba(75, 85, 99, 0.5);
}
</style>