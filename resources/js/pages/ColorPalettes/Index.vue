<template>

  <Head title="Color Palettes" />
  <AppLayout :breadcrumbs="[{ title: 'Color Palettes' }]">
    <div
      class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-black dark:via-gray-950 dark:to-black">
      <div class="p-6 space-y-6">
        <!-- Stats Card -->
        <div
          class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="flex items-center space-x-4">
              <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 lucide lucide-paintbrush-icon lucide-paintbrush"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                  stroke-linejoin="round">
                  <path d="m14.622 17.897-10.68-2.913" />
                  <path
                    d="M18.376 2.622a1 1 0 1 1 3.002 3.002L17.36 9.643a.5.5 0 0 0 0 .707l.944.944a2.41 2.41 0 0 1 0 3.408l-.944.944a.5.5 0 0 1-.707 0L8.354 7.348a.5.5 0 0 1 0-.707l.944-.944a2.41 2.41 0 0 1 3.408 0l.944.944a.5.5 0 0 0 .707 0z" />
                  <path
                    d="M9 8c-1.804 2.71-3.97 3.46-6.583 3.948a.507.507 0 0 0-.302.819l7.32 8.883a1 1 0 0 0 1.185.204C12.735 20.405 16 16.792 16 15" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Palettes</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-white">{{ colorPalettes?.length || 0 }}</p>
              </div>
            </div>

            <div class="flex items-center space-x-4">
              <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                  viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Palettes</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                  {{colorPalettes?.filter(p => p.status).length || 0}}
                </p>
              </div>
            </div>

            <div class="flex items-center space-x-4">
              <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-xl">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                  viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z">
                  </path>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Color Variations</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ colorKeys.length }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Search Section -->
        <div class="rounded-2xl dark:border-neutral-700 flex items-center space-x-4">
          <div class="relative w-full">
            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" fill="none"
              stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input v-model="search" type="text" placeholder="Search palettes by name..."
              class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" />
          </div>
          <button @click="openAddModal"
            class="w-1/5 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-sm hover:shadow-md group">
            <CirclePlus class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-200" />
            Add Palette
          </button>
        </div>

        <!-- Palettes Grid -->
        <div v-if="colorPalettes?.length > 0" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
          <div v-for="(palette, index) in colorPalettes" :key="palette.id"
            class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg hover:border-blue-200 dark:hover:border-neutral-600 transition-all duration-200 overflow-hidden group">
            <!-- Card Header -->
            <div class="p-4 pb-4">
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-3">
                  <div
                    class="w-10 h-10 bg-gradient-to-r from-purple-100 to-pink-100 dark:from-white dark:to-white rounded-xl flex items-center justify-center">
                    <span class="font-bold text-purple-600 dark:text-black">#{{ index + 1 }}</span>
                  </div>
                  <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg">{{ palette.name }}</h3>
                    <div class="flex items-center mt-1">
                      <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" :checked="palette.status" @change="toggleStatus(palette)"
                          class="sr-only peer" />
                        <div
                          class="w-9 h-5 bg-gray-200 dark:bg-neutral-900 rounded-full peer peer-checked:bg-green-600 transition-colors">
                        </div>
                        <div
                          class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4">
                        </div>
                      </label>
                      <span class="ml-2 text-xs font-medium"
                        :class="palette.status ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                        {{ palette.status ? 'Active' : 'Inactive' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Color Swatches -->
              <div class="mb-4 pt-2">
                <div class="grid grid-cols-7 gap-2 mb-2">
                  <div v-for="key in colorKeys" :key="key" class="group/color cursor-pointer flex flex-col items-start"
                    @click="copyColor(palette[key], key)"
                    :title="`${key.charAt(0).toUpperCase() + key.slice(1)}: ${palette[key]}`">
                    <div :style="{ backgroundColor: palette[key] }"
                      class="w-10 h-8 rounded-lg border-2 border-gray-200 dark:border-neutral-700 shadow-sm group-hover/color:scale-110 transition-transform duration-200">
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center truncate">
                      {{ key.charAt(0).toUpperCase() + key.slice(1) }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Header Image -->
              <div v-if="palette.header_image" class="mb-2">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Header Image</p>
                <div class="bg-gray-50 dark:bg-neutral-500 rounded-xl p-3">
                  <img :src="`/${palette.header_image}`" alt="Header"
                    class="h-12 mx-auto rounded dark:border-neutral-700" />
                </div>
              </div>

              <!-- Tab Images -->
              <div class="space-y-4">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Feedback Tabs</p>
                  <div
                    class="flex items-center justify-between space-x-4 bg-gray-50 dark:bg-neutral-500 rounded-xl p-3">
                    <div class="text-center">
                      <img :src="`/${palette.feedbackTab_inactive_image}`" alt="Inactive" class="h-8 mx-auto mb-1" />
                      <span class="text-xs text-gray-500 dark:text-gray-400">Inactive</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <div class="text-center">
                      <img :src="`/${palette.feedbackTab_active_image}`" alt="Active" class="h-8 mx-auto mb-1" />
                      <span class="text-xs text-gray-500 dark:text-gray-400">Active</span>
                    </div>
                  </div>
                </div>

                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Right Side Tabs</p>
                  <div
                    class="flex items-center justify-between space-x-4 bg-gray-50 dark:bg-neutral-500 rounded-xl p-3">
                    <div class="text-center">
                      <img :src="`/${palette.rightSideTab_feedback_description_image}`" alt="Feedback Description"
                        class="h-8 mx-auto mb-1" />
                      <span class="text-xs text-gray-500 dark:text-gray-400">Feedback</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <div class="text-center">
                      <img :src="`/${palette.rightSideTab_color_palette_image}`" alt="Color Palette"
                        class="h-8 mx-auto mb-1" />
                      <span class="text-xs text-gray-500 dark:text-gray-400">Palette</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card Actions -->
            <div class="px-6 py-2 bg-gray-50 dark:bg-neutral-900 border-t border-gray-200 dark:border-neutral-700">
              <div class="flex justify-end space-x-2">
                <button @click="openEditModal(palette)"
                  class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-lg transition-all duration-200 group/edit"
                  title="Edit Palette">
                  <svg class="w-4 h-4 group-hover/edit:scale-110 transition-transform duration-200" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                    </path>
                  </svg>
                </button>
                <button @click="deletePalette(palette.id)"
                  class="p-2 text-red-600 hover:text-red-800 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-lg transition-all duration-200 group/delete"
                  title="Delete Palette">
                  <svg class="w-4 h-4 group-hover/delete:scale-110 transition-transform duration-200" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                    </path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else
          class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-12 text-center">
          <div
            class="w-20 h-20 mx-auto bg-gray-100 dark:bg-neutral-500 rounded-full flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a4 4 0 004-4V5z">
              </path>
            </svg>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No color palettes found</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
            {{ search ? 'Try adjusting your search terms' : 'Get started by creating your first color palette' }}
          </p>
          <button v-if="!search" @click="openAddModal"
            class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors duration-200">
            <CirclePlus class="w-5 h-5 mr-2" />
            Create Your First Palette
          </button>
        </div>
      </div>
    </div>

    <!-- Enhanced Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 ckdrop-blur-sm p-4"
      @click.self="closeModal">
      <div
        class="bg-white dark:bg-neutral-800 rounded-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden shadow-2xl border border-gray-200 dark:border-neutral-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-neutral-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ modalMode === 'edit' ? 'Edit Color Palette' : 'Create New Color Palette' }}
          </h2>
          <button @click="closeModal"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg transition-all duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <form @submit.prevent="submit" class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column: Basic Info & Colors -->
            <div class="space-y-6">
              <!-- Color Inputs -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Color
                  Variations</label>
                <div class="grid grid-cols-1 gap-4">
                  <div v-for="key in colorKeys" :key="key"
                    class="flex items-center space-x-4 p-4 bg-gray-50 dark:bg-neutral-900 rounded-xl border border-gray-200 dark:border-neutral-700">
                    <div class="flex items-center space-x-3 flex-1">
                      <input type="color" v-model="form[key]"
                        class="w-12 h-12 border-2 border-gray-200 dark:border-neutral-700 rounded-lg cursor-pointer" />
                      <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ key.charAt(0).toUpperCase() +
                          key.slice(1) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ form[key] }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Images -->
            <div class="space-y-6">
              <!-- Header Image -->
              <!-- Name Input -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Palette Name</label>
                <input v-model="form.name" type="text" placeholder="Enter palette name..."
                  class="w-full px-4 py-3 border border-gray-300 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  required />
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Header Image
                  (738x294)</label>
                <div
                  class="p-4 bg-gray-50 dark:bg-neutral-900 rounded-xl border border-gray-200 dark:border-neutral-700">
                  <img v-if="form.header_image_preview" :src="form.header_image_preview"
                    class="h-16 mb-3 mx-auto rounded border border-gray-200 dark:border-neutral-700" />
                  <input type="file" @change="onFileChange('header_image', $event)" accept="image/*"
                    class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-300" />
                </div>
              </div>

              <!-- Feedback Tab Images -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Feedback Tab
                  Images (110x33)</label>
                <div class="space-y-4">
                  <div
                    class="p-4 bg-gray-50 dark:bg-neutral-900 rounded-xl border border-gray-200 dark:border-neutral-700">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Inactive
                      State</label>
                    <img v-if="form.feedbackTab_inactive_image_preview" :src="form.feedbackTab_inactive_image_preview"
                      class="h-12 mb-3 rounded border border-gray-200 dark:border-neutral-700" />
                    <input type="file" @change="onFileChange('feedbackTab_inactive_image', $event)" accept="image/*"
                      class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-300" />
                  </div>

                  <div
                    class="p-4 bg-gray-50 dark:bg-neutral-900 rounded-xl border border-gray-200 dark:border-neutral-700">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Active State</label>
                    <img v-if="form.feedbackTab_active_image_preview" :src="form.feedbackTab_active_image_preview"
                      class="h-12 mb-3 rounded border border-gray-200 dark:border-neutral-700" />
                    <input type="file" @change="onFileChange('feedbackTab_active_image', $event)" accept="image/*"
                      class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-300" />
                  </div>
                </div>
              </div>

              <!-- Right Side Tab Images -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Right Side Tab
                  Images (41x137)</label>
                <div class="space-y-4">
                  <div
                    class="p-4 bg-gray-50 dark:bg-neutral-900 rounded-xl border border-gray-200 dark:border-neutral-700">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Feedback
                      Description</label>
                    <img v-if="form.rightSideTab_feedback_description_image_preview"
                      :src="form.rightSideTab_feedback_description_image_preview"
                      class="h-12 mb-3 rounded border border-gray-200 dark:border-neutral-700" />
                    <input type="file" @change="onFileChange('rightSideTab_feedback_description_image', $event)"
                      accept="image/*"
                      class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-300" />
                  </div>

                  <div
                    class="p-4 bg-gray-50 dark:bg-neutral-900 rounded-xl border border-gray-200 dark:border-neutral-700">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Color Palette
                      Tab</label>
                    <img v-if="form.rightSideTab_color_palette_image_preview"
                      :src="form.rightSideTab_color_palette_image_preview"
                      class="h-12 mb-3 rounded border border-gray-200 dark:border-neutral-700" />
                    <input type="file" @change="onFileChange('rightSideTab_color_palette_image', $event)"
                      accept="image/*"
                      class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-300" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Actions -->
          <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200 dark:border-neutral-700">
            <button type="button" @click="closeModal"
              class="px-6 py-3 border border-gray-300 dark:border-neutral-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors duration-200">
              Cancel
            </button>
            <button type="submit"
              class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-sm hover:shadow-md">
              {{ modalMode === 'edit' ? 'Update Palette' : 'Create Palette' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { Head } from '@inertiajs/vue3'
import { CirclePlus } from 'lucide-vue-next'

const props = defineProps({
  colorPalettes: Array
})

const search = ref('')

const colorKeys = [
  'primary', 'secondary', 'tertiary', 'quaternary', 'quinary', 'senary', 'septenary'
]

const showModal = ref(false)
const modalMode = ref('add') // 'add' or 'edit'
const selectedPalette = ref(null)

watch(search, (val) => {
  router.get(route('color-palettes'), { search: val }, {
    preserveScroll: true,
    preserveState: true,
    replace: true,
  })
})

const form = ref({
  name: '',
  primary: '#000000',
  secondary: '#000000',
  tertiary: '#000000',
  quaternary: '#000000',
  quinary: '#000000',
  senary: '#000000',
  septenary: '#000000',
  header_image: null,
  feedbackTab_inactive_image: null,
  feedbackTab_active_image: null,
  rightSideTab_feedback_description_image: null,
  rightSideTab_color_palette_image: null,
  header_image_preview: null,
  feedbackTab_inactive_image_preview: null,
  feedbackTab_active_image_preview: null,
  rightSideTab_feedback_description_image_preview: null,
  rightSideTab_color_palette_image_preview: null,
})

function openEditModal(palette) {
  modalMode.value = 'edit'
  selectedPalette.value = palette
  form.value.name = palette.name ?? ''
  form.value.primary = palette.primary ?? '#000000'
  form.value.secondary = palette.secondary ?? '#000000'
  form.value.tertiary = palette.tertiary ?? '#000000'
  form.value.quaternary = palette.quaternary ?? '#000000'
  form.value.quinary = palette.quinary ?? '#000000'
  form.value.senary = palette.senary ?? '#000000'
  form.value.septenary = palette.septenary ?? '#000000'

  // Updated image field mappings
  form.value.header_image_preview = palette.header_image
    ? `/${palette.header_image}` : null
  form.value.feedbackTab_inactive_image_preview = palette.feedbackTab_inactive_image
    ? `/${palette.feedbackTab_inactive_image}` : null
  form.value.feedbackTab_active_image_preview = palette.feedbackTab_active_image
    ? `/${palette.feedbackTab_active_image}` : null
  form.value.rightSideTab_feedback_description_image_preview = palette.rightSideTab_feedback_description_image
    ? `/${palette.rightSideTab_feedback_description_image}` : null
  form.value.rightSideTab_color_palette_image_preview = palette.rightSideTab_color_palette_image
    ? `/${palette.rightSideTab_color_palette_image}` : null

  // Reset file inputs
  form.value.header_image = null
  form.value.feedbackTab_inactive_image = null
  form.value.feedbackTab_active_image = null
  form.value.rightSideTab_feedback_description_image = null
  form.value.rightSideTab_color_palette_image = null

  showModal.value = true
}

function openAddModal() {
  modalMode.value = 'add'
  selectedPalette.value = null
  Object.assign(form.value, {
    name: '',
    primary: '#000000',
    secondary: '#000000',
    tertiary: '#000000',
    quaternary: '#000000',
    quinary: '#000000',
    senary: '#000000',
    septenary: '#000000',
    header_image: null,
    feedbackTab_inactive_image: null,
    feedbackTab_active_image: null,
    rightSideTab_feedback_description_image: null,
    rightSideTab_color_palette_image: null,
    header_image_preview: null,
    feedbackTab_inactive_image_preview: null,
    feedbackTab_active_image_preview: null,
    rightSideTab_feedback_description_image_preview: null,
    rightSideTab_color_palette_image_preview: null,
  })
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

function copyColor(color, key) {
  navigator.clipboard.writeText(color).then(() => {
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: `Copied: ${color}`,
      text: `${key.charAt(0).toUpperCase() + key.slice(1)} color copied to clipboard`,
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
      customClass: {
        popup: 'rounded-xl text-sm'
      }
    })
  })
}

function submit() {
  const data = new FormData()
  Object.keys(form.value).forEach(k => {
    if (form.value[k] !== null && form.value[k] !== undefined && !k.endsWith('_preview')) {
      data.append(k, form.value[k])
    }
  })
  if (modalMode.value === 'edit' && selectedPalette.value) {
    // Use POST with _method=PUT for file uploads
    data.append('_method', 'PUT')
    router.post(`/color-palettes-update/${selectedPalette.value.id}`, data, {
      onSuccess: () => {
        showModal.value = false
        Swal.fire({
          icon: 'info',
          title: 'Success!',
          text: 'Color Palette Updated successfully!',
          timer: 1000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end',
          timerProgressBar: true,
        });
      }
    })
  } else {
    router.post('/color-palettes/store', data, {
      onSuccess: () => {
        showModal.value = false
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: 'Color Palette created successfully!',
          timer: 1000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end',
          timerProgressBar: true,
        });
      }
    })
  }
}

function deletePalette(id) {
  Swal.fire({
    title: 'Are you sure?',
    text: 'This will permanently delete the color palette.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel',
    reverseButtons: true,
    customClass: {
      popup: 'rounded-2xl',
      confirmButton: 'rounded-lg px-6 py-2',
      cancelButton: 'rounded-lg px-6 py-2'
    }
  }).then(result => {
    if (result.isConfirmed) {
      router.delete(`/color-palettes-delete/${id}`, {
        onSuccess: () => Swal.fire({
          title: 'Deleted!',
          text: 'Color palette deleted successfully.',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false,
          customClass: { popup: 'rounded-2xl' }
        })
      })
    }
  })
}

function toggleStatus(palette) {
  const newStatus = palette.status ? 0 : 1;
  router.put(
    `/color-palettes-toggle-status/${palette.id}`,
    { status: newStatus },
    {
      onSuccess: () => {
        palette.status = newStatus;
      }
    }
  );
}

function onFileChange(field, e) {
  const file = e.target.files[0]
  if (file) {
    form.value[field] = file
    form.value[field + '_preview'] = URL.createObjectURL(file)
  }
}
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.group:hover .group-hover\:scale-110 {
  transform: scale(1.1);
}
</style>