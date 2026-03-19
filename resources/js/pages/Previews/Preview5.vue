<template>
    <div>

        <Head :title="`Creative - ${preview.name}`">
            <link rel="shortcut icon" href="https://www.planetnine.com/logo/new_favicon.png">
            <link rel="stylesheet" :href="asset('previewcssandjsfiles/css/photoswipe.css')">
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

            <!-- Color Palette Container -->
            <div class="color-palette-container">
                <div id="mobilecolorPaletteClick" @click="toggleColorPalette">
                    <img :src="`/${rightTabColorPaletteImage}`" alt="palette icon">
                </div>

                <!-- Color Palette Panel -->
                <div id="mobilecolorPaletteSelection"
                    :class="{ visible: isPaletteVisible, 'transitions-enabled': transitionsEnabled }">
                    <div class="color-grid">
                        <div v-for="color in allColors" :key="color.id" class="mobile-color-box"
                            :style="{ backgroundColor: color.hex, borderColor: color.border }" :title="color.name"
                            @click="changeTheme(color.id)">
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
                                <!-- Mobile Menu Toggle Button -->
                                <div id="mobileMenuToggle" :class="{ hidden: isMobileMenuOpen }">
                                    <button ref="menuToggleBtn" id="openMobileMenu" aria-label="Open menu"
                                        @click="openMobileMenu">
                                        <Menu class="h-5 w-5" />
                                    </button>
                                </div>

                                <!-- Mobile Menu Backdrop -->
                                <Transition name="backdrop-fade">
                                    <div v-if="isMobileMenuOpen" class="mobile-menu-backdrop" @click="closeMobileMenu"
                                        @touchstart="closeMobileMenu"></div>
                                </Transition>

                                <!-- Mobile Menu Panel -->
                                <Transition name="slide-menu">
                                    <div v-if="isMobileMenuOpen" ref="mobileMenuPanel" id="mobileMenu"
                                        class="mobile-menu-panel" @click.stop @touchstart.stop>
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
                                </Transition>
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
                                                                <li>
                                                                    <RotateCw :id="`relBt${banner.id}`"
                                                                        @click="reloadBanner(banner.id)" class="h-4 w-4"
                                                                        style="display: flex; margin-top: 0.5rem; cursor: pointer;" />
                                                                </li>
                                                                <li v-if="authUserClientName === 'Planet Nine'"
                                                                    class="banner-options">
                                                                    <a :href="`/previews/banner/download/${banner.id}`">
                                                                        <Download class="h-4 w-4"
                                                                            style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem;" />
                                                                    </a>
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
                                                                        title="Download">
                                                                        <Download class="h-5 w-5"
                                                                            style="display: flex; margin-left: 0.5rem;" />
                                                                    </a>
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
                                                                        <Download class="h-4 w-4" />
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
                                                                style="width: 100%; max-width: 600px; height: auto; object-fit: contain; box-shadow: 0 2px 8px #0001; cursor: pointer; margin-top: 0;"
                                                                @click="openSocialImageModal(`/${social.path}`, social.name)">
                                                            <ul style="display: flex; flex-direction: row; justify-content: left; margin-top: 10px;"
                                                                class="previewIcons">
                                                                <li v-if="authUserClientName === 'Planet Nine'">
                                                                    <a :href="`/${social.path}`"
                                                                        :download="`${social.name}.jpg`">
                                                                        <Download class="h-5 w-5"
                                                                            style="display: flex; margin-left: 0.5rem;" />
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
                                                                <li>
                                                                    <RotateCw :id="`relBt${gif.id}`"
                                                                        @click="reloadBanner(gif.id)" class="h-5 w-5"
                                                                        style="display: flex; margin-top: 0.5rem; cursor: pointer;" />
                                                                </li>
                                                                <li v-if="authUserClientName === 'Planet Nine'">
                                                                    <a :href="`/${gif.path}`" :download="gif.name">
                                                                        <Download class="h-5 w-5"
                                                                            style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem;" />
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Feedback Description Toggle Button -->
                                <div id="feedbackClick" @click="toggleFeedbackDescription">
                                    <img :src="`/${rightTabFeedbackDescriptionImage}`" alt="feedback icon">
                                </div>

                                <!-- Loader positioned inside right-column -->
                                <div id="loaderArea" :style="{ display: isLoading ? 'flex' : 'none' }">
                                    <span class="loader"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feedback Description Panel (independent) -->
                <div id="feedbackDescription"
                    :class="{ show: isFeedbackDescriptionVisible, 'transitions-enabled': transitionsEnabled }">
                    <div id="feedbackDescriptionUpperpart">
                        <div class="feedback-header-title">Feedback Notes</div>
                        <div class="cursor-pointer feedback-close-btn" @click="hideFeedbackDescription">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                    </div>
                    <div id="feedbackDescriptionLowerPart">
                        <div id="feedbackMessage" v-html="feedbackMessage || 'No feedback description available.'">
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Bottom Right Actions Container -->
        <div class="bottom-right-actions">
            <div class="fileTransferSection">
                <div v-if="fileTransfer && !isIntroActive" id="fileTransferWidget"
                    :class="['file-transfer-widget', { 'minimized': isFileTransferMinimized }]" aria-hidden="false">
                    <div id="fileTransferPanel" class="file-transfer-panel" role="region">
                        <!-- Minimize/Maximize Button -->
                        <button class="ft-toggle-btn" @click="toggleFileTransferWidget"
                            :title="isFileTransferMinimized ? 'Maximize' : 'Minimize'"
                            aria-label="Toggle file transfer widget">
                            <ChevronUp v-if="isFileTransferMinimized" class="h-4 w-4" />
                            <ChevronDown v-else class="h-4 w-4" />
                        </button>

                        <!-- Content (hidden when minimized) -->
                        <Transition name="expand">
                            <div v-if="!isFileTransferMinimized" class="ft-expandable-wrapper">
                                <div class="ft-expandable-content">
                                    <div class="ft-content">
                                        <Download class="ft-icon" />
                                        <div class="ft-text-group">
                                            <div class="ft-title">Files Ready</div>
                                            <div class="ft-subtitle">Download now</div>
                                        </div>
                                    </div>
                                    <a id="fileTransferButton" class="file-transfer-btn"
                                        :href="`/file-transfers-view/${fileTransfer.slug}`" target="_blank"
                                        rel="noopener noreferrer">
                                        <span>Get Files</span>
                                        <ArrowRight class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </Transition>

                        <!-- Minimized State Display -->
                        <Transition name="minimize">
                            <div v-if="isFileTransferMinimized" class="ft-minimized-wrapper">
                                <div class="ft-minimized-content">
                                    <Download class="ft-minimized-icon" />
                                    <span class="ft-minimized-text">Files Ready</span>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>

            <!-- Tour Assistant Chatbot -->
            <div v-if="showTourAssistant" class="tour-assistant">
                <button @click="toggleAssistantMenu" class="tour-help-button" :class="{ 'active': isAssistantMenuOpen }"
                    tabindex="0" title="Need Help?" aria-label="Open assistant chatbot">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </button>

                <!-- Chatbot Interface -->
                <Transition name="chat-slide">
                    <div v-if="isAssistantMenuOpen && !isIntroActive" class="assistant-chatbot" @click.stop>
                        <!-- Chatbot Header -->
                        <div class="chatbot-header">
                            <div class="chatbot-avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M12 8V4H8"></path>
                                    <rect width="16" height="12" x="4" y="8" rx="2"></rect>
                                    <path d="M2 14h2"></path>
                                    <path d="M20 14h2"></path>
                                    <path d="M15 13v2"></path>
                                    <path d="M9 13v2"></path>
                                </svg>
                            </div>
                            <div class="chatbot-header-text">
                                <h4>Preview Assistant</h4>
                            </div>
                            <button @click="toggleAssistantMenu" class="chatbot-close" aria-label="Close chatbot">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>

                        <!-- Chat Messages -->
                        <div class="chatbot-messages">
                            <!-- Bot greeting -->
                            <div class="chat-message bot-message">
                                <div class="message-bubble">
                                    <p>How can we help?</p>
                                </div>
                            </div>

                            <!-- Options as chat buttons -->
                            <div class="chat-options">
                                <button @click="restartTour" class="chat-option-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M3 11l18-5v12L3 14v-3z"></path>
                                        <path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"></path>
                                    </svg>
                                    <span>Take a tour</span>
                                </button>
                                <button v-if="!showContactReply" @click="contactSupport" class="chat-option-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path
                                            d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                                        </path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                    <span>Contact us</span>
                                </button>
                            </div>

                            <!-- Bot reply for contact support -->
                            <Transition name="message-fade">
                                <div v-if="showContactReply" class="chat-message bot-message">
                                    <div class="message-bubble">
                                        <p>We'd love to hear from you! Please reach out to us at:</p>
                                        <a href="mailto:support@planetnine.com" class="support-email">
                                            support@planetnine.com
                                        </a>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- Introduction Tour Overlay -->
        <Transition name="fade">
            <div v-if="showIntroOverlay" class="intro-overlay" @click="skipIntro"></div>
        </Transition>

        <!-- Introduction Tour Modal -->
        <Transition name="slide-up">
            <div v-if="isIntroActive" class="intro-modal"
                :class="{ 'help-button-step': getCurrentStep()?.element === '.tour-help-button' }" @click.stop>
                <!-- Decorative background elements -->
                <div class="intro-modal-bg-decoration"></div>

                <div class="intro-modal-header">
                    <div class="intro-step-indicator">
                        <div class="intro-step-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 16v-4" />
                                <path d="M12 8h.01" />
                            </svg>
                            <span>Step {{ currentStep + 1 }}/{{ steps.length }}</span>
                        </div>
                    </div>
                    <button class="intro-close-btn" @click="skipIntro" aria-label="Skip tour" title="Close tour">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="intro-modal-body">
                    <h3 class="intro-modal-title">{{ getCurrentStep()?.title }}</h3>
                    <p class="intro-modal-description">{{ getCurrentStep()?.description }}</p>
                </div>
                <div class="intro-modal-footer">
                    <div class="intro-nav-buttons">
                        <button class="intro-btn intro-btn-secondary" @click="prevStep" :disabled="currentStep === 0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            <span>Previous</span>
                        </button>
                        <button class="intro-btn intro-btn-primary" @click="nextStep">
                            <span>{{ currentStep === steps.length - 1 ? 'Finish' : 'Next' }}</span>
                            <svg v-if="currentStep !== steps.length - 1" xmlns="http://www.w3.org/2000/svg" width="16"
                                height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </button>
                    </div>
                    <div class="intro-progress-section">
                        <div class="intro-dots">
                            <span v-for="(step, index) in steps" :key="index" class="intro-dot"
                                :class="{ 'active': index === currentStep, 'completed': index < currentStep }"></span>
                        </div>
                        <button class="intro-skip-btn" @click="skipIntro">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="13 17 18 12 13 7"></polyline>
                                <polyline points="6 17 11 12 6 7"></polyline>
                            </svg>
                            <span>Skip Tour</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

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

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { Menu, RotateCw, Download, ChevronUp, ChevronDown, ArrowRight } from 'lucide-vue-next'
import { usePreviewIntro } from '@/composables/usePreviewIntro'

// Import CSS for HMR support
import '../../../css/preview5.css'

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
    isAuthenticated: Boolean,
    intro: {
        type: Boolean,
        default: true
    }
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
const transitionsEnabled = ref(false)
const categories = ref([])
const currentCategoryIndex = ref(0)
const feedbacks = ref([])
const currentFeedbackIndex = ref(0)
const feedbackSets = ref([])
const activeCategory = ref(null)
const fileTransfer = ref(null)
const feedbackMessage = ref('')
const guestName = ref('')
const isFileTransferMinimized = ref(false)
const isAssistantMenuOpen = ref(false)
const showContactReply = ref(false)

// Introduction tour
const {
    isIntroActive,
    currentStep,
    steps,
    startIntro,
    nextStep,
    prevStep,
    skipIntro,
    getCurrentStep,
    hasCompletedIntro,
    setMobileMenuOpen,
    setMobileMenuClose,
    setFeedbackPanelOpen,
    setFeedbackPanelClose
} = usePreviewIntro()

const currentYear = computed(() => new Date().getFullYear())

// Show tour assistant when tour is inactive OR when on the help button step
const showTourAssistant = computed(() => {
    if (!isIntroActive.value) return true
    const currentStepData = getCurrentStep()
    return currentStepData?.element === '.tour-help-button'
})

// Show intro overlay except when on mobile during Creative Showcase, Asset Display, or Feedback Description steps
const showIntroOverlay = computed(() => {
    if (!isIntroActive.value) return false

    const isMobile = window.innerWidth <= 1024
    const currentStepData = getCurrentStep()

    // Hide overlay on mobile when on Creative Showcase step (which uses mobileMenuToggle)
    if (isMobile && currentStepData?.mobileElement === '#mobileMenuToggle') {
        return false
    }

    // Hide overlay on mobile when on Asset Display step
    if (isMobile && currentStepData?.element === '.feedbackSetsContainer') {
        return false
    }

    // Hide overlay on mobile when on Feedback Description step
    if (isMobile && currentStepData?.element === '#feedbackClick') {
        return false
    }

    return true
})

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
    // Close other panels first (but not the menu itself to avoid recursion)
    if (isPaletteVisible.value) {
        hideColorPalette()
    }
    if (isFeedbackDescriptionVisible.value) {
        hideFeedbackDescription()
    }

    // Open the mobile menu
    isMobileMenuOpen.value = true
    document.body.style.overflow = 'hidden'
}

// Removed duplicate setMobileMenuOpen call - it's now called after closeMobileMenu definition

const closeMobileMenu = () => {
    if (!isMobileMenuOpen.value) return

    isMobileMenuOpen.value = false
    document.body.style.overflow = ''
}

// Set the mobile menu functions for the tour
setMobileMenuOpen(openMobileMenu)
setMobileMenuClose(closeMobileMenu)

// Close all panels utility
const closeAllPanels = () => {
    if (isPaletteVisible.value) hideColorPalette()
    if (isFeedbackDescriptionVisible.value) hideFeedbackDescription()
    if (isMobileMenuOpen.value) closeMobileMenu()
}

// Generic panel management
const createPanelHandler = (visibilityRef, panelId, buttonId, otherPanelsToClose) => {
    const show = () => {
        // Close other panels first
        otherPanelsToClose.forEach(closeFn => closeFn())
        visibilityRef.value = true

        // Add click listener to close when clicking outside
        nextTick(() => {
            setTimeout(() => {
                document.addEventListener('click', hide.outsideClickHandler)
            }, 100)
        })
    }

    const hide = () => {
        if (!visibilityRef.value) return
        visibilityRef.value = false
        document.removeEventListener('click', hide.outsideClickHandler)
    }

    hide.outsideClickHandler = (event) => {
        const panel = document.getElementById(panelId)
        const button = document.getElementById(buttonId)
        if (panel && button) {
            if (!panel.contains(event.target) && !button.contains(event.target)) {
                hide()
            }
        }
    }

    const toggle = (event) => {
        event?.stopPropagation()
        visibilityRef.value ? hide() : show()
    }

    return { show, hide, toggle }
}

// Color palette
const {
    show: showColorPalette,
    hide: hideColorPalette,
    toggle: toggleColorPalette
} = createPanelHandler(
    isPaletteVisible,
    'mobilecolorPaletteSelection',
    'mobilecolorPaletteClick',
    [() => isFeedbackDescriptionVisible.value && hideFeedbackDescription(), () => isMobileMenuOpen.value && closeMobileMenu()]
)

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
const {
    show: showFeedbackDescription,
    hide: hideFeedbackDescription,
    toggle: toggleFeedbackDescription
} = createPanelHandler(
    isFeedbackDescriptionVisible,
    'feedbackDescription',
    'feedbackClick',
    [() => isPaletteVisible.value && hideColorPalette(), () => isMobileMenuOpen.value && closeMobileMenu()]
)

// Set the feedback panel functions for the tour
setFeedbackPanelOpen(showFeedbackDescription)
setFeedbackPanelClose(hideFeedbackDescription)

// Vue refs for mobile menu elements
const mobileMenuPanel = ref(null)
const menuToggleBtn = ref(null)

// Categories
const renderCategories = () => {
    axios.get(`/preview/renderCategories/${props.previewId}`)
        .then(response => {
            categories.value = response.data.categories || []
            activeCategory.value = response.data.activeCategory
            currentCategoryIndex.value = categories.value.findIndex(c => c.id === response.data.activeCategory.id)
            if (currentCategoryIndex.value === -1) currentCategoryIndex.value = 0

            renderFeedbacks(response)

            // Initialize file transfer widget after rendering
            nextTick(() => {
                if (fileTransfer.value) {
                    initFileTransferWidget()
                }
            })
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

        // Initialize file transfer widget if data exists
        if (fileTransfer.value) {
            initFileTransferWidget()
        }
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

// Unified feedback navigation
const navigateFeedback = (newIndex) => {
    if (newIndex >= 0 && newIndex < feedbacks.value.length && newIndex !== currentFeedbackIndex.value) {
        currentFeedbackIndex.value = newIndex
        updateActiveFeedback(feedbacks.value[newIndex].id)
        nextTick(() => {
            updateFeedbackNav()
            scrollActiveFeedbackTabIntoView()
        })
    }
}

const navigateFeedbackFarLeft = () => navigateFeedback(0)
const navigateFeedbackLeft = () => navigateFeedback(currentFeedbackIndex.value - 1)
const navigateFeedbackRight = () => navigateFeedback(currentFeedbackIndex.value + 1)
const navigateFeedbackFarRight = () => navigateFeedback(feedbacks.value.length - 1)

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

// File transfer widget handlers
const toggleFileTransferWidget = () => {
    isFileTransferMinimized.value = !isFileTransferMinimized.value
    localStorage.setItem('fileTransferMinimized', isFileTransferMinimized.value)
}

const initFileTransferWidget = () => {
    const widget = document.getElementById('fileTransferWidget')
    if (!widget) return

    // Widget always starts maximized on page load
    isFileTransferMinimized.value = false

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
        if (panel && !isFileTransferMinimized.value) {
            // Initial attention burst
            setTimeout(() => {
                panel.classList.add('attention')
                setTimeout(() => panel.classList.remove('attention'), 1000)
            }, 300)

            // Gentle periodic pulse every 8s (short burst)
            setInterval(() => {
                if (!isFileTransferMinimized.value) {
                    panel.classList.add('attention')
                    setTimeout(() => panel.classList.remove('attention'), 900)
                }
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

// Restart tour
const restartTour = () => {
    closeAllPanels()
    isAssistantMenuOpen.value = false
    showContactReply.value = false
    setTimeout(() => {
        startIntro()
    }, 300)
}

// Assistant menu toggle
const toggleAssistantMenu = () => {
    isAssistantMenuOpen.value = !isAssistantMenuOpen.value
    if (!isAssistantMenuOpen.value) {
        // Reset contact reply when closing
        setTimeout(() => {
            showContactReply.value = false
        }, 300)
    }
}

// Contact support action
const contactSupport = () => {
    showContactReply.value = true
}

// Handle clicks outside assistant menu
const handleAssistantOutsideClick = (event) => {
    const assistantEl = document.querySelector('.tour-assistant')
    if (assistantEl && !assistantEl.contains(event.target)) {
        isAssistantMenuOpen.value = false
        setTimeout(() => {
            showContactReply.value = false
        }, 300)
    }
}

// Lifecycle hooks
let viewerInterval = null
let trackingInterval = null

onMounted(async () => {
    // Load external scripts
    await loadExternalScripts()

    initGuestName()
    renderCategories()

    trackingInterval = setInterval(trackViewer, 8000)

    // Add outside click listener for assistant menu
    document.addEventListener('click', handleAssistantOutsideClick)

    if (props.authUserClientName === 'Planet Nine') {
        fetchViewers()
        viewerInterval = setInterval(fetchViewers, 10000)
    }

    // Start intro tour if enabled and user hasn't seen it before
    if (props.intro && !hasCompletedIntro()) {
        setTimeout(() => {
            // Close any open panels before starting tour
            closeAllPanels()
            startIntro()
        }, 1000)
    }

    // Enable transitions after a brief delay to prevent initial animation
    setTimeout(() => {
        transitionsEnabled.value = true
    }, 100)
})

onUnmounted(() => {
    if (viewerInterval) clearInterval(viewerInterval)
    if (trackingInterval) clearInterval(trackingInterval)

    // Clean up all event listeners
    document.removeEventListener('click', handleOutsideClick)
    document.removeEventListener('click', handleColorPaletteOutsideClick)
    document.removeEventListener('click', handleFeedbackOutsideClick)
    document.removeEventListener('click', handleAssistantOutsideClick)

    // Close all panels
    closeAllPanels()
})
</script>

<style>
/* CSS variables are set synchronously via JavaScript at component setup */

/* Introduction Tour Styles */
.intro-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.75) 0%, rgba(0, 0, 0, 0.85) 100%);
    z-index: 10000;
    backdrop-filter: blur(4px);
    animation: overlayFadeIn 0.4s ease-out;
}

@keyframes overlayFadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

.intro-modal {
    position: fixed;
    bottom: 10px;
    right: 5px;
    transform: none;
    background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 20px;
    box-shadow:
        0 25px 70px -12px rgba(0, 0, 0, 0.3),
        0 0 0 1px rgba(0, 0, 0, 0.05),
        0 0 80px rgba(99, 102, 241, 0.15);
    z-index: 10002;
    width: 90%;
    max-width: 480px;
    overflow: hidden;
    transition: bottom 0.3s ease, right 0.3s ease, left 0.3s ease;
}

/* Move modal to left of help button on help button step to avoid overlap */
.intro-modal.help-button-step {
    right: 80px;
    /* Position to the left of the help button (48px button + 5px right offset + gap) */
    left: auto;
}

@media (max-width: 768px) {
    .intro-modal.help-button-step {
        right: 70px;
        /* Adjust for smaller screens */
        max-width: 320px;
    }
}

@media (max-width: 480px) {
    .intro-modal.help-button-step {
        right: 10px;
        bottom: 80px;
        /* Move above the help button on very small screens */
        max-width: calc(100vw - 20px);
    }
}

/* Decorative background element */
.intro-modal-bg-decoration {
    position: absolute;
    top: -100px;
    right: -100px;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, var(--primary-color, #3b82f6) 0%, transparent 70%);
    opacity: 0.08;
    border-radius: 50%;
    pointer-events: none;
}

.intro-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 24px 12px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(10px);
}

.intro-step-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
}

.intro-step-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, var(--primary-color, #3b82f6) 0%, var(--primary-color, #2563eb) 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 10px;
    font-size: 0.8125rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
    animation: badgePulse 2s ease-in-out infinite;
}

@keyframes badgePulse {

    0%,
    100% {
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
    }

    50% {
        box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4);
    }
}

.intro-step-badge svg {
    width: 16px;
    height: 16px;
}

.intro-close-btn {
    background: rgba(0, 0, 0, 0.04);
    border: none;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s ease;
    padding: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    width: 30px;
    height: 30px;
}

.intro-close-btn:hover {
    background: rgba(0, 0, 0, 0.08);
    color: #111827;
    transform: rotate(90deg);
}

.intro-modal-body {
    padding: 18px 24px;
    text-align: left;
}

.intro-modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    background: linear-gradient(135deg, #111827 0%, #374151 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0 0 8px 0;
    letter-spacing: -0.02em;
}

.intro-modal-description {
    font-size: 0.9375rem;
    color: #6b7280;
    line-height: 1.5;
    margin: 0;
}

.intro-modal-footer {
    padding: 14px 24px 16px;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    background: rgba(249, 250, 251, 0.5);
    backdrop-filter: blur(10px);
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.intro-nav-buttons {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.intro-btn {
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    display: flex;
    align-items: center;
    gap: 6px;
    position: relative;
    overflow: hidden;
}

.intro-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.4);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.intro-btn:active::before {
    width: 300px;
    height: 300px;
}

.intro-btn svg {
    transition: transform 0.3s ease;
}

.intro-btn-primary {
    background: linear-gradient(135deg, var(--primary-color, #3b82f6) 0%, var(--primary-color, #2563eb) 100%);
    color: white;
    box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);
    flex: 1;
}

.intro-btn-primary:hover {
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.45);
}

.intro-btn-primary:hover svg {
    transform: translateX(3px);
}

.intro-btn-secondary {
    background: white;
    color: #374151;
    border: 2px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.intro-btn-secondary:hover:not(:disabled) {
    background: #f9fafb;
    border-color: #d1d5db;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.intro-btn-secondary:hover:not(:disabled) svg {
    transform: translateX(-3px);
}

.intro-btn-secondary:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    border-color: #f3f4f6;
}

.intro-progress-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.intro-dots {
    display: flex;
    gap: 6px;
    align-items: center;
    flex: 1;
    justify-content: center;
}

.intro-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #d1d5db;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.intro-dot.completed {
    background: var(--primary-color, #3b82f6);
    width: 8px;
}

.intro-dot.active {
    background: var(--primary-color, #3b82f6);
    width: 28px;
    border-radius: 4px;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
}

.intro-skip-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    background: transparent;
    border: none;
    color: #9ca3af;
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    padding: 6px 12px;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.intro-skip-btn:hover {
    background: rgba(0, 0, 0, 0.04);
    color: #6b7280;
}

.intro-skip-btn svg {
    width: 14px;
    height: 14px;
    transition: transform 0.2s ease;
}

.intro-skip-btn:hover svg {
    transform: translateX(2px);
}

.intro-highlight {
    animation: intro-pulse 2s infinite;
    box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
    border-radius: 12px;
    isolation: isolate;
}

/* Help button needs special handling - no isolation to maintain z-index stacking */
.tour-help-button.intro-highlight {
    isolation: auto;
    animation: intro-pulse-button 2s infinite !important;
    /* Override the button's default transition during highlight */
    transition: none !important;
}

@keyframes intro-pulse-button {
    0% {
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4), 0 0 0 0 rgba(59, 130, 246, 0.7);
        transform: scale(1);
    }

    50% {
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4), 0 0 0 20px rgba(59, 130, 246, 0);
        transform: scale(1.1);
    }

    100% {
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4), 0 0 0 0 rgba(59, 130, 246, 0);
        transform: scale(1);
    }
}

/* Special handling for fixed positioned elements during intro */
#mobilecolorPaletteClick,
#feedbackClick {
    transition: z-index 0.3s ease;
}

/* Ensure mobile menu toggle is prominently highlighted */
#mobileMenuToggle.intro-highlight {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    padding: 12px;
    border-radius: 12px;
}

/* Ensure navbar is visible and not cut off during intro highlight */
#navbar.intro-highlight {
    margin-top: 20px;
}

/* Ensure feedback sets container maintains proper layout with white-ish background */
.feedbackSetsContainer.intro-highlight {
    padding: 10px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Ensure color palette button is properly highlighted */
#mobilecolorPaletteClick.intro-highlight {
    /* background: rgba(255, 255, 255, 0.95); */
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    padding: 0;
    border-radius: 12px;
}

@keyframes intro-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
    }

    50% {
        box-shadow: 0 0 0 20px rgba(59, 130, 246, 0);
    }

    100% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
    }
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.4s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active {
    transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from {
    transform: translateY(20px) scale(0.9);
    opacity: 0;
}

.slide-up-leave-to {
    transform: translateY(20px) scale(0.95);
    opacity: 0;
}

/* Responsive */
@media (max-width: 640px) {
    .intro-modal {
        width: 95%;
        max-width: 100%;
        bottom: 10px;
        right: 10px;
    }

    .intro-modal-header {
        padding: 12px 18px 10px;
    }

    .intro-modal-body {
        padding: 16px 18px;
    }

    .intro-modal-footer {
        padding: 12px 18px 14px;
    }

    .intro-modal-title {
        font-size: 1.125rem;
    }

    .intro-modal-description {
        font-size: 0.875rem;
    }

    .intro-btn {
        padding: 9px 16px;
        font-size: 0.8125rem;
    }

    .intro-step-badge {
        padding: 5px 10px;
        font-size: 0.75rem;
    }

    .intro-nav-buttons {
        gap: 8px;
    }

    .intro-skip-btn {
        font-size: 0.75rem;
    }
}

@media (max-width: 480px) {
    .intro-modal {
        width: 95%;
    }

    .intro-modal-title {
        font-size: 1rem;
    }

    .intro-modal-description {
        font-size: 0.8125rem;
    }

    .intro-nav-buttons {
        flex-direction: column-reverse;
        width: 100%;
    }

    .intro-btn {
        width: 100%;
        justify-content: center;
    }

    .intro-progress-section {
        flex-direction: column;
        gap: 10px;
    }
}

/* Bottom Right Actions Container */
.bottom-right-actions {
    position: fixed;
    bottom: 10px;
    right: 5px;
    display: flex;
    flex-direction: row;
    align-items: flex-end;
    gap: 12px;
    z-index: 10010;
}

/* Hide file transfer widget on mobile and tablet */
@media (max-width: 1024px) {
    .fileTransferSection {
        display: none;
    }
}

/* Tour Assistant Container */
.tour-assistant {
    position: relative;
    flex-shrink: 0;
}

/* Tour Help Button */
.tour-help-button {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    /* Ensure proper stacking context */
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation: tourButtonEntrance 0.5s ease-out;
}

.tour-help-button:hover,
.tour-help-button.active {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
}

.tour-help-button:focus {
    outline: none;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.4), 0 6px 20px rgba(59, 130, 246, 0.5);
    transform: scale(1.08);
}

.tour-help-button:active {
    transform: translateY(0) scale(0.98);
}

.tour-help-button svg {
    width: 24px;
    height: 24px;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

/* Assistant Menu Dropdown */
.assistant-chatbot {
    position: absolute;
    bottom: calc(100% + 12px);
    right: 0;
    width: 320px;
    max-width: calc(100vw - 20px);
    background: white;
    border-radius: 16px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transform-origin: bottom right;
    display: flex;
    flex-direction: column;
}

/* Chatbot Header */
.chatbot-header {
    background: linear-gradient(135deg, var(--primary-color, #3b82f6) 0%, var(--primary-color, #2563eb) 100%);
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
}

.chatbot-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.chatbot-header-text {
    flex: 1;
}

.chatbot-header-text h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.chatbot-header-text span {
    font-size: 0.75rem;
    opacity: 0.9;
}

.chatbot-close {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.chatbot-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Chat Messages Container */
.chatbot-messages {
    padding: 16px;
    max-height: 300px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Chat Message */
.chat-message {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.bot-message {
    align-items: flex-start;
}

.message-bubble {
    background: #f1f5f9;
    padding: 12px 14px;
    border-radius: 12px;
    border-top-left-radius: 4px;
    max-width: 85%;
}

.message-bubble p {
    margin: 0;
    font-size: 0.9375rem;
    color: #334155;
    line-height: 1.5;
}

.support-email {
    display: inline-block;
    margin-top: 8px;
    padding: 8px 12px;
    background: white;
    color: var(--primary-color, #3b82f6);
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid #e2e8f0;
}

.support-email:hover {
    background: var(--primary-color, #3b82f6);
    color: white;
    border-color: var(--primary-color, #3b82f6);
}

/* Chat Options */
.chat-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 4px;
}

.chat-option-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: white;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    color: #334155;
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
}

.chat-option-btn:hover {
    background: linear-gradient(135deg, #f0f7ff 0%, #e0f2fe 100%);
    border-color: var(--primary-color, #3b82f6);
    color: var(--primary-color, #3b82f6);
}

.chat-option-btn svg {
    flex-shrink: 0;
}

/* Chat slide animation */
.chat-slide-enter-active,
.chat-slide-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.chat-slide-enter-from {
    opacity: 0;
    transform: scale(0.9) translateY(12px);
}

.chat-slide-leave-to {
    opacity: 0;
    transform: scale(0.95) translateY(8px);
}

/* Message fade animation */
.message-fade-enter-active {
    transition: all 0.4s ease;
}

.message-fade-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

@keyframes tourButtonEntrance {
    from {
        transform: scale(0) rotate(-180deg);
        opacity: 0;
    }

    to {
        transform: scale(1) rotate(0deg);
        opacity: 1;
    }
}

/* Responsive adjustments for Tour Help Button and Assistant */

/* Tablet and larger mobile devices */
@media (max-width: 768px) {
    .tour-help-button {
        width: 46px;
        height: 46px;
    }

    .tour-help-button svg {
        width: 22px;
        height: 22px;
    }

    .assistant-chatbot {
        width: 300px;
        max-width: calc(100vw - 40px);
    }
}

/* Smaller mobile devices */
@media (max-width: 640px) {
    .tour-help-button {
        width: 44px;
        height: 44px;
    }

    .tour-help-button svg {
        width: 20px;
        height: 20px;
    }

    .tour-help-button:hover,
    .tour-help-button.active {
        transform: scale(1.05);
    }

    .tour-help-button:focus {
        transform: scale(1.08);
    }

    .assistant-chatbot {
        width: 280px;
    }

    .chatbot-messages {
        max-height: 250px;
    }
}

/* Very small mobile devices */
@media (max-width: 480px) {
    .bottom-right-actions {
        flex-direction: column;
        gap: 8px;
        align-items: flex-end;
        bottom: 12px;
        right: 8px;
    }

    .tour-help-button {
        width: 42px;
        height: 42px;
    }

    .tour-help-button svg {
        width: 18px;
        height: 18px;
    }

    .assistant-chatbot {
        width: calc(100vw - 30px);
        right: -5px;
    }

    .chatbot-messages {
        max-height: 220px;
    }
}

/* Extra small devices */
@media (max-width: 375px) {
    .tour-help-button {
        width: 40px;
        height: 40px;
    }

    .tour-help-button svg {
        width: 16px;
        height: 16px;
    }
}
</style>
