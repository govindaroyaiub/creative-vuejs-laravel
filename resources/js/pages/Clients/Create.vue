<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Clients', href: '/clients' },
  { title: 'Add Client', href: '/clients-create' },
];

const page = usePage();
const colorPalettes = computed(() => page.props.colorPalettes);

const form = ref({
  name: '',
  website: '',
  preview_url: '',
  color_palette_id: '',
  logo: null as File | null,
});

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target?.files?.[0]) {
    form.value.logo = target.files[0];
  }
};

const handleSubmit = () => {
  const formData = new FormData();
  formData.append('name', form.value.name);
  formData.append('website', form.value.website);
  formData.append('preview_url', form.value.preview_url);
  formData.append('color_palette_id', String(form.value.color_palette_id));
  if (form.value.logo) {
    formData.append('logo', form.value.logo);
  }

  router.post('/clients-store', formData, {
    preserveScroll: true,
    onSuccess: () => Swal.fire('Success!', 'Client created successfully.', 'success'),
    onError: () => Swal.fire('Error!', 'Failed to create client.', 'error'),
  });
};
</script>

<template>

  <Head title="Add Client" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 max-w-3xl w-3/4 mx-auto">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium">Name</label>
          <input v-model="form.name" type="text"
            class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
        </div>

        <!-- Website -->
        <div>
          <label class="block text-sm font-medium">Website</label>
          <input v-model="form.website" type="url"
            class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required />
        </div>

        <!-- Preview URL -->
        <div>
          <label class="block text-sm font-medium">Preview URL</label>
          <input v-model="form.preview_url" type="url"
            class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" />
        </div>

        <!-- Brand Color (from Color Palettes) -->
        <div>
          <label class="block text-sm font-medium mb-1">Brand Color</label>
          <select v-model="form.color_palette_id"
            class="w-full rounded border px-3 py-2 dark:bg-gray-700 dark:text-white" required>
            <option disabled value="">Select a color palette</option>
            <option v-for="palette in colorPalettes" :key="palette.id" :value="palette.id">
              {{ palette.name }}
            </option>
          </select>

          <!-- Preview -->
          <div v-if="form.color_palette_id" class="mt-2 flex items-center gap-2">
            <span class="text-sm text-gray-500">Preview:</span>
            <div v-if="colorPalettes.find(p => p.id == form.color_palette_id)"
              :style="{ backgroundColor: colorPalettes.find(p => p.id == form.color_palette_id)?.primary }"
              class="h-5 w-10 rounded-lg border"></div>
            <span class="text-sm text-gray-500">
              {{colorPalettes.find(p => p.id == form.color_palette_id)?.primary}}
            </span>
          </div>
        </div>

        <!-- Logo -->
        <div>
          <label class="block text-sm font-medium">Logo (Image)</label>
          <input type="file" accept="image/*" @change="handleFileChange" class="block w-full text-sm" />
        </div>

        <!-- Submit Button -->
        <div class="flex space-x-4">
          <button type="submit"
            class="w-full rounded-lg bg-green-600 px-6 py-3 text-white shadow-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 dark:bg-green-500 dark:hover:bg-green-600">
            Save
          </button>
          <Link type="button"
            class="w-full text-center rounded-lg bg-red-600 px-6 py-3 text-white shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-red-500 dark:hover:bg-red-600"
            :href="route('clients')">
          Back
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>