<template>
    <div>

        <Head :title="`Creative - ${preview.name}`">
            <link rel="shortcut icon" href="https://www.planetnine.com/logo/new_favicon.png">
            <link rel="stylesheet" :href="asset('previewcssandjsfiles/css/photoswipe.css')">
            <link :href="asset('previewcssandjsfiles/css/preview5.css')" rel="stylesheet">
        </Head>

        <div v-if="isAuthenticated" class="absolute top-4 right-4 flex items-center space-x-3 z-50">
            <div v-if="authUserClientName === 'Planet Nine'" id="viewerList" class="flex space-x-2">
                <span v-for="viewer in viewers" :key="viewer"
                    class="bg-blue-100 text-blue-900 font-semibold rounded-full px-3 py-1 text-sm shadow-sm"
                    :title="viewer">
                    {{ viewer.trim().charAt(0).toUpperCase() }}
                </span>
            </div>
            <form v-if="preview.requires_login" method="POST" :action="route('preview.logout')"
                id="customPreviewLogoutForm" @submit.prevent="handleLogout">
                <input type="hidden" name="preview_id" :value="preview.id">
                <button type="submit"
                    class="bg-red-500 hover:bg-red-600 text-white text-sm font-medium px-3 py-2 rounded-xl shadow transition cursor-pointer">
                    Logout
                </button>
            </form>
        </div>

        <div id="loaderArea" :style="{ display: isLoading ? 'flex' : 'none' }">
            <span class="loader"></span>
        </div>

        <main class="main">
            <section id="top">
                <div class="px-4 py-4 flex justify-center content text-center relative">
                    <div id="topDetails" class="mt-4" :style="{
                        backgroundImage: `url('/${headerImage}')`,
                        backgroundRepeat: 'no-repeat',
                        backgroundPosition: 'center center'
                    }">
                        <img v-if="preview.show_planetnine_logo" :src="asset(`logos/${headerLogo.logo}`)"
                            id="planetnineLogo" alt="planetnineLogo" style="height: 65px; width: auto; margin: 0 auto;">
                        <h1><span class="font-semibold">Name: </span> <span class="capitalize">{{ preview.name }}</span>
                        </h1>
                        <h1><span class="font-semibold">Client: </span> <span class="capitalize">{{ client.name
                        }}</span></h1>
                        <h1>
                            <span class="font-semibold">Date: </span> <span>{{ formatDate(preview.created_at) }}</span>
                        </h1>
                    </div>
                </div>
            </section>

            <div id="mobilecolorPaletteClick" @click="showColorPaletteOptions2">
                <img :src="`/${rightTabColorPaletteImage}`" alt="palette icon">
                <div id="mobilecolorPaletteSelection" :class="{ visible: isPaletteVisible }">
                    <div v-if="isPaletteVisible" class="color-grid">
                        <div v-for="color in allColors" :key="color.id" class="mobile-color-box"
                            :style="{ backgroundColor: color.hex, borderColor: color.border }" :title="color.name"
                            @click.stop="changeTheme(color.id)">
                        </div>
                    </div>
                </div>
            </div>

            <section id="middle" class="mb-4">
                <div id="showcase-section" class="mx-auto custom-container mt-2">
                    <div class="flex row justify-around items-end" style="min-height: 50px;">
                        <div class="py-2 flex items-end justify-center sidebar-top-desktop content-end">
                            <img v-if="preview.show_sidebar_logo === 1" :src="asset(`logos/${client.logo}`)"
                                alt="clientLogo"
                                style="min-height: 65px; max-height: 90px;width: auto; margin: 0 auto;">
                        </div>
                        <div style="flex: 1;" class="feedbackTabs-parent">
                            <div class="feedbacks relative flex justify-center flex-row">
                                <div class="feedbackTabsContainer">
                                    <div v-for="feedback in feedbacks" :key="feedback.id"
                                        style="display: flex; align-items: center; justify-content: center; flex-direction: column;">
                                        <div :id="`feedbackTab${feedback.id}`"
                                            :class="['feedbackTab', { feedbackTabActive: feedback.is_active === 1 }]"
                                            :style="{
                                                bottom: '-1px',
                                                backgroundImage: `url('/${feedback.is_active === 1 ? feedbackActiveImage : feedbackInactiveImage}')`,
                                                backgroundSize: 'cover',
                                                backgroundPosition: 'center',
                                                backgroundRepeat: 'no-repeat',
                                                position: 'relative',
                                                cursor: 'pointer',
                                                minWidth: '110px',
                                                width: '100%',
                                                maxWidth: '110px',
                                                height: '35px'
                                            }" @click="feedback.is_active !== 1 && updateActiveFeedback(feedback.id)"
                                            @mouseover="feedback.is_active !== 1 && changeFeedbackActiveBackground($event)"
                                            @mouseout="feedback.is_active !== 1 && changeFeedbackInactiveBackground($event)">
                                            <div
                                                style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 0.875rem; font-weight: 500; text-align: center; width: 100%; text-shadow: 1px 1px 2px rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center;">
                                                <span style="text-align: center;">{{ feedback.name }}</span>
                                                <div v-if="feedback.is_approved === 1"
                                                    class="w-2 h-2 bg-green-700 rounded-full border border-white animate-pulse-green"
                                                    style="margin-left: 5px; flex-shrink: 0;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="showcase">
                        <div id="bannershowCustom">
                            <nav role="navigation" class="mobileShowcase">
                                <div id="mobileMenuToggle">
                                    <button id="openMobileMenu" aria-label="Open menu" @click="openMobileMenu">
                                        <i class="fa-solid fa-bars"></i>
                                    </button>
                                </div>
                                <div id="mobileMenu" class="mobile-menu-panel" :class="{ open: isMobileMenuOpen }">
                                    <button id="closeMobileMenu" aria-label="Close menu"
                                        @click="closeMobileMenu">&times;</button>
                                    <div v-if="preview.show_sidebar_logo === 1" class="w-full">
                                        <div class="mb-2 mt-2 px-2 py-2 mx-auto flex justify-center">
                                            <img :src="asset(`logos/${client.logo}`)" alt="clientLogo"
                                                style="width: 180px;">
                                        </div>
                                    </div>
                                    <div class="sidebar-image mx-auto mb-4">
                                        <span>Creative Showcase</span>
                                    </div>
                                    <ul id="mobileCategoryList">
                                        <div v-for="category in categories" :key="category.id"
                                            :class="['category-row', { 'category-active': category.is_active === 1 }]"
                                            @click="category.is_active !== 1 && updateActiveCategory(category.id)">
                                            <span :class="{ 'span-active': category.is_active === 1 }"
                                                style="font-size: 0.85rem;">{{ category.name }}</span>
                                            <hr>
                                            <span class="category-row-date" style="font-size: 0.7rem;">{{
                                                formatCategoryDate(category.created_at) }}</span>
                                        </div>
                                    </ul>
                                </div>
                            </nav>
                            <div class="navbar tabDesktopShowcase" id="navbar">
                                <div v-if="preview.show_sidebar_logo === 1"
                                    class="w-full client-logo-div sidebar-top-tab-mobile">
                                    <div id="clientLogoSection" class="mb-2 mt-2 px-2 py-2 mx-auto">
                                        <img :src="asset(`logos/${client.logo}`)" alt="clientLogo"
                                            style="width: 150px;">
                                    </div>
                                </div>
                                <div class="sidebar-image-div w-full py-2">
                                    <div class="sidebar-image mx-auto">
                                        <span>Creative Showcase</span>
                                    </div>
                                </div>

                                <div id="creative-list2">
                                    <div v-for="category in categories" :key="category.id"
                                        :class="['category-row', { 'category-active': category.is_active === 1 }]"
                                        @click="category.is_active !== 1 && updateActiveCategory(category.id)">
                                        <span :class="{ 'span-active': category.is_active === 1 }"
                                            style="font-size: 0.85rem;">{{ category.name }}</span>
                                        <hr>
                                        <span class="category-row-date" style="font-size: 0.7rem;">{{
                                            formatCategoryDate(category.created_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="right-column">
                                <div
                                    class="justify-center items-center mt-1 py-2 px-2 relative top-0 left-0 right-0 currentTotalFeedbacks">
                                    <span id="feedbackCounter" v-html="feedbackCounterHtml"></span>
                                </div>

                                <div class="feedbackSetsContainer">
                                    <template v-for="feedbackSet in feedbackSets" :key="feedbackSet.id">
                                        <div v-if="feedbackSet.name" class="feedbackSet"
                                            :id="`feedbackSet${feedbackSet.id}`"
                                            style="display: flex; align-items: center; justify-content: space-between;">
                                            <div class="feedbackSetName" style="flex: 1; text-align: center;">
                                                {{ feedbackSet.name }}
                                            </div>
                                        </div>
                                        <div class="versions" :id="`versions${feedbackSet.id}`">
                                            <div v-for="version in feedbackSet.versions" :key="version.id">
                                                <div v-if="version.name" class="version-title"
                                                    style="font-weight: bold;">{{
                                                        version.name }}</div>
                                                <div class="banners-list" :id="`bannersList${version.id}`">
                                                    <!-- Banner content -->
                                                    <template v-if="activeCategory && activeCategory.type === 'banner'">
                                                        <div v-for="(banner, index) in version.banners" :key="banner.id"
                                                            :class="`banner-creatives banner-area-${banner.size.width}-${banner.size.height}`"
                                                            style="display: inline-block; margin-right: 0.5rem; margin-left: 0.5rem; margin-bottom: 2rem;"
                                                            :style="{ width: `${banner.size.width}px` }">
                                                            <div
                                                                style="display: flex; justify-content: space-between; padding: 0; color: black; border-top-left-radius: 5px; border-top-right-radius: 5px;">
                                                                <small
                                                                    style="float: left; font-size: 0.85rem; font-weight: bold;">{{
                                                                        banner.size.width }}x{{ banner.size.height
                                                                    }}</small>
                                                                <small
                                                                    style="float: right; font-size: 0.85rem; font-weight: bold;">{{
                                                                        banner.file_size }}</small>
                                                            </div>
                                                            <iframe v-if="index < 3" class="iframe-banners"
                                                                :src="`/${banner.path}/index.html`"
                                                                :width="banner.size.width" :height="banner.size.height"
                                                                frameBorder="0" scrolling="no" :id="`rel${banner.id}`"
                                                                loading="eager"></iframe>
                                                            <div v-else class="banner-placeholder"
                                                                :data-banner-path="`/${banner.path}/index.html`"
                                                                :data-banner-id="banner.id"
                                                                :data-width="banner.size.width"
                                                                :data-height="banner.size.height" :style="{
                                                                    width: `${banner.size.width}px`,
                                                                    height: `${banner.size.height}px`,
                                                                    background: '#f8f9fa',
                                                                    display: 'flex',
                                                                    alignItems: 'center',
                                                                    justifyContent: 'center',
                                                                    border: '1px solid #dee2e6',
                                                                    cursor: 'pointer',
                                                                    position: 'relative'
                                                                }" @click="loadBanner(banner.id)">
                                                                <div style="text-align: center; color: #6c757d;">
                                                                    <div style="font-size: 14px; margin-bottom: 5px;">
                                                                        Click
                                                                        to Load</div>
                                                                    <div style="font-size: 12px;">Banner Preview</div>
                                                                </div>
                                                                <div class="loading-spinner"
                                                                    style="display: none; border: 2px solid #f3f4f6; border-top: 2px solid #3b82f6; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; position: absolute;">
                                                                </div>
                                                            </div>
                                                            <ul style="display: flex; flex-direction: row;"
                                                                class="previewIcons">
                                                                <li><i :id="`relBt${banner.id}`"
                                                                        @click="reloadBanner(banner.id)"
                                                                        class="fa-solid fa-repeat"
                                                                        style="display: flex; margin-top: 0.5rem; cursor: pointer; font-size:1rem;"></i>
                                                                </li>
                                                                <li v-if="authUserClientName === 'Planet Nine'"
                                                                    class="banner-options">
                                                                    <a :href="`/previews/banner/download/${banner.id}`"><i
                                                                            class="fa-solid fa-download"
                                                                            style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:1rem;"></i></a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </template>

                                                    <!-- Video content -->
                                                    <template v-if="activeCategory && activeCategory.type === 'video'">
                                                        <div v-for="video in version.videos" :key="video.id"
                                                            class="mx-auto mb-8" style="max-width: 100%;">
                                                            <div style="background:transparent; display:flex; justify-content:center;"
                                                                class="mt-2 mb-2 rounded-lg">
                                                                <video :src="`/${video.path}`" controls muted
                                                                    class="block mx-auto rounded-2xl video-preview"
                                                                    style="max-width:70vw; max-height:50vh; min-width: 340px; width:auto; height:auto; background:#000;"
                                                                    controlsList="nodownload noremoteplayback"
                                                                    disablePictureInPicture
                                                                    @loadedmetadata="matchVideoMetaWidth"></video>
                                                            </div>
                                                            <div class="bg-gray-50 text-gray-800 text-sm rounded-2xl p-3 mt-2 w-full video-media-info"
                                                                style="margin:0 auto;">
                                                                <div v-if="authUserClientName === 'Planet Nine'"
                                                                    class="flex gap-4 mb-2 justify-center">
                                                                    <a :href="`/${video.path}`" download
                                                                        title="Download"><i class="fa-solid fa-download"
                                                                            style="display: flex; margin-left: 0.5rem; font-size:20px;"></i></a>
                                                                </div>
                                                                <div
                                                                    class="font-semibold text-base mb-1 underline text-center flex justify-center align-center">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                                        height="24" viewBox="0 0 24 24" fill="none"
                                                                        stroke="currentColor" stroke-width="2"
                                                                        stroke-linecap="round" stroke-linejoin="round"
                                                                        class="lucide lucide-info-icon lucide-info">
                                                                        <circle cx="12" cy="12" r="10" />
                                                                        <path d="M12 16v-4" />
                                                                        <path d="M12 8h.01" />
                                                                    </svg>
                                                                </div>
                                                                <div
                                                                    class="font-semibold text-base mb-1 underline text-center">
                                                                    Media Info</div>
                                                                <div><strong>Resolution:</strong> {{ video.size.width }}
                                                                    x
                                                                    {{ video.size.height }}</div>
                                                                <div><strong>Aspect Ratio:</strong> {{
                                                                    video.aspect_ratio ??
                                                                    '-' }}</div>
                                                                <div><strong>Codec:</strong> {{ video.codec ?? '-' }}
                                                                </div>
                                                                <div><strong>FPS:</strong> {{ video.fps ?? '-' }}</div>
                                                                <div><strong>File Size:</strong> {{ video.file_size ??
                                                                    '-'
                                                                    }}</div>
                                                                <div v-if="video.companion_banner_path"
                                                                    class="mt-2 w-full flex flex-col items-center justify-center">
                                                                    <img :src="`/${video.companion_banner_path}`"
                                                                        alt="Companion Banner"
                                                                        class="rounded border mx-auto"
                                                                        style="max-width:970px;max-height:auto;" />
                                                                    <a v-if="authUserClientName === 'Planet Nine'"
                                                                        :href="`/${video.companion_banner_path}`"
                                                                        download title="Download Companion Banner"
                                                                        class="mt-2 flex items-center gap-1 text-blue-600 hover:text-blue-800">
                                                                        <i class="fa-solid fa-download"
                                                                            style="font-size:18px;"></i>
                                                                        <span class="text-xs">Download Companion
                                                                            Banner</span>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <!-- Social content -->
                                                    <template v-if="activeCategory && activeCategory.type === 'social'">
                                                        <div v-for="social in version.socials" :key="social.id"
                                                            style="display: inline-block; margin: 10px; max-width: 1000px;">
                                                            <img :src="`/${social.path}`" :alt="social.name"
                                                                class="social-preview-img rounded-2xl"
                                                                style="width: 100%; max-width: 1200px; height: auto; object-fit: contain; box-shadow: 0 2px 8px #0001; cursor: pointer; margin-top: 0;"
                                                                @click="openSocialImageModal(`/${social.path}`, social.name)">
                                                            <ul style="display: flex; flex-direction: row; justify-content: left; margin-top: 10px;"
                                                                class="previewIcons">
                                                                <li v-if="authUserClientName === 'Planet Nine'">
                                                                    <a :href="`/${social.path}`"
                                                                        :download="`${social.name}.jpg`">
                                                                        <i class="fa-solid fa-download"
                                                                            style="display: flex; margin-left: 0.5rem; font-size:20px;"></i>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </template>

                                                    <!-- GIF content -->
                                                    <template v-if="activeCategory && activeCategory.type === 'gif'">
                                                        <div v-for="gif in version.gifs" :key="gif.id"
                                                            :class="`banner-creatives banner-area-${gif.size.width}-${gif.size.height}`"
                                                            style="display: inline-block; margin-right: 0.5rem; margin-left: 0.5rem; margin-bottom: 1rem;"
                                                            :style="{ width: `${gif.size.width}px` }">
                                                            <div
                                                                style="display: flex; justify-content: space-between; padding: 0; color: black; border-top-left-radius: 5px; border-top-right-radius: 5px;">
                                                                <small
                                                                    style="float: left; font-size: 0.85rem; font-weight: bold;">{{
                                                                        gif.size.width }}x{{ gif.size.height }}</small>
                                                                <small
                                                                    style="float: right; font-size: 0.85rem; font-weight: bold;">{{
                                                                        gif.file_size }}</small>
                                                            </div>
                                                            <iframe class="iframe-banners" style="margin-top: 2px;"
                                                                :src="`/${gif.path}`" :width="gif.size.width"
                                                                :height="gif.size.height" frameBorder="0" scrolling="no"
                                                                :id="`rel${gif.id}`"></iframe>
                                                            <ul style="display: flex; flex-direction: row;"
                                                                class="previewIcons">
                                                                <li><i :id="`relBt${gif.id}`"
                                                                        @click="reloadBanner(gif.id)"
                                                                        class="fa-solid fa-repeat"
                                                                        style="display: flex; margin-top: 0.5rem; cursor: pointer; font-size:20px;"></i>
                                                                </li>
                                                                <li v-if="authUserClientName === 'Planet Nine'">
                                                                    <a :href="`/${gif.path}`" :download="gif.name"><i
                                                                            class="fa-solid fa-download"
                                                                            style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div id="feedbackClick" @click="showFeedbackDescription">
                                    <img :src="`/${rightTabFeedbackDescriptionImage}`" alt="feedback icon">
                                </div>
                                <div id="feedbackDescription" :class="{ show: isFeedbackDescriptionVisible }">
                                    <div id="feedbackDescriptionUpperpart">
                                        <div class="cursor-pointer" style="float: right; padding: 5px;"
                                            @click.stop="hideFeedbackDescription">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div id="feedbackDescriptionLowerPart">
                                        <label id="feedbackMessage" v-html="feedbackMessage"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="fileTransferSection">
                    <div v-if="fileTransfer" id="fileTransferWidget" class="file-transfer-widget" aria-hidden="false">
                        <div id="fileTransferPanel" class="file-transfer-panel" role="region">
                            aria-label="File transfer">
                            <div class="ft-content">
                                <i class="fa-solid fa-download ft-icon"></i>
                                <div class="ft-text-group">
                                    <div class="ft-title">Files Ready</div>
                                    <div class="ft-subtitle">Download now</div>
                                </div>
                            </div>
                            <a id="fileTransferButton" class="file-transfer-btn"
                                :href="`/file-transfers-view/${fileTransfer.slug}`" target="_blank"
                                rel="noopener noreferrer">
                                <span>Get Files</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        <footer v-if="preview.show_footer" class="footer py-8">
            <div class="container mx-auto px-4 text-center text-base text-gray-600">
                &copy; All Rights Reserved.
                <a href="https://www.planetnine.com" class="underline hover:text-black" target="_blank">
                    Planet Nine
                </a> - {{ currentYear }}
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    preview: Object,
    client: Object,
    primary: String,
    secondary: String,
    tertiary: String,
    quaternary: String,
    quinary: String,
    senary: String,
    septenary: String,
    headerImage: String,
    headerLogo: Object,
    allColors: Array,
    rightTabColorPaletteImage: String,
    rightTabFeedbackDescriptionImage: String,
    feedbackActiveImage: String,
    feedbackInactiveImage: String,
    authUserClientName: String,
    previewId: [String, Number],
    isAuthenticated: Boolean
})

// Set CSS variables immediately (synchronously) so they're available when CSS loads
document.documentElement.style.setProperty('--primary-color', props.primary)
document.documentElement.style.setProperty('--secondary-color', props.secondary)
document.documentElement.style.setProperty('--tertiary-color', props.tertiary)
document.documentElement.style.setProperty('--quaternary-color', props.quaternary)
document.documentElement.style.setProperty('--quinary-color', props.quinary)
document.documentElement.style.setProperty('--senary-color', props.senary)
document.documentElement.style.setProperty('--septenary-color', props.septenary)

// Reactive state
const isLoading = ref(false)
const viewers = ref([])
const isPaletteVisible = ref(false)
const isMobileMenuOpen = ref(false)
const isFeedbackDescriptionVisible = ref(false)
const categories = ref([])
const currentCategoryIndex = ref(0)
const feedbacks = ref([])
const currentFeedbackIndex = ref(0)
const feedbackSets = ref([])
const activeCategory = ref(null)
const fileTransfer = ref(null)
const feedbackMessage = ref('')
const guestName = ref('')

const currentYear = computed(() => new Date().getFullYear())

// Helper functions
const asset = (path) => `/${path}`

const route = (name) => {
    const routes = {
        'preview.logout': '/preview/logout'
    }
    return routes[name] || '/'
}

const loadExternalScript = (src) => {
    return new Promise((resolve, reject) => {
        // Check if script is already loaded
        const existingScript = document.querySelector(`script[src="${src}"]`)
        if (existingScript) {
            resolve()
            return
        }

        const script = document.createElement('script')
        script.src = src
        script.onload = () => resolve()
        script.onerror = () => reject(new Error(`Failed to load script: ${src}`))
        document.head.appendChild(script)
    })
}

const loadExternalScripts = async () => {
    try {
        await loadExternalScript('/previewcssandjsfiles/js/fontawesome.all.min.js')
        await loadExternalScript('/previewcssandjsfiles/js/photoswipe.umd.min.js')
        await loadExternalScript('/previewcssandjsfiles/js/photoswipe-lightbox.umd.min.js')
    } catch (error) {
        console.error('Error loading external scripts:', error)
    }
}

const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

const formatCategoryDate = (dateString) => {
    const date = new Date(dateString)
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const year = date.getFullYear()
    return `${day}-${month}-${year}`
}

// Initialize guest name
const initGuestName = () => {
    let name = localStorage.getItem('guest_name')
    if (!name) {
        name = 'Guest-' + Math.floor(Math.random() * 10000)
        localStorage.setItem('guest_name', name)
    }
    guestName.value = name
}

// Viewer tracking
const trackViewer = () => {
    axios.post('/track-viewer', {
        page_id: props.preview.id,
        guest_name: guestName.value
    })
}

const fetchViewers = () => {
    axios.get(`/get-viewers/${props.preview.id}`)
        .then(response => {
            viewers.value = response.data
        })
}

// Mobile menu
const openMobileMenu = () => {
    isMobileMenuOpen.value = true
    document.body.style.overflow = 'hidden'
    document.addEventListener('click', handleOutsideClick)
}

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false
    document.body.style.overflow = ''
    document.removeEventListener('click', handleOutsideClick)
}

// Color palette
const showColorPaletteOptions2 = () => {
    isPaletteVisible.value = true
    // Add a small delay to prevent immediate closing from the same click
    setTimeout(() => {
        document.addEventListener('click', handleOutsideClick)
    }, 10)
}

const changeTheme = (colorId) => {
    axios.get(`/preview/${props.previewId}/change/theme/${colorId}`)
        .then(response => {
            if (response.data.success) {
                location.reload()
            } else {
                alert("Something went wrong changing theme")
            }
        })
        .catch(error => {
            console.error('Error sending color:', error)
        })
}

// Feedback description
const showFeedbackDescription = () => {
    isFeedbackDescriptionVisible.value = true
    setTimeout(() => {
        document.addEventListener('click', closeFeedbackDescriptionOutside, true)
    }, 100)
}

const hideFeedbackDescription = () => {
    isFeedbackDescriptionVisible.value = false
    document.removeEventListener('click', closeFeedbackDescriptionOutside, true)
}

const closeFeedbackDescriptionOutside = (e) => {
    const feedbackPanel = document.getElementById('feedbackDescription')
    const feedbackClick = document.getElementById('feedbackClick')
    if (feedbackPanel && feedbackClick && !feedbackPanel.contains(e.target) && !feedbackClick.contains(e.target)) {
        hideFeedbackDescription()
    }
}

// Outside click handler
const handleOutsideClick = (event) => {
    const paletteDiv = document.getElementById('mobilecolorPaletteSelection')
    const paletteToggle = document.getElementById('mobilecolorPaletteClick')

    if (paletteDiv && isPaletteVisible.value) {
        if (!paletteDiv.contains(event.target) && !paletteToggle.contains(event.target)) {
            isPaletteVisible.value = false
            document.removeEventListener('click', handleOutsideClick)
            return
        }
    }

    const mobileMenu = document.getElementById('mobileMenu')
    const mobileMenuToggle = document.getElementById('mobileMenuToggle')

    if (mobileMenu && isMobileMenuOpen.value) {
        if (!mobileMenu.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
            closeMobileMenu()
            return
        }
    }
}

// Categories
const renderCategories = () => {
    axios.get(`/preview/renderCategories/${props.previewId}`)
        .then(response => {
            categories.value = response.data.categories || []
            activeCategory.value = response.data.activeCategory
            currentCategoryIndex.value = categories.value.findIndex(c => c.id === response.data.activeCategory.id)
            if (currentCategoryIndex.value === -1) currentCategoryIndex.value = 0

            renderFeedbacks(response)
        })
        .catch(_error => {
            alert('No Category is added yet. Please contact Planet Nine team.')
        })
}

const updateActiveCategory = (categoryId) => {
    axios.get(`/preview/updateActiveCategory/${categoryId}`)
        .then(_response => {
            renderCategories()
        })
        .catch(error => {
            console.log(error)
        })
}

// Feedbacks
const updateActiveFeedback = (feedbackId) => {
    axios.get(`/preview/updateActiveFeedback/${feedbackId}`)
        .then(response => {
            renderFeedbacks(response)
        })
        .catch(error => {
            console.log(error)
        })
}

const renderFeedbacks = (response) => {
    feedbacks.value = response.data.feedbacks || []
    fileTransfer.value = response.data.fileTransfer || null

    currentFeedbackIndex.value = feedbacks.value.findIndex(f => f.is_active === 1)
    if (currentFeedbackIndex.value === -1) currentFeedbackIndex.value = 0

    const activeFeedback = feedbacks.value.find(f => f.is_active === 1)
    if (activeFeedback) {
        feedbackMessage.value = activeFeedback.description
    }

    updateFeedbackNav()
    renderFeedbackSets(response)

    nextTick(() => {
        enableFeedbackTabsDragScroll()
        scrollActiveFeedbackTabIntoView()
    })
}

const changeFeedbackActiveBackground = (event) => {
    const element = event.currentTarget
    if (!element.classList.contains('feedbackTabActive')) {
        element.style.backgroundImage = `url('/${props.feedbackActiveImage}')`
    }
}

const changeFeedbackInactiveBackground = (event) => {
    const element = event.currentTarget
    if (!element.classList.contains('feedbackTabActive')) {
        element.style.backgroundImage = `url('/${props.feedbackInactiveImage}')`
    }
}

const feedbackCounterHtml = ref('')

const updateFeedbackNav = () => {
    const total = feedbacks.value.length

    if (total === 0) {
        feedbackCounterHtml.value = 'No Feedbacks'
        return
    }

    const current = currentFeedbackIndex.value + 1
    const isFirst = currentFeedbackIndex.value === 0
    const isLast = currentFeedbackIndex.value === total - 1

    const btn = (id, symbol, disabled = false) =>
        `<button id="${id}" ${disabled ? 'disabled' : ''} style="margin:0 0.5rem"><span class="font-bold">${symbol}</span></button>`

    const span = (text, selected = false) =>
        `<span${selected ? ' class="font-bold selectedFeedback"' : ''}>${selected ? `Feedback ${text}` : text}</span>`

    let row = ''

    if (total === 2) {
        if (isFirst) {
            row = span(current, true) + btn('feedbackRight', '>', true) + span(current + 1)
        } else {
            row = span(current - 1) + btn('feedbackLeft', '<') + span(current, true)
        }
    } else if (total === 3) {
        if (isFirst) {
            row = span(current, true) + btn('feedbackRight', '>', true) + span(current + 1) + btn('feedbackFarRight', '>>') + span(total)
        } else if (currentFeedbackIndex.value === 1) {
            row = span(1) + btn('feedbackLeft', '<<', true) + span(current, true) + btn('feedbackFarRight', '>>') + span(total)
        } else {
            row = span(1) + btn('feedbackFarLeft', '<<') + span(current - 1) + btn('feedbackLeft', '<', true) + span(current, true)
        }
    } else if (total > 3) {
        if (isFirst) {
            row = span(current, true) + btn('feedbackRight', '>', true) + span(current + 1) + btn('feedbackFarRight', '>>') + span(total)
        } else if (isLast) {
            row = span(1) + btn('feedbackFarLeft', '<<') + span(current - 1) + btn('feedbackLeft', '<', true) + span(current, true)
        } else if (current === 2) {
            row = span(1) + btn('feedbackLeft', '<', true) + span(current, true) + btn('feedbackRight', '>', true) + span(current + 1) + btn('feedbackFarRight', '>>') + span(total)
        } else if (current === total - 1) {
            row = span(1) + btn('feedbackFarLeft', '<<') + span(current - 1) + btn('feedbackLeft', '<', true) + span(current, true) + btn('feedbackRight', '>', true) + span(total)
        } else {
            row = span(1) + btn('feedbackFarLeft', '<<') + span(current - 1) + btn('feedbackLeft', '<', true) + span(current, true) + btn('feedbackRight', '>', true) + span(current + 1) + btn('feedbackFarRight', '>>') + span(total)
        }
    }

    feedbackCounterHtml.value = row

    nextTick(() => {
        const feedbackLeft = document.getElementById('feedbackLeft')
        const feedbackRight = document.getElementById('feedbackRight')
        const feedbackFarLeft = document.getElementById('feedbackFarLeft')
        const feedbackFarRight = document.getElementById('feedbackFarRight')

        if (feedbackLeft) {
            feedbackLeft.disabled = isFirst
            feedbackLeft.style.opacity = isFirst ? '0.5' : '1'
            feedbackLeft.onclick = navigateFeedbackLeft
        }
        if (feedbackRight) {
            feedbackRight.disabled = isLast
            feedbackRight.style.opacity = isLast ? '0.5' : '1'
            feedbackRight.onclick = navigateFeedbackRight
        }
        if (feedbackFarLeft) {
            feedbackFarLeft.onclick = navigateFeedbackFarLeft
        }
        if (feedbackFarRight) {
            feedbackFarRight.onclick = navigateFeedbackFarRight
        }
    })
}

const navigateFeedbackFarLeft = () => {
    if (currentFeedbackIndex.value > 0) {
        currentFeedbackIndex.value = 0
        updateActiveFeedback(feedbacks.value[0].id)
        nextTick(() => {
            updateFeedbackNav()
            scrollActiveFeedbackTabIntoView()
        })
    }
}

const navigateFeedbackLeft = () => {
    if (currentFeedbackIndex.value > 0) {
        currentFeedbackIndex.value--
        updateActiveFeedback(feedbacks.value[currentFeedbackIndex.value].id)
        nextTick(() => {
            updateFeedbackNav()
            scrollActiveFeedbackTabIntoView()
        })
    }
}

const navigateFeedbackRight = () => {
    if (currentFeedbackIndex.value < feedbacks.value.length - 1) {
        currentFeedbackIndex.value++
        updateActiveFeedback(feedbacks.value[currentFeedbackIndex.value].id)
        nextTick(() => {
            updateFeedbackNav()
            scrollActiveFeedbackTabIntoView()
        })
    }
}

const navigateFeedbackFarRight = () => {
    if (currentFeedbackIndex.value < feedbacks.value.length - 1) {
        currentFeedbackIndex.value = feedbacks.value.length - 1
        updateActiveFeedback(feedbacks.value[currentFeedbackIndex.value].id)
        nextTick(() => {
            updateFeedbackNav()
            scrollActiveFeedbackTabIntoView()
        })
    }
}

const scrollActiveFeedbackTabIntoView = () => {
    const container = document.querySelector('.feedbackTabsContainer')
    if (!container) return
    const activeTab = container.querySelector('.feedbackTabActive')
    if (activeTab) {
        const containerRect = container.getBoundingClientRect()
        const tabRect = activeTab.getBoundingClientRect()
        const offset = tabRect.left - containerRect.left - (containerRect.width / 2) + (tabRect.width / 2)
        container.scrollBy({
            left: offset,
            behavior: 'smooth'
        })
    }
}

const enableFeedbackTabsDragScroll = () => {
    const container = document.querySelector('.feedbackTabsContainer')
    if (!container) return

    if (container.scrollWidth > container.clientWidth) {
        container.style.justifyContent = 'flex-start'
        const rightColumn = document.querySelector('.right-column')
        if (rightColumn) rightColumn.style.borderTopRightRadius = '0px'
    } else {
        container.style.justifyContent = 'center'
        const rightColumn = document.querySelector('.right-column')
        if (rightColumn) rightColumn.style.borderTopRightRadius = '15px'
    }

    if (container.scrollWidth <= container.clientWidth) return

    let isDown = false
    let startX
    let scrollLeft

    container.addEventListener('mousedown', (e) => {
        isDown = true
        container.classList.add('dragging')
        startX = e.pageX - container.offsetLeft
        scrollLeft = container.scrollLeft
        e.preventDefault()
    })

    container.addEventListener('mouseleave', () => {
        isDown = false
        container.classList.remove('dragging')
    })

    container.addEventListener('mouseup', () => {
        isDown = false
        container.classList.remove('dragging')
    })

    container.addEventListener('mousemove', (e) => {
        if (!isDown) return
        const x = e.pageX - container.offsetLeft
        const walk = (x - startX)
        container.scrollLeft = scrollLeft - walk
    })
}

// Feedback sets
const renderFeedbackSets = (response) => {
    feedbackSets.value = response.data.feedbackSets || []

    feedbackSets.value.forEach(set => {
        renderVersions(set.id, response)
    })
}

const renderVersions = (feedbackSetId, res) => {
    axios.get(`/preview/renderVersions/${feedbackSetId}`)
        .then(response => {
            const versions = response.data.versions || []
            const feedbackSet = feedbackSets.value.find(s => s.id === feedbackSetId)
            if (feedbackSet) {
                feedbackSet.versions = versions

                versions.forEach(version => {
                    if (res.data.activeCategory.type === 'banner') {
                        renderBanners(version.id, version)
                    }
                    if (res.data.activeCategory.type === 'video') {
                        renderVideo(version.id, version)
                    }
                    if (res.data.activeCategory.type === 'social') {
                        renderSocial(version.id, version)
                    }
                    if (res.data.activeCategory.type === 'gif') {
                        renderGif(version.id, version)
                    }
                })
            }
        })
}

const renderBanners = (versionId, version) => {
    isLoading.value = true
    const versionsEl = document.querySelector('.versions')
    if (versionsEl) versionsEl.style.flexDirection = 'row'

    axios.get(`/preview/renderBanners/${versionId}`)
        .then(response => {
            const banners = response.data.banners || []
            version.banners = banners

            nextTick(() => {
                initializeBannerLazyLoading()
            })
        })
        .catch(error => {
            console.log(error)
        })
        .finally(() => {
            setTimeout(() => {
                isLoading.value = false
            }, 1000)
        })
}

const loadBanner = (bannerId) => {
    const placeholder = document.querySelector(`.banner-placeholder[data-banner-id="${bannerId}"]`)
    if (!placeholder || placeholder.nextElementSibling?.tagName === 'IFRAME') return

    const bannerPath = placeholder.getAttribute('data-banner-path')
    const width = placeholder.getAttribute('data-width')
    const height = placeholder.getAttribute('data-height')

    const loadingSpinner = placeholder.querySelector('.loading-spinner')
    if (loadingSpinner) loadingSpinner.style.display = 'block'
    const textDiv = placeholder.querySelector('div:first-child')
    if (textDiv) textDiv.style.display = 'none'

    const iframe = document.createElement('iframe')
    iframe.className = 'iframe-banners'
    iframe.src = bannerPath
    iframe.width = width
    iframe.height = height
    iframe.frameBorder = 0
    iframe.scrolling = 'no'
    iframe.id = 'rel' + bannerId
    iframe.loading = 'lazy'

    iframe.onload = () => {
        placeholder.style.display = 'none'
    }

    iframe.onerror = () => {
        if (loadingSpinner) loadingSpinner.style.display = 'none'
        if (textDiv) {
            textDiv.style.display = 'block'
            textDiv.innerHTML = '<div style="color: #dc3545; font-size: 12px;">Failed to load</div>'
        }
    }

    placeholder.parentNode.insertBefore(iframe, placeholder.nextSibling)
}

const initializeBannerLazyLoading = () => {
    const placeholders = document.querySelectorAll('.banner-placeholder')
    placeholders.forEach(placeholder => {
        placeholder.addEventListener('click', () => {
            const bannerId = placeholder.getAttribute('data-banner-id')
            loadBanner(bannerId)
        })
    })

    if ('IntersectionObserver' in window) {
        const bannerObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const placeholder = entry.target
                    const bannerId = placeholder.getAttribute('data-banner-id')

                    setTimeout(() => {
                        loadBanner(bannerId)
                        bannerObserver.unobserve(entry.target)
                    }, 100)
                }
            })
        }, {
            root: null,
            rootMargin: '100px',
            threshold: 0.1
        })

        placeholders.forEach(placeholder => {
            bannerObserver.observe(placeholder)
        })
    } else {
        setTimeout(() => {
            placeholders.forEach(placeholder => {
                const bannerId = placeholder.getAttribute('data-banner-id')
                loadBanner(bannerId)
            })
        }, 2000)
    }
}

const reloadBanner = (bannerId) => {
    const iframe = document.getElementById('rel' + bannerId)
    if (iframe) {
        iframe.src = iframe.src
    }
}

const renderVideo = (versionId, version) => {
    isLoading.value = true
    const versionsEl = document.querySelector('.versions')
    if (versionsEl) versionsEl.style.flexDirection = 'column'

    axios.get(`/preview/renderVideos/${versionId}`)
        .then(response => {
            version.videos = response.data.videos || []
            nextTick(() => {
                const bannersListEl = document.querySelector('.banners-list')
                if (bannersListEl) bannersListEl.style.flexDirection = 'column'
            })
        })
        .catch(error => {
            console.log(error)
        })
        .finally(() => {
            setTimeout(() => {
                isLoading.value = false
            }, 1000)
        })
}

const matchVideoMetaWidth = (event) => {
    setTimeout(() => {
        const video = event.target
        const width = video.offsetWidth
        const container = video.closest('.mb-8')
        if (container) {
            const nameBar = container.querySelector('.video-name-bar')
            const mediaInfo = container.querySelector('.video-media-info')
            if (nameBar) nameBar.style.width = width + 'px'
            if (mediaInfo) mediaInfo.style.width = width + 'px'
        }
    }, 50)
}

const renderSocial = (versionId, version) => {
    isLoading.value = true
    const versionsEl = document.querySelector('.versions')
    if (versionsEl) versionsEl.style.flexDirection = 'column'

    axios.get(`/preview/renderSocials/${versionId}`)
        .then(response => {
            version.socials = response.data.socials || []
        })
        .catch(error => {
            console.log(error)
        })
        .finally(() => {
            setTimeout(() => {
                isLoading.value = false
            }, 1000)
        })
}

const openSocialImageModal = (src, label) => {
    const img = new Image()
    img.onload = function () {
        if (typeof PhotoSwipeLightbox !== 'undefined' && typeof PhotoSwipe !== 'undefined') {
            const lightbox = new PhotoSwipeLightbox({
                dataSource: [{
                    src: src,
                    alt: label,
                    w: img.naturalWidth,
                    h: img.naturalHeight
                }],
                pswpModule: PhotoSwipe,
                showHideAnimationType: 'zoom',
                zoomAnimationDuration: 300,
                bgOpacity: 0.85,
                spacing: 0.1,
                allowPanToNext: false,
                zoom: true,
                close: true,
                arrowKeys: true,
                returnFocus: true,
                escKey: true,
                clickToCloseNonZoomable: true,
                imageClickAction: 'zoom',
                tapAction: 'zoom',
                doubleTapAction: 'zoom',
                indexIndicatorSep: ' of ',
                preloaderDelay: 2000,
                errorMsg: 'The image could not be loaded'
            })

            lightbox.init()
            lightbox.loadAndOpen(0)
        }
    }

    img.onerror = function () {
        console.error('Failed to load image:', src)
    }

    img.src = src
}

const renderGif = (versionId, version) => {
    isLoading.value = true
    const versionsEl = document.querySelector('.versions')
    if (versionsEl) versionsEl.style.flexDirection = 'column'

    axios.get(`/preview/renderGifs/${versionId}`)
        .then(response => {
            version.gifs = response.data.gifs || []
        })
        .catch(error => {
            console.log(error)
        })
        .finally(() => {
            setTimeout(() => {
                isLoading.value = false
            }, 1000)
        })
}

// File transfer widget
const initFileTransferWidget = () => {
    const widget = document.getElementById('fileTransferWidget')
    if (!widget) return

    window.addEventListener('resize', () => {
        if (window.innerWidth < 420) {
            widget.style.right = '10px'
            widget.style.bottom = '12px'
        } else {
            widget.style.right = ''
            widget.style.bottom = ''
        }
    })

    try {
        const panel = document.getElementById('fileTransferPanel')
        if (panel) {
            setTimeout(() => {
                panel.classList.add('attention')
                setTimeout(() => panel.classList.remove('attention'), 1000)
            }, 300)

            setInterval(() => {
                panel.classList.add('attention')
                setTimeout(() => panel.classList.remove('attention'), 900)
            }, 8000)
        }
    } catch (_e) {
        // ignore
    }
}

// Logout handler
const handleLogout = (event) => {
    event.preventDefault()
    const form = event.target
    router.post(form.action, {
        preview_id: props.preview.id
    })
}

// Lifecycle hooks
let viewerInterval = null
let trackingInterval = null

onMounted(async () => {
    // Load external scripts
    await loadExternalScripts()

    initGuestName()
    renderCategories()
    initFileTransferWidget()

    trackingInterval = setInterval(trackViewer, 8000)

    if (props.authUserClientName === 'Planet Nine') {
        fetchViewers()
        viewerInterval = setInterval(fetchViewers, 10000)
    }
})

onUnmounted(() => {
    if (viewerInterval) clearInterval(viewerInterval)
    if (trackingInterval) clearInterval(trackingInterval)
    document.removeEventListener('click', handleOutsideClick)
    document.removeEventListener('click', closeFeedbackDescriptionOutside, true)
})
</script>

<style>
/* CSS variables are set synchronously via JavaScript at component setup */
</style>
