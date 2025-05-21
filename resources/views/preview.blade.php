<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Creative - {{ $preview['name'] }}</title>
    <link rel="shortcut icon" href="https://www.planetnine.com/logo/new_favicon.png">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
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
    <div id="viewerList" class="absolute top-4 right-4 flex space-x-2 z-50"></div>
    @endif
    <div id="loaderArea">
        <span class="loader"></span>
    </div>

    <main class="main">
        <div class="viewMessage">
            For the best experience, view this showcase on a laptop or desktop — it's where the magic truly comes alive!
        </div>
        <section id="top" class="mb-4">
            <div class="px-4 py-4 flex justify-center content text-center relative">
                <div id="topDetails">
                    <img src="{{ asset('logos/' . $client['logo']) }}" id="planetnineLogo" class="py-3" alt="planetnineLogo">
                    <h1 style="font-size: 1rem;">Client Name: <span>{{ $client['name'] }}</span></h1>
                    <h1 style="font-size: 1rem;">Project Name: <span>{{ $preview['name'] }}</span></h1>
                    <h1 style="font-size: 1rem;" class="font-semibold">
                        Date: <span>{{ \Carbon\Carbon::parse($preview['created_at'])->format('F j, Y') }}</span>
                    </h1>
                </div>
            </div>
        </section>

        <section id="middle">
            <div id="showcase-section" class="mx-auto custom-container mt-2 px-8">
                <div class="flex row">
                    <div style="flex: 1;">
                        <div class="subVersions" style="display: flex; justify-content: center; flex-direction: row;"></div>
                    </div>
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
                            <div id="clientLogoSection" class="mb-2 mt-2 px-2 py-2 mx-auto">
                                <img src="{{ asset('logos/' . $client['logo']) }}"
                                    alt="clientLogo" style="width: 150px;">
                            </div>
                            @endif

                            <h2 style="padding-top: 10px; font-size: 24px; text-decoration: underline; text-align: center;">Creative Showcase</h2>

                            <div id="creative-list2"></div>
                        </div>

                        <div class="right-column">
                            <div id="versionArea">
                                <div id="versionCLick" onclick="showVersionDescription()">
                                    <i class="fa-regular fa-message"></i>
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
        <br>
        <br>
        <br>

        @if($preview['show_footer'])
        <footer class="footer py-6 mt-6">
            <div class="container mx-auto px-4 text-center text-base text-gray-600">
                &copy; All Rights Reserved. 
                <a href="https://www.planetnine.com" class="underline hover:text-black" target="_blank">
                    Planet Nine
                </a> - {{ now()->year }}
            </div>
        </footer>
        @endif
    </main>
</body>

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

                    row2 = row2 + '<div class="version-row ' + versionActive + '" onclick="return updateActiveVersion(' + value.id + ')" id="version' + value.id + '">'
                    row2 = row2 + '<span class="' + spanActive + '">' + value.name + '</span>'
                    row2 = row2 + '</div>';

                    row = row + '<a href="javascript:void(0)" class="nav-link versions" onclick="return updateActiveVersion(' + value.id + ')" id="version' + value.id + '">'
                    row = row + '<li class="' + active + '">' + value.name + '</li>'
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
            .finally(function() {
                // document.getElementById('menuClick').click();
            })
    }

    function updateActiveVersion(version_id) {
        var versionLabel = gsap.timeline();
        versionLabel
            .to('#versionInfo', {
                duration: 0.5,
                y: -30,
                ease: 'power2.in'
            });
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

                    row2 += '<div class="version-row ' + versionActive + '" onclick="return updateActiveVersion(' + value.id + ')" id="version' + value.id + '>';
                    row2 += '<span class="' + spanActive + '">' + value.name + '</span>';
                    row2 += '</div>';

                    row = row + '<a href="javascript:void(0)" class="versions nav-link" onclick="return updateActiveVersion(' + value.id + ')" id="version' + value.id + '">'
                    row = row + '<li class="' + active + '">' + value.name + '</li>'
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
        const url = '/preview/add/version/' + preview_id;
        window.location.href = url;
    }

    function checkVersionType(activeVersion_id) {
        axios.get('/preview/getVersionType/' + activeVersion_id)
            .then(function(response) {
                setVersionsDescription(response.data.version_description);

                if (response.data.type == "banner") {
                    setBannerVersionSubVersions(response.data.subVersions);

                    setBannerActiveSubVersionSettings(response.data.activeSubVersion_id);
                    setBannerActiveVersionSettings(activeVersion_id);

                    setBannerDisplayOfActiveSubVersion(response.data.activeSubVersion_id);
                }
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function setBannerVersionSubVersions(subVersions) {
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
                    <svg class="trapezoid" viewBox="0 0 200 60" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0,60 L200,60 L180,15 C175,5 170,0 160,0 L40,0 C30,0 25,5 20,15 L0,60 Z" fill="${isActive}"/>
                    </svg>
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
                <div class="subVersionTab subVersionAddTab" onclick="addNewSubVersion()">
                    <div class="trapezoid-container">
                        <svg class="trapezoid" viewBox="0 0 200 60" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0,60 L200,60 L180,15 C175,5 170,0 160,0 L40,0 C30,0 25,5 20,15 L0,60 Z"/>
                        </svg>
                        <div class="tab-text text-white text-2xl font-bold">+</div>
                    </div>
                </div>
            `;
        }
        $('.subVersions').html(row);
    }

    function updateBannerActiveSubVersion(subVersion_id) {
        axios.get('/preview/setBannerActiveSubVersion/' + subVersion_id)
            .then(function(response) {
                setBannerVersionSubVersions(response.data.subVersions);
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

    function setBannerActiveSubVersionSettings(activeSubVersion_id) {
        axios.get('/preview/checkSubVersionCount/' + activeSubVersion_id)
            .then(function(response) {
                var display = 'display: block;';
                rows = '';
                rows = rows + "@if($authUserClientName == 'Planet Nine')";
                rows = rows + '<div>';
                rows = rows + '<div style="display: flex; font-size:1.5rem;" class="previewIcons">';                
                rows = rows + '<a href="/preview/banner/edit/subVersion/' + activeSubVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-square-pen"></i></a>';
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

    function setBannerActiveVersionSettings(activeVersion_id) {
        rows = '';
        rows = rows + "@if($authUserClientName == 'Planet Nine')";
        rows = rows + '<div>';
        rows = rows + '<div style="display: flex; font-size:1.5rem;" class="previewIcons">';
        rows = rows + '<a href="/preview/edit/version/' + activeVersion_id + '" style="margin-right: 0.5rem;"><i class="fa-solid fa-pen-to-square"></i></a>';
        rows = rows + '<a href="javascript:void(0)" onclick="return confirmVersionDelete(' + activeVersion_id + ')" style="margin-right: 0.5rem;"><i class="fa-solid fa-circle-minus"></i></a>';
        rows = rows + '</div>';
        rows = rows + '</div>';
        rows = rows + "@endif";

        $('#versionSettings').html(rows);
    }

    function setBannerDisplayOfActiveSubVersion(activeSubVersion_id) {
        document.getElementById('loaderArea').style.display = 'flex';
        axios.get('/preview/getActiveSubVersionBannerData/' + activeSubVersion_id)
            .then(function(response) {
                console.log(response);
                var row = '';

                $.each(response.data, function(key, value) {
                    var resolution = value.size_id;
                    var bannerPath = '/' + value.path + '/index.html';
                    var bannerReloadID = value.id;

                    row = row + '<div style="display: inline-block; width: ' + value.width + 'px; margin-right: 10px;">';
                    row = row + '<div style="display: flex; justify-content: space-between; padding: 0; color: black; border-top-left-radius: 5px; border-top-right-radius: 5px;">';
                    row = row + '<small style="float: left; font-size: 0.85rem;" id="bannerRes">' + value.width + 'x' + value.height + '</small>';
                    row = row + '<small class="float: right font-size: 0.85rem;" id="bannerSize">' + value.file_size + '</small>';
                    row = row + '</div>';
                    row = row + '<iframe style="margin-top: 2px;" src="' + bannerPath + '" width="' + value.width + '" height="' + value.height + '" frameBorder="0" scrolling="no" id=' + "rel" + value.id + '></iframe>'
                    row = row + '<ul style="display: flex; flex-direction: row;" class="previewIcons">';
                    row = row + '<li><i id="relBt' + value.id + '" onClick="reload(' + bannerReloadID + ')" class="fa-solid fa-arrows-rotate" style="display: flex; margin-top: 0.5rem; cursor: pointer; font-size:20px;"></i></li>';
                    row = row + '@if($authUserClientName == "Planet Nine")'
                    row = row + '<li><a href="/project/preview/banner/edit/' + value.id + '"><i class="fa-solid fa-gear" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>';
                    row = row + '<li><a href="/project/preview/banner/download/' + value.id + '"><i class="fa-solid fa-circle-down" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>';
                    row = row + '<li><a href="javascript:void(0)" onclick="return confirmDeleteBanner(' + value.id + ')"><i class="fa-solid fa-trash-can" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>';
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
</script>