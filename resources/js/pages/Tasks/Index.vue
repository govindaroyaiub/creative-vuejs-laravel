<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import {
    CalendarDays,
    CheckCircle2,
    Circle,
    CirclePlus,
    GripVertical,
    LogOut,
    Pencil,
    Search,
    Timer,
    Trash2,
    UserRound,
    Users,
    X,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import draggable from 'vuedraggable';

type TaskStatus = 'todo' | 'in_progress' | 'done';
type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';

interface UserOption {
    id: number;
    name: string;
    email: string;
}

interface TaskItem {
    id: number;
    created_by: number;
    title: string;
    description: string | null;
    status: TaskStatus;
    priority: TaskPriority;
    due_date: string | null;
    position: number;
    completed_at: string | null;
    created_at: string;
    creator?: UserOption;
    // Everyone the task is shared with, including the current user.
    participants?: UserOption[];
}

const props = defineProps<{
    tasks: TaskItem[];
    users: UserOption[];
}>();

const page = usePage<SharedData>();
const authUserId = computed(() => page.props.auth.user.id);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tasks', href: '/tasks' }];

// Result notices are non-blocking, so they go to the top-right as toasts.
// The delete confirmation stays a centered modal — it needs a real decision.
const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2600,
    timerProgressBar: true,
    didOpen: (popup) => {
        popup.addEventListener('mouseenter', Swal.stopTimer);
        popup.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

interface ColumnDef {
    key: TaskStatus;
    label: string;
    icon: typeof Circle;
    // Accent rail across the top of the column — the only colour carrying
    // meaning here, so each column stays identifiable at a glance.
    rail: string;
    dot: string;
}

const COLUMNS: ColumnDef[] = [
    { key: 'todo', label: 'To Do', icon: Circle, rail: 'bg-zinc-400/70', dot: 'bg-zinc-400' },
    { key: 'in_progress', label: 'In Progress', icon: Timer, rail: 'bg-amber-500/70', dot: 'bg-amber-500' },
    { key: 'done', label: 'Done', icon: CheckCircle2, rail: 'bg-emerald-500/70', dot: 'bg-emerald-500' },
];

const PRIORITIES: TaskPriority[] = ['low', 'medium', 'high', 'urgent'];
const PRIORITY_FILTERS: (TaskPriority | 'all')[] = ['all', 'low', 'medium', 'high', 'urgent'];

// Board state is local so drag-and-drop feels instant; the server is the
// source of truth and every prop change rebuilds it.
const board = ref<Record<TaskStatus, TaskItem[]>>({ todo: [], in_progress: [], done: [] });

function buildBoard(tasks: TaskItem[]) {
    const next: Record<TaskStatus, TaskItem[]> = { todo: [], in_progress: [], done: [] };
    for (const task of tasks) {
        (next[task.status] ?? next.todo).push(task);
    }
    for (const key of Object.keys(next) as TaskStatus[]) {
        next[key].sort((a, b) => a.position - b.position);
    }
    board.value = next;
}

buildBoard(props.tasks);
watch(() => props.tasks, buildBoard, { deep: true });

// ─── Filtering ────────────────────────────────────────────────────────────
// Dragging writes positions for every visible card, so it is disabled while a
// filter hides part of a column — otherwise hidden cards would get stale
// positions written over them.
const search = ref('');
const priorityFilter = ref<TaskPriority | 'all'>('all');
const isFiltering = computed(() => search.value.trim() !== '' || priorityFilter.value !== 'all');

function visibleIn(status: TaskStatus): TaskItem[] {
    const term = search.value.trim().toLowerCase();
    return board.value[status].filter((task) => {
        if (priorityFilter.value !== 'all' && task.priority !== priorityFilter.value) return false;
        if (!term) return true;
        return task.title.toLowerCase().includes(term) || (task.description ?? '').toLowerCase().includes(term);
    });
}

const totalCount = computed(() => props.tasks.length);
const openCount = computed(() => props.tasks.filter((t) => t.status !== 'done').length);
const overdueCount = computed(() => props.tasks.filter((t) => isOverdue(t)).length);

// ─── Drag persistence ─────────────────────────────────────────────────────
const dragging = ref(false);

function persistOrder() {
    dragging.value = false;

    const payload = (Object.keys(board.value) as TaskStatus[]).flatMap((status) =>
        board.value[status].map((task, index) => ({
            id: task.id,
            status,
            position: index,
        })),
    );

    router.post(
        route('tasks.reorder'),
        { tasks: payload },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                toast.fire({ icon: 'error', title: 'Failed to save the new order' });
                router.reload({ only: ['tasks'] });
            },
        },
    );
}

// ─── Create / edit modal ──────────────────────────────────────────────────
const showModal = ref(false);
const editingId = ref<number | null>(null);

// `participants` holds the *other* people the task is shared with. You are
// always a participant of your own tasks, so you never appear in the list.
const form = useForm({
    title: '',
    description: '',
    status: 'todo' as TaskStatus,
    priority: 'medium' as TaskPriority,
    due_date: '',
    participants: [] as number[],
});

function openCreate(status: TaskStatus = 'todo') {
    editingId.value = null;
    form.clearErrors();
    form.defaults({
        title: '',
        description: '',
        status,
        priority: 'medium',
        due_date: '',
        participants: [],
    });
    form.reset();
    showModal.value = true;
}

function openEdit(task: TaskItem) {
    editingId.value = task.id;
    form.clearErrors();
    form.defaults({
        title: task.title,
        description: task.description ?? '',
        status: task.status,
        priority: task.priority,
        due_date: task.due_date ?? '',
        participants: othersOf(task).map((u) => u.id),
    });
    form.reset();
    showModal.value = true;
}

function toggleParticipant(userId: number) {
    const next = new Set(form.participants);
    if (next.has(userId)) {
        next.delete(userId);
    } else {
        next.add(userId);
    }
    form.participants = [...next];
}

// ─── Modal behaviour ──────────────────────────────────────────────────────
const titleInput = ref<HTMLInputElement | null>(null);
const participantSearch = ref('');

const filteredUsers = computed(() => {
    const term = participantSearch.value.trim().toLowerCase();
    if (!term) return props.users;
    return props.users.filter((user) => user.name.toLowerCase().includes(term) || user.email.toLowerCase().includes(term));
});

const selectedUsers = computed(() => props.users.filter((user) => form.participants.includes(user.id)));

function onModalKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        event.preventDefault();
        closeModal();
        return;
    }
    // ⌘/Ctrl + Enter saves from anywhere in the form, including the textarea.
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
        event.preventDefault();
        if (!form.processing) submit();
    }
}

watch(showModal, (open) => {
    if (open) {
        participantSearch.value = '';
        document.addEventListener('keydown', onModalKeydown);
        document.body.style.overflow = 'hidden';
        nextTick(() => titleInput.value?.focus());
    } else {
        document.removeEventListener('keydown', onModalKeydown);
        document.body.style.overflow = '';
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', onModalKeydown);
    document.body.style.overflow = '';
});

// ─── Due-date shortcuts ───────────────────────────────────────────────────
const DUE_PRESETS: { label: string; days: number }[] = [
    { label: 'Today', days: 0 },
    { label: 'Tomorrow', days: 1 },
    { label: 'Next week', days: 7 },
];

function presetDate(days: number): string {
    return dayjs().add(days, 'day').format('YYYY-MM-DD');
}

function setDue(days: number | null) {
    form.due_date = days === null ? '' : presetDate(days);
}

function dueIs(days: number): boolean {
    return form.due_date === presetDate(days);
}

function closeModal() {
    showModal.value = false;
    editingId.value = null;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            const wasEdit = editingId.value !== null;
            closeModal();
            toast.fire({
                icon: 'success',
                title: wasEdit ? 'Task updated' : 'Task created',
            });
        },
    };

    if (editingId.value) {
        form.put(route('tasks.update', editingId.value), options);
    } else {
        form.post(route('tasks.store'), options);
    }
}

// ─── Row actions ──────────────────────────────────────────────────────────
// Only the creator can delete a shared task for everyone; other participants
// leave it instead, which just detaches them.
async function deleteTask(task: TaskItem) {
    const owns = isCreator(task);

    const result = await Swal.fire({
        title: owns ? 'Delete this task?' : 'Leave this task?',
        text: owns && isShared(task) ? `${task.title} — this removes it for everyone it is shared with.` : task.title,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: owns ? 'Yes, delete it' : 'Yes, leave it',
    });

    if (!result.isConfirmed) return;

    router.delete(route('tasks.destroy', task.id), {
        preserveScroll: true,
        onSuccess: () => toast.fire({ icon: 'success', title: owns ? 'Task deleted' : 'You left the task' }),
        onError: () => toast.fire({ icon: 'error', title: owns ? 'Failed to delete the task' : 'Failed to leave the task' }),
    });
}

function toggleDone(task: TaskItem) {
    router.put(route('tasks.update-status', task.id), { status: task.status === 'done' ? 'todo' : 'done' }, { preserveScroll: true });
}

// ─── Presentation helpers ─────────────────────────────────────────────────
const PRIORITY_PILL: Record<TaskPriority, string> = {
    low: 'border-border text-muted-foreground',
    medium: 'border-sky-500/40 text-sky-600 dark:text-sky-400',
    high: 'border-amber-500/50 text-amber-600 dark:text-amber-400',
    urgent: 'border-red-500/60 text-red-600 dark:text-red-400',
};

// Priority picker swatches — colour matches the card edge bar, so what you
// choose in the modal is what you see on the board.
const PRIORITY_CHOICE: Record<TaskPriority, { dot: string; active: string }> = {
    low: { dot: 'bg-zinc-400', active: 'border-zinc-400 bg-zinc-400/10' },
    medium: { dot: 'bg-sky-500', active: 'border-sky-500 bg-sky-500/10 text-sky-700 dark:text-sky-300' },
    high: { dot: 'bg-amber-500', active: 'border-amber-500 bg-amber-500/10 text-amber-700 dark:text-amber-300' },
    urgent: { dot: 'bg-red-500', active: 'border-red-500 bg-red-500/10 text-red-700 dark:text-red-300' },
};

// Left edge bar on each card — priority readable without reading the pill.
const PRIORITY_EDGE: Record<TaskPriority, string> = {
    low: 'border-l-zinc-300 dark:border-l-zinc-700',
    medium: 'border-l-sky-500',
    high: 'border-l-amber-500',
    urgent: 'border-l-red-500',
};

/** Participants other than the current user — who the task is shared with. */
function othersOf(task: TaskItem): UserOption[] {
    return (task.participants ?? []).filter((u) => u.id !== authUserId.value);
}

function isShared(task: TaskItem): boolean {
    return othersOf(task).length > 0;
}

function isCreator(task: TaskItem): boolean {
    return task.created_by === authUserId.value;
}

function initials(name: string): string {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

function isOverdue(task: TaskItem): boolean {
    if (!task.due_date || task.status === 'done') return false;
    return dayjs(task.due_date).endOf('day').isBefore(dayjs());
}

function dueLabel(task: TaskItem): string {
    const due = dayjs(task.due_date!);
    if (due.isSame(dayjs(), 'day')) return 'Today';
    if (due.isSame(dayjs().add(1, 'day'), 'day')) return 'Tomorrow';
    return due.format('MMM D');
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :themed="false">
        <Head title="Tasks" />

        <div class="p-4 md:p-6">
            <!-- Header -->
            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold uppercase tracking-wider">Tasks</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] uppercase tracking-[0.12em]">
                        <span class="rounded-full border border-border bg-card px-2.5 py-1 shadow-sm"> {{ openCount }} open </span>
                        <span class="rounded-full border border-border bg-card px-2.5 py-1 text-muted-foreground shadow-sm">
                            {{ totalCount }} total
                        </span>
                        <span
                            v-if="overdueCount"
                            class="rounded-full border border-red-500/50 bg-red-500/10 px-2.5 py-1 text-red-600 shadow-sm dark:text-red-400"
                        >
                            {{ overdueCount }} overdue
                        </span>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <!-- Search -->
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            stroke-width="1.5"
                        />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search tasks"
                            class="w-full rounded-lg border border-border bg-card py-2 pl-9 pr-3 text-sm shadow-sm transition-shadow placeholder:text-muted-foreground focus:shadow-md focus:outline-none focus:ring-2 focus:ring-foreground/10 sm:w-56"
                        />
                    </div>

                    <!-- Priority filter -->
                    <div class="flex items-center gap-1 rounded-lg border border-border bg-card p-1 shadow-sm">
                        <button
                            v-for="option in PRIORITY_FILTERS"
                            :key="option"
                            type="button"
                            class="rounded-md px-2.5 py-1.5 text-[11px] uppercase tracking-[0.12em] transition-all"
                            :class="
                                priorityFilter === option
                                    ? 'bg-foreground text-background shadow-sm'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                            "
                            @click="priorityFilter = option"
                        >
                            {{ option }}
                        </button>
                    </div>

                    <button
                        type="button"
                        class="group flex items-center justify-center gap-2 rounded-lg bg-foreground px-4 py-2 text-sm uppercase tracking-wider text-background shadow-md transition-all hover:-translate-y-px hover:shadow-lg active:translate-y-0"
                        @click="openCreate('todo')"
                    >
                        <CirclePlus class="h-4 w-4 transition-transform duration-200 group-hover:rotate-90" />
                        New Task
                    </button>
                </div>
            </div>

            <p
                v-if="isFiltering"
                class="mb-4 rounded-lg border border-dashed border-border bg-muted/40 px-3 py-2 text-[11px] uppercase tracking-[0.12em] text-muted-foreground"
            >
                Filtered view — clear the search and priority filter to drag cards again.
            </p>

            <!-- Board -->
            <div class="grid gap-4 lg:grid-cols-3">
                <section
                    v-for="column in COLUMNS"
                    :key="column.key"
                    class="relative flex flex-col overflow-hidden rounded-xl border border-border bg-muted/40 shadow-sm dark:bg-white/[0.02]"
                >
                    <!-- Accent rail -->
                    <span aria-hidden="true" class="absolute inset-x-0 top-0 h-0.5" :class="column.rail" />

                    <header class="flex items-center justify-between border-b border-border/70 px-3 py-2.5">
                        <div class="flex items-center gap-2">
                            <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full" :class="column.dot" />
                            <component :is="column.icon" class="h-4 w-4 text-muted-foreground" stroke-width="1.5" />
                            <h2 class="text-[11px] font-medium uppercase tracking-[0.14em] text-muted-foreground">
                                {{ column.label }}
                            </h2>
                            <span
                                class="rounded-full border border-border bg-background px-1.5 py-0.5 text-[10px] tabular-nums text-muted-foreground"
                            >
                                {{ visibleIn(column.key).length }}
                            </span>
                        </div>
                        <button
                            type="button"
                            class="grid h-7 w-7 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-foreground hover:text-background"
                            :aria-label="`Add task to ${column.label}`"
                            :title="`Add task to ${column.label}`"
                            @click="openCreate(column.key)"
                        >
                            <CirclePlus class="h-4 w-4" stroke-width="1.5" />
                        </button>
                    </header>

                    <draggable
                        v-model="board[column.key]"
                        :group="{ name: 'tasks' }"
                        :disabled="isFiltering"
                        item-key="id"
                        handle=".task-drag"
                        ghost-class="task-ghost"
                        drag-class="task-dragging"
                        animation="180"
                        class="flex-1 space-y-2 rounded-lg p-2 transition-colors"
                        :class="dragging ? 'bg-foreground/[0.03] ring-1 ring-inset ring-foreground/10' : ''"
                        style="min-height: 8rem"
                        @start="dragging = true"
                        @end="persistOrder"
                    >
                        <template #item="{ element: task }">
                            <article
                                v-show="visibleIn(column.key).some((t) => t.id === task.id)"
                                class="group rounded-lg border border-l-[3px] bg-card p-3 shadow-sm ring-1 ring-black/[0.03] transition-all hover:-translate-y-px hover:shadow-md dark:ring-white/[0.04]"
                                :class="[
                                    PRIORITY_EDGE[task.priority],
                                    isOverdue(task) ? 'border-red-500/40 ring-red-500/20' : 'border-border hover:border-muted-foreground/40',
                                    task.status === 'done' ? 'opacity-70' : '',
                                ]"
                            >
                                <div class="flex items-start gap-2">
                                    <span
                                        class="task-drag mt-0.5 grid h-5 w-4 shrink-0 place-items-center text-muted-foreground transition-opacity"
                                        :class="
                                            isFiltering
                                                ? 'cursor-not-allowed opacity-30'
                                                : 'cursor-grab opacity-0 active:cursor-grabbing group-hover:opacity-100'
                                        "
                                    >
                                        <GripVertical class="h-4 w-4" />
                                    </span>

                                    <button
                                        type="button"
                                        class="mt-0.5 shrink-0 text-muted-foreground transition-colors hover:text-foreground"
                                        :aria-label="task.status === 'done' ? 'Reopen task' : 'Mark task done'"
                                        :title="task.status === 'done' ? 'Reopen task' : 'Mark task done'"
                                        @click="toggleDone(task)"
                                    >
                                        <CheckCircle2 v-if="task.status === 'done'" class="h-4 w-4 text-emerald-500" stroke-width="1.5" />
                                        <Circle v-else class="h-4 w-4" stroke-width="1.5" />
                                    </button>

                                    <div class="min-w-0 flex-1">
                                        <h3
                                            class="break-words text-sm font-semibold leading-snug"
                                            :class="{ 'line-through opacity-60': task.status === 'done' }"
                                        >
                                            {{ task.title }}
                                        </h3>
                                        <p v-if="task.description" class="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                            {{ task.description }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity focus-within:opacity-100 group-hover:opacity-100"
                                    >
                                        <button
                                            type="button"
                                            class="grid h-7 w-7 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                            aria-label="Edit task"
                                            title="Edit task"
                                            @click="openEdit(task)"
                                        >
                                            <Pencil class="h-3.5 w-3.5" stroke-width="1.5" />
                                        </button>
                                        <button
                                            type="button"
                                            class="grid h-7 w-7 place-items-center rounded-md text-red-600 transition-colors hover:bg-red-500/10 dark:text-red-400"
                                            :aria-label="isCreator(task) ? 'Delete task' : 'Leave task'"
                                            :title="isCreator(task) ? 'Delete task' : 'Leave task'"
                                            @click="deleteTask(task)"
                                        >
                                            <Trash2 v-if="isCreator(task)" class="h-3.5 w-3.5" stroke-width="1.5" />
                                            <LogOut v-else class="h-3.5 w-3.5" stroke-width="1.5" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Meta row -->
                                <div class="mt-3 flex flex-wrap items-center gap-2 pl-6">
                                    <span
                                        class="rounded-full border px-2 py-0.5 text-[10px] uppercase tracking-[0.12em]"
                                        :class="PRIORITY_PILL[task.priority]"
                                    >
                                        {{ task.priority }}
                                    </span>

                                    <span
                                        v-if="task.due_date"
                                        class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px]"
                                        :class="
                                            isOverdue(task)
                                                ? 'border-red-500/50 bg-red-500/10 text-red-600 dark:text-red-400'
                                                : 'border-transparent text-muted-foreground'
                                        "
                                    >
                                        <CalendarDays class="h-3 w-3" stroke-width="1.5" />
                                        {{ dueLabel(task) }}
                                    </span>

                                    <!-- Joint task: who else is on it -->
                                    <span
                                        v-if="isShared(task)"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-indigo-500/40 bg-indigo-500/10 py-0.5 pl-1.5 pr-2 text-[11px] text-indigo-600 dark:text-indigo-400"
                                        :title="`Shared with ${othersOf(task)
                                            .map((u) => u.name)
                                            .join(', ')}`"
                                    >
                                        <Users class="h-3 w-3" stroke-width="1.5" />
                                        <span class="flex items-center -space-x-1">
                                            <span
                                                v-for="participant in othersOf(task).slice(0, 3)"
                                                :key="participant.id"
                                                class="grid h-4 w-4 place-items-center rounded-full bg-indigo-500 text-[8px] font-semibold text-white ring-1 ring-card"
                                            >
                                                {{ initials(participant.name) }}
                                            </span>
                                        </span>
                                        <span v-if="othersOf(task).length > 3">+{{ othersOf(task).length - 3 }}</span>
                                    </span>

                                    <!-- Someone else started it and shared it with you -->
                                    <span
                                        v-if="!isCreator(task) && task.creator"
                                        class="inline-flex items-center gap-1 text-[11px] text-muted-foreground"
                                        :title="`Created by ${task.creator.name}`"
                                    >
                                        <UserRound class="h-3 w-3" stroke-width="1.5" />
                                        {{ task.creator.name }}
                                    </span>
                                </div>
                            </article>
                        </template>
                    </draggable>

                    <p
                        v-if="visibleIn(column.key).length === 0"
                        class="mx-2 mb-2 rounded-lg border border-dashed border-border py-6 text-center text-[11px] uppercase tracking-[0.12em] text-muted-foreground"
                    >
                        Nothing here
                    </p>
                </section>
            </div>
        </div>

        <!-- Create / edit modal -->
        <Transition
            enter-active-class="transition-opacity duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 backdrop-blur-sm sm:items-center sm:p-4"
                @click.self="closeModal"
            >
                <form
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="task-modal-title"
                    class="task-modal flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl border border-border bg-card shadow-2xl sm:max-h-[88vh] sm:max-w-xl sm:rounded-2xl"
                    @submit.prevent="submit"
                >
                    <!-- Header -->
                    <header class="flex items-start justify-between gap-3 border-b border-border px-5 py-4">
                        <div class="min-w-0">
                            <h2 id="task-modal-title" class="text-sm font-bold uppercase tracking-wider">
                                {{ editingId ? 'Edit Task' : 'New Task' }}
                            </h2>
                            <p class="mt-1 flex items-center gap-1.5 text-[11px] text-muted-foreground">
                                <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full" :class="COLUMNS.find((c) => c.key === form.status)?.dot" />
                                {{ editingId ? 'Sitting in' : 'Lands in' }}
                                {{ COLUMNS.find((c) => c.key === form.status)?.label }}
                                <template v-if="selectedUsers.length"> · shared with {{ selectedUsers.length }} </template>
                            </p>
                        </div>
                        <button
                            type="button"
                            class="grid h-8 w-8 shrink-0 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            aria-label="Close"
                            @click="closeModal"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </header>

                    <!-- Scrollable body -->
                    <div class="flex-1 space-y-6 overflow-y-auto px-5 py-5">
                        <!-- Title + description read as one block, like a note -->
                        <div>
                            <label for="task-title" class="sr-only">Title</label>
                            <input
                                id="task-title"
                                ref="titleInput"
                                v-model="form.title"
                                type="text"
                                required
                                maxlength="255"
                                placeholder="What needs doing?"
                                class="w-full border-0 bg-transparent p-0 text-lg font-semibold leading-snug placeholder:font-normal placeholder:text-muted-foreground/60 focus:outline-none focus:ring-0"
                            />

                            <label for="task-description" class="sr-only">Description</label>
                            <textarea
                                id="task-description"
                                v-model="form.description"
                                rows="2"
                                placeholder="Add detail, links, acceptance criteria…"
                                class="mt-2 w-full resize-y border-0 bg-transparent p-0 text-sm leading-relaxed text-muted-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-0"
                            ></textarea>

                            <div class="mt-2 flex items-center justify-between gap-3 border-t border-border/70 pt-2">
                                <p v-if="form.errors.title" class="text-xs text-red-600 dark:text-red-400">
                                    {{ form.errors.title }}
                                </p>
                                <p v-else-if="form.errors.description" class="text-xs text-red-600 dark:text-red-400">
                                    {{ form.errors.description }}
                                </p>
                                <span v-else class="text-[11px] text-muted-foreground">
                                    {{ form.title.length ? `${form.title.length}/255` : 'Title required' }}
                                </span>
                                <span v-if="form.title.length > 220" class="text-[11px] text-amber-600 dark:text-amber-400"> Getting long </span>
                            </div>
                        </div>

                        <!-- Column -->
                        <fieldset>
                            <legend class="mb-2 text-[11px] font-medium uppercase tracking-[0.14em] text-muted-foreground">Column</legend>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="column in COLUMNS"
                                    :key="column.key"
                                    type="button"
                                    class="flex items-center justify-center gap-2 rounded-lg border px-3 py-2 text-xs transition-all"
                                    :class="
                                        form.status === column.key
                                            ? 'border-foreground bg-foreground/[0.06] font-semibold shadow-sm'
                                            : 'border-border text-muted-foreground hover:border-muted-foreground/50 hover:text-foreground'
                                    "
                                    :aria-pressed="form.status === column.key"
                                    @click="form.status = column.key"
                                >
                                    <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full" :class="column.dot" />
                                    {{ column.label }}
                                </button>
                            </div>
                        </fieldset>

                        <!-- Priority -->
                        <fieldset>
                            <legend class="mb-2 text-[11px] font-medium uppercase tracking-[0.14em] text-muted-foreground">Priority</legend>
                            <div class="grid grid-cols-4 gap-2">
                                <button
                                    v-for="priority in PRIORITIES"
                                    :key="priority"
                                    type="button"
                                    class="flex items-center justify-center gap-1.5 rounded-lg border px-2 py-2 text-xs capitalize transition-all"
                                    :class="
                                        form.priority === priority
                                            ? `${PRIORITY_CHOICE[priority].active} font-semibold shadow-sm`
                                            : 'border-border text-muted-foreground hover:border-muted-foreground/50 hover:text-foreground'
                                    "
                                    :aria-pressed="form.priority === priority"
                                    @click="form.priority = priority"
                                >
                                    <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full" :class="PRIORITY_CHOICE[priority].dot" />
                                    {{ priority }}
                                </button>
                            </div>
                        </fieldset>

                        <!-- Due date -->
                        <fieldset>
                            <legend class="mb-2 text-[11px] font-medium uppercase tracking-[0.14em] text-muted-foreground">Due date</legend>
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    v-for="preset in DUE_PRESETS"
                                    :key="preset.label"
                                    type="button"
                                    class="rounded-full border px-3 py-1.5 text-[11px] transition-all"
                                    :class="
                                        dueIs(preset.days)
                                            ? 'border-foreground bg-foreground text-background shadow-sm'
                                            : 'border-border text-muted-foreground hover:border-muted-foreground/50 hover:text-foreground'
                                    "
                                    @click="setDue(preset.days)"
                                >
                                    {{ preset.label }}
                                </button>

                                <label for="task-due" class="sr-only">Pick a due date</label>
                                <input
                                    id="task-due"
                                    v-model="form.due_date"
                                    type="date"
                                    class="rounded-lg border border-input bg-background px-3 py-1.5 text-xs shadow-sm focus:outline-none focus:ring-2 focus:ring-foreground/10"
                                />

                                <button
                                    v-if="form.due_date"
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-1.5 text-[11px] text-muted-foreground transition-colors hover:text-foreground"
                                    @click="setDue(null)"
                                >
                                    <X class="h-3 w-3" />
                                    Clear
                                </button>
                            </div>
                            <p v-if="form.errors.due_date" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                {{ form.errors.due_date }}
                            </p>
                        </fieldset>

                        <!-- Share with -->
                        <fieldset>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <legend class="text-[11px] font-medium uppercase tracking-[0.14em] text-muted-foreground">Share with</legend>
                                <button
                                    v-if="form.participants.length"
                                    type="button"
                                    class="text-[11px] text-muted-foreground transition-colors hover:text-foreground"
                                    @click="form.participants = []"
                                >
                                    Clear all
                                </button>
                            </div>

                            <template v-if="props.users.length">
                                <!-- Chosen people, removable -->
                                <div v-if="selectedUsers.length" class="mb-2 flex flex-wrap gap-1.5">
                                    <button
                                        v-for="user in selectedUsers"
                                        :key="user.id"
                                        type="button"
                                        class="group inline-flex items-center gap-1.5 rounded-full border border-indigo-500/40 bg-indigo-500/10 py-1 pl-1 pr-2 text-[11px] text-indigo-700 transition-colors hover:bg-indigo-500/20 dark:text-indigo-300"
                                        :title="`Remove ${user.name}`"
                                        @click="toggleParticipant(user.id)"
                                    >
                                        <span class="grid h-5 w-5 place-items-center rounded-full bg-indigo-500 text-[9px] font-semibold text-white">
                                            {{ initials(user.name) }}
                                        </span>
                                        {{ user.name }}
                                        <X class="h-3 w-3 opacity-50 transition-opacity group-hover:opacity-100" />
                                    </button>
                                </div>

                                <!-- Search, only worth showing on a longer list -->
                                <div v-if="props.users.length > 5" class="relative mb-2">
                                    <Search
                                        class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground"
                                        stroke-width="1.5"
                                    />
                                    <input
                                        v-model="participantSearch"
                                        type="search"
                                        placeholder="Find a person"
                                        class="w-full rounded-lg border border-input bg-background py-1.5 pl-8 pr-3 text-xs shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-foreground/10"
                                    />
                                </div>

                                <div class="max-h-40 overflow-y-auto rounded-lg border border-input bg-background shadow-sm">
                                    <label
                                        v-for="user in filteredUsers"
                                        :key="user.id"
                                        class="flex cursor-pointer items-center gap-3 border-b border-border/60 px-3 py-2 text-sm transition-colors last:border-b-0 hover:bg-muted"
                                        :class="form.participants.includes(user.id) ? 'bg-muted/60' : ''"
                                    >
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 shrink-0 cursor-pointer rounded border-input accent-foreground"
                                            :checked="form.participants.includes(user.id)"
                                            @change="toggleParticipant(user.id)"
                                        />
                                        <span
                                            class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-[9px] font-semibold transition-colors"
                                            :class="
                                                form.participants.includes(user.id) ? 'bg-indigo-500 text-white' : 'bg-muted text-muted-foreground'
                                            "
                                        >
                                            {{ initials(user.name) }}
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate">{{ user.name }}</span>
                                            <span class="block truncate text-[11px] text-muted-foreground">
                                                {{ user.email }}
                                            </span>
                                        </span>
                                    </label>

                                    <p v-if="!filteredUsers.length" class="px-3 py-4 text-center text-[11px] text-muted-foreground">
                                        No match for “{{ participantSearch }}”
                                    </p>
                                </div>

                                <p class="mt-1.5 text-[11px] text-muted-foreground">
                                    Stays on your board and appears on theirs. Everyone picked gets a notification; unticking removes their access.
                                </p>
                            </template>

                            <p v-else class="rounded-lg border border-dashed border-border px-3 py-3 text-[11px] text-muted-foreground">
                                No other users to share with.
                            </p>

                            <p v-if="form.errors.participants" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                {{ form.errors.participants }}
                            </p>
                        </fieldset>
                    </div>

                    <!-- Footer stays put while the body scrolls -->
                    <footer class="flex items-center justify-between gap-3 border-t border-border bg-card px-5 py-3">
                        <p class="hidden text-[11px] text-muted-foreground sm:block">
                            <kbd class="rounded border border-border px-1 py-0.5 font-mono text-[10px]">⌘</kbd>
                            <kbd class="rounded border border-border px-1 py-0.5 font-mono text-[10px]">↵</kbd>
                            save ·
                            <kbd class="rounded border border-border px-1 py-0.5 font-mono text-[10px]">Esc</kbd>
                            close
                        </p>
                        <div class="ml-auto flex gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-border px-4 py-2 text-sm uppercase tracking-wider text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                @click="closeModal"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.title.trim()"
                                class="rounded-lg bg-foreground px-4 py-2 text-sm uppercase tracking-wider text-background shadow-md transition-all hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                            >
                                {{ form.processing ? 'Saving…' : editingId ? 'Save Changes' : 'Create Task' }}
                            </button>
                        </div>
                    </footer>
                </form>
            </div>
        </Transition>
    </AppLayout>
</template>

<style scoped>
/* Placeholder slot the card will drop into. */
.task-ghost {
    opacity: 0.35;
    border-style: dashed;
    box-shadow: none;
}

/* The card actually following the cursor. */
.task-dragging {
    box-shadow:
        0 12px 24px -8px rgb(0 0 0 / 0.25),
        0 4px 8px -4px rgb(0 0 0 / 0.15);
}

/* Panel rises into place — sheet from the bottom on mobile, lift on desktop. */
.task-modal {
    animation: task-modal-in 180ms cubic-bezier(0.22, 1, 0.36, 1);
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
    .task-modal {
        animation: none;
    }
}
</style>
