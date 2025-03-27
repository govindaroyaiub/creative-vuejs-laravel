<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Edit File Transfer',
    href: '/file-transfer-edit',
  },
];

// Get file transfer data from Inertia props
const fileTransfer = ref(usePage().props.fileTransfer);

// Reactive form using Inertia's useForm
const form = useForm({
  name: '',
  client: '',
  files: [] as File[],
});

const fileSize = ref(0); // Store total file size in MB

// Populate form fields when the component mounts
onMounted(() => {
  if (fileTransfer.value) {
    form.name = fileTransfer.value.name;
    form.client = fileTransfer.value.client;
  }
});

// Handle file selection
const handleFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files) {
    const filesArray = Array.from(input.files);
    form.files = filesArray;

    // Calculate total file size in MB
    let totalSize = 0;
    filesArray.forEach(file => {
      totalSize += file.size;
    });

    fileSize.value = (totalSize / (1024 * 1024)).toFixed(2);
  }
};

const successMessage = ref('');

// Submit form for updating file transfer
const handleSubmit = async () => {
  const formData = new FormData();
  formData.append('name', form.name);
  formData.append('client', form.client);

  form.files.forEach(file => {
    formData.append('file[]', file);
  });

  try {
    const response = await axios.post(`/file-transfers-edit/${fileTransfer.value.id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    successMessage.value = 'File transfer updated successfully!';

    // ✅ Reset the file input and form files array
    form.files = [];
    document.getElementById('file').value = ''; // Clears file input field

    // ✅ Update file list from response (assuming server returns updated paths)
    if (response.data.file_paths) {
      fileTransfer.value.file_paths = response.data.file_paths;
    }

  } catch (error) {
    console.error('Update failed:', error.response?.data || error.message);
    successMessage.value = 'Failed to update file transfer. Please try again.';
  }
};
</script>

<template>
  <Head title="Edit File Transfers" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div v-if="successMessage" class="bg-green-500 text-white p-3 rounded-md mb-4">
            {{ successMessage }}
        </div>
      <form @submit.prevent="handleSubmit" class="space-y-6 w-full max-w-2xl mx-auto" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
        <input type="hidden" name="_method" value="PUT">
        <!-- Name Field -->
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
          <input type="text" name="name" id="name" v-model="form.name" required
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:focus:ring-indigo-400">
        </div>

        <!-- Client Field -->
        <div>
          <label for="client" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Client</label>
          <input type="text" name="client" id="client" v-model="form.client" required
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:focus:ring-indigo-400">
        </div>

        <div v-if="fileTransfer.file_paths.length > 0" class="border p-4 rounded-md shadow bg-gray-50 dark:bg-gray-800">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Existing Files</label>
        <div>
            <div v-for="(file, index) in fileTransfer.file_paths" :key="index" class="text-sm text-gray-600 dark:text-gray-300">
            {{ file }}
            </div>
        </div>
        </div>


        <!-- File Upload Field -->
        <div>
          <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Upload Zip Files</label>
          <input type="file" name="file[]" id="file" multiple accept=".zip" @change="handleFileChange"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:focus:ring-indigo-400">
          
          <!-- Individual file sizes -->
          <div v-if="form.files.length > 0" class="mt-4 space-y-2">
            <div v-for="(file, index) in form.files" :key="index" class="text-sm text-gray-600 dark:text-gray-300">
              {{ file.name }} - {{ (file.size / (1024 * 1024)).toFixed(2) }} MB
            </div>
          </div>

          <!-- Total file size -->
          <div v-if="fileSize" class="text-sm text-gray-600 dark:text-gray-300 mt-2">
            Total File Size: {{ fileSize }} MB
          </div>
        </div>

        <!-- Submit and Back Buttons -->
        <div class="flex space-x-4">
          <button type="submit"
            class="w-full py-3 px-6 bg-indigo-600 text-white rounded-lg shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
            Save Changes
          </button>
          <a type="button"
            class="text-center w-full py-3 px-6 bg-red-600 text-white rounded-lg shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-red-500 dark:hover:bg-red-600"
            :href="route('file-transfers')">
            Back
          </a>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<style scoped>
.dark .bg-gray-700 {
  background-color: #2a2a2a;
}

.dark .text-gray-300 {
  color: #e0e0e0;
}

.dark .border-gray-600 {
  border-color: #4a4a4a;
}

.dark .focus\:ring-indigo-500 {
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.5);
}
</style>