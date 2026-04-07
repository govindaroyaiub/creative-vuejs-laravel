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
            class="text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white rounded-full hover:border hover:border-black dark:hover:border-white transition-colors"
            :class="padding" target="_blank" rel="noopener" aria-label="View Preview"
            @click="stopPropagation ? $event.stopPropagation() : null">
            <Eye :class="iconSize" stroke-width="1.5" />
        </a>

        <!-- Share Button -->
        <a :href="`${preview.client?.preview_url}/previews/show/${preview.slug}`"
            class="text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white rounded-full hover:border hover:border-black dark:hover:border-white transition-colors"
            :class="padding" target="_blank" rel="noopener" aria-label="Share Preview"
            @click="stopPropagation ? $event.stopPropagation() : null">
            <Share2 :class="iconSize" stroke-width="1.5" />
        </a>

        <!-- Edit Button (optional) -->
        <a v-if="showEdit" :href="route('previews.update.all', preview.id)"
            class="text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white rounded-full hover:border hover:border-black dark:hover:border-white transition-colors"
            :class="padding" aria-label="Edit Preview" @click="stopPropagation ? $event.stopPropagation() : null">
            <Settings2 :class="iconSize" stroke-width="1.5" />
        </a>

        <!-- Delete Button -->
        <button @click="handleClick($event, () => deletePreview(preview.id))"
            class="text-[#D71921] hover:bg-[#D71921] hover:text-white rounded-full border border-[#D71921] transition-all"
            :class="padding" aria-label="Delete Preview">
            <Trash2 :class="iconSize" stroke-width="1.5" />
        </button>
    </div>
</template>
