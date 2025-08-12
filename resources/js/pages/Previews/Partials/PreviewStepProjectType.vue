<template>
  <div class="space-y-6">
    <h2 class="text-lg font-semibold">Step 2: Select Type</h2>

    <!-- Advanced Fields with Transition -->
    <div class="space-y-4">
      <!-- Version Name -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Enter Category Name</label>
        <input v-model="form.category_name" type="text" required
          class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:text-white"
          placeholder="Enter type name e.g: Storyboard/Banner/Video/Gif/Social" />
      </div>

      <!-- SubVersion Name -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Enter Feedback Name</label>
        <input v-model="form.feedback_name" type="text" required
          class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:text-white"
          placeholder="Enter feedback name e.g: F1" />
      </div>

      <!-- Version Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Enter Feedback Description</label>
        <textarea v-model="form.feedback_description" rows="2" required
          class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:text-white"
          placeholder="Enter feedback description"></textarea>
      </div>
    </div>

    <!-- Type Selection -->
    <div class="grid grid-cols-2 gap-4 pt-2">
      <button v-for="type in types" :key="type" @click="form.type = type" :class="[
        'px-4 py-3 rounded text-sm font-medium border',
        form.type === type
          ? 'bg-indigo-600 text-white border-indigo-600'
          : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-white border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'
      ]">
        {{ type }}
      </button>
    </div>

    <!-- Navigation -->
    <div class="flex justify-between pt-4">
      <button class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg" @click="$emit('previous')">
        ← Previous
      </button>

      <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg" :disabled="!form.type"
        @click="$emit('next')">
        Next →
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{
  form: any;
}>();

const form = props.form;

const types = ['Banner', 'Video', 'Social', 'Gif'];
</script>