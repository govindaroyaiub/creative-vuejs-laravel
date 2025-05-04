<template>
    <div class="space-y-6">
      <h2 class="text-lg font-semibold">Step 2: Select Project Type</h2>
  
      <div class="grid grid-cols-2 gap-4">
        <button
          v-for="type in types"
          :key="type"
          @click="form.type = type"
          :class="[
            'px-4 py-3 rounded text-sm font-medium border',
            form.type === type
              ? 'bg-indigo-600 text-white border-indigo-600'
              : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-white border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'
          ]"
        >
          {{ type }}
        </button>
      </div>
  
      <!-- Navigation -->
      <div class="flex justify-between pt-4">
        <button
          class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded"
          @click="$emit('previous')"
        >
          ← Previous
        </button>
  
        <button
          class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded"
          :disabled="!form.type"
          @click="$emit('next')"
        >
          Next →
        </button>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  const props = defineProps<{
    form: any;
  }>();
  
  const types = ['Banner', 'Video', 'Social', 'Gif'];
  </script>