<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import { onMounted, onUnmounted, ref } from 'vue';
import utc from 'dayjs/plugin/utc';

dayjs.extend(utc);
dayjs.extend(duration);

const props = defineProps<{
    fileTransfer: {
        created_at: string;
    };
}>();

const countdown = ref('');
const expiry = dayjs.utc(props.fileTransfer.created_at).add(30, 'day');

const updateCountdown = () => {
    const now = dayjs();
    const remainingMs = expiry.diff(now);

    if (remainingMs <= 0) {
        countdown.value = 'Expired';
        return;
    }

    const d = dayjs.duration(remainingMs);
    countdown.value = `${d.days()}d ${d.hours()}h ${d.minutes()}m ${d.seconds()}s`;
};

let interval: number;

onMounted(() => {
    updateCountdown();
    interval = setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css?family=Dosis:300,400,500');

@-moz-keyframes rocket-movement {
    100% {
        -moz-transform: translate(1200px, -600px);
    }
}

@-webkit-keyframes rocket-movement {
    100% {
        -webkit-transform: translate(1200px, -600px);
    }
}

@keyframes rocket-movement {
    100% {
        transform: translate(1200px, -600px);
    }
}

@-moz-keyframes spin-earth {
    100% {
        -moz-transform: rotate(-360deg);
        transition: transform 20s;
    }
}

@-webkit-keyframes spin-earth {
    100% {
        -webkit-transform: rotate(-360deg);
        transition: transform 20s;
    }
}

@keyframes spin-earth {
    100% {
        -webkit-transform: rotate(-360deg);
        transform: rotate(-360deg);
        transition: transform 20s;
    }
}

@-moz-keyframes move-astronaut {
    100% {
        -moz-transform: translate(-160px, -160px);
    }
}

@-webkit-keyframes move-astronaut {
    100% {
        -webkit-transform: translate(-160px, -160px);
    }
}

@keyframes move-astronaut {
    100% {
        -webkit-transform: translate(-160px, -160px);
        transform: translate(-160px, -160px);
    }
}

@-moz-keyframes rotate-astronaut {
    100% {
        -moz-transform: rotate(-720deg);
    }
}

@-webkit-keyframes rotate-astronaut {
    100% {
        -webkit-transform: rotate(-720deg);
    }
}

@keyframes rotate-astronaut {
    100% {
        -webkit-transform: rotate(-720deg);
        transform: rotate(-720deg);
    }
}

@-moz-keyframes glow-star {
    40% {
        -moz-opacity: 0.3;
    }

    90%,
    100% {
        -moz-opacity: 1;
        -moz-transform: scale(1.2);
    }
}

@-webkit-keyframes glow-star {
    40% {
        -webkit-opacity: 0.3;
    }

    90%,
    100% {
        -webkit-opacity: 1;
        -webkit-transform: scale(1.2);
    }
}

@keyframes glow-star {
    40% {
        -webkit-opacity: 0.3;
        opacity: 0.3;
    }

    90%,
    100% {
        -webkit-opacity: 1;
        opacity: 1;
        -webkit-transform: scale(1.2);
        transform: scale(1.2);
        border-radius: 999999px;
    }
}

.spin-earth-on-hover {
    transition: ease 200s !important;
    transform: rotate(-3600deg) !important;
}

.bg-purple {
    width: 100%;
    background: url(http://salehriaz.com/404Page/img/bg_purple.png);
    background-repeat: repeat-x;
    background-size: cover;
    background-position: left top;
    height: 100vh;
    overflow: hidden;
}

.brand-logo {
    margin-left: 25px;
    margin-top: 5px;
    display: inline-block;
}

ul {
    list-style-type: none;
    margin: 0;
    padding: 0;
    /*    overflow: hidden;*/
    display: flex;
    align-items: center;
}

li {
    float: left;
    padding: 0px 15px;
}

li a {
    display: block;
    color: white;
    text-align: center;
    text-decoration: none;
    letter-spacing: 2px;
    font-size: 12px;
    -webkit-transition: all 0.3s ease-in;
    -moz-transition: all 0.3s ease-in;
    -ms-transition: all 0.3s ease-in;
    -o-transition: all 0.3s ease-in;
    transition: all 0.3s ease-in;
}

li a:hover {
    color: #ffcb39;
}

.btn-request {
    padding: 10px 25px;
    border: 1px solid #ffcb39;
    border-radius: 100px;
    font-weight: 400;
}

.btn-request:hover {
    background-color: #ffcb39;
    color: #fff;
    transform: scale(1.05);
    box-shadow: 0px 20px 20px rgba(0, 0, 0, 0.1);
}

.btn-go-home {
    position: relative;
    z-index: 200;
    margin: 15px auto;
    width: 100px;
    padding: 10px 15px;
    border: 1px solid #ffcb39;
    border-radius: 100px;
    font-weight: 400;
    display: block;
    color: white;
    text-align: center;
    text-decoration: none;
    letter-spacing: 2px;
    font-size: 11px;

    -webkit-transition: all 0.3s ease-in;
    -moz-transition: all 0.3s ease-in;
    -ms-transition: all 0.3s ease-in;
    -o-transition: all 0.3s ease-in;
    transition: all 0.3s ease-in;
}

.btn-go-home:hover {
    background-color: #ffcb39;
    color: #fff;
    transform: scale(1.05);
    box-shadow: 0px 20px 20px rgba(0, 0, 0, 0.1);
}

.central-body {
    /*    width: 100%;*/
    padding: 17% 5% 10% 5%;
    text-align: center;
}

.objects img {
    z-index: 90;
    pointer-events: none;
}

.object_rocket {
    z-index: 95;
    position: absolute;
    transform: translateX(-50px);
    top: 75%;
    pointer-events: none;
    animation: rocket-movement 200s linear infinite both running;
}

.object_earth {
    position: absolute;
    top: 20%;
    left: 15%;
    z-index: 90;
}

.object_moon {
    position: absolute;
    top: 12%;
    left: 25%;
}

.object_astronaut {
    animation: rotate-astronaut 200s infinite linear both alternate;
}

.box_astronaut {
    z-index: 110 !important;
    position: absolute;
    top: 60%;
    right: 20%;
    will-change: transform;
    animation: move-astronaut 50s infinite linear both alternate;
}

.image-404 {
    position: relative;
    z-index: 100;
    pointer-events: none;
}

.stars {
    background: url(http://salehriaz.com/404Page/img/overlay_stars.svg);
    background-repeat: repeat;
    background-size: contain;
    background-position: left top;
}

.glowing_stars .star {
    position: absolute;
    border-radius: 100%;
    background-color: #fff;
    width: 3px;
    height: 3px;
    opacity: 0.3;
    will-change: opacity;
}

.glowing_stars .star:nth-child(1) {
    top: 80%;
    left: 25%;
    animation: glow-star 2s infinite ease-in-out alternate 1s;
}

.glowing_stars .star:nth-child(2) {
    top: 20%;
    left: 40%;
    animation: glow-star 2s infinite ease-in-out alternate 3s;
}

.glowing_stars .star:nth-child(3) {
    top: 25%;
    left: 25%;
    animation: glow-star 2s infinite ease-in-out alternate 5s;
}

.glowing_stars .star:nth-child(4) {
    top: 75%;
    left: 80%;
    animation: glow-star 2s infinite ease-in-out alternate 7s;
}

.glowing_stars .star:nth-child(5) {
    top: 90%;
    left: 50%;
    animation: glow-star 2s infinite ease-in-out alternate 9s;
}

@media only screen and (max-width: 600px) {
    .navbar-links {
        display: none;
    }

    .custom-navbar {
        text-align: center;
    }

    .brand-logo img {
        width: 120px;
    }

    .box_astronaut {
        top: 70%;
    }

    .central-body {
        padding-top: 25%;
    }
}
</style>

<template>

    <Head title="Planet Nine Transfer" />
    <div class="bg-purple">
        <div class="stars">
            <div class="objects">
                <img class="object_rocket" src="http://salehriaz.com/404Page/img/rocket.svg" width="40px" />
                <div class="earth-moon">
                    <img class="object_earth" src="http://salehriaz.com/404Page/img/earth.svg" width="100px" />
                    <img class="object_moon" src="http://salehriaz.com/404Page/img/moon.svg" width="80px" />
                </div>
                <div class="box_astronaut">
                    <img class="object_astronaut" src="http://salehriaz.com/404Page/img/astronaut.svg" width="140px" />
                </div>
            </div>
            <div class="glowing_stars">
                <div class="star"></div>
                <div class="star"></div>
                <div class="star"></div>
                <div class="star"></div>
                <div class="star"></div>
            </div>

            <div class="z-50 flex min-h-screen items-center justify-center bg-[#1a1a2e] px-4 text-white">
                <div class="z-50 w-full max-w-2xl rounded-xl bg-white/5 p-8 shadow-xl backdrop-blur-md"
                    style="z-index: 999">
                    <h1 class="mb-4 text-xl font-semibold">Files shared by Planet Nine</h1>
                    <p class="mb-6 text-sm text-yellow-400">File Download is Ready. May the ZIPs Be With You.</p>
                    <!-- <p class="mb-6 text-sm text-yellow-400" v-if="countdown !== 'Expired'">⏳ The transfer link will
                        expire after: {{ countdown }}</p>
                    <p class="mb-6 text-sm font-bold text-red-600" v-else>❌ This transfer has expired.</p> -->
                    <div class="space-y-4">
                        <div v-for="(file, index) in fileTransfer.file_paths" :key="index"
                            class="flex items-center justify-between rounded-md bg-white/10 px-4 py-3 shadow backdrop-blur-sm">
                            <span class="truncate">{{ file }}</span>
                            <a :href="`/Transfer Files/${file}`" download
                                class="rounded bg-yellow-400 px-4 py-2 text-sm font-medium text-black transition hover:bg-yellow-300">
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
