<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import Swal from 'sweetalert2';
import axios from 'axios';

/* ========= Types ========= */
type Id = number;
type TempId = `tmp-${string}`;
type Key = Id | TempId;

type Base = { 
  id?: Id; 
  tempId?: TempId; 
  _state?: 'created' | 'updated' | 'deleted' | 'unchanged';
  is_active?: boolean;
};

type FileItem = Base & {
  version_id: Key;
  size_id?: number | null;
  name?: string;
  path?: string | null;
  position: number;
  _zipFile?: File;
};

type Version = Base & {
  feedback_set_id: Key;
  name: string;
  files?: FileItem[];
};

type FeedbackSet = Base & {
  feedback_id: Key;
  name: string;
  versions?: Version[];
};

type Feedback = Base & {
  category_id: Key;
  name: string;
  is_active: boolean;
  feedback_sets?: FeedbackSet[];
};

type Category = Base & {
  preview_id: Id;
  name: string;
  kind: 'banner' | 'video' | 'gif' | 'social';
  is_active: boolean;
  feedbacks?: Feedback[];
};

type Preview = {
  id: Id;
  name: string;
  categories?: Category[];
};

/* ========= Inertia Props ========= */
const page = usePage<{
  props: {
    preview: Preview;
    bannerSizes?: Array<{ id: number; width: number; height: number }>;
    videoSizes?: Array<{ id: number; name?: string; width?: number; height?: number }>;
    previewETag?: string;
  }
}>();

const preview = reactive<Preview>(JSON.parse(JSON.stringify(page.props.preview)));
const bannerSizes = computed(() => page.props.bannerSizes ?? []);
const videoSizes = computed(() => page.props.videoSizes ?? []);
const etag = computed(() => page.props.previewETag ?? null);

/* ========= Utils ========= */
function generateTempId(): TempId {
  return `tmp-${Math.random().toString(36).slice(2, 9)}` as TempId;
}

function markUpdated(item: Base) {
  if (item._state !== 'created') {
    item._state = 'updated';
  }
}

function ensureTempId(item: any) {
  if (!item.tempId) {
    item.tempId = item.id ? `tmp-${item.id}` : generateTempId();
  }
}

function getActiveItems<T extends Base & { is_active: boolean }>(items: T[]): T[] {
  return items.filter(item => item._state !== 'deleted');
}

function setActiveStatus<T extends Base & { is_active: boolean }>(items: T[], activeItem: T) {
  items.forEach(item => {
    if (item._state !== 'deleted') {
      item.is_active = (item.id ?? item.tempId) === (activeItem.id ?? activeItem.tempId);
      if (item.is_active && item._state !== 'created') {
        markUpdated(item);
      }
    }
  });
}

/* ========= Initialize data structure ========= */
function initializeData() {
  if (preview.categories) {
    preview.categories.forEach(category => {
      ensureTempId(category);
      if (category.feedbacks) {
        category.feedbacks.forEach(feedback => {
          ensureTempId(feedback);
          if (feedback.feedback_sets) {
            feedback.feedback_sets.forEach(feedbackSet => {
              ensureTempId(feedbackSet);
              if (feedbackSet.versions) {
                feedbackSet.versions.forEach(version => {
                  ensureTempId(version);
                  if (version.files) {
                    version.files.forEach(file => {
                      ensureTempId(file);
                    });
                  }
                });
              }
            });
          }
        });
      }
    });
  }
}

// Initialize data on component mount
initializeData();

/* ========= State Management ========= */
const selectedCategory = ref<Category | null>(null);
const selectedFeedback = ref<Feedback | null>(null);
const selectedVersion = ref<Version | null>(null);

// Auto-select first active category and feedback
watch(() => preview.categories, () => {
  if (preview.categories && preview.categories.length > 0) {
    const activeCategory = preview.categories.find(c => c.is_active && c._state !== 'deleted');
    if (activeCategory && !selectedCategory.value) {
      selectedCategory.value = activeCategory;
      
      const activeFeedback = activeCategory.feedbacks?.find(f => f.is_active && f._state !== 'deleted');
      if (activeFeedback) {
        selectedFeedback.value = activeFeedback;
      }
    }
  }
}, { immediate: true });

/* ========= Category Management ========= */
function addCategory(kind: 'banner' | 'video' | 'gif' | 'social') {
  if (!preview.categories) preview.categories = [];
  
  // Set all existing categories to inactive
  preview.categories.forEach(cat => {
    if (cat._state !== 'deleted') {
      cat.is_active = false;
      if (cat._state !== 'created') markUpdated(cat);
    }
  });
  
  const newCategory: Category = {
    tempId: generateTempId(),
    preview_id: preview.id,
    name: kind,
    kind: kind,
    is_active: true,
    feedbacks: [],
    _state: 'created'
  };
  
  preview.categories.push(newCategory);
  selectedCategory.value = newCategory;
  selectedFeedback.value = null;
  selectedVersion.value = null;
}

function deleteCategory(category: Category) {
  category._state = 'deleted';
  
  if (category.is_active) {
    // Find last inserted category to make active
    const activeCategories = getActiveItems(preview.categories || []);
    const lastCategory = activeCategories[activeCategories.length - 2]; // -2 because current is being deleted
    if (lastCategory) {
      setActiveStatus(preview.categories || [], lastCategory);
      selectedCategory.value = lastCategory;
    } else {
      selectedCategory.value = null;
    }
  }
  
  if (selectedCategory.value?.tempId === category.tempId) {
    selectedCategory.value = null;
    selectedFeedback.value = null;
    selectedVersion.value = null;
  }
}

function selectCategory(category: Category) {
  selectedCategory.value = category;
  
  // Auto-select active feedback in this category
  const activeFeedback = category.feedbacks?.find(f => f.is_active && f._state !== 'deleted');
  selectedFeedback.value = activeFeedback || null;
  selectedVersion.value = null;
}

/* ========= Feedback Management ========= */
function addFeedback(category: Category) {
  if (!category.feedbacks) category.feedbacks = [];
  
  // Set all existing feedbacks to inactive
  category.feedbacks.forEach(feedback => {
    if (feedback._state !== 'deleted') {
      feedback.is_active = false;
      if (feedback._state !== 'created') markUpdated(feedback);
    }
  });
  
  const newFeedback: Feedback = {
    tempId: generateTempId(),
    category_id: category.id ?? category.tempId!,
    name: `Feedback ${category.feedbacks.length + 1}`,
    is_active: true,
    feedback_sets: [],
    _state: 'created'
  };
  
  category.feedbacks.push(newFeedback);
  selectedFeedback.value = newFeedback;
  selectedVersion.value = null;
}

function deleteFeedback(category: Category, feedback: Feedback) {
  feedback._state = 'deleted';
  
  if (feedback.is_active && category.feedbacks) {
    // Find last inserted feedback to make active
    const activeFeedbacks = getActiveItems(category.feedbacks);
    const lastFeedback = activeFeedbacks[activeFeedbacks.length - 2]; // -2 because current is being deleted
    if (lastFeedback) {
      setActiveStatus(category.feedbacks, lastFeedback);
      selectedFeedback.value = lastFeedback;
    } else {
      selectedFeedback.value = null;
    }
  }
  
  if (selectedFeedback.value?.tempId === feedback.tempId) {
    selectedFeedback.value = null;
    selectedVersion.value = null;
  }
}

function selectFeedback(feedback: Feedback) {
  selectedFeedback.value = feedback;
  selectedVersion.value = null;
}

/* ========= FeedbackSet Management ========= */
function addFeedbackSet(feedback: Feedback) {
  if (!feedback.feedback_sets) feedback.feedback_sets = [];
  
  const newFeedbackSet: FeedbackSet = {
    tempId: generateTempId(),
    feedback_id: feedback.id ?? feedback.tempId!,
    name: `Set ${feedback.feedback_sets.length + 1}`,
    versions: [],
    _state: 'created'
  };
  
  feedback.feedback_sets.push(newFeedbackSet);
}

function deleteFeedbackSet(feedback: Feedback, feedbackSet: FeedbackSet) {
  feedbackSet._state = 'deleted';
  
  if (selectedVersion.value) {
    const belongsToSet = feedbackSet.versions?.some(v => 
      (v.id ?? v.tempId) === (selectedVersion.value!.id ?? selectedVersion.value!.tempId)
    );
    if (belongsToSet) {
      selectedVersion.value = null;
    }
  }
}

/* ========= Version Management ========= */
function addVersion(feedbackSet: FeedbackSet) {
  if (!feedbackSet.versions) feedbackSet.versions = [];
  
  const newVersion: Version = {
    tempId: generateTempId(),
    feedback_set_id: feedbackSet.id ?? feedbackSet.tempId!,
    name: `Version ${feedbackSet.versions.length + 1}`,
    files: [],
    _state: 'created'
  };
  
  feedbackSet.versions.push(newVersion);
  selectedVersion.value = newVersion;
}

function deleteVersion(feedbackSet: FeedbackSet, version: Version) {
  version._state = 'deleted';
  
  if (selectedVersion.value?.tempId === version.tempId) {
    selectedVersion.value = null;
  }
}

function selectVersion(version: Version) {
  selectedVersion.value = version;
}

/* ========= File Management ========= */
function onUploadFiles(event: Event) {
  const input = event.target as HTMLInputElement;
  const files = input.files;
  
  if (!files?.length || !selectedVersion.value) {
    input.value = '';
    return;
  }

  if (!selectedVersion.value.files) {
    selectedVersion.value.files = [];
  }

  let position = selectedVersion.value.files.length;
  
  Array.from(files).forEach(file => {
    const newFile: FileItem = {
      tempId: generateTempId(),
      version_id: selectedVersion.value!.id ?? selectedVersion.value!.tempId!,
      name: file.name,
      path: null,
      size_id: null,
      position: position++,
      _zipFile: file,
      _state: 'created'
    };
    
    selectedVersion.value!.files!.push(newFile);
  });
  
  input.value = '';
}

function deleteFile(file: FileItem) {
  if (!selectedVersion.value?.files) return;
  
  if (file.id) {
    file._state = 'deleted';
  } else {
    const index = selectedVersion.value.files.findIndex(f => f.tempId === file.tempId);
    if (index !== -1) {
      selectedVersion.value.files.splice(index, 1);
    }
  }
  
  // Reorder remaining files
  selectedVersion.value.files
    .filter(f => f._state !== 'deleted')
    .forEach((f, index) => {
      f.position = index;
      if (f._state !== 'created') markUpdated(f);
    });
}

function onFileReorder() {
  if (!selectedVersion.value?.files) return;
  
  selectedVersion.value.files
    .filter(f => f._state !== 'deleted')
    .forEach((f, index) => {
      f.position = index;
      if (f._state !== 'created') markUpdated(f);
    });
}

/* ========= Size Management ========= */
const sizePickerOpen = ref<string | null>(null);

function toggleSizePicker(fileId: string) {
  sizePickerOpen.value = sizePickerOpen.value === fileId ? null : fileId;
}

function selectSize(file: FileItem, sizeId: number) {
  file.size_id = sizeId;
  markUpdated(file);
  sizePickerOpen.value = null;
}

function getSizeLabel(file: FileItem, categoryKind: string): string {
  if (!file.size_id) return 'Pick a size...';
  
  if (categoryKind === 'video') {
    const size = videoSizes.value.find(s => s.id === file.size_id);
    return size?.name || `${size?.width}x${size?.height}` || 'Unknown size';
  } else {
    const size = bannerSizes.value.find(s => s.id === file.size_id);
    return size ? `${size.width}x${size.height}` : 'Unknown size';
  }
}

/* ========= Save Functionality ========= */
const saving = ref(false);

function buildChangeset() {
  const changeset = {
    etag: etag.value,
    categories: { created: [], updated: [], deleted: [] },
    feedbacks: { created: [], updated: [], deleted: [] },
    feedbackSets: { created: [], updated: [], deleted: [] },
    versions: { created: [], updated: [], deleted: [] },
    banners: { created: [], updated: [], deleted: [] },
    videos: { created: [], updated: [], deleted: [] },
    gifs: { created: [], updated: [], deleted: [] },
    socials: { created: [], updated: [], deleted: [] },
    fileReorders: []
  };

  if (!preview.categories) return changeset;

  for (const category of preview.categories) {
    // Process categories
    if (category._state === 'created') {
      changeset.categories.created.push({
        tempId: category.tempId,
        preview_id: category.preview_id,
        name: category.name,
        kind: category.kind,
        is_active: category.is_active
      });
    } else if (category._state === 'updated') {
      changeset.categories.updated.push({
        id: category.id,
        name: category.name,
        kind: category.kind,
        is_active: category.is_active
      });
    } else if (category._state === 'deleted' && category.id) {
      changeset.categories.deleted.push(category.id);
    }

    if (!category.feedbacks) continue;

    for (const feedback of category.feedbacks) {
      // Process feedbacks
      if (feedback._state === 'created') {
        changeset.feedbacks.created.push({
          tempId: feedback.tempId,
          category_id: category.id ?? category.tempId,
          name: feedback.name,
          is_active: feedback.is_active
        });
      } else if (feedback._state === 'updated') {
        changeset.feedbacks.updated.push({
          id: feedback.id,
          name: feedback.name,
          is_active: feedback.is_active
        });
      } else if (feedback._state === 'deleted' && feedback.id) {
        changeset.feedbacks.deleted.push(feedback.id);
      }

      if (!feedback.feedback_sets) continue;

      for (const feedbackSet of feedback.feedback_sets) {
        // Process feedback sets
        if (feedbackSet._state === 'created') {
          changeset.feedbackSets.created.push({
            tempId: feedbackSet.tempId,
            feedback_id: feedback.id ?? feedback.tempId,
            name: feedbackSet.name
          });
        } else if (feedbackSet._state === 'updated') {
          changeset.feedbackSets.updated.push({
            id: feedbackSet.id,
            name: feedbackSet.name
          });
        } else if (feedbackSet._state === 'deleted' && feedbackSet.id) {
          changeset.feedbackSets.deleted.push(feedbackSet.id);
        }

        if (!feedbackSet.versions) continue;

        for (const version of feedbackSet.versions) {
          // Process versions
          if (version._state === 'created') {
            changeset.versions.created.push({
              tempId: version.tempId,
              feedback_set_id: feedbackSet.id ?? feedbackSet.tempId,
              name: version.name
            });
          } else if (version._state === 'updated') {
            changeset.versions.updated.push({
              id: version.id,
              name: version.name
            });
          } else if (version._state === 'deleted' && version.id) {
            changeset.versions.deleted.push(version.id);
          }

          if (!version.files) continue;

          // Process files based on category kind
          const fileType = `${category.kind}s` as keyof typeof changeset;
          
          for (const file of version.files) {
            if (file._state === 'created') {
              changeset[fileType].created.push({
                tempId: file.tempId,
                version_id: version.id ?? version.tempId,
                size_id: file.size_id,
                path: file.path || '',
                position: file.position
              });
            } else if (file._state === 'updated') {
              changeset[fileType].updated.push({
                id: file.id,
                size_id: file.size_id,
                path: file.path || '',
                position: file.position
              });
            } else if (file._state === 'deleted' && file.id) {
              changeset[fileType].deleted.push(file.id);
            }
          }
        }
      }
    }
  }

  return changeset;
}

async function saveAll() {
  if (saving.value) return;
  
  saving.value = true;
  
  try {
    console.log('Starting save process...');
    
    // Build initial changeset
    const changeset = buildChangeset();
    console.log('Initial changeset:', changeset);
    
    // Collect ZIP files and update file paths
    const zipFiles: { [key: string]: File } = {};
    let zipCounter = 0;
    
    if (preview.categories) {
      for (const category of preview.categories) {
        if (category.feedbacks) {
          for (const feedback of category.feedbacks) {
            if (feedback.feedback_sets) {
              for (const feedbackSet of feedback.feedback_sets) {
                if (feedbackSet.versions) {
                  for (const version of feedbackSet.versions) {
                    if (version.files) {
                      for (const file of version.files) {
                        if (file._state === 'created' && file._zipFile) {
                          const zipKey = `zip_${zipCounter++}`;
                          zipFiles[zipKey] = file._zipFile;
                          file.path = zipKey;
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    
    // Rebuild changeset with updated file paths
    const finalChangeset = buildChangeset();
    console.log('Final changeset with file paths:', finalChangeset);
    console.log('ZIP files to upload:', Object.keys(zipFiles));
    
    // Create FormData
    const formData = new FormData();
    formData.append('changeset', JSON.stringify(finalChangeset));
    
    // Add ZIP files
    Object.entries(zipFiles).forEach(([key, file]) => {
      console.log(`Adding file ${key}:`, file.name, `(${file.size} bytes)`);
      formData.append(key, file);
    });
    
    // Debug FormData contents
    console.log('FormData entries:');
    for (const [key, value] of formData.entries()) {
      if (value instanceof File) {
        console.log(`  ${key}: File(${value.name}, ${value.size} bytes)`);
      } else {
        console.log(`  ${key}:`, value);
      }
    }
    
    // Send request
    const response = await axios.put(route('previews.bulkEdit', { preview: preview.id }), formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    
    console.log('Save response:', response.data);
    
    if (response.data.success) {
      await Swal.fire({
        title: 'Success!',
        text: response.data.success,
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      });
      
      // Refresh page data
      router.reload({ only: ['preview', 'previewETag'] });
    } else {
      throw new Error('Unexpected response format');
    }
    
  } catch (error: any) {
    console.error('Save error:', error);
    
    const message = error.response?.data?.error || error.message || 'Failed to save changes';
    
    await Swal.fire({
      title: 'Error',
      text: message,
      icon: 'error',
      confirmButtonText: 'OK'
    });
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <Head :title="`Update: ${preview.name}`" />
  
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ preview.name }}</h1>
            <p class="text-sm text-gray-600">Manage preview content</p>
          </div>
          <button
            @click="saveAll"
            :disabled="saving"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Categories Panel -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-lg font-medium text-gray-900">Categories</h2>
            </div>
            
            <!-- Category Type Buttons -->
            <div class="grid grid-cols-2 gap-2 mb-6">
              <button
                @click="addCategory('banner')"
                class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100"
              >
                + Banner
              </button>
              <button
                @click="addCategory('video')"
                class="px-3 py-2 text-sm font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-md hover:bg-purple-100"
              >
                + Video
              </button>
              <button
                @click="addCategory('gif')"
                class="px-3 py-2 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-md hover:bg-green-100"
              >
                + GIF
              </button>
              <button
                @click="addCategory('social')"
                class="px-3 py-2 text-sm font-medium text-orange-700 bg-orange-50 border border-orange-200 rounded-md hover:bg-orange-100"
              >
                + Social
              </button>
            </div>
            
            <!-- Categories List -->
            <div class="space-y-3">
              <div
                v-for="category in getActiveItems(preview.categories || [])"
                :key="category.tempId"
                @click="selectCategory(category)"
                :class="[
                  'p-3 rounded-lg border cursor-pointer transition-colors',
                  selectedCategory?.tempId === category.tempId
                    ? 'border-indigo-500 bg-indigo-50'
                    : 'border-gray-200 hover:border-gray-300',
                  category.is_active ? 'ring-2 ring-green-500' : ''
                ]"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <span :class="[
                      'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                      category.kind === 'banner' ? 'bg-blue-100 text-blue-800' :
                      category.kind === 'video' ? 'bg-purple-100 text-purple-800' :
                      category.kind === 'gif' ? 'bg-green-100 text-green-800' :
                      'bg-orange-100 text-orange-800'
                    ]">
                      {{ category.kind }}
                    </span>
                    <span v-if="category.is_active" class="text-green-600 text-xs font-medium">ACTIVE</span>
                  </div>
                  <button
                    @click.stop="deleteCategory(category)"
                    class="text-red-600 hover:text-red-700 text-sm"
                  >
                    Delete
                  </button>
                </div>
                <input
                  v-model="category.name"
                  @input="markUpdated(category)"
                  @click.stop
                  class="mt-2 block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Category name"
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Feedbacks Panel -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-lg font-medium text-gray-900">
                Feedbacks
                <span v-if="selectedCategory" class="text-sm text-gray-500">
                  ({{ selectedCategory.name }})
                </span>
              </h2>
              <button
                v-if="selectedCategory"
                @click="addFeedback(selectedCategory)"
                class="px-3 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-md hover:bg-indigo-100"
              >
                + Add Feedback
              </button>
            </div>

            <div v-if="!selectedCategory" class="text-center text-gray-500 py-8">
              Select a category to manage feedbacks
            </div>

            <div v-else-if="!selectedCategory.feedbacks?.length" class="text-center text-gray-500 py-8">
              No feedbacks yet. Click "Add Feedback" to create one.
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="feedback in getActiveItems(selectedCategory.feedbacks)"
                :key="feedback.tempId"
                @click="selectFeedback(feedback)"
                :class="[
                  'p-3 rounded-lg border cursor-pointer transition-colors',
                  selectedFeedback?.tempId === feedback.tempId
                    ? 'border-indigo-500 bg-indigo-50'
                    : 'border-gray-200 hover:border-gray-300',
                  feedback.is_active ? 'ring-2 ring-green-500' : ''
                ]"
              >
                <div class="flex items-center justify-between mb-2">
                  <span v-if="feedback.is_active" class="text-green-600 text-xs font-medium">ACTIVE</span>
                  <button
                    @click.stop="deleteFeedback(selectedCategory, feedback)"
                    class="text-red-600 hover:text-red-700 text-sm"
                  >
                    Delete
                  </button>
                </div>
                <input
                  v-model="feedback.name"
                  @input="markUpdated(feedback)"
                  @click.stop
                  class="block w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Feedback name"
                >
                
                <!-- Feedback Sets -->
                <div class="mt-4 space-y-2">
                  <div class="flex justify-between items-center">
                    <span class="text-xs font-medium text-gray-700">Sets</span>
                    <button
                      @click.stop="addFeedbackSet(feedback)"
                      class="text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded"
                    >
                      + Add Set
                    </button>
                  </div>
                  
                  <div v-for="feedbackSet in getActiveItems(feedback.feedback_sets || [])" 
                       :key="feedbackSet.tempId"
                       class="p-2 bg-gray-50 rounded border"
                  >
                    <div class="flex items-center justify-between mb-2">
                      <input
                        v-model="feedbackSet.name"
                        @input="markUpdated(feedbackSet)"
                        @click.stop
                        class="text-xs w-full border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Set name"
                      >
                      <button
                        @click.stop="deleteFeedbackSet(feedback, feedbackSet)"
                        class="ml-2 text-red-600 hover:text-red-700 text-xs"
                      >
                        Delete
                      </button>
                    </div>
                    
                    <!-- Versions -->
                    <div class="space-y-1">
                      <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-600">Versions</span>
                        <button
                          @click.stop="addVersion(feedbackSet)"
                          class="text-xs px-2 py-1 bg-white hover:bg-gray-100 rounded border"
                        >
                          + Add
                        </button>
                      </div>
                      
                      <div v-for="version in getActiveItems(feedbackSet.versions || [])"
                           :key="version.tempId"
                           class="flex items-center gap-2"
                      >
                        <input
                          v-model="version.name"
                          @input="markUpdated(version)"
                          @click.stop
                          class="text-xs flex-1 border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="Version name"
                        >
                        <button
                          @click.stop="selectVersion(version)"
                          :class="[
                            'text-xs px-2 py-1 rounded',
                            selectedVersion?.tempId === version.tempId
                              ? 'bg-indigo-100 text-indigo-700'
                              : 'bg-blue-50 text-blue-600 hover:bg-blue-100'
                          ]"
                        >
                          {{ selectedVersion?.tempId === version.tempId ? 'Selected' : 'Select' }}
                        </button>
                        <button
                          @click.stop="deleteVersion(feedbackSet, version)"
                          class="text-red-600 hover:text-red-700 text-xs"
                        >
                          Del
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Files Panel -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-lg font-medium text-gray-900">
                Files
                <span v-if="selectedVersion" class="text-sm text-gray-500">
                  ({{ selectedVersion.name }})
                </span>
              </h2>
              <label v-if="selectedVersion" class="cursor-pointer px-3 py-2 text-sm font-medium text-white bg-gray-800 border border-transparent rounded-md hover:bg-gray-700">
                Upload Files
                <input
                  type="file"
                  multiple
                  accept=".zip,.jpg,.jpeg,.png,.gif,.mp4,.mov"
                  @change="onUploadFiles"
                  class="hidden"
                >
              </label>
            </div>

            <div v-if="!selectedVersion" class="text-center text-gray-500 py-8">
              Select a version to manage files
            </div>

            <div v-else-if="!selectedVersion.files?.length" class="text-center text-gray-500 py-8">
              No files uploaded yet. Click "Upload Files" to add some.
            </div>

            <div v-else class="space-y-3">
              <draggable
                v-model="selectedVersion.files"
                item-key="tempId"
                @end="onFileReorder"
                class="space-y-2"
              >
                <template #item="{ element: file }">
                  <div v-if="file._state !== 'deleted'" class="p-3 border rounded-lg bg-gray-50">
                    <div class="flex items-center justify-between mb-2">
                      <div class="flex-1">
                        <div class="text-sm font-medium text-gray-900 truncate">
                          {{ file.name || `File ${file.position + 1}` }}
                        </div>
                        <div class="text-xs text-gray-500">
                          Position: {{ file.position + 1 }}
                        </div>
                      </div>
                      <button
                        @click="deleteFile(file)"
                        class="text-red-600 hover:text-red-700 text-sm ml-2"
                      >
                        Delete
                      </button>
                    </div>
                    
                    <!-- Size Picker -->
                    <div class="relative">
                      <button
                        @click="toggleSizePicker(file.tempId || '')"
                        class="w-full text-left px-3 py-2 border border-gray-300 rounded-md bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                      >
                        {{ getSizeLabel(file, selectedCategory?.kind || 'banner') }}
                      </button>
                      
                      <div 
                        v-if="sizePickerOpen === (file.tempId || '')"
                        class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-40 overflow-auto"
                      >
                        <template v-if="selectedCategory?.kind === 'video'">
                          <button
                            v-for="size in videoSizes"
                            :key="size.id"
                            @click="selectSize(file, size.id)"
                            class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-100"
                          >
                            {{ size.name || `${size.width}x${size.height}` }}
                          </button>
                        </template>
                        <template v-else>
                          <button
                            v-for="size in bannerSizes"
                            :key="size.id"
                            @click="selectSize(file, size.id)"
                            class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-100"
                          >
                            {{ size.width }}x{{ size.height }}
                          </button>
                        </template>
                      </div>
                    </div>
                  </div>
                </template>
              </draggable>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>
