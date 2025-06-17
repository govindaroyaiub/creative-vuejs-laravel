<template>
  <div class="space-y-6">
    <h2 class="text-lg font-semibold">Step 2: Select Project Type</h2>

    <!-- Advanced Toggle -->
    <div>
      <label class="inline-flex items-center space-x-2 cursor-pointer">
        <input type="checkbox" v-model="showAdvanced" class="form-checkbox" />
        <span class="text-sm text-gray-700 dark:text-white">Customize version details</span>
      </label>
    </div>

    <!-- Advanced Fields with Transition -->
    <Transition name="fade-slide">
      <div v-if="showAdvanced" class="space-y-4">
        <!-- Version Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Version Name</label>
          <input v-model="form.version_name" type="text"
            class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:text-white" />
        </div>

        <!-- Version Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Version Description</label>
          <textarea v-model="form.version_description" rows="2"
            class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:text-white"></textarea>
        </div>

        <!-- SubVersion Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">SubVersion Name</label>
          <input v-model="form.sub_version_name" type="text"
            class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:text-white" />
        </div>

        <!-- Is Active Toggle -->
        <div>
          <label class="inline-flex items-center space-x-2">
            <input type="checkbox" v-model="form.is_active" class="form-checkbox" />
            <span class="text-sm text-gray-700 dark:text-white">Set as active</span>
          </label>
        </div>
      </div>
    </Transition>

    <!-- Type Selection -->
    <div class="grid grid-cols-2 gap-4 pt-4">
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
const showAdvanced = ref(false);

watch(showAdvanced, (val) => {
  if (val) {
    if (!form.version_name) form.version_name = 'Master';
    if (!form.version_description) form.version_description = 'Master Started';
    if (!form.sub_version_name) form.sub_version_name = 'Version 1';
    if (typeof form.is_active === 'undefined') form.is_active = true;
  }
});
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(-8px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>