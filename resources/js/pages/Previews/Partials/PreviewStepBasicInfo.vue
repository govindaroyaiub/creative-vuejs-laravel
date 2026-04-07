<template>
  <div class="space-y-4">
    <!-- Preview Name -->
    <div>
      <label for="preview-name"
        class="block mb-1 text-sm font-medium text-[#666666] dark:text-[#999999] uppercase tracking-widest font-mono">PREVIEW
        NAME *</label>
      <input id="preview-name" :value="form.name"
        @input="emit('updateForm', 'name', ($event.target as HTMLInputElement).value)" type="text"
        placeholder="E.G. FACEBOOK AD - JUNE"
        class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
        required maxlength="255" :class="{ 'border-[#D71921] focus:border-[#D71921]': formErrors.name }" />
      <p v-if="formErrors.name" class="text-[#D71921] text-xs mt-1">{{ formErrors.name }}</p>
    </div>

    <!-- Client, Header Logo, and Color Palette row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Client Dropdown -->
      <div>
        <label for="client-select"
          class="block mb-1 text-sm font-medium text-[#666666] dark:text-[#999999] uppercase tracking-widest font-mono">CLIENT
          *</label>
        <select id="client-select" :value="form.client_id"
          @change="emit('updateForm', 'client_id', ($event.target as HTMLSelectElement).value)"
          class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors"
          required :class="{ 'border-[#D71921] focus:border-[#D71921]': formErrors.client_id }">
          <option disabled value="">SELECT CLIENT</option>
          <option v-for="client in clients" :key="client.id" :value="client.id">
            {{ client.name }}
          </option>
        </select>
        <p v-if="formErrors.client_id" class="text-[#D71921] text-xs mt-1">{{ formErrors.client_id }}</p>
      </div>

      <!-- Header Logo Dropdown (showing clients data) -->
      <div>
        <label for="header-logo-select"
          class="block mb-1 text-sm font-medium text-[#666666] dark:text-[#999999] uppercase tracking-widest font-mono">HEADER
          LOGO *</label>
        <select id="header-logo-select" :value="form.header_logo_id"
          @change="emit('updateForm', 'header_logo_id', ($event.target as HTMLSelectElement).value)"
          class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors"
          required :class="{ 'border-[#D71921] focus:border-[#D71921]': formErrors.header_logo_id }">
          <option disabled value="">SELECT HEADER LOGO</option>
          <option v-for="client in clients" :key="client.id" :value="client.id">
            {{ client.name }}
          </option>
        </select>
        <p v-if="formErrors.header_logo_id" class="text-[#D71921] text-xs mt-1">{{ formErrors.header_logo_id }}</p>
      </div>

      <!-- Color Palette Dropdown -->
      <div>
        <label for="theme-select"
          class="block mb-1 text-sm font-medium text-[#666666] dark:text-[#999999] uppercase tracking-widest font-mono">THEME
          *</label>
        <select id="theme-select" :value="form.color_palette_id"
          @change="emit('updateForm', 'color_palette_id', ($event.target as HTMLSelectElement).value)"
          class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors"
          required :class="{ 'border-[#D71921] focus:border-[#D71921]': formErrors.color_palette_id }">
          <option disabled value="">SELECT THEME</option>
          <option v-for="palette in colorPalettes" :key="palette.id" :value="palette.id">
            {{ palette.name }}
          </option>
        </select>
        <p v-if="formErrors.color_palette_id" class="text-[#D71921] text-xs mt-1">{{ formErrors.color_palette_id }}</p>
      </div>
    </div>

    <!-- Team Members -->
    <div>
      <label for="user-search"
        class="block mb-1 text-sm font-medium text-[#666666] dark:text-[#999999] uppercase tracking-widest font-mono">TEAM
        MEMBERS *</label>
      <div class="flex flex-wrap gap-2 mb-2" v-if="selectedUsers.length > 0">
        <span v-for="user in selectedUsers" :key="user.id"
          class="inline-flex items-center border-2 border-black dark:border-white text-black dark:text-white text-xs px-3 py-1 rounded-full uppercase tracking-wider font-mono transition-colors">
          {{ user.name }}
          <button v-if="user.id !== authUser.id" @click="removeUser(user.id)"
            class="ml-2 text-black dark:text-white hover:text-[#D71921] dark:hover:text-[#D71921] focus:outline-none transition-colors"
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
        <input id="user-search" v-model="userSearch" type="text" placeholder="SEARCH AND ADD TEAM MEMBERS..."
          class="w-full rounded-lg border-2 border-[#CCCCCC] dark:border-[#333333] px-3 py-2 bg-white dark:bg-[#111111] text-black dark:text-white placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
          :class="{ 'border-[#D71921] focus:border-[#D71921]': formErrors.team_ids }" autocomplete="off" />

        <!-- User Search Results -->
        <div v-if="userSearch.trim().length > 0 && filteredUsers.length > 0"
          class="absolute z-10 w-full mt-1 bg-white dark:bg-[#111111] border-2 border-[#E8E8E8] dark:border-[#222222] rounded-lg max-h-40 overflow-y-auto">
          <button v-for="user in filteredUsers" :key="user.id" type="button"
            class="w-full text-left px-3 py-2 hover:bg-[#F5F5F5] dark:hover:bg-black focus:bg-[#F5F5F5] dark:focus:bg-black focus:outline-none transition-colors first:rounded-t-lg last:rounded-b-lg"
            @click="addUser(user)">
            {{ user.name }}
          </button>
        </div>

        <!-- No results message -->
        <div v-else-if="userSearch.trim().length > 0 && filteredUsers.length === 0"
          class="absolute z-10 w-full mt-1 bg-white dark:bg-[#111111] border-2 border-[#E8E8E8] dark:border-[#222222] rounded-lg px-3 py-2 text-[#666666] dark:text-[#999999] text-sm uppercase tracking-wider font-mono">
          NO USERS FOUND MATCHING "{{ userSearch }}"
        </div>
      </div>

      <p v-if="formErrors.team_ids" class="text-[#D71921] text-xs mt-1">{{ formErrors.team_ids }}</p>
      <p class="text-xs text-[#666666] dark:text-[#999999] mt-1 uppercase tracking-wider font-mono">
        {{ selectedUsers.length }} MEMBER{{ selectedUsers.length !== 1 ? 'S' : '' }} SELECTED
      </p>
    </div>

    <!-- Toggle Configuration Section -->
    <div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="toggle in toggleConfigs" :key="toggle.model"
          class="bg-white dark:bg-[#111111] rounded-lg p-4 border-2 border-[#E8E8E8] dark:border-[#222222] hover:border-black dark:hover:border-white transition-colors">
          <!-- Toggle Header -->
          <div class="text-center mb-3">
            <label :for="`toggle-${toggle.model}`"
              class="text-sm font-semibold text-black dark:text-white block mb-1 cursor-pointer uppercase tracking-wider font-mono">
              {{ toggle.label }}
            </label>
            <p class="text-xs text-[#666666] dark:text-[#999999]">
              {{ toggle.description }}
            </p>
          </div>

          <!-- Toggle Switch -->
          <div class="flex justify-center mb-3">
            <label class="relative inline-flex items-center cursor-pointer">
              <input :id="`toggle-${toggle.model}`" type="checkbox" :checked="Boolean(form[toggle.model])"
                @change="emit('updateForm', toggle.model, ($event.target as HTMLInputElement).checked)"
                class="sr-only peer" />
              <div
                class="w-11 h-6 bg-[#E8E8E8] dark:bg-[#222222] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-black dark:peer-focus:ring-white rounded-full peer peer-checked:bg-black dark:peer-checked:bg-white transition-all duration-200">
              </div>
              <div
                class="absolute w-5 h-5 bg-white dark:bg-black peer-checked:bg-white rounded-full left-0.5 top-0.5 peer-checked:translate-x-full transition-transform duration-200">
              </div>
            </label>
          </div>

          <!-- Status Indicator -->
          <div class="text-center">
            <span class="text-xs font-medium px-2 py-1 rounded-full uppercase tracking-wider font-mono" :class="form[toggle.model]
              ? 'text-white bg-black dark:text-black dark:bg-white'
              : 'text-[#666666] bg-[#E8E8E8] dark:text-[#999999] dark:bg-[#222222]'">
              {{ form[toggle.model] ? 'ENABLED' : 'DISABLED' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-between items-center pt-4 border-t border-[#E8E8E8] dark:border-[#222222]">
      <div v-if="!isFormValid" class="text-sm text-[#666666] dark:text-[#999999] uppercase tracking-wider font-mono">
        PLEASE FILL ALL REQUIRED FIELDS
      </div>
      <div v-else class="text-sm text-black dark:text-white uppercase tracking-wider font-mono">
        ✓ FORM IS READY TO SUBMIT
      </div>

      <button type="button"
        class="bg-black hover:bg-white hover:text-black dark:bg-white dark:hover:bg-black dark:hover:text-white disabled:bg-[#CCCCCC] disabled:text-[#999999] disabled:cursor-not-allowed text-white dark:text-black px-6 py-2 rounded-full font-medium uppercase tracking-wider font-mono transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white border-2 border-black dark:border-white"
        :disabled="!isFormValid" @click="handleSubmit">
        <span v-if="!isSubmitting">SAVE</span>
        <span v-else class="flex items-center">
          <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
          </svg>
          PROCESSING...
        </span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, reactive, watch } from 'vue';

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
  updateForm: [key: string, value: any];
}>();

// Reactive data
const userSearch = ref('');
const isSubmitting = ref(false);

// Form alias for template usage
const form = computed(() => props.form);

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
    description: 'Display Planet Nine logo'
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

const selectedClient = computed(() => {
  return props.clients.find(client => client.id == props.form.client_id);
});

const isPlanetNineSelected = computed(() => {
  return selectedClient.value?.name === 'Planet Nine';
});

// Automatically control show_sidebar_logo based on client selection
watch(isPlanetNineSelected, (newValue) => {
  if (newValue) {
    // Turn OFF when Planet Nine is selected
    emit('updateForm', 'show_sidebar_logo', false);
  } else {
    // Turn ON when any other client is selected
    emit('updateForm', 'show_sidebar_logo', true);
  }
}, { immediate: true }); const isFormValid = computed(() => {
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
    const newTeamIds = [...props.form.team_ids, user.id];
    emit('updateForm', 'team_ids', newTeamIds);
    formErrors.team_ids = '';
  }
  userSearch.value = '';
};

const removeUser = (id: number) => {
  // Prevent removing the authenticated user
  if (id === props.authUser.id) return;

  const newTeamIds = props.form.team_ids.filter((uid: number) => uid !== id);
  emit('updateForm', 'team_ids', newTeamIds);
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