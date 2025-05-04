<!-- resources/js/Pages/Previews/Partials/PreviewCreateModal.vue -->
<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-white dark:bg-gray-900 p-6 rounded-lg w-full max-w-lg relative">
        <button class="absolute top-2 right-2 text-gray-500" @click="$emit('close')">✖</button>
  
        <div v-if="!selectedType" class="space-y-4">
          <h2 class="text-lg font-semibold">What type of project?</h2>
          <div class="grid grid-cols-2 gap-4">
            <button v-for="t in types" :key="t" @click="selectedType = t"
              class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded">
              {{ t }}
            </button>
          </div>
        </div>
  
        <component
          v-else
          :is="formComponent"
          @close="$emit('close')"
          @created="handleCreated"
        />
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, computed } from 'vue';
  
  const emit = defineEmits(['close']);
  const selectedType = ref('');
  
  const types = ['Banner', 'Video', 'Social', 'GIF'];
  
  const formComponent = computed(() => {
    switch (selectedType.value) {
    //   case 'Banner': return () => import('./CreateBannerForm.vue');
    //   case 'Video': return () => import('./CreateVideoForm.vue');
    //   case 'Social': return () => import('./CreateSocialForm.vue');
    //   case 'GIF': return () => import('./CreateGifForm.vue');
      default: return null;
    }
  });
  
  const handleCreated = () => {
    emit('close');
  };
  </script>