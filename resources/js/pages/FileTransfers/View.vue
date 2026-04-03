<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { animate as animeAnimate, stagger } from 'animejs';

// Helper wrapper for anime.js v4
const anime = (params: any) => animeAnimate(params.targets, params);

const props = defineProps<{
    fileTransfer: { file_paths: string[] };
}>();

onMounted(() => {
    // Create floating stars
    createFloatingStars();

    // Animate page entrance
    setTimeout(() => {
        // Animate planet entrance
        anime({
            targets: '.planet',
            scale: [0, 1],
            opacity: [0, 1],
            duration: 2000,
            easing: 'easeOutElastic(1, .6)'
        });

        // Rotate planet continuously
        anime({
            targets: '.planet',
            rotate: 360,
            duration: 120000,
            loop: true,
            easing: 'linear'
        });

        // Animate logo
        anime({
            targets: '.planet-logo',
            scale: [0, 1],
            rotate: [180, 0],
            opacity: [0, 1],
            duration: 1200,
            easing: 'easeOutElastic(1, .6)'
        });

        // Pulse logo continuously
        anime({
            targets: '.planet-logo',
            scale: [1, 1.05, 1],
            duration: 2000,
            loop: true,
            easing: 'easeInOutQuad'
        });

        // Animate title
        anime({
            targets: '.main-title',
            translateY: [-30, 0],
            opacity: [0, 1],
            duration: 800,
            delay: 300,
            easing: 'easeOutCubic'
        });

        // Animate subtitle
        anime({
            targets: '.subtitle',
            translateY: [-20, 0],
            opacity: [0, 1],
            duration: 800,
            delay: 500,
            easing: 'easeOutCubic'
        });

        // Stagger file cards
        anime({
            targets: '.file-card',
            translateY: [60, 0],
            opacity: [0, 1],
            scale: [0.8, 1],
            duration: 800,
            delay: stagger(120, { start: 700 }),
            easing: 'easeOutBack'
        });

        // Animate footer
        anime({
            targets: '.footer-info',
            translateY: [20, 0],
            opacity: [0, 1],
            duration: 600,
            delay: 1200,
            easing: 'easeOutQuad'
        });
    }, 100);
});

// Create CSS-based floating stars
const createFloatingStars = () => {
    const container = document.querySelector('.stars-container');
    if (!container) return;

    const starCount = window.innerWidth < 768 ? 80 : 150;

    for (let i = 0; i < starCount; i++) {
        const star = document.createElement('div');
        star.className = 'star';
        star.style.position = 'absolute';

        // Random position
        const left = Math.random() * 100;
        const top = Math.random() * 100;
        star.style.left = `${left}%`;
        star.style.top = `${top}%`;

        // Random size
        const size = Math.random() * 3 + 2;
        star.style.width = `${size}px`;
        star.style.height = `${size}px`;
        star.style.backgroundColor = '#ffffff';
        star.style.borderRadius = '50%';
        star.style.boxShadow = '0 0 2px #fff';

        container.appendChild(star);

        // Animate with anime.js for reliable glow effect
        const delay = Math.random() * 3000;
        const duration = Math.random() * 2000 + 2000;

        animeAnimate(star, {
            opacity: ['0.3', '1', '0.3'],
            scale: ['0.8', '1.4', '0.8'],
            duration: duration,
            delay: delay,
            easing: 'inOutQuad',
            loop: true
        });
    }
};

// Download button hover animation
const handleDownloadHover = (event: MouseEvent) => {
    const button = event.currentTarget as HTMLElement;

    anime({
        targets: button,
        scale: 1.05,
        duration: 300,
        easing: 'easeOutQuad'
    });
};

const handleDownloadLeave = (event: MouseEvent) => {
    const button = event.currentTarget as HTMLElement;

    anime({
        targets: button,
        scale: 1,
        duration: 300,
        easing: 'easeOutQuad'
    });
};
</script>

<template>

    <Head title="Planet Nine Transfer" />

    <div class="page-wrapper">
        <!-- Animated background -->
        <div class="stars-container"></div>

        <!-- Gradient orbs -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <!-- Planet -->
        <div class="planet">
            <div class="planet-surface"></div>
            <div class="planet-glow"></div>
        </div>

        <!-- Main content -->

        <!-- Main content -->
        <div class="content-wrapper">
            <div class="content-container">

                <!-- Logo -->
                <div class="planet-logo">
                    <svg viewBox="0 0 100 100" class="logo-svg">
                        <!-- Outer ring -->
                        <circle cx="50" cy="50" r="40" fill="none" stroke="url(#grad1)" stroke-width="2" />

                        <!-- Inner planet -->
                        <circle cx="50" cy="50" r="25" fill="url(#grad2)" />

                        <!-- Orbital lines -->
                        <circle cx="50" cy="50" r="32" fill="none" stroke="url(#grad3)" stroke-width="0.5" opacity="0.6"
                            stroke-dasharray="5,5">
                            <animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50"
                                dur="20s" repeatCount="indefinite" />
                        </circle>

                        <!-- Dots -->
                        <circle cx="50" cy="10" r="3" fill="#a78bfa" />
                        <circle cx="50" cy="90" r="3" fill="#ec4899" />
                        <circle cx="10" cy="50" r="3" fill="#6366f1" />
                        <circle cx="90" cy="50" r="3" fill="#8b5cf6" />

                        <defs>
                            <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                                <stop offset="50%" style="stop-color:#8b5cf6;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#ec4899;stop-opacity:1" />
                            </linearGradient>
                            <linearGradient id="grad2" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#4c1d95;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#7c3aed;stop-opacity:1" />
                            </linearGradient>
                            <linearGradient id="grad3" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#a78bfa;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#ec4899;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>

                <!-- Title -->
                <h1 class="main-title">
                    Planet Nine Transfer
                </h1>

                <!-- Subtitle -->
                <p class="subtitle">
                    May the high ground and files be with you 🚀
                </p>

                <!-- File Cards -->
                <div class="files-grid">
                    <div v-for="(file, index) in fileTransfer.file_paths" :key="index" class="file-card">
                        <div class="file-card-inner">
                            <!-- File icon -->
                            <div class="file-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 3v6a1 1 0 001 1h6" />
                                </svg>
                            </div>

                            <!-- File info -->
                            <div class="file-info">
                                <p class="file-name">{{ file }}</p>
                                
                            </div>

                            <!-- Download button -->
                            <a :href="`/Transfer Files/${file}`" download class="download-button"
                                @mouseenter="handleDownloadHover" @mouseleave="handleDownloadLeave">
                                <span>Download</span>
                                <svg class="download-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer-info">
                    <div class="footer-badge">
                        <div class="status-dot"></div>
                        <span>Secure Transfer</span>
                    </div>
                    <div class="footer-divider"></div>
                    <div class="footer-badge">
                        <svg class="footer-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>{{ fileTransfer.file_paths.length }} {{ fileTransfer.file_paths.length === 1 ? 'File' :
                            'Files' }}</span>
                    </div>
                </div>

                <p class="footer-text">
                    Powered by <strong>Planet Nine</strong> · Making file transfers out of this world
                </p>
            </div>
        </div>
    </div>
</template>

<style>
/* Prevent white flash on page load */
body {
    background: #0a0a0a;
    margin: 0;
    padding: 0;
}
</style>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap');

* {
    font-family: 'Space Grotesk', sans-serif;
}

.page-wrapper {
    position: relative;
    min-height: 100vh;
    width: 100vw;
    overflow: hidden;
    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
    opacity: 0;
    animation: fadeInPage 0.8s ease-out forwards;
}

@keyframes fadeInPage {
    to {
        opacity: 1;
    }
}

/* Animated stars background */
.stars-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw;
    height: 100vh;
    z-index: 2;
    pointer-events: none;
    overflow: visible;
}

.star {
    position: absolute;
    filter: drop-shadow(0 0 3px #fff) drop-shadow(0 0 6px rgba(255, 255, 255, 0.6));
    will-change: opacity, transform;
}

@keyframes starGlow {

    0%,
    100% {
        opacity: 0.3;
        transform: scale(0.8);
        box-shadow: 0 0 2px #fff;
    }

    50% {
        opacity: 1;
        transform: scale(1.4);
        box-shadow: 0 0 15px #fff, 0 0 25px rgba(255, 255, 255, 0.8), 0 0 35px rgba(255, 255, 255, 0.5);
    }
}

/* Gradient orbs */
.orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.2;
    animation: float 20s ease-in-out infinite;
    pointer-events: none;
    z-index: 1;
}

.orb-1 {
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, #6366f1 0%, transparent 70%);
    top: -200px;
    left: -200px;
    animation-delay: 0s;
}

.orb-2 {
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, #8b5cf6 0%, transparent 70%);
    bottom: -250px;
    right: -250px;
    animation-delay: 5s;
}

.orb-3 {
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, #ec4899 0%, transparent 70%);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation-delay: 10s;
}

@keyframes float {

    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }

    33% {
        transform: translate(50px, -50px) scale(1.1);
    }

    66% {
        transform: translate(-30px, 30px) scale(0.9);
    }
}

/* Planet - Centered purple gas giant */
.planet {
    position: fixed;
    top: 50%;
    left: 50%;
    width: 300px;
    height: 300px;
    margin-left: -150px;
    margin-top: -150px;
    opacity: 0;
    pointer-events: none;
    z-index: 3;
}

.planet-surface {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #fbbf24, #f59e0b 35%, #d97706 65%, #92400e);
    box-shadow:
        inset -25px -25px 50px rgba(0, 0, 0, 0.6),
        0 0 80px rgba(251, 191, 36, 0.4);
    position: relative;
    z-index: 2;
}

.planet-glow {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 320%;
    height: 90%;
    transform: translate(-50%, -50%) rotateX(75deg);
    border-radius: 50%;
    border: 15px solid transparent;
    border-color: rgba(251, 191, 36, 0.5);
    /* z-index: 1; */
}

/* Content */
.content-wrapper {
    position: relative;
    z-index: 10;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
}

.content-container {
    width: 100%;
    max-width: 56rem;
    text-align: center;
}

/* Logo */
.planet-logo {
    width: 120px;
    height: 120px;
    margin: 0 auto 2rem;
    filter: drop-shadow(0 10px 30px rgba(139, 92, 246, 0.4));
}

.logo-svg {
    width: 100%;
    height: 100%;
}

/* Title */
.main-title {
    font-size: clamp(2.5rem, 6vw, 2rem);
    font-weight: 900;
    background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 50%, #ec4899 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 1rem;
    line-height: 1.1;
}

.subtitle {
    font-size: clamp(1rem, 2.5vw, 1.25rem);
    color: rgba(191, 219, 254);
    margin-bottom: 2.5rem;
    font-weight: 300;
}

/* Files grid */
.files-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 3rem;
}

.file-card {
    opacity: 0;
}

.file-card-inner {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1rem;
    transition: all 0.3s ease;
    overflow: hidden;
}

.file-card-inner::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.file-card-inner:hover {
    border-color: rgba(139, 92, 246, 0.5);
    transform: translateY(-2px);
    box-shadow: 0 20px 40px -10px rgba(139, 92, 246, 0.3);
}

.file-card-inner:hover::before {
    opacity: 1;
}

.file-icon {
    flex-shrink: 0;
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 0.75rem;
    color: white;
}

.file-icon svg {
    width: 1.75rem;
    height: 1.75rem;
}

.file-info {
    flex: 1;
    text-align: left;
    min-width: 0;
}

.file-name {
    font-size: 1rem;
    font-weight: 600;
    color: white;
    margin-bottom: 0.25rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.file-status {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: rgba(147, 197, 253, 0.8);
}

.status-icon {
    width: 1rem;
    height: 1rem;
}

/* Download button */
.download-button {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    border-radius: 0.75rem;
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.download-button::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.download-button:hover::before {
    opacity: 1;
}

.download-button span,
.download-button svg {
    position: relative;
    z-index: 1;
}

.download-icon {
    width: 1.25rem;
    height: 1.25rem;
}

/* Footer */
.footer-info {
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 9999px;
    margin-bottom: 1.5rem;
    opacity: 0;
}

.footer-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: rgba(191, 219, 254, 0.9);
}

.status-dot {
    width: 0.5rem;
    height: 0.5rem;
    background: #10b981;
    border-radius: 50%;
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}

.footer-icon {
    width: 1rem;
    height: 1rem;
}

.footer-divider {
    width: 1px;
    height: 1rem;
    background: rgba(255, 255, 255, 0.2);
}

.footer-text {
    font-size: 0.875rem;
    color: rgba(191, 219, 254, 0.6);
    font-weight: 300;
}

.footer-text strong {
    font-weight: 700;
    background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Responsive */
@media (max-width: 768px) {
    .file-card-inner {
        flex-direction: column;
        text-align: center;
    }

    .file-info {
        text-align: center;
    }

    .file-status {
        justify-content: center;
    }

    .download-button {
        width: 100%;
        justify-content: center;
    }

    .footer-info {
        flex-wrap: wrap;
        justify-content: center;
    }

    /* Smaller planet on mobile */
    .planet {
        width: 200px;
        height: 200px;
        margin-left: -100px;
        margin-top: -100px;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {

    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
