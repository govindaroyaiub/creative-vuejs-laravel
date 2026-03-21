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
    // Add these missing ones
    RadialLinearScale,
    TimeScale,
    TimeSeriesScale,
} from 'chart.js';
import { computed, ref, watchEffect, onMounted, onUnmounted } from 'vue';
import { type BreadcrumbItem } from '@/types';

ChartJS.register(
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
    RadialLinearScale,
    TimeScale,
    TimeSeriesScale
);
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

    setTimeout(() => {
        isLoaded.value = true;
    }, 100);
});

onUnmounted(() => {
    if (timeInterval) {
        clearInterval(timeInterval);
    }
});

// Helper function to determine time of day styling
const getTimeOfDayStyle = (dateTime: Date) => {
    const hour = dateTime.getHours();

    if (hour >= 5 && hour < 7) {
        // Early Morning (Dawn) - soft pinks and light blues
        return {
            gradient: 'from-pink-200 via-orange-100 to-blue-200',
            darkGradient: 'dark:from-pink-900/40 dark:via-orange-900/40 dark:to-blue-900/40',
            period: '🌅 Dawn',
            textColor: 'text-orange-900 dark:text-orange-200'
        };
    } else if (hour >= 7 && hour < 12) {
        // Morning - warm yellows and light
        return {
            gradient: 'from-yellow-200 via-amber-100 to-orange-100',
            darkGradient: 'dark:from-yellow-900/40 dark:via-amber-900/40 dark:to-orange-900/40',
            period: '☀️ Morning',
            textColor: 'text-amber-900 dark:text-amber-200'
        };
    } else if (hour >= 12 && hour < 17) {
        // Afternoon - bright and vibrant
        return {
            gradient: 'from-sky-200 via-cyan-100 to-blue-200',
            darkGradient: 'dark:from-sky-900/40 dark:via-cyan-900/40 dark:to-blue-900/40',
            period: '🌞 Afternoon',
            textColor: 'text-sky-900 dark:text-sky-200'
        };
    } else if (hour >= 17 && hour < 19) {
        // Evening (Dusk) - oranges and purples
        return {
            gradient: 'from-orange-200 via-pink-200 to-purple-200',
            darkGradient: 'dark:from-orange-900/40 dark:via-pink-900/40 dark:to-purple-900/40',
            period: '🌆 Dusk',
            textColor: 'text-purple-900 dark:text-purple-200'
        };
    } else if (hour >= 19 && hour < 22) {
        // Evening - deep oranges to blues
        return {
            gradient: 'from-indigo-200 via-purple-200 to-blue-300',
            darkGradient: 'dark:from-indigo-900/40 dark:via-purple-900/40 dark:to-blue-900/40',
            period: '🌃 Evening',
            textColor: 'text-indigo-900 dark:text-indigo-200'
        };
    } else {
        // Night - deep blues and purples
        return {
            gradient: 'from-indigo-300 via-blue-400 to-slate-400',
            darkGradient: 'dark:from-indigo-950/60 dark:via-blue-950/60 dark:to-slate-950/60',
            period: '🌙 Night',
            textColor: 'text-slate-100 dark:text-slate-300'
        };
    }
};

// Bangladesh Time (Asia/Dhaka)
const bangladeshTime = computed(() => {
    const bdTime = new Date(currentTime.value.toLocaleString("en-US", { timeZone: "Asia/Dhaka" }));
    const style = getTimeOfDayStyle(bdTime);
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
        }),
        ...style
    };
});

// Netherlands Time (Europe/Amsterdam)
const netherlandsTime = computed(() => {
    const nlTime = new Date(currentTime.value.toLocaleString("en-US", { timeZone: "Europe/Amsterdam" }));
    const style = getTimeOfDayStyle(nlTime);
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
        }),
        ...style
    };
});

// Silicon Valley Time (America/Los_Angeles)
const siliconValleyTime = computed(() => {
    const svTime = new Date(currentTime.value.toLocaleString("en-US", { timeZone: "America/Los_Angeles" }));
    const style = getTimeOfDayStyle(svTime);
    return {
        time: svTime.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        }),
        date: svTime.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        }),
        ...style
    };
});

// Toronto Time (America/Toronto)
const torontoTime = computed(() => {
    const toTime = new Date(currentTime.value.toLocaleString("en-US", { timeZone: "America/Toronto" }));
    const style = getTimeOfDayStyle(toTime);
    return {
        time: toTime.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        }),
        date: toTime.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        }),
        ...style
    };
});

// London Time (Europe/London)
const londonTime = computed(() => {
    const ldnTime = new Date(currentTime.value.toLocaleString("en-US", { timeZone: "Europe/London" }));
    const style = getTimeOfDayStyle(ldnTime);
    return {
        time: ldnTime.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        }),
        date: ldnTime.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        }),
        ...style
    };
});

// Canberra Time (Australia/Canberra)
const canberraTime = computed(() => {
    const canTime = new Date(currentTime.value.toLocaleString("en-US", { timeZone: "Australia/Canberra" }));
    const style = getTimeOfDayStyle(canTime);
    return {
        time: canTime.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        }),
        date: canTime.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        }),
        ...style
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
            text: `Yearly Content Statistics (${year})`,
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
            ticks: {
                font: { weight: 'bold' },
                color: 'rgba(0, 0, 0, 0.7)' // Add text color
            }
        },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(0, 0, 0, 0.1)' },
            ticks: {
                color: 'rgba(0, 0, 0, 0.7)' // Add text color
            }
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
    userCount: animateCount(page.props.userCount, 2000),
    previewCount: animateCount(page.props.previewCount, 1000),
    bannerCount: animateCount(page.props.bannerCount, 1000),
    videoCount: animateCount(page.props.videoCount, 1000),
    gifCount: animateCount(page.props.gifCount, 1000),
    socialCount: animateCount(page.props.socialCount, 2600),
    fileTransferCount: animateCount(page.props.fileTransferCount, 2000),
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
            backgroundColor: 'rgba(34, 197, 94, 0.8)',
            borderColor: '#22c55e',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
            hoverBackgroundColor: 'rgba(34, 197, 94, 0.9)',
            hoverBorderColor: '#16a34a',
            hoverBorderWidth: 3,
        },
    ],
}));

const previewChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 2500,
        easing: 'easeOutElastic',
    },
    plugins: {
        legend: {
            display: false
        },
        title: {
            display: true,
            text: `Monthly Previews Statistics (${year})`,
            font: { size: 16, weight: 'bold' },
            padding: 20,
        },
        tooltip: {
            backgroundColor: 'rgba(34, 197, 94, 0.9)',
            titleColor: 'white',
            bodyColor: 'white',
            borderColor: '#22c55e',
            borderWidth: 2,
            cornerRadius: 8,
            callbacks: {
                title: (context) => `${context[0].label} ${year}`,
                label: (context) => `Previews: ${context.parsed.y}`,
            }
        }
    },
    scales: {
        x: {
            grid: {
                display: false
            },
            ticks: {
                font: { weight: 'bold' },
                color: 'rgba(0, 0, 0, 0.7)'
            }
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(34, 197, 94, 0.1)',
                lineWidth: 1
            },
            ticks: {
                color: 'rgba(0, 0, 0, 0.7)',
                callback: (value) => `${value} previews`
            }
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
            class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-50 to-gray-50 dark:from-black dark:via-black dark:to-black">
            <div class="p-6 space-y-8">
                <!-- Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 lg:gap-0">
                    <div class="flex-1">
                        <h1
                            class="text-2xl md:text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            Dashboard Overview
                        </h1>
                        <p class="text-gray-600 dark:text-white mt-1 text-sm md:text-base">
                            Analytics and insights for {{ year }}
                        </p>
                    </div>

                    <div class="space-y-3">
                        <!-- World Clocks - Responsive Layout -->
                        <div
                            class="bg-gradient-to-r from-neutral-300 to-neutral-300 dark:from-neutral-800 dark:to-neutral-800 rounded-xl p-2 md:p-3">
                            <!-- Mobile: Single Column | Tablet: 2 Columns | Desktop: 3x2 Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                <!-- Bangladesh -->
                                <div
                                    :class="['flex flex-col bg-gradient-to-br rounded-lg p-2 md:p-2.5 shadow-lg transition-all duration-500', bangladeshTime.gradient, bangladeshTime.darkGradient]">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="text-xs md:text-sm font-bold text-gray-900 dark:text-white truncate">
                                                Dhaka
                                            </div>
                                            <div class="text-[10px] md:text-xs text-gray-700 dark:text-gray-300">
                                                Bangladesh
                                            </div>
                                        </div>
                                        <div
                                            :class="['text-[10px] md:text-xs font-semibold px-1.5 py-0.5 rounded-full bg-white/50 dark:bg-black/30', bangladeshTime.textColor]">
                                            {{ bangladeshTime.period }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div
                                            class="text-base md:text-lg font-mono font-bold text-gray-900 dark:text-white">
                                            {{ bangladeshTime.time }}
                                        </div>
                                        <div class="text-[10px] md:text-xs text-gray-700 dark:text-gray-300">
                                            {{ bangladeshTime.date }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Netherlands -->
                                <div
                                    :class="['flex flex-col bg-gradient-to-br rounded-lg p-2 md:p-2.5 shadow-lg transition-all duration-500', netherlandsTime.gradient, netherlandsTime.darkGradient]">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="text-xs md:text-sm font-bold text-gray-900 dark:text-white truncate">
                                                Amsterdam
                                            </div>
                                            <div class="text-[10px] md:text-xs text-gray-700 dark:text-gray-300">
                                                Netherlands
                                            </div>
                                        </div>
                                        <div
                                            :class="['text-[10px] md:text-xs font-semibold px-1.5 py-0.5 rounded-full bg-white/50 dark:bg-black/30', netherlandsTime.textColor]">
                                            {{ netherlandsTime.period }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div
                                            class="text-base md:text-lg font-mono font-bold text-gray-900 dark:text-white">
                                            {{ netherlandsTime.time }}
                                        </div>
                                        <div class="text-[10px] md:text-xs text-gray-700 dark:text-gray-300">
                                            {{ netherlandsTime.date }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Silicon Valley -->
                                <div
                                    :class="['flex flex-col bg-gradient-to-br rounded-lg p-2 md:p-2.5 shadow-lg transition-all duration-500', siliconValleyTime.gradient, siliconValleyTime.darkGradient]">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="text-xs md:text-sm font-bold text-gray-900 dark:text-white truncate">
                                                Silicon Valley
                                            </div>
                                            <div class="text-[10px] md:text-xs text-gray-700 dark:text-gray-300">
                                                California, USA
                                            </div>
                                        </div>
                                        <div
                                            :class="['text-[10px] md:text-xs font-semibold px-1.5 py-0.5 rounded-full bg-white/50 dark:bg-black/30', siliconValleyTime.textColor]">
                                            {{ siliconValleyTime.period }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div
                                            class="text-base md:text-lg font-mono font-bold text-gray-900 dark:text-white">
                                            {{ siliconValleyTime.time }}
                                        </div>
                                        <div class="text-[10px] md:text-xs text-gray-700 dark:text-gray-300">
                                            {{ siliconValleyTime.date }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Toronto -->
                                <div
                                    :class="['flex flex-col bg-gradient-to-br rounded-lg p-2 md:p-2.5 shadow-lg transition-all duration-500', torontoTime.gradient, torontoTime.darkGradient]">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="text-xs md:text-sm font-bold text-gray-900 dark:text-white truncate">
                                                Toronto
                                            </div>
                                            <div class="text-[10px] md:text-xs text-gray-700 dark:text-gray-300">
                                                Canada
                                            </div>
                                        </div>
                                        <div
                                            :class="['text-[10px] md:text-xs font-semibold px-1.5 py-0.5 rounded-full bg-white/50 dark:bg-black/30', torontoTime.textColor]">
                                            {{ torontoTime.period }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div
                                            class="text-base md:text-lg font-mono font-bold text-gray-900 dark:text-white">
                                            {{ torontoTime.time }}
                                        </div>
                                        <div class="text-[10px] md:text-xs text-gray-700 dark:text-gray-300">
                                            {{ torontoTime.date }}
                                        </div>
                                    </div>
                                </div>

                                <!-- London -->
                                <div
                                    :class="['flex flex-col bg-gradient-to-br rounded-lg p-2 md:p-2.5 shadow-lg transition-all duration-500', londonTime.gradient, londonTime.darkGradient]">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="text-xs md:text-sm font-bold text-gray-900 dark:text-white truncate">
                                                London
                                            </div>
                                            <div class="text-[10px] md:text-xs text-gray-700 dark:text-gray-300">
                                                United Kingdom
                                            </div>
                                        </div>
                                        <div
                                            :class="['text-[10px] md:text-xs font-semibold px-1.5 py-0.5 rounded-full bg-white/50 dark:bg-black/30', londonTime.textColor]">
                                            {{ londonTime.period }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div
                                            class="text-base md:text-lg font-mono font-bold text-gray-900 dark:text-white">
                                            {{ londonTime.time }}
                                        </div>
                                        <div class="text-[10px] md:text-xs text-gray-700 dark:text-gray-300">
                                            {{ londonTime.date }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Canberra -->
                                <div
                                    :class="['flex flex-col bg-gradient-to-br rounded-lg p-2 md:p-2.5 shadow-lg transition-all duration-500', canberraTime.gradient, canberraTime.darkGradient]">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="text-xs md:text-sm font-bold text-gray-900 dark:text-white truncate">
                                                Canberra
                                            </div>
                                            <div class="text-[10px] md:text-xs text-gray-700 dark:text-gray-300">
                                                Australia
                                            </div>
                                        </div>
                                        <div
                                            :class="['text-[10px] md:text-xs font-semibold px-1.5 py-0.5 rounded-full bg-white/50 dark:bg-black/30', canberraTime.textColor]">
                                            {{ canberraTime.period }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div
                                            class="text-base md:text-lg font-mono font-bold text-gray-900 dark:text-white">
                                            {{ canberraTime.time }}
                                        </div>
                                        <div class="text-[10px] md:text-xs text-gray-700 dark:text-gray-300">
                                            {{ canberraTime.date }}
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
                                <p class="text-3xl font-bold text-orange-600 dark:text-white mt-2">
                                    {{ formatNumber(animatedCounts.userCount.value) }}
                                </p>
                            </div>
                            <div class="p-3 bg-orange-100 dark:bg-blue-900/50 rounded-xl">
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
                                <p class="text-3xl font-bold text-green-600 dark:text-white mt-2">
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
                        class="bg-white dark:bg-neutral-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-neutral-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-lg transition-all duration-300">
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

                    <div
                        class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-all duration-300">
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
                            <Line v-if="chartData && chartData.datasets" :data="chartData" :options="chartOptions" />
                            <div v-else class="flex items-center justify-center h-full text-gray-500">
                                Loading chart...
                            </div>
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
                            <Bar :data="previewChartData" :options="previewChartOptions" />
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