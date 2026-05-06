<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Sparkles } from 'lucide-vue-next';

interface Props {
    name: string;
    autoHideMs?: number;
}
const props = withDefaults(defineProps<Props>(), { autoHideMs: 2800 });

const visible = ref(false);
const firstName = (props.name.split(' ')[0] || props.name).trim();

const greetingByHour = (() => {
    const h = new Date().getHours();
    if (h < 5) return 'Working late';
    if (h < 12) return 'Good morning';
    if (h < 17) return 'Good afternoon';
    if (h < 21) return 'Good evening';
    return 'Welcome back';
})();

let timer: ReturnType<typeof setTimeout> | null = null;

function dismiss() { visible.value = false; }

onMounted(() => {
    // Lock scroll while the overlay is visible.
    document.body.style.overflow = 'hidden';
    // Next frame so the enter transition actually animates.
    requestAnimationFrame(() => { visible.value = true; });
    timer = setTimeout(dismiss, props.autoHideMs);
});

onBeforeUnmount(() => {
    if (timer) clearTimeout(timer);
    document.body.style.overflow = '';
});
</script>

<template>
    <Transition
        appear
        enter-active-class="transition-opacity duration-500 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-700 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
        @after-leave="$emit('done')"
    >
        <div
            v-if="visible"
            class="welcome-overlay fixed inset-0 z-[100] flex items-center justify-center"
            role="status"
            aria-live="polite"
            @click="dismiss"
        >
            <!-- Soft, blurred dim layer -->
            <div class="absolute inset-0 bg-background/70 backdrop-blur-md" />

            <!-- Animated radial glow -->
            <div class="welcome-glow absolute inset-0 pointer-events-none" />

            <!-- Floating sparkle particles -->
            <span
                v-for="i in 14"
                :key="i"
                class="welcome-particle absolute rounded-full bg-primary/40"
                :style="{
                    left: ((i * 73) % 100) + '%',
                    top: ((i * 41 + 13) % 100) + '%',
                    width: (4 + (i % 3) * 2) + 'px',
                    height: (4 + (i % 3) * 2) + 'px',
                    animationDelay: (i * 90) + 'ms',
                    animationDuration: (1800 + (i * 60) % 900) + 'ms',
                }"
            />

            <!-- Hero card -->
            <Transition
                appear
                enter-active-class="transition duration-700 ease-out"
                enter-from-class="opacity-0 scale-90 translate-y-3"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-400 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="visible"
                    class="relative px-10 py-12 text-center select-none max-w-[90vw]"
                >
                    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary ring-1 ring-primary/20">
                        <Sparkles class="h-8 w-8 welcome-icon-pulse" />
                    </div>
                    <p class="text-base uppercase tracking-[0.4em] text-muted-foreground mb-3">
                        {{ greetingByHour }}
                    </p>
                    <h1 class="welcome-headline text-5xl sm:text-7xl font-semibold tracking-tight">
                        {{ firstName }}
                    </h1>
                    <p class="mt-5 text-lg text-muted-foreground">
                        Have a pleasant day today.
                    </p>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<style scoped>
.welcome-glow {
    background: radial-gradient(ellipse at center, hsl(var(--primary) / 0.18) 0%, hsl(var(--primary) / 0.04) 35%, transparent 70%);
    animation: welcome-glow-breathe 2.8s ease-in-out forwards;
}
@keyframes welcome-glow-breathe {
    0%   { transform: scale(0.6); opacity: 0; }
    35%  { transform: scale(1.05); opacity: 1; }
    100% { transform: scale(1.4); opacity: 0; }
}

.welcome-headline {
    background: linear-gradient(120deg, hsl(var(--foreground)) 0%, hsl(var(--primary)) 50%, hsl(var(--foreground)) 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    animation: welcome-shimmer 2.4s linear forwards;
}
@keyframes welcome-shimmer {
    from { background-position: 0% center; }
    to   { background-position: 200% center; }
}

.welcome-icon-pulse {
    animation: welcome-icon-spin 2.4s ease-in-out forwards;
}
@keyframes welcome-icon-spin {
    0%   { transform: rotate(-25deg) scale(0.6); opacity: 0.4; }
    25%  { transform: rotate(0deg) scale(1.15); opacity: 1; }
    100% { transform: rotate(8deg) scale(1); opacity: 1; }
}

.welcome-particle {
    animation-name: welcome-float;
    animation-iteration-count: 1;
    animation-fill-mode: forwards;
    animation-timing-function: ease-out;
    opacity: 0;
}
@keyframes welcome-float {
    0%   { transform: translateY(20px) scale(0.5); opacity: 0; }
    25%  { opacity: 0.9; }
    100% { transform: translateY(-40px) scale(1.1); opacity: 0; }
}
</style>
