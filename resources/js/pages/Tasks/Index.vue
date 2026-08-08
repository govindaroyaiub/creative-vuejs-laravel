<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import {
    CalendarDays,
    Check,
    CheckCheck,
    GripVertical,
    LayoutGrid,
    LogOut,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    Trash2,
    UserPlus,
    Users,
    X,
} from 'lucide-vue-next';
import type { SweetAlertOptions } from 'sweetalert2';
import Swal from 'sweetalert2';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import draggable from 'vuedraggable';

/** Focus an element as soon as it renders — used by the inline list rename. */
const vFocus = {
    mounted: (el: HTMLElement) => el.focus(),
};

interface UserOption {
    id: number;
    name: string;
    email: string;
}

interface Card {
    id: number;
    list_id: number;
    created_by: number;
    title: string;
    description: string | null;
    due_date: string | null;
    position: number;
    completed_at: string | null;
    created_at: string;
    members?: UserOption[];
    creator?: UserOption;
}

interface BoardList {
    id: number;
    board_id: number;
    name: string;
    position: number;
    // Seeded backbone lists — renameable and reorderable, but not deletable.
    is_protected: boolean;
    tasks: Card[];
}

interface BoardSummary {
    id: number;
    name: string;
    user_id: number;
}

interface BoardDetail extends BoardSummary {
    lists: BoardList[];
    members: UserOption[];
}

/** An archived card. Carries the list it will be restored into. */
interface CompletedCard extends Card {
    list_name: string;
}

const props = withDefaults(
    defineProps<{
        boards: BoardSummary[];
        board: BoardDetail;
        /** Cheap count, always sent — the archive itself loads lazily, see below. */
        completedCount: number;
        /**
         * Both are `Inertia::optional()` server-side: excluded from every normal
         * visit (including the one after every task save) and fetched only when
         * `router.reload({ only: [...] })` asks for them explicitly — otherwise
         * they'd be re-fetched in full on every single task edit.
         */
        completedCards?: CompletedCard[];
        users?: UserOption[];
    }>(),
    { completedCards: () => [], users: () => [] },
);

// Shadow the raw props under the same names: `withDefaults` guarantees these
// are never actually undefined at runtime, but the template's implicit prop
// bindings don't pick up that narrowing, so `completedCards.length` etc. in
// the template would otherwise type-check as possibly undefined.
const completedCards = computed(() => props.completedCards ?? []);
const users = computed(() => props.users ?? []);

const page = usePage<SharedData>();
const authUserId = computed(() => page.props.auth.user.id);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Tasks', href: '/tasks' },
    { title: props.board.name, href: route('tasks.board', props.board.id) },
]);

/**
 * Top-right toast for non-blocking notices. Confirmations stay centred and go
 * through `Swal.fire` directly.
 *
 * Deliberately NOT `Swal.mixin`: app.ts replaces the static `Swal.fire` with a
 * theme-injecting wrapper that delegates to a copy bound to `Swal` itself. A
 * mixin subclass inherits that wrapper, so its merged params never reach the
 * dialog and every "toast" renders as a centred modal. Passing the options to
 * `Swal.fire` in one call goes through the wrapper intact.
 */
const toast = {
    fire: (options: SweetAlertOptions) =>
        Swal.fire({
            ...options,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2400,
            timerProgressBar: true,
            didOpen: (popup) => {
                popup.addEventListener('mouseenter', Swal.stopTimer);
                popup.addEventListener('mouseleave', Swal.resumeTimer);

                // Swal renders its container on <body>, so it knows nothing
                // about the app header. Without this the toast lands on top of
                // the notification bell and user menu.
                const container = popup.closest('.swal2-container') as HTMLElement | null;
                if (container) {
                    container.style.paddingTop = '4.5rem';
                    container.style.paddingRight = '1rem';
                }
            },
        }),
};

const isOwner = computed(() => props.board.user_id === authUserId.value);

// ─── Local board state ────────────────────────────────────────────────────
// Mirrored locally so drags land instantly; the server is still the truth and
// every prop change rebuilds from it.
const lists = ref<BoardList[]>([]);

function buildLists(source: BoardList[]) {
    lists.value = source
        .slice()
        .sort((a, b) => a.position - b.position)
        .map((list) => ({
            ...list,
            tasks: (list.tasks ?? []).slice().sort((a, b) => a.position - b.position),
        }));
}

buildLists(props.board.lists);
watch(
    () => props.board,
    (board) => buildLists(board.lists),
    { deep: true },
);

const cardCount = computed(() => lists.value.reduce((total, list) => total + list.tasks.length, 0));

// ─── Drag persistence ─────────────────────────────────────────────────────
// An empty list collapses to nothing so there is no dead space above "Add a
// card". While a card is in flight it opens up into a visible drop target,
// otherwise there would be nothing to aim at.
const draggingCard = ref(false);

function persistCards() {
    draggingCard.value = false;

    const cards = lists.value.flatMap((list) => list.tasks.map((card, index) => ({ id: card.id, list_id: list.id, position: index })));

    if (!cards.length) return;

    router.post(
        route('tasks.cards.reorder'),
        { cards },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                router.reload();
            },
        },
    );
}

function persistLists() {
    router.post(
        route('tasks.lists.reorder', props.board.id),
        { lists: lists.value.map((list, index) => ({ id: list.id, position: index })) },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                router.reload();
            },
        },
    );
}

// ─── Card composer: one input, Trello style ───────────────────────────────
const composingListId = ref<number | null>(null);
const composerTitle = ref('');
const composerInput = ref<HTMLTextAreaElement | null>(null);

function openComposer(listId: number) {
    composingListId.value = listId;
    composerTitle.value = '';
    nextTick(() => composerInput.value?.focus());
}

function closeComposer() {
    composingListId.value = null;
    composerTitle.value = '';
}

function submitComposer(listId: number) {
    const title = composerTitle.value.trim();
    if (!title) {
        closeComposer();
        return;
    }

    router.post(
        route('tasks.cards.store', listId),
        { title },
        {
            preserveScroll: true,
            // Close on success. The composer only ever appears because the user
            // asked for it, so leaving an empty field open afterwards reads like
            // the page is waiting on them.
            onSuccess: () => closeComposer(),
        },
    );
}

// ─── List composer ────────────────────────────────────────────────────────
const addingList = ref(false);
const newListName = ref('');
const newListInput = ref<HTMLInputElement | null>(null);

function openListComposer() {
    addingList.value = true;
    newListName.value = '';
    nextTick(() => newListInput.value?.focus());
}

function submitListComposer() {
    const name = newListName.value.trim();
    if (!name) {
        addingList.value = false;
        return;
    }

    router.post(
        route('tasks.lists.store', props.board.id),
        { name },
        {
            preserveScroll: true,
            // Same rule as the task composer: close once it has done its job.
            onSuccess: () => {
                newListName.value = '';
                addingList.value = false;
            },
        },
    );
}

// ─── List rename / delete ─────────────────────────────────────────────────
const renamingListId = ref<number | null>(null);
const renameListName = ref('');

function startRenameList(list: BoardList) {
    renamingListId.value = list.id;
    renameListName.value = list.name;
}

function submitRenameList(list: BoardList) {
    const name = renameListName.value.trim();
    renamingListId.value = null;
    if (!name || name === list.name) return;

    router.put(route('tasks.lists.update', list.id), { name }, { preserveScroll: true });
}

async function deleteList(list: BoardList) {
    if (list.is_protected) {
        toast.fire({ icon: 'info', title: `“${list.name}” is a default list` });
        return;
    }

    const result = await Swal.fire({
        title: `Delete “${list.name}”?`,
        text: list.tasks.length ? `${list.tasks.length} task${list.tasks.length === 1 ? '' : 's'} in it will be deleted too.` : 'This list is empty.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Delete list',
    });

    if (!result.isConfirmed) return;

    router.delete(route('tasks.lists.destroy', list.id), {
        preserveScroll: true,
        onSuccess: () => toast.fire({ icon: 'success', title: 'List deleted' }),
    });
}

// ─── Bottom dock ──────────────────────────────────────────────────────────
type DockPanel = 'completed' | 'boards' | null;

const dockPanel = ref<DockPanel>(null);
const completedSearch = ref('');
const loadingCompleted = ref(false);

function toggleDockPanel(panel: Exclude<DockPanel, null>) {
    dockPanel.value = dockPanel.value === panel ? null : panel;
    if (dockPanel.value !== 'completed') {
        completedSearch.value = '';
        return;
    }

    // The archive is `Inertia::optional()` server-side — never sent on a normal
    // visit (including the reload every task save already triggers), only on a
    // partial reload that names it. Fetch it the moment the drawer opens.
    loadingCompleted.value = true;
    router.reload({
        only: ['completedCards'],
        onFinish: () => (loadingCompleted.value = false),
    });
}

/** The archive can grow without bound, so it is filterable. */
const visibleCompleted = computed(() => {
    const term = completedSearch.value.trim().toLowerCase();
    if (!term) return props.completedCards;
    return props.completedCards.filter((card) => card.title.toLowerCase().includes(term) || (card.list_name ?? '').toLowerCase().includes(term));
});

// ─── Completion ───────────────────────────────────────────────────────────
// Completing archives a card: it keeps its list and position server-side, so
// restoring drops it back exactly where it was.
/**
 * `completedCards` is lazy (`Inertia::optional()`, see the props above) — the
 * plain reload every mutation triggers via `back()` never includes it, so
 * `completedCount` updates but the drawer's own list goes stale (or, once it
 * empties out, wrongly shows "Nothing completed yet") the moment anything
 * changes while it's open. Re-fetch it whenever that can happen.
 */
function refreshCompletedArchive() {
    if (dockPanel.value !== 'completed') return;
    router.reload({ only: ['completedCards'] });
}

function completeCard(card: Card) {
    router.put(
        route('tasks.cards.complete', card.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.fire({ icon: 'success', title: 'Task completed' });
                refreshCompletedArchive();
            },
        },
    );
}

/** Complete from the detail panel, which then has nothing left to show. */
function completeFromModal() {
    if (!openCard.value) return;

    const card = openCard.value;
    closeCardDetail();
    completeCard(card);
}

function restoreCard(card: CompletedCard) {
    router.put(
        route('tasks.cards.complete', card.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.fire({ icon: 'success', title: `Restored to ${card.list_name}` });
                refreshCompletedArchive();
            },
        },
    );
}

// ─── Card detail panel ────────────────────────────────────────────────────
const openCard = ref<Card | null>(null);

// The whole card is draggable, so a click and the start of a drag are the same
// gesture until the pointer moves. Compare press and release positions rather
// than guessing from event order — anything past a few pixels was a drag.
const CLICK_SLOP = 5;
let pressedAt: { x: number; y: number } | null = null;

function onCardPointerDown(event: PointerEvent) {
    pressedAt = { x: event.clientX, y: event.clientY };
}

function onCardClick(card: Card, event: MouseEvent) {
    if (pressedAt) {
        const movedX = Math.abs(event.clientX - pressedAt.x);
        const movedY = Math.abs(event.clientY - pressedAt.y);
        pressedAt = null;

        if (movedX > CLICK_SLOP || movedY > CLICK_SLOP) return;
    }

    openCardDetail(card);
}

const cardForm = useForm({
    title: '',
    description: '',
    due_date: '',
});

function openCardDetail(card: Card) {
    openCard.value = card;
    cardForm.clearErrors();
    cardForm.defaults({
        title: card.title,
        description: card.description ?? '',
        due_date: card.due_date ?? '',
    });
    cardForm.reset();
}

function closeCardDetail() {
    openCard.value = null;
}

function saveCard() {
    if (!openCard.value) return;

    cardForm.put(route('tasks.cards.update', openCard.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeCardDetail();
            toast.fire({ icon: 'success', title: 'Task saved' });
        },
    });
}

async function deleteCard(card: Card) {
    const mine = card.created_by === authUserId.value;
    const assigned = (card.members ?? []).some((m) => m.id === authUserId.value);
    const leaving = !mine && assigned;

    const result = await Swal.fire({
        title: leaving ? 'Leave this task?' : 'Delete this task?',
        text: card.title,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: leaving ? 'Leave task' : 'Delete task',
    });

    if (!result.isConfirmed) return;

    router.delete(route('tasks.cards.destroy', card.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeCardDetail();
            toast.fire({ icon: 'success', title: leaving ? 'You left the task' : 'Task deleted' });
            refreshCompletedArchive();
        },
    });
}

// ─── Boards ───────────────────────────────────────────────────────────────
const showBoardModal = ref(false);
const boardModalMode = ref<'create' | 'rename'>('create');
const boardName = ref('');
const boardNameInput = ref<HTMLInputElement | null>(null);

function openCreateBoard() {
    boardModalMode.value = 'create';
    boardName.value = '';
    showBoardModal.value = true;
    dockPanel.value = null;
    nextTick(() => boardNameInput.value?.focus());
}

function openRenameBoard() {
    boardModalMode.value = 'rename';
    boardName.value = props.board.name;
    showBoardModal.value = true;
    dockPanel.value = null;
    nextTick(() => boardNameInput.value?.select());
}

function submitBoardModal() {
    const name = boardName.value.trim();
    if (!name) return;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showBoardModal.value = false;
            toast.fire({
                icon: 'success' as const,
                title: boardModalMode.value === 'create' ? 'Board created' : 'Board renamed',
            });
        },
    };

    if (boardModalMode.value === 'create') {
        router.post(route('tasks.boards.store'), { name }, options);
    } else {
        router.put(route('tasks.boards.update', props.board.id), { name }, options);
    }
}

async function deleteBoard() {
    dockPanel.value = null;

    const result = await Swal.fire({
        title: isOwner.value ? `Delete “${props.board.name}”?` : `Leave “${props.board.name}”?`,
        text: isOwner.value
            ? `All ${lists.value.length} lists and ${cardCount.value} tasks go with it. This cannot be undone.`
            : 'You will lose access to its lists and tasks.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: isOwner.value ? 'Delete board' : 'Leave board',
    });

    if (!result.isConfirmed) return;

    router.delete(route('tasks.boards.destroy', props.board.id), {
        onSuccess: () => toast.fire({ icon: 'success', title: isOwner.value ? 'Board deleted' : 'You left the board' }),
    });
}

// ─── Board members ────────────────────────────────────────────────────────
const showMembersModal = ref(false);
const loadingUsers = ref(false);
const memberForm = useForm({ members: [] as number[] });

function openMembersModal() {
    dockPanel.value = null;
    memberForm.clearErrors();
    memberForm.defaults({
        members: props.board.members.filter((m) => m.id !== props.board.user_id).map((m) => m.id),
    });
    memberForm.reset();
    showMembersModal.value = true;

    // `users` (every other account, invite candidates) is `Inertia::optional()`
    // too — same reasoning as the completed archive: only the owner needs it,
    // and only while this modal is open.
    if (isOwner.value) {
        loadingUsers.value = true;
        router.reload({
            only: ['users'],
            onFinish: () => (loadingUsers.value = false),
        });
    }
}

function toggleBoardMember(userId: number) {
    const next = new Set(memberForm.members);
    if (next.has(userId)) next.delete(userId);
    else next.add(userId);
    memberForm.members = [...next];
}

function saveMembers() {
    memberForm.put(route('tasks.boards.members', props.board.id), {
        preserveScroll: true,
        onSuccess: () => {
            showMembersModal.value = false;
            toast.fire({ icon: 'success', title: 'Members updated' });
        },
    });
}

// ─── Shared helpers ───────────────────────────────────────────────────────
function initials(name: string): string {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

function isOverdue(card: Card): boolean {
    if (!card.due_date || card.completed_at) return false;
    return dayjs(card.due_date).endOf('day').isBefore(dayjs());
}

function dueLabel(card: Card): string {
    const due = dayjs(card.due_date!);
    if (due.isSame(dayjs(), 'day')) return 'Today';
    if (due.isSame(dayjs().add(1, 'day'), 'day')) return 'Tomorrow';
    if (due.isSame(dayjs().subtract(1, 'day'), 'day')) return 'Yesterday';
    return due.format('MMM D');
}

// Escape closes whatever is on top, in that order.
function onKeydown(event: KeyboardEvent) {
    if (event.key !== 'Escape') return;

    if (showBoardModal.value) showBoardModal.value = false;
    else if (showMembersModal.value) showMembersModal.value = false;
    else if (openCard.value) closeCardDetail();
    else if (composingListId.value !== null) closeComposer();
    else if (addingList.value) addingList.value = false;
    else if (dockPanel.value) dockPanel.value = null;
}

/**
 * The "Switch board" panel has no dedicated backdrop like the Completed
 * drawer or the modals do, so a click anywhere outside the dock has nothing
 * to close it. Any click that lands outside `dockRef` while it's open closes
 * it — the click that opens it lands ON a button inside `dockRef`, so this
 * never fires on the same click that opened it.
 *
 * Scoped to `'boards'` only: the Completed drawer already closes itself via
 * its own backdrop, and it renders outside `dockRef` — treating it the same
 * way here would close it the instant you clicked anything inside it.
 */
const dockRef = ref<HTMLElement | null>(null);

function onDocumentClick(event: MouseEvent) {
    if (dockPanel.value !== 'boards') return;
    if (dockRef.value && !dockRef.value.contains(event.target as Node)) {
        dockPanel.value = null;
    }
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    document.addEventListener('click', onDocumentClick);
});
onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
    document.removeEventListener('click', onDocumentClick);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :themed="false">
        <Head :title="`${board.name} · Tasks`" />

        <!-- Fixed-height shell so only the board scrolls sideways -->
        <!--
            min-w-0 keeps this page from widening the sidebar's <main>, which is
            a flex item with min-width:auto. Without it the whole document
            scrolls sideways and takes the app header — notifications, user menu
            — off screen with it.
        -->
        <div class="flex h-[calc(100vh-4rem)] min-w-0 flex-col overflow-hidden">
            <!--
                Board header: name + who's on it, always visible so the board's
                identity isn't hidden behind the bottom dock or a hover tooltip.
            -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 py-3 md:px-6">
                <h1 class="truncate text-lg font-bold">{{ board.name }}</h1>
                <div v-if="board.members.length" class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                    <span v-for="member in board.members" :key="member.id" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-indigo-500 text-[8px] font-semibold text-white">
                            {{ initials(member.name) }}
                        </span>
                        {{ member.name }}
                    </span>
                </div>
            </div>

            <!--
                Lists: the only horizontally scrolling region. The scroller is
                absolutely positioned so its width never feeds back into the
                layout — the box below it is sized purely by the flex column.
            -->
            <div class="relative min-w-0 flex-1">
                <!-- pb leaves room for the dock so the last card is never under it -->
                <div class="absolute inset-0 overflow-x-auto overflow-y-hidden px-4 pb-24 pt-4 md:px-6">
                    <draggable
                        v-model="lists"
                        :group="{ name: 'lists' }"
                        item-key="id"
                        handle=".list-drag"
                        ghost-class="list-ghost"
                        animation="180"
                        class="flex h-full items-start gap-3"
                        @end="persistLists"
                    >
                        <template #item="{ element: list }">
                            <section
                                class="flex max-h-full w-[17rem] shrink-0 flex-col rounded-xl border border-border bg-muted/40 shadow-sm dark:bg-white/[0.02]"
                            >
                                <!-- List header -->
                                <header class="flex items-center gap-1.5 px-2.5 py-2">
                                    <!--
                                        The drag handle only exists on custom lists. `handle=".list-drag"`
                                        on the lists draggable means a list without this element cannot be
                                        picked up at all — the default lists stay put by construction.
                                    -->
                                    <span
                                        v-if="!list.is_protected"
                                        class="list-drag grid h-6 w-4 shrink-0 cursor-grab place-items-center text-muted-foreground opacity-40 transition-opacity hover:opacity-100 active:cursor-grabbing"
                                        title="Drag list"
                                    >
                                        <GripVertical class="h-3.5 w-3.5" />
                                    </span>

                                    <input
                                        v-if="renamingListId === list.id"
                                        v-model="renameListName"
                                        type="text"
                                        class="min-w-0 flex-1 rounded border border-input bg-background px-1.5 py-1 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-foreground/10"
                                        @blur="submitRenameList(list)"
                                        @keydown.enter.prevent="submitRenameList(list)"
                                        @keydown.esc="renamingListId = null"
                                        v-focus
                                    />
                                    <button
                                        v-else
                                        type="button"
                                        class="min-w-0 flex-1 truncate rounded px-1 py-0.5 text-left text-sm font-semibold transition-colors hover:bg-muted"
                                        title="Rename list"
                                        @click="startRenameList(list)"
                                    >
                                        {{ list.name }}
                                    </button>

                                    <span class="shrink-0 rounded-full bg-background px-1.5 py-0.5 text-[10px] tabular-nums text-muted-foreground">
                                        {{ list.tasks.length }}
                                    </span>

                                    <button
                                        v-if="!list.is_protected"
                                        type="button"
                                        class="grid h-6 w-6 shrink-0 place-items-center rounded text-muted-foreground transition-colors hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400"
                                        title="Delete list"
                                        @click="deleteList(list)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" stroke-width="1.5" />
                                    </button>
                                </header>

                                <!-- Cards -->
                                <div class="min-h-0 shrink overflow-y-auto px-2">
                                    <draggable
                                        v-model="list.tasks"
                                        :group="{ name: 'cards' }"
                                        item-key="id"
                                        ghost-class="card-ghost"
                                        drag-class="card-dragging"
                                        filter=".card-no-drag"
                                        :prevent-on-filter="false"
                                        animation="180"
                                        class="space-y-2 rounded-lg transition-all"
                                        :class="
                                            list.tasks.length
                                                ? 'pb-1'
                                                : draggingCard
                                                  ? 'min-h-[3.25rem] border border-dashed border-muted-foreground/40 bg-foreground/[0.03]'
                                                  : 'min-h-0'
                                        "
                                        @start="draggingCard = true"
                                        @end="persistCards"
                                    >
                                        <!--
                                            The whole card is the drag handle. `pointerdown` records where the
                                            press started so a drag is never mistaken for the click that opens
                                            the detail panel.

                                            Keep this comment outside the #item slot: vuedraggable counts
                                            vnodes there and a comment node makes it two children, which
                                            throws "Item slot must have only one child".
                                        -->
                                        <template #item="{ element: card }">
                                            <article
                                                class="group cursor-grab rounded-lg border bg-card p-2.5 shadow-sm ring-1 ring-black/[0.03] transition-all hover:-translate-y-px hover:shadow-md active:cursor-grabbing dark:ring-white/[0.04]"
                                                :class="isOverdue(card) ? 'border-red-500/40' : 'border-border hover:border-muted-foreground/40'"
                                                @pointerdown="onCardPointerDown"
                                                @click="onCardClick(card, $event)"
                                            >
                                                <div class="flex items-start gap-1.5">
                                                    <!--
                                                        One-click complete. `card-no-drag` is in the draggable's
                                                        filter, so pressing it never starts a drag, and
                                                        prevent-on-filter is off so the click still lands.
                                                    -->
                                                    <button
                                                        type="button"
                                                        class="card-no-drag mt-px grid h-5 w-5 shrink-0 cursor-pointer place-items-center rounded-full border border-muted-foreground/50 text-transparent transition-all hover:border-emerald-500 hover:bg-emerald-500 hover:text-white"
                                                        title="Mark complete"
                                                        aria-label="Mark complete"
                                                        @click.stop="completeCard(card)"
                                                    >
                                                        <Check class="h-3 w-3" stroke-width="3" />
                                                    </button>

                                                    <p class="min-w-0 flex-1 break-words text-sm leading-snug">
                                                        {{ card.title }}
                                                    </p>
                                                </div>

                                                <div
                                                    v-if="card.due_date || card.description"
                                                    class="mt-2 flex flex-wrap items-center gap-1.5 pl-[1.625rem]"
                                                >
                                                    <span
                                                        v-if="card.due_date"
                                                        class="inline-flex items-center gap-1 rounded border px-1.5 py-0.5 text-[10px]"
                                                        :class="
                                                            isOverdue(card)
                                                                ? 'border-red-500/50 bg-red-500/10 text-red-600 dark:text-red-400'
                                                                : 'border-transparent text-muted-foreground'
                                                        "
                                                    >
                                                        <CalendarDays class="h-3 w-3" stroke-width="1.5" />
                                                        {{ dueLabel(card) }}
                                                    </span>

                                                    <span
                                                        v-if="card.description"
                                                        class="text-[10px] text-muted-foreground"
                                                        title="This task has a description"
                                                    >
                                                        ≡
                                                    </span>
                                                </div>
                                            </article>
                                        </template>
                                    </draggable>

                                    <!-- Composer: one input, exactly like Trello -->
                                    <div v-if="composingListId === list.id" class="mt-2 pb-2">
                                        <textarea
                                            ref="composerInput"
                                            v-model="composerTitle"
                                            rows="2"
                                            placeholder="Enter a title…"
                                            class="w-full resize-none rounded-lg border border-input bg-card p-2.5 text-sm shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-foreground/10"
                                            @keydown.enter.prevent="submitComposer(list.id)"
                                            @keydown.esc="closeComposer"
                                        ></textarea>
                                        <div class="mt-1.5 flex items-center gap-2">
                                            <button
                                                type="button"
                                                class="rounded-md bg-foreground px-3 py-1.5 text-xs font-semibold text-background shadow-sm transition-shadow hover:shadow"
                                                @click="submitComposer(list.id)"
                                            >
                                                Add
                                            </button>
                                            <button
                                                type="button"
                                                class="grid h-7 w-7 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                aria-label="Cancel"
                                                @click="closeComposer"
                                            >
                                                <X class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    v-if="composingListId !== list.id"
                                    type="button"
                                    class="group/add mx-2 mb-2 mt-1 flex items-center gap-2 rounded-lg border border-dashed border-border bg-card/60 px-2.5 py-2 text-left text-xs font-semibold text-muted-foreground shadow-sm transition-all hover:-translate-y-px hover:border-solid hover:border-indigo-500/50 hover:bg-indigo-500/10 hover:text-indigo-700 hover:shadow-md dark:hover:text-indigo-300"
                                    @click="openComposer(list.id)"
                                >
                                    <span
                                        class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-muted text-muted-foreground transition-colors group-hover/add:bg-indigo-500 group-hover/add:text-white"
                                    >
                                        <Plus class="h-3 w-3" stroke-width="2.5" />
                                    </span>
                                    Add task
                                </button>
                            </section>
                        </template>

                        <!-- Add-list column pinned after the lists -->
                        <template #footer>
                            <div class="w-[17rem] shrink-0">
                                <div v-if="addingList" class="rounded-xl border border-border bg-card p-2 shadow-sm">
                                    <input
                                        ref="newListInput"
                                        v-model="newListName"
                                        type="text"
                                        placeholder="List name…"
                                        class="w-full rounded-lg border border-input bg-background px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-foreground/10"
                                        @keydown.enter.prevent="submitListComposer"
                                        @keydown.esc="addingList = false"
                                    />
                                    <div class="mt-1.5 flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded-md bg-foreground px-3 py-1.5 text-xs font-semibold text-background shadow-sm"
                                            @click="submitListComposer"
                                        >
                                            Add list
                                        </button>
                                        <button
                                            type="button"
                                            class="grid h-7 w-7 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                            aria-label="Cancel"
                                            @click="addingList = false"
                                        >
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <button
                                    v-else
                                    type="button"
                                    class="flex w-full items-center gap-2 rounded-xl border border-dashed border-border bg-muted/30 px-3 py-2.5 text-sm text-muted-foreground transition-colors hover:border-muted-foreground/50 hover:text-foreground"
                                    @click="openListComposer"
                                >
                                    <Plus class="h-4 w-4" />
                                    Add list
                                </button>
                            </div>
                        </template>
                    </draggable>
                </div>
            </div>
        </div>

        <!-- ─── Bottom dock ───────────────────────────────────────────────
            Fixed, centred, floating above the board. Everything that is about
            the board rather than about a card lives here: the archive and the
            board itself (switching, actions, members).
        -->
        <div ref="dockRef" class="pointer-events-none fixed bottom-5 left-0 right-0 z-40 flex flex-col items-center gap-2 px-4">
            <!-- Boards panel -->
            <div
                v-if="dockPanel === 'boards'"
                class="dock-panel pointer-events-auto flex max-h-[60vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-border bg-card shadow-2xl"
            >
                <header class="flex items-center justify-between gap-3 border-b border-border px-4 py-3">
                    <div class="min-w-0">
                        <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider">
                            <LayoutGrid class="h-4 w-4 text-muted-foreground" stroke-width="1.5" />
                            Boards
                        </h2>
                        <p class="mt-0.5 truncate text-[11px] text-muted-foreground">
                            {{ board.name }} · {{ lists.length }} list{{ lists.length === 1 ? '' : 's' }} · {{ cardCount }} task{{
                                cardCount === 1 ? '' : 's'
                            }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="grid h-8 w-8 shrink-0 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="Close"
                        @click="dockPanel = null"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </header>

                <div class="flex-1 overflow-y-auto">
                    <!-- Switch board -->
                    <p class="px-4 pb-1 pt-3 text-[10px] uppercase tracking-[0.14em] text-muted-foreground">Switch board</p>
                    <Link
                        v-for="item in boards"
                        :key="item.id"
                        :href="route('tasks.board', item.id)"
                        class="flex items-center justify-between gap-2 px-4 py-2 text-sm transition-colors hover:bg-muted"
                        :class="item.id === board.id ? 'bg-muted/70 font-semibold' : ''"
                        @click="dockPanel = null"
                    >
                        <span class="truncate">{{ item.name }}</span>
                        <Check v-if="item.id === board.id" class="h-3.5 w-3.5 shrink-0" />
                        <span v-else-if="item.user_id !== authUserId" class="shrink-0 text-[10px] uppercase tracking-wider text-muted-foreground">
                            shared
                        </span>
                    </Link>

                    <!-- Members, moved down from the old top-right corner -->
                    <div class="mt-2 border-t border-border">
                        <p class="px-4 pb-1 pt-3 text-[10px] uppercase tracking-[0.14em] text-muted-foreground">Members</p>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm transition-colors hover:bg-muted"
                            @click="openMembersModal"
                        >
                            <span class="flex -space-x-1.5">
                                <span
                                    v-for="member in board.members.slice(0, 4)"
                                    :key="member.id"
                                    class="grid h-6 w-6 place-items-center rounded-full bg-indigo-500 text-[8px] font-semibold text-white ring-2 ring-card"
                                    :title="member.name"
                                >
                                    {{ initials(member.name) }}
                                </span>
                            </span>
                            <span class="min-w-0 flex-1 truncate">
                                {{ board.members.map((m) => m.name).join(', ') }}
                            </span>
                            <UserPlus v-if="isOwner" class="h-3.5 w-3.5 shrink-0 text-muted-foreground" stroke-width="1.5" />
                        </button>
                    </div>

                    <!-- Board actions -->
                    <div class="mt-2 border-t border-border pb-2">
                        <p class="px-4 pb-1 pt-3 text-[10px] uppercase tracking-[0.14em] text-muted-foreground">Actions</p>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 px-4 py-2 text-sm transition-colors hover:bg-muted"
                            @click="openCreateBoard"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            New board
                        </button>
                        <button
                            v-if="isOwner"
                            type="button"
                            class="flex w-full items-center gap-2 px-4 py-2 text-sm transition-colors hover:bg-muted"
                            @click="openRenameBoard"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                            Rename board
                        </button>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 transition-colors hover:bg-red-500/10 dark:text-red-400"
                            @click="deleteBoard"
                        >
                            <component :is="isOwner ? Trash2 : LogOut" class="h-3.5 w-3.5" />
                            {{ isOwner ? 'Delete board' : 'Leave board' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- The pill itself -->
            <div
                class="pointer-events-auto flex items-center gap-1 rounded-full border border-border bg-card/95 p-1 shadow-2xl ring-1 ring-black/5 backdrop-blur supports-[backdrop-filter]:bg-card/80 dark:ring-white/10"
            >
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-full px-3.5 py-2 text-xs font-semibold transition-colors"
                    :class="
                        dockPanel === 'completed' ? 'bg-foreground text-background' : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    :aria-pressed="dockPanel === 'completed'"
                    @click="toggleDockPanel('completed')"
                >
                    <CheckCheck class="h-4 w-4" stroke-width="1.75" />
                    Completed
                    <span
                        v-if="completedCount"
                        class="rounded-full px-1.5 py-0.5 text-[10px] tabular-nums"
                        :class="dockPanel === 'completed' ? 'bg-background/20' : 'bg-muted'"
                    >
                        {{ completedCount }}
                    </span>
                </button>

                <span aria-hidden="true" class="h-5 w-px bg-border" />

                <!-- Board name now lives in the page header up top, so the pill
                     just names the action rather than repeating it. -->
                <button
                    type="button"
                    class="flex max-w-[14rem] items-center gap-2 rounded-full px-3.5 py-2 text-xs font-semibold transition-colors"
                    :class="dockPanel === 'boards' ? 'bg-foreground text-background' : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                    :aria-pressed="dockPanel === 'boards'"
                    @click="toggleDockPanel('boards')"
                >
                    <LayoutGrid class="h-4 w-4 shrink-0" stroke-width="1.75" />
                    <span class="truncate">Switch board</span>
                </button>
            </div>
        </div>

        <!-- ─── Completed drawer ──────────────────────────────────────────
            Slides in from the right and runs the full height of the viewport,
            so the archive keeps working when there are hundreds of cards —
            a panel above the dock would have run out of room.
        -->
        <div v-if="dockPanel === 'completed'" class="fixed inset-0 z-[55] bg-black/40 backdrop-blur-sm" @click="dockPanel = null" />

        <aside
            v-if="dockPanel === 'completed'"
            role="dialog"
            aria-modal="true"
            aria-label="Completed tasks"
            class="completed-drawer fixed bottom-0 right-0 top-0 z-[60] flex w-full max-w-md flex-col border-l border-border bg-card shadow-2xl"
        >
            <header class="flex items-start justify-between gap-3 border-b border-border px-4 py-4">
                <div class="min-w-0">
                    <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider">
                        <CheckCheck class="h-4 w-4 text-emerald-500" stroke-width="1.5" />
                        Completed
                    </h2>
                    <p class="mt-0.5 truncate text-[11px] text-muted-foreground">
                        {{ completedCount }} task{{ completedCount === 1 ? '' : 's' }} archived from {{ board.name }}
                    </p>
                </div>
                <button
                    type="button"
                    class="grid h-8 w-8 shrink-0 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    aria-label="Close"
                    @click="dockPanel = null"
                >
                    <X class="h-4 w-4" />
                </button>
            </header>

            <!-- Search, so a long archive stays usable -->
            <div v-if="completedCount > 6" class="border-b border-border px-4 py-3">
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground"
                        stroke-width="1.5"
                    />
                    <input
                        v-model="completedSearch"
                        type="search"
                        placeholder="Search completed tasks"
                        class="w-full rounded-lg border border-input bg-background py-2 pl-9 pr-3 text-xs shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-foreground/10"
                    />
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-2">
                <div
                    v-for="card in visibleCompleted"
                    :key="card.id"
                    class="group flex items-start gap-2.5 rounded-lg border border-transparent px-2.5 py-2.5 transition-colors hover:border-border hover:bg-muted/50"
                >
                    <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-500 text-white">
                        <Check class="h-3 w-3" stroke-width="3" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block break-words text-sm text-muted-foreground line-through">{{ card.title }}</span>
                        <span class="mt-0.5 block text-[11px] text-muted-foreground">
                            {{ card.list_name }}
                            <template v-if="card.completed_at"> · {{ dayjs(card.completed_at).format('MMM D') }}</template>
                            <template v-if="card.creator"> · {{ card.creator.name }}</template>
                        </span>
                    </span>

                    <span class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity focus-within:opacity-100 group-hover:opacity-100">
                        <button
                            type="button"
                            class="grid h-7 w-7 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            :title="`Restore to ${card.list_name}`"
                            @click="restoreCard(card)"
                        >
                            <RotateCcw class="h-3.5 w-3.5" stroke-width="1.5" />
                        </button>
                        <button
                            type="button"
                            class="grid h-7 w-7 place-items-center rounded-md text-red-600 transition-colors hover:bg-red-500/10 dark:text-red-400"
                            title="Delete permanently"
                            @click="deleteCard(card)"
                        >
                            <Trash2 class="h-3.5 w-3.5" stroke-width="1.5" />
                        </button>
                    </span>
                </div>

                <p v-if="loadingCompleted" class="px-3 py-12 text-center text-[11px] uppercase tracking-[0.12em] text-muted-foreground">
                    Loading…
                </p>
                <p v-else-if="!completedCards.length" class="px-3 py-12 text-center text-[11px] uppercase tracking-[0.12em] text-muted-foreground">
                    Nothing completed yet
                </p>
                <p v-else-if="!visibleCompleted.length" class="px-3 py-12 text-center text-[11px] text-muted-foreground">
                    No match for “{{ completedSearch }}”
                </p>
            </div>
        </aside>
        <!-- ─── Card detail ───────────────────────────────────────────── -->
        <div
            v-if="openCard"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 backdrop-blur-sm sm:items-center sm:p-4"
            @click.self="closeCardDetail"
        >
            <form
                role="dialog"
                aria-modal="true"
                aria-label="Task detail"
                class="task-modal flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl border border-border bg-card shadow-2xl sm:max-h-[88vh] sm:max-w-lg sm:rounded-2xl"
                @submit.prevent="saveCard"
            >
                <header class="flex items-start gap-3 border-b border-border px-5 py-4">
                    <div class="min-w-0 flex-1">
                        <label for="card-title" class="sr-only">Title</label>
                        <input
                            id="card-title"
                            v-model="cardForm.title"
                            type="text"
                            required
                            maxlength="255"
                            class="w-full border-0 bg-transparent p-0 text-base font-semibold leading-snug focus:outline-none focus:ring-0"
                        />
                        <p class="mt-0.5 text-[11px] text-muted-foreground">in {{ lists.find((l) => l.id === openCard!.list_id)?.name }}</p>
                        <p v-if="cardForm.errors.title" class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ cardForm.errors.title }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="grid h-8 w-8 shrink-0 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="Close"
                        @click="closeCardDetail"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </header>

                <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5">
                    <div>
                        <label for="card-description" class="mb-1.5 block text-[11px] font-medium uppercase tracking-[0.14em] text-muted-foreground">
                            Description
                        </label>
                        <textarea
                            id="card-description"
                            v-model="cardForm.description"
                            rows="4"
                            placeholder="Add detail, links, acceptance criteria…"
                            class="w-full resize-y rounded-lg border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-foreground/10"
                        ></textarea>
                    </div>

                    <div>
                        <label for="card-due" class="mb-1.5 block text-[11px] font-medium uppercase tracking-[0.14em] text-muted-foreground">
                            Due date
                        </label>
                        <div class="flex items-center gap-2">
                            <input
                                id="card-due"
                                v-model="cardForm.due_date"
                                type="date"
                                class="rounded-lg border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-foreground/10"
                            />
                            <button
                                v-if="cardForm.due_date"
                                type="button"
                                class="inline-flex items-center gap-1 text-[11px] text-muted-foreground transition-colors hover:text-foreground"
                                @click="cardForm.due_date = ''"
                            >
                                <X class="h-3 w-3" />
                                Clear
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="mb-1.5 text-[11px] font-medium uppercase tracking-[0.14em] text-muted-foreground">Created by</p>
                        <div class="flex items-center gap-3 rounded-lg border border-border bg-muted/40 px-3 py-2.5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-indigo-500 text-[10px] font-semibold text-white">
                                {{ initials(openCard!.creator?.name ?? '?') }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">
                                    {{ openCard!.creator?.name ?? 'Unknown' }}
                                    <span v-if="openCard!.created_by === authUserId" class="font-normal text-muted-foreground">(you)</span>
                                </span>
                                <span class="block truncate text-[11px] text-muted-foreground">
                                    {{ openCard!.creator?.email }}
                                </span>
                            </span>
                            <span v-if="openCard!.created_at" class="shrink-0 text-[11px] text-muted-foreground">
                                {{ dayjs(openCard!.created_at).format('MMM D, YYYY') }}
                            </span>
                        </div>
                    </div>
                </div>

                <footer class="flex items-center justify-between gap-3 border-t border-border px-5 py-3">
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-500/10 dark:text-emerald-400"
                            title="Archive this task into Completed"
                            @click="completeFromModal"
                        >
                            <CheckCheck class="h-3.5 w-3.5" />
                            Complete
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2 py-2 text-xs text-red-600 transition-colors hover:bg-red-500/10 dark:text-red-400"
                            @click="deleteCard(openCard!)"
                        >
                            <component :is="openCard!.created_by === authUserId ? Trash2 : LogOut" class="h-3.5 w-3.5" />
                            {{ openCard!.created_by === authUserId ? 'Delete' : 'Leave' }}
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-border px-4 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            @click="closeCardDetail"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="cardForm.processing || !cardForm.title.trim()"
                            class="rounded-lg bg-foreground px-4 py-2 text-sm font-semibold text-background shadow-md transition-all hover:shadow-lg disabled:opacity-40"
                        >
                            {{ cardForm.processing ? 'Saving…' : 'Save' }}
                        </button>
                    </div>
                </footer>
            </form>
        </div>

        <!-- ─── Board create / rename ─────────────────────────────────── -->
        <div
            v-if="showBoardModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click.self="showBoardModal = false"
        >
            <form
                role="dialog"
                aria-modal="true"
                class="task-modal w-full max-w-sm rounded-2xl border border-border bg-card p-5 shadow-2xl"
                @submit.prevent="submitBoardModal"
            >
                <h2 class="text-sm font-bold uppercase tracking-wider">
                    {{ boardModalMode === 'create' ? 'New board' : 'Rename board' }}
                </h2>
                <p v-if="boardModalMode === 'create'" class="mt-1 text-[11px] text-muted-foreground">
                    Starts with Today, Tomorrow, This week and Later — rename or delete them freely.
                </p>

                <input
                    ref="boardNameInput"
                    v-model="boardName"
                    type="text"
                    required
                    maxlength="255"
                    placeholder="Board name"
                    class="mt-4 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-foreground/10"
                />

                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-border px-4 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        @click="showBoardModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="!boardName.trim()"
                        class="rounded-lg bg-foreground px-4 py-2 text-sm font-semibold text-background shadow-md transition-all hover:shadow-lg disabled:opacity-40"
                    >
                        {{ boardModalMode === 'create' ? 'Create' : 'Rename' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- ─── Board members ─────────────────────────────────────────── -->
        <div
            v-if="showMembersModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click.self="showMembersModal = false"
        >
            <form
                role="dialog"
                aria-modal="true"
                class="task-modal flex max-h-[85vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-border bg-card shadow-2xl"
                @submit.prevent="saveMembers"
            >
                <header class="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
                    <div>
                        <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider">
                            <Users class="h-4 w-4 text-muted-foreground" stroke-width="1.5" />
                            Board members
                        </h2>
                        <p class="mt-0.5 text-[11px] text-muted-foreground">Members see every list and task on this board.</p>
                    </div>
                    <button
                        type="button"
                        class="grid h-8 w-8 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="Close"
                        @click="showMembersModal = false"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </header>

                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <div class="mb-3 flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-3 py-2">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-indigo-500 text-[9px] font-semibold text-white">
                            {{ initials(board.members.find((m) => m.id === board.user_id)?.name ?? '?') }}
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm">
                            {{ board.members.find((m) => m.id === board.user_id)?.name }}
                        </span>
                        <span class="text-[10px] uppercase tracking-wider text-muted-foreground">owner</span>
                    </div>

                    <template v-if="isOwner">
                        <p v-if="loadingUsers" class="rounded-lg border border-dashed border-border px-3 py-3 text-[11px] text-muted-foreground">
                            Loading…
                        </p>
                        <div v-else-if="users.length" class="overflow-hidden rounded-lg border border-input">
                            <label
                                v-for="user in users"
                                :key="user.id"
                                class="flex cursor-pointer items-center gap-3 border-b border-border/60 px-3 py-2 text-sm transition-colors last:border-b-0 hover:bg-muted"
                                :class="memberForm.members.includes(user.id) ? 'bg-muted/60' : ''"
                            >
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 shrink-0 cursor-pointer rounded border-input accent-foreground"
                                    :checked="memberForm.members.includes(user.id)"
                                    @change="toggleBoardMember(user.id)"
                                />
                                <span
                                    class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-[9px] font-semibold"
                                    :class="memberForm.members.includes(user.id) ? 'bg-indigo-500 text-white' : 'bg-muted text-muted-foreground'"
                                >
                                    {{ initials(user.name) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate">{{ user.name }}</span>
                                    <span class="block truncate text-[11px] text-muted-foreground">{{ user.email }}</span>
                                </span>
                            </label>
                        </div>
                        <p v-else class="rounded-lg border border-dashed border-border px-3 py-3 text-[11px] text-muted-foreground">
                            No other users to add.
                        </p>
                        <p class="mt-2 text-[11px] text-muted-foreground">Removing someone also unassigns them from this board's tasks.</p>
                    </template>

                    <template v-else>
                        <div
                            v-for="member in board.members.filter((m) => m.id !== board.user_id)"
                            :key="member.id"
                            class="flex items-center gap-3 border-b border-border/60 px-1 py-2 text-sm last:border-b-0"
                        >
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-indigo-500 text-[9px] font-semibold text-white">
                                {{ initials(member.name) }}
                            </span>
                            <span class="min-w-0 flex-1 truncate">{{ member.name }}</span>
                            <span v-if="member.id === authUserId" class="text-[10px] uppercase tracking-wider text-muted-foreground"> you </span>
                        </div>
                        <p class="mt-3 text-[11px] text-muted-foreground">Only the board owner can add or remove members.</p>
                    </template>
                </div>

                <footer v-if="isOwner" class="flex justify-end gap-2 border-t border-border px-5 py-3">
                    <button
                        type="button"
                        class="rounded-lg border border-border px-4 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        @click="showMembersModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="memberForm.processing"
                        class="rounded-lg bg-foreground px-4 py-2 text-sm font-semibold text-background shadow-md transition-all hover:shadow-lg disabled:opacity-40"
                    >
                        {{ memberForm.processing ? 'Saving…' : 'Save members' }}
                    </button>
                </footer>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.card-ghost {
    opacity: 0.35;
    border-style: dashed;
    box-shadow: none;
}

.card-dragging {
    box-shadow:
        0 12px 24px -8px rgb(0 0 0 / 0.25),
        0 4px 8px -4px rgb(0 0 0 / 0.15);
}

.list-ghost {
    opacity: 0.4;
}

.task-modal,
.dock-panel {
    animation: task-modal-in 180ms cubic-bezier(0.22, 1, 0.36, 1);
}

/* Archive slides in from the right edge. */
.completed-drawer {
    animation: completed-drawer-in 220ms cubic-bezier(0.22, 1, 0.36, 1);
}
@keyframes completed-drawer-in {
    from {
        transform: translateX(100%);
    }
    to {
        transform: translateX(0);
    }
}
@keyframes task-modal-in {
    from {
        opacity: 0;
        transform: translateY(12px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
@media (prefers-reduced-motion: reduce) {
    .task-modal,
    .dock-panel,
    .completed-drawer {
        animation: none;
    }
}
</style>
