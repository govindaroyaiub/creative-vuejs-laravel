<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Creative - {{ $preview['name'] }}</title>
    <link rel="preload" as="image" href="/preview_images/sidebar-image.png">
    <link rel="preload" as="image" href="/preview_images/top-bg.png">
    <link rel="preload" as="image" href="/preview_images/subVersionDefault.png">
    <link rel="preload" as="image" href="/preview_images/subVersionActive.png">
    <link rel="shortcut icon" href="https://www.planetnine.com/logo/new_favicon.png">
    @vite('resources/css/app.css')
    <script src="https://code.jquery.com/jquery-3.6.1.slim.min.js"
        integrity="sha256-w8CvhFs7iHNVUtnSP0YKEg00p9Ih13rlL9zGqvLdePA=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.9.0/axios.min.js" integrity="sha512-FPlUpimug7gt7Hn7swE8N2pHw/+oQMq/+R/hH/2hZ43VOQ+Kjh25rQzuLyPz7aUWKlRpI7wXbY6+U3oFPGjPOA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/js/all.min.js" integrity="sha512-rpLlll167T5LJHwp0waJCh3ZRf7pO6IT1+LZOhAyP6phAirwchClbTZV3iqL3BMrVxIYRbzGTpli4rfxsCK6Vw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://s0.2mdn.net/ads/studio/cached_libs/gsap_3.5.1_min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: {{ $primary }};
            --secondary-color: {{ $secondary }};
            --tertiary-color: {{ $tertiary }};
            --quaternary-color: {{ $quaternary }};
        }
    </style>
    <link href="{{ asset('css/preview.css') }}" rel="stylesheet">
    <script>
        const pageId = '{{ $preview->id }}';

        // Assign a unique name to guest using localStorage
        let guestName = localStorage.getItem('guest_name');
        if (!guestName) {
            guestName = 'Guest-' + Math.floor(Math.random() * 10000);
            localStorage.setItem('guest_name', guestName);
        }

        // Send ping every 8 seconds
        setInterval(() => {
            axios.post('/track-viewer', {
                page_id: pageId,
                guest_name: guestName
            });
        }, 8000); // every 8 seconds

        // Fetch and render current viewers
        function fetchViewers() {
            axios.get('/get-viewers/' + pageId)
                .then(response => {
                    const viewers = response.data;
                    const html = viewers.map(name => {
                        const initial = name.trim().charAt(0).toUpperCase();
                        return `
                            <span class="bg-blue-100 text-blue-900 font-semibold rounded-full px-3 py-1 text-sm shadow-sm" title="${name}">
                                ${initial}
                            </span>
                        `;
                    }).join('');
                    document.getElementById('viewerList').innerHTML = html;
                });
        }

        // Call every 10 seconds
        setInterval(fetchViewers, 10000);
        fetchViewers(); // initial call
    </script>
</head>

<body>
    @if($authUserClientName == "Planet Nine")
    <div class="absolute top-4 right-4 flex items-center space-x-3 z-50">
        <div id="viewerList" class="flex space-x-2"></div>

        @if(auth()->check() && $preview->requires_login)
            <form method="POST" action="{{ route('preview.logout') }}" id="customPreviewLogoutForm">
                @csrf
                <input type="hidden" name="preview_id" value="{{ $preview->id }}">
                <button type="submit"
                    class="bg-red-500 hover:bg-red-600 text-white text-sm font-medium px-3 py-1 rounded shadow transition cursor-pointer">
                    Logout
                </button>
            </form>
        @endif
    </div>
    @endif

    <div id="loaderArea">
        <span class="loader"></span>
    </div>

    <main class="main">
        <div class="viewMessage">
            For the best experience, view this showcase on a laptop or desktop.
        </div>
        <section id="top" class="mb-4">
            <div class="px-4 py-4 flex justify-center content text-center relative">
                <div id="topDetails" class="mt-4">
                    @if($preview->show_planetnine_logo)
                        <img src="{{ asset('logos/' . $client['logo']) }}" id="planetnineLogo" class="py-3" alt="planetnineLogo">
                    @endif
                    <h1 style="font-size: 1rem;"><span class="font-semibold">Name: </span> <span class="capitalize">{{ $preview['name'] }}</span></h1>
                    <h1 class="mt-1" style="font-size: 1rem;"><span class="font-semibold">Client: </span> <span class="capitalize">{{ $client['name'] }}</span></h1>
                    <h1 style="font-size: 1rem;">
                        <span class="font-semibold">Date: </span> <span>{{ \Carbon\Carbon::parse($preview['created_at'])->format('F j, Y') }}</span>
                    </h1>
                </div>
            </div>
        </section>

        <section id="middle" class="mb-4">
            <div id="showcase-section" class="mx-auto custom-container mt-2 px-8">
                <div class="flex row justify-around items-end" style="min-height: 50px;">
                    <div class="py-2 flex items-end justify-center sidebar-top-desktop">
                        @if($preview['show_sidebar_logo'] == 1)
                            <img src="{{ asset('logos/' . $client['logo']) }}" 
                                alt="clientLogo" style="max-width: 200px; margin: 0 auto;">
                        @endif
                    </div>
                    <div style="flex: 1;">
                        <div class="subVersions relative flex justify-center flex-row"></div>
                    </div>
                    <div style="width: 270px; min-height: 60px;" class="sidebar-top-extra"></div>
                </div>
                <div id="showcase">
                    <div id="bannershowCustom">
                        <nav role="navigation" class="mobileShowcase">
                            <div id="menuToggle">
                                <input type="checkbox" id="menuClick" />
                                <span></span>
                                <span></span>
                                <span></span>
                                
                                <ul id="menu"></ul>
                            </div>
                        </nav>
                        <div class="navbar tabDesktopShowcase" id="navbar">
                            @if($preview['show_sidebar_logo'] == 1)
                            <div class="w-full client-logo-div sidebar-top-tab-mobile">
                                <div id="clientLogoSection" class="mb-2 mt-2 px-2 py-2 mx-auto">
                                    <img src="{{ asset('logos/' . $client['logo']) }}"
                                    alt="clientLogo" style="width: 150px;">
                                </div>
                            </div>
                            @endif
                            <div class="sidebar-image-div w-full py-2">
                                <div class="sidebar-image mx-auto">
                                    <span>Creative Showcase</span>
                                </div>
                            </div>

                            <div id="creative-list2"></div>
                        </div>

                        <div class="right-column">
                            <div id="versionArea">
                                <div id="versionCLick" onclick="showVersionDescription()">
                                    <i class="fa-regular fa-message" style="transform: rotate(90deg) scaleX(-1);"></i>
                                </div>

                                @php
                                    $colorsData = $all_colors->map(fn($color) => ['id' => $color->id, 'hex' => $color->primary, 'border' => $color->tertiary]);
                                @endphp

                                <div id="colorPaletteClick" onclick="showColorPaletteOptions()">
                                    <i class="fa-solid fa-palette"></i>
                                </div>

                                <div id="colorPaletteSelection" data-colors='@json($colorsData)'>
                                </div>

                                <div id="versionDescription">
                                    <div id="versionDescriptionUpperpart">
                                        <div class="cursor-pointer" style="float: right;" onclick="hideVersionDescription()">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div id="versionDescriptionLowerPart">
                                        <label id="versionMessage"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="versionControl">
                                <div id="versionSettings"></div>
                                <div id="subVersionSettings" style="position: absolute; right: 0;"></div>
                            </div>
                            <div id="bannerShowcase"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @if($preview['show_footer'])
        <footer class="footer py-8">
            <div class="container mx-auto px-4 text-center text-base text-gray-600">
                &copy; All Rights Reserved. 
                <a href="https://www.planetnine.com" class="underline hover:text-black" target="_blank">
                    Planet Nine
                </a> - {{ now()->year }}
            </div>
        </footer>
        @endif
</body>

<script>
function showColorPaletteOptions() {
    const preview_id = '{{ $preview_id }}';
    const paletteDiv = document.getElementById('colorPaletteSelection');

    if (paletteDiv.innerHTML.trim() === '') {
        // Parse JSON array of objects with {id, hex}
        const colors = JSON.parse(paletteDiv.dataset.colors);
        paletteDiv.classList.add('color-grid');

        colors.forEach(({ id, hex, border }) => {
            const colorBox = document.createElement('div');
            colorBox.className = 'color-box';
            colorBox.style.backgroundColor = hex;
            colorBox.style.borderColor = border;

            colorBox.title = hex; // optional: show hex on hover

            colorBox.addEventListener('click', () => {
                document.getElementById('loaderArea').style.display = 'flex';
                axios.get('/preview/'+ preview_id +'/change/theme/' + id)
                .then(response => {
                    if (response.data.success) {
                        location.reload();
                    } else {
                        alert("Something went wrong changing theme");
                    }
                })
                .catch(error => {
                    console.error('Error sending color:', error);
                });
            });

            paletteDiv.appendChild(colorBox);
        });
    }

    paletteDiv.classList.add('visible');
    document.addEventListener('click', handleOutsideClick);
}

function handleOutsideClick(event) {
    const paletteDiv = document.getElementById('colorPaletteSelection');
    const paletteToggle = document.getElementById('colorPaletteClick');

    if (!paletteDiv.contains(event.target) && !paletteToggle.contains(event.target)) {
        paletteDiv.classList.remove('visible');
        document.removeEventListener('click', handleOutsideClick);
    }
}
</script>

<script>
    const preview_id = '{{ $preview_id }}';
    const authUserClientName = '{{ $authUserClientName }}';
    var primary = '{{ $primary }}';
    var secondary = '{{ $secondary }}';
    var tertiary = '{{ $tertiary }}';
    var quaternary = '{{ $quaternary }}';
    var viewversion;

    $(document).ready(function() {
        getAllVersions();
    });

    function showVersionDescription() {
        viewversion = true;

        var moveversion = gsap.timeline();

        moveversion
            .to('#versionDescription', {
                duration: 1,
                x: 0,
                ease: 'power2.out'
            });

        if (viewversion == true) {
            var except = document.getElementById('versionDescription');

            document.addEventListener('click', closeThisversion, true);

            function closeThisversion(e) {
                if (!except.contains(e.target)) { //if the clicked element is the version div then it wont disappear
                    var listID = document.getElementById('versionDescription');
                    let viewversionTimeline = gsap.timeline();
                    viewversionTimeline
                        .to('#versionDescription', {
                            duration: 0.5,
                            x: 310,
                            ease: 'power2.in'
                        });
                }
            }
            viewversion = false;
        }
    }

    function hideVersionDescription() {
        var moveversion = gsap.timeline();

        moveversion
            .to('#versionDescription', {
                duration: 0.5,
                x: 310,
                ease: 'power2.in'
            })
    }

    function getAllVersions() {
        axios.get('/preview/getallversions/' + preview_id)
            .then(function(response) {
                var active;
                var versionActive;
                var spanActive;
                var row = '';
                var row2 = '';

                row = row + '@if($preview['show_sidebar_logo'] == 1)';
                    row = row + '<div class="w-full" style="background-color: white; border-radius: 40px;">';
                        row = row + '<div class="mb-2 mt-2 px-2 py-2 mx-auto">';
                            row = row + '<img src="{{ asset('logos/' . $client['logo']) }}" alt="clientLogo" style="width: 250px;">';
                        row = row + '</div>';
                    row = row + '</div>';
                row = row + '@endif';

                $.each(response.data.versions, function(key, value) {
                    if (value.is_active == 1) {
                        active = 'menuToggleActive';
                        versionActive = 'version-active';
                        spanActive = 'span-active';
                    } else {
                        active = '';
                        versionActive = '';
                        spanActive = '';
                    }

                    const date = new Date(value.created_at);
                    const formatted = date.toLocaleDateString('en-GB'); // DD/MM/YYYY
                    const formatted2 = `${date.getDate().toString().padStart(2, '0')}-${(date.getMonth()+1).toString().padStart(2, '0')}-${date.getFullYear()}`;

                    row2 = row2 + '<div class="version-row ' + versionActive + '" onclick="return updateActiveVersion(' + value.id + ')" id="version' + value.id + '">';
                    row2 = row2 + '<span class="' + spanActive + '" style="font-size: 0.85rem;">' + value.name + '</span>';
                    row2 = row2 + '<hr>';
                    row2 += '<span class="version-row-date" style="font-size: 0.7rem;">' + formatted2 + '</span>';
                    row2 = row2 + '</div>';

                    row = row + '<a href="javascript:void(0)" class="nav-link versions" onclick="return updateActiveVersion(' + value.id + ')" id="version' + value.id + '">';
                    row = row + '<li class="' + active + '">' + value.name + '</li>';
                    row = row + '</a>';
                });

                
                if(authUserClientName == "Planet Nine"){
                    row2 += `
                        <div class="version-row version-add-btn" onclick="return addNewVersion(${preview_id})" style="cursor: pointer; margin-top: 8px;">
                            <span class="text-2xl text-green-500 hover:text-green-700 font-bold">+</span>
                        </div>
                    `;
                }

                $('#creative-list2').html(row2);
                $('#creative-list').html(row);
                $('#menu').html(row);

                checkVersionType(response.data.activeVersion_id);
            })
            .catch(function(error) {
                console.log(error);
            })
            .finally(function() {
                // document.getElementById('menuClick').click();
            })
    }

    function updateActiveVersion(version_id) {
        document.getElementById('menuClick').click();
        axios.get('/preview/updateActiveVersion/' + version_id)
            .then(function(response) {
                var active;
                var versionActive;
                var spanActive;
                var row = '';
                var row2 = '';

                $.each(response.data.versions, function(key, value) {
                    if (value.is_active == 1) {
                        active = 'menuToggleActive';
                        versionActive = 'version-active';
                        spanActive = 'span-active';
                    } else {
                        active = '';
                        versionActive = '';
                        spanActive = '';
                    }

                    const date = new Date(value.created_at);
                    const formatted = date.toLocaleDateString('en-GB'); // DD/MM/YYYY
                    const formatted2 = `${date.getDate().toString().padStart(2, '0')}-${(date.getMonth()+1).toString().padStart(2, '0')}-${date.getFullYear()}`;

                    row2 = row2 + '<div class="version-row ' + versionActive + '" onclick="return updateActiveVersion(' + value.id + ')" id="version' + value.id + '">';
                    row2 = row2 + '<span class="' + spanActive + '" style="font-size: 0.85rem;">' + value.name + '</span>';
                    row2 = row2 + '<hr>';
                    row2 += '<span class="version-row-date" style="font-size: 0.7rem;">' + formatted2 + '</span>';
                    row2 = row2 + '</div>';

                    row = row + '<a href="javascript:void(0)" class="nav-link versions" onclick="return updateActiveVersion(' + value.id + ')" id="version' + value.id + '">';
                    row = row + '<li class="' + active + '">' + value.name + '</li>';
                    row = row + '</a>';
                });

                if(authUserClientName == 'Planet Nine'){
                    row2 += `
                        <div class="version-row version-add-btn" onclick="return addNewVersion(${preview_id})" style="cursor: pointer; margin-top: 8px;">
                            <span class="text-2xl text-green-500 hover:text-green-700 font-bold">+</span>
                        </div>
                    `;
                }

                $('#creative-list2').html(row2);
                $('#creative-list').html(row);
                $('#menu').html(row);

                checkVersionType(response.data.activeVersion_id);
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function addNewVersion(preview_id){
        const url = '/previews/version/add/' + preview_id;
        window.location.href = url;
    }

    function checkVersionType(activeVersion_id) {
        axios.get('/preview/getVersionType/' + activeVersion_id)
            .then(function(response) {
                setVersionsDescription(response.data.version_description);

                if (response.data.type == "banner") {
                    setBannerVersionSubVersions(response.data.subVersions, response.data.version_id);
                    setBannerActiveSubVersionSettings(response.data.activeSubVersion_id);
                    setBannerActiveVersionSettings(activeVersion_id);
                    setBannerDisplayOfActiveSubVersion(response.data.activeSubVersion_id);
                }
                if(response.data.type == "social") {
                    setSocialVersionSubVersions(response.data.subVersions, response.data.version_id);
                    setSocialActiveSubVersionSettings(response.data.activeSubVersion_id);
                    setSocialActiveVersionSettings(activeVersion_id);
                    setSocialDisplayOfActiveSubVersion(response.data.activeSubVersion_id);
                }
                if(response.data.type == "video"){
                    setVideoVersionSubVersions(response.data.subVersions, response.data.version_id);
                    setVideoActiveSubVersionSettings(response.data.activeSubVersion_id);
                    setVideoActiveVersionSettings(activeVersion_id);
                    setVideoDisplayOfActiveSubVersion(response.data.activeSubVersion_id);
                }
                if(response.data.type == "gif"){
                    setGifVersionSubVersions(response.data.subVersions, response.data.version_id);
                    setGifActiveSubVersionSettings(response.data.activeSubVersion_id);
                    setGifActiveVersionSettings(activeVersion_id);
                    setGifDisplayOfActiveSubVersion(response.data.activeSubVersion_id);
                }
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function setSocialVersionSubVersions(subVersions, version_id) {
        var subVersionCount = subVersions.length;
        var isActive;

        if (subVersionCount > 0) {
            var row = '';
            $.each(subVersions, function(key, value) {
                if (value.is_active == 1) {
                    isActive = ' subVersionTabActive';
                } else {
                    isActive = '';
                }
               row += `
               <div id="subVersionTab${value.id}" class="subVersionTab${isActive}" onclick="updateSocialActiveSubVersion(${value.id})">
                    <div class="trapezoid-container">
                        <div class="tab-text text-white text-base">${value.name}</div>
                    </div>
                </div>
                `;
            });
        } else {
            var row = '';
        }
        if(authUserClientName == 'Planet Nine'){
            row += `
                <div class="subVersionTab subVersionAddTab" onclick="addSocialNewSubVersion(${version_id})">
                    <div class="trapezoid-container">
                        <div class="tab-text text-white text-2xl font-bold">+</div>
                    </div>
                </div>
            `;
        }
        $('.subVersions').html(row);
    }

    function setVideoVersionSubVersions(subVersions, version_id) {
        var subVersionCount = subVersions.length;
        var isActive;

        if (subVersionCount > 0) {
            var row = '';
            $.each(subVersions, function(key, value) {
                if (value.is_active == 1) {
                    isActive = ' subVersionTabActive';
                } else {
                    isActive = '';
                }
               row += `
                <div id="subVersionTab${value.id}" class="subVersionTab${isActive}" onclick="updateVideoActiveSubVersion(${value.id})">
                    <div class="trapezoid-container">
                        <div class="tab-text text-white text-base">${value.name}</div>
                    </div>
                </div>
                `;
            });
        } else {
            var row = '';
        }
        if(authUserClientName == 'Planet Nine'){
            row += `
                <div class="subVersionTab subVersionAddTab" onclick="addVideoNewSubVersion(${version_id})">
                    <div class="trapezoid-container">
                        <div class="tab-text text-white text-2xl font-bold">+</div>
                    </div>
                </div>
            `;
        }
        $('.subVersions').html(row);
    }

    function addVideoNewSubVersion(version_id){
        const url = '/previews/version/'+ version_id +'/video/add/subVersion';
        window.location.href = url;
    }

    function addSocialNewSubVersion(version_id){
        const url = '/previews/version/'+ version_id +'/social/add/subVersion';
        window.location.href = url;
    }

    function updateSocialActiveSubVersion(subVersion_id){
        axios.get('/preview/setSocialActiveSubVersion/' + subVersion_id)
            .then(function(response) {
                setSocialVersionSubVersions(response.data.subVersions, response.data.version_id);
                setSocialActiveSubVersionSettings(response.data.activeSubVersion_id);
                setSocialDisplayOfActiveSubVersion(response.data.activeSubVersion_id);
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function setBannerVersionSubVersions(subVersions, version_id) {
        var subVersionCount = subVersions.length;
        var isActive;

        if (subVersionCount > 0) {
            var row = '';
            $.each(subVersions, function(key, value) {
                if (value.is_active == 1) {
                    isActive = ' subVersionTabActive';
                } else {
                    isActive = '';
                }
               row += `
                <div id="subVersionTab${value.id}" class="subVersionTab${isActive}" onclick="updateBannerActiveSubVersion(${value.id})">
                    <div class="trapezoid-container">
                        <div class="tab-text text-white text-base">${value.name}</div>
                    </div>
                </div>
                `;
            });
        } else {
            var row = '';
        }
        if(authUserClientName == 'Planet Nine'){
            row += `
                <div class="subVersionTab subVersionAddTab" onclick="addBannerNewSubVersion(${version_id})">
                    <div class="trapezoid-container">
                        <div class="tab-text text-white text-2xl font-bold">+</div>
                    </div>
                </div>
            `;
        }
        $('.subVersions').html(row);
    }

    function setGifVersionSubVersions(subVersions, version_id) {
        var subVersionCount = subVersions.length;
        var isActive;

        if (subVersionCount > 0) {
            var row = '';
            $.each(subVersions, function(key, value) {
                if (value.is_active == 1) {
                    isActive = ' subVersionTabActive';
                } else {
                    isActive = '';
                }
               row += `
                <div id="subVersionTab${value.id}" class="subVersionTab${isActive}" onclick="updateGifActiveSubVersion(${value.id})">
                    <div class="trapezoid-container">
                        <div class="tab-text text-white text-base">${value.name}</div>
                    </div>
                </div>
                `;
            });
        } else {
            var row = '';
        }
        if(authUserClientName == 'Planet Nine'){
            row += `
                <div class="subVersionTab subVersionAddTab" onclick="addGifNewSubVersion(${version_id})">
                    <div class="trapezoid-container">
                        <div class="tab-text text-white text-2xl font-bold">+</div>
                    </div>
                </div>
            `;
        }
        $('.subVersions').html(row);
    }

    function updateGifActiveSubVersion(subVersion_id){
        axios.get('/preview/setGifActiveSubVersion/' + subVersion_id)
            .then(function(response) {
                setGifVersionSubVersions(response.data.subVersions, response.data.version_id);
                setGifActiveSubVersionSettings(response.data.activeSubVersion_id);
                setGifDisplayOfActiveSubVersion(response.data.activeSubVersion_id);
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function addGifNewSubVersion(version_id){
        const url = '/previews/version/'+ version_id +'/gif/add/subVersion';
        window.location.href = url;
    }

    function addBannerNewSubVersion(version_id){
        const url = '/previews/version/'+ version_id +'/banner/add/subVersion';
        window.location.href = url;
    }

    function updateBannerActiveSubVersion(subVersion_id) {
        axios.get('/preview/setBannerActiveSubVersion/' + subVersion_id)
            .then(function(response) {
                setBannerVersionSubVersions(response.data.subVersions, response.data.version_id);
                setBannerActiveSubVersionSettings(response.data.activeSubVersion_id);
                setBannerDisplayOfActiveSubVersion(response.data.activeSubVersion_id);
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function setVersionsDescription(version_description) {
        $('#versionMessage').html(version_description);
    }

    function setSocialActiveSubVersionSettings(activeSubVersion_id) {
        axios.get('/preview/checkSubVersionCount/' + activeSubVersion_id)
            .then(function(response) {
                var display = 'display: block;';
                rows = '';
                rows = rows + "@if($authUserClientName == 'Planet Nine')";
                rows = rows + '<div>';
                rows = rows + '<div style="display: flex; font-size:1.5rem;" class="previewIcons">';
                rows = rows + '<a href="/previews/version/social/edit/subVersion/position/' + activeSubVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-list-ol"></i></a>';
                rows = rows + '<a href="/previews/version/social/edit/subVersion/' + activeSubVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-square-pen"></i></a>';
                rows = rows + '<a href="javascript:void(0)" onclick="return confirmSocialSubVersionDelete(' + activeSubVersion_id + ')" style="' + display + ' margin-right: 0.5rem;"><i class="fa-solid fa-square-minus"></i></a>';
                rows = rows + '</div>';
                rows = rows + '</div>';
                rows = rows + "@endif";
                $('#subVersionSettings').html(rows);
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function confirmSocialSubVersionDelete(activeSubVersion_id){
        Swal.fire({
            title: 'Delete This Sub Version?!',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Delete',
            denyButtonText: `Thinking.....`,
        }).then((result) => {
        /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                axios.get('/previews/social/subVersion/delete/'+ activeSubVersion_id)
                .then(function (response){
                    console.log(response);
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: 'Sub Version Has Been Deleted!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    location.reload();
                })
                .catch(function (error){
                    console.log(error);
                })
            } else if (result.isDenied) {
                Swal.fire('Thanks for using your brain', '', 'info')
            }
        })
    }

    function setVideoActiveSubVersionSettings(activeSubVersion_id) {
        axios.get('/preview/checkSubVersionCount/' + activeSubVersion_id)
            .then(function(response) {
                var display = 'display: block;';
                rows = '';
                rows = rows + "@if($authUserClientName == 'Planet Nine')";
                rows = rows + '<div>';
                rows = rows + '<div style="display: flex; font-size:1.5rem;" class="previewIcons">';
                rows = rows + '<a href="/previews/version/video/edit/subVersion/position/' + activeSubVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-list-ol"></i></a>';
                rows = rows + '<a href="/previews/version/video/edit/subVersion/' + activeSubVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-square-pen"></i></a>';
                rows = rows + '<a href="javascript:void(0)" onclick="return confirmVideoSubVersionDelete(' + activeSubVersion_id + ')" style="' + display + ' margin-right: 0.5rem;"><i class="fa-solid fa-square-minus"></i></a>';
                rows = rows + '</div>';
                rows = rows + '</div>';
                rows = rows + "@endif";
                $('#subVersionSettings').html(rows);
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function confirmVideoSubVersionDelete($id){
        Swal.fire({
            title: 'Delete This Sub Version?!',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Delete',
            denyButtonText: `Thinking.....`,
        }).then((result) => {
        /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                axios.get('/previews/video/subVersion/delete/'+ $id)
                .then(function (response){
                    console.log(response);
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: 'Sub Version Has Been Deleted!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    location.reload();
                })
                .catch(function (error){
                    console.log(error);
                })
            } else if (result.isDenied) {
                Swal.fire('Thanks for using your brain', '', 'info')
            }
        })
    }

    function setBannerActiveSubVersionSettings(activeSubVersion_id) {
        axios.get('/preview/checkSubVersionCount/' + activeSubVersion_id)
            .then(function(response) {
                var display = 'display: block;';
                rows = '';
                rows = rows + "@if($authUserClientName == 'Planet Nine')";
                rows = rows + '<div>';
                rows = rows + '<div style="display: flex; font-size:1.5rem;" class="previewIcons">';
                rows = rows + '<a href="/previews/version/banner/edit/subVersion/position/' + activeSubVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-list-ol"></i></a>';
                rows = rows + '<a href="/previews/version/banner/edit/subVersion/' + activeSubVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-square-pen"></i></a>';
                rows = rows + '<a href="javascript:void(0)" onclick="return confirmBannerSubVersionDelete(' + activeSubVersion_id + ')" style="' + display + ' margin-right: 0.5rem;"><i class="fa-solid fa-square-minus"></i></a>';
                rows = rows + '</div>';
                rows = rows + '</div>';
                rows = rows + "@endif";
                $('#subVersionSettings').html(rows);
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function setGifActiveSubVersionSettings(activeSubVersion_id) {
        axios.get('/preview/checkSubVersionCount/' + activeSubVersion_id)
            .then(function(response) {
                var display = 'display: block;';
                rows = '';
                rows = rows + "@if($authUserClientName == 'Planet Nine')";
                rows = rows + '<div>';
                rows = rows + '<div style="display: flex; font-size:1.5rem;" class="previewIcons">';
                rows = rows + '<a href="/previews/version/gif/edit/subVersion/' + activeSubVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-square-pen"></i></a>';
                rows = rows + '<a href="javascript:void(0)" onclick="return confirmGifSubVersionDelete(' + activeSubVersion_id + ')" style="' + display + ' margin-right: 0.5rem;"><i class="fa-solid fa-square-minus"></i></a>';
                rows = rows + '</div>';
                rows = rows + '</div>';
                rows = rows + "@endif";
                $('#subVersionSettings').html(rows);
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function confirmGifSubVersionDelete(activeSubVersion_id){
        Swal.fire({
            title: 'Delete This Sub Version?!',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Delete',
            denyButtonText: `Thinking.....`,
        }).then((result) => {
        /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                axios.get('/previews/gif/subVersion/delete/'+ activeSubVersion_id)
                .then(function (response){
                    console.log(response);
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: 'Sub Version Has Been Deleted!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    location.reload();
                })
                .catch(function (error){
                    console.log(error);
                })
            } else if (result.isDenied) {
                Swal.fire('Thanks for using your brain', '', 'info')
            }
        })
    }

    function confirmBannerSubVersionDelete(activeSubVersion_id){
        Swal.fire({
            title: 'Delete This Sub Version?!',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Delete',
            denyButtonText: `Thinking.....`,
        }).then((result) => {
        /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                axios.get('/previews/banner/subVersion/delete/'+ activeSubVersion_id)
                .then(function (response){
                    console.log(response);
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: 'Sub Version Has Been Deleted!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    // checkVersionType(response.data.version_id);
                    location.reload();
                })
                .catch(function (error){
                    console.log(error);
                })
            } else if (result.isDenied) {
                Swal.fire('Thanks for using your brain', '', 'info')
            }
        })
    }

    function reloadBanner(bannerReloadID) {
        var iframe = document.getElementById("rel" + bannerReloadID);
        iframe.src = iframe.src;
    }

    function setSocialActiveVersionSettings(activeVersion_id) {
        rows = '';
        rows = rows + "@if($authUserClientName == 'Planet Nine')";
        rows = rows + '<div>';
        rows = rows + '<div style="display: flex; font-size:1.5rem;" class="previewIcons">';
        rows = rows + '<a href="/previews/edit/version/' + activeVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-pen-to-square"></i></a>';
        rows = rows + '<a href="javascript:void(0)" onclick="return confirmVersionDelete(' + activeVersion_id + ')" style="margin-right: 0.5rem;"><i class="fa-solid fa-circle-minus"></i></a>';
        rows = rows + '</div>';
        rows = rows + '</div>';
        rows = rows + "@endif";

        $('#versionSettings').html(rows);
    }

    function setBannerActiveVersionSettings(activeVersion_id) {
        rows = '';
        rows = rows + "@if($authUserClientName == 'Planet Nine')";
        rows = rows + '<div>';
        rows = rows + '<div style="display: flex; font-size:1.5rem;" class="previewIcons">';
        rows = rows + '<a href="/previews/edit/version/' + activeVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-pen-to-square"></i></a>';
        rows = rows + '<a href="javascript:void(0)" onclick="return confirmVersionDelete(' + activeVersion_id + ')" style="margin-right: 0.5rem;"><i class="fa-solid fa-circle-minus"></i></a>';
        rows = rows + '</div>';
        rows = rows + '</div>';
        rows = rows + "@endif";

        $('#versionSettings').html(rows);
    }

    function setGifActiveVersionSettings(activeVersion_id) {
        rows = '';
        rows = rows + "@if($authUserClientName == 'Planet Nine')";
        rows = rows + '<div>';
        rows = rows + '<div style="display: flex; font-size:1.5rem;" class="previewIcons">';
        rows = rows + '<a href="/previews/edit/version/' + activeVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-pen-to-square"></i></a>';
        rows = rows + '<a href="javascript:void(0)" onclick="return confirmVersionDelete(' + activeVersion_id + ')" style="margin-right: 0.5rem;"><i class="fa-solid fa-circle-minus"></i></a>';
        rows = rows + '</div>';
        rows = rows + '</div>';
        rows = rows + "@endif";

        $('#versionSettings').html(rows);
    }

    function setVideoActiveVersionSettings(activeVersion_id) {
        rows = '';
        rows = rows + "@if($authUserClientName == 'Planet Nine')";
        rows = rows + '<div>';
        rows = rows + '<div style="display: flex; font-size:1.5rem;" class="previewIcons">';
        rows = rows + '<a href="/previews/edit/version/' + activeVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-pen-to-square"></i></a>';
        rows = rows + '<a href="javascript:void(0)" onclick="return confirmVersionDelete(' + activeVersion_id + ')" style="margin-right: 0.5rem;"><i class="fa-solid fa-circle-minus"></i></a>';
        rows = rows + '</div>';
        rows = rows + '</div>';
        rows = rows + "@endif";

        $('#versionSettings').html(rows);
    }

    function confirmVersionDelete(version_id){
        Swal.fire({
            title: 'Delete This Version?!',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Delete',
            denyButtonText: `Thinking.....`,
        }).then((result) => {
        /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                axios.get('/previews/version/delete/'+ version_id)
                .then(function (response){
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: 'Version Has Been Deleted!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    location.reload();
                })
                .catch(function (error){
                    console.log(error);
                })
            } else if (result.isDenied) {
                Swal.fire('Thanks for using your brain', '', 'info')
            }
        })
    }

    function setBannerDisplayOfActiveSubVersion(activeSubVersion_id) {
        document.getElementById('loaderArea').style.display = 'flex';
        axios.get('/preview/getActiveSubVersionBannerData/' + activeSubVersion_id)
            .then(function(response) {
                var row = '';

                $.each(response.data, function(key, value) {
                    var resolution = value.size_id;
                    var bannerPath = '/' + value.path + '/index.html';
                    var bannerReloadID = value.id;

                    row = row + '<div style="display: inline-block; width: ' + value.width + 'px; margin-right: 10px;">';
                    row = row + '<div style="display: flex; justify-content: space-between; padding: 0; color: black; border-top-left-radius: 5px; border-top-right-radius: 5px;">';
                    row = row + '<small style="float: left; font-size: 0.85rem; font-weight: bold;" id="bannerRes">' + value.width + 'x' + value.height + '</small>';
                    row = row + '<small style="float: right font-size: 0.85rem; font-weight: bold;" id="bannerSize">' + value.file_size + '</small>';
                    row = row + '</div>';
                    row = row + '<iframe style="margin-top: 2px;" src="' + bannerPath + '" width="' + value.width + '" height="' + value.height + '" frameBorder="0" scrolling="no" id=' + "rel" + value.id + '></iframe>'
                    row = row + '<ul style="display: flex; flex-direction: row;" class="previewIcons">';
                    row = row + '<li><i id="relBt' + value.id + '" onClick="reloadBanner(' + bannerReloadID + ')" class="fa-solid fa-repeat" style="display: flex; margin-top: 0.5rem; cursor: pointer; font-size:20px;"></i></li>';
                    row = row + '@if($authUserClientName == "Planet Nine")'
                    row = row + '<li><a href="/previews/banner/single/edit/' + value.id + '"><i class="fa-solid fa-pen-to-square" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>';
                    row = row + '<li><a href="/previews/banner/single/download/' + value.id + '"><i class="fa-solid fa-download" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>';
                    row = row + '<li><a href="javascript:void(0)" onclick="return confirmDeleteBanner(' + value.id + ')"><i class="fa-solid fa-trash" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>';
                    row = row + '@endif';
                    row = row + '</ul>';
                    row = row + '</div>';
                });

                $('#bannerShowcase').html(row);
            })
            .catch(function(error) {
                console.log(error);
            })
            .finally(function() {
                document.getElementById('loaderArea').style.display = 'none';
            })
    }

    function setGifDisplayOfActiveSubVersion(activeSubVersion_id) {
        document.getElementById('loaderArea').style.display = 'flex';
        axios.get('/preview/getActiveSubVersionGifData/' + activeSubVersion_id)
            .then(function(response) {
                var row = '';

                $.each(response.data, function(key, value) {
                    var resolution = value.size_id;
                    var bannerPath = '/' + value.path;
                    var bannerReloadID = value.id;

                    row = row + '<div style="display: inline-block; width: ' + value.width + 'px; margin-right: 10px;">';
                    row = row + '<div style="display: flex; justify-content: space-between; padding: 0; color: black; border-top-left-radius: 5px; border-top-right-radius: 5px;">';
                    row = row + '<small style="float: left; font-size: 0.85rem; font-weight: bold;" id="bannerRes">' + value.width + 'x' + value.height + '</small>';
                    row = row + '<small style="float: right font-size: 0.85rem; font-weight: bold;" id="bannerSize">' + value.file_size + '</small>';
                    row = row + '</div>';
                    row = row + '<iframe style="margin-top: 2px;" src="' + bannerPath + '" width="' + value.width + '" height="' + value.height + '" frameBorder="0" scrolling="no" id=' + "rel" + value.id + '></iframe>'
                    row = row + '<ul style="display: flex; flex-direction: row;" class="previewIcons">';
                    row = row + '<li><i id="relBt' + value.id + '" onClick="reloadGif(' + bannerReloadID + ')" class="fa-solid fa-repeat" style="display: flex; margin-top: 0.5rem; cursor: pointer; font-size:20px;"></i></li>';
                    row = row + '@if($authUserClientName == "Planet Nine")'
                    row = row + '<li><a href="/previews/gif/single/edit/' + value.id + '"><i class="fa-solid fa-pen-to-square" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>';
                    row = row + '<li><a href="/' + value.path + '" download><i class="fa-solid fa-download" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>';
                    row = row + '<li><a href="javascript:void(0)" onclick="return confirmDeleteGif(' + value.id + ')"><i class="fa-solid fa-trash" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>';
                    row = row + '@endif';
                    row = row + '</ul>';
                    row = row + '</div>';
                });

                $('#bannerShowcase').html(row);
            })
            .catch(function(error) {
                console.log(error);
            })
            .finally(function() {
                document.getElementById('loaderArea').style.display = 'none';
            })
    }

    function reloadGif(bannerReloadID) {
        var iframe = document.getElementById("rel" + bannerReloadID);
        iframe.src = iframe.src;
    }

    function confirmDeleteGif(id){
        Swal.fire({
            title: 'Delete This GIF?!',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Delete',
            denyButtonText: `Thinking.....`,
        }).then((result) => {
        /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                axios.delete('/previews/gif/single/delete/' + id)
                .then(function (response){
                    console.log(response);
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: 'GIF Has Been Deleted!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    location.reload();
                })
                .catch(function (error){
                    console.log(error);
                })
            } else if (result.isDenied) {
                Swal.fire('Thanks for using your brain', '', 'info')
            }
        })
    }

    function setVideoDisplayOfActiveSubVersion(activeSubVersion_id) {
        document.getElementById('loaderArea').style.display = 'flex';
        axios.get('/preview/getActiveSubVersionVideoData/' + activeSubVersion_id)
            .then(function(response) {
                var row = '';

                $.each(response.data, function(key, value) {
                    // Give each block a unique id for targeting
                    var uniqueId = 'videoBlock_' + value.id;
                    row += `
                        <div id="${uniqueId}" class="mx-auto mb-8" style="max-width: 100%;">
                            <!-- Name Bar -->
                            <div class="video-title font-semibold text-lg px-4 py-2 mx-auto text-center shadow-sm video-name-bar" style="letter-spacing:0.5px;border-radius: 40px;">
                                ${value.name}
                            </div>
                            <!-- Video -->
                            <div style="background:transparent; display:flex; justify-content:center;" class="mt-2 mb-2 rounded-lg">
                                <video 
                                    src="/${value.path}" 
                                    controls 
                                    class="block mx-auto rounded-2xl video-preview"
                                    style="max-width:90vw; max-height:70vh; width:auto; height:auto; background:#000;"
                                    controlsList="nodownload noremoteplayback"
                                    disablePictureInPicture
                                    onloadedmetadata="matchVideoMetaWidth(this)"
                                ></video>
                            </div>
                            <!-- Media Info -->
                            <div class="bg-gray-50 text-gray-800 text-sm rounded-2xl p-3 mt-2 w-full shadow-sm video-media-info" style="margin:0 auto;">
                                @if($authUserClientName == "Planet Nine")
                                <div class="flex gap-4 mb-2 justify-center">
                                    <a href="/previews/video/single/edit/${value.id}" class="edit-btn" data-id="${value.id}" title="Edit"><i class="fa-solid fa-pen-to-square" style="display: flex; margin-left: 0.5rem; font-size:20px;"></i></a>
                                    <a href="/${value.path}" download title="Download"><i class="fa-solid fa-download" style="display: flex; margin-left: 0.5rem; font-size:20px;"></i></a>
                                    <a href="javascript:void(0)" onclick="confirmDeleteVideo(${value.id})" class="delete-btn" data-id="${value.id}" title="Delete"><i class="fa-solid fa-trash" style="display: flex; margin-left: 0.5rem; font-size:20px;"></i></a>
                                </div>
                                @endif
                                <div class="font-semibold text-base mb-1 underline text-center flex justify-center align-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                </div>
                                <div class="font-semibold text-base mb-1 underline text-center">Media Info</div>
                                <div><strong>Resolution:</strong> ${value.width} x ${value.height}</div>
                                <div><strong>Aspect Ratio:</strong> ${value.aspect_ratio ?? '-'}</div>
                                <div><strong>Codec:</strong> ${value.codec ?? '-'}</div>
                                <div><strong>FPS:</strong> ${value.fps ?? '-'}</div>
                                <div><strong>File Size:</strong> ${value.file_size ?? '-'}</div>
                                <div class="mt-2 w-full flex flex-col items-center justify-center">
                                    ${
                                        value.companion_banner_path
                                        ? `
                                            <img src="/${value.companion_banner_path}" alt="Companion Banner" class="rounded border mx-auto" style="max-width:970px;max-height:auto;" />
                                            @if($authUserClientName == "Planet Nine")
                                                <a href="/${value.companion_banner_path}" download title="Download Companion Banner" class="mt-2 flex items-center gap-1 text-blue-600 hover:text-blue-800">
                                                    <i class="fa-solid fa-download" style="font-size:18px;"></i>
                                                    <span class="text-xs">Download Companion Banner</span>
                                                </a>
                                            @endif
                                        `
                                        : ''
                                    }
                                </div>
                            </div>
                        </div>
                    `;
                });

                $('#bannerShowcase').html(row);
            })
            .catch(function(error) {
                console.log(error);
            })
            .finally(function() {
                document.getElementById('loaderArea').style.display = 'none';
            })
    }

    function updateVideoActiveSubVersion(id){
        // Logic to update the active sub-version of the video
        axios.get('/preview/setVideoActiveSubVersion/' + id)
            .then(function(response) {
                setVideoVersionSubVersions(response.data.subVersions, response.data.version_id);
                setVideoActiveSubVersionSettings(response.data.activeSubVersion_id);
                setVideoDisplayOfActiveSubVersion(response.data.activeSubVersion_id);
            })
            .catch(function(error) {
                console.log(error);
            });
    }

    function confirmDeleteVideo(id){
        Swal.fire({
            title: 'Delete This Video?',
            text: "Are you sure you want to delete this video?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                axios.delete('/previews/video/single/delete/' + id)
                    .then(function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Video deleted!',
                            showConfirmButton: false,
                            timer: 1200
                        });
                        // Optionally refresh the video list
                        setVideoDisplayOfActiveSubVersion(response.data.subVersion_id);
                    })
                    .catch(function(error) {
                        Swal.fire('Error', 'Failed to delete video.', 'error');
                    });
            }
        });
    }

    // Helper function: match name bar and media info width to video
    function matchVideoMetaWidth(videoEl) {
        setTimeout(function() {
            var $video = $(videoEl);
            var width = $video.width();
            var $container = $video.closest('.mb-8');
            $container.find('.video-name-bar').css('width', width + 'px');
            $container.find('.video-media-info').css('width', width + 'px');
        }, 50);
    }

    function confirmDeleteBanner(id){
        Swal.fire({
                title: 'Delete This Banner?',
                text: "Are you sure you want to delete this banner?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete('/previews/banner/single/delete/' + id)
                        .then(function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Banner deleted!',
                                showConfirmButton: false,
                                timer: 1200
                            });
                            // Optionally refresh the banners list
                            setBannerDisplayOfActiveSubVersion(response.data.subVersion_id);
                        })
                        .catch(function(error) {
                            Swal.fire('Error', 'Failed to delete banner.', 'error');
                        });
                }
            });
    }

    function confirmDeleteSocial(id){
        Swal.fire({
                title: 'Delete This Social Image?',
                text: "Are you sure you want to delete this social image?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete('/previews/social/single/delete/' + id)
                        .then(function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Social Image deleted!',
                                showConfirmButton: false,
                                timer: 1200
                            });
                            // Optionally refresh the social images list
                            setSocialDisplayOfActiveSubVersion(response.data.subVersion_id);
                        })
                        .catch(function(error) {
                            Swal.fire('Error', 'Failed to delete social image.', 'error');
                        });
                }
            });
    }

    function setSocialDisplayOfActiveSubVersion(activeSubVersion_id) {
        document.getElementById('loaderArea').style.display = 'flex';
        axios.get('/preview/getActiveSubVersionSocialData/' + activeSubVersion_id)
            .then(function(response) {
                var row = '';
                $.each(response.data, function(key, value) {
                    row += `
                        <div style="display: inline-block; margin: 10px; width: 600px;">
                            <div class="text-center text-xl font-semibold capitalize social-title flex justify-center items-center"
                                style="padding: 10px; width: 100%; margin-bottom: 12px;">
                                <span>${value.name}</span>
                            </div>
                            <img src="/${value.path}" 
                                alt="${value.name}" 
                                class="social-preview-img rounded-2xl"
                                style="width: 600px; height: auto; object-fit: contain; box-shadow: 0 2px 8px #0001; cursor: pointer; margin-top: 0;"
                                onclick="openSocialImageModal('/${value.path}', '${value.name}')"
                            >
                            <ul style="display: flex; flex-direction: row; justify-content: left; margin-top: 10px;" class="previewIcons">
                                @if($authUserClientName == "Planet Nine")
                                    <li>
                                        <a href="/previews/social/single/edit/${value.id}">
                                            <i class="fa-solid fa-pen-to-square" style="display: flex; margin-left: 0.5rem; font-size:20px;"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="/${value.path}" download="${value.name}.jpg">
                                            <i class="fa-solid fa-download" style="display: flex; margin-left: 0.5rem; font-size:20px;"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0)" onclick="return confirmDeleteSocial(${value.id})">
                                            <i class="fa-solid fa-trash" style="display: flex; margin-left: 0.5rem; font-size:20px;"></i>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    `;
                });
                $('#bannerShowcase').html(row);
            })
            .catch(function(error) {
                console.log(error);
            })
            .finally(function() {
                document.getElementById('loaderArea').style.display = 'none';
            });
    }

    // Modal HTML (add this at the end of your <body>)
    if (!document.getElementById('socialImageModal')) {
        $('body').append(`
            <div id="socialImageModal" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.85); align-items:center; justify-content:center;">
                <span id="closeSocialModal" style="position:absolute; top:30px; right:40px; font-size:2.5rem; color:white; cursor:pointer; z-index:10001;">&times;</span>
                <img id="socialModalImg" src="" alt="" 
                    style="max-width:80vw; max-height:80vh; transition:transform 0.2s; cursor:zoom-in; display:block; margin:auto; padding:40px; background:rgba(0,0,0,0.1); border-radius:12px;">
            </div>
        `);
    }

    // When opening the modal
    window.openSocialImageModal = function(src, label) {
        // Always reset zoom and styles
        $('#socialModalImg')
            .attr('src', src)
            .css({
                width: '',
                height: '',
                'max-width': '80vw',
                'max-height': '80vh',
                'cursor': 'zoom-in',
                'transform': 'none'
            })
            .data('zoom', 1);
        $('#socialModalImgLabel').text(label);
        $('#socialImageModal').fadeIn(150);
        $('body').css('overflow', 'hidden'); // Disable background scroll
    };

    // When closing the modal
    $(document).on('click', '#closeSocialModal', function() {
        $('#socialImageModal').fadeOut(150);
        $('body').css('overflow', ''); // Re-enable background scroll
    });

    $(document).on('click', '#socialImageModal', function(e) {
        if (e.target === this) {
            $('#socialImageModal').fadeOut(150);
            $('body').css('overflow', ''); // Re-enable background scroll
        }
    });

    $('#socialImageModal').css('overflow', 'auto');

    let isDragging = false;
    let startX, startY, scrollLeft, scrollTop;
    let dragMoved = false; // <-- Add this

    $('#socialModalImg').on('mousedown', function(e) {
        if ($(this).data('zoom') === 2) {
            isDragging = true;
            dragMoved = false; // <-- Reset on mousedown
            $(this).css('cursor', 'grabbing');
            startX = e.pageX;
            startY = e.pageY;
            scrollLeft = $('#socialImageModal').scrollLeft();
            scrollTop = $('#socialImageModal').scrollTop();
            e.preventDefault();
        }
    });

    $(document).on('mousemove', function(e) {
        if (isDragging) {
            const x = e.pageX;
            const y = e.pageY;
            if (Math.abs(x - startX) > 3 || Math.abs(y - startY) > 3) { // <-- Threshold for drag
                dragMoved = true;
            }
            $('#socialImageModal').scrollLeft(scrollLeft - (x - startX));
            $('#socialImageModal').scrollTop(scrollTop - (y - startY));
        }
    });

    $(document).on('mouseup', function() {
        isDragging = false;
        $('#socialModalImg').css('cursor', $('#socialModalImg').data('zoom') === 2 ? 'zoom-out' : 'zoom-in');
    });

    // Zoom in/out on image click
    $(document).on('click', '#socialModalImg', function(e) {
        if (dragMoved) {
            dragMoved = false;
            return;
        }
        var zoom = $(this).data('zoom') || 1;

        if (zoom === 1) {
            $(this)
                .css({
                    width: '1600px',
                    height: 'auto',
                    'max-width': 'none',
                    'max-height': 'none',
                    'cursor': 'zoom-in',
                    'transform': 'none'
                })
                .data('zoom', 2);
        } else if (zoom === 2) {
            $(this)
                .css({
                    width: '2200px', // or any larger value
                    height: 'auto',
                    'max-width': 'none',
                    'max-height': 'none',
                    'cursor': 'zoom-out',
                    'transform': 'none'
                })
                .data('zoom', 3);
        } else {
            $(this)
                .css({
                    width: '',
                    height: '',
                    'max-width': '80vw',
                    'max-height': '80vh',
                    'cursor': 'zoom-in',
                    'transform': 'none'
                })
                .data('zoom', 1);
            $('#socialImageModal').scrollTop(0).scrollLeft(0);
        }
    });
</script>