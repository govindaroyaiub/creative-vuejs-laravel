<template>

    <Head title="Bulk Customization" />
    <AppLayout :breadcrumbs="[{ title: 'Previews', href: '/previews' }, { title: 'Bulk Customization' }]">
        <div class="max-w-8xl py-4 px-4">
            <div v-if="preview">
                <div v-for="(category, catIdx) in preview.categories" :key="category.id" class="mb-8">
                    <details v-bind="category.is_active == 1 ? { open: true } : {}" class="mb-4 border rounded shadow">
                        <summary
                            class="px-4 py-2 font-semibold text-lg bg-gray-100 dark:bg-gray-800 cursor-pointer flex items-center justify-between">
                            <!-- Left side: icon, name, type -->
                            <span class="flex items-center gap-2">
                                <svg width="16" height="16" class="mr-1">
                                    <circle cx="8" cy="8" r="7" :fill="category.is_active == 1 ? '#22c55e' : 'red'" />
                                </svg>
                                <span>Category:</span>
                                <input v-model="category.name"
                                    class="border rounded px-2 py-1 font-semibold text-lg bg-white-100 dark:bg-gray-800"
                                    placeholder="Category Name" style="min-width:180px;" />
                                <span class="text-xs text-gray-500 ml-2">({{ category.type }})</span>
                            </span>
                            <!-- Right side: delete buttons -->
                            <span class="flex items-center gap-2">
                                <button v-if="!isDbId(category.id)" @click.stop="removeCategory(catIdx)"
                                    class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs">Delete</button>
                                <button v-else @click.stop="deleteCategory(category, catIdx)"
                                    class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs">Delete</button>
                            </span>
                        </summary>
                        <div class="p-4">
                            <div v-for="(feedback, fbIdx) in category.feedbacks" :key="feedback.id" class="mb-6">
                                <details v-bind="feedback.is_active == 1 ? { open: true } : {}"
                                    class="mb-2 border rounded">
                                    <summary
                                        class="px-3 py-1 font-medium bg-gray-50 dark:bg-gray-900 cursor-pointer flex items-center justify-between">
                                        <!-- Left side: icon, label, name, description -->
                                        <span class="flex items-center gap-2">
                                            <svg width="16" height="16" class="mr-1">
                                                <circle cx="8" cy="8" r="7"
                                                    :fill="feedback.is_active == 1 ? '#22c55e' : 'red'" />
                                            </svg>
                                            <span>Feedback:</span>
                                            <input :value="feedback.name" @input="onFeedbackNameInput($event, feedback)"
                                                class="border rounded px-2 py-1 font-medium" placeholder="Feedback Name"
                                                style="min-width:120px;" />
                                            <textarea v-model="feedback.description"
                                                class="border rounded px-2 py-1 text-xs text-gray-400"
                                                placeholder="Feedback Description"
                                                style="min-width:120px;min-height:28px;" />
                                        </span>
                                        <!-- Right side: delete buttons -->
                                        <span class="flex items-center gap-2">
                                            <button v-if="!isDbId(feedback.id)"
                                                @click.stop="removeFeedback(category, fbIdx)"
                                                class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs">Delete</button>
                                            <button v-else @click.stop="deleteFeedback(feedback, category, fbIdx)"
                                                class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs">Delete</button>
                                        </span>
                                    </summary>
                                    <div class="p-3">
                                        <div v-for="(set, setIdx) in feedback.feedback_sets" :key="set.id" class="mb-4">
                                            <details open class="mb-2 border rounded">
                                                <summary
                                                    class="px-2 py-1 font-medium bg-gray-50 dark:bg-gray-900 cursor-pointer flex items-center justify-between">
                                                    <span>
                                                        <input v-model="set.name" class="border rounded px-2 py-1"
                                                            placeholder="Set Name" style="min-width:100px;" />
                                                    </span>
                                                    <button v-if="!isDbId(set.id)"
                                                        @click.stop="removeSet(feedback, setIdx)"
                                                        class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs">Delete</button>
                                                    <button v-else @click.stop="deleteSet(set, feedback, setIdx)"
                                                        class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs">Delete</button>
                                                </summary>
                                                <div class="p-2">
                                                    <div v-for="(version, verIdx) in set.versions" :key="version.id"
                                                        class="mb-4">
                                                        <details open class="mb-2 border rounded">
                                                            <summary
                                                                class="px-2 py-1 font-medium bg-gray-50 dark:bg-gray-900 cursor-pointer flex items-center justify-between">
                                                                <span>
                                                                    <input v-model="version.name"
                                                                        class="border rounded px-2 py-1"
                                                                        placeholder="Version Name"
                                                                        style="min-width:100px;" />
                                                                </span>
                                                                <button v-if="!isDbId(version.id)"
                                                                    @click.stop="removeVersion(set, verIdx)"
                                                                    class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs">Delete</button>
                                                                <button v-else
                                                                    @click.stop="deleteVersion(version, set, verIdx)"
                                                                    class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs">Delete</button>
                                                            </summary>
                                                            <div class="p-2">
                                                                <h4 class="font-semibold mb-2">Assets</h4>
                                                                <div v-if="category.type === 'banner'">
                                                                    <div v-if="!version.banners || version.banners.length === 0"
                                                                        class="mt-4 flex gap-2 items-center">
                                                                        <FilePond :allowMultiple="true"
                                                                            :acceptedFileTypes="['application/zip']"
                                                                            :labelIdle="'Drag & Drop your zip files or <span class=\'filepond--label-action\'>Browse</span>'"
                                                                            :files="version._filepondFiles || []"
                                                                            @updatefiles="files => handleBannerDrop(files, version)"
                                                                            class="my-4 filepond-dropzone" />
                                                                    </div>
                                                                    <draggable v-model="version.banners" item-key="id"
                                                                        @end="updateBannerPositions(version)"
                                                                        class="space-y-2 mt-4">
                                                                        <template #item="{ element, index }">
                                                                            <div
                                                                                class="flex items-center bg-white dark:bg-gray-800 rounded shadow px-4 py-3">
                                                                                <div
                                                                                    class="cursor-move text-gray-400 hover:text-gray-600 mr-3">
                                                                                    <svg width="20" height="20"
                                                                                        fill="none" viewBox="0 0 20 20">
                                                                                        <rect x="3" y="6" width="14"
                                                                                            height="2" rx="1"
                                                                                            fill="currentColor" />
                                                                                        <rect x="3" y="9" width="14"
                                                                                            height="2" rx="1"
                                                                                            fill="currentColor" />
                                                                                        <rect x="3" y="12" width="14"
                                                                                            height="2" rx="1"
                                                                                            fill="currentColor" />
                                                                                    </svg>
                                                                                </div>
                                                                                <span
                                                                                    class="font-medium min-w-[120px]">{{
                                                                                        element.name }}</span>
                                                                                <div class="flex-1"></div>
                                                                                <div class="flex items-center gap-4">
                                                                                    <span
                                                                                        class="text-xs text-gray-500">Position:
                                                                                        {{ index + 1 }}</span>
                                                                                    <span
                                                                                        class="text-xs text-gray-500">Size:</span>
                                                                                    <v-select
                                                                                        :options="bannerSizeOptions"
                                                                                        label="label"
                                                                                        :reduce="size => size.id"
                                                                                        v-model="element.size_id"
                                                                                        placeholder="Select Banner Size"
                                                                                        :clearable="false"
                                                                                        class="w-40" />
                                                                                </div>
                                                                                <button v-if="isDbId(element.id)"
                                                                                    @click.stop="editBanner(element, version, index)"
                                                                                    class="text-blue-600 hover:text-blue-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                    Edit
                                                                                </button>
                                                                                <button v-if="isDbId(element.id)"
                                                                                    @click.stop="deleteBanner(element, version, index)"
                                                                                    class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                    Delete
                                                                                </button>
                                                                            </div>
                                                                        </template>
                                                                    </draggable>
                                                                </div>
                                                                <div v-else-if="category.type === 'video'">
                                                                    <div class="text-gray-400 italic">Video asset
                                                                        editing coming soon...</div>
                                                                </div>
                                                                <div v-else-if="category.type === 'social'">
                                                                    <div class="text-gray-400 italic">Social asset
                                                                        editing coming soon...</div>
                                                                </div>
                                                                <div v-else-if="category.type === 'gif'">
                                                                    <div class="text-gray-400 italic">Gif asset editing
                                                                        coming soon...</div>
                                                                </div>
                                                            </div>
                                                        </details>
                                                    </div>
                                                    <div class="mt-4 flex gap-2 items-center justify-start">
                                                        <button
                                                            @click="showAddVersion[set.id] = !showAddVersion[set.id]"
                                                            class="bg-indigo-300 text-white px-3 py-1 rounded text-sm">
                                                            + Add Version
                                                        </button>
                                                    </div>
                                                    <div v-if="showAddVersion[set.id]"
                                                        class="mt-2 flex gap-2 items-center justify-start">
                                                        <input v-model="newVersionName[set.id]"
                                                            placeholder="Version Name"
                                                            class="border rounded px-2 py-1" />
                                                        <button @click="addVersion(set)"
                                                            class="bg-green-600 text-white px-3 py-1 rounded">Add</button>
                                                    </div>
                                                </div>
                                            </details>
                                        </div>
                                        <div class="mt-4 flex gap-2 items-center justify-start">
                                            <button @click="showAddSet[feedback.id] = !showAddSet[feedback.id]"
                                                class="bg-indigo-400 text-white px-3 py-1 rounded text-sm">
                                                + Add Feedback Set
                                            </button>
                                        </div>
                                        <div v-if="showAddSet[feedback.id]"
                                            class="mt-2 flex gap-2 items-center justify-start">
                                            <input v-model="newSetName[feedback.id]" placeholder="Set Name"
                                                class="border rounded px-2 py-1" />
                                            <button @click="addSet(feedback)"
                                                class="bg-green-600 text-white px-3 py-1 rounded">Add</button>
                                        </div>
                                    </div>
                                </details>
                            </div>
                            <div class="mt-4 flex gap-2 items-center justify-start">
                                <button @click="showAddFeedback[category.id] = !showAddFeedback[category.id]"
                                    class="bg-indigo-500 text-white px-3 py-1 rounded text-sm">
                                    + Add Feedback
                                </button>
                            </div>
                            <div v-if="showAddFeedback[category.id]"
                                class="mt-2 flex-col gap-2 items-start justify-start">
                                <textarea v-model="newFeedbackDesc[category.id]" placeholder="Description"
                                    class="border rounded px-2 py-1 min-w-[200px] min-h-[60px]" />
                                <div class="flex gap-2">
                                    <input :value="newFeedbackName[category.id]"
                                        @input="onNewFeedbackNameInput($event, category)" placeholder="Feedback Name"
                                        class="border rounded px-2 py-1" />
                                    <button @click="addFeedback(category)"
                                        class="bg-green-600 text-white px-3 py-1 rounded">Add</button>
                                </div>
                            </div>
                        </div>
                    </details>
                </div>
                <div class="mt-4 flex gap-2 items-center justify-start">
                    <button @click="showAddCategory = !showAddCategory"
                        class="bg-indigo-600 text-white px-4 py-2 rounded">
                        + Add Category
                    </button>
                </div>
                <div v-if="showAddCategory" class="mt-2 flex gap-2 items-center justify-start">
                    <input v-model="newCategoryName" placeholder="Category Name" class="border rounded px-2 py-1" />
                    <select v-model="newCategoryType" class="border rounded px-2 py-1">
                        <option value="banner">Banner</option>
                        <option value="video">Video</option>
                        <option value="social">Social</option>
                        <option value="gif">Gif</option>
                    </select>
                    <button @click="addCategory" class="bg-green-600 text-white px-3 py-1 rounded">Add</button>
                </div>
                <div class="mt-8 text-right space-x-4">
                    <button type="button" @click="goToPreview"
                        class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 font-semibold">
                        View Preview
                    </button>
                    <button @click="saveAll"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-semibold">
                        Save Changes
                    </button>
                </div>
            </div>
            <div v-else>
                <div class="text-center text-gray-500 py-12">Loading preview data...</div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import AppLayout from '@/layouts/AppLayout.vue';
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import Swal from 'sweetalert2';

import vueFilePond from 'vue-filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import 'filepond/dist/filepond.min.css';
const FilePond = vueFilePond(FilePondPluginFileValidateType);

const page = usePage();
const preview = computed(() => page.props.preview);
const bannerSizes = computed(() => page.props.bannerSizes);

function goToPreview() {
    window.open(`/previews/show/${preview.value.slug}`, '_blank');
}

const showAddCategory = ref(false);
const newCategoryName = ref('');
const newCategoryType = ref('banner');

const showAddFeedback = reactive({});
const newFeedbackName = reactive({});
const newFeedbackDesc = reactive({});

const showAddSet = reactive({});
const newSetName = reactive({});

const showAddVersion = reactive({});
const newVersionName = reactive({});

function addCategory() {
    preview.value.categories.push({
        id: Date.now(),
        name: newCategoryName.value,
        type: newCategoryType.value,
        is_active: 1,
        feedbacks: [],
    });
    showAddCategory.value = false;
    newCategoryName.value = '';
    newCategoryType.value = 'banner';
}

const bannerSizeOptions = computed(() =>
    bannerSizes.value.map(size => ({
        id: size.id,
        label: `${size.width}x${size.height}`
    }))
);

function onFeedbackNameInput(event, feedback) {
    const val = event.target.value.toUpperCase();
    feedback.name = val;
    event.target.value = val;
}

function onNewFeedbackNameInput(event, category) {
    const val = event.target.value.toUpperCase();
    newFeedbackName[category.id] = val;
    event.target.value = val;
}

function isDbId(id) {
    return typeof id === 'number' && id < 1000000000000;
}

// Remove Category (new, unsaved)
function removeCategory(idx: number) {
    preview.value.categories.splice(idx, 1);
}
// Remove Category (saved, just UI for now)
function deleteCategory(category, idx) {
    // TODO: Implement API call later
    alert('This is a saved category. Implement API delete here.');
}

// Add Feedback
function addFeedback(category: any) {
    if (!category.feedbacks) category.feedbacks = [];
    category.feedbacks.push({
        id: Date.now(),
        name: newFeedbackName[category.id] || '',
        description: newFeedbackDesc[category.id] || '',
        is_active: 1,
        feedback_sets: [],
    });
    showAddFeedback[category.id] = false;
    newFeedbackName[category.id] = '';
    newFeedbackDesc[category.id] = '';
}
// Remove Feedback (new, unsaved)
function removeFeedback(category: any, idx: number) {
    category.feedbacks.splice(idx, 1);
}
// Remove Feedback (saved, just UI for now)
function deleteFeedback(feedback, category, idx) {
    // TODO: Implement API call later
    alert('This is a saved feedback. Implement API delete here.');
}

// Add Feedback Set
function addSet(feedback: any) {
    if (!feedback.feedback_sets) feedback.feedback_sets = [];
    feedback.feedback_sets.push({
        id: Date.now(),
        name: newSetName[feedback.id] || '',
        versions: [],
    });
    showAddSet[feedback.id] = false;
    newSetName[feedback.id] = '';
}
// Remove Set (new, unsaved)
function removeSet(feedback: any, idx: number) {
    feedback.feedback_sets.splice(idx, 1);
}
// Remove Set (saved, just UI for now)
function deleteSet(set, feedback, idx) {
    // TODO: Implement API call later
    alert('This is a saved set. Implement API delete here.');
}

// Add Version
function addVersion(set: any) {
    if (!set.versions) set.versions = [];
    set.versions.push({
        id: Date.now(),
        name: newVersionName[set.id] || '',
        banners: [],
        idFromDb: false,
    });
    showAddVersion[set.id] = false;
    newVersionName[set.id] = '';
}
// Remove Version (new, unsaved)
function removeVersion(set: any, idx: number) {
    set.versions.splice(idx, 1);
}
// Remove Version (saved, just UI for now)
function deleteVersion(version, set, idx) {
    // TODO: Implement API call later
    alert('This is a saved version. Implement API delete here.');
}

// Banner CRUD
function handleBannerDrop(files, version) {
    version._filepondFiles = files;
    version.banners = [];
    files.forEach((fileWrapper, idx) => {
        const file = fileWrapper.file;
        version.banners.push({
            id: Date.now() + idx,
            name: file.name,
            file,
            size_id: '',
            position: version.banners.length + 1,
        });
    });
    updateBannerPositions(version);
}
// Remove Banner (new, unsaved)
function removeBanner(version: any, index: number) {
    version.banners.splice(index, 1);
    updateBannerPositions(version);
}
function editBanner(banner, version, index) {
    // TODO: Implement API call later
    alert('This is a saved banner. Implement API edit here.');
}
// Remove Banner (saved, just UI for now)
function deleteBanner(banner, version, index) {
    // TODO: Implement API call later
    alert('This is a saved banner. Implement API delete here.');
}

function updateBannerPositions(version: any) {
    version.banners.forEach((banner: any, idx: number) => {
        banner.position = idx + 1;
    });
}

function saveAll() {
    const formData = new FormData();
    formData.append('preview_id', preview.value.id);

    preview.value.categories.forEach((category, catIdx) => {
        if (isDbId(category.id)) {
            formData.append(`categories[${catIdx}][id]`, category.id);
        }
        formData.append(`categories[${catIdx}][name]`, category.name);
        formData.append(`categories[${catIdx}][type]`, category.type);

        category.feedbacks.forEach((feedback, fbIdx) => {
            if (isDbId(feedback.id)) {
                formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][id]`, feedback.id);
            }
            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][name]`, feedback.name);
            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][description]`, feedback.description);

            feedback.feedback_sets.forEach((set, setIdx) => {
                if (isDbId(set.id)) {
                    formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][id]`, set.id);
                }
                formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][name]`, set.name);

                set.versions.forEach((version, verIdx) => {
                    if (isDbId(version.id)) {
                        formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][id]`, version.id);
                    }
                    formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][name]`, version.name);

                    version.banners.forEach((banner, bIdx) => {
                        if (isDbId(banner.id)) {
                            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][banners][${bIdx}][id]`, banner.id);
                        }
                        formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][banners][${bIdx}][name]`, banner.name);
                        formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][banners][${bIdx}][size_id]`, banner.size_id);
                        formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][banners][${bIdx}][position]`, banner.position);
                        if (banner.file) {
                            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][banners][${bIdx}][file]`, banner.file);
                        }
                    });
                });
            });
        });
    });

    router.post(route('previews.bulkEdit', preview.value.id), formData, {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Saved successfully!',
                showConfirmButton: false,
                timer: 1800
            });
        },
        onError: (err) => {
            console.error('Failed', err);
            if (err && err.response && err.response.data && err.response.data.errors) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: `<pre style="text-align:left;">${JSON.stringify(err.response.data.errors, null, 2)}</pre>`,
                    width: 600
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed',
                    text: 'See console for details.'
                });
            }
        }
    });
}
</script>

<style scoped>
body {
    background: #f7f8fa;
}

details[open]>summary {
    border-bottom: 1px solid #e5e7eb;
}

textarea {
    min-width: 600px;
}

.filepond-dropzone {
    width: 100%;
}

.filepond--credits {
    display: none !important;
}
</style>