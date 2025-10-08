<template>
  <div class="space-y-6">
    <h2 class="text-lg font-semibold">Step 1: Basic Information</h2>

    <!-- Preview Name -->
    <div>
      <label for="preview-name" class="block mb-1 text-sm font-medium">Preview Name *</label>
      <input id="preview-name" v-model="form.name" type="text" placeholder="e.g. Facebook Ad - June"
        class="w-full rounded-2xl border border-gray-300 dark:border-gray-600 px-3 py-2 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
        required maxlength="255" :class="{ 'border-red-500': formErrors.name }" />
      <p v-if="formErrors.name" class="text-red-500 text-xs mt-1">{{ formErrors.name }}</p>
    </div>

    <!-- Client, Header Logo, and Color Palette row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Client Dropdown -->
      <div>
        <label for="client-select" class="block mb-1 text-sm font-medium">Client *</label>
        <select id="client-select" v-model="form.client_id"
          class="w-full rounded-2xl border border-gray-300 dark:border-gray-600 px-3 py-2 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
          required :class="{ 'border-red-500': formErrors.client_id }">
          <option disabled value="">Select Client</option>
          <option v-for="client in clients" :key="client.id" :value="client.id">
            {{ client.name }}
          </option>
        </select>
        <p v-if="formErrors.client_id" class="text-red-500 text-xs mt-1">{{ formErrors.client_id }}</p>
      </div>

      <!-- Header Logo Dropdown (showing clients data) -->
      <div>
        <label for="header-logo-select" class="block mb-1 text-sm font-medium">Header Logo *</label>
        <select id="header-logo-select" v-model="form.header_logo_id"
          class="w-full rounded-2xl border border-gray-300 dark:border-gray-600 px-3 py-2 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
          required :class="{ 'border-red-500': formErrors.header_logo_id }">
          <option disabled value="">Select Header Logo</option>
          <option v-for="client in clients" :key="client.id" :value="client.id">
            {{ client.name }}
          </option>
        </select>
        <p v-if="formErrors.header_logo_id" class="text-red-500 text-xs mt-1">{{ formErrors.header_logo_id }}</p>
      </div>

      <!-- Color Palette Dropdown -->
      <div>
        <label for="theme-select" class="block mb-1 text-sm font-medium">Theme *</label>
        <select id="theme-select" v-model="form.color_palette_id"
          class="w-full rounded-2xl border border-gray-300 dark:border-gray-600 px-3 py-2 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
          required :class="{ 'border-red-500': formErrors.color_palette_id }">
          <option disabled value="">Select Theme</option>
          <option v-for="palette in colorPalettes" :key="palette.id" :value="palette.id">
            {{ palette.name }}
          </option>
        </select>
        <p v-if="formErrors.color_palette_id" class="text-red-500 text-xs mt-1">{{ formErrors.color_palette_id }}</p>
      </div>
    </div>

    <!-- Team Members -->
    <div>
      <label for="user-search" class="block mb-1 text-sm font-medium">Team Members *</label>
      <div class="flex flex-wrap gap-2 mb-2" v-if="selectedUsers.length > 0">
        <span v-for="user in selectedUsers" :key="user.id"
          class="inline-flex items-center bg-indigo-100 text-indigo-700 text-xs px-3 py-1 rounded-full dark:bg-indigo-800 dark:text-indigo-200 transition-colors">
          {{ user.name }}
          <button v-if="user.id !== authUser.id" @click="removeUser(user.id)"
            class="ml-2 text-indigo-500 hover:text-red-500 focus:outline-none focus:text-red-500 transition-colors"
            type="button" :aria-label="`Remove ${user.name} from team`">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"></path>
            </svg>
          </button>
        </span>
      </div>

      <div class="relative">
        <input id="user-search" v-model="userSearch" type="text" placeholder="Search and add team members..."
          class="w-full rounded-2xl border border-gray-300 dark:border-gray-600 px-3 py-2 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
          :class="{ 'border-red-500': formErrors.team_ids }" autocomplete="off" />

        <!-- User Search Results -->
        <div v-if="userSearch.trim().length > 0 && filteredUsers.length > 0"
          class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl shadow-lg max-h-40 overflow-y-auto">
          <button v-for="user in filteredUsers" :key="user.id" type="button"
            class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 focus:outline-none transition-colors first:rounded-t-xl last:rounded-b-xl"
            @click="addUser(user)">
            {{ user.name }}
          </button>
        </div>

        <!-- No results message -->
        <div v-else-if="userSearch.trim().length > 0 && filteredUsers.length === 0"
          class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl shadow-lg px-3 py-2 text-gray-500 dark:text-gray-400 text-sm">
          No users found matching "{{ userSearch }}"
        </div>
      </div>

      <p v-if="formErrors.team_ids" class="text-red-500 text-xs mt-1">{{ formErrors.team_ids }}</p>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
        {{ selectedUsers.length }} member{{ selectedUsers.length !== 1 ? 's' : '' }} selected
      </p>
    </div>

    <!-- Toggle Configuration Section -->
    <div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="toggle in toggleConfigs" :key="toggle.model"
          class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 border border-gray-200 dark:border-gray-700 transition-colors">
          <!-- Toggle Header -->
          <div class="text-center mb-3">
            <label :for="`toggle-${toggle.model}`"
              class="text-sm font-semibold text-gray-900 dark:text-white block mb-1 cursor-pointer">
              {{ toggle.label }}
            </label>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ toggle.description }}
            </p>
          </div>

          <!-- Toggle Switch -->
          <div class="flex justify-center mb-3">
            <label class="relative inline-flex items-center cursor-pointer">
              <input :id="`toggle-${toggle.model}`" type="checkbox" v-model="form[toggle.model]" class="sr-only peer" />
              <div
                class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:bg-green-600 transition-all duration-200">
              </div>
              <div
                class="absolute w-5 h-5 bg-white rounded-full left-0.5 top-0.5 peer-checked:translate-x-full transition-transform duration-200 shadow-sm">
              </div>
            </label>
          </div>

          <!-- Status Indicator -->
          <div class="text-center">
            <span class="text-xs font-medium px-2 py-1 rounded-full" :class="form[toggle.model]
              ? 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900'
              : 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-800'">
              {{ form[toggle.model] ? 'Enabled' : 'Disabled' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
      <div v-if="!isFormValid" class="text-sm text-gray-500 dark:text-gray-400">
        Please fill all required fields
      </div>
      <div v-else class="text-sm text-green-600 dark:text-green-400">
        ✓ Form is ready to submit
      </div>

      <button type="button"
        class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-2 rounded-xl font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
        :disabled="!isFormValid" @click="handleSubmit">
        <span v-if="!isSubmitting">Save</span>
        <span v-else class="flex items-center">
          <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
          </svg>
          Processing...
        </span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, reactive } from 'vue';

// TypeScript interfaces
interface FormData {
  name: string;
  client_id: string | number;
  color_palette_id: string | number;
  header_logo_id: string | number;
  team_ids: number[];
  requires_login: boolean;
  show_planetnine_logo: boolean;
  show_sidebar_logo: boolean;
  show_footer: boolean;
}

interface User {
  id: number;
  name: string;
  email?: string;
}

interface Client {
  id: number;
  name: string;
}

interface ColorPalette {
  id: number;
  name: string;
}

// Props - headerLogos can be omitted since we're using clients data
const props = defineProps<{
  form: FormData;
  users: User[];
  clients: Client[];
  colorPalettes: ColorPalette[];
  headerLogos?: Client[]; // Optional since we use clients data
  authUser: User;
}>();

// Emit declaration
const emit = defineEmits<{
  submit: [];
}>();

// Reactive data
const userSearch = ref('');
const isSubmitting = ref(false);

// Form validation errors
const formErrors = reactive({
  name: '',
  client_id: '',
  color_palette_id: '',
  header_logo_id: '',
  team_ids: ''
});

// Toggle configuration
const toggleConfigs = [
  {
    label: 'Requires Login?',
    model: 'requires_login' as keyof FormData,
    description: 'User authentication required'
  },
  {
    label: 'Show Header Logo?',
    model: 'show_planetnine_logo' as keyof FormData,
    description: 'Display company branding'
  },
  {
    label: 'Show Sidebar Logo?',
    model: 'show_sidebar_logo' as keyof FormData,
    description: 'Logo in navigation'
  },
  {
    label: 'Show Footer?',
    model: 'show_footer' as keyof FormData,
    description: 'Display page footer'
  },
] as const;

// Computed properties
const selectedUsers = computed(() =>
  props.users.filter((u) => props.form.team_ids.includes(u.id))
);

const filteredUsers = computed(() => {
  const query = userSearch.value.toLowerCase().trim();
  if (!query) return [];

  return props.users
    .filter((u) => !props.form.team_ids.includes(u.id))
    .filter((u) => u.name.toLowerCase().includes(query))
    .slice(0, 5); // Limit results for performance
});

const isFormValid = computed(() => {
  const isValid = (
    props.form.name.trim() !== '' &&
    props.form.client_id !== '' &&
    props.form.color_palette_id !== '' &&
    props.form.header_logo_id !== '' &&
    props.form.team_ids.length > 0
  );

  // Clear errors if form becomes valid
  if (isValid) {
    clearErrors();
  }

  return isValid;
});

// Methods
const addUser = (user: User) => {
  if (!props.form.team_ids.includes(user.id)) {
    props.form.team_ids.push(user.id);
    formErrors.team_ids = '';
  }
  userSearch.value = '';
};

const removeUser = (id: number) => {
  // Prevent removing the authenticated user
  if (id === props.authUser.id) return;

  props.form.team_ids = props.form.team_ids.filter((uid: number) => uid !== id);
};

const clearErrors = () => {
  Object.keys(formErrors).forEach(key => {
    formErrors[key as keyof typeof formErrors] = '';
  });
};

const validateForm = (): boolean => {
  clearErrors();
  let isValid = true;

  if (!props.form.name.trim()) {
    formErrors.name = 'Preview name is required';
    isValid = false;
  }

  if (!props.form.client_id) {
    formErrors.client_id = 'Please select a client';
    isValid = false;
  }

  if (!props.form.color_palette_id) {
    formErrors.color_palette_id = 'Please select a theme';
    isValid = false;
  }

  if (!props.form.header_logo_id) {
    formErrors.header_logo_id = 'Please select a header logo';
    isValid = false;
  }

  if (props.form.team_ids.length === 0) {
    formErrors.team_ids = 'Please add at least one team member';
    isValid = false;
  }

  return isValid;
};

const handleSubmit = async () => {
  if (!validateForm()) return;

  try {
    isSubmitting.value = true;
    emit('submit');
  } catch (error) {
    console.error('Submit error:', error);
  } finally {
    isSubmitting.value = false;
  }
};

// Clear search when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement;
  if (!target.closest('#user-search') && !target.closest('.absolute')) {
    userSearch.value = '';
  }
};

// Auto-focus search when typing
const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === '/' && event.target !== document.getElementById('user-search')) {
    event.preventDefault();
    document.getElementById('user-search')?.focus();
  }
};

// Lifecycle hooks
import { onMounted, onUnmounted } from 'vue';

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('keydown', handleKeydown);
});
</script>