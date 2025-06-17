<template>
    <div class="space-y-4">
      <h2 class="text-lg font-semibold">Step 3: Review Summary</h2>
  
      <div class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
        <p><strong>Name:</strong> {{ form.name }}</p>
        <p><strong>Client ID:</strong> {{ form.client_id }}</p>
        <p><strong>Team IDs:</strong> {{ form.team_ids.join(', ') }}</p>
        <p><strong>Theme ID:</strong> {{ form.color_palette_id }}</p>
        <p><strong>Type:</strong> {{ form.type }}</p>
      </div>
  
      <div class="text-right pt-4">
        <button class="bg-gray-600 text-white px-4 py-2 rounded-lg" @click="$emit('close')">Close</button>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  const props = defineProps<{
    form: {
      name: string;
      client_id: string | number;
      team_ids: number[];
      color_palette_id: string | number;
      type: string;
    };
  }>();
  </script>