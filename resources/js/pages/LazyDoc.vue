<template>

    <Head title="Q/A Documentation" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-black dark:via-black dark:to-black">
            <div class="container mx-auto px-4 py-8 max-w-5xl">
                <!-- Header Section -->
                <div class="text-center mb-12">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                            </path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                        Creative Planet Nine
                        <span
                            class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Documentation</span>
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                        Everything you need to know about our preview system, from basics to advanced features.
                    </p>
                </div>

                <!-- Search Section -->
                <div class="relative mb-8">
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
                            class="w-full text-left px-6 py-5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset group">
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
                                            class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border-l-4 border-blue-400">
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
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z">
                            </path>
                        </svg>
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
        answer: 'Creative Planet Nine is a platform where the main focus is to create preview links for clients in such a way that the user can upload Banners, Videos, Gifs and Social Images/Images. We call this the Preview System. In order to make this preview system work there is a heirarchy of users, models and permissions.',
        additionalInfo: `
        Lets try to get you familiar with what you can do in the system. I will try to cover as much as I can on this page. Just like any developer I am lazy. So dont expect too much. <span class="font-bold">Look at the left sidebar whole reading the points.</span>
        <ul class="list-disc list-inside space-y-3 mt-4">
            <li><span class="font-bold text-blue-600">Color Palettes:</span> Add/Edit Colors with tabs(preview page thing) as image upload.</li>
            <li><span class="font-bold text-green-600">Client Handling:</span> Each client has their own color theme. Which comes from Color Palettes.</li>
            <li><span class="font-bold text-purple-600">Banner and Video Sizes:</span> Banners, Videos, Gifs, Social -> we call them creatives. Each creative has their own size. To show each size dynamic on the preview page (which client will see), maintaining Banner sizes and video sizes is necessary.</li>
            <li><span class="font-bold text-orange-600">File Transfers:</span> We create transfer links and provide to our clients so that they can download their creatives and upload them on their end.</li>
            <li><span class="font-bold text-red-600">Bills:</span> This is for Limon Bhai only. The guy who will not just give you life lessions but also take care of you like his own brother/sister. Kindly respect him. If you dont, I will make sure that your self respect gets sold to the highest bidder in LGBTQ++ community and the bidder ends up on your doorstep knocking late at night to make love to you.</li>
            <li><span class="font-bold text-indigo-600">Media Library:</span> Sometimes in video banners we need to provide an external link for the banner to work. This segment will do the part. Of course you can upload other files as well.</li>
            <li><span class="font-bold text-teal-600">TinyPNG:</span> 500 free image compression per month. This was built as a learning curve. Works fine af though.</li>
            <li><span class="font-bold text-pink-600">Preview System:</span> This is the original preview system that started it all. It has evolved over time but the core functionality remains the same. More info is to the other Q/A module.</li>
        </ul>
        `
    },
    {
        question: 'What is a Client?',
        answer: 'Well like the name suggests, client page is to manage the clients. However, there some other things as well. Each Client has their own logo, brand color, url and preview url as well. For example: CMN is a client where the preview url is creative.cmn.com. Another example: for Merkle it will be creative.merkle.com. But its not necessary to have a different preview url for each client. Only if the client requires it then you have to create it.',
        additionalInfo: `
        In case you want to change anything for the client, here are the technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Model:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">Client.php</code></li>
            <li>🗃️ <strong>Migration:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">2025_05_02_135230_create_clients_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">ClientController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">Pages/Clients</code></li>
        </ul>
        `
    },
    {
        question: 'What are Color Palettes?',
        answer: 'Color Palettes are the colors that are used in the preview system. The name does say Color Palette but we are developing a theme. A theme can have multiple colors. Each Color Palette has a name and colors.',
        additionalInfo: `
        In case you want to change anything for the Color Palette, here are the technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Model:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">ColorPalette.php</code></li>
            <li>🗃️ <strong>Migration:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">2025_05_01_082516_create_color_palettes_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">ColorPaletteController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">Pages/ColorPalettes</code></li>
        </ul>
        `
    },
    {
        question: 'What are Banner Sizes and Video Sizes?',
        answer: 'Banner Sizes are the sizes of the banners that are used in the preview system. Video Sizes are the sizes of the videos that are used in the preview system. Each Banner Size has a name, width and height. Each Video Size has a name, width and height as well. The difference between Banner Size and Video Size is that Banner Size is used for banners only. Video Size is used for videos only.',
        additionalInfo: `
        In case you want to change anything for Banner Size or Video Size, here are the technical details:
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border">
                <h5 class="font-bold text-blue-600 mb-2">Banner Sizes</h5>
                <ul class="list-disc list-inside space-y-1 text-sm">
                    <li><strong>Model:</strong> <code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">BannerSize.php</code></li>
                    <li><strong>Migration:</strong> <code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">2025_04_15_070726_create_banner_sizes_table.php</code></li>
                    <li><strong>Controller:</strong> <code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">BannerSizeController.php</code></li>
                    <li><strong>Vue Files:</strong> <code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">Pages/BannerSizes</code></li>
                </ul>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border">
                <h5 class="font-bold text-green-600 mb-2">Video Sizes</h5>
                <ul class="list-disc list-inside space-y-1 text-sm">
                    <li><strong>Model:</strong> <code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">VideoSize.php</code></li>
                    <li><strong>Migration:</strong> <code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">2025_04_17_173143_create_video_sizes_table.php</code></li>
                    <li><strong>Controller:</strong> <code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">VideoSizeController.php</code></li>
                    <li><strong>Vue Files:</strong> <code class="px-1 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">Pages/VideoSizes</code></li>
                </ul>
            </div>
        </div>
        `
    },
    {
        question: 'What are File Transfers?',
        answer: 'File Transfers are the links that are used to transfer files from the server to the client. Each File Transfer has a unique link that can be used to download the files. Making sure that the server stays clean, we have to delete the file transfers after 1 Year 1 Month. But make sure that the files are stored at our <span class="font-bold text-red-600">DIVANAS</span> server.',
        additionalInfo: `
        In case you want to change anything for File Transfer, here are the technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Model:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">FileTransfer.php</code></li>
            <li>🗃️ <strong>Migration:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">2025_05_01_134500_create_file_transfers_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">FileTransferController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">Pages/FileTransfers</code></li>
        </ul>
        `
    },
    {
        question: 'What is Media Library?',
        answer: 'Media Library is a place where you can upload files that are used in the preview system. Each file has a name, url and type. The type can be image, video or other.',
        additionalInfo: `
        In case you want to change anything for Media Library, here are the technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Model:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">Media.php</code></li>
            <li>🗃️ <strong>Migration:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">2025_05_03_114912_create_media_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">MediaController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">Pages/Medias</code></li>
        </ul>
        `
    },
    {
        question: 'What is TinyPNG?',
        answer: 'TinyPNG is a place where you can compress your images. You get 500 free compressions per month. After that you have to pay for it. If the free trial is over then just head to <a href="https://tinypng.com" target="_blank" class="text-blue-500 underline hover:text-blue-700">TinyPNG</a>. Or if you want to use your own API key then you can set it in the .env file. The key is TINYPNG_API_KEY and you can get it from <a href="https://tinypng.com/developers" target="_blank" class="text-blue-500 underline hover:text-blue-700">here</a>.',
        additionalInfo: `
        <div class="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-lg border-l-4 border-yellow-400 mb-4">
            <p class="text-sm"><strong>Note:</strong> There is no Model or Migration for TinyPNG as we are not storing any data in the database plus it's a third party service that works with API key.</p>
        </div>
        In case you want to change anything for TinyPNG, here are the technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>🎛️ <strong>Controller:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">TinyPngController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">Pages/TinyPng</code></li>
            <li>🔑 <strong>ENV Variable:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">TINYPNG_API_KEY</code></li>
        </ul>
        `
    },
    {
        question: 'What is Bills?',
        answer: 'Bills is a place where Limon Bhai can manage his bills. Each bill has a sub bill. And total of sub bills is the total of the bill. This is used mainly to keep the record of bills that is being used in the office.',
        additionalInfo: `
        In case you want to change anything for Bills, here are the technical details:
        <ul class="list-disc list-inside space-y-2 mt-4">
            <li>📄 <strong>Models:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">Bill.php</code>, <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">SubBill.php</code></li>
            <li>🗃️ <strong>Migrations:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">2025_04_21_123650_create_bills_table.php</code>, <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">2025_04_21_123714_create_sub_bills_table.php</code></li>
            <li>🎛️ <strong>Controller:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">BillController.php</code></li>
            <li>🖼️ <strong>Vue Files:</strong> <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm">Pages/Bills</code></li>
        </ul>
        `
    },
    {
        question: 'What is the Preview Mechanism?',
        answer: 'Now, lets dive into the main feature of this app. A versatile system that allows users to create Preview links with additional info and enables the user to upload Banners(zip), Videos, Gifs and Social Images/Images. I tried to make the system user friendly and easy to use.',
        additionalInfo: `
            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg mb-4">
                <h5 class="font-bold text-blue-800 dark:text-blue-300 mb-2">System Hierarchy</h5>
                <p class="text-sm text-blue-700 dark:text-blue-300">Understanding the relationship between different components:</p>
            </div>
            
            <div class="space-y-4">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">1</div>
                    <div>
                        <p>Each <span class="font-bold text-blue-600">Preview</span> is associated with a <span class="font-bold text-green-600">Client</span>, <span class="font-bold text-purple-600">Color Palette</span>, and <span class="font-bold text-orange-600">User</span>.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold">2</div>
                    <div>
                        <p>Each <span class="font-bold text-blue-600">Preview</span> can have multiple <span class="font-bold text-indigo-600">Categories</span> (Banner, Video, Gif, Social).</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center text-xs font-bold">3</div>
                    <div>
                        <p>Each <span class="font-bold text-indigo-600">Category</span> can have multiple <span class="font-bold text-teal-600">Feedbacks</span>. Feedbacks will come from the client or the manager.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-6 h-6 bg-teal-500 text-white rounded-full flex items-center justify-center text-xs font-bold">4</div>
                    <div>
                        <p>Each <span class="font-bold text-teal-600">Feedback</span> can have multiple <span class="font-bold text-pink-600">Feedback Sets</span>.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-6 h-6 bg-pink-500 text-white rounded-full flex items-center justify-center text-xs font-bold">5</div>
                    <div>
                        <p>Each <span class="font-bold text-pink-600">Feedback Set</span> can have multiple <span class="font-bold text-red-600">Versions</span>.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs font-bold">6</div>
                    <div>
                        <p>Each <span class="font-bold text-red-600">Version</span> can have multiple <span class="font-bold text-gray-700 dark:text-gray-300">Creatives</span>. (Depending on the Category type, the creative can be Banner, Video, Gif or Social Image/Image).</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/30 rounded-lg border-l-4 border-green-400">
                <h5 class="font-bold text-green-800 dark:text-green-300 mb-2">Key Features</h5>
                <ul class="list-disc list-inside space-y-1 text-sm text-green-700 dark:text-green-300">
                    <li>User can change the color theme by switching the color palette</li>
                    <li>Each Feedback has its own description that users can view</li>
                    <li>Dynamic preview system adapts to different creative types</li>
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
    padding-left: 1.5rem;
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