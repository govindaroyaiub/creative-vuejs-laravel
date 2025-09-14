<template>

  <Head title="Color Palettes" />
  <AppLayout :breadcrumbs="[{ title: 'Color Palettes' }]">
    <div class="flex justify-end items-center mb-6 px-4 mt-4">
      <button @click="openAddModal" class="bg-indigo-600 text-white px-4 py-2 rounded">Add New</button>
    </div>
    <div class="overflow-x-auto px-4">
      <table class="min-w-full table-auto border bg-white dark:bg-black text-black dark:text-white text-center">
        <thead>
          <tr>
            <th class="px-2 py-2">Name</th>
            <th class="px-2 py-2">Colors</th>
            <th class="px-2 py-2">FeedbackTabs</th>
            <th class="px-2 py-2">RightSideTabs</th>
            <th class="px-2 py-2">Status</th>
            <th class="px-2 py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="palette in colorPalettes" :key="palette.id"
            class="border-t hover:bg-gray-100 dark:hover:bg-gray-800">
            <td class="px-2 py-2">{{ palette.name }}</td>
            <td class="px-2 py-2" style="width: 270px;">
              <div class="grid grid-cols-2 gap-2">
                <span v-for="key in colorKeys" :key="key" class="flex items-center justify-between">
                  <span class="text-xs font-semibold">{{ key.charAt(0).toUpperCase() + key.slice(1) }}:</span>
                  <span :style="{ background: palette[key], border: '1px solid #ccc', cursor: 'pointer' }"
                    class="w-6 h-6 rounded" @click="copyColor(palette[key], key)" title="Click to copy"></span>
                </span>
              </div>
            </td>
            <td class="px-2 py-2">
              <img :src="`/${palette.feedbackTab_inactive_image}`" alt="Inactive" class="h-10 mx-auto mb-1 mt-1" />
              <hr>
              <img :src="`/${palette.feedbackTab_active_image}`" alt="Active" class="h-10 mx-auto mb-1 mt-1" />
            </td>
            <td class="px-2 py-2">
              <img :src="`/${palette.rightSideTab_inactive_image}`" alt="Inactive" class="h-10 mx-auto mt-1 mb-1" />
              <hr>
              <img :src="`/${palette.rightSideTab_active_image}`" alt="Active" class="h-10 mx-auto mt-1 mb-1" />
            </td>
            <td class="px-2 py-2">
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" :checked="palette.status" @change="toggleStatus(palette)" class="sr-only peer" />
                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 transition"></div>
                <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition peer-checked:translate-x-5">
                </div>
              </label>
            </td>
            <td class="px-2 py-2">
              <button @click="openEditModal(palette)" class="text-indigo-600 underline mr-2">Edit</button>
              <button @click="deletePalette(palette.id)" class="text-red-600 underline">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal (Add/Edit) -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-900 p-6 rounded shadow-lg w-full max-w-4xl">
        <h2 class="text-xl font-bold mb-4">{{ modalMode === 'edit' ? 'Edit Palette' : 'Add Palette' }}</h2>
        <form @submit.prevent="submit">
          <div class="flex flex-row gap-8">
            <!-- Left: Colors -->
            <div class="flex-1">
              <div class="mb-4">
                <label class="block mb-1 font-semibold">Name</label>
                <input v-model="form.name" type="text" placeholder="Palette Name"
                  class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white" required />
              </div>
              <div class="mb-4 grid grid-cols-2 gap-4">
                <div v-for="key in colorKeys" :key="key">
                  <label class="block mb-1 font-semibold">{{ key.charAt(0).toUpperCase() + key.slice(1) }}</label>
                  <input type="color" v-model="form[key]" class="w-10 h-10 border rounded" />
                  <span class="ml-2 text-xs">{{ form[key] }}</span>
                </div>
              </div>
            </div>
            <!-- Right: Images -->
            <div class="flex-1">
              <div class="mb-4 mt-5">
                <label class="block mb-1 font-semibold">FeedbackTab Inactive Image</label>
                <img v-if="form.feedbackTab_inactive_image_preview" :src="form.feedbackTab_inactive_image_preview"
                  class="h-10 mt-2" />
                <input type="file" @change="onFileChange('feedbackTab_inactive_image', $event)" />
              </div>
              <div class="mb-4">
                <label class="block mb-1 font-semibold">FeedbackTab Active Image</label>
                <img v-if="form.feedbackTab_active_image_preview" :src="form.feedbackTab_active_image_preview"
                  class="h-10 mt-2" />
                <input type="file" @change="onFileChange('feedbackTab_active_image', $event)" />
              </div>
              <div class="mb-4">
                <label class="block mb-1 font-semibold">RightSideTab Inactive Image</label>
                <img v-if="form.rightSideTab_inactive_image_preview" :src="form.rightSideTab_inactive_image_preview"
                  class="h-10 mt-2" />
                <input type="file" @change="onFileChange('rightSideTab_inactive_image', $event)" />
              </div>
              <div class="mb-4">
                <label class="block mb-1 font-semibold">RightSideTab Active Image</label>
                <img v-if="form.rightSideTab_active_image_preview" :src="form.rightSideTab_active_image_preview"
                  class="h-10 mt-2" />
                <input type="file" @change="onFileChange('rightSideTab_active_image', $event)" />
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-6">
            <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">{{ modalMode === 'edit' ? 'Update'
              : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
  colorPalettes: Array
})

const colorKeys = [
  'primary', 'secondary', 'tertiary', 'quaternary', 'quinary', 'senary', 'septenary'
]

const showModal = ref(false)
const modalMode = ref('add') // 'add' or 'edit'
const selectedPalette = ref(null)

const form = ref({
  name: '',
  primary: '#000000',
  secondary: '#000000',
  tertiary: '#000000',
  quaternary: '#000000',
  quinary: '#000000',
  senary: '#000000',
  septenary: '#000000',
  feedbackTab_inactive_image: null,
  feedbackTab_active_image: null,
  rightSideTab_inactive_image: null,
  rightSideTab_active_image: null,
  feedbackTab_inactive_image_preview: null,
  feedbackTab_active_image_preview: null,
  rightSideTab_inactive_image_preview: null,
  rightSideTab_active_image_preview: null,
})

function openEditModal(palette) {
  modalMode.value = 'edit'
  selectedPalette.value = palette
  form.value.name = palette.name ?? ''
  form.value.primary = palette.primary ?? '#000000'
  form.value.secondary = palette.secondary ?? '#000000'
  form.value.tertiary = palette.tertiary ?? '#000000'
  form.value.quaternary = palette.quaternary ?? '#000000'
  form.value.quinary = palette.quinary ?? '#000000'
  form.value.senary = palette.senary ?? '#000000'
  form.value.septenary = palette.septenary ?? '#000000'
  form.value.feedbackTab_inactive_image_preview = palette.feedbackTab_inactive_image
    ? `/${palette.feedbackTab_inactive_image}` : null
  form.value.feedbackTab_active_image_preview = palette.feedbackTab_active_image
    ? `/${palette.feedbackTab_active_image}` : null
  form.value.rightSideTab_inactive_image_preview = palette.rightSideTab_inactive_image
    ? `/${palette.rightSideTab_inactive_image}` : null
  form.value.rightSideTab_active_image_preview = palette.rightSideTab_active_image
    ? `/${palette.rightSideTab_active_image}` : null
  form.value.feedbackTab_inactive_image = null
  form.value.feedbackTab_active_image = null
  form.value.rightSideTab_inactive_image = null
  form.value.rightSideTab_active_image = null
  showModal.value = true
}

function openAddModal() {
  modalMode.value = 'add'
  selectedPalette.value = null
  Object.assign(form.value, {
    name: '',
    primary: '#000000',
    secondary: '#000000',
    tertiary: '#000000',
    quaternary: '#000000',
    quinary: '#000000',
    senary: '#000000',
    septenary: '#000000',
    feedbackTab_inactive_image: null,
    feedbackTab_active_image: null,
    rightSideTab_inactive_image: null,
    rightSideTab_active_image: null,
    feedbackTab_inactive_image_preview: null,
    feedbackTab_active_image_preview: null,
    rightSideTab_inactive_image_preview: null,
    rightSideTab_active_image_preview: null,
  })
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

function copyColor(color, key) {
  navigator.clipboard.writeText(color).then(() => {
    Swal.fire('Copied!', `${key.charAt(0).toUpperCase() + key.slice(1)} color (${color}) copied to clipboard.`, 'success')
  })
}

function submit() {
  const data = new FormData()
  Object.keys(form.value).forEach(k => {
    if (form.value[k] !== null && form.value[k] !== undefined && !k.endsWith('_preview')) {
      data.append(k, form.value[k])
    }
  })
  if (modalMode.value === 'edit' && selectedPalette.value) {
    // Use POST with _method=PUT for file uploads
    data.append('_method', 'PUT')
    router.post(`/color-palettes-update/${selectedPalette.value.id}`, data, {
      onSuccess: () => {
        showModal.value = false
        Swal.fire('Updated!', 'Palette updated successfully.', 'success')
      }
    })
  } else {
    router.post('/color-palettes/store', data, {
      onSuccess: () => {
        showModal.value = false
        Swal.fire('Added!', 'Palette added successfully.', 'success')
      }
    })
  }
}

function deletePalette(id) {
  Swal.fire({
    title: 'Are you sure?',
    text: 'This will delete the palette.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!'
  }).then(result => {
    if (result.isConfirmed) {
      router.delete(`/color-palettes-delete/${id}`, {
        onSuccess: () => Swal.fire('Deleted!', 'Palette deleted.', 'success')
      })
    }
  })
}

function toggleStatus(palette) {
  const newStatus = palette.status ? 0 : 1;
  router.put(
    `/color-palettes-toggle-status/${palette.id}`,
    { status: newStatus },
    {
      onSuccess: () => {
        palette.status = newStatus;
      }
    }
  );
}

function onFileChange(field, e) {
  const file = e.target.files[0]
  if (file) {
    form.value[field] = file
    form.value[field + '_preview'] = URL.createObjectURL(file)
  }
}
</script>