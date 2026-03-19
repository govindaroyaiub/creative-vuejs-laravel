<script setup lang="ts">
import { Eye, Trash2, Share2, Settings2 } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

interface Props {
    preview: any;
    size?: 'sm' | 'md';
    showEdit?: boolean;
    stopPropagation?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    showEdit: true,
    stopPropagation: false
});

const iconSize = props.size === 'sm' ? 'h-4 w-4' : 'h-5 w-5';
const padding = props.size === 'sm' ? 'p-1' : 'p-2';

const deletePreview = async (id: number) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This will permanently delete the preview.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });

    if (result.isConfirmed) {
        router.delete(route('previews-delete', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire('Deleted!', 'Preview deleted successfully.', 'success'),
            onError: () => Swal.fire('Error!', 'Failed to delete preview.', 'error'),
        });
    }
};

const handleClick = (e: Event, callback: () => void) => {
    if (props.stopPropagation) {
        e.stopPropagation();
    }
    callback();
};
</script>

<template>
    <div class="flex items-center gap-2">
        <!-- View Button -->
        <a :href="route('previews-show', preview.slug)"
            class="text-green-600 hover:text-green-800 dark:hover:text-green-400 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 transition"
            :class="padding" target="_blank" rel="noopener" aria-label="View Preview"
            @click="stopPropagation ? $event.stopPropagation() : null">
            <Eye :class="iconSize" />
        </a>

        <!-- Share Button -->
        <a :href="`${preview.client?.preview_url}/previews/show/${preview.slug}`"
            class="text-yellow-600 hover:text-yellow-800 dark:hover:text-yellow-400 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition"
            :class="padding" target="_blank" rel="noopener" aria-label="Share Preview"
            @click="stopPropagation ? $event.stopPropagation() : null">
            <Share2 :class="iconSize" />
        </a>

        <!-- Edit Button (optional) -->
        <a v-if="showEdit" :href="route('previews.update.all', preview.id)"
            class="text-indigo-600 hover:text-indigo-800 dark:hover:text-indigo-400 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition"
            :class="padding" aria-label="Edit Preview" @click="stopPropagation ? $event.stopPropagation() : null">
            <Settings2 :class="iconSize" />
        </a>

        <!-- Delete Button -->
        <button @click="handleClick($event, () => deletePreview(preview.id))"
            class="text-red-600 hover:text-red-800 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition"
            :class="padding" aria-label="Delete Preview">
            <Trash2 :class="iconSize" />
        </button>
    </div>
</template>
