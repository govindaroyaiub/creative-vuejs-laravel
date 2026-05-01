<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Pencil, Trash2, X, ChevronLeft, ChevronRight, Image as ImageIcon, Film } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';

const page = usePage<any>();
const type = computed<'banner' | 'video'>(() => (page.props.type as any) === 'video' ? 'video' : 'banner');
const sizes = computed<any>(() => page.props.sizes ?? { data: [], links: [] });
const counts = computed<{ banner: number; video: number }>(() => page.props.counts ?? { banner: 0, video: 0 });
const search = ref<string>((page.props.search as string) ?? '');

// Inline state
const adding = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);
const newForm = ref<{ width: string | number; height: string | number }>({ width: '', height: '' });
const editForm = ref<{ width: string | number; height: string | number }>({ width: '', height: '' });

// Tab switcher
function switchTab(next: 'banner' | 'video') {
    if (type.value === next) return;
    cancelAdding();
    cancelEditing();
    router.get(route('creative-sizes-index'), next === 'video' ? { type: 'video' } : {}, {
        preserveState: false,
        replace: true,
    });
}

// Search (server-side, debounced via watch)
let searchTimer: number | null = null;
watch(search, (value) => {
    if (searchTimer) window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
        const params: Record<string, any> = { search: value };
        if (type.value === 'video') params.type = 'video';
        router.get(route('creative-sizes-index'), params, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const changePage = (url: string) => {
    if (!url) return;
    router.get(url, undefined, { preserveScroll: true, preserveState: true });
};

// Adding
function startAdding() {
    cancelEditing();
    adding.value = true;
    newForm.value = { width: '', height: '' };
}
function cancelAdding() {
    adding.value = false;
    newForm.value = { width: '', height: '' };
}
function saveNew() {
    if (!newForm.value.width || !newForm.value.height) return;

    saving.value = true;
    const payload: Record<string, any> = {
        type: type.value,
        width: newForm.value.width,
        height: newForm.value.height,
    };

    router.post(route('creative-sizes-store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            cancelAdding();
            Swal.fire({
                icon: 'success',
                title: 'Added',
                text: type.value === 'video' ? 'Video size added.' : 'Banner size added.',
                timer: 1000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                timerProgressBar: true,
                customClass: { popup: 'rounded-lg' },
            });
        },
        onError: () => {
            Swal.fire({ title: 'Error', text: 'Could not save.', icon: 'error', customClass: { popup: 'rounded-lg' } });
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}

// Editing
function startEditing(size: any) {
    cancelAdding();
    editingId.value = size.id;
    editForm.value = {
        width: size.width,
        height: size.height,
    };
}
function cancelEditing() {
    editingId.value = null;
    editForm.value = { width: '', height: '' };
}
function saveEdit(id: number) {
    saving.value = true;
    const payload: Record<string, any> = {
        type: type.value,
        width: editForm.value.width,
        height: editForm.value.height,
    };

    router.put(route('creative-sizes-update', id), payload, {
        preserveScroll: true,
        onSuccess: () => {
            cancelEditing();
            Swal.fire({
                icon: 'success',
                title: 'Updated',
                text: type.value === 'video' ? 'Video size updated.' : 'Banner size updated.',
                timer: 1000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                timerProgressBar: true,
                customClass: { popup: 'rounded-lg' },
            });
        },
        onError: () => {
            Swal.fire({ title: 'Error', text: 'Could not update.', icon: 'error', customClass: { popup: 'rounded-lg' } });
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}

// Delete
async function remove(id: number) {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#D71921',
        cancelButtonColor: '#666666',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-lg',
            confirmButton: 'rounded-full px-6 py-2',
            cancelButton: 'rounded-full px-6 py-2',
        },
    });
    if (!result.isConfirmed) return;

    router.delete(route('creative-sizes-delete', id), {
        data: { type: type.value } as any,
        preserveScroll: true,
        onSuccess: () => Swal.fire({
            title: 'Deleted',
            text: 'Removed.',
            icon: 'success',
            timer: 1200,
            showConfirmButton: false,
            customClass: { popup: 'rounded-lg' },
        }),
        onError: () => Swal.fire({
            title: 'Error',
            text: 'Could not delete.',
            icon: 'error',
            customClass: { popup: 'rounded-lg' },
        }),
    });
}
</script>

<template>

    <Head title="Creative Sizes" />
    <AppLayout :breadcrumbs="[{ title: 'Creative Sizes', href: '/creative-sizes' }]">
        <div class="min-h-screen bg-white dark:bg-black font-mono">
            <div class="p-4 md:p-6 space-y-4">
                <!-- Top bar: tabs + search + add -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <!-- Tab switcher -->
                    <div
                        class="inline-flex items-center rounded-full border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A] p-0.5 self-start">
                        <button @click="switchTab('banner')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-mono tracking-wide transition-colors"
                            :class="type === 'banner'
                                ? 'bg-black dark:bg-white text-white dark:text-black'
                                : 'text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white'">
                            <ImageIcon class="w-3.5 h-3.5" :stroke-width="1.5" />
                            Banner
                            <span class="text-[10px] tabular-nums opacity-70">({{ counts.banner }})</span>
                        </button>
                        <button @click="switchTab('video')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-mono tracking-wide transition-colors"
                            :class="type === 'video'
                                ? 'bg-black dark:bg-white text-white dark:text-black'
                                : 'text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white'">
                            <Film class="w-3.5 h-3.5" :stroke-width="1.5" />
                            Video
                            <span class="text-[10px] tabular-nums opacity-70">({{ counts.video }})</span>
                        </button>
                    </div>

                    <!-- Search + Add -->
                    <div class="flex items-center gap-2 flex-1 sm:justify-end">
                        <input v-model="search" placeholder="Search..."
                            class="w-full sm:max-w-xs rounded-full border border-[#CCCCCC] dark:border-[#333333] px-4 py-2 bg-white dark:bg-black text-sm text-[#1A1A1A] dark:text-[#E8E8E8] focus:border-black dark:focus:border-white focus:outline-none transition-colors" />
                        <button @click="startAdding"
                            class="inline-flex items-center gap-1.5 px-2 py-2 rounded-full border-2 border-black dark:border-white bg-black dark:bg-white text-white dark:text-black text-xs font-mono tracking-wide hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white transition-colors group whitespace-nowrap">
                            <CirclePlus class="w-4 h-4 group-hover:rotate-90 transition-transform duration-200"
                                :stroke-width="1.5" />
                            Add size
                        </button>
                    </div>
                </div>

                <!-- Desktop Table -->
                <div
                    class="hidden md:block overflow-x-auto rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A]">
                    <table class="w-full">
                        <thead
                            class="bg-[#F5F5F5] dark:bg-black text-[10px] uppercase font-mono tracking-widest text-[#666666] dark:text-[#999999]">
                            <tr>
                                <th class="px-2 py-2 text-center font-medium w-12">#</th>
                                <th class="px-2 py-2 text-left font-medium">Dimensions</th>
                                <th class="px-2 py-2 text-center font-medium w-24">Width</th>
                                <th class="px-2 py-2 text-center font-medium w-24">Height</th>
                                <th class="px-2 py-2 text-center font-medium w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8E8E8] dark:divide-[#222222]">
                            <!-- New Row -->
                            <tr v-if="adding" class="bg-[#FAFAFA] dark:bg-black/30">
                                <td class="px-2 py-2 text-center text-xs text-[#999999]">—</td>
                                <td class="px-2 py-2">
                                    <span class="text-sm font-mono tabular-nums font-semibold text-[#999999]">
                                        {{ newForm.width || '—' }}×{{ newForm.height || '—' }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <input v-model="newForm.width" type="number" min="1"
                                        class="w-20 rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-1 text-sm text-center bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <input v-model="newForm.height" type="number" min="1"
                                        class="w-20 rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-1 text-sm text-center bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="cancelAdding"
                                            class="px-3 py-1 text-[10px] font-mono tracking-wide rounded-full border border-[#D71921] text-[#D71921] hover:bg-[#D71921] hover:text-white transition-colors">Cancel</button>
                                        <button @click="saveNew" :disabled="saving"
                                            class="px-3 py-1 text-[10px] font-mono tracking-wide rounded-full border-2 border-black dark:border-white bg-black dark:bg-white text-white dark:text-black hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white transition-colors disabled:opacity-50">Save</button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Existing Rows -->
                            <tr v-for="(size, index) in sizes.data" :key="size.id"
                                class="hover:bg-[#F5F5F5] dark:hover:bg-black/40 transition-colors">
                                <td class="px-2 py-2 text-center text-xs text-[#666666] dark:text-[#999999] tabular-nums">
                                    {{ index + 1 }}</td>

                                <!-- Dimensions -->
                                <td class="px-2 py-2">
                                    <span v-if="editingId === size.id"
                                        class="text-sm font-mono tabular-nums font-semibold text-[#999999]">
                                        {{ editForm.width || '—' }}×{{ editForm.height || '—' }}
                                    </span>
                                    <span v-else class="text-sm font-mono tabular-nums font-semibold text-black dark:text-white">
                                        {{ size.width }}×{{ size.height }}
                                    </span>
                                </td>

                                <!-- Width -->
                                <td class="px-2 py-2 text-center">
                                    <input v-if="editingId === size.id" v-model="editForm.width" type="number" min="1"
                                        class="w-20 rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-1 text-sm text-center bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                    <span v-else class="text-sm tabular-nums text-[#1A1A1A] dark:text-[#E8E8E8]">{{ size.width }}</span>
                                </td>

                                <!-- Height -->
                                <td class="px-2 py-2 text-center">
                                    <input v-if="editingId === size.id" v-model="editForm.height" type="number" min="1"
                                        class="w-20 rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-1 text-sm text-center bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                    <span v-else class="text-sm tabular-nums text-[#1A1A1A] dark:text-[#E8E8E8]">{{ size.height }}</span>
                                </td>

                                <!-- Actions -->
                                <td class="px-2 py-2 text-center">
                                    <template v-if="editingId === size.id">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="cancelEditing"
                                                class="px-3 py-1 text-[10px] font-mono tracking-wide rounded-full border border-[#D71921] text-[#D71921] hover:bg-[#D71921] hover:text-white transition-colors">Cancel</button>
                                            <button @click="saveEdit(size.id)" :disabled="saving"
                                                class="px-3 py-1 text-[10px] font-mono tracking-wide rounded-full border-2 border-black dark:border-white bg-black dark:bg-white text-white dark:text-black hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white transition-colors disabled:opacity-50">Update</button>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="startEditing(size)"
                                                class="p-1.5 rounded-full text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white transition-colors"
                                                title="Edit">
                                                <Pencil class="w-3.5 h-3.5" :stroke-width="1.5" />
                                            </button>
                                            <button @click="remove(size.id)"
                                                class="p-1.5 rounded-full text-[#D71921] hover:bg-[#D71921] hover:text-white border border-transparent hover:border-[#D71921] transition-all"
                                                title="Delete">
                                                <Trash2 class="w-3.5 h-3.5" :stroke-width="1.5" />
                                            </button>
                                        </div>
                                    </template>
                                </td>
                            </tr>

                            <tr v-if="(sizes.data || []).length === 0 && !adding">
                                <td colspan="5"
                                    class="px-4 py-8 text-center font-mono text-[10px] uppercase tracking-widest text-[#999999]">
                                    No {{ type }} sizes found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden space-y-3">
                    <!-- New Card -->
                    <div v-if="adding"
                        class="bg-white dark:bg-[#0A0A0A] rounded-lg border border-[#E8E8E8] dark:border-[#222222] p-3">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold font-mono text-black dark:text-white">
                                New {{ type }} size
                            </h3>
                            <button @click="cancelAdding"
                                class="text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white transition-colors">
                                <X class="w-4 h-4" :stroke-width="1.5" />
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label
                                        class="block text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">Width</label>
                                    <input v-model="newForm.width" type="number" min="1"
                                        class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">Height</label>
                                    <input v-model="newForm.height" type="number" min="1"
                                        class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                </div>
                            </div>
                            <div class="flex gap-2 pt-1">
                                <button @click="cancelAdding"
                                    class="flex-1 px-3 py-2 text-xs font-mono tracking-wide rounded-full border border-[#D71921] text-[#D71921] hover:bg-[#D71921] hover:text-white transition-colors">Cancel</button>
                                <button @click="saveNew" :disabled="saving"
                                    class="flex-1 px-3 py-2 text-xs font-mono tracking-wide rounded-full border-2 border-black dark:border-white bg-black dark:bg-white text-white dark:text-black hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white transition-colors disabled:opacity-50">Save</button>
                            </div>
                        </div>
                    </div>

                    <!-- Existing -->
                    <div v-for="(size, index) in sizes.data" :key="size.id"
                        class="bg-white dark:bg-[#0A0A0A] rounded-lg border border-[#E8E8E8] dark:border-[#222222] hover:border-black dark:hover:border-white p-3 transition-colors">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-[10px] font-mono tabular-nums text-[#999999]">#{{ index + 1 }}</span>
                                <span class="text-sm font-mono tabular-nums font-semibold text-black dark:text-white">
                                    {{ size.width }}×{{ size.height }}
                                </span>
                            </div>
                            <div v-if="editingId !== size.id" class="flex items-center gap-1 flex-shrink-0">
                                <button @click="startEditing(size)"
                                    class="p-1.5 rounded-full text-[#666666] dark:text-[#999999] hover:text-black dark:hover:text-white transition-colors">
                                    <Pencil class="w-3.5 h-3.5" :stroke-width="1.5" />
                                </button>
                                <button @click="remove(size.id)"
                                    class="p-1.5 rounded-full text-[#D71921] hover:bg-[#D71921] hover:text-white border border-transparent hover:border-[#D71921] transition-all">
                                    <Trash2 class="w-3.5 h-3.5" :stroke-width="1.5" />
                                </button>
                            </div>
                        </div>

                        <template v-if="editingId === size.id">
                            <div class="space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label
                                            class="block text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">Width</label>
                                        <input v-model="editForm.width" type="number" min="1"
                                            class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999] mb-1">Height</label>
                                        <input v-model="editForm.height" type="number" min="1"
                                            class="w-full rounded-lg border border-[#CCCCCC] dark:border-[#333333] px-2 py-2 text-sm bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors tabular-nums" />
                                    </div>
                                </div>
                                <div class="flex gap-2 pt-1">
                                    <button @click="cancelEditing"
                                        class="flex-1 px-3 py-2 text-xs font-mono tracking-wide rounded-full border border-[#D71921] text-[#D71921] hover:bg-[#D71921] hover:text-white transition-colors">Cancel</button>
                                    <button @click="saveEdit(size.id)" :disabled="saving"
                                        class="flex-1 px-3 py-2 text-xs font-mono tracking-wide rounded-full border-2 border-black dark:border-white bg-black dark:bg-white text-white dark:text-black hover:bg-white hover:text-black dark:hover:bg-black dark:hover:text-white transition-colors disabled:opacity-50">Update</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div v-if="(sizes.data || []).length === 0 && !adding"
                        class="rounded-lg border border-[#E8E8E8] dark:border-[#222222] bg-white dark:bg-[#0A0A0A] p-6 text-center font-mono text-[10px] uppercase tracking-widest text-[#999999]">
                        No {{ type }} sizes found
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="sizes.data?.length && (sizes.prev_page_url || sizes.next_page_url)"
                    class="flex items-center justify-between gap-3">
                    <span
                        class="text-[10px] font-mono uppercase tracking-widest text-[#666666] dark:text-[#999999]">Showing
                        <span class="tabular-nums text-black dark:text-white">{{ sizes.from }}–{{ sizes.to }}</span> of
                        <span class="tabular-nums text-black dark:text-white">{{ sizes.total }}</span></span>
                    <div class="flex items-center gap-2">
                        <button @click="changePage(sizes.prev_page_url)" :disabled="!sizes.prev_page_url"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-mono tracking-wide rounded-full border border-[#CCCCCC] dark:border-[#333333] text-[#1A1A1A] dark:text-[#E8E8E8] hover:border-black dark:hover:border-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            <ChevronLeft class="w-3.5 h-3.5" :stroke-width="1.5" />
                            Previous
                        </button>
                        <button @click="changePage(sizes.next_page_url)" :disabled="!sizes.next_page_url"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-mono tracking-wide rounded-full border border-[#CCCCCC] dark:border-[#333333] text-[#1A1A1A] dark:text-[#E8E8E8] hover:border-black dark:hover:border-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            Next
                            <ChevronRight class="w-3.5 h-3.5" :stroke-width="1.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
