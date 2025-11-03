<template>
  <div id="app">

    <Head :title="`Creative - ${preview.name}`" />

    <!-- Viewer List for Planet Nine users -->
    <div v-if="auth.user" class="absolute top-4 right-4 flex items-center space-x-3 z-50">
      <div v-if="authUserClientName === 'Planet Nine'" id="viewerList" class="flex space-x-2">
        <span v-for="viewer in viewers" :key="viewer"
          class="bg-blue-100 text-blue-900 font-semibold rounded-full px-3 py-1 text-sm shadow-sm" :title="viewer">
          {{ viewer.trim().charAt(0).toUpperCase() }}
        </span>
      </div>

      <!-- Logout Button -->
      <form v-if="preview.requires_login" @submit.prevent="logout" class="inline">
        <button type="submit"
          class="bg-red-500 hover:bg-red-600 text-white text-sm font-medium px-3 py-2 rounded-xl shadow transition cursor-pointer">
          Logout
        </button>
      </form>
    </div>

    <!-- Loader -->
    <div v-show="isLoading" id="loaderArea">
      <span class="loader"></span>
    </div>

    <!-- Settings Link for Planet Nine -->
    <a v-if="authUserClientName === 'Planet Nine'" :href="`/previews/update/${preview.id}`" id="bulk-customization"
      class="text-white font-medium cursor-pointer">
      <i class="fa-solid fa-gear"></i>
    </a>

    <main class="main">
      <!-- Header Section -->
      <section id="top" class="mb-4">
        <div class="px-4 py-4 flex justify-center content text-center relative">
          <div id="topDetails" class="mt-4" :style="{ backgroundImage: `url('/${headerImage}')` }">
            <img v-if="preview.show_planetnine_logo" :src="`/logos/${headerLogo.logo}`" id="planetnineLogo"
              alt="planetnineLogo">
            <h1>
              <span class="font-semibold">Name: </span>
              <span class="capitalize">{{ preview.name }}</span>
            </h1>
            <h1>
              <span class="font-semibold">Client: </span>
              <span class="capitalize">{{ client.name }}</span>
            </h1>
            <h1>
              <span class="font-semibold">Date: </span>
              <span>{{ formatDate(preview.created_at) }}</span>
            </h1>
          </div>
        </div>
      </section>

      <!-- Mobile Color Palette -->
      <div id="mobilecolorPaletteClick" @click="showColorPaletteOptions2">
        <img :src="`/${rightTabColorPaletteImage}`" alt="palette icon">
      </div>
      <div id="mobilecolorPaletteSelection" ref="mobileColorPalette"></div>

      <!-- Main Content Section -->
      <section id="middle" class="mb-4">
        <div id="showcase-section" class="mx-auto custom-container mt-2">

          <!-- Top Row with Logo and Feedback Tabs -->
          <div class="flex row justify-around items-end" style="min-height: 50px;">
            <div class="py-2 flex items-end justify-center sidebar-top-desktop">
              <img v-if="preview.show_sidebar_logo" :src="`/logos/${client.logo}`" alt="clientLogo"
                style="min-width:50px; width: 100%; max-width: 120px; margin: 0 auto;">
            </div>
            <div style="flex: 1;" class="feedbackTabs-parent">
              <div class="feedbacks relative flex justify-center flex-row" v-html="feedbackTabsHtml"></div>
            </div>
          </div>

          <!-- Main Showcase Area -->
          <div id="showcase">
            <div id="bannershowCustom">

              <!-- Mobile Menu -->
              <nav role="navigation" class="mobileShowcase">
                <div id="mobileMenuToggle">
                  <button id="openMobileMenu" @click="openMobileMenu" aria-label="Open menu">
                    <i class="fa-solid fa-bars"></i>
                  </button>
                </div>
                <div id="mobileMenu" :class="{ open: isMobileMenuOpen }" class="mobile-menu-panel">
                  <button id="closeMobileMenu" @click="closeMobileMenu" aria-label="Close menu">&times;</button>
                  <div v-if="preview.show_sidebar_logo" class="w-full">
                    <div class="mb-2 mt-2 px-2 py-2 mx-auto flex justify-center">
                      <img :src="`/logos/${client.logo}`" alt="clientLogo" style="width: 180px;">
                    </div>
                  </div>
                  <div class="sidebar-image mx-auto mb-4">
                    <span>Creative Showcase</span>
                  </div>
                  <ul id="mobileCategoryList" v-html="mobileCategoryListHtml"></ul>
                </div>
              </nav>

              <!-- Desktop Sidebar -->
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
                <div id="creative-list2" v-html="categoryListHtml"></div>
              </div>

              <!-- Right Column with Content -->
              <div class="right-column">
                <div
                  class="justify-center items-center mt-1 py-2 px-2 relative top-0 left-0 right-0 currentTotalFeedbacks">
                  <span id="feedbackCounter" v-html="feedbackCounterHtml"></span>
                </div>

                <div class="feedbackSetsContainer" v-html="feedbackSetsHtml"></div>

                <!-- Feedback Area -->
                <div id="feedbackArea">
                  <div id="feedbackCLick" @click="showFeedbackDescription">
                    <img :src="`/${rightTabFeedbackDescriptionImage}`" alt="feedback icon">
                  </div>

                  <div id="colorPaletteClick" @click="showColorPaletteOptions">
                    <img :src="`/${rightTabColorPaletteImage}`" alt="palette icon">
                  </div>

                  <div id="colorPaletteSelection" ref="colorPalette"></div>

                  <div id="feedbackDescription" :class="{ show: showFeedbackPanel }">
                    <div id="feedbackDescriptionUpperpart">
                      <div class="cursor-pointer" style="float: right; padding: 5px;" @click="hideFeedbackDescription">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                          stroke="currentColor" class="w-6 h-6">
                          <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                    </div>
                    <div id="feedbackDescriptionLowerPart">
                      <label id="feedbackMessage">{{ currentFeedbackMessage }}</label>
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
        </a> - {{ currentYear }}
      </div>
    </footer>

    <!-- Social Image Modal -->
    <div id="socialImageModal" v-show="socialModalVisible" @click="closeSocialModal"
      style="position:fixed; z-index:9999; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.85); display:flex; align-items:center; justify-content:center;">
      <span id="closeSocialModal" @click="closeSocialModal"
        style="position:fixed; top:30px; right:40px; font-size:2.5rem; color:red; cursor:pointer; z-index:10001;">&times;</span>
      <img id="socialModalImg" :src="socialModalSrc" :alt="socialModalAlt" :style="socialModalImageStyle" @click.stop
        @mousedown="startDrag"
        style="max-width:80vw; max-height:80vh; transition:transform 0.2s; cursor:zoom-in; display:block; margin:auto; padding:40px; background:rgba(0,0,0,0.1); border-radius:12px;">

      <!-- Zoom Controls -->
      <div id="zoomControls"
        style="position: fixed; top: 20px; left: 20px; z-index: 10002; display: flex; flex-direction: column; gap: 10px;">
        <button @click="zoomIn"
          style="background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center;">+</button>
        <button @click="zoomOut"
          style="background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center;">−</button>
        <button @click="resetZoom"
          style="background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 20px; padding: 8px 12px; font-size: 12px; cursor: pointer;">Reset</button>
      </div>

      <div id="zoomInfo"
        style="position: fixed; bottom: 20px; left: 20px; z-index: 10002; background: rgba(0,0,0,0.7); color: white; padding: 8px 12px; border-radius: 15px; font-size: 12px;">
        Zoom: <span>{{ Math.round(modalZoomScale * 100) }}%</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'

// Props from Inertia
const props = defineProps({
  preview: Object,
  client: Object,
  headerImage: String,
  headerLogo: Object,
  rightTabColorPaletteImage: String,
  rightTabFeedbackDescriptionImage: String,
  feedbackActiveImage: String,
  feedbackInactiveImage: String,
  authUserClientName: String,
  previewId: String,
  allColors: Array,
  primary: String,
  secondary: String,
  tertiary: String,
  quaternary: String,
  quinary: String,
  senary: String,
  septenary: String,
  auth: Object
})

// Reactive state
const isLoading = ref(false)
const isMobileMenuOpen = ref(false)
const showFeedbackPanel = ref(false)
const viewers = ref([])
const categories = ref([])
const feedbacks = ref([])
const currentCategoryIndex = ref(0)
const currentFeedbackIndex = ref(0)
const currentFeedbackMessage = ref('')
const feedbackTabsHtml = ref('')
const categoryListHtml = ref('')
const mobileCategoryListHtml = ref('')
const feedbackCounterHtml = ref('')
const feedbackSetsHtml = ref('')

// Social Modal state
const socialModalVisible = ref(false)
const socialModalSrc = ref('')
const socialModalAlt = ref('')
const modalZoomScale = ref(1)
const modalCurrentX = ref(0)
const modalCurrentY = ref(0)
const isDragging = ref(false)

// Computed
const currentYear = computed(() => new Date().getFullYear())

const socialModalImageStyle = computed(() => ({
  transform: `translate(calc(-50% + ${modalCurrentX.value}px), calc(-50% + ${modalCurrentY.value}px)) scale(${modalZoomScale.value})`,
  transition: isDragging.value ? 'none' : 'transform 0.3s ease',
  position: 'absolute',
  top: '50%',
  left: '50%',
  transformOrigin: 'center center',
  userSelect: 'none',
  pointerEvents: 'auto'
}))

// Guest name for tracking
let guestName = localStorage.getItem('guest_name')
if (!guestName) {
  guestName = 'Guest-' + Math.floor(Math.random() * 10000)
  localStorage.setItem('guest_name', guestName)
}

// Methods
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const openMobileMenu = () => {
  isMobileMenuOpen.value = true
  document.body.style.overflow = 'hidden'
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
  document.body.style.overflow = ''
}

const showFeedbackDescription = () => {
  // Close color palette if open
  const paletteDiv = document.getElementById('colorPaletteSelection')
  if (paletteDiv) {
    paletteDiv.classList.remove('visible')
  }
  showFeedbackPanel.value = true
}

const hideFeedbackDescription = () => {
  showFeedbackPanel.value = false
}

const showColorPaletteOptions = () => {
  const paletteDiv = document.getElementById('colorPaletteSelection')
  if (!paletteDiv) return

  if (paletteDiv.innerHTML.trim() === '') {
    setupColorPalette(paletteDiv, false)
  }
  paletteDiv.classList.add('visible')
}

const showColorPaletteOptions2 = () => {
  const paletteDiv = document.getElementById('mobilecolorPaletteSelection')
  if (!paletteDiv) return

  if (paletteDiv.innerHTML.trim() === '') {
    setupColorPalette(paletteDiv, true)
  }
  paletteDiv.classList.add('visible')
}

const setupColorPalette = (paletteDiv, isMobile) => {
  paletteDiv.classList.add('color-grid')

  props.allColors.forEach(color => {
    const colorBox = document.createElement('div')
    colorBox.className = isMobile ? 'mobile-color-box' : 'color-box'
    colorBox.style.backgroundColor = color.primary
    colorBox.style.borderColor = color.tertiary
    colorBox.title = color.primary

    colorBox.addEventListener('click', () => {
      changeTheme(color.id)
    })

    paletteDiv.appendChild(colorBox)
  })
}

const changeTheme = async (colorId) => {
  try {
    const response = await axios.get(`/preview/${props.previewId}/change/theme/${colorId}`)
    if (response.data.success) {
      location.reload()
    } else {
      alert("Something went wrong changing theme")
    }
  } catch (error) {
    console.error('Error changing theme:', error)
  }
}

const logout = () => {
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = '/preview/logout'

  const csrfInput = document.createElement('input')
  csrfInput.type = 'hidden'
  csrfInput.name = '_token'
  csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content')

  const previewInput = document.createElement('input')
  previewInput.type = 'hidden'
  previewInput.name = 'preview_id'
  previewInput.value = props.preview.id

  form.appendChild(csrfInput)
  form.appendChild(previewInput)
  document.body.appendChild(form)
  form.submit()
}

// API calls
const fetchViewers = async () => {
  try {
    const response = await axios.get(`/get-viewers/${props.preview.id}`)
    viewers.value = response.data
  } catch (error) {
    console.error('Error fetching viewers:', error)
  }
}

const trackViewer = async () => {
  try {
    await axios.post('/track-viewer', {
      page_id: props.preview.id,
      guest_name: guestName
    })
  } catch (error) {
    console.error('Error tracking viewer:', error)
  }
}

const renderCategories = async () => {
  try {
    isLoading.value = true
    const response = await axios.get(`/preview/renderCategories/${props.previewId}`)

    categories.value = response.data.categories || []
    currentCategoryIndex.value = categories.value.findIndex(c => c.id == response.data.activeCategory.id)
    if (currentCategoryIndex.value === -1) currentCategoryIndex.value = 0

    // Build category HTML
    let categoryHtml = ''
    let mobileCategoryHtml = ''

    categories.value.forEach(category => {
      const isActive = category.is_active == 1
      const activeClass = isActive ? 'category-active' : ''
      const spanClass = isActive ? 'span-active' : ''
      const clickHandler = isActive ? '' : `onclick="updateActiveCategory(${category.id})"`

      const date = new Date(category.created_at)
      const formattedDate = `${date.getDate().toString().padStart(2, '0')}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getFullYear()}`

      const categoryHtml_item = `
        <div class="category-row ${activeClass}" ${clickHandler} id="category${category.id}">
          <span class="${spanClass}" style="font-size: 0.85rem;">${category.name}</span>
          <hr>
          <span class="category-row-date" style="font-size: 0.7rem;">${formattedDate}</span>
        </div>
      `

      categoryHtml += categoryHtml_item
      mobileCategoryHtml += categoryHtml_item
    })

    categoryListHtml.value = categoryHtml
    mobileCategoryListHtml.value = mobileCategoryHtml

    await renderFeedbacks(response)
  } catch (error) {
    console.error('Error rendering categories:', error)
  } finally {
    isLoading.value = false
  }
}

const renderFeedbacks = async (response) => {
  feedbacks.value = response.data.feedbacks || []
  currentFeedbackIndex.value = feedbacks.value.findIndex(f => f.is_active == 1)
  if (currentFeedbackIndex.value === -1) currentFeedbackIndex.value = 0

  let feedbackHtml = '<div class="feedbackTabsContainer">'

  feedbacks.value.forEach(feedback => {
    const isActive = feedback.is_active == 1
    const activeClass = isActive ? ' feedbackTabActive' : ''
    const clickHandler = isActive ? '' : `onclick="updateActiveFeedback(${feedback.id})"`
    const tabImagePath = isActive ? `/${props.feedbackActiveImage}` : `/${props.feedbackInactiveImage}`
    const hoverEvents = isActive ? '' : 'onmouseover="changeFeedbackActiveBackground(this)" onmouseout="changeFeedbackInactiveBackground(this)"'
    const approvedIndicator = feedback.is_approved == 1 ?
      '<div class="w-2 h-2 bg-green-700 rounded-full border border-white animate-pulse-green" style="margin-left: 5px; flex-shrink: 0;"></div>' : ''

    feedbackHtml += `
      <div style="display: flex; align-items: center; justify-content: center; flex-direction: column;">
        <div id="feedbackTab${feedback.id}" class="feedbackTab${activeClass}" ${clickHandler} ${hoverEvents}
          style="bottom: -1px; background-image: url('${tabImagePath}'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative; cursor: pointer; min-width: 110px; width: 100%; max-width: 110px; height: 35px;">
          <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 0.875rem; font-weight: 500; text-align: center; width: 100%; text-shadow: 1px 1px 2px rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center;">
            <span style="text-align: center;">${feedback.name}</span>${approvedIndicator}
          </div>
        </div>
      </div>
    `

    if (isActive) {
      currentFeedbackMessage.value = feedback.description
    }
  })

  feedbackHtml += '</div>'
  feedbackTabsHtml.value = feedbackHtml

  updateFeedbackNav()
  await renderFeedbackSets(response)
}

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

  let navigationHtml = ''

  if (total === 2) {
    if (isFirst) {
      navigationHtml = span(current, true) + btn('feedbackRight', '>', true) + span(current + 1)
    } else {
      navigationHtml = span(current - 1) + btn('feedbackLeft', '<') + span(current, true)
    }
  } else if (total === 3) {
    if (isFirst) {
      navigationHtml = span(current, true) + btn('feedbackRight', '>', true) + span(current + 1) + btn('feedbackFarRight', '>>') + span(total)
    } else if (currentFeedbackIndex.value === 1) {
      navigationHtml = span(1) + btn('feedbackLeft', '<<', true) + span(current, true) + btn('feedbackFarRight', '>>') + span(total)
    } else {
      navigationHtml = span(1) + btn('feedbackFarLeft', '<<') + span(current - 1) + btn('feedbackLeft', '<', true) + span(current, true)
    }
  } else if (total > 3) {
    if (isFirst) {
      navigationHtml = span(current, true) + btn('feedbackRight', '>', true) + span(current + 1) + btn('feedbackFarRight', '>>') + span(total)
    } else if (isLast) {
      navigationHtml = span(1) + btn('feedbackFarLeft', '<<') + span(current - 1) + btn('feedbackLeft', '<', true) + span(current, true)
    } else if (current === 2) {
      navigationHtml = span(1) + btn('feedbackLeft', '<', true) + span(current, true) + btn('feedbackRight', '>', true) + span(current + 1) + btn('feedbackFarRight', '>>') + span(total)
    } else if (current === total - 1) {
      navigationHtml = span(1) + btn('feedbackFarLeft', '<<') + span(current - 1) + btn('feedbackLeft', '<', true) + span(current, true) + btn('feedbackRight', '>', true) + span(total)
    } else {
      navigationHtml = span(1) + btn('feedbackFarLeft', '<<') + span(current - 1) + btn('feedbackLeft', '<', true) + span(current, true) + btn('feedbackRight', '>', true) + span(current + 1) + btn('feedbackFarRight', '>>') + span(total)
    }
  }

  feedbackCounterHtml.value = navigationHtml
}

const renderFeedbackSets = async (response) => {
  const feedbackSets = response.data.feedbackSets
  let setsHtml = ''

  feedbackSets.forEach(feedbackSet => {
    if (feedbackSet.name) {
      setsHtml += `
        <div class="feedbackSet" id="feedbackSet${feedbackSet.id}" style="display: flex; align-items: center; justify-content: space-between;">
          <div class="feedbackSetName" style="flex: 1; text-align: center;">
            ${feedbackSet.name}
          </div>
        </div>
      `
    }
    setsHtml += `<div class="versions" id="versions${feedbackSet.id}"></div>`
  })

  feedbackSetsHtml.value = setsHtml

  // Render versions for each feedbackSet
  for (const set of feedbackSets) {
    await renderVersions(set.id, response)
  }
}

const renderVersions = async (feedbackSetId, res) => {
  try {
    const response = await axios.get(`/preview/renderVersions/${feedbackSetId}`)
    const versions = response.data.versions
    let versionRows = ''

    versions.forEach(version => {
      versionRows += `
        <div>
          ${version.name ? `<div class="version-title" style="font-weight: bold;">${version.name}</div>` : ''}
          <div class="banners-list" id="bannersList${version.id}"></div>
        </div>
      `

      // Render content based on category type
      if (res.data.activeCategory.type === 'banner') {
        renderBanners(version.id)
      } else if (res.data.activeCategory.type === 'video') {
        renderVideo(version.id)
      } else if (res.data.activeCategory.type === 'social') {
        renderSocial(version.id)
      } else if (res.data.activeCategory.type === 'gif') {
        renderGif(version.id)
      }
    })

    // Update the versions container
    nextTick(() => {
      const versionsContainer = document.getElementById(`versions${feedbackSetId}`)
      if (versionsContainer) {
        versionsContainer.innerHTML = versionRows
      }
    })
  } catch (error) {
    console.error('Error rendering versions:', error)
  }
}

const renderBanners = async (versionId) => {
  try {
    const response = await axios.get(`/preview/renderBanners/${versionId}`)
    const banners = response.data.banners
    let bannersHtml = ''

    banners.forEach((banner, index) => {
      const bannerPath = '/' + banner.path + '/index.html'
      const loadPriority = index < 3 ? 'immediate' : 'lazy'

      bannersHtml += `<div class="banner-creatives banner-area-${banner.size.width}-${banner.size.height}" style="display: inline-block; width: ${banner.size.width}px; margin-right: 0.5rem; margin-left: 0.5rem; margin-bottom: 2rem;">`
      bannersHtml += `<div style="display: flex; justify-content: space-between; padding: 0; color: black; border-top-left-radius: 5px; border-top-right-radius: 5px;">`
      bannersHtml += `<small style="float: left; font-size: 0.85rem; font-weight: bold;">${banner.size.width}x${banner.size.height}</small>`
      bannersHtml += `<small style="float: right; font-size: 0.85rem; font-weight: bold;">${banner.file_size}</small>`
      bannersHtml += `</div>`

      if (loadPriority === 'immediate') {
        bannersHtml += `<iframe class="iframe-banners" src="${bannerPath}" width="${banner.size.width}" height="${banner.size.height}" frameBorder="0" scrolling="no" id="rel${banner.id}" loading="eager"></iframe>`
      } else {
        bannersHtml += `<div class="banner-placeholder" data-banner-path="${bannerPath}" data-banner-id="${banner.id}" data-width="${banner.size.width}" data-height="${banner.size.height}" style="width: ${banner.size.width}px; height: ${banner.size.height}px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 1px solid #dee2e6; cursor: pointer; position: relative;">`
        bannersHtml += `<div style="text-align: center; color: #6c757d;"><div style="font-size: 14px; margin-bottom: 5px;">Click to Load</div><div style="font-size: 12px;">Banner Preview</div></div>`
        bannersHtml += `<div class="loading-spinner" style="display: none; border: 2px solid #f3f4f6; border-top: 2px solid #3b82f6; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; position: absolute;"></div>`
        bannersHtml += `</div>`
      }

      bannersHtml += `<ul style="display: flex; flex-direction: row;" class="previewIcons">`
      bannersHtml += `<li><i id="relBt${banner.id}" onClick="reloadBanner(${banner.id})" class="fa-solid fa-repeat" style="display: flex; margin-top: 0.5rem; cursor: pointer; font-size:1rem;"></i></li>`
      if (props.authUserClientName === "Planet Nine") {
        bannersHtml += `<li class="banner-options"><a href="/previews/banner/download/${banner.id}"><i class="fa-solid fa-download" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:1rem;"></i></a></li>`
      }
      bannersHtml += `</ul>`
      bannersHtml += `</div>`
    })

    nextTick(() => {
      const bannersListContainer = document.getElementById(`bannersList${versionId}`)
      if (bannersListContainer) {
        bannersListContainer.innerHTML = bannersHtml
        initializeBannerLazyLoading()
      }
    })
  } catch (error) {
    console.error('Error rendering banners:', error)
  }
}

const renderVideo = async (versionId) => {
  try {
    const response = await axios.get(`/preview/renderVideos/${versionId}`)
    let videoHtml = ''

    response.data.videos.forEach(video => {
      const uniqueId = 'videoBlock_' + video.id
      videoHtml += `
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
            ></video>
          </div>
          <div class="bg-gray-50 text-gray-800 text-sm rounded-2xl p-3 mt-2 w-full video-media-info" style="margin:0 auto;">
            ${props.authUserClientName === "Planet Nine" ? `
              <div class="flex gap-4 mb-2 justify-center">
                <a href="/${video.path}" download title="Download"><i class="fa-solid fa-download" style="display: flex; margin-left: 0.5rem; font-size:20px;"></i></a>
              </div>
            ` : ''}
            <div class="font-semibold text-base mb-1 underline text-center">Media Info</div>
            <div><strong>Resolution:</strong> ${video.size.width} x ${video.size.height}</div>
            <div><strong>Aspect Ratio:</strong> ${video.aspect_ratio || '-'}</div>
            <div><strong>Codec:</strong> ${video.codec || '-'}</div>
            <div><strong>FPS:</strong> ${video.fps || '-'}</div>
            <div><strong>File Size:</strong> ${video.file_size || '-'}</div>
          </div>
        </div>
      `
    })

    nextTick(() => {
      const container = document.getElementById(`bannersList${versionId}`)
      if (container) {
        container.innerHTML = videoHtml
        container.style.flexDirection = 'column'
      }
    })
  } catch (error) {
    console.error('Error rendering videos:', error)
  }
}

const renderSocial = async (versionId) => {
  try {
    const response = await axios.get(`/preview/renderSocials/${versionId}`)
    let socialHtml = ''

    response.data.socials.forEach(social => {
      socialHtml += `
        <div style="display: inline-block; margin: 10px; max-width: 1000px;">
          <img src="/${social.path}" 
            alt="${social.name}"
            class="social-preview-img rounded-2xl"
            style="width: 100%; max-width: 1200px; height: auto; object-fit: contain; box-shadow: 0 2px 8px #0001; cursor: pointer; margin-top: 0;"
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
      `
    })

    nextTick(() => {
      const container = document.getElementById(`bannersList${versionId}`)
      if (container) {
        container.innerHTML = socialHtml
      }
    })
  } catch (error) {
    console.error('Error rendering social content:', error)
  }
}

const renderGif = async (versionId) => {
  try {
    const response = await axios.get(`/preview/renderGifs/${versionId}`)
    let gifsHtml = ''

    response.data.gifs.forEach(gif => {
      const gifPath = '/' + gif.path
      gifsHtml += `<div class="banner-creatives banner-area-${gif.size.width}-${gif.size.height}" style="display: inline-block; width: ${gif.size.width}px; margin-right: 0.5rem; margin-left: 0.5rem; margin-bottom: 1rem;">`
      gifsHtml += `<div style="display: flex; justify-content: space-between; padding: 0; color: black; border-top-left-radius: 5px; border-top-right-radius: 5px;">`
      gifsHtml += `<small style="float: left; font-size: 0.85rem; font-weight: bold;">${gif.size.width}x${gif.size.height}</small>`
      gifsHtml += `<small style="float: right; font-size: 0.85rem; font-weight: bold;">${gif.file_size}</small>`
      gifsHtml += `</div>`
      gifsHtml += `<iframe class="iframe-banners" style="margin-top: 2px;" src="${gifPath}" width="${gif.size.width}" height="${gif.size.height}" frameBorder="0" scrolling="no" id="rel${gif.id}"></iframe>`
      gifsHtml += `<ul style="display: flex; flex-direction: row;" class="previewIcons">`
      gifsHtml += `<li><i id="relBt${gif.id}" onClick="reloadBanner(${gif.id})" class="fa-solid fa-repeat" style="display: flex; margin-top: 0.5rem; cursor: pointer; font-size:20px;"></i></li>`
      if (props.authUserClientName === "Planet Nine") {
        gifsHtml += `<li class="banner-options"><a href="/${gif.path}" download="${gif.name}"><i class="fa-solid fa-download" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>`
      }
      gifsHtml += `</ul>`
      gifsHtml += `</div>`
    })

    nextTick(() => {
      const container = document.getElementById(`bannersList${versionId}`)
      if (container) {
        container.innerHTML = gifsHtml
      }
    })
  } catch (error) {
    console.error('Error rendering gifs:', error)
  }
}

const initializeBannerLazyLoading = () => {
  // Add click handlers for manual loading
  document.querySelectorAll('.banner-placeholder').forEach(placeholder => {
    placeholder.addEventListener('click', function () {
      const bannerId = this.dataset.bannerId
      loadBanner(bannerId)
    })
  })

  // Initialize Intersection Observer for auto-loading
  if ('IntersectionObserver' in window) {
    const bannerObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const placeholder = entry.target
          const bannerId = placeholder.dataset.bannerId

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

    document.querySelectorAll('.banner-placeholder').forEach(placeholder => {
      bannerObserver.observe(placeholder)
    })
  }
}

const loadBanner = (bannerId) => {
  const placeholder = document.querySelector(`.banner-placeholder[data-banner-id="${bannerId}"]`)
  if (!placeholder || placeholder.nextElementSibling?.tagName === 'IFRAME') return

  const bannerPath = placeholder.dataset.bannerPath
  const width = placeholder.dataset.width
  const height = placeholder.dataset.height

  // Show loading spinner
  const spinner = placeholder.querySelector('.loading-spinner')
  const content = placeholder.querySelector('div:first-child')
  if (spinner) spinner.style.display = 'block'
  if (content) content.style.display = 'none'

  // Create iframe
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
    if (spinner) spinner.style.display = 'none'
    if (content) {
      content.style.display = 'block'
      content.innerHTML = '<div style="color: #dc3545; font-size: 12px;">Failed to load</div>'
    }
  }

  placeholder.parentNode.insertBefore(iframe, placeholder.nextSibling)
}

// Social Modal Functions
const openSocialImageModal = (src, label) => {
  resetModalState()
  socialModalSrc.value = src
  socialModalAlt.value = label
  socialModalVisible.value = true
  document.body.style.overflow = 'hidden'
}

const closeSocialModal = () => {
  socialModalVisible.value = false
  document.body.style.overflow = ''
  resetModalState()
}

const resetModalState = () => {
  modalZoomScale.value = 1
  modalCurrentX.value = 0
  modalCurrentY.value = 0
  isDragging.value = false
}

const zoomIn = () => {
  const newScale = Math.min(modalZoomScale.value * 1.5, 5)
  modalZoomScale.value = newScale
}

const zoomOut = () => {
  const newScale = Math.max(modalZoomScale.value / 1.5, 0.5)
  modalZoomScale.value = newScale

  if (modalZoomScale.value <= 1) {
    modalCurrentX.value = 0
    modalCurrentY.value = 0
    modalZoomScale.value = 1
  }
}

const resetZoom = () => {
  modalZoomScale.value = 1
  modalCurrentX.value = 0
  modalCurrentY.value = 0
}

const startDrag = (e) => {
  if (modalZoomScale.value > 1) {
    isDragging.value = true
    const startX = e.clientX
    const startY = e.clientY
    const initialX = modalCurrentX.value
    const initialY = modalCurrentY.value

    const handleMouseMove = (moveEvent) => {
      if (isDragging.value) {
        const deltaX = moveEvent.clientX - startX
        const deltaY = moveEvent.clientY - startY
        modalCurrentX.value = initialX + deltaX
        modalCurrentY.value = initialY + deltaY
      }
    }

    const handleMouseUp = () => {
      isDragging.value = false
      document.removeEventListener('mousemove', handleMouseMove)
      document.removeEventListener('mouseup', handleMouseUp)
    }

    document.addEventListener('mousemove', handleMouseMove)
    document.addEventListener('mouseup', handleMouseUp)
  }
}

// Global functions for onclick handlers
window.updateActiveCategory = async (categoryId) => {
  try {
    await axios.get(`/preview/updateActiveCategory/${categoryId}`)
    await renderCategories()
  } catch (error) {
    console.error('Error updating category:', error)
  }
}

window.updateActiveFeedback = async (feedbackId) => {
  try {
    const response = await axios.get(`/preview/updateActiveFeedback/${feedbackId}`)
    await renderFeedbacks(response)
  } catch (error) {
    console.error('Error updating feedback:', error)
  }
}

window.reloadBanner = (bannerReloadID) => {
  const iframe = document.getElementById("rel" + bannerReloadID)
  if (iframe) {
    iframe.src = iframe.src
  }
}

window.openSocialImageModal = openSocialImageModal

window.changeFeedbackActiveBackground = (element) => {
  if (!element.classList.contains('feedbackTabActive')) {
    element.style.backgroundImage = `url('/${props.feedbackActiveImage}')`
  }
}

window.changeFeedbackInactiveBackground = (element) => {
  if (!element.classList.contains('feedbackTabActive')) {
    element.style.backgroundImage = `url('/${props.feedbackInactiveImage}')`
  }
}

// Lifecycle
onMounted(async () => {
  // Set CSS variables for colors
  document.documentElement.style.setProperty('--primary-color', props.primary || '#000')
  document.documentElement.style.setProperty('--secondary-color', props.secondary || '#fff')
  document.documentElement.style.setProperty('--tertiary-color', props.tertiary || '#ccc')
  document.documentElement.style.setProperty('--quaternary-color', props.quaternary || '#f5f5f5')
  document.documentElement.style.setProperty('--quinary-color', props.quinary || '#eeeeee')
  document.documentElement.style.setProperty('--senary-color', props.senary || '#dddddd')
  document.documentElement.style.setProperty('--septenary-color', props.septenary || '#cccccc')

  // Start tracking and fetching
  setInterval(trackViewer, 8000)
  setInterval(fetchViewers, 10000)

  fetchViewers()
  await renderCategories()
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap');

:root {
  --primary-color: v-bind(props.primary || '#000');
  --secondary-color: v-bind(props.secondary || '#fff');
  --tertiary-color: v-bind(props.tertiary || '#ccc');
  --quaternary-color: v-bind(props.quaternary || '#f5f5f5');
  --quinary-color: v-bind(props.quinary || '#eeeeee');
  --senary-color: v-bind(props.senary || '#dddddd');
  --septenary-color: v-bind(props.allColors[0]?.septenary || '#cccccc');
}

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
  overflow-x: hidden;
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
  /* border-radius: 10px */
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
  background-size: contain;
  width: 100%;
  min-height: 200px;
  position: relative;
  overflow: hidden;
  box-sizing: border-box;
}

#topDetails h1 {
  font-size: 1rem;
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
  border-bottom-color: #4c4f6e;
  border-right-color: #f15a29;
  border-top-color: #8cd1cf;
  border-left-color: #f5f5f5;
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
  gap: 5px;
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
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 110px;
  width: 130px;
  max-width: 140px;
  height: 35px;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  transition: background-image 0.1s ease;
}

.feedbackTabHover:hover {
  transition: background-image 0.1s ease-in-out;
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
  color: var(--septenary-color)
}

#feedbackCLick,
#colorPaletteClick {
  position: relative;
  right: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}

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

#feedbackCLick img {
  width: 30px;
  height: auto;
}

#colorPaletteClick {
  top: 5rem
}

#colorPaletteClick img {
  width: 30px;
  height: auto;
}

#bulk-customization {
  position: fixed;
  top: 9rem
}

#feedbackDescription {
  position: absolute;
  top: 3rem;
  right: -320px;
  /* Start hidden off-screen */
  width: 302px;
  background: white;
  border: 1px solid var(--tertiary-color);
  border-radius: 10px 0 0 10px;
  box-shadow: -5px 0 15px rgba(0, 0, 0, 0.2);
  transition: right 0.3s ease-in-out;
  z-index: 1000;
  display: block;
}

#feedbackDescription.show {
  right: 0;
}

/* Color Palette */
#colorPaletteSelection {
  position: absolute;
  top: 12rem;
  right: -320px;
  /* Start hidden off-screen */
  width: 8rem;
  background: white;
  border: 1px solid var(--tertiary-color);
  border-radius: 10px 0 0 10px;
  box-shadow: -5px 0 15px rgba(0, 0, 0, 0.2);
  transition: right 0.3s ease-in-out;
  z-index: 1000;
}

#colorPaletteSelection.visible {
  right: 0;
}

#mobilecolorPaletteSelection {
  position: absolute;
  top: 10rem;
  right: -340px;
  /* Start hidden */
  width: 100px;
  background: #f2f2f2;
  padding: 3px;
  border-radius: 10px 0 0 10px;
  box-shadow: -5px 0 15px rgba(0, 0, 0, 0.2);
  transition: right 0.3s ease-in-out;
  z-index: 1000;
  display: none;
}

#mobilecolorPaletteSelection.visible {
  display: grid;
  right: 0;
}

#colorPaletteSelection.visible,
#mobilecolorPaletteSelection.visible {
  right: 0;
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .5rem;
  padding: 5px;
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
  display: flex;
  justify-content: flex-end;
  align-items: center;
  border: 1px solid var(--tertiary-color);
  border-top-left-radius: 8px;
  padding: 0;
  background-color: var(--tertiary-color);
  color: white
}

#feedbackDescriptionLowerPart {
  background-color: #f2f2f2;
  color: black;
  padding: 5px;
  height: auto;
  width: 300px;
  white-space: nowrap;
  text-overflow: ellipsis;
  border-bottom-left-radius: 10px
}

/* Mobile Menu */
#mobileMenuToggle {
  position: fixed;
  top: 21rem;
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
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 5px;
  justify-content: center;
  align-items: center;
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
  position: absolute;
  right: -2px;
  top: 10rem;
  color: white;
  cursor: pointer;
  z-index: 99
}

#mobilecolorPaletteClick img {
  width: 30px;
  height: auto;
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
  box-shadow: rgba(0, 0, 0, .15) -5.05px 6.95px 14.6px;
  overflow: hidden;
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
  color: black;
  border-radius: 40px;
  border-bottom: 4px solid black;
  transition: all .25s;
  flex-direction: column;
  margin: 0 auto 10px auto;
  word-break: break-word;
  gap: 5px;
}

.category-row:hover,
.category-active {
  background-color: var(--senary-color);
  color: var(--quaternary-color);
  border-bottom: 4px solid var(--senary-color)
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
  background-color: var(--quinary-color);
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
    padding-top: 0;
    max-width: 170px;
  }

  #topDetails h1 {
    font-size: 0.85rem;
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

@media (max-width:767px) {
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
    border-radius: 40px
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
    transform: scale(calc(336 / 1080));
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
    transform: scale(calc(336 / 970));
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
    transform: scale(calc(336 / 728));
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
    transform: scale(calc(336 / 468));
    transform-origin: center left;
    width: 468px;
    height: 60px;
    border: none;
    display: block;
    margin-top: 0px !important;
    margin-bottom: 0px !important
  }

  .banner-area-500-500 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-500-500 iframe {
    transform: scale(calc(336 / 500));
    transform-origin: center left;
    width: 500px;
    height: 500px;
    border: none;
    display: block;
    margin-top: -75px !important;
    margin-bottom: -75px !important
  }

  .banner-area-1272-328 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1272-328 iframe {
    transform: scale(calc(336 / 1272));
    transform-origin: center left;
    width: 1272px;
    height: 328px;
    border: none;
    display: block;
    margin-top: -120px !important;
    margin-bottom: -120px !important
  }

  .banner-area-1115-300 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1115-300 iframe {
    transform: scale(calc(336 / 1115));
    transform-origin: center left;
    width: 1115px;
    height: 300px;
    border: none;
    display: block;
    margin-top: -100px !important;
    margin-bottom: -100px !important
  }

  .banner-area-1024-768 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1024-768 iframe {
    transform: scale(calc(336 / 1024));
    transform-origin: center left;
    width: 1024px;
    height: 768px;
    border: none;
    display: block;
    margin-top: -250px !important;
    margin-bottom: -250px !important
  }

  .banner-area-970-500 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-970-500 iframe {
    transform: scale(calc(336 / 970));
    transform-origin: center left;
    width: 970px;
    height: 500px;
    border: none;
    display: block;
    margin-top: -160px !important;
    margin-bottom: -160px !important
  }

  .banner-area-970-90 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-970-90 iframe {
    transform: scale(calc(336 / 970));
    transform-origin: center left;
    width: 970px;
    height: 90px;
    border: none;
    display: block;
    margin-top: -25px !important;
    margin-bottom: -25px !important
  }

  .banner-area-960-300 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-960-300 iframe {
    transform: scale(calc(336 / 960));
    transform-origin: center left;
    width: 960px;
    height: 300px;
    border: none;
    display: block;
    margin-top: -92px !important;
    margin-bottom: -92px !important;
  }

  .banner-area-768-1024 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-768-1024 iframe {
    transform: scale(calc(336 / 768));
    transform-origin: center left;
    width: 768px;
    height: 1024px;
    border: none;
    display: block;
    margin-top: -280px !important;
    margin-bottom: -280px !important;
  }

  .banner-area-600-700 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-600-700 iframe {
    transform: scale(calc(336 / 600));
    transform-origin: center left;
    width: 600px;
    height: 700px;
    border: none;
    display: block;
    margin-top: -150px !important;
    margin-bottom: -150px !important;
  }

  .banner-area-600-100 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-600-100 iframe {
    transform: scale(calc(336 / 600));
    transform-origin: center left;
    width: 600px;
    height: 100px;
    border: none;
    display: block;
    margin-top: -15px !important;
    margin-bottom: -15px !important;
  }

  .banner-area-580-400 {
    width: 336px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-580-400 iframe {
    transform: scale(calc(336 / 580));
    transform-origin: center left;
    width: 580px;
    height: 400px;
    border: none;
    display: block;
    margin-top: -80px !important;
    margin-bottom: -80px !important;
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
    right: -1px;
    width: auto;
    height: auto;
  }

  #feedbackCLick img {
    width: 20px;
    height: auto;
  }

  #mobilecolorPaletteClick img {
    width: 20px;
    height: auto;
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

@media (max-width:767px) {
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
    right: -320px;
    top: 19rem;
    transition: right 0.3s ease-in-out;
  }

  #feedbackDescription.show {
    right: 0;
  }

  .feedbacks {
    width: 100%
  }
}

@media (min-width:768px) and (max-width:1200px) {
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
    transform: scale(calc(728 / 1080));
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
    transform: scale(calc(728 / 970));
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

  .banner-area-1272-328 {
    width: 728px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1272-328 iframe {
    transform: scale(calc(728 / 1272));
    transform-origin: center left;
    width: 1272px;
    height: 328px;
    border: none;
    display: block;
    margin-top: -60px !important;
    margin-bottom: -60px !important;
  }

  .banner-area-1115-300 {
    width: 728px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1115-300 iframe {
    transform: scale(calc(728 / 1115));
    transform-origin: center left;
    width: 1115px;
    height: 300px;
    border: none;
    display: block;
    margin-top: -45px !important;
    margin-bottom: -45px !important;
  }

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
    transform: scale(calc(728 / 1080));
    transform-origin: center left;
    width: 1080px;
    height: 1080px;
    border: none;
    display: block;
    margin-top: -160px !important;
    margin-bottom: -160px !important;
  }

  .banner-area-1024-768 {
    width: 728px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1024-768 iframe {
    transform: scale(calc(728 / 1024));
    transform-origin: center left;
    width: 1024px;
    height: 768px;
    border: none;
    display: block;
    margin-top: -100px !important;
    margin-bottom: -100px !important;
  }

  .banner-area-970-90 {
    width: 728px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-970-90 iframe {
    transform: scale(calc(728 / 970));
    transform-origin: center left;
    width: 970px;
    height: 90px;
    border: none;
    display: block;
    margin-top: 0px !important;
    margin-bottom: 0px !important;
  }

  .banner-area-960-300 {
    width: 728px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-960-300 iframe {
    transform: scale(calc(728 / 960));
    transform-origin: center left;
    width: 960px;
    height: 300px;
    border: none;
    display: block;
    margin-top: -25px !important;
    margin-bottom: -25px !important;
  }

  .banner-area-768-1024 {
    width: 728px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-768-1024 iframe {
    transform: scale(calc(728 / 768));
    transform-origin: center left;
    width: 768px;
    height: 1024px;
    border: none;
    display: block;
    margin-top: -25px !important;
    margin-bottom: -25px !important;
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
    right: -1px;
    width: auto;
    height: auto;
  }

  #feedbackDescription {
    position: fixed;
    right: -320px;
    top: 20rem;
    transition: right 0.3s ease-in-out;
  }

  #feedbackDescription.show {
    right: 0;
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

@media (min-width:1200px) {

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
}

@media(min-width: 800px) and (max-width: 1199px) {
  .banner-area-1272-328 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1272-328 iframe {
    transform: scale(calc(800 / 1272));
    transform-origin: center left;
    width: 1272px;
    height: 328px;
    border: none;
    display: block;
    margin-top: -60px !important;
    margin-bottom: -60px !important;
  }

  .banner-area-1115-300 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1115-300 iframe {
    transform: scale(calc(800 / 1115));
    transform-origin: center left;
    width: 1115px;
    height: 300px;
    border: none;
    display: block;
    margin-top: -40px !important;
    margin-bottom: -40px !important;
  }

  .banner-area-1080-1080 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1080-1080 iframe {
    transform: scale(calc(800 / 1080));
    transform-origin: center left;
    width: 1080px;
    height: 1080px;
    border: none;
    display: block;
    margin-top: -140px !important;
    margin-bottom: -140px !important;
  }

  .banner-area-1024-768 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1024-768 iframe {
    transform: scale(calc(800 / 1024));
    transform-origin: center left;
    width: 1024px;
    height: 768px;
    border: none;
    display: block;
    margin-top: -85px !important;
    margin-bottom: -85px !important;
  }

  .banner-area-970-500 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-970-500 iframe {
    transform: scale(calc(800 / 970));
    transform-origin: center left;
    width: 970px;
    height: 500px;
    border: none;
    display: block;
    margin-top: -40px !important;
    margin-bottom: -40px !important;
  }

  .banner-area-970-250 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-970-250 iframe {
    transform: scale(calc(800 / 970));
    transform-origin: center left;
    width: 970px;
    height: 250px;
    border: none;
    display: block;
    margin-top: -20px !important;
    margin-bottom: -20px !important;
  }

  .banner-area-970-90 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-970-90 iframe {
    transform: scale(calc(800 / 970));
    transform-origin: center left;
    width: 970px;
    height: 90px;
    border: none;
    display: block;
    margin-top: -7px !important;
    margin-bottom: -7px !important;
  }

  .banner-area-960-300 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-960-300 iframe {
    transform: scale(calc(800 / 960));
    transform-origin: center left;
    width: 960px;
    height: 300px;
    border: none;
    display: block;
    margin-top: -25px !important;
    margin-bottom: -25px !important;
  }
}

@media(min-width: 1200px) and (max-width: 1600px) {
  .banner-area-1272-328 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1272-328 iframe {
    transform: scale(calc(800 / 1272));
    transform-origin: center left;
    width: 1272px;
    height: 328px;
    border: none;
    display: block;
    margin-top: -60px !important;
    margin-bottom: -60px !important;
  }

  .banner-area-1115-300 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1115-300 iframe {
    transform: scale(calc(800 / 1115));
    transform-origin: center left;
    width: 1115px;
    height: 300px;
    border: none;
    display: block;
    margin-top: -40px !important;
    margin-bottom: -40px !important;
  }

  .banner-area-1080-1080 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1080-1080 iframe {
    transform: scale(calc(800 / 1080));
    transform-origin: center left;
    width: 1080px;
    height: 1080px;
    border: none;
    display: block;
    margin-top: -140px !important;
    margin-bottom: -140px !important;
  }

  .banner-area-1024-768 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-1024-768 iframe {
    transform: scale(calc(800 / 1024));
    transform-origin: center left;
    width: 1024px;
    height: 768px;
    border: none;
    display: block;
    margin-top: -85px !important;
    margin-bottom: -85px !important;
  }

  .banner-area-970-500 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-970-500 iframe {
    transform: scale(calc(800 / 970));
    transform-origin: center left;
    width: 970px;
    height: 500px;
    border: none;
    display: block;
    margin-top: -40px !important;
    margin-bottom: -40px !important;
  }

  .banner-area-970-250 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-970-250 iframe {
    transform: scale(calc(800 / 970));
    transform-origin: center left;
    width: 970px;
    height: 250px;
    border: none;
    display: block;
    margin-top: -20px !important;
    margin-bottom: -20px !important;
  }

  .banner-area-970-90 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-970-90 iframe {
    transform: scale(calc(800 / 970));
    transform-origin: center left;
    width: 970px;
    height: 90px;
    border: none;
    display: block;
    margin-top: -7px !important;
    margin-bottom: -7px !important;
  }

  .banner-area-960-300 {
    width: 800px !important;
    height: auto;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    margin-right: 0 !important;
    overflow: hidden
  }

  .banner-area-960-300 iframe {
    transform: scale(calc(800 / 960));
    transform-origin: center left;
    width: 960px;
    height: 300px;
    border: none;
    display: block;
    margin-top: -25px !important;
    margin-bottom: -25px !important;
  }
}

.banners-list {
  display: flex;
  flex-direction: row;
  justify-content: center;
  flex-wrap: wrap;
  gap: 1rem;
}

@keyframes pulse-green {

  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }

  50% {
    opacity: 0.5;
    transform: scale(0.8);
  }
}

.animate-pulse-green {
  animation: pulse-green 1.5s ease-in-out infinite;
}
</style>
