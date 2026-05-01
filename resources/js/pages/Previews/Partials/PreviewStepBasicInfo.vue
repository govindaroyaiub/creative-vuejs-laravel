<template>
  <div class="flex h-full flex-col">
    <!-- Header bar: title + actions + close -->
    <div
      class="flex items-center justify-between gap-2 border-b border-[#E8E8E8] dark:border-[#222222] px-3 py-2 shrink-0">
      <h2 class="text-base font-semibold font-mono text-black dark:text-white truncate">Create new preview</h2>
      <div class="flex items-center gap-2">
        <button type="button" @click="emit('close')"
          class="inline-flex items-center px-2 py-2 rounded-full border border-[#CCCCCC] dark:border-[#333333] text-xs font-mono tracking-wide text-[#1A1A1A] dark:text-[#E8E8E8] bg-white dark:bg-[#111111] hover:border-black dark:hover:border-white transition-colors">
          Cancel
        </button>
        <button type="button" @click="handleSubmit" :disabled="!isFormValid || isSubmitting"
          class="inline-flex items-center gap-1.5 px-2 py-2 rounded-full border-2 border-black dark:border-white bg-black dark:bg-white text-white dark:text-black text-xs font-mono tracking-wide hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
          <svg v-if="isSubmitting" class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          {{ isSubmitting ? 'Creating...' : 'Create preview' }}
        </button>
        <button type="button" @click="emit('close')"
          class="p-1.5 text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white border border-transparent hover:border-[#CCCCCC] dark:hover:border-[#333333] rounded transition-colors"
          aria-label="Close">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Body: 2-col grid -->
    <div class="grid flex-1 min-h-0 grid-cols-1 gap-3 p-3 lg:grid-cols-3">
      <!-- Left column: identification (2/3 width) -->
      <div
        class="lg:col-span-2 flex min-h-0 flex-col gap-3 rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A] p-3">
        <!-- Name -->
        <div>
          <label for="preview-name"
            class="mb-1 block text-[10px] font-medium uppercase tracking-widest font-mono text-[#666666] dark:text-[#999999]">
            Preview name *
          </label>
          <input id="preview-name" :value="form.name"
            @input="emit('updateForm', 'name', ($event.target as HTMLInputElement).value)" type="text"
            placeholder="e.g. Facebook Ad - June"
            class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
            :class="{ 'border-[#D71921] focus:border-[#D71921]': formErrors.name }" required maxlength="255" />
          <p v-if="formErrors.name" class="mt-1 text-xs text-[#D71921]">{{ formErrors.name }}</p>
        </div>

        <!-- Client / Header Logo / Theme -->
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <div>
            <label for="client-select"
              class="mb-1 block text-[10px] font-medium uppercase tracking-widest font-mono text-[#666666] dark:text-[#999999]">Client
              *</label>
            <select id="client-select" :value="form.client_id"
              @change="emit('updateForm', 'client_id', ($event.target as HTMLSelectElement).value)"
              class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors"
              :class="{ 'border-[#D71921] focus:border-[#D71921]': formErrors.client_id }" required>
              <option disabled value="">Select client</option>
              <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
            </select>
            <p v-if="formErrors.client_id" class="mt-1 text-xs text-[#D71921]">{{ formErrors.client_id }}</p>
          </div>
          <div>
            <label for="header-logo-select"
              class="mb-1 block text-[10px] font-medium uppercase tracking-widest font-mono text-[#666666] dark:text-[#999999]">Header
              logo *</label>
            <select id="header-logo-select" :value="form.header_logo_id"
              @change="emit('updateForm', 'header_logo_id', ($event.target as HTMLSelectElement).value)"
              class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors"
              :class="{ 'border-[#D71921] focus:border-[#D71921]': formErrors.header_logo_id }" required>
              <option disabled value="">Select header logo</option>
              <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
            </select>
            <p v-if="formErrors.header_logo_id" class="mt-1 text-xs text-[#D71921]">{{ formErrors.header_logo_id }}</p>
          </div>
          <div>
            <label for="theme-select"
              class="mb-1 block text-[10px] font-medium uppercase tracking-widest font-mono text-[#666666] dark:text-[#999999]">Theme
              *</label>
            <select id="theme-select" :value="form.color_palette_id"
              @change="emit('updateForm', 'color_palette_id', ($event.target as HTMLSelectElement).value)"
              class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:outline-none focus:border-black dark:focus:border-white transition-colors"
              :class="{ 'border-[#D71921] focus:border-[#D71921]': formErrors.color_palette_id }" required>
              <option disabled value="">Select theme</option>
              <option v-for="palette in colorPalettes" :key="palette.id" :value="palette.id">{{ palette.name }}</option>
            </select>
            <p v-if="formErrors.color_palette_id" class="mt-1 text-xs text-[#D71921]">{{ formErrors.color_palette_id }}</p>
          </div>
        </div>

        <!-- Team Members (fills remaining vertical space) -->
        <div class="flex flex-1 min-h-0 flex-col">
          <div class="mb-1 flex items-center justify-between">
            <label for="team-search"
              class="text-[10px] font-medium uppercase tracking-widest font-mono text-[#666666] dark:text-[#999999]">Team
              members *</label>
            <span class="text-[10px] font-mono text-[#999999]">{{ selectedUsers.length }} selected</span>
          </div>
          <input id="team-search" v-model="userSearch" type="text" placeholder="Search team members..."
            class="mb-1.5 w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white placeholder-[#999999] dark:placeholder-[#666666] focus:outline-none focus:border-black dark:focus:border-white transition-colors"
            :class="{ 'border-[#D71921] focus:border-[#D71921]': formErrors.team_ids }" autocomplete="off" />

          <div
            class="flex-1 min-h-0 overflow-y-auto rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#111111] divide-y divide-[#E8E8E8] dark:divide-[#222222]">
            <button v-for="user in displayUsers" :key="user.id" type="button" @click="toggleUser(user)"
              :disabled="user.id === authUser.id"
              class="w-full text-left px-2 py-2 transition-colors flex items-center gap-2 hover:bg-[#F5F5F5] dark:hover:bg-black focus:bg-[#F5F5F5] dark:focus:bg-black focus:outline-none disabled:cursor-not-allowed">
              <span
                class="w-6 h-6 flex-shrink-0 rounded-full grid place-items-center text-[10px] font-bold"
                :class="form.team_ids.includes(user.id)
                  ? 'bg-black dark:bg-white text-white dark:text-black'
                  : 'bg-[#F5F5F5] dark:bg-black text-[#666666] dark:text-[#999999] border border-[#E8E8E8] dark:border-[#222222]'">
                {{ user.name.charAt(0).toUpperCase() }}
              </span>
              <span class="flex-1 truncate text-sm"
                :class="form.team_ids.includes(user.id) ? 'text-black dark:text-white font-semibold' : 'text-[#666666] dark:text-[#999999]'">
                {{ user.name }}
              </span>
              <span v-if="user.id === authUser.id"
                class="text-[9px] font-mono uppercase tracking-widest text-[#999999]">You</span>
              <span v-else-if="form.team_ids.includes(user.id)" class="text-black dark:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </span>
            </button>
            <div v-if="displayUsers.length === 0" class="px-2 py-4 text-center text-xs font-mono text-[#999999]">
              No users found
            </div>
          </div>
          <p v-if="formErrors.team_ids" class="mt-1 text-xs text-[#D71921]">{{ formErrors.team_ids }}</p>
        </div>
      </div>

      <!-- Right column: settings (1/3 width) -->
      <div
        class="flex flex-col rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A] p-3">
        <h2
          class="mb-2 text-[10px] font-medium uppercase tracking-widest font-mono text-[#666666] dark:text-[#999999]">
          Settings
        </h2>
        <div class="flex flex-1 flex-col justify-around">
          <div v-for="toggle in toggleConfigs" :key="toggle.model"
            class="flex items-center justify-between gap-3 border-b border-[#E8E8E8] dark:border-[#222222] py-2 last:border-b-0">
            <div class="min-w-0">
              <label :for="`toggle-${toggle.model}`"
                class="block text-xs font-semibold font-mono text-black dark:text-white cursor-pointer">{{ toggle.label
                }}</label>
              <p class="text-[10px] text-[#666666] dark:text-[#999999]">{{ toggle.description }}</p>
            </div>
            <label class="relative inline-flex flex-shrink-0 cursor-pointer items-center">
              <input :id="`toggle-${toggle.model}`" type="checkbox" :checked="Boolean(form[toggle.model])"
                @change="emit('updateForm', toggle.model, ($event.target as HTMLInputElement).checked)"
                class="sr-only peer" />
              <div
                class="w-9 h-5 bg-[#E8E8E8] dark:bg-[#222222] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-[#CCCCCC] after:border dark:after:bg-black after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black dark:peer-checked:bg-white">
              </div>
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, reactive, watch } from 'vue';

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

interface User { id: number; name: string; email?: string; }
interface Client { id: number; name: string; }
interface ColorPalette { id: number; name: string; }

const props = defineProps<{
  form: FormData;
  users: any;
  clients: any;
  colorPalettes: any;
  headerLogos?: any;
  authUser: any;
}>();

const emit = defineEmits<{
  submit: [];
  close: [];
  updateForm: [key: string, value: any];
}>();

const userSearch = ref('');
const isSubmitting = ref(false);

const formErrors = reactive({
  name: '',
  client_id: '',
  color_palette_id: '',
  header_logo_id: '',
  team_ids: '',
});

const toggleConfigs = [
  { label: 'Requires login?', model: 'requires_login' as keyof FormData, description: 'Auth required to view' },
  { label: 'Show header logo?', model: 'show_planetnine_logo' as keyof FormData, description: 'Display Planet Nine logo' },
  { label: 'Show sidebar logo?', model: 'show_sidebar_logo' as keyof FormData, description: 'Logo in navigation' },
  { label: 'Show footer in preview?', model: 'show_footer' as keyof FormData, description: 'Display page footer' },
] as const;

const selectedUsers = computed(() =>
  ((props.users as any[]) || []).filter((u: any) => props.form.team_ids.includes(u.id))
);

// All users (filtered by search query if any), with selected ones first
const displayUsers = computed(() => {
  const all: any[] = (props.users as any[]) || [];
  const query = userSearch.value.toLowerCase().trim();
  const list: any[] = query
    ? all.filter((u: any) => u.name.toLowerCase().includes(query))
    : all.slice();
  return list.sort((a: any, b: any) => {
    const aSel = props.form.team_ids.includes(a.id) ? 0 : 1;
    const bSel = props.form.team_ids.includes(b.id) ? 0 : 1;
    if (aSel !== bSel) return aSel - bSel;
    return a.name.localeCompare(b.name);
  });
});

const toggleUser = (user: any) => {
  if (user.id === props.authUser.id) return;
  if (props.form.team_ids.includes(user.id)) {
    const next = props.form.team_ids.filter((uid: number) => uid !== user.id);
    emit('updateForm', 'team_ids', next);
  } else {
    emit('updateForm', 'team_ids', [...props.form.team_ids, user.id]);
  }
};

const selectedClient = computed(() => ((props.clients as any[]) || []).find((c: any) => c.id == props.form.client_id));
const isPlanetNineSelected = computed(() => selectedClient.value?.name === 'Planet Nine');

// Auto-toggle sidebar logo when Planet Nine is selected
watch(isPlanetNineSelected, (newValue) => {
  emit('updateForm', 'show_sidebar_logo', !newValue);
}, { immediate: true });

const isFormValid = computed(() => {
  return (
    props.form.name.trim() !== '' &&
    props.form.client_id !== '' &&
    props.form.color_palette_id !== '' &&
    props.form.header_logo_id !== '' &&
    props.form.team_ids.length > 0
  );
});

const validateForm = (): boolean => {
  let ok = true;
  formErrors.name = props.form.name.trim() ? '' : 'Preview name is required';
  formErrors.client_id = props.form.client_id ? '' : 'Please select a client';
  formErrors.color_palette_id = props.form.color_palette_id ? '' : 'Please select a theme';
  formErrors.header_logo_id = props.form.header_logo_id ? '' : 'Please select a header logo';
  formErrors.team_ids = props.form.team_ids.length > 0 ? '' : 'Please add at least one team member';
  Object.values(formErrors).forEach((v) => { if (v) ok = false; });
  return ok;
};

const handleSubmit = async () => {
  if (!validateForm()) return;
  try {
    isSubmitting.value = true;
    emit('submit');
  } finally {
    isSubmitting.value = false;
  }
};
</script>
