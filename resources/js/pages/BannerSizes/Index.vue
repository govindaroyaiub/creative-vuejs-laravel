<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Settings, CircleX, CirclePlus, ArrowBigLeft, ArrowBigRight, Share2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { ref, computed } from "vue";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Banner Sizes',
        href: '/banner-sizes',
    },
];

const bannerSizes = ref([
    { id: 1, width: 728, height: 90 },
    { id: 2, width: 300, height: 250 },
    { id: 3, width: 160, height: 600 },
  ])
  
  const editSize = (size) => {
    console.log('Edit clicked for', size)
    // Navigate to edit page or open modal
  }
  
  const deleteSize = (id) => {
    console.log('Delete clicked for ID:', id)
    // Confirm and delete logic here
  }

</script>

<template>
    <Head title="Banner Sizes" />
  
    <AppLayout :breadcrumbs="breadcrumbs">
      <div class="p-6">
        <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-100">
          Banner Sizes
        </h2>
        <div class="flex justify-between items-center mb-4">
                <!-- Search Bar -->
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search..."
                    class="px-4 py-2 border rounded-md w-full max-w-xs dark:bg-gray-700 dark:text-white"
                />

                <!-- Add Button -->
                <a :href="route('banner-sizes-create')" class="ml-4 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">
                    <CirclePlus class="w-6 h-6 inline-block" />
                </a>
            </div>
  
        <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg shadow">
          <thead>
            <tr class="bg-gray-100 dark:bg-gray-700 text-center text-sm uppercase text-gray-600 dark:text-gray-300">
              <th class="px-4 py-3">#</th>
              <th class="px-4 py-3">Width</th>
              <th class="px-4 py-3">Height</th>
              <th class="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(size, index) in bannerSizes"
              :key="size.id"
              class="border-t border-gray-200 dark:border-gray-700 text-sm text-center hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ index + 1 }}</td>
              <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ size.width }}</td>
              <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ size.height }}</td>
              <td class="px-4 py-2 space-x-2">
                <button class="text-blue-600 dark:text-blue-400 hover:underline">
                    <Settings class="w-8 h-8 inline-block" />
                </button>
                <button class="text-red-600 dark:text-red-400 hover:underline">
                    <CircleX class="w-8 h-8 inline-block" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppLayout>
  </template>
  