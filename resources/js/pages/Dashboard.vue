<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import { MonitorStop, Video, ImagePlay, Wallpaper, Paperclip, UsersRound, MonitorCog, PiggyBank } from 'lucide-vue-next';
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
    ArcElement,
    BarElement,
} from 'chart.js';
import { computed, ref, watchEffect, onMounted, onUnmounted } from 'vue';
import { type BreadcrumbItem } from '@/types';

ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, Filler, ArcElement, BarElement);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

const page = usePage();
const isLoaded = ref(false);

const currentTime = ref(new Date());
let timeInterval: NodeJS.Timeout;

onMounted(() => {
    timeInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (timeInterval) {
        clearInterval(timeInterval);
    }
});

// Bangladesh Time (Asia/Dhaka)
const bangladeshTime = computed(() => {
    const bdTime = new Date(currentTime.value.toLocaleString("en-US", { timeZone: "Asia/Dhaka" }));
    return {
        time: bdTime.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        }),
        date: bdTime.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        })
    };
});

// Netherlands Time (Europe/Amsterdam)
const netherlandsTime = computed(() => {
    const nlTime = new Date(currentTime.value.toLocaleString("en-US", { timeZone: "Europe/Amsterdam" }));
    return {
        time: nlTime.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        }),
        date: nlTime.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        })
    };
});

const stats = computed(() => page.props.monthlyStats ?? {});
const year = new Date().getFullYear();
const currentMonth = new Date().getMonth();

const labels = Array.from({ length: 12 }, (_, i) =>
    new Date(0, i).toLocaleString('default', { month: 'short' })
);

const datasets = computed(() => [
    {
        label: 'Banners',
        data: labels.map((_, i) => stats.value.banners?.[i + 1] || 0),
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        borderWidth: 3,
        tension: 0.4,
        fill: true,
    },
    {
        label: 'Videos',
        data: labels.map((_, i) => stats.value.videos?.[i + 1] || 0),
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        borderWidth: 3,
        tension: 0.4,
        fill: true,
    },
    {
        label: 'GIFs',
        data: labels.map((_, i) => stats.value.gifs?.[i + 1] || 0),
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245, 158, 11, 0.1)',
        borderWidth: 3,
        tension: 0.4,
        fill: true,
    },
    {
        label: 'Socials',
        data: labels.map((_, i) => stats.value.socials?.[i + 1] || 0),
        borderColor: '#ef4444',
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        borderWidth: 3,
        tension: 0.4,
        fill: true,
    },
]);

const chartData = computed(() => ({
    labels,
    datasets: datasets.value,
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    animation: {
        duration: 2000,
        easing: 'easeOutCubic',
    },
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                padding: 20,
                usePointStyle: true,
                pointStyle: 'circle',
            }
        },
        title: {
            display: true,
            text: `Monthly Content Statistics (${year})`,
            font: { size: 16, weight: 'bold' },
            padding: 20,
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: 'white',
            bodyColor: 'white',
            borderColor: '#6366f1',
            borderWidth: 1,
        }
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { font: { weight: 'bold' } }
        },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(0, 0, 0, 0.1)' }
        }
    }
};

// Enhanced count-up animation
const animateCount = (target: number, duration: number = 2000) => {
    const count = ref(0);

    watchEffect((onCleanup) => {
        if (!isLoaded.value) return;

        let current = 0;
        const increment = target / (duration / 16);

        const animate = () => {
            current += increment;
            if (current >= target) {
                count.value = target;
            } else {
                count.value = Math.floor(current);
                requestAnimationFrame(animate);
            }
        };

        const timeout = setTimeout(animate, Math.random() * 500);
        onCleanup(() => clearTimeout(timeout));
    });

    return count;
};

const animatedCounts = {
    userCount: animateCount(page.props.userCount, 1500),
    previewCount: animateCount(page.props.previewCount, 1800),
    bannerCount: animateCount(page.props.bannerCount, 2000),
    videoCount: animateCount(page.props.videoCount, 2200),
    gifCount: animateCount(page.props.gifCount, 2400),
    socialCount: animateCount(page.props.socialCount, 2600),
    fileTransferCount: animateCount(page.props.fileTransferCount, 1700),
    totalBill: animateCount(page.props.totalBill, 2800),
};

const monthlyBillTotals = computed(() => page.props.monthlyBillTotals ?? {});
const monthlyPreviewStats = computed(() => page.props.monthlyPreviewStats ?? {});

// Doughnut chart for content distribution
const contentDistributionData = computed(() => ({
    labels: ['Banners', 'Videos', 'GIFs', 'Socials'],
    datasets: [{
        data: [
            page.props.bannerCount,
            page.props.videoCount,
            page.props.gifCount,
            page.props.socialCount
        ],
        backgroundColor: [
            '#6366f1',
            '#10b981',
            '#f59e0b',
            '#ef4444'
        ],
        borderWidth: 0,
        hoverOffset: 10,
    }]
}));

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 2000,
        easing: 'easeOutCubic',
    },
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                padding: 15,
                usePointStyle: true,
            }
        },
        title: {
            display: true,
            text: 'Content Distribution',
            font: { size: 16, weight: 'bold' },
            padding: 20,
        }
    },
    cutout: '60%',
};

const billChartData = computed(() => ({
    labels,
    datasets: [
        {
            label: 'Monthly Bills (BDT)',
            data: labels.map((_, i) => monthlyBillTotals.value[i + 1] || 0),
            backgroundColor: 'rgba(99,102,241,0.8)',
            borderColor: '#6366f1',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        },
    ],
}));

const billChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 2000,
        easing: 'easeOutCubic',
    },
    plugins: {
        legend: {
            display: false
        },
        title: {
            display: true,
            text: `Monthly Bills Overview (${year})`,
            font: { size: 16, weight: 'bold' },
            padding: 20,
        }
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { font: { weight: 'bold' } }
        },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(0, 0, 0, 0.1)' },
            ticks: {
                callback: (value) => `৳${value}`
            }
        }
    }
};

const previewChartData = computed(() => ({
    labels,
    datasets: [
        {
            label: 'Monthly Previews',
            data: labels.map((_, i) => monthlyPreviewStats.value[i + 1] || 0),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6,
        },
    ],
}));

const previewChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 2000,
        easing: 'easeOutCubic',
    },
    plugins: {
        legend: { display: false },
        title: {
            display: true,
            text: `Monthly Previews Statistics (${year})`,
            font: { size: 16, weight: 'bold' },
            padding: 20,
        }
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { font: { weight: 'bold' } }
        },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(0, 0, 0, 0.1)' }
        }
    }
};

// Calculate growth percentages
const currentMonthData = computed(() => {
    const current = currentMonth + 1;
    const previous = current === 1 ? 12 : current - 1;

    return {
        previews: {
            current: monthlyPreviewStats.value[current] || 0,
            previous: monthlyPreviewStats.value[previous] || 0,
        },
        bills: {
            current: monthlyBillTotals.value[current] || 0,
            previous: monthlyBillTotals.value[previous] || 0,
        }
    };
});

const calculateGrowth = (current: number, previous: number) => {
    if (previous === 0) return current > 0 ? 100 : 0;
    return Math.round(((current - previous) / previous) * 100);
};

const formatNumber = (num: number) => {
    return new Intl.NumberFormat().format(num);
};
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-black dark:via-black dark:to-black">
            <div class="p-6 space-y-8">
                <!-- Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 lg:gap-0">
                    <div class="flex-1">
                        <h1
                            class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            Dashboard Overview
                        </h1>
                        <p class="text-gray-600 dark:text-white mt-1 text-sm md:text-base">
                            Analytics and insights for {{ year }}
                        </p>
                    </div>

                    <div class="space-y-3">
                        <!-- World Clocks - Responsive Layout -->
                        <div
                            class="bg-gradient-to-r from-indigo-300 to-blue-300 dark:from-neutral-800 dark:to-neutral-800 rounded-xl p-3 md:p-3">
                            <!-- Mobile: Stacked Layout -->
                            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 md:justify-around sm:justify-around">
                                <!-- Bangladesh -->
                                <div
                                    class="flex items-center justify-between bg-white dark:bg-neutral-900 rounded-lg p-2 md:p-3 sm:w-full lg:w-fit shadow-sm gap-4">
                                    <div class="flex items-center space-x-2 md:space-x-3 min-w-0">
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="text-xs md:text-sm font-medium text-gray-900 dark:text-white truncate">
                                                Dhaka
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Bangladesh
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right ml-2">
                                        <div
                                            class="text-xs md:text-sm font-mono font-bold text-green-600 dark:text-green-400">
                                            {{ bangladeshTime.time }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ bangladeshTime.date }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Netherlands -->
                                <div
                                    class="flex items-center justify-between bg-white dark:bg-neutral-900 rounded-lg p-2 md:p-3 sm:w-full lg:w-fit shadow-sm gap-4">
                                    <div class="flex items-center space-x-2 md:space-x-3 min-w-0">
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="text-xs md:text-sm font-medium text-gray-900 dark:text-white truncate">
                                                Amsterdam
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Netherlands
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right ml-2">
                                        <div
                                            class="text-xs md:text-sm font-mono font-bold text-blue-600 dark:text-blue-400">
                                            {{ netherlandsTime.time }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ netherlandsTime.date }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Users Card -->
                    <div
                        class="group relative bg-white dark:bg-neutral-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg hover:border-orange-300 dark:hover:border-orange-600 transition-all duration-300 overflow-hidden">
                        <div
                            class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-orange-500/10 to-500/10 rounded-full -translate-y-10 translate-x-10">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-20 h-20 bg-gradient-to-br from-orange-500/10 to-500/10 rounded-full translate-y-10 -translate-x-10">
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-white">Total Users</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                    {{ formatNumber(animatedCounts.userCount.value) }}
                                </p>
                            </div>
                            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
                                <UsersRound class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Previews Card -->
                    <div
                        class="group relative bg-white dark:bg-neutral-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg hover:border-green-300 dark:hover:border-green-600 transition-all duration-300 overflow-hidden">
                        <div
                            class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-full -translate-y-10 translate-x-10">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-20 h-20 bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-full translate-y-10 -translate-x-10">
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-white">Total Previews</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                    {{ formatNumber(animatedCounts.previewCount.value) }}
                                </p>
                            </div>
                            <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
                                <MonitorStop class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Content Cards Row -->
                    <div class="col-span-1 sm:col-span-2 lg:col-span-2 grid grid-cols-2 gap-4">
                        <div
                            class="bg-white dark:bg-neutral-800 rounded-2xl p-4 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg hover:border-purple-300 dark:hover:border-purple-600 transition-all duration-300">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-white">Banners</p>
                                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                        {{ formatNumber(animatedCounts.bannerCount.value) }}
                                    </p>
                                </div>
                                <div
                                    class="w-8 h-8 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                                    <MonitorCog class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-neutral-800 rounded-2xl p-4 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-600 transition-all duration-300">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-white">Videos</p>
                                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ formatNumber(animatedCounts.videoCount.value) }}
                                    </p>
                                </div>
                                <div
                                    class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                                    <Video class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-neutral-800 rounded-2xl p-4 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg hover:border-yellow-300 dark:hover:border-yellow-600 transition-all duration-300">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-white">GIFs</p>
                                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                        {{ formatNumber(animatedCounts.gifCount.value) }}
                                    </p>
                                </div>
                                <div
                                    class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg flex items-center justify-center">
                                    <ImagePlay class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-neutral-800 rounded-2xl p-4 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg hover:border-pink-300 dark:hover:border-pink-600 transition-all duration-300">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-white">Socials</p>
                                    <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">
                                        {{ formatNumber(animatedCounts.socialCount.value) }}
                                    </p>
                                </div>
                                <div
                                    class="w-8 h-8 bg-pink-100 dark:bg-pink-900/50 rounded-lg flex items-center justify-center">
                                    <Wallpaper class="w-4 h-4 text-pink-600 dark:text-pink-400" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Stats Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div
                        class="bg-white dark:bg-neutral-800 rounded-2xl p-6 shadow-sm border border-gray-200 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-lg transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-white">File Transfers</p>
                                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ formatNumber(animatedCounts.fileTransferCount.value) }}
                                </p>
                            </div>
                            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
                                <Paperclip class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100">Total Bills (BDT)</p>
                                <p class="text-3xl font-bold">
                                    ৳{{ formatNumber(animatedCounts.totalBill.value) }}
                                </p>
                                <div class="flex items-center mt-2">
                                    <span class="text-green-200 text-sm font-medium">
                                        {{ calculateGrowth(currentMonthData.bills.current,
                                            currentMonthData.bills.previous) }}%
                                    </span>
                                    <span class="text-xs text-green-200 ml-1">vs last month</span>
                                </div>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <PiggyBank class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 ">
                    <!-- Content Trends Chart -->
                    <div
                        class="lg:col-span-2 bg-white dark:bg-neutral-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg transition-all duration-300">
                        <div style="height: 400px;">
                            <Line :data="chartData" :options="chartOptions" />
                        </div>
                    </div>

                    <!-- Content Distribution Pie Chart -->
                    <div
                        class="bg-white dark:bg-neutral-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg transition-all duration-300">
                        <div style="height: 400px;">
                            <Doughnut :data="contentDistributionData" :options="doughnutOptions" />
                        </div>
                    </div>
                </div>

                <!-- Bottom Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Preview Statistics -->
                    <div
                        class="bg-white dark:bg-neutral-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg transition-all duration-300">
                        <div style="height: 350px;">
                            <Line :data="previewChartData" :options="previewChartOptions" />
                        </div>
                    </div>

                    <!-- Bills Overview -->
                    <div
                        class="bg-white dark:bg-neutral-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-neutral-700 hover:shadow-lg transition-all duration-300">
                        <div style="height: 350px;">
                            <Bar :data="billChartData" :options="billChartOptions" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.group:hover .absolute {
    animation: pulse 2s infinite;
}

@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: .5;
    }
}
</style>