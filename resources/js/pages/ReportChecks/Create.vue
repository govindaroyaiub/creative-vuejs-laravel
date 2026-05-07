<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Upload, Send, X, FileCheck2, FileWarning, ChevronRight } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

interface SourceSpec { key: string; label: string; expected_filename: string; }

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Check Reports', href: '/reports/checks' },
    { title: 'New Check', href: '/reports/checks/create' },
];

const page = usePage();
const publishers = computed(() => page.props.publishers as Record<string, { display_name: string }>);

const SOURCES: SourceSpec[] = [
    { key: 'adform',     label: 'Adform',     expected_filename: 'Adform.xlsx' },
    { key: 'gam',        label: 'GAM',        expected_filename: 'GAM.xlsx' },
    { key: 'ogury',      label: 'Ogury',      expected_filename: 'Ogury.xlsx' },
    { key: 'seedtag',    label: 'SeedTag',    expected_filename: 'SeedTag.xlsx' },
    { key: 'showheroes', label: 'ShowHeroes', expected_filename: 'Showheroes.xlsx' },
    { key: 'teads',      label: 'Teads',      expected_filename: 'Teads.xlsx' },
    { key: 'adhese',     label: 'Adhese',     expected_filename: 'Adhese f1.csv' },
    { key: 'outbrain',   label: 'Outbrain',   expected_filename: 'Outbrain.csv' },
    { key: 'analytics',  label: 'Analytics (GA4)', expected_filename: 'analytics.csv' },
    { key: 'outcome',    label: 'Produced Report', expected_filename: '* Revenue Report - *.xlsx' },
];

// Detect source key client-side so the UI can show what it picked up.
const detectSourceKey = (filename: string): string | null => {
    const n = filename.toLowerCase().trim();
    const exact: Record<string, string> = {
        'adform.xlsx': 'adform',
        'gam.xlsx': 'gam',
        'ogury.xlsx': 'ogury',
        'seedtag.xlsx': 'seedtag',
        'showheroes.xlsx': 'showheroes',
        'teads.xlsx': 'teads',
        'outbrain.csv': 'outbrain',
        'analytics.csv': 'analytics',
    };
    if (exact[n]) return exact[n];
    if (/^adhese[\w \-]*\.csv$/.test(n)) return 'adhese';
    if (n.includes('revenue report') && n.endsWith('.xlsx')) return 'outcome';
    return null;
};

const detected = ref<Record<string, File>>({});
const unknown = ref<File[]>([]);
const dragOver = ref(false);

const form = useForm({
    publisher: Object.keys(publishers.value)[0] ?? 'f1maximaal',
    files: [] as File[],
});

const allSourcesDetected = computed(() => SOURCES.every(s => detected.value[s.key]));

const addFiles = (files: FileList | File[]) => {
    for (const f of Array.from(files)) {
        const key = detectSourceKey(f.name);
        if (key === null) {
            unknown.value.push(f);
        } else {
            detected.value[key] = f;
        }
    }
};

const onDrop = (e: DragEvent) => {
    dragOver.value = false;
    if (e.dataTransfer?.files) addFiles(e.dataTransfer.files);
};
const onPick = (e: Event) => {
    const t = e.target as HTMLInputElement;
    if (t.files) addFiles(t.files);
    t.value = '';
};
const removeSource = (key: string) => { delete detected.value[key]; };
const clearUnknown = () => { unknown.value = []; };

const submit = () => {
    if (!allSourcesDetected.value) {
        Swal.fire('Missing files', 'Upload all 10 source files before validating.', 'error');
        return;
    }
    const fd = Object.values(detected.value);
    form.transform(() => ({ publisher: form.publisher, files: fd })).post(route('report-checks.store'), {
        forceFormData: true,
        onError: (errs) => {
            const msgs = Object.values(errs).join('\n');
            Swal.fire('Validation failed', msgs || 'Could not start the check.', 'error');
        },
    });
};
</script>

<template>
    <Head title="New Check Report" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 sm:p-6 max-w-5xl mx-auto">
                <div class="bg-white dark:bg-[#111111] rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222] p-6 sm:p-8">
                    <div class="mb-6">
                        <h1 class="text-2xl sm:text-3xl font-bold text-black dark:text-white uppercase font-mono tracking-wider mb-2">
                            New Check
                        </h1>
                        <p class="text-sm text-[#666666] dark:text-[#999999]">
                            Drop the 9 raw source files plus the produced revenue report. We'll detect each one by its filename.
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white mb-2">
                                Publisher
                            </label>
                            <select v-model="form.publisher"
                                class="w-full px-4 py-3 border-2 border-[#CCCCCC] dark:border-[#333333] rounded-lg bg-white dark:bg-[#111111] text-black dark:text-white focus:border-black dark:focus:border-white focus:outline-none transition-colors">
                                <option v-for="(p, slug) in publishers" :key="slug" :value="slug">{{ p.display_name }}</option>
                            </select>
                        </div>

                        <!-- Drop zone -->
                        <div>
                            <label class="block text-xs uppercase font-mono tracking-wider text-black dark:text-white mb-2">
                                Source files
                            </label>
                            <div @dragenter.prevent="dragOver = true" @dragover.prevent="dragOver = true"
                                @dragleave.prevent="dragOver = false" @drop.prevent="onDrop"
                                :class="['border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                                    dragOver
                                        ? 'border-black dark:border-white bg-[#F5F5F5] dark:bg-[#0A0A0A]'
                                        : 'border-[#CCCCCC] dark:border-[#333333] hover:border-black hover:dark:border-white']">
                                <Upload class="mx-auto h-12 w-12 text-[#666666] dark:text-[#999999]" stroke-width="1.5" />
                                <label for="file-pick" class="mt-2 block cursor-pointer">
                                    <span class="text-black dark:text-white font-semibold uppercase font-mono tracking-wider text-xs">Drop files here</span>
                                    <span class="text-[#666666] dark:text-[#999999] text-xs"> or click to browse</span>
                                </label>
                                <input id="file-pick" type="file" multiple class="hidden"
                                    accept=".xlsx,.csv" @change="onPick" />
                                <p class="mt-2 text-xs text-[#666666] dark:text-[#999999]">9 raw files + 1 produced report — XLSX/CSV, 25MB each max.</p>
                            </div>
                        </div>

                        <!-- Slot status -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div v-for="s in SOURCES" :key="s.key"
                                :class="['flex items-center justify-between gap-3 px-4 py-3 rounded-lg border-2',
                                    detected[s.key]
                                        ? 'border-green-600/40 dark:border-green-400/40 bg-green-50 dark:bg-green-900/10'
                                        : 'border-[#E8E8E8] dark:border-[#222222] bg-[#F5F5F5] dark:bg-[#0A0A0A]']">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <component :is="detected[s.key] ? FileCheck2 : FileWarning"
                                            :class="detected[s.key] ? 'text-green-700 dark:text-green-400' : 'text-[#666666] dark:text-[#999999]'"
                                            class="h-4 w-4 shrink-0" stroke-width="1.5" />
                                        <span class="text-xs font-mono uppercase tracking-wider text-black dark:text-white">{{ s.label }}</span>
                                    </div>
                                    <div class="mt-1 text-xs text-[#666666] dark:text-[#999999] truncate">
                                        {{ detected[s.key]?.name ?? s.expected_filename }}
                                    </div>
                                </div>
                                <button v-if="detected[s.key]" type="button" @click="removeSource(s.key)"
                                    class="text-[#666666] dark:text-[#999999] hover:text-[#D71921] transition-colors shrink-0">
                                    <X class="h-4 w-4" stroke-width="1.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Unknown files warning -->
                        <div v-if="unknown.length"
                            class="px-4 py-3 rounded-lg border-2 border-[#D71921]/40 bg-[#D71921]/5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-xs">
                                    <div class="font-mono uppercase tracking-wider text-[#D71921] mb-1">Unrecognized files</div>
                                    <div class="text-[#666666] dark:text-[#999999]">
                                        <span v-for="(u, i) in unknown" :key="i">
                                            {{ u.name }}<span v-if="i < unknown.length - 1">, </span>
                                        </span>
                                    </div>
                                </div>
                                <button type="button" @click="clearUnknown"
                                    class="text-[#D71921] hover:text-red-700 transition-colors shrink-0">
                                    <X class="h-4 w-4" stroke-width="1.5" />
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-6 border-t-2 border-[#E8E8E8] dark:border-[#222222]">
                            <a href="/reports/checks" class="w-full sm:w-auto">
                                <button type="button"
                                    class="w-full rounded-full px-6 py-2 border-2 border-[#CCCCCC] dark:border-[#333333] text-black dark:text-white hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] uppercase font-mono tracking-wider text-sm transition-colors">
                                    Cancel
                                </button>
                            </a>
                            <button type="submit" :disabled="form.processing || !allSourcesDetected"
                                class="w-full sm:w-auto rounded-full bg-black dark:bg-white text-white dark:text-black px-6 py-2 hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white disabled:opacity-50 disabled:cursor-not-allowed uppercase font-mono tracking-wider text-sm transition-colors">
                                <Send class="mr-2 inline h-4 w-4" stroke-width="1.5" />
                                {{ form.processing ? 'Validating…' : 'Validate' }}
                                <ChevronRight class="ml-1 inline h-4 w-4" stroke-width="1.5" />
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
