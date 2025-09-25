<template>

    <Head title="Q/A Doc" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-4 max-w-6xl">
            <input v-model="search" type="text" placeholder="Search questions..."
                class="w-full mb-6 px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" />

            <div v-for="(qa, idx) in filteredQA" :key="idx" class="mb-4">
                <button @click="toggle(idx)"
                    class="w-full text-left px-4 py-3 bg-white dark:bg-gray-800 rounded shadow hover:bg-blue-50 dark:hover:bg-blue-900 transition-colors font-semibold text-lg flex justify-between items-center">
                    <span>{{ qa.question }}</span>
                    <span>
                        <svg v-if="openIdx === idx" class="w-5 h-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7">
                            </path>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </span>
                </button>
                <transition name="fade">
                    <div v-if="openIdx === idx"
                        class="px-4 py-3 bg-gray-50 dark:bg-gray-900 rounded-b text-gray-700 dark:text-gray-200 border-t border-gray-200 dark:border-gray-700">
                        <div v-html="qa.answer"></div>
                        <div v-html="qa.additionalInfo"></div>
                    </div>
                </transition>
            </div>
            <div v-if="filteredQA.length === 0" class="text-gray-500 text-center mt-8">No questions found.</div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Q/A Documentation', href: '/lazyDoc' }];

const search = ref('')
const openIdx = ref(null)

// Example Q/A data. You can expand this or load from a JSON file.
const qaList = ref([
    {
        question: 'What is Creative Planet Nine?',
        answer: 'Creative Planet Nine is a platform where the main focus is to create preview links for clients in such a way that the user can upload Banners, Videos, Gifs and Social Images/Images. We call this the Preview System. In order to make this preview system work there is a heirarchy of users, models and permissions.'
    },
    {
        question: 'What features are in the Creative?',
        answer: `
        Lets try to get you familiar with what you can do in the system. I will try to cover as much as I can on this page. Just like any developer I am lazy. So dont expect too much. <span class="font-bold">Look at the left sidebar whole reading the points.</span>
        <ul class="list-disc list-inside">
            <li class="mt-2 mb-2 ml-2 mr-2"><span class="font-bold">Color Palettes: </span> Add/Edit Colors with tabs(preview page thing) as image upload.</li>
            <li class="mt-2 mb-2 ml-2 mr-2"><span class="font-bold">Client Handling: </span> Each client has their own color theme. Which comes from Color Palettes.</li>
            <li class="mt-2 mb-2 ml-2 mr-2"><span class="font-bold">Banner and Video Sizes: </span> Banners, Videos, Gifs, Social -> we call them creatives. Each creative has their own size. To show each size dynamic on the preview page (which client will see), maintaining Banner sizes and video sizes is necessary.</li>
            <li class="mt-2 mb-2 ml-2 mr-2"><span class="font-bold">File Transfers: </span> We create transfer links and provide to our clients so that they can download their creatives and upload them on their end.</li>
            <li class="mt-2 mb-2 ml-2 mr-2"><span class="font-bold">Bills: </span>This is for Limon Bhai only. The guy who will not just give you life lessions but also take care of you like his own brother/sister. Kindly respect him. If you dont, I will make sure that your self respect gets sold to the highest bidder in LGBTQ++ community and the bidder ends up on your doorstep knocking late at night to make love to you.</li>
            <li class="mt-2 mb-2 ml-2 mr-2"><span class="font-bold">Media Library: </span>Sometimes in video banners we need to provide an external link for the banner to work. This segment will do the part. Of course you can upload other files as well.</li>
            <li class="mt-2 mb-2 ml-2 mr-2"><span class="font-bold">TinyPNG: </span>500 free image compression per month. This was built as a learning curve. Works fine af though.</li>
            <li class="mt-2 mb-2 ml-2 mr-2"><span class="font-bold">Preview System: </span>This is the original preview system that started it all. It has evolved over time but the core functionality remains the same. More info is to the other Q/A module.</li>
        </ul>
    `,
    },
    {
        question: 'What is a Client? ',
        answer: 'Well like the name suggests, client page is to manage the clients. However, there some other things as well. Each Client has their own logo, brand color, url and preview url as well. For exampple: CMN is a client where the preview url is creative.cmn.com. Another example: mor Merkle it will be creative.merkle.com. But its not necessary to have the a different preview url for each clients. Only if the client requires it then you have to create it.'
    },
    {
        question: 'How to create client specific preview url?',
        answer: 'A sub domain will be created under creative.planetnine.com. For example: creative.cmn.com. This sub domain will point to the same server as creative.planetnine.com. In the server we will check the url and load the client specific data. So if you go to creative.cmn.com it will load the client cmn data. If you go to creative.merkle.com it will load the client merkle data. If you go to creative.planetnine.com it will load the default data. The file you are looking for is <span class="font-bold">web.php</span>',
        additionalInfo: `
        In case you want to change any for the client, here are model, migration and controller of Client:
        <ul class="list-disc list-inside">
            <li class="mt-2 mb-2 ml-2 mr-2">Model: <span class="font-bold">Client.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Migration: <span class="font-bold">2025_05_02_135230_create_clients_table.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Controller: <span class="font-bold">ClientController.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Vue Files: <span class="font-bold">Pages/Clients</span></li>
        </ul>
        `
    },
    {
        question: 'What are Color Palettes?',
        answer: 'Color Palettes are the colors that are used in the preview system. The name does say Color Palette but we are developing a theme. A theme can have multiple colors. Each Color Palette has a name and colors.',
        additionalInfo: `
        In case you want to change any for the Color Palette, here are model, migration and controller of Color Palette:
        <ul class="list-disc list-inside">
            <li class="mt-2 mb-2 ml-2 mr-2">Model: <span class="font-bold">ColorPalette.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Migration: <span class="font-bold">2025_05_01_082516_create_color_palettes_table.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Controller: <span class="font-bold">ColorPaletteController.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Vue Files: <span class="font-bold">Pages/ColorPalettes</span></li>
        </ul>
        `
    },
    {
        question: 'What are Banner Sizes and Video Size?',
        answer: 'Banner Sizes are the sizes of the banners that are used in the preview system. Video Sizes are the sizes of the videos that are used in the preview system. Each Banner Size has a name, width and height. Each Video Size has a name, width and height as well. The difference between Banner Size and Video Size is that Banner Size is used for banners only. Video Size is used for videos only.',
        additionalInfo: `
        In case you want to change any for the Banner Size or Video Size, here are model, migration and controller of Banner Size and Video Size:
        <ul class="list-disc list-inside">
            <li class="mt-2 mb-2 ml-2 mr-2">Model: <span class="font-bold">BannerSize.php</span>, <span class="font-bold">VideoSize.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Migration: <span class="font-bold">2025_04_15_070726_create_banner_sizes_table.php</span>, <span class="font-bold">2025_04_17_173143_create_video_sizes_table.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Controller: <span class="font-bold">BannerSizeController.php</span>, <span class="font-bold">VideoSizeController.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Vue Files: <span class="font-bold">Pages/BannerSizes</span>, <span class="font-bold">Pages/VideoSizes</span></li>
        </ul>
        `
    },
    {
        question: 'What are File Transfers?',
        answer: 'File Transfers are the links that are used to transfer files from the server to the client. Each File Transfer has a unique link that can be used to download the files. Making sure that that server stays clean, we have to delete the file transfers after 1 Year 1 Month. But make sure that the files are stored at our <span class="font-bold">DIVANAS</span> server.',
        additionalInfo: `
        In case you want to change any for the File Transfer, here are model, migration and controller of File Transfer:
        <ul class="list-disc list-inside">
            <li class="mt-2 mb-2 ml-2 mr-2">Model: <span class="font-bold">FileTransfer.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Migration: <span class="font-bold">2025_05_01_134500_create_file_transfers_table.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Controller: <span class="font-bold">FileTransferController.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Vue Files: <span class="font-bold">Pages/FileTransfers</span></li>
        </ul>
        `
    },
    {
        question: 'What is Media Library?',
        answer: 'Media Library is a place where you can upload files that are used in the preview system. Each file has a name, url and type. The type can be image, video or other.',
        additionalInfo: `
        In case you want to change any for the Media Library, here are model, migration and controller of Media Library:
        <ul class="list-disc list-inside">
            <li class="mt-2 mb-2 ml-2 mr-2">Model: <span class="font-bold">Media.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Migration: <span class="font-bold">2025_05_03_114912_create_media_table.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Controller: <span class="font-bold">MediaController.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Vue Files: <span class="font-bold">Pages/Medias</span></li>
        </ul>
        `
    },
    {
        question: 'What is TinyPNG?',
        answer: 'TinyPNG is a place where you can compress your images. You get 500 free compressions per month. After that you have to pay for it. If the free trial is over then just head to <a href="https://tinypng.com" target="_blank" class="text-blue-500 underline">TinyPNG</a>. Or if you want to use your own API key then you can set it in the .env file. The key is TINYPNG_API_KEY and you can get it from <a href="https://tinypng.com/developers" target="_blank" class="text-blue-500 underline">here</a>.',
        additionalInfo: `
        In case you want to change any for the TinyPNG, here are model, migration and controller of TinyPNG:
        <ul class="list-disc list-inside">
            <li class="mt-2 mb-2 ml-2 mr-2">Controller: <span class="font-bold">TinyPngController.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Vue Files: <span class="font-bold">Pages/TinyPng</span></li>
        </ul>

        There is no Model or Migration for TinyPNG as we are not storing any data in the database plus its a third party service that is working with API key.
        `
    },
    {
        question: 'What is Bills?',
        answer: 'Bills is a place where Limon Bhai can manage his bills. Each bill has a sub bill. And total of sub bills is the total of the bill. This is used for mainly to keep the record of bills that is being used in the office.',
        additionalInfo: `
        In case you want to change any for the Bills, here are model, migration and controller of Bills:
        <ul class="list-disc list-inside">
            <li class="mt-2 mb-2 ml-2 mr-2">Model: <span class="font-bold">Bill.php</span>, <span class="font-bold">SubBill.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Migration: <span class="font-bold">2025_04_21_123650_create_bills_table.php</span>, <span class="font-bold">2025_04_21_123714_create_sub_bills_table.php</span></li>
            <li class="mt-2 mb-2 ml-2 mr-2">Controller: <span class="font-bold">BillController.php</span>
            <li class="mt-2 mb-2 ml-2 mr-2">Vue Files: <span class="font-bold">Pages/Bills</span>
        </ul>
        `
    },
    {
        question: 'What is the Preview Mechanism?',
        answer: 'Now, lets dive into the main feature of this app. A versatile system that allows users to create Preview links with additional info and enables the user to upload Banners(zip), Videos, Gifs and Social Images/Images. I tried to make the system user friendly and easy to use.',
        additionalInfo: `
            First lets understand the heirarchy of the system:
            <ul class="list-disc list-inside">
                <li class="mt-2 mb-2 ml-2 mr-2">Each <span class="font-bold">Preview</span> is associated with a <span class="font-bold">Client</span> and <span class="font-bold">Color Palette</span> and <span class="font-bold">User</span>.</li>
                <li class="mt-2 mb-2 ml-2 mr-2">Each <span class="font-bold">Preview</span> can have multiple <span class="font-bold">Categories</span> (Banner, Video, Gif, Social).</li>
                <li class="mt-2 mb-2 ml-2 mr-2">Each <span class="font-bold">Category</span> can have multiple <span class="font-bold">Feedbacks</span>. Feedbacks will come from the client or the manager.</li>
                <li class="mt-2 mb-2 ml-2 mr-2">Each <span class="font-bold">Feedback</span> can have multiple <span class="font-bold">Feedback Sets</span>.</li>
                <li class="mt-2 mb-2 ml-2 mr-2">Each <span class="font-bold">Feedback Set</span> can have multiple <span class="font-bold">Versions</span>.</li>
                <li class="mt-2 mb-2 ml-2 mr-2">Each <span class="font-bold">Version</span> can have multiple <span class="font-bold">Creatives</span>. (Depending on the Category type, the creative can be Banner, Video, Gif or Social Image/Image).</li>
                <li class="mt-2 mb-2 ml-2 mr-2">For better understanding, please check this: <a href="#" class="text-blue-500 underline">Preview Link</a></li>
                <li class="mt-2 mb-2 ml-2 mr-2"><span class="font-bold">Few things that will be noticed: </span>User can change the color theme by switching the color palette and since each <span class="font-bold">Feedback</span> has their own description user can also view it.</li>
            </ul>
        `
    }
])

const filteredQA = computed(() => {
    if (!search.value) return qaList.value
    const s = search.value.toLowerCase()
    return qaList.value.filter(
        qa => qa.question.toLowerCase().includes(s) || qa.answer.toLowerCase().includes(s)
    )
})

function toggle(idx) {
    openIdx.value = openIdx.value === idx ? null : idx
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>