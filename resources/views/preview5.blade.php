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
    <link href="{{ asset('css/preview5.css') }}" rel="stylesheet">
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

    @if($authUserClientName == "Planet Nine")
    <a href="/previews/update/{{ $preview->id }}" id="bulk-customization" class="text-white font-medium cursor-pointer" style="z-index: 1000;">
        <i class="fa-solid fa-gear"></i>
    </a>
    @endif

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
                    <div style="flex: 1;" class="feedbackTabs-parent">
                        <div class="feedbacks relative flex justify-center flex-row"></div>
                    </div>
                    <!-- <div style="width: 270px; min-height: 60px;" class="sidebar-top-extra"></div> -->
                </div>
                <div id="showcase">
                    <div id="bannershowCustom">
                        <nav role="navigation" class="mobileShowcase">
                            <div id="mobileMenuToggle">
                                <button id="openMobileMenu" aria-label="Open menu">
                                    <i class="fa-solid fa-bars"></i>
                                </button>
                            </div>
                            <div id="mobileMenu" class="mobile-menu-panel">
                                <button id="closeMobileMenu" aria-label="Close menu">&times;</button>
                                <div class="sidebar-image mx-auto mb-4">
                                        <span>Creative Showcase</span>
                                    </div>
                                <ul id="mobileCategoryList"></ul>
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
                            <!-- <div class="justify-center items-center mt-2 py-2 px-2 absolute top-0 left-0 right-0 currentTotalFeedbacks">
                                <button id="categoryLeft" disabled style="margin-right:10px;">
                                    <i class="fa-solid fa-arrow-left"></i>
                                </button>
                                <span id="categoryCounter"></span>
                                <button id="categoryRight" disabled style="margin-left:10px;">
                                   <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div> -->
                            <div class="justify-center items-center mt-1 py-2 px-2 absolute top-0 left-0 right-0 currentTotalFeedbacks">
                                <button id="feedbackLeft" disabled style="margin-right:10px;">
                                    <i class="fa-solid fa-arrow-left"></i>
                                </button>
                                <span id="feedbackCounter"></span>
                                <button id="feedbackRight" disabled style="margin-left:10px;">
                                   <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>

                            <div class="feedbackSetsContainer"></div>

                            <div id="feedbackArea">
                                <div id="feedbackCLick" onclick="showFeedbackDescription()">
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
                                        <div class="cursor-pointer" style="float: right;" onclick="hideFeedbackDescription()">
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
    const authUserClientName = '{{ $authUserClientName }}';
    const preview_id = '{{ $preview_id }}';

    let categories = [];
    let currentCategoryIndex = 0;
    let feedbacks = [];
    let currentFeedbackIndex = 0;

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

    // Open/close mobile menu
    document.getElementById('openMobileMenu').onclick = function() {
        document.getElementById('mobileMenu').classList.add('open');
        document.getElementById('mobileMenuToggle').classList.add('hidden');
        document.body.style.overflow = 'hidden';
        document.addEventListener('click', handleOutsideClick);
    };
    document.getElementById('closeMobileMenu').onclick = function() {
        document.getElementById('mobileMenu').classList.remove('open');
        document.getElementById('mobileMenuToggle').classList.remove('hidden');
        document.body.style.overflow = '';
        document.removeEventListener('click', handleOutsideClick);
    };

    function showFeedbackDescription() {
        viewFeedback = true;

        var moveFeedback = gsap.timeline();

        moveFeedback
            .to('#feedbackDescription', {
                duration: 1,
                display: 'flex',
                opacity: 1,
                x: 0,
                ease: 'power2.out'
            });

        if (viewFeedback == true) {
            var except = document.getElementById('feedbackDescription');

            document.addEventListener('click', closeThisFeedback, true);

            function closeThisFeedback(e) {
                if (!except.contains(e.target)) { //if the clicked element is the feedback div then it wont disappear
                    var listID = document.getElementById('feedbackDescription');
                    let viewFeedbackTimeline = gsap.timeline();
                    viewFeedbackTimeline
                        .to('#feedbackDescription', {
                            duration: 0.5,
                            x: 310,
                            ease: 'power2.in'
                        });
                }
            }
            viewversion = false;
        }
    }

    function hideFeedbackDescription() {
        var moveFeedback = gsap.timeline();

        moveFeedback
            .to('#feedbackDescription', {
                duration: 0.5,
                x: 310,
                opacity: 0,
                display: 'none',
                ease: 'power2.in'
            })
    }

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
        document.addEventListener('click', handleOutsideClick);
    }

    function handleOutsideClick(event) {
        // Color palette desktop
        const paletteDiv = document.getElementById('colorPaletteSelection');
        const paletteToggle = document.getElementById('colorPaletteClick');
        if (paletteDiv && paletteDiv.classList.contains('visible')) {
            if (!paletteDiv.contains(event.target) && !paletteToggle.contains(event.target)) {
                paletteDiv.classList.remove('visible');
                document.removeEventListener('click', handleOutsideClick);
                return;
            }
        }

        // Color palette mobile
        const paletteDiv2 = document.getElementById('mobilecolorPaletteSelection');
        const paletteToggle2 = document.getElementById('mobilecolorPaletteClick');
        if (paletteDiv2 && paletteDiv2.classList.contains('visible')) {
            if (!paletteDiv2.contains(event.target) && !paletteToggle2.contains(event.target)) {
                paletteDiv2.classList.remove('visible');
                document.removeEventListener('click', handleOutsideClick);
                return;
            }
        }

        // Mobile menu
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        if (mobileMenu && mobileMenu.classList.contains('open')) {
            if (!mobileMenu.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                mobileMenu.classList.remove('open');
                mobileMenuToggle.classList.remove('hidden');
                document.body.style.overflow = '';
                document.removeEventListener('click', handleOutsideClick);
                return;
            }
        }
    }

    function renderCategories() {
        axios.get('/preview/renderCategories/' + preview_id)
        .then(function(response) {
            // Find active category index
            categories = response.data.categories || [];
            currentCategoryIndex = categories.findIndex(c => c.id == response.data.activeCategory.id);
            if (currentCategoryIndex === -1) currentCategoryIndex = 0;
            
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
                    var clickHandler = ''; // No click for active
                } else {
                    active = '';
                    categoryActive = '';
                    spanActive = '';
                    var clickHandler = 'onclick="return updateActiveCategory(' + value.id + ')"';
                }

                const date = new Date(value.created_at);
                const formatted2 = `${date.getDate().toString().padStart(2, '0')}-${(date.getMonth()+1).toString().padStart(2, '0')}-${date.getFullYear()}`;

                row2 += '<div class="category-row ' + categoryActive + '" ' + clickHandler + ' id="category' + value.id + '">';
                row2 += '<span class="' + spanActive + '" style="font-size: 0.85rem;">' + value.name + '</span>';
                row2 += '<hr>';
                row2 += '<span class="category-row-date" style="font-size: 0.7rem;">' + formatted2 + '</span>';
                row2 += '</div>';

                row += `<a href="javascript:void(0)" class="nav-link categories ${active}" ${clickHandler} id="category${value.id}" style="display: block; padding: 8px 0;">`;
                row += `<span class="category-number">${key + 1}.</span> ${value.name}`;
                row += `</a>`;
            });

            renderFeedbacks(response);
            updateCategoryNav();
            renderMobileCategories(categories, response.data.activeCategory.id);

            $('#creative-list2').html(row2);
            $('#creative-list').html(row);
            $('#menu').html(row);
        });
    }

    // Render categories in mobile menu
    function renderMobileCategories(categories, activeCategoryId) {
        const list = document.getElementById('mobileCategoryList');
        list.innerHTML = '';
        categories.forEach((cat, idx) => {
            const li = document.createElement('li');
            li.textContent = `${idx + 1}. ${cat.name}`;
            if (cat.id === activeCategoryId) li.classList.add('active');
            li.onclick = function() {
                updateActiveCategory(cat.id);
                document.getElementById('mobileMenu').classList.remove('open');
                document.getElementById('mobileMenuToggle').classList.remove('hidden'); // <-- Add this line
                document.body.style.overflow = '';
            };
            list.appendChild(li);
        });
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
        axios.get('/preview/updateActiveCategory/' + category_id)
            .then(function(response) {
               renderCategories();
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function updateActiveFeedback(feedback_id) {
        axios.get('/preview/updateActiveFeedback/' + feedback_id)
            .then(function(response) {
               renderFeedbacks(response);
            })
            .catch(function(error) {
                console.log(error);
            })
    }

    function renderFeedbacks(response){
        feedbacks = response.data.feedbacks || [];
        var feedbackCount = feedbacks.length;
        var isActive;

        currentFeedbackIndex = feedbacks.findIndex(f => f.is_active == 1);
        if (currentFeedbackIndex === -1) currentFeedbackIndex = 0;

        var row = '';
        row += `<div class="feedbackTabsContainer">`;
        $.each(feedbacks, function(key, value) {
            if (value.is_active == 1) {
                isActive = ' feedbackTabActive';
                var clickHandler = ''; // No click for active feedback
            } else {
                isActive = '';
                var clickHandler = 'onclick="updateActiveFeedback(' + value.id + ')"';
            }
            row += `
                <div style="display: flex; align-items: center; justify-content: center; flex-direction: column;">
                    <div id="feedbackTab${value.id}" class="feedbackTab${isActive}" ${clickHandler}>
                        <div class="trapezoid-container">
                            <div class="tab-text text-white text-base">${value.name}</div>
                        </div>
                    </div>
                </div>
            `;
            $('#feedbackMessage').html(value.description);
        });
        row += '</div>';
        $('.feedbacks').html(row);
        updateFeedbackNav();
        renderFeedbackSets(response);
        setTimeout(enableFeedbackTabsDragScroll, 10);
        scrollActiveFeedbackTabIntoView();
    }

    function updateFeedbackNav() {
        const total = feedbacks.length;
        if(total > 1){
            $('#feedbackCounter').text(total ? `Feedbacks: ${currentFeedbackIndex + 1} of ${total}` : 'No Feedbacks');
        }
        $('#feedbackCounter').text(total ? `Feedbacks: ${currentFeedbackIndex + 1} of ${total}` : 'No Feedbacks');
        if (currentFeedbackIndex === 0) {
            $('#feedbackLeft').prop('disabled', true).css('opacity', 0.5);
        } else {
            $('#feedbackLeft').prop('disabled', false).css('opacity', 1);
        }

        // Disable and set opacity for right arrow
        if (currentFeedbackIndex === total - 1 || total === 0) {
            $('#feedbackRight').prop('disabled', true).css('opacity', 0.5);
        } else {
            $('#feedbackRight').prop('disabled', false).css('opacity', 1);
        }
    }

    $('#feedbackLeft').on('click', function() {
        if (currentFeedbackIndex > 0 && feedbacks[currentFeedbackIndex - 1]) {
            currentFeedbackIndex--;
            updateActiveFeedback(feedbacks[currentFeedbackIndex].id);
            updateFeedbackNav();
            setTimeout(scrollActiveFeedbackTabIntoView, 100);
        }
    });

    $('#feedbackRight').on('click', function() {
        if (currentFeedbackIndex < feedbacks.length - 1 && feedbacks[currentFeedbackIndex + 1]) {
            currentFeedbackIndex++;
            updateActiveFeedback(feedbacks[currentFeedbackIndex].id);
            updateFeedbackNav();
            setTimeout(scrollActiveFeedbackTabIntoView, 100);
        }
    });

    function scrollActiveFeedbackTabIntoView() {
        const container = document.querySelector('.feedbackTabsContainer');
        if (!container) return;
        const activeTab = container.querySelector('.feedbackTabActive');
        if (activeTab) {
            const containerRect = container.getBoundingClientRect();
            const tabRect = activeTab.getBoundingClientRect();
            const offset = tabRect.left - containerRect.left - (containerRect.width / 2) + (tabRect.width / 2);
            container.scrollBy({ left: offset, behavior: 'smooth' });
        }
    }

    function enableFeedbackTabsDragScroll() {
        const container = document.querySelector('.feedbackTabsContainer');
        console.log(container);
        if (!container) return;

        // Set justify-content based on overflow
        if (container.scrollWidth > container.clientWidth) {
            container.style.justifyContent = 'flex-start';
        } else {
            container.style.justifyContent = 'center';
        }

        // Only enable drag if overflow exists
        if (container.scrollWidth <= container.clientWidth) return;

        let isDown = false;
        let startX;
        let scrollLeft;

        container.addEventListener('mousedown', (e) => {
            isDown = true;
            container.classList.add('dragging');
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
            e.preventDefault();
        });

        container.addEventListener('mouseleave', () => {
            isDown = false;
            container.classList.remove('dragging');
        });

        container.addEventListener('mouseup', () => {
            isDown = false;
            container.classList.remove('dragging');
        });

        container.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            const x = e.pageX - container.offsetLeft;
            const walk = (x - startX); // scroll-fast
            container.scrollLeft = scrollLeft - walk;
        });
    }

    function renderFeedbackSets(response){
        var feedbackSets = response.data.feedbackSets;
        var row = '';

        $.each(feedbackSets, function(key, value) {
            if (value.name) {
                row += `
                    <div class="feedbackSet" id="feedbackSet${value.id}" style="display: flex; align-items: center; justify-content: space-between;">
                        <div class="feedbackSetName" style="flex: 1; text-align: center;">
                            ${value.name}
                        </div>
                    </div>
                `;
            }
            // Unique versions container for each feedbackSet
            row += `<div class="versions" id="versions${value.id}"></div>`;
        });
        $('.feedbackSetsContainer').html(row);

        // Now render versions for each feedbackSet in its own container
        feedbackSets.forEach(function(set) {
            renderVersions(set.id, response);
        });
    }

    function renderVersions(feedbackSet_id, res){
        axios.get('/preview/renderVersions/' + feedbackSet_id)
        .then(function(response) {
            const versions = response.data.versions;
            let versionRows = '';
            versions.forEach(version => {
                versionRows += `
                    <div>
                        ${version.name ? `<div class="version-title" style="font-weight: bold;">${version.name}</div>` : ''}
                        <div class="banners-list" id="bannersList${version.id}"></div>
                    </div>
                `;
                if(res.data.activeCategory.type == 'banner'){
                    renderBanners(version.id);
                }
                if(res.data.activeCategory.type == 'video'){
                    renderVideo(version.id);
                }
                if(res.data.activeCategory.type == 'social'){
                    renderSocial(version.id);
                }
                if(res.data.activeCategory.type == 'gif'){
                    renderGif(version.id);
                }
            });
            // Render versions in the correct feedbackSet container
            $('#versions' + feedbackSet_id).html(versionRows);
        });
    }

    function renderBanners(version_id){
        document.getElementById('loaderArea').style.display = 'flex';
        axios.get('/preview/renderBanners/' + version_id)
        .then(function(response) {
            const banners = response.data.banners;
            let bannersHtml = '';
            banners.forEach(function(banner) {
                var bannerPath = '/' + banner.path + '/index.html';
                var bannerReloadID = banner.id;
                bannersHtml += '<div class="banner-creatives banner-area-'+ banner.size.width +'" style="display: inline-block; width: ' + banner.size.width + 'px; margin-right: 0.5rem; margin-left: 0.5rem;">';
                bannersHtml += '<div style="display: flex; justify-content: space-between; padding: 0; color: black; border-top-left-radius: 5px; border-top-right-radius: 5px;">';
                bannersHtml += '<small style="float: left; font-size: 0.85rem; font-weight: bold;" id="bannerRes">' + banner.size.width + 'x' + banner.size.height + '</small>';
                bannersHtml += '<small style="float: right; font-size: 0.85rem; font-weight: bold;" id="bannerSize">' + banner.file_size + '</small>';
                bannersHtml += '</div>';
                bannersHtml += '<iframe class="iframe-banners" style="margin-top: 2px;" src="' + bannerPath + '" width="' + banner.size.width + '" height="' + banner.size.height + '" frameBorder="0" scrolling="no" id="rel' + banner.id + '"></iframe>';
                bannersHtml += '<ul style="display: flex; flex-direction: row;" class="previewIcons">';
                bannersHtml += '<li><i id="relBt' + banner.id + '" onClick="reloadBanner(' + bannerReloadID + ')" class="fa-solid fa-repeat" style="display: flex; margin-top: 0.5rem; cursor: pointer; font-size:20px;"></i></li>';
                bannersHtml += '@if($authUserClientName == "Planet Nine")'
                bannersHtml += '<li class="banner-options"><a href="/previews/banner/download/' + banner.id + '"><i class="fa-solid fa-download" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>';
                bannersHtml += '@endif';
                bannersHtml += '</ul>';
                bannersHtml += '</div>';
            });
            // Render banners in the correct version container
            $('#bannersList' + version_id).html(bannersHtml);
        })
        .catch(function(error) {
            console.log(error);
        })
        .finally(function() {
            document.getElementById('loaderArea').style.display = 'none';
        });
    }

    function renderVideo(version_id){
        document.getElementById('loaderArea').style.display = 'flex';
        axios.get('/preview/renderVideos/' + version_id)
        .then(function(response) {
            var row = '';

            $.each(response.data.videos, function(key, value) {
                // Give each block a unique id for targeting
                var uniqueId = 'videoBlock_' + value.id;
                row += `
                    <div id="${uniqueId}" class="mx-auto mb-8" style="max-width: 100%;">
                        <!-- Video -->
                        <div style="background:transparent; display:flex; justify-content:center;" class="mt-2 mb-2 rounded-lg">
                            <video 
                                src="/${value.path}" 
                                controls 
                                muted
                                class="block mx-auto rounded-2xl video-preview"
                                style="max-width:70vw; max-height:50vh; min-width: 340px; width:auto; height:auto; background:#000;"
                                controlsList="nodownload noremoteplayback"
                                disablePictureInPicture
                                onloadedmetadata="matchVideoMetaWidth(this)"
                            ></video>
                        </div>
                        <!-- Media Info -->
                        <div class="bg-gray-50 text-gray-800 text-sm rounded-2xl p-3 mt-2 w-full video-media-info" style="margin:0 auto;">
                            @if($authUserClientName == "Planet Nine")
                            <div class="flex gap-4 mb-2 justify-center">
                                <a href="/${value.path}" download title="Download"><i class="fa-solid fa-download" style="display: flex; margin-left: 0.5rem; font-size:20px;"></i></a>                                </div>
                            @endif
                            <div class="font-semibold text-base mb-1 underline text-center flex justify-center align-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                            </div>
                            <div class="font-semibold text-base mb-1 underline text-center">Media Info</div>
                            <div><strong>Resolution:</strong> ${value.size.width} x ${value.size.height}</div>
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

            $('#bannersList' + version_id).html(row);
        })
        .catch(function(error) {
            console.log(error);
        })
        .finally(function() {
            document.getElementById('loaderArea').style.display = 'none';
        })
    }

    function matchVideoMetaWidth(videoEl) {
        setTimeout(function() {
            var $video = $(videoEl);
            var width = $video.width();
            var $container = $video.closest('.mb-8');
            $container.find('.video-name-bar').css('width', width + 'px');
            $container.find('.video-media-info').css('width', width + 'px');
        }, 50);
    }

    function renderSocial(version_id){
        document.getElementById('loaderArea').style.display = 'flex';
        axios.get('/preview/renderSocials/' + version_id)
        .then(function(response) {
            var row = '';
            $.each(response.data.socials, function(key, value) {
                row += `
                    <div style="display: inline-block; margin: 10px; max-width: 1000px;">
                        <img src="/${value.path}" 
                            alt="${value.name}"
                            class="social-preview-img rounded-2xl"
                            style="width: 100%; height: auto; object-fit: contain; box-shadow: 0 2px 8px #0001; cursor: pointer; margin-top: 0;"
                            onclick="openSocialImageModal('/${value.path}', '${value.name}')"
                        >
                        <ul style="display: flex; flex-direction: row; justify-content: left; margin-top: 10px;" class="previewIcons">
                            @if($authUserClientName == "Planet Nine")
                                <li>
                                    <a href="/${value.path}" download="${value.name}.jpg">
                                        <i class="fa-solid fa-download" style="display: flex; margin-left: 0.5rem; font-size:20px;"></i>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                `;
            });
            // Render into the correct version container
            $('#bannersList' + version_id).html(row);
        })
        .catch(function(error) {
            console.log(error);
        })
        .finally(function() {
            setTimeout(function() {
                document.getElementById('loaderArea').style.display = 'none';
            }, 200); // Optional: delay for smoother UX
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
                'position': 'absolute',
                'top': '50%',
                'left': 0,
                'right': 0,
                'transform': 'translateY(-50%)'
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
                    'position': 'absolute',
                    'top': '50%',
                    'left': 0,
                    'right': 0,
                    'transform': 'translateY(-50%)'
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
                    'position': 'absolute',
                    'top': '50%',
                    'left': 0,
                    'right': 0,
                    'transform': 'translateY(-50%)'
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
                    'transform': 'none',
                    'position': 'absolute',
                    'top': '50%',
                    'left': 0,
                    'right': 0,
                    'transform': 'translateY(-50%)'
                })
                .data('zoom', 1);
            $('#socialImageModal').scrollTop(0).scrollLeft(0);
        }
    });

    function renderGif(version_id){
        document.getElementById('loaderArea').style.display = 'flex';
        axios.get('/preview/renderGifs/' + version_id)
        .then(function(response) {
            const gifs = response.data.gifs;
            let gifsHtml = '';
            gifs.forEach(function(gif) {
                var gifPath = '/' + gif.path;
                var gifReloadID = gif.id;
                gifsHtml += '<div class="banner-creatives banner-area-'+ gif.size.width +'" style="display: inline-block; width: ' + gif.size.width + 'px; margin-right: 0.5rem; margin-left: 0.5rem;">';
                gifsHtml += '<div style="display: flex; justify-content: space-between; padding: 0; color: black; border-top-left-radius: 5px; border-top-right-radius: 5px;">';
                gifsHtml += '<small style="float: left; font-size: 0.85rem; font-weight: bold;" id="bannerRes">' + gif.size.width + 'x' + gif.size.height + '</small>';
                gifsHtml += '<small style="float: right; font-size: 0.85rem; font-weight: bold;" id="bannerSize">' + gif.file_size + '</small>';
                gifsHtml += '</div>';
                gifsHtml += '<img class="iframe-banners" style="margin-top: 2px;" src="' + gifPath + '" width="' + gif.size.width + '" height="' + gif.size.height + '" frameBorder="0" scrolling="no" id="rel' + gif.id + '"></img>';
                gifsHtml += '<ul style="display: flex; flex-direction: row;" class="previewIcons">';
                gifsHtml += '<li><i id="relBt' + gif.id + '" onClick="reloadBanner(' + gifReloadID + ')" class="fa-solid fa-repeat" style="display: flex; margin-top: 0.5rem; cursor: pointer; font-size:20px;"></i></li>';
                gifsHtml += '@if($authUserClientName == "Planet Nine")'
                gifsHtml += `<li class="banner-options"><a href="/${gif.path}" download="${gif.name}"><i class="fa-solid fa-download" style="display: flex; margin-top: 0.5rem; margin-left: 0.5rem; font-size:20px;"></i></a></li>`;                gifsHtml += '@endif';
                gifsHtml += '</ul>';
                gifsHtml += '</div>';
            });
            // Render gifs in the correct version container
            $('#bannersList' + version_id).html(gifsHtml);
        })
        .catch(function(error) {
            console.log(error);
        })
        .finally(function() {
            document.getElementById('loaderArea').style.display = 'none';
        });
    }

    // Call every 10 seconds
    setInterval(fetchViewers, 10000);
    fetchViewers();
    renderCategories();

</script>