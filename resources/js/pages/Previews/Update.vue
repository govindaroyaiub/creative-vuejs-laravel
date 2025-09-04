<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import AppLayout from '@/layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import axios from 'axios';

/* ========= Types ========= */
type Id = number;
type TempId = `tmp-${string}`;
type Key = Id | TempId;
type CategoryKind = 'banner' | 'video' | 'gif' | 'social';

type Base = { id?: Id; tempId?: TempId; _state?: 'created' | 'updated' | 'deleted' | 'unchanged' };

type FileRow = Base & {
    version_id: Key;
    size_id?: number | null;
    name?: string;
    path?: string | null;
    position: number;
};

type VersionRow = Base & {
    feedback_set_id: Key; name: string;
    banners?: FileRow[]; videos?: FileRow[]; gifs?: FileRow[]; socials?: FileRow[];
};

type FeedbackSetRow = Base & { feedback_id: Key; name: string; versions?: VersionRow[] };
type FeedbackRow = Base & { category_id: Key; name: string; feedback_sets?: FeedbackSetRow[] };
type CategoryRow = Base & { preview_id: Id; name: string; feedbacks?: FeedbackRow[] };
type PreviewTree = { id: Id; name: string; categories?: CategoryRow[] };

type Changeset = {
    etag?: string | null;
    categories: { created: any[]; updated: any[]; deleted: Id[] };
    feedbacks: { created: any[]; updated: any[]; deleted: Id[] };
    feedbackSets: { created: any[]; updated: any[]; deleted: Id[] };
    versions: { created: any[]; updated: any[]; deleted: Id[] };
    banners: { created: any[]; updated: any[]; deleted: Id[] };
    videos: { created: any[]; updated: any[]; deleted: Id[] };
    gifs: { created: any[]; updated: any[]; deleted: Id[] };
    socials: { created: any[]; updated: any[]; deleted: Id[] };
    fileReorders: Array<{ version_id: Key; idsInOrder: Key[] }>;
};

/* ========= Inertia props ========= */
const page = usePage<{
    props: {
        preview: PreviewTree;
        bannerSizes?: Array<{ id: number; width: number; height: number }>;
        videoSizes?: Array<{ id: number; name?: string; width?: number; height?: number }>;
        previewETag?: string;
    }
}>();
const preview = reactive<PreviewTree>(JSON.parse(JSON.stringify(page.props.preview)));
const bannerSizes = computed(() => page.props.bannerSizes ?? []);
const videoSizes = computed(() => page.props.videoSizes ?? []);
const etag = computed(() => page.props.previewETag ?? null);

/* ========= Utils ========= */
function tmp(): TempId { return `tmp-${Math.random().toString(36).slice(2, 9)}` as TempId; }
function markUpdated(x: Base) { if (x._state !== 'created') x._state = 'updated'; }
function ensureTemp(o: any) { if (!o.tempId) o.tempId = (o.id ? (`tmp-${o.id}`) : tmp()); }
function notDeleted<T extends Base>(arr: T[] | undefined): T[] { return (arr ?? []).filter(x => x._state !== 'deleted'); }

/* Normalize temps once */
(function normalizeAll() {
    (preview.categories ?? []).forEach((c) => {
        ensureTemp(c);
        (c.feedbacks ?? []).forEach((f: any) => {
            ensureTemp(f);
            (f.feedback_sets ?? f.feedbackSets ?? []).forEach((s: any) => {
                if (s.feedbackSets && !s.feedback_sets) s.feedback_sets = s.feedbackSets;
                ensureTemp(s);
                (s.versions ?? []).forEach((v: any) => {
                    ensureTemp(v);
                    (v.banners ?? []).forEach(ensureTemp);
                    (v.videos ?? []).forEach(ensureTemp);
                    (v.gifs ?? []).forEach(ensureTemp);
                    (v.socials ?? []).forEach(ensureTemp);
                });
            });
        });
    });
})();

/* ========= Selection (Versions only) ========= */
const sel = reactive<{ versionId?: Key }>({});
function selectVersion(v: VersionRow) { sel.versionId = v.id ?? v.tempId; }
const selectedVersion = computed<VersionRow | null>(() => {
    if (!sel.versionId) return null;
    for (const c of notDeleted(preview.categories)) {
        for (const f of notDeleted(c.feedbacks)) {
            for (const s of notDeleted(f.feedback_sets)) {
                for (const v of notDeleted(s.versions)) {
                    if ((v.id ?? v.tempId) === sel.versionId) return v;
                }
            }
        }
    }
    return null;
});

/* ========= Kind & files ========= */
function findCategoryForVersion(versionId: Key): CategoryRow | null {
    for (const c of notDeleted(preview.categories)) {
        for (const f of notDeleted(c.feedbacks)) {
            for (const s of notDeleted(f.feedback_sets)) {
                for (const v of notDeleted(s.versions)) {
                    if ((v.id ?? v.tempId) === versionId) return c;
                }
            }
        }
    }
    return null;
}
function detectKindForVersion(v: VersionRow): CategoryKind {
    const cat = findCategoryForVersion(v.id ?? v.tempId!);
    const n = (cat?.name ?? '').toString().toLowerCase();
    if (n === 'banner' || n === 'video' || n === 'gif' || n === 'social') return n;
    if ((v.banners?.length ?? 0) > 0) return 'banner';
    if ((v.videos?.length ?? 0) > 0) return 'video';
    if ((v.gifs?.length ?? 0) > 0) return 'gif';
    if ((v.socials?.length ?? 0) > 0) return 'social';
    return 'banner';
}
function getFilesArrayForKind(v: VersionRow, kind: CategoryKind): FileRow[] {
    const key = (kind === 'banner' ? 'banners' : kind === 'video' ? 'videos' : kind === 'gif' ? 'gifs' : 'socials') as keyof VersionRow;
    return ((v as any)[key] ?? []) as FileRow[];
}
function setFilesArrayForKind(v: VersionRow, kind: CategoryKind, arr: FileRow[]) {
    const key = (kind === 'banner' ? 'banners' : kind === 'video' ? 'videos' : kind === 'gif' ? 'gifs' : 'socials') as keyof VersionRow;
    (v as any)[key] = arr;
}

/* ========= FilesRef (fixes drag + breaks feedback loop) ========= */
const filesRef = ref<FileRow[]>([]);
const currentKind = computed<CategoryKind>(() => selectedVersion.value ? detectKindForVersion(selectedVersion.value) : 'banner');
const syncing = ref(false); // guard to prevent recursive watchers

// Pull selected version -> filesRef
watch([selectedVersion, currentKind], () => {
    if (syncing.value) return;
    const v = selectedVersion.value;
    if (!v) { filesRef.value = []; return; }
    const arr = getFilesArrayForKind(v, currentKind.value)
        .filter(f => f._state !== 'deleted')
        .map(f => ({ ...f })) // shallow copy for stable reactivity
        .sort((a, b) => (a.position ?? 0) - (b.position ?? 0));
    arr.forEach(ensureTemp);
    syncing.value = true;
    filesRef.value = arr;
    syncing.value = false;
}, { immediate: true });

// Push filesRef -> selected version
watch(filesRef, (arr) => {
    if (syncing.value) return;
    const v = selectedVersion.value; if (!v) return;
    syncing.value = true;
    setFilesArrayForKind(v, currentKind.value, arr);
    syncing.value = false;
}, { deep: true });

/* ========= Structure CRUD ========= */
function addCategory(kind: CategoryKind) {
    const c: CategoryRow = { tempId: tmp(), preview_id: preview.id, name: kind, feedbacks: [], _state: 'created' };
    preview.categories = [...(preview.categories ?? []), c];
}
function deleteCategory(c: CategoryRow) {
    c._state = 'deleted';
    const sv = selectedVersion.value;
    if (sv) {
        const cat = findCategoryForVersion(sv.id ?? sv.tempId!);
        if (cat && (cat.id ?? cat.tempId) === (c.id ?? c.tempId)) sel.versionId = undefined;
    }
}
function addFeedback(c: CategoryRow) {
    c.feedbacks ??= [];
    c.feedbacks.push({ tempId: tmp(), category_id: (c.id ?? c.tempId!), name: 'New Feedback', feedback_sets: [], _state: 'created' });
}
function deleteFeedback(parent: CategoryRow, f: FeedbackRow) {
    f._state = 'deleted';
    sel.versionId = undefined;
}
function addSet(f: FeedbackRow) {
    f.feedback_sets ??= [];
    f.feedback_sets.push({ tempId: tmp(), feedback_id: (f.id ?? f.tempId!), name: 'New Set', versions: [], _state: 'created' });
}
function deleteSet(parent: FeedbackRow, s: FeedbackSetRow) {
    s._state = 'deleted';
    sel.versionId = undefined;
}
function addVersion(s: FeedbackSetRow) {
    s.versions ??= [];
    const v: VersionRow = {
        tempId: tmp(), feedback_set_id: (s.id ?? s.tempId!), name: 'New Version', _state: 'created',
        banners: [], videos: [], gifs: [], socials: []
    };
    s.versions.push(v);
    selectVersion(v);
}
function deleteVersion(parent: FeedbackSetRow, v: VersionRow) {
    v._state = 'deleted';
    if (sel.versionId === (v.id ?? v.tempId)) sel.versionId = undefined;
}

/* ========= Files (upload, delete, reorder) ========= */
const fileReorders = ref<Array<{ version_id: Key; idsInOrder: Key[] }>>([]);

async function onUploadZips(ev: Event) {
    const input = ev.target as HTMLInputElement;
    const files = input.files;
    if (!files?.length || !selectedVersion.value) return;

    // TODO: replace with your staging upload+unzip endpoint; mocked:
    const returned = Array.from(files).map(f => ({ name: f.name, path: `staging/${f.name}` }));

    let pos = filesRef.value.length;
    for (const item of returned) {
        filesRef.value.push({
            tempId: tmp(),
            version_id: (selectedVersion.value!.id ?? selectedVersion.value!.tempId!),
            name: item.name,
            path: item.path,
            size_id: null,
            position: pos++,
            _state: 'created',
        });
    }
    onReorder();
    input.value = '';
}

function getKindKey(kind: CategoryKind): keyof VersionRow {
    return (kind === 'banner' ? 'banners' : kind === 'video' ? 'videos' : kind === 'gif' ? 'gifs' : 'socials') as keyof VersionRow;
}

function findSourceFileAndIndex(v: VersionRow, kind: CategoryKind, key: Key) {
    const arr = (v as any)[getKindKey(kind)] as FileRow[] | undefined;
    if (!arr) return { arr: [] as FileRow[], idx: -1 };
    const idx = arr.findIndex(f => (f.id ?? f.tempId) === key);
    return { arr, idx };
}

function deleteFile(row: FileRow) {
    const v = selectedVersion.value; if (!v) return;
    const kind = currentKind.value;
    const fileKey = (row.id ?? row.tempId)!;

    const { arr, idx } = findSourceFileAndIndex(v, kind, fileKey);
    if (idx === -1) return;

    const src = arr[idx];
    if (src.id) {
        src._state = 'deleted';        // existing -> mark deleted (will be sent to server)
    } else {
        arr.splice(idx, 1);            // brand new -> just drop
    }

    filesRef.value = filesRef.value.filter(f => (f.id ?? f.tempId) !== fileKey); // hide in UI
    onReorder();
}

function onReorder() {
    const v = selectedVersion.value; if (!v) return;
    filesRef.value.forEach((f, i) => f.position = i);
    const vid = (v.id ?? v.tempId!) as Key;
    const ids = filesRef.value.map(f => (f.id ?? f.tempId!)) as Key[];
    const found = fileReorders.value.find(r => r.version_id === vid);
    if (found) found.idsInOrder = ids; else fileReorders.value.push({ version_id: vid, idsInOrder: ids });
}

/* ========= Size picker (keyboard + 'x' search) ========= */
const openSizeFor = ref<Key | null>(null);
const sizeQuery = ref('');
const highlightIx = ref(0);
function norm(s: string) { return (s ?? '').toLowerCase().replace(/×/g, 'x').replace(/\s+/g, ''); }

function sizeOptionsFor(kind: CategoryKind) {
    return kind === 'video'
        ? videoSizes.value.map(s => ({ id: s.id, label: s.name ?? `${s.width}x${s.height}` }))
        : bannerSizes.value.map(s => ({ id: s.id, label: `${s.width}x${s.height}` }));
}
function filteredSizes(kind: CategoryKind) {
    const q = norm(sizeQuery.value);
    const all = sizeOptionsFor(kind);
    const list = q ? all.filter(o => norm(o.label).includes(q)) : all;
    if (highlightIx.value >= list.length) highlightIx.value = Math.max(0, list.length - 1);
    return list;
}
function openSizePicker(id: Key) {
    openSizeFor.value = openSizeFor.value === id ? null : id;
    sizeQuery.value = '';
    highlightIx.value = 0;
}
function onSizeKeydown(e: KeyboardEvent, list: Array<{ id: number; label: string }>, file: FileRow) {
    if (e.key === 'ArrowDown') { e.preventDefault(); if (highlightIx.value < list.length - 1) highlightIx.value++; }
    else if (e.key === 'ArrowUp') { e.preventDefault(); if (highlightIx.value > 0) highlightIx.value--; }
    else if (e.key === 'Enter') { e.preventDefault(); const opt = list[highlightIx.value]; if (opt) pickSize(file, opt.id); }
    else if (e.key === 'Escape') { openSizeFor.value = null; }
}
function pickSize(file: FileRow, id: number) {
    file.size_id = id;
    markUpdated(file);
    openSizeFor.value = null;
    sizeQuery.value = '';
    highlightIx.value = 0;
}

/* ========= Changeset & Save ========= */
function flatten<T extends Base>(ok: boolean, arr: T[] | undefined) { return ok ? (arr ?? []) : []; }

function buildChangeset(): Changeset {
    const cs: Changeset = {
        etag: etag.value,
        categories: { created: [], updated: [], deleted: [] },
        feedbacks: { created: [], updated: [], deleted: [] },
        feedbackSets: { created: [], updated: [], deleted: [] },
        versions: { created: [], updated: [], deleted: [] },
        banners: { created: [], updated: [], deleted: [] },
        videos: { created: [], updated: [], deleted: [] },
        gifs: { created: [], updated: [], deleted: [] },
        socials: { created: [], updated: [], deleted: [] },
        fileReorders: fileReorders.value.slice(),
    };

    for (const c of (preview.categories ?? [])) {
        if (c._state === 'created') cs.categories.created.push({ tempId: c.tempId, preview_id: c.preview_id, name: c.name });
        else if (c._state === 'updated') cs.categories.updated.push({ id: c.id, name: c.name });
        else if (c._state === 'deleted' && c.id) cs.categories.deleted.push(c.id);

        for (const f of flatten(true, c.feedbacks)) {
            if (f._state === 'created') cs.feedbacks.created.push({ tempId: f.tempId, category_id: (c.id ?? c.tempId), name: f.name });
            else if (f._state === 'updated') cs.feedbacks.updated.push({ id: f.id, name: f.name });
            else if (f._state === 'deleted' && f.id) cs.feedbacks.deleted.push(f.id);

            for (const s of flatten(true, f.feedback_sets)) {
                if (s._state === 'created') cs.feedbackSets.created.push({ tempId: s.tempId, feedback_id: (f.id ?? f.tempId), name: s.name });
                else if (s._state === 'updated') cs.feedbackSets.updated.push({ id: s.id, name: s.name });
                else if (s._state === 'deleted' && s.id) cs.feedbackSets.deleted.push(s.id);

                for (const v of flatten(true, s.versions)) {
                    if (v._state === 'created') cs.versions.created.push({ tempId: v.tempId, feedback_set_id: (s.id ?? s.tempId), name: v.name });
                    else if (v._state === 'updated') cs.versions.updated.push({ id: v.id, name: v.name });
                    else if (v._state === 'deleted' && v.id) cs.versions.deleted.push(v.id);

                    const kind = detectKindForVersion(v);
                    const listAll = getFilesArrayForKind(v, kind) as FileRow[];

                    for (const file of listAll) {
                        if (file._state === 'created') (cs as any)[`${kind}s`].created.push({
                            tempId: file.tempId, version_id: (v.id ?? v.tempId), size_id: file.size_id ?? null, path: file.path ?? '', position: file.position ?? 0
                        });
                        else if (file._state === 'updated') (cs as any)[`${kind}s`].updated.push({
                            id: file.id, size_id: file.size_id ?? null, path: file.path ?? '', position: file.position ?? 0
                        });
                    }
                    for (const file of listAll) if (file._state === 'deleted' && file.id) (cs as any)[`${kind}s`].deleted.push(file.id);
                }
            }
        }
    }
    return cs;
}

const saving = ref(false);
async function saveAll() {
    saving.value = true;
    try {
        const payload = buildChangeset();
        (payload as any).type = 'banner'; // <— tell backend this is a banner run

        const res = await axios.put(route('previews.bulkEdit', { preview: preview.id }), payload);
        // ... SweetAlert as you already have ...
        console.log(res.data);
    } catch (err: any) {
        // ... SweetAlert error as you already have ...
        console.log(err);
    } finally {
        saving.value = false;
    }
}
</script>

<template>

    <Head :title="`Update: ${preview.name}`" />
    <AppLayout :breadcrumbs="[{ title: 'Previews', href: '/previews' }, { title: 'Edit Preview' }]">
        <div class="space-y-4">
            <!-- Top bar -->
            <header class="sticky top-0 z-10">
                <div class="max-w-8xl mx-auto px-2 py-3 flex items-center justify-end">
                    <button
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm shadow-sm"
                        :disabled="saving" @click="saveAll">

                        {{ saving ? 'Saving…' : 'Save' }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>
            </header>

            <main class="max-w-8xl mx-auto px-2">
                <div class="grid grid-cols-12 gap-4">
                    <!-- Left slim sidebar -->
                    <aside class="col-span-12 md:col-span-4 lg:col-span-3">
                        <div class="border rounded-xl p-3 bg-white/70 backdrop-blur">
                            <div class="flex items-center justify-between mb-2">
                                <h2 class="text-sm font-semibold text-slate-700">Structure</h2>
                                <div class="flex gap-1">
                                    <button class="px-2 py-1 text-xs rounded-lg bg-slate-100 hover:bg-slate-200"
                                        @click="addCategory('banner')">+ Banner</button>
                                    <button class="px-2 py-1 text-xs rounded-lg bg-slate-100 hover:bg-slate-200"
                                        @click="addCategory('video')">+ Video</button>
                                    <button class="px-2 py-1 text-xs rounded-lg bg-slate-100 hover:bg-slate-200"
                                        @click="addCategory('gif')">+ Gif</button>
                                    <button class="px-2 py-1 text-xs rounded-lg bg-slate-100 hover:bg-slate-200"
                                        @click="addCategory('social')">+ Social</button>
                                </div>
                            </div>

                            <span class="text-xs text-slate-500">Categories</span>

                            <div v-for="c in notDeleted(preview.categories)" :key="c.tempId"
                                class="mb-3 rounded-lg border bg-white">
                                <div class="p-2 flex items-center gap-2 border-b">
                                    <!-- ALL categories editable -->
                                    <input
                                        class="text-sm w-full border rounded px-2 py-1 focus:ring focus:ring-blue-100"
                                        v-model="c.name" @input="markUpdated(c)"
                                        placeholder="banner / video / gif / social" />
                                    <button class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1"
                                        @click="deleteCategory(c)">Delete</button>
                                </div>

                                <!-- Feedbacks -->
                                <div class="p-2">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-slate-500">Feedbacks</span>
                                        <button class="text-xs px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200"
                                            @click="addFeedback(c)">+ Add</button>
                                    </div>

                                    <div v-for="f in notDeleted(c.feedbacks)" :key="f.tempId"
                                        class="mb-2 rounded border">
                                        <div class="p-2 flex items-center gap-2 border-b">
                                            <input
                                                class="text-sm w-full border rounded px-2 py-1 focus:ring focus:ring-blue-100"
                                                v-model="f.name" @input="markUpdated(f)" placeholder="Feedback name" />
                                            <button class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1"
                                                @click="deleteFeedback(c, f)">Delete</button>
                                        </div>

                                        <!-- Sets -->
                                        <div class="p-2">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs text-slate-500">Sets</span>
                                                <button
                                                    class="text-xs px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200"
                                                    @click="addSet(f)">+ Add</button>
                                            </div>

                                            <div v-for="s in notDeleted(f.feedback_sets)" :key="s.tempId"
                                                class="mb-2 rounded border">
                                                <div class="p-2 flex items-center gap-2 border-b">
                                                    <input
                                                        class="text-xs w-full border rounded px-2 py-1 focus:ring focus:ring-blue-100"
                                                        v-model="s.name" @input="markUpdated(s)"
                                                        placeholder="Set name" />
                                                    <button class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1"
                                                        @click="deleteSet(f, s)">Delete</button>
                                                </div>

                                                <!-- Versions -->
                                                <div class="p-2">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-xs text-slate-500">Versions</span>
                                                        <button
                                                            class="text-xs px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200"
                                                            @click="addVersion(s)">+ Add</button>
                                                    </div>

                                                    <div v-for="v in notDeleted(s.versions)" :key="v.tempId"
                                                        class="flex items-center gap-2 mb-1">
                                                        <input
                                                            class="text-xs w-full border rounded px-2 py-1 focus:ring focus:ring-blue-100"
                                                            v-model="v.name" @input="markUpdated(v)"
                                                            placeholder="Version name" />
                                                        <button
                                                            class="text-xs px-2 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                            @click="selectVersion(v)">Select</button>
                                                        <button
                                                            class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1"
                                                            @click="deleteVersion(s, v)">Delete</button>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- Right: Files panel -->
                    <section class="col-span-12 md:col-span-8 lg:col-span-9">
                        <div v-if="!selectedVersion"
                            class="border rounded-xl p-6 bg-white/70 backdrop-blur text-sm text-slate-500">
                            Select a <span class="font-medium text-slate-700">Version</span> on the left to manage its
                            files.
                            Upload ZIPs, choose sizes, and drag to reorder.
                        </div>

                        <div v-else class="space-y-4">
                            <div class="border rounded-xl p-4 bg-white shadow-sm">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <div class="text-xs text-slate-500">Editing Version</div>
                                        <div class="font-medium text-slate-800">{{ selectedVersion.name }}</div>
                                        <div class="text-[11px] text-slate-400">Kind: {{
                                            detectKindForVersion(selectedVersion) }}</div>
                                    </div>

                                    <label
                                        class="text-xs px-3 py-1.5 rounded-lg bg-slate-900/90 hover:bg-slate-900 text-white cursor-pointer shadow-sm">
                                        Upload ZIPs
                                        <input type="file" accept=".zip" multiple class="hidden"
                                            @change="onUploadZips" />
                                    </label>
                                </div>

                                <draggable v-model="filesRef" item-key="tempId" handle=".handle" class="space-y-2"
                                    @end="onReorder">
                                    <template #item="{ element, index }">
                                        <div class="flex items-center gap-3 border rounded-lg p-2 hover:bg-slate-50">
                                            <span class="handle cursor-grab select-none text-slate-400">≡</span>

                                            <div
                                                class="w-10 h-8 rounded bg-slate-100 flex items-center justify-center text-xs text-slate-500">
                                                {{ index + 1 }}
                                            </div>

                                            <div class="flex-1">
                                                <div class="text-sm font-medium text-slate-800 truncate">
                                                    {{ element.name || element.path || ('#' + (element.id ??
                                                        element.tempId))
                                                    }}
                                                </div>
                                            </div>

                                            <!-- Size picker -->
                                            <div class="relative text-xs w-44">
                                                <button type="button"
                                                    class="w-full border rounded-lg px-2 py-1 text-left hover:bg-slate-50"
                                                    @click="openSizePicker(element.id ?? element.tempId)">
                                                    <span v-if="element.size_id == null" class="opacity-50">Pick a
                                                        size…</span>
                                                    <span v-else>
                                                        <template
                                                            v-if="detectKindForVersion(selectedVersion) === 'video'">
                                                            {{
                                                                (videoSizes.find(x => x.id === element.size_id)?.name)
                                                                ??
                                                                `${videoSizes.find(x => x.id
                                                                    === element.size_id)?.width}x${videoSizes.find(x => x.id ===
                                                                        element.size_id)?.height}`
                                                            }}
                                                        </template>
                                                        <template v-else>
                                                            {{
                                                                bannerSizes.find(x => x.id === element.size_id)?.width
                                                            }}x{{
                                                                bannerSizes.find(x => x.id === element.size_id)?.height
                                                            }}
                                                        </template>
                                                    </span>
                                                </button>

                                                <div v-if="openSizeFor === (element.id ?? element.tempId)"
                                                    class="absolute right-0 z-10 mt-1 w-56 bg-white border rounded-lg shadow-lg">
                                                    <input class="w-full px-2 py-1 border-b text-xs rounded-t-lg"
                                                        v-model="sizeQuery" placeholder="Search 300x250…"
                                                        @keydown.stop="onSizeKeydown($event, filteredSizes(detectKindForVersion(selectedVersion)), element)" />
                                                    <div class="max-h-56 overflow-auto">
                                                        <button
                                                            v-for="(opt, i) in filteredSizes(detectKindForVersion(selectedVersion))"
                                                            :key="opt.id" class="w-full text-left px-2 py-1"
                                                            :class="i === highlightIx ? 'bg-blue-50' : 'hover:bg-slate-50'"
                                                            @mouseenter="highlightIx = i"
                                                            @click="pickSize(element, opt.id)">
                                                            {{ opt.label }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <button class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1"
                                                @click="deleteFile(element)">
                                                Delete
                                            </button>
                                        </div>
                                    </template>
                                </draggable>

                                <div v-if="filesRef.length === 0" class="text-sm text-slate-500">
                                    No files yet. Upload one or more ZIPs to add files for this version.
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Tailwind covers most styling */
</style>