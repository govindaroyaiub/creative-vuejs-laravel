<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { Eye, EyeOff, GripVertical, RotateCcw } from 'lucide-vue-next';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';

import {
    editorRowsForSection,
    type NavPreferenceEntry,
} from '@/lib/sidebar-nav';

defineProps<{ status?: string }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sidebar settings', href: '/settings/sidebar' },
];

const page = usePage<SharedData>();

/**
 * Pull the user's saved preferences (or null if they've never saved)
 * and turn each section into the editor shape — one row per canonical
 * item, in the user's chosen order, with the visibility flag attached.
 * `preferencesForEditor` handles the "new item appended at end as
 * visible" rule for entries the user hasn't seen yet.
 */
const initialMain = computed(() =>
    editorRowsForSection('main', (page.props.auth.user as any)?.nav_preferences),
);
const initialFooter = computed(() =>
    editorRowsForSection('footer', (page.props.auth.user as any)?.nav_preferences),
);

// Working copies the user is editing. Cloned from the initial state so
// we can compare against it for the "dirty" indicator + reset-from-
// initial behaviour without touching the server-side prefs.
const mainRows = ref(clone(initialMain.value));
const footerRows = ref(clone(initialFooter.value));

// When the page hot-reloads after a save, repopulate from the new
// shared user prop so the UI reflects what's actually in the DB.
watch(initialMain, (next) => { mainRows.value = clone(next); });
watch(initialFooter, (next) => { footerRows.value = clone(next); });

function clone<T>(arr: T[]): T[] {
    return arr.map((row) => ({ ...row }));
}

function toggle(row: { visible: boolean }) {
    row.visible = !row.visible;
}

const form = useForm<{
    main: NavPreferenceEntry[];
    footer: NavPreferenceEntry[];
}>({
    main: [],
    footer: [],
});

const isDirty = computed(() => {
    return (
        JSON.stringify(stripIcons(mainRows.value)) !== JSON.stringify(stripIcons(initialMain.value)) ||
        JSON.stringify(stripIcons(footerRows.value)) !== JSON.stringify(stripIcons(initialFooter.value))
    );
});

function stripIcons(rows: any[]) {
    return rows.map((r) => ({ href: r.href, visible: r.visible }));
}

const submit = () => {
    form.main = stripIcons(mainRows.value);
    form.footer = stripIcons(footerRows.value);
    form.put(route('sidebar.update'), {
        preserveScroll: true,
    });
};

const resetting = ref(false);
const reset = () => {
    if (resetting.value) return;
    resetting.value = true;
    useForm({}).delete(route('sidebar.reset'), {
        preserveScroll: true,
        onFinish: () => { resetting.value = false; },
    });
};

const justSaved = computed(() => (page.props as any).status === 'sidebar-updated');
const justReset = computed(() => (page.props as any).status === 'sidebar-reset');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Sidebar settings" />

        <SettingsLayout>
            <div class="space-y-6 font-mono">
                <HeadingSmall
                    title="Sidebar layout"
                    description="Reorder the sidebar items by dragging, or hide ones you don't use. Changes apply to your account on every device."
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2 md:items-stretch">
                    <!-- ============ MAIN NAV ============ -->
                    <section class="flex flex-col gap-3">
                        <header class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">
                                Workspace tools
                            </h3>
                            <span class="text-[11px] text-muted-foreground">
                                {{ mainRows.filter((r) => r.visible).length }} / {{ mainRows.length }} visible
                            </span>
                        </header>

                        <draggable
                            v-model="mainRows"
                            :group="{ name: 'nav' }"
                            item-key="href"
                            handle=".sb-drag"
                            ghost-class="sb-ghost"
                            animation="180"
                            class="flex-1 space-y-1.5 rounded-lg border border-dashed border-transparent transition-colors [&:empty]:border-[#CCCCCC] dark:[&:empty]:border-[#222222]"
                            style="min-height: 12rem"
                        >
                            <template #item="{ element: row }">
                                <div
                                    class="group flex items-center gap-3 rounded-lg border-2 px-3 py-2.5 transition-colors"
                                    :class="row.visible
                                        ? 'border-[#CCCCCC] bg-white dark:border-[#222222] dark:bg-black'
                                        : 'border-dashed border-[#CCCCCC] bg-[#fafafa] dark:border-[#222222] dark:bg-[#0d0d0d] opacity-60'"
                                >
                                    <span class="sb-drag grid h-7 w-5 shrink-0 cursor-grab place-items-center text-muted-foreground active:cursor-grabbing">
                                        <GripVertical class="h-4 w-4" />
                                    </span>

                                    <component
                                        :is="row.icon"
                                        class="h-4 w-4 shrink-0 text-muted-foreground"
                                        aria-hidden="true"
                                    />

                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-black dark:text-white">{{ row.title }}</span>
                                        <span class="block truncate text-[11px] text-muted-foreground">{{ row.href }}</span>
                                    </span>

                                    <button
                                        type="button"
                                        class="grid h-8 w-8 shrink-0 place-items-center rounded-md text-muted-foreground transition hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black"
                                        :aria-pressed="row.visible"
                                        :aria-label="row.visible ? `Hide ${row.title}` : `Show ${row.title}`"
                                        :title="row.visible ? 'Hide from sidebar' : 'Show in sidebar'"
                                        @click="toggle(row)"
                                    >
                                        <Eye v-if="row.visible" class="h-4 w-4" />
                                        <EyeOff v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </template>
                        </draggable>
                    </section>

                    <!-- ============ FOOTER NAV ============ -->
                    <section class="flex flex-col gap-3">
                        <header class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">
                                Footer tools
                            </h3>
                            <span class="text-[11px] text-muted-foreground">
                                {{ footerRows.filter((r) => r.visible).length }} / {{ footerRows.length }} visible
                            </span>
                        </header>

                        <draggable
                            v-model="footerRows"
                            :group="{ name: 'nav' }"
                            item-key="href"
                            handle=".sb-drag"
                            ghost-class="sb-ghost"
                            animation="180"
                            class="flex-1 space-y-1.5 rounded-lg border border-dashed border-transparent transition-colors [&:empty]:border-[#CCCCCC] dark:[&:empty]:border-[#222222]"
                            style="min-height: 12rem"
                        >
                            <template #item="{ element: row }">
                                <div
                                    class="group flex items-center gap-3 rounded-lg border-2 px-3 py-2.5 transition-colors"
                                    :class="row.visible
                                        ? 'border-[#CCCCCC] bg-white dark:border-[#222222] dark:bg-black'
                                        : 'border-dashed border-[#CCCCCC] bg-[#fafafa] dark:border-[#222222] dark:bg-[#0d0d0d] opacity-60'"
                                >
                                    <span class="sb-drag grid h-7 w-5 shrink-0 cursor-grab place-items-center text-muted-foreground active:cursor-grabbing">
                                        <GripVertical class="h-4 w-4" />
                                    </span>
                                    <component :is="row.icon" class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-black dark:text-white">{{ row.title }}</span>
                                        <span class="block truncate text-[11px] text-muted-foreground">{{ row.href }}</span>
                                    </span>
                                    <button
                                        type="button"
                                        class="grid h-8 w-8 shrink-0 place-items-center rounded-md text-muted-foreground transition hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black"
                                        :aria-pressed="row.visible"
                                        :aria-label="row.visible ? `Hide ${row.title}` : `Show ${row.title}`"
                                        :title="row.visible ? 'Hide from sidebar' : 'Show in sidebar'"
                                        @click="toggle(row)"
                                    >
                                        <Eye v-if="row.visible" class="h-4 w-4" />
                                        <EyeOff v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </template>
                        </draggable>
                    </section>
                    </div>

                    <!-- ============ ACTIONS ============ -->
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t-2 border-[#CCCCCC] dark:border-[#222222] pt-4">
                        <div class="flex items-center gap-3 text-[11px] text-muted-foreground">
                            <Transition name="fade">
                                <span v-if="justSaved" class="text-emerald-600 dark:text-emerald-400">
                                    Saved.
                                </span>
                            </Transition>
                            <Transition name="fade">
                                <span v-if="justReset" class="text-emerald-600 dark:text-emerald-400">
                                    Reset to defaults.
                                </span>
                            </Transition>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="form.processing || resetting"
                                @click="reset"
                                title="Drop your customisation and use the default order"
                            >
                                <RotateCcw class="h-3.5 w-3.5" />
                                Reset to default
                            </Button>
                            <Button type="submit" :disabled="!isDirty || form.processing">
                                {{ form.processing ? 'Saving…' : 'Save changes' }}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

<style scoped>
.sb-ghost {
    opacity: 0.4;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 200ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
