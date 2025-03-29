<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Bolt, BadgeX, BadgePlus, ArrowBigLeft, ArrowBigRight, Share2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { ref, computed } from "vue";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'File Transfers',
        href: '/file-transfers',
    },
];

const { fileTransfers } = usePage().props;

const searchQuery = ref("");
const currentPage = ref(1);
const itemsPerPage = 10; // Items per page for pagination

// Filter the fileTransfers based on the search query
const filteredTransfers = computed(() => {
  return fileTransfers
    .filter((fileTransfer) => {
      return (
        fileTransfer.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        fileTransfer.client.toLowerCase().includes(searchQuery.value.toLowerCase())
      );
    })
    .slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage);
});

const totalPages = computed(() => {
  return Math.ceil(fileTransfers.length / itemsPerPage);
});

// Delete file transfer
const deleteTransfer = async (id) => {
  // Show SweetAlert2 confirmation modal
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: 'You will not be able to revert this!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
    // SweetAlert2 will auto-detect and apply the system's theme (light/dark mode)
 });

  // If confirmed, proceed with deletion
  if (result.isConfirmed) {
    try {
      // Get CSRF token from the meta tag
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

      // Send DELETE request using Axios with CSRF token
      await axios.get(`/file-transfers/${id}`, {
        headers: {
          'X-CSRF-TOKEN': csrfToken,
        },
      });

      // After the delete request, remove the item from the fileTransfers array
      const index = fileTransfers.findIndex(item => item.id === id);
      if (index !== -1) {
        fileTransfers.splice(index, 1);
      }

      // Success message after deletion
      await Swal.fire(
        'Deleted!',
        'Your file transfer has been deleted.',
        'success'
      );
    } catch (error) {
      // Error handling in case deletion fails
      console.log(error);
      await Swal.fire(
        'Error!',
        'There was an issue deleting the file transfer.',
        'error'
      );
    }
  }
};

// Pagination functions
const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

const copySuccess = ref(false);

const getTransferLink = async (id) => {
  const url = `${window.location.origin}/file-transfers-view/${id}`;

  try {
    await navigator.clipboard.writeText(url);
    copySuccess.value = true;

    // Hide message after 2 seconds
    setTimeout(() => {
      copySuccess.value = false;
    }, 3000);
  } catch (error) {
    console.error("Failed to copy URL:", error);
  }
};

</script>

<style scoped>
    /* Transition Effect */
    .fade-enter-active, .fade-leave-active {
    transition: opacity 1s ease;
    }

    .fade-enter, .fade-leave-to /* .fade-leave-active in <2.1.8 */ {
    opacity: 0;
    }
</style>

<template>
    <Head title="File Transfers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <transition name="fade">
                <div v-if="flashMessage" class="bg-green-500 text-white p-3 rounded-md mb-4">
                {{ flashMessage }}
                </div>
            </transition>

            <div v-if="copySuccess" class="bg-green-500 text-white p-3 rounded-md mb-4">
                URL Copied!
            </div>

            <!-- Search and Add Button Row -->
            <div class="flex justify-between items-center mb-4">
                <!-- Search Bar -->
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search..."
                    class="px-4 py-2 border rounded-md w-full max-w-xs dark:bg-gray-700 dark:text-white"
                />

                <!-- Add Button -->
                <a :href="route('file-transfers-add')" class="ml-4 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600">
                    <BadgePlus class="w-8 h-8 inline-block" />
                </a>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto bg-white dark:bg-gray-900 shadow-md rounded-lg">
                <table class="w-full border-collapse">
                    <!-- Table Header -->
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr class="text-gray-700 dark:text-gray-300">
                            <th class="px-6 py-3 text-left">#</th>
                            <th class="px-6 py-3 text-left">Name</th>
                            <th class="px-6 py-3 text-left">Client</th>
                            <th class="px-6 py-3 text-center">Uploader</th>
                            <th class="px-6 py-3 text-center">Actions</th>
                        </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody v-if="filteredTransfers.length === 0">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No data available.
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr
                        v-for="(fileTransfer, index) in filteredTransfers"
                        :key="fileTransfer.id"
                        class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                        >
                        <td class="px-6 py-4 text-gray-900 dark:text-gray-100">{{ index + 1 }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-gray-100">
                            <a :href="`/file-transfers-view/${fileTransfer.id}`" target="_blank" class="text-blue-500 hover:text-blue-700">
                            {{ fileTransfer.name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ fileTransfer.client }}</td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300 text-center">
                            {{ fileTransfer.user }}
                            <hr>
                            <span class="text-gray-500 text-sm">{{ fileTransfer.created_at }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button
                            class="text-green-500 hover:text-green-700 transition mr-1"
                            @click="getTransferLink(fileTransfer.id)"
                            >
                            <Share2 class="w-8 h-8 inline-block" />
                            </button>
                            <a
                            class="text-blue-500 hover:text-blue-700 transition mr-1"
                            :href="`/file-transfers-edit/${fileTransfer.id}`"
                            >
                            <Bolt class="w-8 h-8 inline-block" />
                            </a>
                            <button
                            class="text-red-500 hover:text-red-700 transition"
                            @click="deleteTransfer(fileTransfer.id)"
                            >
                            <BadgeX class="w-8 h-8 inline-block" />
                            </button>
                        </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div class="flex justify-between items-center mt-4">
                <button
                    :disabled="currentPage === 1"
                    @click="previousPage"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                >
                    <ArrowBigLeft class="w-8 h-8 inline-block" />
                </button>

                <div class="flex items-center space-x-2">
                    <span>Page {{ currentPage }} of {{ totalPages }}</span>
                </div>

                <button
                    :disabled="currentPage === totalPages"
                    @click="nextPage"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                >
                    <ArrowBigRight class="w-8 h-8 inline-block" />
                </button>
            </div>
        </div>
    </AppLayout>
</template>
<script lang="ts">
export default {
    data() {
        return {
            searchQuery: "", // To hold the search query
            currentPage: 1, // Current page number
            perPage: 10, // Number of items per page
            fileTransfers: [] // All the file transfer data (should come from props or API)
        };
    },
    computed: {
        filteredTransfers() {
            return this.fileTransfers.filter((fileTransfer) => {
                const lowerQuery = this.searchQuery.toLowerCase();
                return (
                    fileTransfer.name.toLowerCase().includes(lowerQuery) ||
                    fileTransfer.client.toLowerCase().includes(lowerQuery)
                );
            });
        },
        paginatedTransfers() {
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            return this.filteredTransfers.slice(start, end);
        },
        totalPages() {
            return Math.ceil(this.filteredTransfers.length / this.perPage);
        }
    },
    methods: {
        previousPage() {
            if (this.currentPage > 1) {
                this.currentPage -= 1;
            }
        },
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage += 1;
            }
        }
    }
};

import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const flashMessage = computed(() => page.props.flash || '');
</script>