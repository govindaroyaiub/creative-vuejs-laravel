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
let nebulaClouds: THREE.Mesh[] = [];
let shootingStars: THREE.Mesh[] = [];
let asteroidBelt: THREE.Points | null = null;
let orbitalRings: THREE.Object3D[] = [];

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

    // --- CENTER PLANET (Purple Planet) ---
    const planetGeometry = new THREE.SphereGeometry(1, 64, 64);
    const planetMaterial = new THREE.ShaderMaterial({
        uniforms: {
            time: { value: 0 },
            primaryColor: { value: new THREE.Color(0x4c1d95) }, // Deep purple
            secondaryColor: { value: new THREE.Color(0x7c3aed) }, // Brighter purple
            glowColor: { value: new THREE.Color(0xa855f7) } // Purple glow
        },
        vertexShader: `
            varying vec2 vUv;
            varying vec3 vNormal;
            varying vec3 vPosition;
            uniform float time;
            
            void main() {
                vUv = uv;
                vNormal = normalize(normalMatrix * normal);
                vPosition = position;
                
                // Subtle surface displacement
                vec3 pos = position + normal * sin(time * 0.8 + position.x * 8.0) * 0.03;
                gl_Position = projectionMatrix * modelViewMatrix * vec4(pos, 1.0);
            }
        `,
        fragmentShader: `
            uniform float time;
            uniform vec3 primaryColor;
            uniform vec3 secondaryColor;
            uniform vec3 glowColor;
            varying vec2 vUv;
            varying vec3 vNormal;
            varying vec3 vPosition;
            
            void main() {
                // Create surface patterns
                float noise = sin(vPosition.x * 12.0 + time) * 
                             sin(vPosition.y * 10.0 + time * 0.7) * 
                             sin(vPosition.z * 14.0 + time * 0.5);
                
                // Fresnel effect for atmospheric glow
                float fresnel = pow(1.0 - dot(vNormal, vec3(0.0, 0.0, 1.0)), 2.5);
                
                // Mix colors with surface patterns
                vec3 surfaceColor = mix(primaryColor, secondaryColor, noise * 0.4 + 0.3);
                vec3 color = mix(surfaceColor, glowColor, fresnel * 0.6);
                
                // Add pulsing glow effect
                float pulse = 0.85 + sin(time * 2.5) * 0.15;
                color *= pulse;
                
                gl_FragColor = vec4(color, 1.0);
            }
        `
    });
    const planet = new THREE.Mesh(planetGeometry, planetMaterial);
    scene.add(planet);

    const glow = new THREE.Mesh(
        new THREE.SphereGeometry(1.3, 64, 64),
        new THREE.MeshBasicMaterial({
            color: 0x8b5cf6,
            transparent: true,
            opacity: 0.2,
            side: THREE.BackSide,
        })
    );
    scene.add(glow);

    // --- ORBITAL RINGS (decorative, subtle) ---
    const ringColors = [0x5b21b6, 0x7c3aed, 0xfb7185];
    for (let i = 0; i < 3; i++) {
        const inner = 1.6 + i * 0.28;
        const outer = inner + 0.02;
        const ringGeo = new THREE.RingGeometry(inner, outer, 128);
        const ringMat = new THREE.MeshBasicMaterial({
            color: ringColors[i],
            transparent: true,
            opacity: 0.22,
            side: THREE.DoubleSide,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
        });
        const ringMesh = new THREE.Mesh(ringGeo, ringMat);
        ringMesh.rotation.x = Math.PI / 2 + (i * 0.12);
        ringMesh.rotation.z = i * 0.5;
        scene.add(ringMesh);
        orbitalRings.push(ringMesh);
    }

    // --- ASTEROID BELT (points) ---
    const asteroidCount = 420;
    const aPos: number[] = [];
    const aCol: number[] = [];
    for (let i = 0; i < asteroidCount; i++) {
        const ang = Math.random() * Math.PI * 2;
        const r = 3.6 + Math.random() * 1.6;
        const x = Math.cos(ang) * r;
        const y = (Math.random() - 0.5) * 0.6;
        const z = Math.sin(ang) * r;
        aPos.push(x, y, z);
        const c = new THREE.Color().setHSL(0.08 + Math.random() * 0.05, 0.4, 0.35 + Math.random() * 0.15);
        aCol.push(c.r, c.g, c.b);
    }
    const astGeo = new THREE.BufferGeometry();
    astGeo.setAttribute('position', new THREE.Float32BufferAttribute(aPos, 3));
    astGeo.setAttribute('color', new THREE.Float32BufferAttribute(aCol, 3));
    const astMat = new THREE.PointsMaterial({ size: 0.06, vertexColors: true, transparent: true, opacity: 0.9 });
    asteroidBelt = new THREE.Points(astGeo, astMat);
    scene.add(asteroidBelt);

    // --- NEBULA CLOUDS (soft additive spheres with shader) ---
    const nebulaDefs = [
        { color: 0xff6b9d, pos: new THREE.Vector3(-9, 6, -18), scale: 4.5 },
        { color: 0x4dabf7, pos: new THREE.Vector3(10, -4, -20), scale: 5.2 },
        { color: 0xa78bfa, pos: new THREE.Vector3(-12, -3, -24), scale: 6.0 },
    ];
    nebulaDefs.forEach((n, idx) => {
        const g = new THREE.SphereGeometry(1, 32, 32);
        const mat = new THREE.ShaderMaterial({
            uniforms: { time: { value: 0 }, color: { value: new THREE.Color(n.color) }, scale: { value: n.scale } },
            vertexShader: `varying vec3 vPos; void main(){ vPos = position; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0);} `,
            fragmentShader: `uniform float time; uniform vec3 color; uniform float scale; varying vec3 vPos; void main(){ float n = length(vPos) + sin(time*0.2+vPos.x*0.8)*0.3; float a = smoothstep(1.0,0.1,n); gl_FragColor = vec4(color/255.0, a*0.35); }`,
            transparent: true,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
        });
        const mesh = new THREE.Mesh(g, mat);
        mesh.position.copy(n.pos);
        mesh.scale.setScalar(n.scale);
        scene.add(mesh);
        nebulaClouds.push(mesh);
    });

    // --- SHOOTING STARS (created dynamically) ---
    const createShooting = () => {
        const geo = new THREE.CylinderGeometry(0.01, 0.01, 1.6, 6);
        const mat = new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.95 });
        const star = new THREE.Mesh(geo, mat);
        star.rotation.z = Math.random() * Math.PI;
        star.position.set((Math.random() - 0.5) * 24, 8 + Math.random() * 6, -18 - Math.random() * 6);
        star.userData = { vel: new THREE.Vector3((Math.random() - 0.2) * -0.06, -0.18 - Math.random() * 0.06, 0.03 + Math.random() * 0.03) };
        scene.add(star);
        shootingStars.push(star);
        setTimeout(() => {
            scene.remove(star);
            geo.dispose();
            mat.dispose();
            const idx = shootingStars.indexOf(star);
            if (idx > -1) shootingStars.splice(idx, 1);
        }, 3000 + Math.random() * 2000);
    };
    const shootingInterval = setInterval(createShooting, 1800);
    createShooting();

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
            const t = time * 0.001;
            // Update planet shader time
            if (planetMaterial.uniforms) {
                planetMaterial.uniforms.time.value = t;
            }

            planet.rotation.y += 0.0016;
            glow.rotation.y += 0.0012;
            stars.rotation.y += 0.00045;

            // nebula subtle motion
            nebulaClouds.forEach((n, i) => {
                if ((n.material as any).uniforms) (n.material as any).uniforms.time.value = t;
                n.rotation.y += 0.00025 * (i % 2 === 0 ? 1 : -1);
            });

            // rings and asteroid motion
            orbitalRings.forEach((r, i) => (r.rotation.z += 0.0008 * (i + 1)));
            if (asteroidBelt) asteroidBelt.rotation.y += 0.0009;

            // shooting stars motion
            shootingStars.forEach((s) => {
                const vel = s.userData.vel as THREE.Vector3;
                s.position.add(vel);
                s.rotation.z += 0.08;
                if ((s.material as THREE.Material).opacity !== undefined) {
                    (s.material as THREE.Material as any).opacity = Math.max(0, (s.material as any).opacity - 0.008);
                }
            });

            if (smallEarth) {
                smallEarth.rotation.y += 0.01;
                smallEarth.position.y = 1.8 + Math.sin(t * 2.0) * 0.05;
            }

            camera.rotation.x += (mouse.y - camera.rotation.x) * 0.02;
            camera.rotation.y += (mouse.x - camera.rotation.y) * 0.02;

            renderer.render(scene, camera);
            lastTime = time;
        }
        animationId = requestAnimationFrame(animate);
    };
    animate();

    // cleanup shooting stars interval on unmount
    const cleanupShooting = () => clearInterval(shootingInterval);

    const handleResize = () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    };
    window.addEventListener('resize', handleResize);

    onUnmounted(() => {
        cancelAnimationFrame(animationId);
        window.removeEventListener('resize', handleResize);
        cleanupShooting();
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
                class="w-full max-w-3xl rounded-2xl bg-white/10 p-6 sm:p-8 shadow-xl backdrop-blur-xl border border-white/20">
                <h1
                    class="mb-4 flex items-center gap-2 text-left text-2xl sm:text-3xl font-extrabold text-indigo-300 drop-shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"
                        class="w-8 h-8 text-indigo-300 drop-shadow-md animate-pulse">
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
                        class="flex items-center justify-between rounded-xl bg-white/10 px-3 sm:px-4 py-2 sm:py-3 shadow hover:bg-white/20 transition">
                        <span class="truncate text-sm sm:text-base">{{ file }}</span>
                        <a :href="`/Transfer Files/${file}`" download
                            class="mt-2 sm:mt-0 rounded-md bg-yellow-400 px-3 sm:px-4 py-1 sm:py-2 text-sm font-medium text-black transition hover:bg-yellow-300 focus:outline-dashed focus:outline-2 focus:outline-yellow-400">
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

/* Enhanced decorative elements and subtle UI polish (download panel left intact) */
.object_rocket {
    position: absolute;
    top: 18%;
    left: 6%;
    width: 64px;
    animation: rocket-float 6s ease-in-out infinite alternate;
    will-change: transform;
    filter: drop-shadow(0 6px 18px rgba(251, 191, 36, 0.25));
    z-index: 6;
}

.object_moon {
    position: absolute;
    top: 6%;
    right: 12%;
    width: 110px;
    opacity: 0.22;
    animation: slow-rotate 24s linear infinite reverse, moon-pulse 6s ease-in-out infinite;
    filter: drop-shadow(0 8px 24px rgba(147, 197, 253, 0.25));
    z-index: 4;
}

.box_astronaut {
    position: absolute;
    top: 56%;
    right: 10%;
    z-index: 6;
}

.object_astronaut {
    width: 160px;
    animation: float-around 20s ease-in-out infinite alternate;
    will-change: transform;
    filter: drop-shadow(0 8px 22px rgba(167, 139, 250, 0.18));
}

@keyframes float-around {
    0% {
        transform: translate(0, 0) rotate(0deg);
    }

    25% {
        transform: translate(-28px, -36px) rotate(-6deg);
    }

    50% {
        transform: translate(36px, -14px) rotate(12deg);
    }

    75% {
        transform: translate(-22px, 28px) rotate(-10deg);
    }

    100% {
        transform: translate(28px, 18px) rotate(8deg);
    }
}

@keyframes rocket-float {
    0% {
        transform: translateY(0px) rotate(-15deg);
    }

    50% {
        transform: translateY(-28px) rotate(-10deg);
    }

    100% {
        transform: translateY(-6px) rotate(-22deg);
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

@keyframes moon-pulse {

    0%,
    100% {
        opacity: 0.18;
        transform: scale(1);
    }

    50% {
        opacity: 0.36;
        transform: scale(1.06);
    }
}

/* Shooting particles overlay to add depth (DOM layer) */
.particles-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 2;
}

.particle-dot {
    position: absolute;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0));
    opacity: 0.9;
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.4);
    animation: rise 9s linear infinite;
}

@keyframes rise {
    0% {
        transform: translateY(20vh) scale(0.9);
        opacity: 0;
    }

    10% {
        opacity: 1;
    }

    90% {
        opacity: 1;
    }

    100% {
        transform: translateY(-120vh) scale(1.2);
        opacity: 0;
    }
}

/* Small satellites (pure CSS) */
.satellite {
    position: absolute;
    width: 20px;
    height: 12px;
    border-radius: 4px;
    background: linear-gradient(90deg, #94a3b8, #e2e8f0);
    box-shadow: 0 0 10px rgba(99, 102, 241, 0.2);
    transform-origin: center;
}

.satellite.s1 {
    top: 22%;
    left: 74%;
    animation: small-orbit 22s linear infinite;
}

.satellite.s2 {
    top: 68%;
    left: 18%;
    animation: small-orbit 28s linear infinite reverse;
}

@keyframes small-orbit {
    0% {
        transform: translateX(0) rotate(0deg);
    }

    100% {
        transform: translateX(60px) rotate(360deg);
    }
}

/* keep responsive balance */
@media (max-width: 768px) {
    .object_rocket {
        width: 42px;
        top: 72%;
        left: 6%;
    }

    .object_moon {
        width: 82px;
        right: 10%;
    }

    .object_astronaut {
        width: 120px;
    }

    .satellite.s2 {
        display: none;
    }
}

@media (max-width: 480px) {
    .object_rocket {
        width: 34px;
    }

    .object_moon {
        width: 64px;
    }

    .object_astronaut {
        width: 96px;
    }
}
</style>