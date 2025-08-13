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
    <link rel="preload" as="image" href="/preview_images/white-smart.png">
    <link rel="shortcut icon" href="https://www.planetnine.com/logo/new_favicon.png">
    @vite('resources/css/app.css')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.9.0/axios.min.js" integrity="sha512-FPlUpimug7gt7Hn7swE8N2pHw/+oQMq/+R/hH/2hZ43VOQ+Kjh25rQzuLyPz7aUWKlRpI7wXbY6+U3oFPGjPOA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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
    <link href="{{ asset('css/preview4.css') }}" rel="stylesheet">
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

        @php
            $colorsData = $all_colors->map(fn($color) => ['id' => $color->id, 'hex' => $color->primary, 'border' => $color->tertiary]);
        @endphp

        <div id="mobilecolorPaletteClick" onclick="showColorPaletteOptions2()">
            <i class="fa-solid fa-palette"></i>
        </div>

        <div id="mobilecolorPaletteSelection" data-colors='@json($colorsData)'>
        </div>

        <section id="middle" class="mb-4">
            <div id="showcase-section" class="mx-auto custom-container mt-2">
                <div class="flex row justify-around items-end" style="min-height: 50px;">
                    <div class="py-2 flex items-end justify-center sidebar-top-desktop">
                        @if($preview['show_sidebar_logo'] == 1)
                            <img src="{{ asset('logos/' . $client['logo']) }}" 
                                alt="clientLogo" style="max-width: 200px; margin: 0 auto;">
                        @endif
                    </div>
                    <div style="flex: 1;">
                        <div class="feedbacks relative flex justify-center flex-row"></div>
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
                            <div class="justify-center items-center mt-2 py-2 px-2 absolute top-0 left-0 right-0 currentTotalFeedbacks">
                                <button id="categoryLeft" disabled style="margin-right:10px;">
                                    &#11207;
                                </button>
                                <span id="categoryCounter"></span>
                                <button id="categoryRight" disabled style="margin-left:10px;">
                                   &#11208;
                                </button>
                            </div>

                            <div class="feedbackSetsContainer"></div>

                            <div id="feedbackArea">
                                <div id="feedbackCLick" onclick="showcategoryDescription()">
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

                                <div id="feedbackDescription">
                                    <div id="feedbackDescriptionUpperpart">
                                        <div class="cursor-pointer" style="float: right;" onclick="hidefeedbackDescription()">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div id="feedbackDescriptionLowerPart">
                                        <label id="feedbackMessage"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="categoryControl">
                                <div id="categorySettings"></div>
                                <div id="feedbackSettings" style="position: absolute; right: 0;"></div>
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

    function showColorPaletteOptions2() {
        const preview_id = '{{ $preview_id }}';
        const paletteDiv2 = document.getElementById('mobilecolorPaletteSelection');

        if (paletteDiv2.innerHTML.trim() === '') {
            // Parse JSON array of objects with {id, hex}
            const colors = JSON.parse(paletteDiv2.dataset.colors);
            paletteDiv2.classList.add('color-grid');

            colors.forEach(({ id, hex, border }) => {
                const colorBox = document.createElement('div');
                colorBox.className = 'mobile-color-box';
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

                paletteDiv2.appendChild(colorBox);
            });
        }

        paletteDiv2.classList.add('visible');
        document.addEventListener('click', handleOutsideClick2);
    }

    function handleOutsideClick(event) {
        const paletteDiv = document.getElementById('colorPaletteSelection');
        const paletteToggle = document.getElementById('colorPaletteClick');

        if (!paletteDiv.contains(event.target) && !paletteToggle.contains(event.target)) {
            paletteDiv.classList.remove('visible');
            document.removeEventListener('click', handleOutsideClick);
        }
    }

    function handleOutsideClick2(event) {
        const paletteDiv2 = document.getElementById('mobilecolorPaletteSelection');
        const paletteToggle2 = document.getElementById('mobilecolorPaletteClick');

        if (!paletteDiv2.contains(event.target) && !paletteToggle2.contains(event.target)) {
            paletteDiv2.classList.remove('visible');
            document.removeEventListener('click', handleOutsideClick2);
        }
    }


    const authUserClientName = '{{ $authUserClientName }}';
    const preview_id = '{{ $preview_id }}';

    let categories = [];
    let currentCategoryIndex = 0;

    function getAllCategories() {
        axios.get('/preview/getallcategories/' + preview_id)
        .then(function(response) {
            categories = response.data.categories || [];
            // Find active category index
            currentCategoryIndex = categories.findIndex(c => c.id == response.data.activeCategory_id);
            if (currentCategoryIndex === -1) currentCategoryIndex = 0;
            renderCategories(response);
            updateCategoryNav();
        });
    }

    function renderCategories(response) {
        var active;
        var categoryActive;
        var spanActive;
        var row = '';
        var row2 = '';

        row = row + '@if($preview['show_sidebar_logo'] == 1)';
            row = row + '<div class="w-full">';
                row = row + '<div class="mb-2 mt-2 px-2 py-2 mx-auto">';
                    row = row + '<img src="{{ asset('logos/' . $client['logo']) }}" alt="clientLogo" style="width: 250px;">';
                row = row + '</div>';
            row = row + '</div>';
        row = row + '@endif';

        $.each(response.data.categories, function(key, value) {
            if (value.is_active == 1) {
                active = 'menuToggleActive';
                categoryActive = 'category-active';
                spanActive = 'span-active';
            } else {
                active = '';
                categoryActive = '';
                spanActive = '';
            }

            const date = new Date(value.created_at);
            const formatted = date.toLocaleDateString('en-GB'); // DD/MM/YYYY
            const formatted2 = `${date.getDate().toString().padStart(2, '0')}-${(date.getMonth()+1).toString().padStart(2, '0')}-${date.getFullYear()}`;

            row2 = row2 + '<div class="category-row ' + categoryActive + '" onclick="return updateActiveCategory(' + value.id + ')" id="category' + value.id + '">';
            row2 = row2 + '<span class="' + spanActive + '" style="font-size: 0.85rem;">' + value.name + '</span>';
            row2 = row2 + '<hr>';
            row2 += '<span class="category-row-date" style="font-size: 0.7rem;">' + formatted2 + '</span>';
            row2 = row2 + '</div>';

            row = row + '<a href="javascript:void(0)" class="nav-link categories" onclick="return updateActiveCategory(' + value.id + ')" id="category' + value.id + '">';
            row = row + '<li class="' + active + '">' + value.name + '</li>';
            row = row + '</a>';
        });

        
        if(authUserClientName == "Planet Nine"){
            row2 += `
                <div class="category-row category-add-btn" onclick="return addNewcategory(${preview_id})" style="cursor: pointer; margin-top: 8px;">
                    <span class="text-2xl text-green-500 hover:text-green-700 font-bold">+</span>
                </div>
            `;
        }

        $('#creative-list2').html(row2);
        $('#creative-list').html(row);
        $('#menu').html(row);

        checkCategoryType(response.data.activeCategory_id);
    }

    function updateCategoryNav() {
        const total = categories.length;
        $('#categoryCounter').text(total ? `${currentCategoryIndex + 1} of ${total}` : '0 of 0');
        $('#categoryLeft').prop('disabled', currentCategoryIndex === 0);
        $('#categoryRight').prop('disabled', currentCategoryIndex === total - 1 || total === 0);
    }

    $('#categoryLeft').on('click', function() {
        if (currentCategoryIndex > 0) {
            currentCategoryIndex--;
            updateActiveCategory(categories[currentCategoryIndex].id);
            updateCategoryNav();
        }
    });

    $('#categoryRight').on('click', function() {
        if (currentCategoryIndex < categories.length - 1) {
            currentCategoryIndex++;
            updateActiveCategory(categories[currentCategoryIndex].id);
            updateCategoryNav();
        }
    });

    function updateActiveCategory(category_id) {
        // document.getElementById('menuClick').click();
        alert('asdasd');
    }

    function checkCategoryType(category_id){
        axios.get('/preview/fetchCategoryType/' + category_id)
            .then(function(response) {
                if (response.data.type == "banner") {
                    renderFeedbacks(response.data.feedbacks, response.data.category_id);
                    fetchFeedbackSets(response.data.activeFeedback_id);
                }
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function renderFeedbacks(feedbacks, category_id){
        var feedbackCount = feedbacks.length;
        var isActive;

        if (feedbackCount > 0) {
            var row = '';
            $.each(feedbacks, function(key, value) {
                if (value.is_active == 1) {
                    isActive = ' feedbackTabActive';
                } else {
                    isActive = '';
                }
               row += `
                <div id="feedbackTab${value.id}" class="feedbackTab${isActive}" onclick="updateBannerActiveFeedback(${value.id})">
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
                <div class="feedbackTab feedbackAddTab" onclick="addBannerNewFeedback(${category_id})">
                    <div class="trapezoid-container">
                        <div class="tab-text text-white text-2xl font-bold">+</div>
                    </div>
                </div>
            `;
        }
        $('.feedbacks').html(row);
    }

    function fetchFeedbackSets(feedback_id){
        axios.get('/preview/fetchFeedbackSets/' + feedback_id)
            .then(function(response) {
                renderFeedbackSets(response.data.feedbackSets);
            })
            .catch(function(error) {
                console.log(error);
            });
    }

    function renderFeedbackSets(feedbackSets){
        var row = '';
        $.each(feedbackSets, function(key, value) {
            if(value.name || authUserClientName == 'Planet Nine'){
                row += `
                <div class="feedbackSet" id="feedbackSet${value.id}" style="display: flex; align-items: center; justify-content: space-between;">
                    <div class="feedbackSetName" style="flex: 1; text-align: center;">${value.name ? value.name : ''}</div>
                    ${authUserClientName == 'Planet Nine' ? `
                        <div class="feedbackSetActions" style="display: flex; gap: 0.5rem;">
                            <button onclick="addFeedbackSet(${value.id})" title="Add"><i class="fa-solid fa-plus"></i></button>
                            <button onclick="editFeedbackSet(${value.id})" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                            <button onclick="deleteFeedbackSet(${value.id})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    ` : ''}
                </div>
            `;
            }
        });
        $('.feedbackSetsContainer').html(row);
    }

    getAllCategories();

</script>
