<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    CategoryScale,
    LinearScale,
    PointElement,
    Filler,
} from 'chart.js';
import { computed, ref, watchEffect } from 'vue';
import { type BreadcrumbItem } from '@/types';

ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, Filler);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

const page = usePage();

const stats = computed(() => page.props.monthlyStats ?? {});
const year = new Date().getFullYear();

const labels = Array.from({ length: 12 }, (_, i) =>
    new Date(0, i).toLocaleString('default', { month: 'short' })
);

const datasets = computed(() => [
    {
        label: 'Banners',
        data: labels.map((_, i) => stats.value.banners?.[i + 1] || 0),
        borderColor: '#4f46e5',
        tension: 0.4,
    },
    {
        label: 'Videos',
        data: labels.map((_, i) => stats.value.videos?.[i + 1] || 0),
        borderColor: '#10b981',
        tension: 0.4,
    },
    {
        label: 'GIFs',
        data: labels.map((_, i) => stats.value.gifs?.[i + 1] || 0),
        borderColor: '#f59e0b',
        tension: 0.4,
    },
    {
        label: 'Socials',
        data: labels.map((_, i) => stats.value.socials?.[i + 1] || 0),
        borderColor: '#ef4444',
        tension: 0.4,
    },
]);

const chartData = computed(() => ({
    labels,
    datasets: datasets.value,
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 1000,
        easing: 'easeOutQuart',
    },
    plugins: {
        legend: { position: 'bottom' },
        title: { display: true, text: `Monthly Content Statistics (${year})` }
    },
};

// Simple count-up animation for numbers
const animateCount = (target: number) => {
    const count = ref(0);
    const step = Math.ceil(target / 20);

    watchEffect((onCleanup) => {
        let current = 0;
        const interval = setInterval(() => {
            current += step;
            if (current >= target) {
                count.value = target;
                clearInterval(interval);
            } else {
                count.value = current;
            }
        }, 20);
        onCleanup(() => clearInterval(interval));
    });

    return count;
};

const animatedCounts = {
    userCount: animateCount(page.props.userCount),
    previewCount: animateCount(page.props.previewCount),
    bannerCount: animateCount(page.props.bannerCount),
    videoCount: animateCount(page.props.videoCount),
    gifCount: animateCount(page.props.gifCount),
    socialCount: animateCount(page.props.socialCount),
    fileTransferCount: animateCount(page.props.fileTransferCount),
    totalBill: animateCount(page.props.totalBill),
};

const monthlyBillTotals = computed(() => page.props.monthlyBillTotals ?? {});

const billChartData = computed(() => ({
    labels,
    datasets: [
        {
            label: 'Monthly Bill Total',
            data: labels.map((_, i) => monthlyBillTotals.value[i + 1] || 0),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.1)',
            tension: 0.4,
            fill: true,
        },
    ],
}));

const billChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 1000,
        easing: 'easeOutQuart',
    },
    plugins: {
        legend: { position: 'bottom' },
        title: { display: true, text: `Monthly Bill Total (${year})` }
    },
    scales: {
        y: {
            beginAtZero: true,
            title: { display: true, text: 'Total Amount' }
        }
    }
};
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <div class="flex items-center gap-4">
                <h1 class="text-2xl font-bold">Statistics of {{ year }}</h1>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="text-sm text-gray-500">Users</div>
                    <div class="text-2xl font-bold">{{ animatedCounts.userCount }}</div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="text-sm text-gray-500">Previews</div>
                    <div class="text-2xl font-bold">{{ animatedCounts.previewCount }}</div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="text-sm text-gray-500">Banners</div>
                    <div class="text-2xl font-bold">{{ animatedCounts.bannerCount }}</div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="text-sm text-gray-500">Videos</div>
                    <div class="text-2xl font-bold">{{ animatedCounts.videoCount }}</div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="text-sm text-gray-500">GIFs</div>
                    <div class="text-2xl font-bold">{{ animatedCounts.gifCount }}</div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="text-sm text-gray-500">Socials</div>
                    <div class="text-2xl font-bold">{{ animatedCounts.socialCount }}</div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="text-sm text-gray-500">File Transfers</div>
                    <div class="text-2xl font-bold">{{ animatedCounts.fileTransferCount }}</div>
                </div>
                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="text-sm text-gray-500">Total Bills (BDT)</div>
                    <div class="text-2xl font-bold">{{ animatedCounts.totalBill }}</div>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow w-full" :style="{ height: `calc(100vh - 350px)` }">
                    <Line :data="chartData" :options="chartOptions" />
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow w-full" :style="{ height: `calc(100vh - 350px)` }">
                    <Line :data="chartData" :options="chartOptions" />
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow mt-8" :style="{ height: `350px` }">
                <Line :data="billChartData" :options="billChartOptions" />
            </div>
        </div>
    </AppLayout>
</template>