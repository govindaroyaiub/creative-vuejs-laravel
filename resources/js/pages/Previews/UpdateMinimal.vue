<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

// Simple types
type Preview = {
  id: number;
  name: string;
  categories?: Category[];
};

type Category = {
  id?: number;
  tempId?: string;
  preview_id: number;
  name: string;
  type: string;
  is_active: boolean;
  feedbacks?: Feedback[];
  _state?: string;
};

type Feedback = {
  id?: number;
  tempId?: string;
  category_id: number | string;
  name: string;
  is_active: boolean;
  feedback_sets?: FeedbackSet[];
  _state?: string;
};

type FeedbackSet = {
  id?: number;
  tempId?: string;
  feedback_id: number | string;
  name: string;
  versions?: Version[];
  _state?: string;
};

type Version = {
  id?: number;
  tempId?: string;
  feedback_set_id: number | string;
  name: string;
  files?: FileItem[];
  _state?: string;
};

type FileItem = {
  id?: number;
  tempId?: string;
  version_id: number | string;
  name: string;
  position: number;
  _state?: string;
  _zipFile?: File;
};

// Props
const page = usePage<{
  props: {
    preview: Preview;
  }
}>();

const preview = reactive<Preview>(JSON.parse(JSON.stringify(page.props.preview)));

// State
const selectedCategory = ref<Category | null>(null);
const selectedFeedback = ref<Feedback | null>(null);
const selectedVersion = ref<Version | null>(null);

// Debug display
const debugInfo = ref('');

function updateDebug(message: string) {
  debugInfo.value = new Date().toLocaleTimeString() + ': ' + message;
}

// Utils
function generateTempId(): string {
  return `tmp-${Math.random().toString(36).slice(2, 9)}`;
}

// Category functions
function addCategory(kind: string) {
  updateDebug(`Adding category: ${kind}`);
  
  if (!preview.categories) preview.categories = [];
  
  const newCategory: Category = {
    tempId: generateTempId(),
    preview_id: preview.id,
    name: kind,
    type: kind,
    is_active: true,
    feedbacks: [],
    _state: 'created'
  };
  
  preview.categories.push(newCategory);
  selectedCategory.value = newCategory;
  updateDebug(`Category added. Total categories: ${preview.categories.length}`);
}

function selectCategory(category: Category) {
  selectedCategory.value = category;
  selectedFeedback.value = null;
  selectedVersion.value = null;
  updateDebug(`Selected category: ${category.name}`);
}

// Feedback functions
function addFeedback() {
  if (!selectedCategory.value) {
    updateDebug('No category selected');
    return;
  }
  
  updateDebug(`Adding feedback to category: ${selectedCategory.value.name}`);
  
  if (!selectedCategory.value.feedbacks) {
    selectedCategory.value.feedbacks = [];
  }
  
  const newFeedback: Feedback = {
    tempId: generateTempId(),
    category_id: selectedCategory.value.id || selectedCategory.value.tempId!,
    name: `Feedback ${selectedCategory.value.feedbacks.length + 1}`,
    is_active: true,
    feedback_sets: [],
    _state: 'created'
  };
  
  selectedCategory.value.feedbacks.push(newFeedback);
  selectedFeedback.value = newFeedback;
  updateDebug(`Feedback added. Total feedbacks: ${selectedCategory.value.feedbacks.length}`);
}

function selectFeedback(feedback: Feedback) {
  selectedFeedback.value = feedback;
  selectedVersion.value = null;
  updateDebug(`Selected feedback: ${feedback.name}`);
}

// FeedbackSet functions
function addFeedbackSet() {
  if (!selectedFeedback.value) {
    updateDebug('No feedback selected');
    return;
  }
  
  updateDebug(`Adding feedback set to: ${selectedFeedback.value.name}`);
  
  if (!selectedFeedback.value.feedback_sets) {
    selectedFeedback.value.feedback_sets = [];
  }
  
  const newSet: FeedbackSet = {
    tempId: generateTempId(),
    feedback_id: selectedFeedback.value.id || selectedFeedback.value.tempId!,
    name: `Set ${selectedFeedback.value.feedback_sets.length + 1}`,
    versions: [],
    _state: 'created'
  };
  
  selectedFeedback.value.feedback_sets.push(newSet);
  updateDebug(`Feedback set added. Total sets: ${selectedFeedback.value.feedback_sets.length}`);
}

// Version functions
function addVersion(feedbackSet: FeedbackSet) {
  updateDebug(`Adding version to set: ${feedbackSet.name}`);
  
  if (!feedbackSet.versions) {
    feedbackSet.versions = [];
  }
  
  const newVersion: Version = {
    tempId: generateTempId(),
    feedback_set_id: feedbackSet.id || feedbackSet.tempId!,
    name: `Version ${feedbackSet.versions.length + 1}`,
    files: [],
    _state: 'created'
  };
  
  feedbackSet.versions.push(newVersion);
  selectedVersion.value = newVersion;
  updateDebug(`Version added. Total versions: ${feedbackSet.versions.length}. Selected: ${newVersion.name}`);
}

function selectVersion(version: Version) {
  selectedVersion.value = version;
  updateDebug(`Selected version: ${version.name}`);
}

// File functions
function onUploadFiles(event: Event) {
  if (!selectedVersion.value) {
    updateDebug('No version selected for file upload');
    return;
  }
  
  const input = event.target as HTMLInputElement;
  const files = input.files;
  
  if (!files?.length) {
    updateDebug('No files selected');
    return;
  }
  
  updateDebug(`Uploading ${files.length} files to version: ${selectedVersion.value.name}`);
  
  if (!selectedVersion.value.files) {
    selectedVersion.value.files = [];
  }
  
  let position = selectedVersion.value.files.length;
  
  Array.from(files).forEach(file => {
    const newFile: FileItem = {
      tempId: generateTempId(),
      version_id: selectedVersion.value!.id || selectedVersion.value!.tempId!,
      name: file.name,
      position: position++,
      _zipFile: file,
      _state: 'created'
    };
    
    selectedVersion.value!.files!.push(newFile);
  });
  
  input.value = '';
  updateDebug(`Files added. Total files in version: ${selectedVersion.value.files.length}`);
}
</script>

<template>
  <Head :title="`Test: ${preview.name}`" />
  
  <div class="min-h-screen bg-gray-100 p-8">
    <h1 class="text-2xl font-bold mb-4">{{ preview.name }} - Debug Version</h1>
    
    <!-- Debug Info -->
    <div class="bg-yellow-100 border border-yellow-300 p-4 mb-8 rounded">
      <h3 class="font-bold">Debug Info:</h3>
      <p>{{ debugInfo || 'No debug info yet' }}</p>
      <div class="mt-2 text-sm">
        <p>Selected Category: {{ selectedCategory?.name || 'None' }}</p>
        <p>Selected Feedback: {{ selectedFeedback?.name || 'None' }}</p>
        <p>Selected Version: {{ selectedVersion?.name || 'None' }}</p>
      </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      
      <!-- Categories -->
      <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Categories</h2>
        
        <button 
          @click="addCategory('banner')"
          class="w-full mb-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
        >
          Add Banner Category
        </button>
        
        <div class="space-y-2">
          <div 
            v-for="category in preview.categories"
            :key="category.tempId || category.id"
            @click="selectCategory(category)"
            :class="[
              'p-3 border rounded cursor-pointer',
              selectedCategory?.tempId === category.tempId ? 'bg-blue-100 border-blue-500' : 'border-gray-300 hover:border-gray-400'
            ]"
          >
            <div class="font-medium">{{ category.name }}</div>
            <div class="text-sm text-gray-600">{{ category.type }}</div>
            <div class="text-xs text-gray-500">
              Feedbacks: {{ category.feedbacks?.length || 0 }}
            </div>
          </div>
        </div>
      </div>
      
      <!-- Feedbacks -->
      <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Feedbacks</h2>
        
        <button 
          @click="addFeedback"
          :disabled="!selectedCategory"
          class="w-full mb-4 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 disabled:bg-gray-300"
        >
          Add Feedback
        </button>
        
        <div v-if="selectedCategory?.feedbacks" class="space-y-2">
          <div 
            v-for="feedback in selectedCategory.feedbacks"
            :key="feedback.tempId || feedback.id"
            @click="selectFeedback(feedback)"
            :class="[
              'p-3 border rounded cursor-pointer',
              selectedFeedback?.tempId === feedback.tempId ? 'bg-green-100 border-green-500' : 'border-gray-300 hover:border-gray-400'
            ]"
          >
            <div class="font-medium">{{ feedback.name }}</div>
            <div class="text-xs text-gray-500">
              Sets: {{ feedback.feedback_sets?.length || 0 }}
            </div>
            
            <!-- Feedback Sets -->
            <div class="mt-2 space-y-1">
              <button 
                @click.stop="addFeedbackSet"
                class="text-xs px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded"
              >
                Add Set
              </button>
              
              <div v-if="feedback.feedback_sets" class="ml-2">
                <div 
                  v-for="feedbackSet in feedback.feedback_sets"
                  :key="feedbackSet.tempId || feedbackSet.id"
                  class="p-2 bg-gray-50 rounded border mb-1"
                >
                  <div class="text-sm font-medium">{{ feedbackSet.name }}</div>
                  <div class="text-xs text-gray-600">
                    Versions: {{ feedbackSet.versions?.length || 0 }}
                  </div>
                  
                  <!-- Versions -->
                  <div class="mt-1">
                    <button 
                      @click.stop="addVersion(feedbackSet)"
                      class="text-xs px-2 py-1 bg-blue-200 hover:bg-blue-300 rounded"
                    >
                      Add Version
                    </button>
                    
                    <div v-if="feedbackSet.versions" class="mt-1 space-y-1">
                      <div 
                        v-for="version in feedbackSet.versions"
                        :key="version.tempId || version.id"
                        @click.stop="selectVersion(version)"
                        :class="[
                          'p-1 text-xs rounded cursor-pointer',
                          selectedVersion?.tempId === version.tempId ? 'bg-blue-200' : 'bg-white hover:bg-gray-100'
                        ]"
                      >
                        {{ version.name }} ({{ version.files?.length || 0 }} files)
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Files -->
      <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Files</h2>
        
        <div v-if="selectedVersion">
          <h3 class="font-medium mb-2">{{ selectedVersion.name }}</h3>
          
          <label class="block w-full mb-4 px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 cursor-pointer text-center">
            Upload Files
            <input 
              type="file" 
              multiple 
              @change="onUploadFiles"
              class="hidden"
            >
          </label>
          
          <div v-if="selectedVersion.files" class="space-y-2">
            <div 
              v-for="file in selectedVersion.files"
              :key="file.tempId || file.id"
              class="p-2 border rounded bg-gray-50"
            >
              <div class="text-sm font-medium">{{ file.name }}</div>
              <div class="text-xs text-gray-600">Position: {{ file.position }}</div>
            </div>
          </div>
          
          <div v-else class="text-gray-500 text-sm">
            No files uploaded yet
          </div>
        </div>
        
        <div v-else class="text-gray-500">
          Select a version to manage files
        </div>
      </div>
      
    </div>
    
    <!-- Data Dump -->
    <div class="mt-8 bg-gray-800 text-green-400 p-4 rounded font-mono text-xs overflow-x-auto">
      <h3 class="text-white font-bold mb-2">Data Structure:</h3>
      <pre>{{ JSON.stringify(preview, null, 2) }}</pre>
    </div>
  </div>
</template>
