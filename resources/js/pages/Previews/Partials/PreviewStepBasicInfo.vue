<template>
  <div class="space-y-2">
    <h2 class="text-lg font-semibold">Step 1: Basic Information</h2>

    <!-- Preview Name -->
    <div>
      <label class="block mb-1 text-sm font-medium">Preview Name</label>
      <input v-model="form.name" type="text" placeholder="e.g. Facebook Ad - June"
        class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white" />
    </div>

    <!-- Client and Color Palette on same row -->
    <div class="flex flex-col md:flex-row gap-4">
      <!-- Client Dropdown -->
      <div class="flex-1">
        <label class="block mb-1 text-sm font-medium">Client</label>
        <select v-model="form.client_id" class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white">
          <option disabled value="">Select Client</option>
          <option v-for="client in clients" :key="client.id" :value="client.id">
            {{ client.name }}
          </option>
        </select>
      </div>

      <!-- Color Palette Dropdown -->
      <div class="flex-1">
        <label class="block mb-1 text-sm font-medium">Theme</label>
        <select v-model="form.color_palette_id"
          class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white">
          <option disabled value="">Select Theme</option>
          <option v-for="palette in colorPalettes" :key="palette.id" :value="palette.id">
            {{ palette.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Team Members -->
    <div>
      <label class="block mb-1 text-sm font-medium">Team Members</label>
      <div class="flex flex-wrap gap-2 mb-2">
        <span v-for="user in selectedUsers" :key="user.id"
          class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-2xl dark:bg-indigo-800 dark:text-white">
          {{ user.name }}
          <span v-if="user.id !== authUser.id" @click="removeUser(user.id)" class="ml-1 cursor-pointer">×</span>
        </span>
      </div>
      <input v-model="userSearch" type="text" placeholder="Search users..."
        class="w-full rounded-2xl border px-3 py-2 dark:bg-black dark:text-white" />
      <ul v-if="userSearch.trim().length > 0 && filteredUsers.length > 0"
        class="mt-1 max-h-32 overflow-y-auto rounded-2xl border dark:border-gray-700">
        <li v-for="user in filteredUsers" :key="user.id"
          class="cursor-pointer px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700" @click="addUser(user)">
          {{ user.name }}
        </li>
      </ul>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="toggle in [
        { label: 'Requires Login?', model: 'requires_login', description: 'User authentication required' },
        { label: 'Show Planet Nine Logo?', model: 'show_planetnine_logo', description: 'Display company branding' },
        { label: 'Show Sidebar Logo?', model: 'show_sidebar_logo', description: 'Logo in navigation' },
        { label: 'Show Footer?', model: 'show_footer', description: 'Display page footer' },
      ]" :key="toggle.model"
        class="bg-gray-50 dark:bg-gray-950 rounded-xl p-4 border border-gray-200 dark:border-gray-700">

        <!-- Toggle Header -->
        <div class="flex items-start justify-between mb-2">
          <div class="flex-1">
            <label class="text-sm font-semibold text-gray-900 dark:text-white block mb-1 text-center">
              {{ toggle.label }}
            </label>
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
              {{ toggle.description }}
            </p>
          </div>
        </div>

        <!-- Toggle Switch -->
        <div class="flex justify-center mt-3">
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="form[toggle.model]" class="sr-only peer" />
            <div
              class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:bg-green-600 transition-colors duration-200">
            </div>
            <div
              class="absolute w-5 h-5 bg-white rounded-full left-0.5 top-0.5 peer-checked:translate-x-full transition-transform duration-200 shadow-sm">
            </div>
          </label>
        </div>

        <!-- Status Indicator -->
        <div class="text-center mt-2">
          <span class="text-xs font-medium"
            :class="form[toggle.model] ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
            {{ form[toggle.model] ? 'Enabled' : 'Disabled' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="text-right pt-2">
      <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl disabled:opacity-50"
        :disabled="!isFormValid" @click="$emit('submit')">
        Submit
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

const selectedUsers = computed(() =>
  props.users.filter((u) => props.form.team_ids.includes(u.id))
);

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

const isFormValid = computed(() => {
  return (
    props.form.name.trim() !== '' &&
    props.form.client_id !== '' &&
    props.form.color_palette_id !== '' &&
    props.form.team_ids.length > 0
  );
});
</script>