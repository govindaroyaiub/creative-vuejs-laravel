<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CirclePlus, Eye, Trash2, ShieldCheck, ShieldAlert, ShieldX, Loader2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed } from 'vue';

interface CheckRow {
    id: number;
    publisher: string;
    period_start: string;
    period_end: string;
    outcome_filename: string;
    status: string;
    fx_rate_used: number | null;
    major_count: number;
    minor_count: number;
    uploader: { id: number; name: string; email: string } | null;
    created_at: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Check Reports', href: '/reports/checks' },
];

const page = usePage();
const checks = computed(() => page.props.checks as { data: CheckRow[]; links: any[] });

const statusMeta = (status: string) => {
    switch (status) {
        case 'pass':
            return { label: 'Pass', icon: ShieldCheck, cls: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' };
        case 'fail_minor':
            return { label: 'Fail (minor)', icon: ShieldAlert, cls: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' };
        case 'fail_major':
            return { label: 'Fail (major)', icon: ShieldX, cls: 'bg-[#D71921]/10 dark:bg-[#D71921]/20 text-[#D71921]' };
        case 'error':
            return { label: 'Error', icon: ShieldX, cls: 'bg-[#D71921]/10 dark:bg-[#D71921]/20 text-[#D71921]' };
        default:
            return { label: 'Pending', icon: Loader2, cls: 'bg-[#F5F5F5] dark:bg-[#0A0A0A] text-[#666666] dark:text-[#999999]' };
    }
};

const formatPeriod = (a: string, b: string) => {
    const fmt = (s: string) => new Date(s).toLocaleDateString('en-US', { month: 'short', day: '2-digit' });
    const yr = new Date(a).getFullYear();
    return a === b ? `${fmt(a)} ${yr}` : `${fmt(a)} – ${fmt(b)} ${yr}`;
};

const deleteCheck = async (id: number) => {
    const result = await Swal.fire({
        title: 'Delete this check?',
        text: 'The validation result and all linked issues will be removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
    });
    if (!result.isConfirmed) return;

    router.delete(route('report-checks.destroy', id), {
        preserveScroll: true,
        onSuccess: () => Swal.fire('Deleted!', 'Check removed.', 'success'),
        onError: () => Swal.fire('Error!', 'Failed to delete.', 'error'),
    });
};
</script>

<template>
    <Head title="Check Reports" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-black">
            <div class="p-4 sm:p-6">
                <div class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-0 sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-black dark:text-white uppercase font-mono tracking-wider">
                            Check Reports
                        </h1>
                        <p class="text-xs text-[#666666] dark:text-[#999999] mt-1">
                            Validate a produced revenue report against its source files.
                        </p>
                    </div>
                    <Link :href="route('report-checks.create')">
                        <button class="w-full sm:w-auto rounded-full bg-black dark:bg-white text-white dark:text-black px-4 py-2 hover:bg-white hover:dark:bg-black hover:text-black hover:dark:text-white border-2 border-black dark:border-white group whitespace-nowrap uppercase font-mono tracking-wider text-sm transition-colors">
                            <CirclePlus class="mr-1 inline h-5 w-5 group-hover:rotate-90 transition-transform duration-200" />
                            New Check
                        </button>
                    </Link>
                </div>

                <div class="overflow-x-auto rounded-lg border-2 border-[#E8E8E8] dark:border-[#222222]">
                    <table class="w-full bg-white dark:bg-[#111111]">
                        <thead class="bg-[#F5F5F5] dark:bg-[#0A0A0A] text-black dark:text-white">
                            <tr class="text-left text-xs uppercase font-mono tracking-wider">
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Period</th>
                                <th class="px-4 py-3">Publisher</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Issues</th>
                                <th class="px-4 py-3">FX</th>
                                <th class="px-4 py-3">By</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in checks.data" :key="c.id"
                                class="border-t border-[#E8E8E8] dark:border-[#222222] text-sm hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] transition-colors">
                                <td class="px-4 py-3"><span class="font-mono text-[#666666] dark:text-[#999999]">#{{ c.id }}</span></td>
                                <td class="px-4 py-3 text-black dark:text-white font-mono text-xs">
                                    {{ formatPeriod(c.period_start, c.period_end) }}
                                </td>
                                <td class="px-4 py-3 text-black dark:text-white text-sm capitalize">{{ c.publisher }}</td>
                                <td class="px-4 py-3">
                                    <span :class="statusMeta(c.status).cls"
                                        class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold uppercase font-mono">
                                        <component :is="statusMeta(c.status).icon" class="h-3.5 w-3.5" stroke-width="1.5" />
                                        {{ statusMeta(c.status).label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs font-mono">
                                    <span v-if="c.major_count" class="text-[#D71921] mr-2">{{ c.major_count }} major</span>
                                    <span v-if="c.minor_count" class="text-yellow-600 dark:text-yellow-400">{{ c.minor_count }} minor</span>
                                    <span v-if="!c.major_count && !c.minor_count" class="text-[#666666] dark:text-[#999999]">—</span>
                                </td>
                                <td class="px-4 py-3 text-xs font-mono text-[#666666] dark:text-[#999999]">
                                    {{ c.fx_rate_used ? Number(c.fx_rate_used).toFixed(4) : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-black dark:text-white text-xs font-medium">{{ c.uploader?.name ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs font-mono text-[#666666] dark:text-[#999999]">
                                    {{ new Date(c.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-3">
                                        <Link :href="route('report-checks.show', c.id)"
                                            class="text-black dark:text-white hover:text-[#666666] dark:hover:text-[#999999] transition-colors">
                                            <Eye class="h-5 w-5" stroke-width="1.5" />
                                        </Link>
                                        <button @click="deleteCheck(c.id)" class="text-[#D71921] hover:text-red-700 transition-colors">
                                            <Trash2 class="h-5 w-5" stroke-width="1.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="checks.data.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-[#666666] dark:text-[#999999] uppercase font-mono tracking-wider text-sm">
                                    No checks yet — upload a set of source files to run your first one.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="border-t-2 border-[#E8E8E8] dark:border-[#222222] p-4" v-if="checks.data.length > 0 && checks.links.length">
                    <div class="flex justify-center gap-2">
                        <template v-for="link in checks.links" :key="link.label">
                            <component :is="link.url ? 'a' : 'span'" v-html="link.label" :href="link.url"
                                class="rounded-lg px-4 py-2 text-xs uppercase font-mono tracking-wider border-2 transition-colors"
                                :class="{
                                    'bg-black dark:bg-white text-white dark:text-black border-black dark:border-white': link.active,
                                    'cursor-not-allowed text-[#999999] border-[#E8E8E8] dark:border-[#222222]': !link.url,
                                    'hover:bg-[#F5F5F5] hover:dark:bg-[#0A0A0A] border-[#CCCCCC] dark:border-[#333333] text-black dark:text-white': link.url && !link.active,
                                }" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
