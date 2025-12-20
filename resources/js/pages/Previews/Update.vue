<template>

    <Head title="Batch Update" />
    <AppLayout :breadcrumbs="[{ title: 'Previews', href: '/previews' }, { title: 'Batch Update' }]">
        <div class="max-w-8xl py-4 px-4">
            <div v-if="preview">
                <div v-for="(category, catIdx) in preview.categories" :key="category.id" class="mb-4">
                    <details v-bind="category.is_active == 1 ? { open: true } : {}" class="mb-4 border rounded shadow">
                        <summary
                            class="px-4 py-2 font-semibold text-lg bg-gray-100 dark:bg-neutral-900 cursor-pointer flex items-center justify-between">
                            <!-- Left side: icon, name, type -->
                            <span class="flex items-center gap-2">
                                <svg width="16" height="16" class="mr-1">
                                    <circle cx="8" cy="8" r="7"
                                        :fill="!isDbId(category.id) ? '#facc15' : (category.is_active == 1 ? '#22c55e' : 'red')" />
                                </svg>
                                <span>Category:</span>
                                <input v-model="category.name"
                                    class="border rounded px-2 py-1 font-semibold text-lg bg-white-100 dark:bg-neutral-900 dark:text-white dark:border-neutral-700"
                                    placeholder="Category Name" style="min-width:180px;" />
                                <span class="text-xs text-red-500 ml-2 uppercase">({{ category.type }})</span>
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
                            <div v-for="(feedback, fbIdx) in category.feedbacks" :key="feedback.id" class="mb-4">
                                <details v-bind="feedback.is_active == 1 ? { open: true } : {}"
                                    class="mb-2 border rounded">
                                    <summary
                                        class="px-3 py-1 font-medium bg-gray-50 dark:bg-neutral-800 cursor-pointer flex items-center justify-between">
                                        <!-- Left side: icon, label, name, description -->
                                        <span class="flex items-center gap-2">
                                            <svg width="16" height="16" class="mr-1">
                                                <circle cx="8" cy="8" r="7"
                                                    :fill="!isDbId(feedback.id) ? '#facc15' : (feedback.is_active == 1 ? '#22c55e' : 'red')" />
                                            </svg>
                                            <span>Feedback:</span>
                                            <input :value="feedback.name" @input="onFeedbackNameInput($event, feedback)"
                                                class="border rounded px-2 py-1 font-medium dark:bg-neutral-900 dark:text-white dark:border-neutral-700"
                                                placeholder="Feedback Name" style="min-width:120px;" />
                                        </span>
                                        <!-- Right side: delete buttons -->
                                        <span class="flex items-center gap-2">
                                            <!-- Detailed approval status -->
                                            <button v-if="isDbId(feedback.id) && feedback.is_approved"
                                                @click.stop="disapproveFeedback(feedback, category, fbIdx)"
                                                :disabled="feedback.isDisapproving"
                                                class="flex items-center bg-green-50 border hover:bg-green-200 border-green-200 text-green-700 px-2 py-1 rounded text-xs font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span v-if="feedback.isDisapproving" class="flex items-center">
                                                    <svg class="animate-spin w-3 h-3 mr-1" fill="none"
                                                        viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                        </path>
                                                    </svg>
                                                    Disapproving...
                                                </span>
                                                <span v-else class="flex items-center">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    Feedback Approved
                                                </span>
                                            </button>

                                            <!-- Approve button with loading state -->
                                            <button v-else-if="isDbId(feedback.id) && !feedback.is_approved"
                                                @click.stop="approveFeedback(feedback, category, fbIdx)"
                                                :disabled="feedback.isApproving"
                                                class="bg-blue-100 text-blue-700 hover:bg-blue-200 hover:text-blue-900 px-2 py-1 rounded text-xs font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span v-if="feedback.isApproving" class="flex items-center">
                                                    <svg class="animate-spin w-3 h-3 mr-1" fill="none"
                                                        viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                        </path>
                                                    </svg>
                                                    Approving...
                                                </span>
                                                <span v-else class="flex items-center">
                                                    Approve
                                                </span>
                                            </button>

                                            <!-- Delete Buttons -->
                                            <button v-if="!isDbId(feedback.id)"
                                                @click.stop="removeFeedback(category, fbIdx)"
                                                class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs">
                                                Delete
                                            </button>
                                            <button v-else @click.stop="deleteFeedback(feedback, category, fbIdx)"
                                                class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs">
                                                Delete
                                            </button>
                                        </span>
                                    </summary>
                                    <div class="p-3">
                                        <textarea v-model="feedback.description"
                                            class="border rounded px-2 py-1 mb-2 w-1/2 dark:bg-neutral-900 dark:text-white dark:border-neutral-700"
                                            placeholder="Feedback Description" />
                                        <div v-for="(set, setIdx) in feedback.feedback_sets" :key="set.id" class="mb-4">
                                            <details open class="mb-2 border rounded">
                                                <summary
                                                    class="px-2 py-1 mb-2 font-medium bg-gray-50 dark:bg-neutral-900 dark:text-white dark:border-neutral-700 cursor-pointer flex items-center justify-between">
                                                    <span>
                                                        <span class="px-2">Set:</span>
                                                        <input v-model="set.name"
                                                            class="border rounded px-2 py-1 dark:bg-neutral-900 dark:text-white dark:border-neutral-700"
                                                            placeholder="Set Name (Optional)"
                                                            style="min-width:100px;" />
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
                                                                class="px-2 py-1 font-medium bg-gray-50 dark:bg-neutral-800 cursor-pointer flex items-center justify-between">
                                                                <span>
                                                                    <span class="px-2">Version:</span>
                                                                    <input v-model="version.name"
                                                                        class="border rounded px-2 py-1 dark:bg-neutral-900 dark:text-white dark:border-neutral-700"
                                                                        placeholder="Version Name (Optional)"
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
                                                                                class="flex items-center bg-white dark:bg-neutral-900 rounded shadow px-4 py-3">
                                                                                <span
                                                                                    class="text-xs text-gray-500 mr-2">{{
                                                                                        index + 1 }}</span>
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
                                                                                        class="text-xs text-gray-500">Size:</span>
                                                                                    <v-select
                                                                                        :options="bannerSizeOptions"
                                                                                        label="label"
                                                                                        :reduce="size => size.id"
                                                                                        v-model="element.size_id"
                                                                                        placeholder="Select Banner Size"
                                                                                        :clearable="false"
                                                                                        class="w-40 dark:bg-white dark:text-gray-800 dark:border-neutral-700" />
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
                                                                                <button v-if="!isDbId(element.id)"
                                                                                    @click.stop="removeBanner(version, index)"
                                                                                    class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                    Delete
                                                                                </button>

                                                                            </div>
                                                                        </template>
                                                                    </draggable>
                                                                    <!-- Banner Edit Modal -->
                                                                    <template v-if="showBannerEdit">
                                                                        <div
                                                                            class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
                                                                            <div
                                                                                class="bg-white dark:bg-neutral-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                                                                                <h2 class="text-lg font-bold mb-4">
                                                                                    Edit Banner</h2>
                                                                                <div class="mb-4">
                                                                                    <label
                                                                                        class="block text-sm font-medium mb-1">Banner
                                                                                        File</label>
                                                                                    <FilePond :allowMultiple="false"
                                                                                        :acceptedFileTypes="['application/zip']"
                                                                                        :files="bannerEditFile"
                                                                                        @updatefiles="files => bannerEditFile = files"
                                                                                        class="filepond-dropzone" />
                                                                                </div>
                                                                                <div class="mb-4">
                                                                                    <label
                                                                                        class="block text-sm font-medium mb-1">Banner
                                                                                        Size</label>
                                                                                    <v-select
                                                                                        :options="bannerSizeOptions"
                                                                                        label="label"
                                                                                        :reduce="size => size.id"
                                                                                        v-model="bannerEditSizeId"
                                                                                        placeholder="Select Banner Size"
                                                                                        :clearable="false"
                                                                                        class="w-full dark:bg-neutral-900 dark:text-white dark:border-neutral-700" />
                                                                                </div>
                                                                                <div class="flex gap-2 mt-6 w-full">
                                                                                    <button @click="closeBannerEdit"
                                                                                        class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded font-semibold">Cancel</button>
                                                                                    <button @click="submitBannerEdit"
                                                                                        class="w-full bg-blue-600 text-white px-4 py-2 rounded font-semibold">Update</button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                                <div v-if="category.type === 'video'">
                                                                    <draggable
                                                                        v-if="version.videos && version.videos.length"
                                                                        v-model="version.videos" item-key="id"
                                                                        @end="updateVideoPositions(version)"
                                                                        class="space-y-2 mt-4">
                                                                        <template #item="{ element, index }">
                                                                            <div
                                                                                class="bg-white dark:bg-neutral-900 rounded shadow px-4 py-3 mb-2">
                                                                                <div
                                                                                    class="flex items-center gap-2 mb-2 w-full">
                                                                                    <span
                                                                                        class="text-xs text-gray-500 mr-1">{{
                                                                                            index + 1 }}</span>
                                                                                    <div class="w-full">
                                                                                        <label
                                                                                            class="block text-xs mb-1">Video
                                                                                            File</label>
                                                                                        <template v-if="element.path">
                                                                                            <video
                                                                                                :src="`/${element.path}`"
                                                                                                controls
                                                                                                class="w-40 h-24 rounded" />
                                                                                        </template>
                                                                                        <template v-else>
                                                                                            <FilePond
                                                                                                :allowMultiple="false"
                                                                                                :acceptedFileTypes="['video/mp4', 'video/webm']"
                                                                                                :files="element._filepondVideo || []"
                                                                                                @updatefiles="files => handleVideoFile(files, element)"
                                                                                                class="filepond-dropzone" />
                                                                                        </template>
                                                                                    </div>
                                                                                    <div class="w-full">
                                                                                        <label
                                                                                            class="block text-xs mb-1">Companion
                                                                                            Banner</label>
                                                                                        <template
                                                                                            v-if="!isDbId(element.id)">
                                                                                            <!-- New row: show FilePond for companion banner -->
                                                                                            <FilePond
                                                                                                :allowMultiple="false"
                                                                                                :acceptedFileTypes="['image/png', 'image/jpeg', 'image/gif']"
                                                                                                :files="element._filepondBanner || []"
                                                                                                @updatefiles="files => handleCompanionBanner(files, element)"
                                                                                                class="filepond-dropzone" />
                                                                                        </template>
                                                                                        <template v-else>
                                                                                            <!-- Saved row: show image or fallback -->
                                                                                            <template
                                                                                                v-if="element.companion_banner_path">
                                                                                                <img :src="`/${element.companion_banner_path}`"
                                                                                                    alt="Companion Banner"
                                                                                                    class="w-24 h-24 object-cover rounded" />
                                                                                            </template>
                                                                                            <button
                                                                                                v-if="element.companion_banner_path"
                                                                                                @click.stop="deleteCompanionBanner(element, version, index)"
                                                                                                class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                                Delete
                                                                                            </button>
                                                                                            <template
                                                                                                v-else-if="!element.companion_banner_path">
                                                                                                <div
                                                                                                    class="text-gray-400 text-xs py-8 text-center border rounded bg-gray-50">
                                                                                                    Nothing To See Here
                                                                                                </div>
                                                                                            </template>
                                                                                        </template>
                                                                                    </div>
                                                                                    <button v-if="isDbId(element.id)"
                                                                                        @click.stop="editVideo(element, version, index)"
                                                                                        class="text-blue-600 hover:text-blue-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                        Edit
                                                                                    </button>
                                                                                    <button v-if="isDbId(element.id)"
                                                                                        @click.stop="deleteVideo(element, version, index)"
                                                                                        class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                        Delete
                                                                                    </button>
                                                                                    <button v-if="!isDbId(element.id)"
                                                                                        @click.stop="removeVideo(version, index)"
                                                                                        class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                        Delete
                                                                                    </button>
                                                                                </div>
                                                                                <div
                                                                                    class="flex items-center gap-4 w-full">
                                                                                    <div class="w-full">
                                                                                        <label
                                                                                            class="block text-xs mb-1">Video
                                                                                            Size</label>
                                                                                        <v-select
                                                                                            :options="videoSizeOptions"
                                                                                            label="label"
                                                                                            :reduce="size => size.id"
                                                                                            v-model="element.size_id"
                                                                                            placeholder="Select Video Size"
                                                                                            :clearable="false"
                                                                                            class="w-fill dark:bg-white dark:text-gray-800 dark:border-neutral-700" />
                                                                                    </div>
                                                                                    <div class="w-full">
                                                                                        <label
                                                                                            class="block text-xs mb-1">Aspect
                                                                                            Ratio</label>
                                                                                        <input
                                                                                            v-model="element.aspect_ratio"
                                                                                            placeholder="Aspect Ratio"
                                                                                            class="border rounded px-2 py-1 text-xs w-full dark:bg-neutral-900 dark:text-white dark:border-neutral-700" />
                                                                                    </div>
                                                                                    <div class="w-full">
                                                                                        <label
                                                                                            class="block text-xs mb-1">Codec</label>
                                                                                        <input v-model="element.codec"
                                                                                            placeholder="Codec"
                                                                                            class="border rounded px-2 py-1 text-xs w-full dark:bg-neutral-900 dark:text-white dark:border-neutral-700" />
                                                                                    </div>
                                                                                    <div class="w-full">
                                                                                        <label
                                                                                            class="block text-xs mb-1">FPS</label>
                                                                                        <input v-model="element.fps"
                                                                                            placeholder="FPS"
                                                                                            class="border rounded px-2 py-1 text-xs w-full dark:bg-neutral-900 dark:text-white dark:border-neutral-700" />
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </template>
                                                                    </draggable>
                                                                    <div class="mb-2">
                                                                        <button @click="addVideo(version)"
                                                                            class="bg-indigo-500 text-white px-3 py-1 rounded text-sm">
                                                                            + Add Video
                                                                        </button>
                                                                    </div>

                                                                    <template v-if="showVideoEdit">
                                                                        <div
                                                                            class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
                                                                            <div
                                                                                class="bg-white dark:bg-neutral-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                                                                                <h2 class="text-lg font-bold mb-4">Edit
                                                                                    Video</h2>
                                                                                <div class="mb-4">
                                                                                    <label
                                                                                        class="block text-sm font-medium mb-1">Video
                                                                                        File</label>
                                                                                    <FilePond :allowMultiple="false"
                                                                                        :acceptedFileTypes="['video/mp4', 'video/webm']"
                                                                                        :files="videoEditFile.value"
                                                                                        @updatefiles="files => videoEditFile = files"
                                                                                        class="filepond-dropzone" />
                                                                                </div>
                                                                                <div class="mb-4">
                                                                                    <label
                                                                                        class="block text-sm font-medium mb-1">Companion
                                                                                        Banner</label>
                                                                                    <FilePond :allowMultiple="false"
                                                                                        :acceptedFileTypes="['image/png', 'image/jpeg', 'image/gif']"
                                                                                        :files="videoEditBannerFile.value"
                                                                                        @updatefiles="files => videoEditBannerFile = files"
                                                                                        class="filepond-dropzone" />
                                                                                </div>
                                                                                <div class="mb-4">
                                                                                    <label
                                                                                        class="block text-sm font-medium mb-1">Video
                                                                                        Size</label>
                                                                                    <v-select
                                                                                        :options="videoSizeOptions"
                                                                                        label="label"
                                                                                        :reduce="size => size.id"
                                                                                        v-model="videoEditSizeId"
                                                                                        placeholder="Select Video Size"
                                                                                        :clearable="false"
                                                                                        class="w-full dark:bg-white dark:text-gray-800 dark:border-neutral-700" />
                                                                                </div>
                                                                                <div class="flex gap-1 w-full">
                                                                                    <div class="mb-4">
                                                                                        <label
                                                                                            class="block text-sm font-medium mb-1">Codec</label>
                                                                                        <input v-model="videoEditCodec"
                                                                                            placeholder="Codec"
                                                                                            class="border rounded px-2 py-1 text-xs w-full dark:bg-neutral-900 dark:text-white dark:border-neutral-700" />
                                                                                    </div>
                                                                                    <div class="mb-4">
                                                                                        <label
                                                                                            class="block text-sm font-medium mb-1">Aspect
                                                                                            Ratio</label>
                                                                                        <input
                                                                                            v-model="videoEditAspectRatio"
                                                                                            placeholder="Aspect Ratio"
                                                                                            class="border rounded px-2 py-1 text-xs w-full dark:bg-neutral-900 dark:text-white dark:border-neutral-700" />
                                                                                    </div>
                                                                                    <div class="mb-4">
                                                                                        <label
                                                                                            class="block text-sm font-medium mb-1">FPS</label>
                                                                                        <input v-model="videoEditFps"
                                                                                            placeholder="FPS"
                                                                                            class="border rounded px-2 py-1 text-xs w-full dark:bg-neutral-900 dark:text-white dark:border-neutral-700" />
                                                                                    </div>
                                                                                </div>

                                                                                <div class="flex gap-2 mt-6 w-full">
                                                                                    <button @click="closeVideoEdit"
                                                                                        class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded font-semibold">Cancel</button>
                                                                                    <button @click="submitVideoEdit"
                                                                                        class="w-full bg-blue-600 text-white px-4 py-2 rounded font-semibold">
                                                                                        Update
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                                <!-- Socials FilePond for new upload -->
                                                                <div v-if="category.type === 'social'">
                                                                    <div
                                                                        v-if="!version.socials || version.socials.length === 0">
                                                                        <FilePond :allowMultiple="true"
                                                                            :acceptedFileTypes="['image/jpeg', 'image/png', 'image/webp', 'image/bmp', 'image/svg+xml']"
                                                                            :labelIdle="'Drag & Drop your images or <span class=\'filepond--label-action\'>Browse</span>'"
                                                                            :files="version._filepondSocialFiles || []"
                                                                            @updatefiles="files => handleSocialDrop(files, version)"
                                                                            class="my-4 filepond-dropzone" />
                                                                    </div>

                                                                    <!-- Draggable list of socials -->
                                                                    <draggable
                                                                        v-if="version.socials && version.socials.length"
                                                                        v-model="version.socials" item-key="id"
                                                                        @end="updateSocialPositions(version)"
                                                                        class="space-y-2 mt-4">
                                                                        <template #item="{ element, index }">
                                                                            <div
                                                                                class="flex items-center bg-white dark:bg-neutral-900 rounded shadow px-4 py-3">
                                                                                <span
                                                                                    class="text-xs text-gray-500 mr-2">{{
                                                                                        index + 1 }}</span>
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
                                                                                <img v-if="element.file && typeof element.file === 'object'"
                                                                                    :src="URL.createObjectURL(element.file)"
                                                                                    alt="Social Image"
                                                                                    class="w-16 h-16 object-cover rounded mr-4" />
                                                                                <img v-else-if="element.path"
                                                                                    :src="`/${element.path}`"
                                                                                    alt="Social Image"
                                                                                    class="w-16 h-16 object-cover rounded mr-4" />
                                                                                <span
                                                                                    class="font-medium min-w-[120px]">{{
                                                                                        element.name }}</span>
                                                                                <div class="flex-1"></div>
                                                                                <button v-if="isDbId(element.id)"
                                                                                    @click.stop="editSocial(element, version, index)"
                                                                                    class="text-blue-600 hover:text-blue-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                    Edit
                                                                                </button>
                                                                                <button v-if="isDbId(element.id)"
                                                                                    @click.stop="deleteSocial(element, version, index)"
                                                                                    class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                    Delete
                                                                                </button>
                                                                                <button v-if="!isDbId(element.id)"
                                                                                    @click.stop="removeSocial(version, index)"
                                                                                    class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                    Delete
                                                                                </button>
                                                                            </div>
                                                                        </template>
                                                                    </draggable>
                                                                    <template v-if="showSocialEdit">
                                                                        <div
                                                                            class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
                                                                            <div
                                                                                class="bg-white dark:bg-neutral-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                                                                                <h2 class="text-lg font-bold mb-4">Edit
                                                                                    Social Image</h2>
                                                                                <div class="mb-4">
                                                                                    <FilePond :allowMultiple="false"
                                                                                        :acceptedFileTypes="['image/jpeg', 'image/png', 'image/webp', 'image/bmp', 'image/svg+xml']"
                                                                                        :files="socialEditFile.value"
                                                                                        @updatefiles="files => { socialEditFile = files; }"
                                                                                        class="filepond-dropzone" />
                                                                                </div>
                                                                                <div class="flex gap-2 mt-6 w-full">
                                                                                    <button @click="closeSocialEdit"
                                                                                        class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded font-semibold">Cancel</button>
                                                                                    <button @click="submitSocialEdit"
                                                                                        class="w-full bg-blue-600 text-white px-4 py-2 rounded font-semibold">
                                                                                        Update
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>

                                                                <div v-if="category.type === 'gif'">
                                                                    <div v-if="!version.gifs || version.gifs.length === 0"
                                                                        class="mt-4 flex gap-2 items-center">
                                                                        <FilePond :allowMultiple="true"
                                                                            :acceptedFileTypes="['image/gif']"
                                                                            :labelIdle="'Drag & Drop your GIF files or <span class=\'filepond--label-action\'>Browse</span>'"
                                                                            :files="version._filepondGifFiles || []"
                                                                            @updatefiles="files => handleGifDrop(files, version)"
                                                                            class="my-4 filepond-dropzone" />
                                                                    </div>
                                                                    <draggable
                                                                        v-if="version.gifs && version.gifs.length"
                                                                        v-model="version.gifs" item-key="id"
                                                                        @end="updateGifPositions(version)"
                                                                        class="space-y-2 mt-4">
                                                                        <template #item="{ element, index }">
                                                                            <div
                                                                                class="flex items-center bg-white dark:bg-neutral-900 rounded shadow px-4 py-3">
                                                                                <span
                                                                                    class="text-xs text-gray-500 mr-2">{{
                                                                                        index + 1 }}</span>
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
                                                                                <img v-if="element.file && typeof element.file === 'object'"
                                                                                    :src="URL.createObjectURL(element.file)"
                                                                                    alt="GIF"
                                                                                    class="w-16 h-16 object-cover rounded mr-4" />
                                                                                <img v-else-if="element.path"
                                                                                    :src="`/${element.path}`" alt="GIF"
                                                                                    class="w-16 h-16 object-cover rounded mr-4" />
                                                                                <span
                                                                                    class="font-medium min-w-[120px]">{{
                                                                                        element.name }}</span>
                                                                                <div class="flex-1"></div>
                                                                                <div class="flex items-center gap-4">
                                                                                    <span
                                                                                        class="text-xs text-gray-500">Size:</span>
                                                                                    <v-select
                                                                                        :options="bannerSizeOptions"
                                                                                        label="label"
                                                                                        :reduce="size => size.id"
                                                                                        v-model="element.size_id"
                                                                                        placeholder="Select GIF Size"
                                                                                        :clearable="false"
                                                                                        class="w-48 dark:bg-white dark:text-gray-800 dark:border-neutral-700" />
                                                                                </div>
                                                                                <button v-if="isDbId(element.id)"
                                                                                    @click.stop="editGif(element, version, index)"
                                                                                    class="text-blue-600 hover:text-blue-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                    Edit
                                                                                </button>
                                                                                <button v-if="isDbId(element.id)"
                                                                                    @click.stop="deleteGif(element, version, index)"
                                                                                    class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                    Delete
                                                                                </button>
                                                                                <button v-if="!isDbId(element.id)"
                                                                                    @click.stop="removeGif(version, index)"
                                                                                    class="text-red-600 hover:text-red-800 hover:underline px-2 py-1 rounded text-xs ml-2">
                                                                                    Delete
                                                                                </button>
                                                                            </div>
                                                                        </template>
                                                                    </draggable>
                                                                    <!-- GIF Edit Modal (see script section below) -->
                                                                    <template v-if="showGifEdit">
                                                                        <div
                                                                            class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
                                                                            <div
                                                                                class="bg-white dark:bg-neutral-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                                                                                <h2 class="text-lg font-bold mb-4">Edit
                                                                                    GIF</h2>
                                                                                <div class="mb-4">
                                                                                    <FilePond :allowMultiple="false"
                                                                                        :acceptedFileTypes="['image/gif']"
                                                                                        :files="gifEditFile"
                                                                                        @updatefiles="files => gifEditFile = files"
                                                                                        class="filepond-dropzone" />
                                                                                </div>
                                                                                <div class="mb-4">
                                                                                    <label
                                                                                        class="block text-sm font-medium mb-1">GIF
                                                                                        Size</label>
                                                                                    <v-select
                                                                                        :options="bannerSizeOptions"
                                                                                        label="label"
                                                                                        :reduce="size => size.id"
                                                                                        v-model="gifEditSizeId"
                                                                                        placeholder="Select GIF Size"
                                                                                        :clearable="false"
                                                                                        class="w-full dark:bg-white dark:text-gray-800 dark:border-neutral-700" />
                                                                                </div>
                                                                                <div class="flex gap-2 mt-6 w-full">
                                                                                    <button @click="closeGifEdit"
                                                                                        class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded font-semibold">Cancel</button>
                                                                                    <button @click="submitGifEdit"
                                                                                        class="w-full bg-blue-600 text-white px-4 py-2 rounded font-semibold">Update</button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </template>
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
                                                            placeholder="Version Name (Optional)"
                                                            class="border rounded px-2 py-1 dark:bg-neutral-900 dark:text-white dark:border-neutral-700" />
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
                                            <input v-model="newSetName[feedback.id]" placeholder="Set Name (Optional)"
                                                class="border rounded px-2 py-1 dark:bg-neutral-900 dark:text-white dark:border-neutral-700" />
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
                                <textarea v-model="newFeedbackDesc[category.id]" placeholder="Enter Description"
                                    class="border rounded px-2 py-1 min-w-[200px] min-h-[60px] dark:bg-neutral-900 dark:text-white dark:border-neutral-700" />
                                <div class="flex gap-2">
                                    <input :value="newFeedbackName[category.id]"
                                        @input="onNewFeedbackNameInput($event, category)" placeholder="Feedback Name"
                                        class="border rounded px-2 py-1 dark:bg-neutral-900 dark:text-white dark:border-neutral-700" />
                                    <button @click="addFeedback(category)"
                                        class="bg-green-600 text-white px-3 py-1 rounded">Add</button>
                                </div>
                            </div>
                        </div>
                    </details>
                </div>
                <div class="mt-4 flex gap-2 items-center justify-start">
                    <button @click="showAddCategory = !showAddCategory"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg">
                        + Add Category
                    </button>
                </div>
                <div v-if="showAddCategory" class="mt-2 flex gap-2 items-center justify-start">
                    <input v-model="newCategoryName" placeholder="Category Name"
                        class="border rounded px-2 py-1 dark:bg-neutral-900 dark:text-white dark:border-neutral-700" />
                    <select v-model="newCategoryType"
                        class="border rounded-lg px-2 py-1 dark:bg-neutral-900 dark:text-white dark:border-neutral-700">
                        <option value="banner">Banner</option>
                        <option value="video">Video</option>
                        <option value="social">Social</option>
                        <option value="gif">Gif</option>
                    </select>
                    <button @click="addCategory" class="bg-green-600 text-white px-3 py-1 rounded-lg">Add</button>
                </div>
                <!-- Approve modal -->
                <div v-if="showApproveModal"
                    class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
                    <div class="bg-white dark:bg-neutral-900 rounded-lg shadow-xl w-full max-w-3xl overflow-hidden">
                        <div class="flex items-center justify-between px-6 py-4 border-b dark:border-neutral-800">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold">Prepare File Transfer</h3>
                            </div>
                            <button @click="closeApproveModal" aria-label="Close"
                                class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 011.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div class="px-6 py-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File
                                        Transfer
                                        Name</label>
                                    <input v-model="approveTransferName"
                                        class="w-full border rounded-lg px-3 py-2 bg-white dark:bg-neutral-800 text-sm"
                                        placeholder="E.g. Weekly Transfer - 2025-12-19" />
                                    <p class="text-xs text-gray-500 mt-1">A short descriptive name for this transfer.
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client
                                        Name</label>
                                    <input v-model="approveClientName"
                                        class="w-full border rounded-lg px-3 py-2 bg-white dark:bg-neutral-800 text-sm"
                                        placeholder="Client name" />
                                    <p class="text-xs text-gray-500 mt-1">Optional — shown on the file transfer page.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Upload
                                    ZIP files</label>
                                <div class="border border-dashed rounded p-3 bg-gray-50 dark:bg-neutral-850">
                                    <FilePond ref="approvePond" :allowMultiple="true"
                                        :acceptedFileTypes="['application/zip', 'application/x-zip-compressed']"
                                        :files="approveFiles.value" @updatefiles="files => approveFiles.value = files"
                                        name="approve_files[]" :server="null" class="my-2" />
                                    <p class="text-xs text-gray-500 mt-2">Only .zip files are allowed. You may upload
                                        multiple archives.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t dark:border-neutral-800">
                            <button @click="closeApproveModal"
                                class="px-4 py-2 rounded-xl bg-transparent border border-gray-200 dark:border-neutral-700 text-sm">Cancel</button>
                            <button @click="submitApprove"
                                class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm hover:bg-indigo-700">Approve
                                &
                                Upload</button>
                        </div>
                    </div>
                </div>

                <div
                    class="sticky-action-bar bg-white dark:bg-neutral-900 flex items-center justify-center gap-3 p-4 border-t shadow-lg">
                    <a :href="route('previews-index')"
                        class="bg-gray-600 text-white px-6 py-2 rounded-xl hover:bg-gray-700 font-semibold inline-flex items-center gap-2 transition-colors">
                        <ArrowLeft class="w-4 h-4" />
                        Back
                    </a>
                    <a :href="route('previews-edit', preview.id)"
                        class="bg-blue-600 text-white px-6 py-2 rounded-xl hover:bg-blue-700 font-semibold inline-flex items-center gap-2 transition-colors"
                        aria-label="Edit Preview">
                        <Info class="w-4 h-4" />
                        More Info
                    </a>
                    <button type="button" @click="goToPreview"
                        class="bg-purple-600 text-white px-6 py-2 rounded-xl hover:bg-purple-700 font-semibold inline-flex items-center gap-2 transition-colors">
                        <Eye class="w-4 h-4" />
                        Preview
                    </button>
                    <button @click="saveAll"
                        class="bg-green-600 text-white px-6 py-2 rounded-xl hover:bg-green-700 font-semibold inline-flex items-center gap-2 transition-colors">
                        <Save class="w-4 h-4" />
                        Save
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
import { ArrowLeft, Info, Eye, Save } from 'lucide-vue-next';
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
const preview_id = computed(() => page.props.preview_id);
const preview_name = computed(() => page.props.preview_name);
const client_name = computed(() => page.props.client_name);
const bannerSizes = computed(() => page.props.bannerSizes);
const slug = computed(() => page.props.slug);
const flashMessage = computed(() => page.props.flash);

function goToPreview() {
    window.open(`/previews/show/${preview.value.slug}`, '_blank');
}

const URL = window.URL;

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

const showBannerEdit = ref(false);
const bannerEditFile = ref([]);
const bannerEditSizeId = ref(null);
let bannerEditObj = null;
let bannerEditVersion = null;
let bannerEditIndex = null;

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
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the category and its contents.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('previews.category.delete', category.id), {
                preserveState: true,
                onSuccess: (page) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Category has been deleted.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    // Update categories with returned data
                    if (page.props.categories) {
                        preview.value.categories = page.props.categories;
                    }
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete failed',
                        text: 'An error occurred.'
                    });
                }
            });
        }
    });
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

function approveFeedback(feedback, category, fbIdx) {
    Swal.fire({
        title: 'Approve Feedback?',
        text: 'This will mark this feedback as approved.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, approve it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // open modal to collect transfer details and files
            approveFeedbackObj = feedback;
            approveTransferName.value = `${preview_name.value || ''} - File Transfer`;
            approveClientName.value = client_name.value || '';
            approveFiles.value = [];
            showApproveModal.value = true;
        }
    });
}

function closeApproveModal() {
    showApproveModal.value = false;
    approveTransferName.value = '';
    approveClientName.value = '';
    approveFiles.value = [];
    approveFeedbackObj = null;
}

async function submitApprove() {
    if (!approveTransferName.value) {
        Swal.fire({ icon: 'error', title: 'Missing Name', text: 'Please enter a File Transfer Name.' });
        return;
    }

    if (!approveFeedbackObj) return;

    approveFeedbackObj.isApproving = true;

    const formData = new FormData();
    formData.append('transfer_name', approveTransferName.value);
    formData.append('client_name', approveClientName.value);
    // also include feedback id explicitly
    formData.append('feedback_id', approveFeedbackObj.id);

    // debug log to inspect files client-side
    console.log('approveFiles value before upload:', approveFiles.value);

    // Prefer getting files from FilePond instance (safer); fall back to approveFiles ref
    let pondFiles = [];
    try {
        if (approvePond && approvePond.value && typeof approvePond.value.getFiles === 'function') {
            pondFiles = approvePond.value.getFiles();
        }
    } catch (e) {
        pondFiles = [];
    }

    const sourceFiles = (pondFiles && pondFiles.length) ? pondFiles : (approveFiles.value || []);
    sourceFiles.forEach((fileItem, idx) => {
        let fileObj = null;
        if (!fileItem) return;
        if (fileItem instanceof File) {
            fileObj = fileItem;
        } else if (fileItem.file instanceof File) {
            fileObj = fileItem.file;
        } else if (fileItem.getFile && typeof fileItem.getFile === 'function') {
            try { fileObj = fileItem.getFile(); } catch (e) { /* ignore */ }
        } else if (fileItem.originFileObj instanceof File) {
            fileObj = fileItem.originFileObj;
        } else if (fileItem.file && fileItem.file.file instanceof File) {
            fileObj = fileItem.file.file;
        }

        if (fileObj) {
            // Append under the conventional key the controller expects
            formData.append(`files[${idx}]`, fileObj, fileObj.name);
        }
    });

    // Log formData entries (files will appear as File objects)
    for (let pair of formData.entries()) {
        console.log('formData entry:', pair[0], pair[1]);
    }

    const url = route('previews.feedback.approve', approveFeedbackObj.id);
    try {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrf = tokenMeta ? tokenMeta.getAttribute('content') : null;

        const res = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {}
        });

        if (!res.ok) {
            const text = await res.text();
            console.error('Upload failed', res.status, text);
            approveFeedbackObj.isApproving = false;
            Swal.fire({ icon: 'error', title: 'Approval failed', text: 'Disapprove the approved feedback first.' });
            return;
        }

        const data = await res.json().catch(() => null);

        // Update the feedback object first so the UI clears loading state
        if (approveFeedbackObj) {
            approveFeedbackObj.is_approved = true;
            approveFeedbackObj.isApproving = false;
            if (data && data.feedback) {
                Object.assign(approveFeedbackObj, data.feedback);
            }
        }

        // Show success toast and then close modal after updating state
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Approved!',
            text: 'Feedback has been approved and files uploaded.',
            timer: 1600,
            timerProgressBar: true,
            showConfirmButton: false,
        });

        // Close modal after showing success
        closeApproveModal();
    } catch (err) {
        console.error('Approval failed (fetch):', err);
        approveFeedbackObj.isApproving = false;
        Swal.fire({ icon: 'error', title: 'Approval failed', text: 'An error occurred while approving the feedback.' });
    }
}

function disapproveFeedback(feedback, category, fbIdx) {
    Swal.fire({
        title: 'Disapprove Feedback?',
        text: 'This will mark this feedback as not approved.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, disapprove it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // ✅ Set loading state for disapproval
            feedback.isDisapproving = true;

            router.put(route('previews.feedback.disapprove', feedback.id), {}, {
                onSuccess: (page) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Disapproved!',
                        text: 'Feedback has been disapproved.',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // ✅ Update approval status and clear loading
                    feedback.is_approved = false;
                    feedback.isDisapproving = false;

                    if (page.props.feedback) {
                        Object.assign(feedback, page.props.feedback);
                    }
                },
                onError: (err) => {
                    console.error('Disapproval failed:', err);
                    feedback.isDisapproving = false; // ✅ Clear loading on error

                    Swal.fire({
                        icon: 'error',
                        title: 'Disapproval failed',
                        text: 'An error occurred while disapproving the feedback.'
                    });
                }
            });
        }
    });
}

// Remove Feedback (saved, just UI for now)
function deleteFeedback(feedback, category, idx) {
    // TODO: Implement API call later
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the feedback and its contents.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('previews.feedback.delete', feedback.id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Feedback has been deleted.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    // Remove feedback from UI
                    category.feedbacks.splice(idx, 1);
                },
                onError: (err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete failed',
                        text: 'An error occurred.'
                    });
                }
            });
        }
    });
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
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the set and its contents.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('previews.feedback.set.delete', set.id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Set has been deleted.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    // Remove set from UI
                    feedback.feedback_sets.splice(idx, 1);
                },
                onError: (err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete failed',
                        text: 'An error occurred.'
                    });
                }
            });
        }
    });
}

// Add Version
function addVersion(set: any) {
    if (!set.versions) set.versions = [];
    set.versions.push({
        id: Date.now(),
        name: newVersionName[set.id] || '',
        banners: [],
        socials: [],
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
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the version and its contents.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('previews.version.delete', version.id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Version has been deleted.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    // Remove version from UI
                    set.versions.splice(idx, 1);
                },
                onError: (err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete failed',
                        text: 'An error occurred.'
                    });
                }
            });
        }
    });
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
function removeBanner(version, index) {
    version.banners.splice(index, 1);
    updateBannerPositions(version);
    // Reset FilePond if all banners are deleted
    if (version.banners.length === 0) {
        version._filepondFiles = [];
    }
}
function editBanner(banner, version, index) {
    showBannerEdit.value = true;
    bannerEditObj = banner;
    bannerEditVersion = version;
    bannerEditIndex = index;
    bannerEditFile.value = [];
    bannerEditSizeId.value = banner.size_id;
}
function closeBannerEdit() {
    showBannerEdit.value = false;
    bannerEditObj = null;
    bannerEditVersion = null;
    bannerEditIndex = null;
    bannerEditFile.value = [];
    bannerEditSizeId.value = null;
}

function submitBannerEdit() {
    const formData = new FormData();
    formData.append('size_id', bannerEditSizeId.value);
    if (bannerEditFile.value.length > 0 && bannerEditFile.value[0].file) {
        formData.append('file', bannerEditFile.value[0].file);
    }
    router.post(route('previews.banner.edit', bannerEditObj.id), formData, {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Banner updated!',
                timer: 1500,
                showConfirmButton: false
            });
            closeBannerEdit();
            // Optionally, refresh the banner data here
        },
        onError: (err) => {
            Swal.fire({
                icon: 'error',
                title: 'Update failed',
                text: 'An error occurred.'
            });
        }
    });
}
// Remove Banner (saved, just UI for now)
function deleteBanner(banner, version, index) {
    // TODO: Implement API call later
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the banner and its contents.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('previews.banner.delete', banner.id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Banner has been deleted.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    // Remove banner from UI
                    version.banners.splice(index, 1);
                },
                onError: (err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete failed',
                        text: 'An error occurred.'
                    });
                }
            });
        }
    });
}

function updateBannerPositions(version: any) {
    version.banners.forEach((banner: any, idx: number) => {
        banner.position = idx + 1;
    });
}

// Social image upload handler
function handleSocialDrop(files, version) {
    version._filepondSocialFiles = files;
    version.socials = [];
    files.forEach((fileWrapper, idx) => {
        const file = fileWrapper.file;
        version.socials.push({
            id: Date.now() + idx,
            name: file.name,
            file,
            position: version.socials.length + 1,
        });
    });
    updateSocialPositions(version);
}

// Update positions after drag/drop
function updateSocialPositions(version) {
    version.socials.forEach((social, idx) => {
        social.position = idx + 1;
    });
}

function removeSocial(version, index) {
    version.socials.splice(index, 1);
    updateSocialPositions(version);
    if (version.socials.length === 0) {
        version._filepondSocialFiles = [];
    }
}

const showSocialEdit = ref(false);
const socialEditFile = ref([]);
let socialEditObj = null;
let socialEditVersion = null;
let socialEditIndex = null;

// Open modal for editing a social image
function editSocial(social, version, index) {
    showSocialEdit.value = true;
    socialEditObj = social;
    socialEditVersion = version;
    socialEditIndex = index;
    socialEditFile.value = [];
}

// Close modal
function closeSocialEdit() {
    showSocialEdit.value = false;
    socialEditObj = null;
    socialEditVersion = null;
    socialEditIndex = null;
    socialEditFile.value = [];
}

// Submit new image for social
function submitSocialEdit() {
    if (socialEditFile.value.length === 0 || !socialEditFile.value[0].file) {
        Swal.fire({
            icon: 'error',
            title: 'No image selected',
            text: 'Please select an image to upload.'
        });
        return;
    }

    const formData = new FormData();
    formData.append('file', socialEditFile.value[0].file);
    formData.append('position', socialEditObj.position);

    // Optional: log FormData before submission
    for (let pair of formData.entries()) {
        console.log(pair[0], pair[1]);
    }

    router.post(route('previews.social.edit', socialEditObj.id), formData, {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Social image updated!',
                timer: 1500,
                showConfirmButton: false
            });
            closeSocialEdit();
            // Optionally, refresh the social data here
        },
        onError: (err) => {
            Swal.fire({
                icon: 'error',
                title: 'Update failed',
                text: 'An error occurred.'
            });
        }
    });
}

function deleteSocial(social, version, index) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the social image and its record.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('previews.social.delete', social.id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Social image has been deleted.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    version.socials.splice(index, 1);
                },
                onError: (err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete failed',
                        text: 'An error occurred.'
                    });
                }
            });
        }
    });
}

const showGifEdit = ref(false);
const gifEditFile = ref([]);
const gifEditSizeId = ref(null);
let gifEditObj = null;
let gifEditVersion = null;
let gifEditIndex = null;

// Approve modal state
const showApproveModal = ref(false);
const approveTransferName = ref('');
const approveClientName = ref('');
let approveFiles = ref([]);
let approveFeedbackObj = null;
const approvePond = ref(null);

function handleGifDrop(files, version) {
    version._filepondGifFiles = files;
    version.gifs = [];
    files.forEach((fileWrapper, idx) => {
        const file = fileWrapper.file;
        version.gifs.push({
            id: Date.now() + idx,
            name: file.name,
            file,
            size_id: '',
            position: version.gifs.length + 1,
        });
    });
    updateGifPositions(version);
}

function updateGifPositions(version) {
    version.gifs.forEach((gif, idx) => {
        gif.position = idx + 1;
    });
}

function removeGif(version, index) {
    version.gifs.splice(index, 1);
    updateGifPositions(version);
    if (version.gifs.length === 0) {
        version._filepondGifFiles = [];
    }
}

function editGif(gif, version, index) {
    showGifEdit.value = true;
    gifEditObj = gif;
    gifEditVersion = version;
    gifEditIndex = index;
    gifEditFile.value = [];
    gifEditSizeId.value = gif.size_id;
}

function closeGifEdit() {
    showGifEdit.value = false;
    gifEditObj = null;
    gifEditVersion = null;
    gifEditIndex = null;
    gifEditFile.value = [];
    gifEditSizeId.value = null;
}

function submitGifEdit() {
    const formData = new FormData();
    formData.append('size_id', gifEditSizeId.value);
    if (gifEditFile.value.length > 0 && gifEditFile.value[0].file) {
        formData.append('file', gifEditFile.value[0].file);
    }
    router.post(route('previews.gif.edit', gifEditObj.id), formData, {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'GIF updated!',
                timer: 1500,
                showConfirmButton: false
            });
            closeGifEdit();
            // Optionally, refresh the GIF data here
        },
        onError: (err) => {
            Swal.fire({
                icon: 'error',
                title: 'Update failed',
                text: 'An error occurred.'
            });
        }
    });
}

function deleteGif(gif, version, index) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the GIF and its record.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('previews.gif.delete', gif.id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'GIF has been deleted.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    version.gifs.splice(index, 1);
                },
                onError: (err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete failed',
                        text: 'An error occurred.'
                    });
                }
            });
        }
    });
}

const videoSizes = computed(() => page.props.videoSizes);
const videoSizeOptions = computed(() =>
    videoSizes.value.map(size => ({
        id: Number(size.id), // <-- Ensure id is a number
        label: `${size.width}x${size.height}`
    }))
);

function addVideo(version) {
    if (!version.videos) version.videos = [];
    version.videos.push({
        id: Date.now(),
        codec: 'H264',
        aspect_ratio: '',
        fps: '30 FPS',
        size_id: '',
        position: version.videos.length + 1,
        _filepondVideo: [],
        _filepondBanner: [],
    });
    updateVideoPositions(version);
}

function handleVideoFile(files, video) {
    video._filepondVideo = files;
    video.file = files.length > 0 ? files[0].file : null;
}

function handleCompanionBanner(files, video) {
    video._filepondBanner = files;
    video.companion_banner = files.length > 0 ? files[0].file : null;
}

function removeVideo(version, index) {
    version.videos.splice(index, 1);
    updateVideoPositions(version);
}

function updateVideoPositions(version) {
    version.videos.forEach((video, idx) => {
        video.position = idx + 1;
    });
}

function deleteVideo(video, version, index) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the video and its files.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('previews.video.delete', video.id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Video has been deleted.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    version.videos.splice(index, 1);
                },
                onError: (err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete failed',
                        text: 'An error occurred.'
                    });
                }
            });
        }
    });
}

const showVideoEdit = ref(false);
const videoEditFile = ref([]);
const videoEditBannerFile = ref([]);
const videoEditSizeId = ref(null);
const videoEditCodec = ref('');
const videoEditAspectRatio = ref('');
const videoEditFps = ref('');
let videoEditObj = null;
let videoEditVersion = null;
let videoEditIndex = null;

function editVideo(video, version, index) {
    showVideoEdit.value = true;
    videoEditObj = video;
    videoEditVersion = version;
    videoEditIndex = index;
    videoEditFile.value = [];
    videoEditBannerFile.value = [];
    videoEditSizeId.value = video.size_id;
    videoEditCodec.value = video.codec || '';
    videoEditAspectRatio.value = video.aspect_ratio || '';
    videoEditFps.value = video.fps || '';
}

function closeVideoEdit() {
    showVideoEdit.value = false;
    videoEditObj = null;
    videoEditVersion = null;
    videoEditIndex = null;
    videoEditFile.value = [];
    videoEditBannerFile.value = [];
    videoEditSizeId.value = null;
}

function submitVideoEdit() {
    const formData = new FormData();
    formData.append('size_id', videoEditSizeId.value);
    formData.append('codec', videoEditCodec.value);
    formData.append('aspect_ratio', videoEditAspectRatio.value);
    formData.append('fps', videoEditFps.value);
    if (videoEditFile.value.length > 0 && videoEditFile.value[0].file) {
        formData.append('file', videoEditFile.value[0].file);
    }
    if (videoEditBannerFile.value.length > 0 && videoEditBannerFile.value[0].file) {
        formData.append('companion_banner', videoEditBannerFile.value[0].file);
    }
    router.post(route('previews.video.edit', videoEditObj.id), formData, {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Video updated!',
                timer: 1500,
                showConfirmButton: false
            });
            closeVideoEdit();
        },
        onError: (err) => {
            Swal.fire({
                icon: 'error',
                title: 'Update failed',
                text: 'An error occurred.'
            });
        }
    });
}

function deleteCompanionBanner(video, version, index) {
    // If video is not saved in DB, just remove the companion_banner from UI
    if (!isDbId(video.id)) {
        video.companion_banner = null;
        video.companion_banner_path = null;
        video._filepondBanner = [];
        return;
    }

    // If video is saved in DB, call API to delete companion banner
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the companion banner image.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('previews.companion.banner.delete', video.id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Companion banner has been deleted.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    video.companion_banner_path = null;
                },
                onError: (err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete failed',
                        text: 'An error occurred.'
                    });
                }
            });
        }
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

                    // Banner assets
                    if (category.type === 'banner' && version.banners) {
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
                    }

                    // Social assets
                    if (category.type === 'social' && version.socials) {
                        version.socials.forEach((social, sIdx) => {
                            if (isDbId(social.id)) {
                                formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][socials][${sIdx}][id]`, social.id);
                            }
                            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][socials][${sIdx}][name]`, social.name);
                            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][socials][${sIdx}][position]`, social.position);
                            if (social.file) {
                                formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][socials][${sIdx}][file]`, social.file);
                            }
                        });
                    }

                    // Video assets
                    if (category.type === 'video' && version.videos) {
                        version.videos.forEach((video, vIdx) => {
                            if (isDbId(video.id)) {
                                formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][videos][${vIdx}][id]`, video.id);
                            }
                            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][videos][${vIdx}][codec]`, video.codec);
                            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][videos][${vIdx}][aspect_ratio]`, video.aspect_ratio);
                            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][videos][${vIdx}][fps]`, video.fps);
                            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][videos][${vIdx}][size_id]`, video.size_id);
                            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][videos][${vIdx}][position]`, video.position);
                            if (video.file) {
                                formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][videos][${vIdx}][file]`, video.file);
                            }
                            if (video.companion_banner) {
                                formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][videos][${vIdx}][companion_banner]`, video.companion_banner);
                            }
                        });
                    }

                    // Gif assets
                    if (category.type === 'gif' && version.gifs) {
                        version.gifs.forEach((gif, gIdx) => {
                            if (isDbId(gif.id)) {
                                formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][gifs][${gIdx}][id]`, gif.id);
                            }
                            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][gifs][${gIdx}][name]`, gif.name);
                            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][gifs][${gIdx}][size_id]`, gif.size_id);
                            formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][gifs][${gIdx}][position]`, gif.position);
                            if (gif.file) {
                                formData.append(`categories[${catIdx}][feedbacks][${fbIdx}][feedback_sets][${setIdx}][versions][${verIdx}][gifs][${gIdx}][file]`, gif.file);
                            }
                        });
                    }
                });
            });
        });
    });

    router.post(route('previews.bulkEdit', preview.value.id), formData, {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Saved successfully!',
                timer: 1000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                timerProgressBar: true,
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

.sticky-action-bar {
    position: fixed;
    bottom: 0;
    right: 0;
    width: auto;
    border-top-left-radius: 0.5rem;
    box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
    padding: 16px 32px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    z-index: 50;
}
</style>