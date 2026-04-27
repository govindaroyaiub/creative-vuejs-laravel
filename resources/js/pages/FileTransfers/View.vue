<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { animate, stagger } from 'animejs';

interface FileTransferProps {
    slug?: string;
    id?: number;
    name?: string;
    client?: string;
    user?: string;
    created_at?: string;
    file_paths: string[];
}

const props = defineProps<{ fileTransfer: FileTransferProps }>();

const ft = computed(() => props.fileTransfer);
const transferName = computed(() => ft.value.name || 'Untitled System');
const clientName = computed(() => ft.value.client || 'Recipient');
const uploaderName = computed(() => ft.value.user || 'Observer');
const createdAt = computed(() => ft.value.created_at || '');
const slug = computed(() => ft.value.slug || '');
const refId = computed(() => slug.value.slice(0, 6).toUpperCase() || '000000');

const editionDate = computed(() => {
    const d = createdAt.value ? new Date(createdAt.value.replace(' ', 'T')) : new Date();
    return isNaN(d.getTime()) ? new Date() : d;
});
const editionShort = computed(() =>
    editionDate.value.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).toUpperCase()
);
const editionTime = computed(() => editionDate.value.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }));

// Seeded PRNG (xorshift) — stable across renders for the same slug
const makeRand = (seedStr: string) => {
    let seed = 0;
    for (let i = 0; i < seedStr.length; i++) seed = (seed * 31 + seedStr.charCodeAt(i)) | 0;
    if (seed === 0) seed = 1;
    let s = seed >>> 0;
    return () => {
        s ^= s << 13;
        s ^= s >>> 17;
        s ^= s << 5;
        return ((s >>> 0) % 1000000) / 1000000;
    };
};

const fileExt = (file: string) => {
    const dot = file.lastIndexOf('.');
    return dot >= 0 ? file.slice(dot + 1).toLowerCase() : 'pkg';
};
const fileBase = (file: string) => {
    const dot = file.lastIndexOf('.');
    return dot >= 0 ? file.slice(0, dot) : file;
};
const fileUrl = (file: string) => `/Transfer Files/${file}`;

// Build celestial dossier per file
interface CelestialBody {
    file: string;
    name: string;
    designation: string;
    radius: number;        // orbit radius
    period: number;        // seconds per orbit
    direction: 1 | -1;
    startAngle: number;    // degrees
    bodyR: number;         // body visual radius
    color: string;         // body color
    glow: string;          // glow color
    spectral: string;
    classification: string;
    inclination: number;
    ext: string;
    coords: string;
}

const spectralForExt = (ext: string): { spectral: string; color: string; glow: string } => {
    const map: Record<string, { spectral: string; color: string; glow: string }> = {
        zip: { spectral: 'G-Type · Compressed Stellar Body', color: '#f5d27a', glow: 'rgba(245,210,122,0.6)' },
        rar: { spectral: 'G-Type · Compressed Stellar Body', color: '#f5d27a', glow: 'rgba(245,210,122,0.6)' },
        '7z': { spectral: 'G-Type · Compressed Stellar Body', color: '#f5d27a', glow: 'rgba(245,210,122,0.6)' },
        pdf: { spectral: 'Y-Type · Subdwarf', color: '#a78bfa', glow: 'rgba(167,139,250,0.6)' },
        psd: { spectral: 'B-Type · Hot Blue Giant', color: '#7dd3fc', glow: 'rgba(125,211,252,0.6)' },
        ai: { spectral: 'K-Type · Orange Subgiant', color: '#fb923c', glow: 'rgba(251,146,60,0.6)' },
        fig: { spectral: 'M-Type · Magenta Dwarf', color: '#f472b6', glow: 'rgba(244,114,182,0.6)' },
        png: { spectral: 'F-Type · Yellow-White', color: '#fde68a', glow: 'rgba(253,230,138,0.6)' },
        jpg: { spectral: 'F-Type · Yellow-White', color: '#fde68a', glow: 'rgba(253,230,138,0.6)' },
        jpeg: { spectral: 'F-Type · Yellow-White', color: '#fde68a', glow: 'rgba(253,230,138,0.6)' },
        gif: { spectral: 'M-Type · Pulsing Red Dwarf', color: '#fb7185', glow: 'rgba(251,113,133,0.6)' },
        svg: { spectral: 'L-Type · Vector Brown Dwarf', color: '#c4b5fd', glow: 'rgba(196,181,253,0.6)' },
        webp: { spectral: 'F-Type · Yellow-White', color: '#fde68a', glow: 'rgba(253,230,138,0.6)' },
        mp4: { spectral: 'Q-Type · Variable Pulsar', color: '#22d3ee', glow: 'rgba(34,211,238,0.6)' },
        mov: { spectral: 'Q-Type · Variable Pulsar', color: '#22d3ee', glow: 'rgba(34,211,238,0.6)' },
        webm: { spectral: 'Q-Type · Variable Pulsar', color: '#22d3ee', glow: 'rgba(34,211,238,0.6)' },
    };
    return map[ext] || { spectral: 'X-Type · Anomalous Body', color: '#94a3b8', glow: 'rgba(148,163,184,0.6)' };
};

const classifyMass = (bytes: number | null) => {
    if (bytes == null) return 'Unknown Mass';
    const mb = bytes / (1024 * 1024);
    if (mb < 1) return 'Micro Body';
    if (mb < 10) return 'Asteroid Class';
    if (mb < 100) return 'Minor Planet';
    if (mb < 1024) return 'Terrestrial';
    return 'Gas Giant';
};

const fileSizes = ref<Record<string, number | null>>({});
const allSizesLoaded = computed(() =>
    ft.value.file_paths.length > 0 && ft.value.file_paths.every((f) => fileSizes.value[f] !== undefined)
);
const totalBytes = computed(() => ft.value.file_paths.reduce((sum, f) => sum + (fileSizes.value[f] || 0), 0));

const formatBytes = (bytes: number | null | undefined) => {
    if (bytes == null) return '—';
    if (bytes === 0) return '0 B';
    const k = 1024;
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.min(Math.floor(Math.log(bytes) / Math.log(k)), units.length - 1);
    return `${(bytes / Math.pow(k, i)).toFixed(i === 0 ? 0 : 2)} ${units[i]}`;
};

const bodies = computed<CelestialBody[]>(() => {
    const list = ft.value.file_paths;
    const N = list.length || 1;

    // Multi-body-per-orbit packing — like an asteroid belt for high counts
    const maxOrbits = N <= 5 ? N : N <= 10 ? 5 : N <= 20 ? 6 : 7;
    const orbitCount = Math.min(N, maxOrbits);
    const baseR = 110;
    const maxR = 460;
    const stepR = orbitCount > 1 ? (maxR - baseR) / (orbitCount - 1) : 0;
    const bodiesPerOrbit = Math.ceil(N / orbitCount);
    const angleStep = 360 / Math.max(bodiesPerOrbit, 1);

    // Body sizing scales down for crowded systems
    const bodyScale = N > 20 ? 0.65 : N > 12 ? 0.78 : N > 6 ? 0.9 : 1;

    return list.map((file, i) => {
        const r = makeRand(slug.value + file + i);
        const ext = fileExt(file);
        const sp = spectralForExt(ext);
        const orbitIdx = i % orbitCount;
        const positionInOrbit = Math.floor(i / orbitCount);
        const radius = baseR + orbitIdx * stepR;
        const baseAngle = positionInOrbit * angleStep;
        const startAngle = (baseAngle + r() * 18 + orbitIdx * 19) % 360;
        const period = 28 + orbitIdx * 11 + r() * 6;
        const direction: 1 | -1 = orbitIdx % 2 === 0 ? 1 : -1;
        const bytes = fileSizes.value[file];
        const massScale = bytes ? Math.min(1, Math.log10(bytes + 1) / 10) : 0.5;
        const bodyR = (3.5 + massScale * 6) * bodyScale;
        const inclinationDeg = (r() - 0.5) * 6;
        const ra = (r() * 24).toFixed(2);
        const dec = ((r() - 0.5) * 90).toFixed(1);
        const sign = parseFloat(dec) >= 0 ? '+' : '−';

        return {
            file,
            name: fileBase(file),
            designation: `PN-${refId.value}-${String(i + 1).padStart(3, '0')}`,
            radius,
            period,
            direction,
            startAngle,
            bodyR,
            color: sp.color,
            glow: sp.glow,
            spectral: sp.spectral,
            classification: classifyMass(bytes ?? null),
            inclination: inclinationDeg,
            ext,
            coords: `RA ${ra}h · DEC ${sign}${Math.abs(parseFloat(dec)).toFixed(1)}°`,
        };
    });
});

const totalLength = computed(() => bodies.value.length);
const showAllLabels = computed(() => bodies.value.length <= 8);
const outerRadius = computed(() => {
    if (!bodies.value.length) return 200;
    return Math.max(...bodies.value.map((b) => b.radius)) + 60;
});

// Static starfield (deterministic)
interface Star {
    cx: number;
    cy: number;
    r: number;
    o: number;
}
const stars = computed<Star[]>(() => {
    const r = makeRand(slug.value || 'fallback');
    const arr: Star[] = [];
    const size = outerRadius.value * 2.4;
    const count = 130;
    for (let i = 0; i < count; i++) {
        arr.push({
            cx: (r() - 0.5) * size,
            cy: (r() - 0.5) * size,
            r: r() < 0.85 ? 0.6 + r() * 0.8 : 1.2 + r() * 1.2,
            o: 0.25 + r() * 0.6,
        });
    }
    return arr;
});

// Distinct orbital rings — bodies sharing a radius share an orbit ellipse
interface OrbitRing {
    radius: number;
    inclination: number;
    bodyIndices: number[];
}
const orbits = computed<OrbitRing[]>(() => {
    const map = new Map<string, OrbitRing>();
    bodies.value.forEach((b, i) => {
        const key = String(Math.round(b.radius));
        let ring = map.get(key);
        if (!ring) {
            ring = { radius: b.radius, inclination: b.inclination, bodyIndices: [] };
            map.set(key, ring);
        }
        ring.bodyIndices.push(i);
    });
    return Array.from(map.values());
});

// Active body — used to highlight orbit/track on the map and the matching list row
const activeIdx = ref(0);
const hoveredIdx = ref<number | null>(null);
const isPaused = computed(() => hoveredIdx.value !== null);

const setActive = (i: number) => {
    activeIdx.value = i;
    // Scroll the matching row into view inside the list panel only
    if (typeof document !== 'undefined') {
        const row = document.querySelector(`[data-mfst-idx="${i}"]`) as HTMLElement | null;
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            row.classList.remove('ob-fl-row-flash');
            void row.offsetWidth;
            row.classList.add('ob-fl-row-flash');
        }
    }
};
const onBodyEnter = (i: number) => { hoveredIdx.value = i; };
const onBodyLeave = () => { hoveredIdx.value = null; };

// rAF orbit loop — drive bodies and labels in lockstep
const bodyRefs = ref<(SVGCircleElement | null)[]>([]);
const labelRefs = ref<(HTMLElement | null)[]>([]);
const trackRefs = ref<(SVGGElement | null)[]>([]);
const setBodyRef = (i: number) => (el: any) => { bodyRefs.value[i] = el as SVGCircleElement | null; };
const setLabelRef = (i: number) => (el: any) => { labelRefs.value[i] = el as HTMLElement | null; };
const setTrackRef = (i: number) => (el: any) => { trackRefs.value[i] = el as SVGGElement | null; };

let rafId = 0;
let startTime = 0;
let pauseAt = 0;
let pauseAccum = 0;
const mapSize = { w: 1, h: 1 };
const mapEl = ref<HTMLDivElement | null>(null);

const tick = () => {
    if (!isPaused.value) {
        const elapsed = (performance.now() - startTime - pauseAccum) / 1000;
        const list = bodies.value;
        const w = mapSize.w / 2;
        const h = mapSize.h / 2;
        const scale = Math.min(mapSize.w, mapSize.h) / (outerRadius.value * 2.2);

        for (let i = 0; i < list.length; i++) {
            const b = list[i];
            if (!b) continue;
            const ang = ((b.startAngle + (elapsed * 360 / b.period) * b.direction) % 360) * Math.PI / 180;
            const x = Math.cos(ang) * b.radius;
            const y = Math.sin(ang) * b.radius;

            const bodyEl = bodyRefs.value[i];
            if (bodyEl) {
                bodyEl.setAttribute('cx', String(x));
                bodyEl.setAttribute('cy', String(y));
            }
            const trackEl = trackRefs.value[i];
            if (trackEl) {
                trackEl.setAttribute('x1', String(0));
                trackEl.setAttribute('y1', String(0));
                trackEl.setAttribute('x2', String(x));
                trackEl.setAttribute('y2', String(y));
            }
            const labelEl = labelRefs.value[i];
            if (labelEl) {
                const px = w + x * scale;
                const py = h + y * scale;
                labelEl.style.transform = `translate(${px}px, ${py}px)`;
            }
        }
    }
    rafId = requestAnimationFrame(tick);
};

const measureMap = () => {
    if (!mapEl.value) return;
    const rect = mapEl.value.getBoundingClientRect();
    mapSize.w = rect.width;
    mapSize.h = rect.height;
};

let resizeObs: ResizeObserver | null = null;

const fetchSizes = () => {
    ft.value.file_paths.forEach(async (file) => {
        try {
            const res = await fetch(fileUrl(file), { method: 'HEAD' });
            const len = res.headers.get('content-length');
            fileSizes.value[file] = len ? parseInt(len, 10) : null;
        } catch {
            fileSizes.value[file] = null;
        }
    });
};

const reducedMotion = ref(false);

// Ticker state
const tickerTime = ref('');
let timeIv: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    fetchSizes();

    reducedMotion.value =
        typeof window !== 'undefined' && window.matchMedia
            ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
            : false;

    const updateTime = () => {
        const d = new Date();
        tickerTime.value = `${d.getUTCHours().toString().padStart(2, '0')}:${d.getUTCMinutes().toString().padStart(2, '0')}:${d.getUTCSeconds().toString().padStart(2, '0')} UTC`;
    };
    updateTime();
    timeIv = setInterval(updateTime, 1000);

    measureMap();
    resizeObs = new ResizeObserver(measureMap);
    if (mapEl.value) resizeObs.observe(mapEl.value);

    startTime = performance.now();
    if (!reducedMotion.value) rafId = requestAnimationFrame(tick);
    else tick(); // run once for static layout

    animate('.ob-ticker', {
        opacity: [0, 1],
        translateY: [-8, 0],
        duration: 600,
        easing: 'easeOutCubic',
    });

    animate('.ob-head-line', {
        scaleX: [0, 1],
        duration: 700,
        delay: 200,
        easing: 'easeOutCubic',
    });

    animate('.ob-eyebrow', {
        opacity: [0, 1],
        translateY: [-6, 0],
        duration: 500,
        delay: 300,
        easing: 'easeOutCubic',
    });

    animate('.ob-system-name', {
        opacity: [0, 1],
        translateY: [40, 0],
        duration: 1000,
        delay: 400,
        easing: 'easeOutExpo',
    });

    animate('.ob-byline', {
        opacity: [0, 1],
        duration: 600,
        delay: 700,
        easing: 'easeOutCubic',
    });

    animate('.ob-stage', {
        opacity: [0, 1],
        scale: [0.96, 1],
        duration: 1100,
        delay: 700,
        easing: 'easeOutExpo',
    });

    animate('.ob-fl-row', {
        opacity: [0, 1],
        translateX: [12, 0],
        duration: 400,
        delay: stagger(35, { start: 1100 }),
        easing: 'easeOutCubic',
    });

    animate('.ob-foot', {
        opacity: [0, 1],
        duration: 600,
        delay: 1700,
        easing: 'easeOutCubic',
    });
});

onBeforeUnmount(() => {
    if (rafId) cancelAnimationFrame(rafId);
    if (timeIv) clearInterval(timeIv);
    if (resizeObs) resizeObs.disconnect();
});

// When pause/unpause toggles, accumulate pause time so resume continues smoothly
import { watch } from 'vue';
watch(isPaused, (paused) => {
    if (paused) {
        pauseAt = performance.now();
    } else if (pauseAt > 0) {
        pauseAccum += performance.now() - pauseAt;
        pauseAt = 0;
    }
});

// Ping interaction — pulse all bodies + an expanding wave
const isPinging = ref(false);
const ping = () => {
    if (isPinging.value) return;
    isPinging.value = true;
    animate('.ob-ping-wave', {
        r: [40, outerRadius.value + 40],
        opacity: [0.7, 0],
        strokeWidth: [2, 0.4],
        duration: 1800,
        easing: 'easeOutQuad',
    });
    animate('.ob-body', {
        scale: [1, 1.7, 1],
        duration: 900,
        delay: stagger(50),
        easing: 'easeOutQuad',
    });
    setTimeout(() => { isPinging.value = false; }, 1900);
};

// Actions
const downloadingAll = ref(false);
const downloadAll = async () => {
    if (downloadingAll.value || !ft.value.file_paths.length) return;
    downloadingAll.value = true;
    for (const file of ft.value.file_paths) {
        if (!file) continue;
        const a = document.createElement('a');
        a.href = fileUrl(file);
        a.download = file;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        await new Promise((r) => setTimeout(r, 600));
    }
    downloadingAll.value = false;
};

const linkCopied = ref(false);
const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        linkCopied.value = true;
        setTimeout(() => (linkCopied.value = false), 1800);
    } catch { /* ignore */ }
};
</script>

<template>

    <Head :title="`Observation №${refId} — ${transferName}`" />

    <div class="ob" :class="{ 'ob-paused': isPaused }">
        <!-- =================== TICKER =================== -->
        <div class="ob-ticker" role="status">
            <div class="ob-ticker-fixed">
                <span class="ob-ticker-led"></span>
                <span>OBSV-09 LIVE</span>
            </div>
            <div class="ob-ticker-track">
                <span>UPLINK ESTABLISHED</span>
                <span class="ob-ticker-sep">·</span>
                <span>STATION 09 // PALOMAR-2</span>
                <span class="ob-ticker-sep">·</span>
                <span>OBSERVATION №{{ refId }}</span>
                <span class="ob-ticker-sep">·</span>
                <span>{{ totalLength }} BODIES CHARTED</span>
                <span class="ob-ticker-sep">·</span>
                <span>{{ tickerTime }}</span>
                <span class="ob-ticker-sep">·</span>
                <span>SIGNAL: NOMINAL</span>
            </div>
        </div>

        <!-- =================== HEADER =================== -->
        <header class="ob-header">
            <p class="ob-eyebrow">
                <span class="ob-eyebrow-mark">⌖</span>
                <span>OBSERVATION №{{ refId }} — DEEP SPACE CHART</span>
                <span class="ob-eyebrow-mark">⌖</span>
            </p>

            <h1 class="ob-system-name" :title="transferName">{{ transferName }}</h1>

            <p class="ob-system-sub">
                A SYSTEM CHARTED FOR <strong>{{ clientName }}</strong>
            </p>

            <p class="ob-byline">
                <span>BY {{ uploaderName.toUpperCase() }}</span>
                <span class="ob-byline-sep">·</span>
                <span>PLANET NINE OBSERVATORY</span>
                <span class="ob-byline-sep">·</span>
                <span>{{ editionShort }} · {{ editionTime }}</span>
            </p>

            <div class="ob-head-line" aria-hidden="true"></div>
        </header>

        <!-- =================== STAGE =================== -->
        <section class="ob-stage" v-if="bodies.length">
            <!-- Map -->
            <div ref="mapEl" class="ob-map-wrap">
                <svg class="ob-map"
                    :viewBox="`-${outerRadius * 1.1} -${outerRadius * 1.1} ${outerRadius * 2.2} ${outerRadius * 2.2}`"
                    preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <radialGradient id="ob-core" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#fff7d6" stop-opacity="1" />
                            <stop offset="35%" stop-color="#ffd97a" stop-opacity="0.9" />
                            <stop offset="70%" stop-color="#fb923c" stop-opacity="0.5" />
                            <stop offset="100%" stop-color="#fb923c" stop-opacity="0" />
                        </radialGradient>
                        <radialGradient id="ob-vignette" cx="50%" cy="50%" r="50%">
                            <stop offset="78%" stop-color="rgba(0,0,0,0)" />
                            <stop offset="100%" stop-color="rgba(0,0,0,0.4)" />
                        </radialGradient>
                        <filter id="ob-blur" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur stdDeviation="2" />
                        </filter>
                    </defs>

                    <!-- Coordinate grid (RA / DEC stylized) -->
                    <g class="ob-grid" aria-hidden="true">
                        <circle v-for="r in [outerRadius * 0.25, outerRadius * 0.5, outerRadius * 0.75, outerRadius]"
                            :key="`g${r}`" cx="0" cy="0" :r="r" />
                        <line v-for="i in 12" :key="`r${i}`" x1="0" y1="0"
                            :x2="Math.cos((i - 1) * 30 * Math.PI / 180) * outerRadius * 1.05"
                            :y2="Math.sin((i - 1) * 30 * Math.PI / 180) * outerRadius * 1.05" />
                    </g>

                    <!-- Static deep-field stars -->
                    <g class="ob-stars" aria-hidden="true">
                        <circle v-for="(s, i) in stars" :key="`s${i}`" :cx="s.cx" :cy="s.cy" :r="s.r" :opacity="s.o" />
                    </g>

                    <!-- RA labels (top, right, bottom, left) -->
                    <g class="ob-ra-labels" aria-hidden="true">
                        <text :x="0" :y="-outerRadius - 14" text-anchor="middle">00h</text>
                        <text :x="outerRadius + 18" :y="4" text-anchor="start">06h</text>
                        <text :x="0" :y="outerRadius + 22" text-anchor="middle">12h</text>
                        <text :x="-outerRadius - 18" :y="4" text-anchor="end">18h</text>
                    </g>

                    <!-- Orbital rings (one per ring, deduped) -->
                    <g class="ob-orbits" aria-hidden="true">
                        <ellipse v-for="(o, i) in orbits" :key="`o${i}`" cx="0" cy="0" :rx="o.radius"
                            :ry="o.radius * Math.cos(o.inclination * Math.PI / 180)"
                            :transform="`rotate(${o.inclination * 6})`" :class="['ob-orbit', {
                                'ob-orbit-active': o.bodyIndices.includes(activeIdx),
                                'ob-orbit-hover': hoveredIdx !== null && o.bodyIndices.includes(hoveredIdx),
                            }]" />
                    </g>

                    <!-- Tracking lines from primary to body (active only) -->
                    <g class="ob-tracks">
                        <line v-for="(_, i) in bodies" :key="`t${i}`" :ref="setTrackRef(i)" x1="0" y1="0" x2="0" y2="0"
                            :class="['ob-track', { 'ob-track-active': activeIdx === i || hoveredIdx === i }]" />
                    </g>

                    <!-- Ping wave -->
                    <circle class="ob-ping-wave" cx="0" cy="0" r="40" fill="none" stroke="#7dd3fc" stroke-width="2"
                        opacity="0" />

                    <!-- Primary star (the transfer itself) -->
                    <g class="ob-primary">
                        <circle r="46" fill="url(#ob-core)" filter="url(#ob-blur)" />
                        <circle r="22" fill="#fff7d6" />
                        <circle r="14" fill="#ffe9a8" />
                    </g>

                    <!-- Bodies -->
                    <g class="ob-bodies">
                        <circle v-for="(b, i) in bodies" :key="`b${i}`" :ref="setBodyRef(i)" cx="0" cy="0" :r="b.bodyR"
                            :fill="b.color" :stroke="b.color" stroke-width="1"
                            :class="['ob-body', { 'ob-body-active': activeIdx === i, 'ob-body-hover': hoveredIdx === i }]"
                            :style="{ filter: `drop-shadow(0 0 ${(activeIdx === i || hoveredIdx === i) ? 14 : 6}px ${b.glow})` }"
                            @click="setActive(i)" @mouseenter="onBodyEnter(i)" @mouseleave="onBodyLeave" tabindex="0"
                            @keydown.enter="setActive(i)" :aria-label="`Body ${b.designation} — ${b.name}`" />
                    </g>

                    <!-- Vignette -->
                    <rect class="ob-vignette" :x="-outerRadius * 1.1" :y="-outerRadius * 1.1" :width="outerRadius * 2.2"
                        :height="outerRadius * 2.2" fill="url(#ob-vignette)" pointer-events="none" />
                </svg>

                <!-- HTML labels overlay (positioned via rAF) -->
                <div class="ob-labels" aria-hidden="false">
                    <button v-for="(b, i) in bodies" :key="`l${i}`" :ref="setLabelRef(i)" type="button" class="ob-label"
                        :class="{
                            'ob-label-full': showAllLabels,
                            'ob-label-active': activeIdx === i,
                            'ob-label-hover': hoveredIdx === i,
                        }" @click="setActive(i)" @mouseenter="onBodyEnter(i)" @mouseleave="onBodyLeave">
                        <span class="ob-label-num">{{ String(i + 1).padStart(2, '0') }}</span>
                        <span class="ob-label-name">{{ b.name }}</span>
                    </button>
                </div>

                <!-- Primary label (centered text, html overlay) -->
                <div class="ob-primary-label" aria-hidden="true">
                    <div class="ob-primary-name">{{ transferName }}</div>
                    <div class="ob-primary-meta">PRIMARY · {{ totalLength }} {{ totalLength === 1 ? 'BODY' : 'BODIES' }}
                    </div>
                </div>

                <!-- Map controls -->
                <div class="ob-controls">
                    <button type="button" class="ob-ctrl" @click="ping" :disabled="isPinging">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                            class="ob-ctrl-icon">
                            <path stroke-linecap="round" d="M3 12c2-2 4-3 9-3s7 1 9 3" />
                            <path stroke-linecap="round" d="M5 16c1.5-1 3-2 7-2s5.5 1 7 2" />
                            <circle cx="12" cy="20" r="1.5" fill="currentColor" />
                        </svg>
                        <span>{{ isPinging ? 'Pinging…' : 'Ping system' }}</span>
                    </button>
                    <div class="ob-ctrl-status">
                        <span class="ob-ctrl-led" :class="{ 'ob-ctrl-led-paused': isPaused }"></span>
                        <span>{{ isPaused ? 'Holding orbit · hover off to resume' : 'Tracking · live' }}</span>
                    </div>
                </div>
            </div>

            <!-- =================== FILE LIST (side panel) =================== -->
            <aside class="ob-fl">
                <header class="ob-fl-head">
                    <div class="ob-fl-tag">
                        <span class="ob-fl-tag-mark">⌖</span>
                        <span>FILES · {{ String(totalLength).padStart(2, '0') }}</span>
                    </div>
                    <div class="ob-fl-actions">
                        <button type="button" class="ob-fl-mini" :disabled="downloadingAll" @click="downloadAll">
                            {{ downloadingAll ? 'Releasing…' : 'Retrieve all' }}
                        </button>
                        <button type="button" class="ob-fl-mini ob-fl-mini-ghost" @click="copyLink">
                            {{ linkCopied ? 'Copied' : 'Copy link' }}
                        </button>
                    </div>
                </header>

                <div class="ob-fl-list" ref="listEl">
                    <button v-for="(b, i) in bodies" :key="`fl${i}`" type="button" :data-mfst-idx="i" class="ob-fl-row"
                        :class="{ 'ob-fl-row-active': activeIdx === i }" @click="setActive(i)"
                        @mouseenter="onBodyEnter(i)" @mouseleave="onBodyLeave">
                        <span class="ob-fl-glyph" :style="{ background: b.color, boxShadow: `0 0 10px ${b.glow}` }"
                            aria-hidden="true"></span>
                        <span class="ob-fl-info">
                            <span class="ob-fl-name" :title="b.name">{{ b.name }}</span>
                            <span class="ob-fl-meta">
                                <span class="ob-fl-desig">{{ b.designation }}</span>
                                <span class="ob-fl-sep">·</span>
                                <span class="tabular-nums">{{ formatBytes(fileSizes[b.file]) }}</span>
                            </span>
                        </span>
                        <a :href="fileUrl(b.file)" :download="b.file" class="ob-fl-btn" @click.stop>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                class="ob-fl-btn-icon">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Retrieve</span>
                        </a>
                    </button>
                </div>
            </aside>
        </section>

        <!-- Empty system -->
        <section v-else class="ob-empty">
            <div class="ob-empty-mark">⌖</div>
            <h2>No bodies in this system</h2>
            <p>The observatory has not yet logged any objects in this transfer.</p>
        </section>

        <!-- =================== COLOPHON =================== -->
        <footer class="ob-foot" v-if="bodies.length">
            <div class="ob-foot-grid">
                <div>
                    <span class="ob-foot-key">Delivered to</span>
                    <span class="ob-foot-val">{{ clientName }}</span>
                </div>
                <div>
                    <span class="ob-foot-key">Total mass</span>
                    <span class="ob-foot-val tabular-nums">
                        <template v-if="allSizesLoaded">{{ formatBytes(totalBytes) }}</template>
                        <span v-else class="ob-dim">measuring…</span>
                    </span>
                </div>
                <div>
                    <span class="ob-foot-key">Ephemeris</span>
                    <span class="ob-foot-val">{{ editionShort }}</span>
                </div>
            </div>
            <div class="ob-foot-brand">
                <span>⌖</span>
                <span>PLANET NINE OBSERVATORY · LIVE FEED · OBSERVATION №{{ refId }}</span>
                <span>⌖</span>
            </div>
        </footer>
    </div>
</template>

<style>
:root {
    color-scheme: dark;
}

body {
    margin: 0;
    padding: 0;
    background: #050614;
}
</style>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700;800&family=Space+Grotesk:wght@400;500;600;700;800&display=swap');

.ob {
    --bg: #050614;
    --bg-2: #07091e;
    --ink: #e8eaf3;
    --ink-soft: #b9bdd0;
    --muted: #6b6f87;
    --line: #1a1d33;
    --line-soft: #14162a;
    --primary-glow: #ffd97a;
    --accent: #7dd3fc;
    --accent-2: #f472b6;
    --ok: #4ade80;
    --warn: #fbbf24;

    position: relative;
    min-height: 100vh;
    background:
        radial-gradient(ellipse at 50% 30%, #0e1235 0%, var(--bg) 60%),
        var(--bg);
    color: var(--ink);
    font-family: 'Space Grotesk', system-ui, sans-serif;
    overflow-x: hidden;
}

/* ============================== TICKER ============================== */
.ob-ticker {
    position: sticky;
    top: 0;
    z-index: 30;
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    background: rgba(7, 9, 30, 0.92);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--line);
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--ink-soft);
    overflow: hidden;
    opacity: 0;
}

.ob-ticker-fixed {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.6rem 1rem;
    background: rgba(125, 211, 252, 0.08);
    border-right: 1px solid var(--line);
    color: var(--accent);
}

.ob-ticker-led {
    display: inline-block;
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: var(--ok);
    box-shadow: 0 0 8px var(--ok);
    animation: ob-led-blink 1.6s ease-in-out infinite;
}

@keyframes ob-led-blink {
    50% {
        opacity: 0.4;
    }
}

.ob-ticker-track {
    display: flex;
    align-items: center;
    justify-content: end;
    gap: 1rem;
    padding: 0.6rem 1rem;
    white-space: nowrap;
    overflow: hidden;
    animation: ob-ticker-scroll 60s linear infinite;
}

.ob-ticker-sep {
    opacity: 0.3;
}

/* ============================== HEADER ============================== */
.ob-header {
    position: relative;
    z-index: 1;
    max-width: 1280px;
    margin: 0 auto;
    padding: 3rem 2rem 1.5rem;
    text-align: center;
}

.ob-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
    margin: 0 0 1rem;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.32em;
    text-transform: uppercase;
    color: var(--accent);
    opacity: 0;
}

.ob-eyebrow-mark {
    color: var(--primary-glow);
    opacity: 0.7;
}

.ob-system-name {
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 800;
    font-size: clamp(2.4rem, 7vw, 5rem);
    line-height: 0.95;
    letter-spacing: -0.025em;
    color: var(--primary-glow);
    text-transform: uppercase;
    text-shadow: 0 0 48px rgba(255, 217, 122, 0.22);
    margin: 0 0 1rem;
    opacity: 0;
    max-width: 1080px;
    margin-left: auto;
    margin-right: auto;
    word-break: break-word;
}

.ob-system-sub {
    margin: 0 0 0.6rem;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--muted);
    letter-spacing: 0.24em;
    text-transform: uppercase;
}

.ob-system-sub strong {
    color: var(--accent);
    font-weight: 800;
}

.ob-byline {
    margin: 0;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.78rem;
    color: var(--muted);
    letter-spacing: 0.18em;
    text-transform: uppercase;
    opacity: 0;
}

.ob-byline strong {
    color: var(--ink);
    font-weight: 700;
}

.ob-byline-sep {
    margin: 0 0.6rem;
    opacity: 0.3;
}

.ob-head-line {
    width: min(60%, 360px);
    height: 1px;
    background: linear-gradient(to right, transparent, var(--accent), transparent);
    margin: 1.5rem auto 0;
    transform-origin: center;
}

/* ============================== STAGE ============================== */
.ob-stage {
    --map-size: clamp(360px, 56vmin, 600px);
    position: relative;
    z-index: 1;
    max-width: 1180px;
    margin: 0 auto;
    padding: 2rem 2rem 3rem;
    display: grid;
    grid-template-columns: var(--map-size) minmax(280px, 1fr);
    gap: 1.5rem;
    align-items: start;
    opacity: 0;
}

@media (max-width: 980px) {
    .ob-stage {
        grid-template-columns: 1fr;
        --map-size: min(80vmin, 520px);
    }

    .ob-fl {
        height: auto;
        max-height: 520px;
    }

    .ob-map-wrap {
        margin: 0 auto;
    }
}

.ob-map-wrap {
    position: relative;
    width: var(--map-size);
    height: var(--map-size);
    background:
        radial-gradient(ellipse at 50% 50%, rgba(13, 18, 50, 0.6) 0%, rgba(5, 6, 20, 0.95) 70%),
        var(--bg);
    border: 1px solid var(--line);
    overflow: hidden;
}

.ob-map {
    width: 100%;
    height: 100%;
    display: block;
}

/* Grid */
.ob-grid {
    fill: none;
    stroke: var(--line);
    stroke-width: 0.6;
    opacity: 0.45;
}

.ob-grid line {
    stroke-dasharray: 2 5;
}

.ob-stars {
    fill: #f4f6ff;
}

.ob-ra-labels text {
    fill: var(--muted);
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.18em;
}

.ob-orbit {
    fill: none;
    stroke: rgba(125, 211, 252, 0.42);
    stroke-width: 1;
    stroke-dasharray: 2 4;
    vector-effect: non-scaling-stroke;
    transition: stroke 0.22s ease, stroke-width 0.22s ease, filter 0.22s ease, stroke-dasharray 0.22s ease;
}

.ob-orbit-hover {
    stroke: rgba(125, 211, 252, 0.85);
    stroke-width: 1.4;
    stroke-dasharray: 0;
}

.ob-orbit-active {
    stroke: var(--accent);
    stroke-width: 1.8;
    stroke-dasharray: 0;
    filter: drop-shadow(0 0 6px rgba(125, 211, 252, 0.6));
}

.ob-track {
    stroke: var(--accent);
    stroke-width: 0.5;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.ob-track-active {
    opacity: 0.5;
}

.ob-primary {
    pointer-events: none;
}

.ob-body {
    cursor: pointer;
    transition: opacity 0.2s ease;
    transform-box: fill-box;
    transform-origin: center;
}

.ob-body:focus {
    outline: none;
}

.ob-vignette {
    pointer-events: none;
}

/* HTML labels overlay */
.ob-labels {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.ob-label {
    position: absolute;
    top: 0;
    left: 0;
    transform: translate(0, 0);
    pointer-events: auto;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.28rem 0.55rem;
    margin: 14px 0 0 14px;
    background: rgba(7, 9, 30, 0.7);
    border: 1px solid var(--line);
    color: var(--ink-soft);
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    cursor: pointer;
    backdrop-filter: blur(6px);
    transition: color 0.15s, border-color 0.15s, background 0.15s, padding 0.18s, max-width 0.22s;
    will-change: transform;
    max-width: 28px;
    overflow: hidden;
    border-radius: 999px;
}

.ob-label:hover,
.ob-label-hover,
.ob-label-active,
.ob-label-full {
    max-width: 220px;
    color: var(--ink);
    background: rgba(7, 9, 30, 0.95);
    border-color: var(--accent);
    padding: 0.28rem 0.7rem;
}

.ob-label-active {
    box-shadow: 0 0 0 1px var(--accent), 0 0 20px rgba(125, 211, 252, 0.25);
}

.ob-label-num {
    color: var(--muted);
    font-weight: 800;
    letter-spacing: 0.04em;
}

.ob-label-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 160px;
    opacity: 0;
    transition: opacity 0.18s ease;
}

.ob-label:hover .ob-label-name,
.ob-label-hover .ob-label-name,
.ob-label-active .ob-label-name,
.ob-label-full .ob-label-name {
    opacity: 1;
}

/* Primary label (centered) */
.ob-primary-label {
    opacity: 0.35;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, calc(-50% + 60px));
    text-align: center;
    pointer-events: none;
}

.ob-primary-name {
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 800;
    font-size: clamp(0.85rem, 1.4vw, 1.05rem);
    color: var(--ink);
    letter-spacing: 0.04em;
    text-transform: uppercase;
    max-width: 220px;
    margin: 0 auto 0.2rem;
    text-shadow: 0 0 24px rgba(255, 217, 122, 0.4);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ob-primary-meta {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.28em;
    color: var(--muted);
    text-transform: uppercase;
}

/* Map controls */
.ob-controls {
    position: absolute;
    bottom: 0.85rem;
    left: 0.85rem;
    right: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    pointer-events: none;
}

.ob-ctrl,
.ob-ctrl-status {
    pointer-events: auto;
}

.ob-ctrl {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.8rem;
    background: rgba(7, 9, 30, 0.8);
    border: 1px solid var(--line);
    color: var(--ink-soft);
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    cursor: pointer;
    border-radius: 999px;
    backdrop-filter: blur(6px);
    transition: color 0.15s, border-color 0.15s, background 0.15s, transform 0.15s;
}

.ob-ctrl:hover:not(:disabled) {
    color: var(--accent);
    border-color: var(--accent);
    transform: translateY(-1px);
}

.ob-ctrl:disabled {
    opacity: 0.6;
    cursor: progress;
}

.ob-ctrl-icon {
    width: 0.85rem;
    height: 0.85rem;
}

.ob-ctrl-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 0.8rem;
    background: rgba(7, 9, 30, 0.6);
    border: 1px solid var(--line);
    border-radius: 999px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--muted);
}

.ob-ctrl-led {
    width: 0.4rem;
    height: 0.4rem;
    border-radius: 50%;
    background: var(--ok);
    box-shadow: 0 0 8px var(--ok);
    animation: ob-led-blink 1.6s infinite;
}

.ob-ctrl-led-paused {
    background: var(--warn);
    box-shadow: 0 0 8px var(--warn);
    animation: none;
}

.ob-dim {
    color: var(--muted);
}

/* ============================== EMPTY ============================== */
.ob-empty {
    text-align: center;
    padding: 4rem 1.5rem;
    color: var(--muted);
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
    margin: 2rem auto;
    max-width: 600px;
}

.ob-empty-mark {
    font-size: 3rem;
    opacity: 0.6;
    margin-bottom: 1rem;
}

.ob-empty h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    margin: 0 0 0.5rem;
}

/* ============================== FILE LIST PANEL ============================== */
.ob-fl {
    display: flex;
    flex-direction: column;
    height: var(--map-size);
    background: linear-gradient(180deg, rgba(13, 18, 50, 0.4) 0%, rgba(7, 9, 30, 0.6) 100%);
    border: 1px solid var(--line);
    overflow: hidden;
}

.ob-fl-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--line);
    background: rgba(7, 9, 30, 0.55);
    flex: 0 0 auto;
}

.ob-fl-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.24em;
    text-transform: uppercase;
    color: var(--accent);
}

.ob-fl-tag-mark {
    opacity: 0.6;
}

.ob-fl-actions {
    display: flex;
    gap: 0.4rem;
}

.ob-fl-mini {
    padding: 0.4rem 0.7rem;
    background: var(--ink);
    color: var(--bg);
    border: 1px solid var(--ink);
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.6rem;
    font-weight: 800;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    cursor: pointer;
    border-radius: 999px;
    transition: background 0.18s, color 0.18s, transform 0.18s, border-color 0.18s;
}

.ob-fl-mini:hover:not(:disabled) {
    background: var(--accent);
    border-color: var(--accent);
    color: #051022;
    transform: translateY(-1px);
}

.ob-fl-mini:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.ob-fl-mini-ghost {
    background: transparent;
    color: var(--ink-soft);
    border-color: var(--line);
}

.ob-fl-mini-ghost:hover {
    background: rgba(125, 211, 252, 0.08);
    color: var(--accent);
    border-color: var(--accent);
}

.ob-fl-list {
    flex: 1 1 auto;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: var(--line) transparent;
}

.ob-fl-list::-webkit-scrollbar {
    width: 8px;
}

.ob-fl-list::-webkit-scrollbar-track {
    background: transparent;
}

.ob-fl-list::-webkit-scrollbar-thumb {
    background: var(--line);
    border-radius: 4px;
}

.ob-fl-list::-webkit-scrollbar-thumb:hover {
    background: var(--accent);
}

.ob-fl-row {
    display: grid;
    grid-template-columns: 16px minmax(0, 1fr) auto;
    gap: 0.7rem;
    align-items: center;
    width: 100%;
    padding: 0.7rem 0.9rem;
    background: transparent;
    border: 0;
    border-bottom: 1px solid var(--line-soft);
    color: var(--ink-soft);
    text-align: left;
    cursor: pointer;
    opacity: 0;
    transition: background 0.16s ease;
    position: relative;
}

.ob-fl-row:last-child {
    border-bottom: 0;
}

.ob-fl-row:hover {
    background: rgba(125, 211, 252, 0.05);
}

.ob-fl-row-active {
    background: rgba(125, 211, 252, 0.08);
    box-shadow: inset 2px 0 0 var(--accent);
}

.ob-fl-row-flash {
    animation: ob-row-flash 1.1s ease-out 1;
}

@keyframes ob-row-flash {
    0% {
        background: rgba(125, 211, 252, 0.32);
        box-shadow: inset 2px 0 0 var(--accent), 0 0 0 1px rgba(125, 211, 252, 0.6);
    }

    100% {
        background: rgba(125, 211, 252, 0.08);
        box-shadow: inset 2px 0 0 var(--accent);
    }
}

.ob-fl-glyph {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
    display: inline-block;
}

.ob-fl-info {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.ob-fl-name {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--ink);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    letter-spacing: 0.005em;
}

.ob-fl-meta {
    display: flex;
    align-items: baseline;
    gap: 0.35rem;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.62rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ob-fl-desig {
    color: var(--accent);
}

.ob-fl-sep {
    opacity: 0.4;
}

.ob-fl-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.8rem;
    background: transparent;
    border: 1px solid var(--accent);
    color: var(--accent);
    text-decoration: none;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    border-radius: 999px;
    transition: background 0.18s, color 0.18s, transform 0.18s;
    flex: 0 0 auto;
}

.ob-fl-row:hover .ob-fl-btn {
    background: var(--accent);
    color: #051022;
}

.ob-fl-btn:hover {
    background: var(--accent) !important;
    color: #051022 !important;
    transform: translateY(-1px);
}

.ob-fl-btn-icon {
    width: 0.78rem;
    height: 0.78rem;
}

@media (max-width: 1080px) {
    .ob-fl-btn span {
        display: none;
    }

    .ob-fl-btn {
        padding: 0.45rem 0.55rem;
    }
}

/* ============================== FOOTER ============================== */
.ob-foot {
    max-width: 1280px;
    margin: 0 auto;
    padding: 1.5rem 2rem 3rem;
    border-top: 1px solid var(--line);
    opacity: 0;
}

.ob-foot-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem 3rem;
    margin-bottom: 1.5rem;
}

.ob-foot-grid>div {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.25rem;
}

.ob-foot-key {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    color: var(--muted);
}

.ob-foot-val {
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: var(--ink);
}

.ob-foot-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--line-soft);
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.66rem;
    font-weight: 800;
    letter-spacing: 0.32em;
    text-transform: uppercase;
    color: var(--accent);
}

@media (max-width: 720px) {
    .ob-foot-grid {
        gap: 1rem 1.5rem;
    }

    .ob-foot-brand {
        flex-wrap: wrap;
        gap: 0.6rem;
        text-align: center;
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

    .ob-eyebrow,
    .ob-system-name,
    .ob-byline,
    .ob-stage,
    .ob-mfst-row,
    .ob-foot,
    .ob-ticker {
        opacity: 1 !important;
    }

    .ob-ticker-track {
        animation: none !important;
    }
}
</style>
