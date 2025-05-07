<template>
  <div class="space-y-6">
    <h2 class="text-lg font-semibold">Step 1: Basic Information</h2>

    <!-- Preview Name -->
    <div>
      <label class="block mb-1 text-sm font-medium">Preview Name</label>
      <input v-model="form.name" type="text" placeholder="e.g. Facebook Ad - June"
        class="w-full rounded border px-3 py-2 dark:bg-gray-800 dark:text-white" />
    </div>

    <!-- Client Dropdown -->
    <div>
      <label class="block mb-1 text-sm font-medium">Client</label>
      <select v-model="form.client_id" class="w-full rounded border px-3 py-2 dark:bg-gray-800 dark:text-white">
        <option disabled value="">Select Client</option>
        <option v-for="client in clients" :key="client.id" :value="client.id">
          {{ client.name }}
        </option>
      </select>
    </div>

    <!-- Team Members -->
    <div>
      <label class="block mb-1 text-sm font-medium">Team Members</label>

      <!-- Selected users display -->
      <div class="flex flex-wrap gap-2 mb-2">
        <span v-for="user in selectedUsers" :key="user.id"
          class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full dark:bg-indigo-800 dark:text-white">
          {{ user.name }}
          <span v-if="user.id !== authUser.id" @click="removeUser(user.id)" class="ml-1 cursor-pointer">×</span>
        </span>
      </div>

      <!-- User search input -->
      <input v-model="userSearch" type="text" placeholder="Search users..."
        class="w-full rounded border px-3 py-2 dark:bg-gray-800 dark:text-white" />

      <!-- Search result list -->
      <ul v-if="userSearch.trim().length > 0 && filteredUsers.length > 0"
        class="mt-1 max-h-32 overflow-y-auto rounded border dark:border-gray-700">
        <li v-for="user in filteredUsers" :key="user.id"
          class="cursor-pointer px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700" @click="addUser(user)">
          {{ user.name }}
        </li>
      </ul>
    </div>

    <!-- Requires Login Toggle -->
    <div class="flex items-center justify-between">
      <label class="text-sm font-medium">Requires Login?</label>
      <label class="inline-flex items-center cursor-pointer">
        <input type="checkbox" v-model="form.requires_login" class="sr-only peer" />
        <div
          class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer dark:bg-gray-700 peer-checked:bg-green-600 transition-colors">
        </div>
        <div
          class="absolute w-5 h-5 bg-white border rounded-full left-0.5 top-0.5 peer-checked:translate-x-full transition-transform">
        </div>
      </label>
    </div>

    <!-- Show Planet Nine Logo -->
    <div class="flex items-center justify-between">
      <label class="text-sm font-medium">Show Planet Nine Logo?</label>
      <label class="inline-flex items-center cursor-pointer">
        <input type="checkbox" v-model="form.show_planetnine_logo" class="sr-only peer" />
        <div
          class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer dark:bg-gray-700 peer-checked:bg-green-600 transition-colors">
        </div>
        <div
          class="absolute w-5 h-5 bg-white border rounded-full left-0.5 top-0.5 peer-checked:translate-x-full transition-transform">
        </div>
      </label>
    </div>

    <!-- Show Sidebar Logo -->
    <div class="flex items-center justify-between">
      <label class="text-sm font-medium">Show Sidebar Logo?</label>
      <label class="inline-flex items-center cursor-pointer">
        <input type="checkbox" v-model="form.show_sidebar_logo" class="sr-only peer" />
        <div
          class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer dark:bg-gray-700 peer-checked:bg-green-600 transition-colors">
        </div>
        <div
          class="absolute w-5 h-5 bg-white border rounded-full left-0.5 top-0.5 peer-checked:translate-x-full transition-transform">
        </div>
      </label>
    </div>

    <!-- Show Footer -->
    <div class="flex items-center justify-between">
      <label class="text-sm font-medium">Show Footer?</label>
      <label class="inline-flex items-center cursor-pointer">
        <input type="checkbox" v-model="form.show_footer" class="sr-only peer" />
        <div
          class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer dark:bg-gray-700 peer-checked:bg-green-600 transition-colors">
        </div>
        <div
          class="absolute w-5 h-5 bg-white border rounded-full left-0.5 top-0.5 peer-checked:translate-x-full transition-transform">
        </div>
      </label>
    </div>

    <!-- Navigation -->
    <div class="text-right pt-2">
      <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded disabled:opacity-50"
        :disabled="!isFormValid" @click="$emit('next')">
        Next →
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{
  form: any;
  users: { id: number; name: string }[];
  clients: { id: number; name: string }[];
  colorPalettes: { id: number; name: string }[];
  authUser: { id: number; name: string };
}>();

const userSearch = ref('');

// Selected users display
const selectedUsers = computed(() =>
  props.users.filter((u) => props.form.team_ids.includes(u.id))
);

// Search results
const filteredUsers = computed(() => {
  const query = userSearch.value.toLowerCase();
  return props.users
    .filter((u) => !props.form.team_ids.includes(u.id))
    .filter((u) => u.name.toLowerCase().includes(query));
});

const addUser = (user: { id: number; name: string }) => {
  if (!props.form.team_ids.includes(user.id)) {
    props.form.team_ids.push(user.id);
  }
  userSearch.value = '';
};

const removeUser = (id: number) => {
  props.form.team_ids = props.form.team_ids.filter((uid: number) => uid !== id);
};

// Form validation
const isFormValid = computed(() => {
  return (
    props.form.name.trim() !== '' &&
    props.form.client_id !== '' &&
    props.form.color_palette_id !== '' &&
    props.form.team_ids.length > 0
  );
});
</script>