<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, nextTick } from 'vue';
import axios from 'axios';
import { gsap } from 'gsap';

// Add this computed property
const cssVariables = computed(() => ({
  '--primary-color': props.primary,
  '--secondary-color': props.secondary,
  '--tertiary-color': props.tertiary,
  '--quaternary-color': props.quaternary,
}));

// Define types
interface Size {
  width: number;
  height: number;
}

interface Category {
  id: number;
  name: string;
  type: string;
  is_active: number;
  created_at: string;
}

interface Feedback {
  id: number;
  name: string;
  description: string;
  is_active: number;
}

interface FeedbackSet {
  id: number;
  name: string;
}

interface Banner {
  id: number;
  path: string;
  size: Size;
  file_size: string;
  name: string;
}

interface Video {
  id: number;
  path: string;
  size: Size;
  file_size: string;
  name: string;
  aspect_ratio?: string;
  codec?: string;
  fps?: string;
  companion_banner_path?: string;
}

interface Social {
  id: number;
  path: string;
  name: string;
}

interface Gif {
  id: number;
  path: string;
  size: Size;
  file_size: string;
  name: string;
}

interface ColorData {
  id: number;
  hex: string;
  border: string;
}

// Props from Laravel
interface Props {
  preview: {
    id: number;
    name: string;
    show_planetnine_logo: boolean;
    show_sidebar_logo: boolean;
    show_footer: boolean;
    requires_login: boolean;
    created_at: string;
  };
  client: {
    name: string;
    logo: string;
  };
  primary: string;
  secondary: string;
  tertiary: string;
  quaternary: string;
  all_colors: ColorData[];
  authUserClientName: string;
  preview_id: number;
}

const props = withDefaults(defineProps<Props>(), {});

// Reactive data
const categories = ref<Category[]>([]);
const currentCategoryIndex = ref(0);
const feedbacks = ref<Feedback[]>([]);
const currentFeedbackIndex = ref(0);
const feedbackSets = ref<FeedbackSet[]>([]);
const isLoading = ref(false);
const guestName = ref('');
const viewers = ref<string[]>([]);
const viewersInterval = ref<number | null>(null);
const trackingInterval = ref<number | null>(null);

// Mobile and UI state
const isMobileMenuOpen = ref(false);
const isFeedbackDescriptionVisible = ref(false);
const isColorPaletteVisible = ref(false);
const isMobileColorPaletteVisible = ref(false);
const isMobilePopupVisible = ref(false);

// Image modal state
const isImageModalVisible = ref(false);
const modalImageSrc = ref('');
const modalImageAlt = ref('');
const isDragging = ref(false);
const dragMoved = ref(false);
const currentScale = ref(1);
const currentX = ref(0);
const currentY = ref(0);
const isZoomed = ref(false);

// Initialize guest name
const initializeGuestName = () => {
  let storedName = localStorage.getItem('guest_name');
  if (!storedName) {
    storedName = 'Guest-' + Math.floor(Math.random() * 10000);
    localStorage.setItem('guest_name', storedName);
  }
  guestName.value = storedName;
};

// API calls
const trackViewer = async () => {
  try {
    await axios.post('/track-viewer', {
      page_id: props.preview.id,
      guest_name: guestName.value
    });
  } catch (error) {
    console.error('Error tracking viewer:', error);
  }
};

const fetchViewers = async () => {
  try {
    const response = await axios.get(`/get-viewers/${props.preview.id}`);
    viewers.value = response.data;
  } catch (error) {
    console.error('Error fetching viewers:', error);
  }
};

const renderCategories = async () => {
  try {
    const response = await axios.get(`/preview/renderCategories/${props.preview_id}`);
    categories.value = response.data.categories || [];
    currentCategoryIndex.value = categories.value.findIndex(c => c.id == response.data.activeCategory.id);
    if (currentCategoryIndex.value === -1) currentCategoryIndex.value = 0;

    await renderFeedbacks(response);
  } catch (error) {
    console.error('Error rendering categories:', error);
  }
};

const updateActiveCategory = async (categoryId: number) => {
  try {
    await axios.get(`/preview/updateActiveCategory/${categoryId}`);
    await renderCategories();
  } catch (error) {
    console.error('Error updating active category:', error);
  }
};

const updateActiveFeedback = async (feedbackId: number) => {
  try {
    const response = await axios.get(`/preview/updateActiveFeedback/${feedbackId}`);
    await renderFeedbacks(response);
  } catch (error) {
    console.error('Error updating active feedback:', error);
  }
};

const renderFeedbacks = async (response: any) => {
  feedbacks.value = response.data.feedbacks || [];
  currentFeedbackIndex.value = feedbacks.value.findIndex(f => f.is_active == 1);
  if (currentFeedbackIndex.value === -1) currentFeedbackIndex.value = 0;

  await renderFeedbackSets(response);
  await nextTick();
  enableFeedbackTabsDragScroll();
  scrollActiveFeedbackTabIntoView();
};

const renderFeedbackSets = async (response: any) => {
  feedbackSets.value = response.data.feedbackSets || [];

  // Render versions for each feedback set
  for (const set of feedbackSets.value) {
    await renderVersions(set.id, response);
  }
};

const renderVersions = async (feedbackSetId: number, res: any) => {
  try {
    const response = await axios.get(`/preview/renderVersions/${feedbackSetId}`);
    const versionsList = response.data.versions;

    // Render content based on category type
    for (const version of versionsList) {
      if (res.data.activeCategory.type === 'banner') {
        await renderBanners(version.id);
      } else if (res.data.activeCategory.type === 'video') {
        await renderVideos(version.id);
      } else if (res.data.activeCategory.type === 'social') {
        await renderSocials(version.id);
      } else if (res.data.activeCategory.type === 'gif') {
        await renderGifs(version.id);
      }
    }
  } catch (error) {
    console.error('Error rendering versions:', error);
  }
};

const renderBanners = async (versionId: number) => {
  isLoading.value = true;
  try {
    const response = await axios.get(`/preview/renderBanners/${versionId}`);
    const banners: Banner[] = response.data.banners;

    await nextTick();
    const container = document.getElementById(`bannersList${versionId}`);
    if (container) {
      let bannersHtml = '';
      banners.forEach(banner => {
        const bannerPath = `/${banner.path}/index.html`;
        bannersHtml += `
                    <div class="banner-creatives banner-area-${banner.size.width}-${banner.size.height}" 
                         style="display: inline-block; width: ${banner.size.width}px; margin-right: 0.5rem; margin-left: 0.5rem; margin-bottom: 2rem;">
                        <div style="display: flex; justify-content: space-between; padding: 0; color: black; border-top-left-radius: 5px; border-top-right-radius: 5px;">
                            <small style="float: left; font-size: 0.85rem; font-weight: bold;">${banner.size.width}x${banner.size.height}</small>
                            <small style="float: right; font-size: 0.85rem; font-weight: bold;">${banner.file_size}</small>
                        </div>
                        <iframe class="iframe-banners" src="${bannerPath}" width="${banner.size.width}" height="${banner.size.height}" frameBorder="0" scrolling="no" id="rel${banner.id}"></iframe>
                        <ul style="display: flex; flex-direction: row;" class="previewIcons">
                            <li><i id="relBt${banner.id}" onclick="reloadBanner(${banner.id})" class="fa-solid fa-repeat" style="display: flex; margin-top: 0.5rem; cursor: pointer; font-size:1rem;"></i></li>
                            ${props.authUserClientName === "Planet Nine" ? `<li class="banner-options"><a href="/previews/banner/download/${banner.id}"><i class="fa-solid fa-download" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:1rem;"></i></a></li>` : ''}
                        </ul>
                    </div>
                `;
      });
      container.innerHTML = bannersHtml;
    }
  } catch (error) {
    console.error('Error rendering banners:', error);
  } finally {
    isLoading.value = false;
  }
};

const renderVideos = async (versionId: number) => {
  isLoading.value = true;
  try {
    const response = await axios.get(`/preview/renderVideos/${versionId}`);
    const videos: Video[] = response.data.videos;

    await nextTick();
    const container = document.getElementById(`bannersList${versionId}`);
    if (container) {
      let videosHtml = '';
      videos.forEach(video => {
        const uniqueId = `videoBlock_${video.id}`;
        videosHtml += `
                    <div id="${uniqueId}" class="mx-auto mb-8" style="max-width: 100%;">
                        <div style="background:transparent; display:flex; justify-content:center;" class="mt-2 mb-2 rounded-lg">
                            <video 
                                src="/${video.path}" 
                                controls 
                                muted
                                class="block mx-auto rounded-2xl video-preview"
                                style="max-width:70vw; max-height:50vh; min-width: 340px; width:auto; height:auto; background:#000;"
                                controlsList="nodownload noremoteplayback"
                                disablePictureInPicture
                                onloadedmetadata="matchVideoMetaWidth(this)"
                            ></video>
                        </div>
                        <div class="bg-gray-50 text-gray-800 text-sm rounded-2xl p-3 mt-2 w-full video-media-info" style="margin:0 auto;">
                            ${props.authUserClientName === "Planet Nine" ? `
                                <div class="flex gap-4 mb-2 justify-center">
                                    <a href="/${video.path}" download title="Download"><i class="fa-solid fa-download" style="display: flex; margin-left: 0.5rem; font-size:20px;"></i></a>
                                </div>
                            ` : ''}
                            <div class="font-semibold text-base mb-1 underline text-center flex justify-center align-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                            </div>
                            <div class="font-semibold text-base mb-1 underline text-center">Media Info</div>
                            <div><strong>Resolution:</strong> ${video.size.width} x ${video.size.height}</div>
                            <div><strong>Aspect Ratio:</strong> ${video.aspect_ratio ?? '-'}</div>
                            <div><strong>Codec:</strong> ${video.codec ?? '-'}</div>
                            <div><strong>FPS:</strong> ${video.fps ?? '-'}</div>
                            <div><strong>File Size:</strong> ${video.file_size ?? '-'}</div>
                            <div class="mt-2 w-full flex flex-col items-center justify-center">
                                ${video.companion_banner_path ? `
                                    <img src="/${video.companion_banner_path}" alt="Companion Banner" class="rounded border mx-auto" style="max-width:970px;max-height:auto;" />
                                    ${props.authUserClientName === "Planet Nine" ? `
                                        <a href="/${video.companion_banner_path}" download title="Download Companion Banner" class="mt-2 flex items-center gap-1 text-blue-600 hover:text-blue-800">
                                            <i class="fa-solid fa-download" style="font-size:18px;"></i>
                                            <span class="text-xs">Download Companion Banner</span>
                                        </a>
                                    ` : ''}
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
      });
      container.innerHTML = videosHtml;
    }
  } catch (error) {
    console.error('Error rendering videos:', error);
  } finally {
    isLoading.value = false;
  }
};

const renderSocials = async (versionId: number) => {
  isLoading.value = true;
  try {
    const response = await axios.get(`/preview/renderSocials/${versionId}`);
    const socials: Social[] = response.data.socials;

    await nextTick();
    const container = document.getElementById(`bannersList${versionId}`);
    if (container) {
      let socialsHtml = '';
      socials.forEach(social => {
        socialsHtml += `
                    <div style="display: inline-block; margin: 10px; max-width: 1000px;">
                        <img src="/${social.path}" 
                            alt="${social.name}"
                            class="social-preview-img rounded-2xl"
                            style="width: 100%; max-width: 700px; height: auto; object-fit: contain; box-shadow: 0 2px 8px #0001; cursor: pointer; margin-top: 0;"
                            onclick="openSocialImageModal('/${social.path}', '${social.name}')"
                        >
                        <ul style="display: flex; flex-direction: row; justify-content: left; margin-top: 10px;" class="previewIcons">
                            ${props.authUserClientName === "Planet Nine" ? `
                                <li>
                                    <a href="/${social.path}" download="${social.name}.jpg">
                                        <i class="fa-solid fa-download" style="display: flex; margin-left: 0.5rem; font-size:20px;"></i>
                                    </a>
                                </li>
                            ` : ''}
                        </ul>
                    </div>
                `;
      });
      container.innerHTML = socialsHtml;
    }
  } catch (error) {
    console.error('Error rendering socials:', error);
  } finally {
    setTimeout(() => {
      isLoading.value = false;
    }, 200);
  }
};

const renderGifs = async (versionId: number) => {
  isLoading.value = true;
  try {
    const response = await axios.get(`/preview/renderGifs/${versionId}`);
    const gifs: Gif[] = response.data.gifs;

    await nextTick();
    const container = document.getElementById(`bannersList${versionId}`);
    if (container) {
      let gifsHtml = '';
      gifs.forEach(gif => {
        const gifPath = `/${gif.path}`;
        gifsHtml += `
                    <div class="banner-creatives banner-area-${gif.size.width}" style="display: inline-block; width: ${gif.size.width}px; margin-right: 0.5rem; margin-left: 0.5rem; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; padding: 0; color: black; border-top-left-radius: 5px; border-top-right-radius: 5px;">
                            <small style="float: left; font-size: 0.85rem; font-weight: bold;">${gif.size.width}x${gif.size.height}</small>
                            <small style="float: right; font-size: 0.85rem; font-weight: bold;">${gif.file_size}</small>
                        </div>
                        <img class="iframe-banners" style="margin-top: 2px;" src="${gifPath}" width="${gif.size.width}" height="${gif.size.height}" frameBorder="0" scrolling="no" id="rel${gif.id}">
                        <ul style="display: flex; flex-direction: row;" class="previewIcons">
                            <li><i id="relBt${gif.id}" onclick="reloadBanner(${gif.id})" class="fa-solid fa-repeat" style="display: flex; margin-top: 0.5rem; cursor: pointer; font-size:20px;"></i></li>
                            ${props.authUserClientName === "Planet Nine" ? `<li class="banner-options"><a href="/${gif.path}" download="${gif.name}"><i class="fa-solid fa-download" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>` : ''}
                        </ul>
                    </div>
                `;
      });
      container.innerHTML = gifsHtml;
    }
  } catch (error) {
    console.error('Error rendering gifs:', error);
  } finally {
    isLoading.value = false;
  }
};

// UI interaction methods
const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
  if (isMobileMenuOpen.value) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
};

const showFeedbackDescription = () => {
  isFeedbackDescriptionVisible.value = true;

  const timeline = gsap.timeline();
  timeline.to('#feedbackDescription', {
    duration: 1,
    display: 'flex',
    opacity: 1,
    x: 0,
    ease: 'power2.out'
  });
};

const hideFeedbackDescription = () => {
  const timeline = gsap.timeline();
  timeline.to('#feedbackDescription', {
    duration: 0.5,
    x: 310,
    opacity: 0,
    display: 'none',
    ease: 'power2.in'
  });
  isFeedbackDescriptionVisible.value = false;
};

const showColorPaletteOptions = () => {
  isColorPaletteVisible.value = !isColorPaletteVisible.value;
};

const showMobileColorPaletteOptions = () => {
  isMobileColorPaletteVisible.value = !isMobileColorPaletteVisible.value;
};

const changeTheme = async (colorId: number) => {
  try {
    const response = await axios.get(`/preview/${props.preview_id}/change/theme/${colorId}`);
    if (response.data.success) {
      window.location.reload();
    } else {
      alert("Something went wrong changing theme");
    }
  } catch (error) {
    console.error('Error changing theme:', error);
  }
};

// Image modal functions
const openSocialImageModal = (src: string, alt: string) => {
  modalImageSrc.value = src;
  modalImageAlt.value = alt;
  isImageModalVisible.value = true;
  resetModalState();
  document.body.style.overflow = 'hidden';
};

const closeSocialImageModal = () => {
  isImageModalVisible.value = false;
  document.body.style.overflow = '';
  resetModalState();
};

const resetModalState = () => {
  currentScale.value = 1;
  currentX.value = 0;
  currentY.value = 0;
  isZoomed.value = false;
  isDragging.value = false;
  dragMoved.value = false;
};

// Utility functions
const reloadBanner = (bannerId: number) => {
  const iframe = document.getElementById(`rel${bannerId}`) as HTMLIFrameElement;
  if (iframe) {
    iframe.src = iframe.src;
  }
};

const enableFeedbackTabsDragScroll = () => {
  const container = document.querySelector('.feedbackTabsContainer') as HTMLElement;
  if (!container) return;

  if (container.scrollWidth > container.clientWidth) {
    container.style.justifyContent = 'flex-start';
  } else {
    container.style.justifyContent = 'center';
  }
};

const scrollActiveFeedbackTabIntoView = () => {
  const container = document.querySelector('.feedbackTabsContainer') as HTMLElement;
  if (!container) return;
  const activeTab = container.querySelector('.feedbackTabActive') as HTMLElement;
  if (activeTab) {
    const containerRect = container.getBoundingClientRect();
    const tabRect = activeTab.getBoundingClientRect();
    const offset = tabRect.left - containerRect.left - (containerRect.width / 2) + (tabRect.width / 2);
    container.scrollBy({ left: offset, behavior: 'smooth' });
  }
};

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return `${date.getDate().toString().padStart(2, '0')}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getFullYear()}`;
};

const isMobileDevice = () => {
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
};

const setCookie = (name: string, value: string, days: number) => {
  let expires = "";
  if (days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "") + expires + "; path=/";
};

const getCookie = (name: string) => {
  const nameEQ = name + "=";
  const ca = document.cookie.split(';');
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
  }
  return null;
};

// Computed properties
const formattedDate = computed(() => {
  const date = new Date(props.preview.created_at);
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
});

const activeFeedback = computed(() => {
  return feedbacks.value.find(f => f.is_active === 1) || feedbacks.value[0];
});

const feedbackNavigation = computed(() => {
  const total = feedbacks.value.length;
  const current = currentFeedbackIndex.value + 1;
  return {
    total,
    current,
    isFirst: currentFeedbackIndex.value === 0,
    isLast: currentFeedbackIndex.value === total - 1
  };
});

// Global functions for templates
(window as any).reloadBanner = reloadBanner;
(window as any).openSocialImageModal = openSocialImageModal;
(window as any).matchVideoMetaWidth = (videoEl: HTMLVideoElement) => {
  setTimeout(() => {
    const video = videoEl;
    const width = video.clientWidth;
    const container = video.closest('.mb-8') as HTMLElement;
    if (container) {
      const nameBar = container.querySelector('.video-name-bar') as HTMLElement;
      const mediaInfo = container.querySelector('.video-media-info') as HTMLElement;
      if (nameBar) nameBar.style.width = width + 'px';
      if (mediaInfo) mediaInfo.style.width = width + 'px';
    }
  }, 50);
};

// Lifecycle hooks
onMounted(() => {
  initializeGuestName();

  // Set up intervals
  trackingInterval.value = window.setInterval(trackViewer, 8000);
  viewersInterval.value = window.setInterval(fetchViewers, 10000);

  // Initial data load
  fetchViewers();
  renderCategories();

  // Handle mobile popup
  if (isMobileDevice() && !getCookie('hideMobilePopup')) {
    isMobilePopupVisible.value = true;
  }
});

onUnmounted(() => {
  if (trackingInterval.value) {
    clearInterval(trackingInterval.value);
  }
  if (viewersInterval.value) {
    clearInterval(viewersInterval.value);
  }
});
</script>

<template>

  <Head :title="`Creative - ${preview.name}`">
    <link rel="preload" as="image" href="/preview_images/sidebar-image.png">
    <link rel="preload" as="image" href="/preview_images/top-bg.png">
    <link rel="preload" as="image" href="/preview_images/white-smart.png">
    <link rel="shortcut icon" href="https://www.planetnine.com/logo/new_favicon.png">
    <!-- <script src="/previewcssandjsfiles/js/jquery.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script> -->
    <!-- <script src="/previewcssandjsfiles/js/fontawesome.all.min.js" crossorigin="anonymous"
      referrerpolicy="no-referrer"></script> -->
  </Head>

  <div :style="cssVariables">
    <!-- Viewer List and Logout -->
    <div v-if="authUserClientName === 'Planet Nine'" class="absolute top-4 right-4 flex items-center space-x-3 z-50">
      <div id="viewerList" class="flex space-x-2">
        <span v-for="viewer in viewers" :key="viewer"
          class="bg-blue-100 text-blue-900 font-semibold rounded-full px-3 py-1 text-sm shadow-sm" :title="viewer">
          {{ viewer.trim().charAt(0).toUpperCase() }}
        </span>
      </div>

      <form v-if="preview.requires_login" method="POST" action="/preview.logout" id="customPreviewLogoutForm">
        <input type="hidden" name="preview_id" :value="preview.id">
        <button type="submit"
          class="bg-red-500 hover:bg-red-600 text-white text-sm font-medium px-3 py-1 rounded shadow transition cursor-pointer">
          Logout
        </button>
      </form>
    </div>

    <!-- Loader -->
    <div v-show="isLoading" id="loaderArea">
      <span class="loader"></span>
    </div>

    <!-- Bulk Customization Link -->
    <a v-if="authUserClientName === 'Planet Nine'" :href="`/previews/update/${preview.id}`" id="bulk-customization"
      class="text-white font-medium cursor-pointer" style="z-index: 1000;">
      <i class="fa-solid fa-gear"></i>
    </a>

    <main class="main">
      <!-- Top Section -->
      <section id="top" class="mb-4">
        <div class="px-4 py-4 flex justify-center content text-center relative">
          <div id="topDetails" class="mt-4">
            <img v-if="preview.show_planetnine_logo" :src="`/logos/${client.logo}`" id="planetnineLogo" class="py-3"
              alt="planetnineLogo">
            <h1 style="font-size: 1rem;">
              <span class="font-semibold">Name: </span>
              <span class="capitalize">{{ preview.name }}</span>
            </h1>
            <h1 class="mt-1" style="font-size: 1rem;">
              <span class="font-semibold">Client: </span>
              <span class="capitalize">{{ client.name }}</span>
            </h1>
            <h1 style="font-size: 1rem;">
              <span class="font-semibold">Date: </span>
              <span>{{ formattedDate }}</span>
            </h1>
          </div>
        </div>
      </section>

      <!-- Mobile Color Palette Toggle -->
      <div id="mobilecolorPaletteClick" @click="showMobileColorPaletteOptions">
        <i class="fa-solid fa-palette"></i>
      </div>

      <!-- Mobile Color Palette Selection -->
      <div id="mobilecolorPaletteSelection" :class="{ 'visible': isMobileColorPaletteVisible, 'color-grid': true }">
        <div v-for="color in all_colors" :key="color.id" class="mobile-color-box"
          :style="{ backgroundColor: color.hex, borderColor: color.border }" :title="color.hex"
          @click="changeTheme(color.id)">
        </div>
      </div>

      <!-- Middle Section -->
      <section id="middle" class="mb-4">
        <div id="showcase-section" class="mx-auto custom-container mt-2">
          <div class="flex row justify-around items-end" style="min-height: 50px;">
            <div class="py-2 flex items-end justify-center sidebar-top-desktop">
              <img v-if="preview.show_sidebar_logo" :src="`/logos/${client.logo}`" alt="clientLogo"
                style="max-width: 200px; margin: 0 auto;">
            </div>
            <div style="flex: 1;" class="feedbackTabs-parent">
              <div class="feedbacks relative flex justify-center flex-row">
                <div class="feedbackTabsContainer">
                  <div v-for="feedback in feedbacks" :key="feedback.id"
                    style="display: flex; align-items: center; justify-content: center; flex-direction: column;">
                    <div :id="`feedbackTab${feedback.id}`"
                      :class="['feedbackTab', { 'feedbackTabActive': feedback.is_active === 1 }]"
                      @click="feedback.is_active !== 1 ? updateActiveFeedback(feedback.id) : null">
                      <div class="trapezoid-container">
                        <div class="tab-text text-white text-base">{{ feedback.name }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div id="showcase">
            <div id="bannershowCustom">
              <!-- Mobile Navigation -->
              <nav role="navigation" class="mobileShowcase">
                <div id="mobileMenuToggle">
                  <button id="openMobileMenu" aria-label="Open menu" @click="toggleMobileMenu">
                    <i class="fa-solid fa-bars"></i>
                  </button>
                </div>
                <div id="mobileMenu" :class="['mobile-menu-panel', { 'open': isMobileMenuOpen }]">
                  <button id="closeMobileMenu" aria-label="Close menu" @click="toggleMobileMenu">&times;</button>
                  <div class="sidebar-image mx-auto mb-4">
                    <span>Creative Showcase</span>
                  </div>
                  <ul id="mobileCategoryList">
                    <a v-for="(category, index) in categories" :key="category.id" href="javascript:void(0)"
                      :class="['nav-link', 'categories', { 'menuToggleActive': category.is_active === 1 }]"
                      @click="category.is_active !== 1 ? updateActiveCategory(category.id) : null"
                      :id="`category${category.id}`">
                      <span class="category-number">{{ index + 1 }}.</span> {{ category.name }}
                    </a>
                  </ul>
                </div>
              </nav>

              <!-- Desktop Navigation -->
              <div class="navbar tabDesktopShowcase" id="navbar">
                <div v-if="preview.show_sidebar_logo" class="w-full client-logo-div sidebar-top-tab-mobile">
                  <div id="clientLogoSection" class="mb-2 mt-2 px-2 py-2 mx-auto">
                    <img :src="`/logos/${client.logo}`" alt="clientLogo" style="width: 150px;">
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
                    @click="category.is_active !== 1 ? updateActiveCategory(category.id) : null"
                    :id="`category${category.id}`">
                    <span :class="{ 'span-active': category.is_active === 1 }" style="font-size: 0.85rem;">
                      {{ category.name }}
                    </span>
                    <hr>
                    <span class="category-row-date" style="font-size: 0.7rem;">
                      {{ formatDate(category.created_at) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Right Column -->
              <div class="right-column">
                <div
                  class="justify-center items-center mt-1 py-2 px-2 relative top-0 left-0 right-0 currentTotalFeedbacks">
                  <span id="feedbackCounter">
                    <span v-if="feedbackNavigation.total === 0">No Feedbacks</span>
                    <span v-else class="font-bold selectedFeedback">
                      Feedback {{ feedbackNavigation.current }}
                    </span>
                  </span>
                </div>

                <!-- Feedback Sets Container -->
                <div class="feedbackSetsContainer">
                  <div v-for="feedbackSet in feedbackSets" :key="feedbackSet.id">
                    <div v-if="feedbackSet.name" class="feedbackSet" :id="`feedbackSet${feedbackSet.id}`"
                      style="display: flex; align-items: center; justify-content: space-between;">
                      <div class="feedbackSetName" style="flex: 1; text-align: center;">
                        {{ feedbackSet.name }}
                      </div>
                    </div>
                    <div class="versions" :id="`versions${feedbackSet.id}`">
                      <div :id="`bannersList${feedbackSet.id}`"></div>
                    </div>
                  </div>
                </div>

                <!-- Feedback Area -->
                <div id="feedbackArea">
                  <div id="feedbackCLick" @click="showFeedbackDescription">
                    <i class="fa-regular fa-message" style="transform: rotate(90deg) scaleX(-1);"></i>
                  </div>

                  <div id="colorPaletteClick" @click="showColorPaletteOptions">
                    <i class="fa-solid fa-palette"></i>
                  </div>

                  <div id="colorPaletteSelection" :class="{ 'visible': isColorPaletteVisible, 'color-grid': true }">
                    <div v-for="color in all_colors" :key="color.id" class="color-box"
                      :style="{ backgroundColor: color.hex, borderColor: color.border }" :title="color.hex"
                      @click="changeTheme(color.id)">
                    </div>
                  </div>

                  <div id="feedbackDescription" :style="{ display: isFeedbackDescriptionVisible ? 'flex' : 'none' }">
                    <div id="feedbackDescriptionUpperpart">
                      <div class="cursor-pointer" style="float: right;" @click="hideFeedbackDescription">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                          stroke="currentColor" class="w-6 h-6">
                          <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                    </div>
                    <div id="feedbackDescriptionLowerPart">
                      <label id="feedbackMessage">{{ activeFeedback?.description }}</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer v-if="preview.show_footer" class="footer py-8">
      <div class="container mx-auto px-4 text-center text-base text-gray-600">
        &copy; All Rights Reserved.
        <a href="https://www.planetnine.com" class="underline hover:text-black" target="_blank">
          Planet Nine
        </a> - {{ new Date().getFullYear() }}
      </div>
    </footer>

    <!-- Mobile Popup -->
    <div v-if="isMobilePopupVisible" id="mobilePopup"
      style="position:fixed; z-index:99999; bottom:5px; left:0; right: 0; margin: 0 auto; background:#fff; border-radius:16px; box-shadow:0 2px 16px #0002; padding:24px 32px; text-align:center; max-width:90vw;">
      <button id="closeMobilePopup" @click="isMobilePopupVisible = false; setCookie('hideMobilePopup', '1', 7)"
        style="position:absolute; top:8px; right:12px; background:none; border:none; font-size:1.5rem; color:#888; cursor:pointer;">&times;</button>
      <div style="font-size:1.2rem; font-weight:600; color:#222; margin-bottom:8px;">
        Switch to desktop or laptop for better view.
      </div>
      <div style="font-size:0.95rem; color:#666;">
        For the best experience, please use a desktop or laptop device.
      </div>
    </div>

    <!-- Image Modal -->
    <div v-if="isImageModalVisible" id="socialImageModal"
      style="position:fixed; z-index:9999; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.85); display:flex; align-items:center; justify-content:center;"
      @click="closeSocialImageModal">
      <span id="closeSocialModal" @click="closeSocialImageModal"
        style="position:fixed; top:30px; right:40px; font-size:2.5rem; color:red; cursor:pointer; z-index:10001;">&times;</span>
      <img :id="'socialModalImg'" :src="modalImageSrc" :alt="modalImageAlt"
        style="max-width:90vw; max-height:90vh; cursor:zoom-in; display:block; margin:auto; padding:40px; background:rgba(0,0,0,0.1); border-radius:12px; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%) scale(1); transition:transform 0.3s ease; user-select:none; pointer-events:auto; transform-origin:center center;"
        @click.stop>
    </div>
  </div>
</template>

<style scoped>
/* Component-specific styles here */
@import url('https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap');

*,
*:before,
*:after {
  box-sizing: border-box
}

html {
  height: 100%
}

body {
  position: relative;
  margin: 0;
  min-height: 100%;
  box-shadow: inset 0 0 30px 25px #fff;
  background-color: var(--secondary-color);
  font-family: "Montserrat", sans-serif;
  display: flex;
  flex-direction: column;
  overflow-x: hidden
}

.main {
  flex: 1 0 auto
}

/* Scrollbar */
::-webkit-scrollbar {
  width: 8px
}

::-webkit-scrollbar-track {
  box-shadow: inset 0 0 5px var(--primary-color);
  border-radius: 10px
}

::-webkit-scrollbar-thumb {
  background: var(--tertiary-color);
  border-radius: 10px
}

section {
  position: relative;
  width: 100%;
  height: auto
}

hr {
  background-color: var(--primary-color) !important;
  width: 85%;
  height: 1px;
  border: none;
  margin: 0 auto
}

.single-div {
  display: inline-block
}

#planetnineLogo {
  width: 100%;
  max-width: 210px
}

#polygon {
  position: absolute;
  top: -30%;
  right: 0;
  width: 100%;
  height: auto;
  max-width: 500px;
  min-width: 500px;
  transform-origin: center
}

.custom-radius {
  border-bottom-left-radius: 0 !important;
  border-bottom-right-radius: 0 !important
}

.version-bar {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  justify-content: space-between;
  color: white
}

.left,
.right {
  display: flex;
  align-items: center;
  height: 100%
}

#topDetails {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  color: black;
  background: url('/preview_images/top-bg.png') no-repeat center center;
  background-size: contain;
  width: 100%;
  min-height: 170px;
  position: relative;
  overflow: hidden;
  box-sizing: border-box
}

/* Loader */
#loaderArea {
  position: fixed;
  top: 0;
  left: 0;
  background: rgba(0, 0, 0, .75);
  width: 100%;
  height: 100%;
  z-index: 9999;
  display: none;
  justify-content: center
}

.loader {
  position: relative;
  top: 50%;
  width: 48px;
  height: 48px;
  border: 5px solid #fff;
  border-radius: 50%;
  display: inline-block;
  box-sizing: border-box;
  animation: rotation 1s linear infinite;
  border-bottom-color: #1b283b;
  border-right-color: #f15a29
}

@keyframes rotation {
  0% {
    transform: rotate(0deg)
  }

  100% {
    transform: rotate(360deg)
  }
}

.footer {
  flex-shrink: 0
}

/* Banner Showcase */
#bannershowCustom {
  width: 100%;
  height: auto;
  min-height: 600px;
  position: relative;
  display: flex;
  overflow: hidden;
  padding-bottom: 20px
}

#categoryInfo {
  position: relative;
  display: block;
  width: fit-content;
  height: auto;
  border: 1px solid;
  color: white;
  border-bottom-left-radius: 20px;
  border-bottom-right-radius: 20px;
  text-align: center;
  left: 0;
  right: 0;
  margin: 0 auto;
  transform: translateY(-30px)
}

#categoryLabel {
  padding: 40px;
  word-break: break-word
}

#showcase {
  position: relative;
  width: 100%;
  height: auto
}

#bannerShowcase {
  width: 100%;
  height: auto;
  text-align: center;
  position: relative;
  top: 50%;
  transform: translateY(-50%);
  padding-bottom: 2rem;
  padding-right: 40px;
  margin-top: 0;
  min-height: 300px
}

/* Feedback Tabs */
.trapezoid-container {
  max-height: 35px;
  height: 35px;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center
}

.trapezoid {
  width: 100%;
  height: 100%
}

.tab-text {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%)
}

.feedbackTabsContainer {
  display: flex;
  flex-direction: row;
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-width: none;
  -ms-overflow-style: none;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  position: relative;
  top: 1px;
  gap: 2px
}

.feedbackTabsContainer.dragging {
  cursor: grabbing;
  user-select: none
}

.feedbackTabsContainer::-webkit-scrollbar {
  display: none
}

.feedbackTab {
  cursor: pointer;
  --r: 18px;
  line-height: 1.8;
  padding-inline: .5em;
  border-inline: var(--r) solid #0000;
  border-radius: calc(2.5*var(--r)) calc(2.5*var(--r)) 0 0/var(--r);
  mask: radial-gradient(var(--r) at var(--r) 0, #0000 98%, #000 101%) calc(-1*var(--r)) 100%/100% var(--r) repeat-x, conic-gradient(#000 0 0) padding-box;
  background: var(--primary-color) border-box;
  flex: 0 0 auto;
  min-width: 110px;
  width: 130px;
  max-width: 140px
}

.feedbackTab:hover,
.feedbackTabActive {
  background: var(--tertiary-color) border-box
}

.feedbackTabs-parent {
  flex: 1 1 0;
  min-width: 0;
  width: 97%
}

/* Side Elements */
#feedbackArea {
  position: absolute;
  top: 4rem;
  right: 0;
  z-index: 99
}

.previewIcons,
#bannerRes,
#bannerSize {
  color: var(--tertiary-color)
}

#feedbackCLick,
#colorPaletteClick,
#bulk-customization {
  font-size: 1.5rem;
  border: 1px solid var(--tertiary-color);
  position: absolute;
  right: -1.75rem;
  color: white;
  cursor: pointer;
  --r: 20px;
  padding-inline: .5em;
  border-inline: var(--r) solid #0000;
  border-radius: calc(2*var(--r)) calc(2*var(--r)) 0 0/var(--r);
  mask: radial-gradient(var(--r) at var(--r) 0, #0000 98%, #000 101%) calc(-1*var(--r)) 100%/100% var(--r) repeat-x, conic-gradient(#000 0 0) padding-box;
  background: var(--tertiary-color) border-box;
  width: fit-content;
  transform: rotate(-90deg)
}

#feedbackCLick {
  top: 2rem
}

#colorPaletteClick {
  top: 9rem
}

#bulk-customization {
  position: fixed;
  top: 9rem
}

#feedbackDescription {
  display: flex;
  flex-direction: column;
  transform: translateX(310px);
  opacity: 0;
  display: none
}

/* Color Palette */
#colorPaletteSelection,
#mobilecolorPaletteSelection {
  opacity: 0;
  pointer-events: none;
  transform: translateX(100px);
  transition: opacity .3s ease, transform .3s ease;
  z-index: 9999;
  position: absolute;
  right: 0;
  background: #f2f2f2;
  padding: 7px;
  max-width: 330px;
  border-top-left-radius: 10px;
  border-bottom-left-radius: 10px
}

#colorPaletteSelection {
  top: 8rem
}

#mobilecolorPaletteSelection {
  display: none;
  top: 12rem
}

#colorPaletteSelection.visible,
#mobilecolorPaletteSelection.visible {
  opacity: 1;
  pointer-events: auto;
  transform: translateX(0)
}

#mobilecolorPaletteSelection.visible {
  display: block
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .5rem
}

#mobilecolorPaletteSelection.color-grid {
  gap: .25rem
}

.color-box {
  color: #000;
  border: 1px solid #aaa;
  width: 30px;
  height: 30px;
  border-radius: 5px;
  font-family: sans-serif;
  white-space: pre-line;
  text-align: center;
  line-height: 30px;
  cursor: pointer
}

.mobile-color-box {
  color: #000;
  border: 1px solid #aaa;
  width: 25px;
  height: 25px;
  border-radius: 5px;
  font-family: sans-serif;
  white-space: pre-line;
  text-align: center;
  line-height: 25px;
  cursor: pointer
}

/* Sidebar */
.sidebar-image-div {
  border-top-left-radius: 25px;
  border-left: 3px solid var(--tertiary-color);
  border-top: 3px solid var(--tertiary-color);
  background-color: var(--primary-color)
}

.sidebar-image {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background: url("/preview_images/sidebar-image.png") no-repeat center center;
  background-size: contain;
  color: black;
  padding: 16px 10px;
  text-align: center;
  font-size: 1.05rem;
  border-radius: 5px;
  width: 100%;
  max-width: 250px;
  min-height: 65px;
  box-sizing: border-box;
  overflow: hidden
}

.video-title,
.social-title {
  color: black
}

/* Modal */
#socialImageModal {
  align-items: center;
  justify-content: center;
  overflow: auto
}

#socialModalImg {
  display: block;
  margin: auto;
  max-width: 80vw;
  max-height: 80vh;
  transition: transform .2s;
  cursor: zoom-in
}

#feedbackDescriptionUpperpart {
  border: 1px solid var(--tertiary-color);
  border-top-left-radius: 10px;
  padding: 5px;
  background-color: var(--tertiary-color);
  color: white
}

#feedbackDescriptionLowerPart {
  background-color: #f2f2f2;
  color: black;
  padding: 5px;
  height: auto;
  width: 300px;
  white-space: pre-line;
  text-overflow: ellipsis;
  border-bottom-left-radius: 10px
}

/* Mobile Menu */
#mobileMenuToggle {
  position: fixed;
  top: 19rem;
  left: 2.5px;
  z-index: 10001;
  background: var(--tertiary-color);
  border: 1px solid var(--tertiary-color);
  border-radius: 50%;
  width: 35px;
  height: 35px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
  cursor: pointer;
  color: white
}

#mobileMenuToggle.hidden {
  display: none
}

.mobile-menu-panel {
  position: fixed;
  top: 0;
  left: 0;
  width: 70vw;
  max-width: 350px;
  height: 100vh;
  background: var(--primary-color);
  z-index: 10000;
  padding: 60px 20px 20px 20px;
  box-shadow: 2px 0 12px rgba(0, 0, 0, .2);
  transform: translateX(-100%);
  transition: transform .3s ease;
  opacity: 1;
  overflow-y: auto;
  list-style: none
}

.mobile-menu-panel.open {
  transform: translateX(0)
}

#closeMobileMenu {
  position: absolute;
  top: 20px;
  right: 20px;
  font-size: 2rem;
  color: white;
  background: none;
  border: none;
  cursor: pointer
}

#mobileCategoryList {
  list-style: none;
  padding: 0;
  margin: 0
}

#mobileCategoryList li {
  padding: 15px 0;
  font-size: 1.2rem;
  border-bottom: 1px solid var(--tertiary-color);
  color: var(--tertiary-color);
  cursor: pointer
}

#mobileCategoryList li.active {
  color: #fff;
  font-weight: bold
}

#mobilecolorPaletteClick {
  font-size: 1rem;
  border: 1px solid var(--tertiary-color);
  position: absolute;
  right: -1.2rem;
  top: 13rem;
  color: white;
  cursor: pointer;
  --r: 15px;
  padding-inline: .5em;
  border-inline: var(--r) solid #0000;
  border-radius: calc(2*var(--r)) calc(2*var(--r)) 0 0/var(--r);
  mask: radial-gradient(var(--r) at var(--r) 0, #0000 98%, #000 101%) calc(-1*var(--r)) 100%/100% var(--r) repeat-x, conic-gradient(#000 0 0) padding-box;
  background: var(--tertiary-color) border-box;
  width: fit-content;
  transform: rotate(-90deg);
  z-index: 99
}

/* Social Grid */
.columnSocial {
  display: inline-block;
  padding: 0 5px
}

.columnSocial img {
  cursor: pointer
}

.columnSocial img:hover {
  opacity: 1
}

.rowSocial:after {
  content: "";
  display: table;
  clear: both
}

.imageContainerSocial {
  position: relative;
  display: none
}

.imagesSocial {
  width: 300px;
  height: auto;
  border-radius: 10px;
  border: 1px solid #dedede
}

.rowSocial {
  max-width: 1280px;
  margin: 0 auto;
  padding: 1rem;
  text-align: center
}

/* Modal Styles */
.modalSocial {
  display: none;
  position: fixed;
  z-index: 9999;
  padding-top: 100px;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, .9)
}

.modal-contentSocial {
  margin: auto;
  display: block;
  width: 100%
}

#captionSocial,
#anotherCaptionSocial {
  margin: auto;
  display: block;
  width: 100%;
  max-width: 700px;
  text-align: center;
  color: #ccc;
  padding: 10px 0;
  height: auto;
  transform-origin: center
}

.modal-contentSocial,
#captionSocial,
#anotherCaptionSocial {
  animation: zoom .6s
}

@keyframes zoom {
  from {
    transform: scale(0)
  }

  to {
    transform: scale(1)
  }
}

.closeSocial {
  position: absolute;
  top: 15px;
  right: 25px;
  color: #f1f1f1;
  font-size: 2rem;
  font-weight: bold;
  transition: .3s
}

.closeSocial:hover,
.closeSocial:focus {
  color: #bbb;
  text-decoration: none;
  cursor: pointer
}

.alt-wrapSocial {
  display: block;
  position: relative;
  margin: 20px;
  color: whitesmoke
}

.alt-wrapSocial p.alt {
  position: absolute;
  opacity: 0;
  left: 0;
  right: 0;
  bottom: 0;
  margin: 0;
  padding: 5px;
  font-size: 1rem;
  line-height: 13px;
  background-color: rgba(0, 0, 0, .8);
  transition: all 300ms ease;
  transition-delay: 100ms;
  overflow: hidden;
  border-bottom-left-radius: 10px;
  border-bottom-right-radius: 10px
}

.alt-wrapSocial:hover>p.alt {
  opacity: 1;
  transition-delay: 0s
}

/* Layout */
.custom-container {
  width: 100vw;
  max-width: 100vw;
  box-sizing: border-box
}

#navbar {
  max-width: 230px;
  width: 100%;
  min-width: 210px;
  color: #fff;
  padding-bottom: 1.75rem;
  min-height: 300px;
  height: auto;
  flex-direction: column;
  align-items: stretch;
  overflow-y: auto
}

.tabDesktopShowcase {
  display: flex
}

.client-logo-div {
  display: none;
  background-color: var(--primary-color);
  border-right: 3px solid var(--tertiary-color)
}

#clientLogoSection {
  background: #fff;
  display: flex;
  justify-content: center;
  margin-bottom: 10px;
  border-radius: 40px;
  width: 80%
}

#creative-list {
  display: none;
  padding: 20px;
  list-style-type: disclosure-closed
}

.nav-link {
  text-decoration: none;
  display: block;
  margin-bottom: 0.5rem;
  font-size: 1rem;
  background-color: #f5f5f5;
  color: var(--tertiary-color);
  padding: 0.5rem;
  border-radius: 15px;
  white-space: nowrap;
  text-align: center;
}

.right-column {
  flex: 1;
  padding: 0 5px 20px 5px;
  border: 3px solid var(--tertiary-color);
  border-top-right-radius: 25px;
  border-bottom-left-radius: 25px;
  border-bottom-right-radius: 25px;
  box-shadow: rgba(0, 0, 0, .15) -5.05px 6.95px 14.6px
}

.mobileShowcase {
  position: absolute;
  top: -27px
}

.viewMessage {
  text-align: center;
  background: rgba(247, 76, 76);
  color: white;
  height: 50px;
  display: none;
  justify-content: center;
  align-items: center;
  padding: 3px
}

#creative-list2,
#creative-list {
  max-height: 70vh;
  overflow-y: auto
}

#creative-list2 {
  font-size: 1.25rem;
  padding: 10px 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
  justify-content: center;
  align-items: center;
  background-color: var(--primary-color);
  border-left: 3px solid var(--tertiary-color);
  border-bottom-left-radius: 25px;
  border-bottom: 3px solid var(--tertiary-color);
  box-shadow: rgba(0, 0, 0, .15) 6.95px 8.95px 7.6px
}

#showcase-section {
  padding-left: 2rem;
  padding-right: 2rem
}

.currentTotalFeedbacks {
  display: flex;
  color: var(--tertiary-color)
}

.category-row {
  text-align: center;
  width: calc(100% - 40px);
  max-width: 200px;
  min-height: 60px;
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
  overflow: hidden;
  cursor: pointer;
  background-color: white;
  color: var(--quaternary-color);
  border-radius: 40px;
  border-bottom: 4px solid black;
  transition: all .25s;
  flex-direction: column;
  margin: 0 auto 10px auto;
  word-break: break-word
}

.category-row:hover,
.category-active {
  background-color: var(--tertiary-color);
  color: white;
  border-bottom: 4px solid var(--tertiary-color)
}

.category-active {
  box-shadow: 0 2px 8px rgba(0, 0, 0, .08)
}

.category-row span {
  overflow: hidden;
  text-overflow: ellipsis;
  display: block;
  max-width: 90%
}

.sidebar-top-desktop {
  max-width: 230px;
  width: 100%;
  min-width: 210px;
  min-height: 60px
}

.feedbackSetsContainer {
  margin-top: 3rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  gap: 1rem
}

.feedbackSet {
  width: calc(100% - 5rem);
  border-radius: 1rem;
  background-color: var(--primary-color);
  padding: .5rem;
  color: white
}

.feedbackSetName {
  font-weight: bold;
  text-align: center
}

.feedbacks {
  width: 100%;
  min-width: 0
}

.versions {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: center;
  align-items: center
}

.version-title {
  color: var(--tertiary-color)
}

.selectedFeedback {
  border: 1px solid var(--tertiary-color);
  border-radius: 10px;
  padding: 5px
}

/* Mobile Responsive */
@media (max-width:480px) {

  #polygon,
  #preview-shapes {
    display: none
  }

  #planetnineLogo {
    margin: 0 auto;
    text-align: center;
    padding-top: 0
  }
}

@media (min-width:481px) and (max-width:768px) {
  #preview-shapes {
    display: none
  }
}

@media (min-width:769px) and (max-width:1024px) {
  #polygon {
    max-width: 400px;
    min-width: 400px
  }

  #preview-shapes {
    display: none
  }
}

@media (max-width:700px) {
  .feedbackTab {
    width: 90px;
    min-width: 70px;
    font-size: .95rem
  }

  .category-row {
    max-width: 100%;
    min-height: 45px;
    font-size: 1rem;
    margin: 0 0 8px 0;
    border-radius: 20px
  }

  .banners-list {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center
  }

  .feedbackTab {
    width: 110px
  }

  #topDetails {
    background: url('/preview_images/white-smart.png') no-repeat center center
  }

  .banner-creatives {
    margin-right: 0 !important
  }

  /* Banner scaling for mobile */
  .banner-area-1080-1080 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1080-1080 iframe {
    transform: scale(.311);
    transform-origin: center left;
    width: 1080px;
    height: 1080px;
    border: none;
    display: block;
    margin-top: -370px !important;
    margin-bottom: -378px !important
  }

  .banner-area-970-250 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-970-250 iframe {
    transform: scale(.346);
    transform-origin: center left;
    width: 970px;
    height: 250px;
    border: none;
    display: block;
    margin-top: -75px !important;
    margin-bottom: -83px !important
  }

  .banner-area-728-90 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-728-90 iframe {
    transform: scale(.4615);
    transform-origin: center left;
    width: 728px;
    height: 90px;
    border: none;
    display: block;
    margin-top: -20px !important;
    margin-bottom: -28px !important
  }

  .banner-area-468-60 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-468-60 iframe {
    transform: scale(.718);
    transform-origin: center left;
    width: 468px;
    height: 60px;
    border: none;
    display: block;
    margin-top: -10px !important;
    margin-bottom: -10px !important
  }

  .modal-contentSocial {
    width: 100%
  }

  .alt-wrapSocial p.alt {
    display: none
  }

  #creative-list {
    display: block
  }

  #creative-list2,
  .categoryControl,
  .feedbackAddTab,
  #customPreviewLogoutForm {
    display: none
  }

  #showcase-section {
    padding-left: .1rem;
    padding-right: 0
  }

  .right-column {
    border-radius: 0;
    border: none;
    padding: 0;
    box-shadow: none
  }

  #bannerShowcase {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    justify-content: center;
    align-items: center;
    padding-right: 0;
    top: 0;
    transform: translateY(0);
    margin-top: 60px
  }

  #showcase {
    overflow: hidden;
    border-radius: 0;
    z-index: 99;
    border-top: var(--tertiary-color) 3px solid;
    border-bottom: var(--tertiary-color) 3px solid
  }

  body {
    box-shadow: inset 0 0 20px 5px #fff;
    overflow-x: hidden !important
  }

  #colorPaletteClick {
    position: fixed;
    font-size: 1.25rem;
    line-height: 1.8;
    display: none
  }

  #feedbackCLick {
    position: fixed;
    font-size: 1rem;
    top: 19rem;
    --r: 15px;
    right: -1.2rem
  }

  #feedbackDescription {
    position: fixed;
    right: 0;
    top: 20rem
  }

  .feedbacks {
    width: 100%
  }
}

@media (max-width:600px) {
  .category-blank-space {
    display: none
  }

  #bannershowCustom {
    display: block
  }

  .clientMobileLogo,
  .mobileShowcase,
  .viewMessage {
    display: block
  }

  .tabDesktopShowcase,
  .categoryControl,
  .feedbackAddTab,
  #customPreviewLogoutForm,
  .sidebar-top-desktop,
  .sidebar-top-extra {
    display: none
  }

  .sidebar-top-tab-mobile {
    display: block
  }

  .banner-options,
  #bulk-customization {
    display: none
  }

  #feedbackDescription {
    position: fixed;
    right: 0;
    top: 18rem
  }

  .feedbacks {
    width: 100%
  }
}

@media (min-width:768px) and (max-width:991px) {
  .feedbackTab {
    width: 90px;
    min-width: 70px;
    font-size: .95rem
  }

  .category-row {
    max-width: 100%;
    min-height: 45px;
    font-size: 1rem;
    margin: 0 0 8px 0;
    border-radius: 20px
  }

  .banners-list {
    display: flex;
    flex-direction: row;
    justify-content: space-evenly;
    align-items: center;
    flex-wrap: wrap;
  }

  .feedbackTab {
    width: 110px
  }

  #topDetails {
    background: url('/preview_images/white-smart.png') no-repeat center center
  }

  .banner-creatives {
    margin-right: 0 !important
  }

  /* Banner scaling for tablet - slightly larger than mobile */
  .banner-area-1080-1080 {
    width: 728px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1080-1080 iframe {
    transform: scale(.674);
    transform-origin: center left;
    width: 1080px;
    height: 1080px;
    border: none;
    display: block;
    margin-top: -175px !important;
    margin-bottom: -175px !important
  }

  .banner-area-970-250 {
    width: 728px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-970-250 iframe {
    transform: scale(.75);
    transform-origin: center left;
    width: 970px;
    height: 250px;
    border: none;
    display: block;
    margin-top: -30px !important;
    margin-bottom: -30px !important
  }

  .banner-area-728-90 {
    width: 728px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-728-90 iframe {
    transform: scale(1);
    transform-origin: center left;
    width: 728px;
    height: 90px;
    border: none;
    display: block;
    margin-top: 0 !important;
    margin-bottom: 0 !important
  }

  .banner-area-468-60 {
    width: 468px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-468-60 iframe {
    transform: scale(1);
    transform-origin: center left;
    width: 468px;
    height: 60px;
    border: none;
    display: block;
    margin-top: -1px !important;
    margin-bottom: -1px !important
  }

  .modal-contentSocial {
    width: 100%
  }

  .alt-wrapSocial p.alt {
    display: none
  }

  #creative-list {
    display: block
  }

  #creative-list2,
  .categoryControl,
  .feedbackAddTab,
  #customPreviewLogoutForm {
    display: none
  }

  #showcase-section {
    padding-left: 1rem;
    padding-right: 0.5rem
  }

  .right-column {
    border-radius: 0;
    border: none;
    padding: 0;
    box-shadow: none
  }

  #bannerShowcase {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    justify-content: center;
    align-items: center;
    padding-right: 0;
    top: 0;
    transform: translateY(0);
    margin-top: 60px
  }

  #showcase {
    overflow: hidden;
    border-radius: 0;
    z-index: 99;
    border-top: var(--tertiary-color) 3px solid;
    border-bottom: var(--tertiary-color) 3px solid
  }

  body {
    box-shadow: inset 0 0 20px 5px #fff;
    overflow-x: hidden !important
  }

  #colorPaletteClick {
    position: fixed;
    font-size: 1.25rem;
    line-height: 1.8;
    display: none
  }

  #feedbackCLick {
    position: fixed;
    font-size: 1rem;
    top: 19rem;
    --r: 15px;
    right: -1.2rem
  }

  #feedbackDescription {
    position: fixed;
    right: 0;
    top: 20rem
  }

  .feedbacks {
    width: 100%
  }

  /* Tablet specific adjustments */
  .category-blank-space {
    display: none
  }

  #bannershowCustom {
    display: block
  }

  .clientMobileLogo,
  .mobileShowcase,
  .viewMessage {
    display: block
  }

  .tabDesktopShowcase,
  .sidebar-top-desktop,
  .sidebar-top-extra {
    display: none
  }

  .sidebar-top-tab-mobile {
    display: block
  }

  .banner-options,
  #bulk-customization {
    display: none
  }

  #mobilecolorPaletteClick,
  #mobilecolorPaletteSelection {
    display: block
  }

  /* Show mobile menu toggle for tablets */
  #mobileMenuToggle {
    display: flex
  }
}

@media (min-width:992px) {

  #mobilecolorPaletteClick,
  #mobilecolorPaletteSelection {
    display: none
  }

  .banner-options,
  .previewIcons,
  .categoryControl,
  .feedbackAddTab,
  #customPreviewLogoutForm,
  .sidebar-top-desktop,
  .sidebar-top-extra,
  #bulk-customization {
    display: block
  }

  .category-blank-space {
    display: block
  }

  #bannershowCustom {
    display: flex
  }

  .clientMobileLogo,
  .mobileShowcase,
  .viewMessage {
    display: none
  }

  .tabDesktopShowcase {
    display: block
  }

  .sidebar-top-tab-mobile {
    display: none
  }

  .right-column {
    border-top-left-radius: 0
  }

  .feedbacks {
    width: 97%
  }

  #feedbackDescription {
    top: 23rem
  }
}

@media (min-width:1200px) {

  .previewIcons,
  .category-blank-space,
  .tabDesktopShowcase,
  .categoryControl,
  .feedbackAddTab,
  #customPreviewLogoutForm,
  .sidebar-top-desktop,
  .sidebar-top-extra {
    display: block
  }

  #bannershowCustom {
    display: flex
  }

  .clientMobileLogo,
  .mobileShowcase,
  .viewMessage {
    display: none
  }

  .sidebar-top-tab-mobile {
    display: none
  }

  .feedbacks {
    width: 97%
  }

  #feedbackDescription {
    top: 23rem
  }
}
</style>