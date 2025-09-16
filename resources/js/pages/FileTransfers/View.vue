<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import * as THREE from 'three';

const props = defineProps<{
    fileTransfer: { file_paths: string[] };
}>();

let renderer: THREE.WebGLRenderer;
let scene: THREE.Scene;
let camera: THREE.PerspectiveCamera;
let animationId: number;
let smallEarth: THREE.Mesh;

onMounted(() => {
    const canvas = document.getElementById('starfield') as HTMLCanvasElement;

    // Scene setup
    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(
        75,
        window.innerWidth / window.innerHeight,
        0.1,
        1000
    );
    camera.position.z = 5;

    renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(window.devicePixelRatio);

    // --- STARFIELD ---
    const starCount = window.innerWidth < 768 ? 800 : 2000;
    const radius = 300;
    const positions = [];
    for (let i = 0; i < starCount; i++) {
        const phi = Math.acos(2 * Math.random() - 1);
        const theta = 2 * Math.PI * Math.random();
        const r = radius * Math.cbrt(Math.random());
        positions.push(
            r * Math.sin(phi) * Math.cos(theta),
            r * Math.sin(phi) * Math.sin(theta),
            r * Math.cos(phi)
        );
    }
    const starsGeometry = new THREE.BufferGeometry();
    starsGeometry.setAttribute(
        'position',
        new THREE.Float32BufferAttribute(positions, 3)
    );
    const starTexture = new THREE.TextureLoader().load(
        'https://threejs.org/examples/textures/sprites/disc.png'
    );
    const starsMaterial = new THREE.PointsMaterial({
        size: 1.2,
        map: starTexture,
        transparent: true,
        opacity: 0.7,
        blending: THREE.AdditiveBlending,
        depthWrite: false,
    });
    const stars = new THREE.Points(starsGeometry, starsMaterial);
    scene.add(stars);

    // --- CENTER PLANET ---
    const planetTexture = new THREE.TextureLoader().load('/Transfer Files/earth.svg');
    const planet = new THREE.Mesh(
        new THREE.SphereGeometry(1, 64, 64),
        new THREE.MeshStandardMaterial({ map: planetTexture })
    );
    scene.add(planet);

    const glow = new THREE.Mesh(
        new THREE.SphereGeometry(1.3, 64, 64),
        new THREE.MeshBasicMaterial({
            color: 0x4facfe,
            transparent: true,
            opacity: 0.2,
            side: THREE.BackSide,
        })
    );
    scene.add(glow);

    // --- SMALL FLOATING EARTH ---
    const smallEarthTexture = new THREE.TextureLoader().load('/Transfer Files/earth.svg');
    smallEarth = new THREE.Mesh(
        new THREE.SphereGeometry(0.3, 32, 32),
        new THREE.MeshStandardMaterial({ map: smallEarthTexture })
    );
    smallEarth.position.set(-2.5, 1.8, -2); // top-left corner placement
    scene.add(smallEarth);

    // Lighting
    const ambient = new THREE.AmbientLight(0xffffff, 0.5);
    const point = new THREE.PointLight(0x9cf, 1.5);
    point.position.set(5, 5, 5);
    scene.add(ambient, point);

    const mouse = { x: 0, y: 0 };
    window.addEventListener('mousemove', (e) => {
        mouse.x = (e.clientX / window.innerWidth - 0.5) * 0.4;
        mouse.y = (e.clientY / window.innerHeight - 0.5) * 0.4;
    });

    // Animation
    let lastTime = 0;
    const animate = (time = 0) => {
        const delta = time - lastTime;
        if (delta > 16) {
            planet.rotation.y += 0.0015;
            glow.rotation.y += 0.001;
            stars.rotation.y += 0.0005;

            if (smallEarth) {
                smallEarth.rotation.y += 0.01; // Spin small Earth
                smallEarth.position.y = 1.8 + Math.sin(time * 0.001) * 0.05; // Float slightly
            }

            camera.rotation.x += (mouse.y - camera.rotation.x) * 0.02;
            camera.rotation.y += (mouse.x - camera.rotation.y) * 0.02;

            renderer.render(scene, camera);
            lastTime = time;
        }
        animationId = requestAnimationFrame(animate);
    };
    animate();

    const handleResize = () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    };
    window.addEventListener('resize', handleResize);

    onUnmounted(() => {
        cancelAnimationFrame(animationId);
        window.removeEventListener('resize', handleResize);
        scene.traverse((object) => {
            if ((object as THREE.Mesh).geometry)
                (object as THREE.Mesh).geometry.dispose();
            if ((object as THREE.Mesh).material) {
                const mat = (object as THREE.Mesh).material as THREE.Material;
                mat.dispose();
            }
        });
        renderer.dispose();
    });
});
</script>

<template>

    <Head title="Planet Nine Transfer" />
    <div class="relative min-h-screen w-screen overflow-hidden bg-[#0a0a1a] text-white">
        <canvas id="starfield" class="absolute top-0 left-0 h-full w-full"></canvas>

        <!-- Decorative Assets -->
        <img class="object_rocket" src="/Transfer Files/rocket.svg" alt="rocket" aria-hidden="true" />
        <img class="object_moon" src="/Transfer Files/download.png" alt="moon" aria-hidden="true" />
        <div class="box_astronaut">
            <img id="astronaut" class="object_astronaut" src="/Transfer Files/astronaut.svg" alt="astronaut" />
        </div>

        <!-- Download Panel -->
        <div class="relative z-10 flex min-h-screen items-center justify-center px-4">
            <div
                class="w-full max-w-3xl rounded-2xl bg-white/10 p-6 sm:p-8 shadow-xl backdrop-blur-lg border border-white/20">
                <h1
                    class="mb-4 flex items-center gap-2 text-left text-2xl sm:text-3xl font-extrabold text-yellow-300 drop-shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"
                        class="w-8 h-8 text-yellow-300 drop-shadow-md animate-pulse">
                        <path d="M12 2C8 6 7 10 7 13l-4 4 3 3 4-4c3 0 7-1 11-5-1-4-5-8-9-9Z" />
                        <circle cx="12" cy="12" r="1.5" fill="#1a1a2e" />
                    </svg>
                    Planet Nine Transfer
                </h1>
                <p class="mb-6 text-left text-sm sm:text-base text-blue-200">
                    Transfer files are ready! May the high grounds and files be with you.
                </p>
                <div class="space-y-4">
                    <div v-for="(file, index) in fileTransfer.file_paths" :key="index"
                        class="flex items-center justify-between rounded-md bg-white/10 px-3 sm:px-4 py-2 sm:py-3 shadow hover:bg-white/20 transition">
                        <span class="truncate text-sm sm:text-base">{{ file }}</span>
                        <a :href="`/Transfer Files/${file}`" download
                            class="mt-2 sm:mt-0 rounded bg-yellow-400 px-3 sm:px-4 py-1 sm:py-2 text-sm font-medium text-black transition hover:bg-yellow-300 focus:outline-dashed focus:outline-2 focus:outline-yellow-400">
                            Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css?family=Dosis:300,400,500');

.object_rocket {
    position: absolute;
    top: 70%;
    left: 10%;
    width: 50px;
    animation: rocket-float 6s ease-in-out infinite alternate;
    will-change: transform;
}

.object_moon {
    position: absolute;
    top: 10%;
    left: 30%;
    width: 90px;
    opacity: 0.2;
    animation: slow-rotate 15s linear infinite reverse;
}

.box_astronaut {
    position: absolute;
    top: 55%;
    right: 20%;
    z-index: 5;
}

.object_astronaut {
    width: 150px;
    animation: float-around 18s ease-in-out infinite alternate;
    will-change: transform;
}

@keyframes float-around {
    0% {
        transform: translate(0, 0) rotate(0deg);
    }

    25% {
        transform: translate(-20px, -30px) rotate(-5deg);
    }

    50% {
        transform: translate(30px, -10px) rotate(10deg);
    }

    75% {
        transform: translate(-15px, 25px) rotate(-8deg);
    }

    100% {
        transform: translate(20px, 15px) rotate(5deg);
    }
}

@keyframes rocket-float {
    0% {
        transform: translateY(0px) rotate(-10deg);
    }

    100% {
        transform: translateY(-20px) rotate(10deg);
    }
}

@keyframes slow-rotate {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

@media (max-width: 768px) {
    .object_rocket {
        width: 35px;
        top: 75%;
    }

    .object_moon {
        width: 70px;
        top: 12%;
    }

    .object_astronaut {
        width: 120px;
    }

    .box_astronaut {
        top: 60%;
        right: 15%;
    }
}

@media (max-width: 480px) {
    .object_rocket {
        width: 25px;
    }

    .object_moon {
        width: 50px;
    }

    .object_astronaut {
        width: 90px;
    }
}
</style>
