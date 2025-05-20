<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Pencil, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const colorPalettes = computed(() => page.props.colorPalettes);
const flashError = computed(() => page.props.errors?.status);
const search = ref('');

onMounted(() => {
  if (flashError.value) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: flashError.value,
    });
  }
});

const filteredPalettes = computed(() => {
  const query = search.value.toLowerCase();
  return colorPalettes.value.filter(p => p.name.toLowerCase().includes(query));
});

const editingId = ref<number | null>(null);
const editForm = ref({ name: '', primary: '', secondary: '', tertiary: '', quaternary: '', status: false });

const adding = ref(false);
const newForm = ref({ name: '', primary: '', secondary: '', tertiary: '', quaternary: '', status: false });

const startEditing = (palette: any) => {
  editingId.value = palette.id;
  editForm.value = {
    name: palette.name,
    primary: palette.primary,
    secondary: palette.secondary,
    tertiary: palette.tertiary,
    quaternary: palette.quaternary,
    status: !!palette.status,
  };
};

const cancelEditing = () => {
  editingId.value = null;
};

const saveEdit = (id: number) => {
  router.put(route('color-palettes-update', id), editForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      editingId.value = null;
      Swal.fire('Updated!', 'Color palette updated.', 'success');
    },
    onError: (errors) => {
      Swal.fire('Error!', errors?.status ?? 'Failed to update.', 'error');
    },
  });
};

const startAdding = () => {
  adding.value = true;
  newForm.value = { name: '', primary: '', secondary: '', tertiary: '', quaternary: '', status: false };
};

const cancelAdding = () => {
  adding.value = false;
};

const saveNew = () => {
  router.post(route('color-palettes-store'), newForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      adding.value = false;
      Swal.fire('Added!', 'Color palette added successfully.', 'success');
    },
    onError: () => {
      Swal.fire('Error!', 'Failed to create.', 'error');
    },
  });
};

const deletePalette = async (id: number) => {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: 'This will permanently delete the color palette.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
  });

  if (result.isConfirmed) {
    router.delete(route('color-palettes-delete', id), {
      preserveScroll: true,
      onSuccess: () => {
        Swal.fire('Deleted!', 'Color palette deleted.', 'success');
      },
      onError: () => {
        Swal.fire('Error!', 'Could not delete.', 'error');
      },
    });
  }
};

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text).then(() => {
    Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1000, icon: 'success', title: 'Copied!' });
  });
};
</script>

<template>

  <Head title="Color Palettes" />
  <AppLayout :breadcrumbs="[{ title: 'Color Palettes', href: '/color-palettes' }]">
    <div class="p-6">
      <div class="mb-6 flex items-center justify-between">
        <input v-model="search" placeholder="Search..."
          class="w-full max-w-xs rounded border px-4 py-2 dark:bg-gray-700 dark:text-white" />
        <button @click="startAdding" class="ml-4 rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
          <CirclePlus class="mr-1 inline h-5 w-5" /> Add
        </button>
      </div>

      <table class="w-full rounded bg-white shadow dark:bg-gray-800">
        <thead class="bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
          <tr class="bg-gray-50 dark:bg-gray-900 uppercase">
            <th class="px-4 py-3 text-left">#</th>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-center">Primary</th>
            <th class="px-4 py-3 text-center">Secondary</th>
            <th class="px-4 py-3 text-center">Tertiary</th>
            <th class="px-4 py-3 text-center">Quaternary</th>
            <th class="px-4 py-3 text-center">Status</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="adding" class="bg-white dark:bg-gray-800 rounded shadow">
            <td class="px-4 py-3">#</td>
            <td class="px-4 py-3"><input v-model="newForm.name"
                class="w-full rounded border px-2 py-1 text-sm dark:bg-gray-700 dark:text-white" /></td>
            <td class="px-4 py-3 text-center"><input v-model="newForm.primary"
                class="w-20 rounded border px-2 py-1 text-sm dark:bg-gray-700 dark:text-white" /></td>
            <td class="px-4 py-3 text-center"><input v-model="newForm.secondary"
                class="w-20 rounded border px-2 py-1 text-sm dark:bg-gray-700 dark:text-white" /></td>
            <td class="px-4 py-3 text-center"><input v-model="newForm.tertiary"
                class="w-20 rounded border px-2 py-1 text-sm dark:bg-gray-700 dark:text-white" /></td>
            <td class="px-4 py-3 text-center"><input v-model="newForm.quaternary"
                class="w-20 rounded border px-2 py-1 text-sm dark:bg-gray-700 dark:text-white" /></td>
            <td class="px-4 py-3 text-center">
              <label class="inline-flex cursor-pointer items-center">
                <input type="checkbox" v-model="newForm.status" class="sr-only peer" />
                <div class="peer h-5 w-10 rounded-full bg-gray-300 peer-checked:bg-green-500 transition"></div>
              </label>
            </td>
            <td class="px-4 py-3 text-center space-x-2">
              <button @click="saveNew" class="text-green-600 hover:underline text-sm">Save</button>
              <button @click="cancelAdding" class="text-gray-500 hover:underline text-sm">Cancel</button>
            </td>
          </tr>

          <tr v-for="(palette, index) in filteredPalettes" :key="palette.id"
            class="bg-white dark:bg-gray-800 rounded shadow">
            <td class="px-4 py-3">{{ index + 1 }}</td>
            <td class="px-4 py-3">
              <template v-if="editingId === palette.id">
                <input v-model="editForm.name"
                  class="w-full rounded border px-2 py-1 text-sm dark:bg-gray-700 dark:text-white" />
              </template>
              <template v-else>{{ palette.name }}</template>
            </td>

            <td class="px-4 py-3 text-center">
              <template v-if="editingId === palette.id">
                <input v-model="editForm.primary"
                  class="w-20 rounded border px-2 py-1 text-sm dark:bg-gray-700 dark:text-white" />
              </template>
              <template v-else>
                <div :style="{ backgroundColor: palette.primary }"
                  class="h-6 w-10 mx-auto cursor-pointer border border-gray-400 rounded"
                  @click="copyToClipboard(palette.primary)"></div>
              </template>
            </td>

            <td class="px-4 py-3 text-center">
              <template v-if="editingId === palette.id">
                <input v-model="editForm.secondary"
                  class="w-20 rounded border px-2 py-1 text-sm dark:bg-gray-700 dark:text-white" />
              </template>
              <template v-else>
                <div :style="{ backgroundColor: palette.secondary }"
                  class="h-6 w-10 mx-auto cursor-pointer border border-gray-400 rounded"
                  @click="copyToClipboard(palette.secondary)"></div>
              </template>
            </td>

            <td class="px-4 py-3 text-center">
              <template v-if="editingId === palette.id">
                <input v-model="editForm.tertiary"
                  class="w-20 rounded border px-2 py-1 text-sm dark:bg-gray-700 dark:text-white" />
              </template>
              <template v-else>
                <div :style="{ backgroundColor: palette.tertiary }"
                  class="h-6 w-10 mx-auto cursor-pointer border border-gray-400 rounded"
                  @click="copyToClipboard(palette.tertiary)"></div>
              </template>
            </td>

            <td class="px-4 py-3 text-center">
              <template v-if="editingId === palette.id">
                <input v-model="editForm.quaternary"
                  class="w-20 rounded border px-2 py-1 text-sm dark:bg-gray-700 dark:text-white" />
              </template>
              <template v-else>
                <div :style="{ backgroundColor: palette.quaternary }"
                  class="h-6 w-10 mx-auto cursor-pointer border border-gray-400 rounded"
                  @click="copyToClipboard(palette.quaternary)"></div>
              </template>
            </td>

            <td class="px-4 py-3 text-center">
              <template v-if="editingId === palette.id">
                <label class="inline-flex cursor-pointer items-center">
                  <input type="checkbox" v-model="editForm.status" class="sr-only peer" />
                  <div class="peer h-5 w-10 rounded-full bg-gray-300 peer-checked:bg-green-500 transition"></div>
                </label>
              </template>
              <template v-else>
                <span :class="palette.status ? 'text-green-600' : 'text-red-600'">
                  {{ palette.status ? 'Active' : 'Inactive' }}
                </span>
              </template>
            </td>

            <td class="px-4 py-3 text-center">
              <template v-if="editingId === palette.id">
                <div class="flex justify-center gap-2">
                  <button @click="saveEdit(palette.id)" class="text-blue-600 hover:underline text-sm">Update</button>
                  <button @click="cancelEditing" class="text-red-500 hover:underline text-sm">Cancel</button>
                </div>
              </template>
              <template v-else>
                <div class="flex justify-center space-x-2">
                  <button @click="startEditing(palette)" class="text-blue-600 hover:text-blue-800">
                    <Pencil class="inline h-5 w-5" />
                  </button>
                  <button @click="deletePalette(palette.id)" class="text-red-600 hover:text-red-800">
                    <Trash2 class="inline h-5 w-5" />
                  </button>
                </div>
              </template>
            </td>
          </tr>

          <tr v-if="filteredPalettes.length === 0 && !adding">
            <td colspan="8" class="px-4 py-4 text-center text-gray-500">No color palettes found.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>
