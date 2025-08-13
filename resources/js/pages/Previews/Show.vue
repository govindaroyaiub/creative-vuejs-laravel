<template>
  <!-- keep stylesheet link (asset path translated to public path) -->
  <link href="/css/preview2.css" rel="stylesheet" />

  <div>
    <div id="loaderArea">
      <span class="loader"></span>
    </div>

    <main class="main">
      <div class="viewMessage">For the best experience, view this showcase on a laptop or desktop.</div>

      <section id="top" class="mb-4">
        <div class="px-4 py-4 flex justify-center content text-center relative">
          <div id="topDetails" class="mt-4">
            <img src="/logos/example.png" id="planetnineLogo" class="py-3" alt="planetnineLogo" />
            <h1 style="font-size: 1rem;"><span class="font-semibold">Name: </span> <span class="capitalize">Example Preview</span></h1>
            <h1 class="mt-1" style="font-size: 1rem;"><span class="font-semibold">Client: </span> <span class="capitalize">Client Name</span></h1>
            <h1 style="font-size: 1rem;"><span class="font-semibold">Date: </span> <span>January 1, 2025</span></h1>
          </div>
        </div>
      </section>

      <div id="mobilecolorPaletteClick">
        <i class="fa-solid fa-palette"></i>
      </div>
      <div id="mobilecolorPaletteSelection"></div>

      <section id="middle" class="mb-4">
        <div id="showcase-section" class="mx-auto custom-container mt-2">
          <div class="flex row justify-around items-end" style="min-height: 50px;">
            <div class="py-2 flex items-end justify-center sidebar-top-desktop">
              <img src="/logos/example.png" alt="clientLogo" style="max-width: 200px; margin: 0 auto;" />
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
                <div class="w-full client-logo-div sidebar-top-tab-mobile">
                  <div id="clientLogoSection" class="mb-2 mt-2 px-2 py-2 mx-auto">
                    <img src="/logos/example.png" alt="clientLogo" style="width: 150px;" />
                  </div>
                </div>
                <div class="sidebar-image-div w-full py-2">
                  <div class="sidebar-image mx-auto">
                    <span>Creative Showcase</span>
                  </div>
                </div>
                <div id="creative-list2"></div>
              </div>

              <div class="right-column">
                <div class="justify-center items-center mt-2 py-2 px-2 absolute top-0 left-0 right-0 currentTotalFeedbacks">
                  <button id="versionLeft" disabled style="margin-right:10px;">&#11207;</button>
                  <span id="versionCounter"></span>
                  <button id="versionRight" disabled style="margin-left:10px;">&#11208;</button>
                </div>

                <div id="versionArea">
                  <div id="versionCLick">
                    <i class="fa-regular fa-message" style="transform: rotate(90deg) scaleX(-1);"></i>
                  </div>

                  <div id="colorPaletteClick">
                    <i class="fa-solid fa-palette"></i>
                  </div>

                  <div id="colorPaletteSelection"></div>

                  <div id="versionDescription">
                    <div id="versionDescriptionUpperpart">
                      <div class="cursor-pointer" style="float: right;">
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

    <footer class="footer py-8">
      <div class="container mx-auto px-4 text-center text-base text-gray-600">
        &copy; All Rights Reserved.
        <a href="https://www.planetnine.com" class="underline hover:text-black" target="_blank">Planet Nine</a> - 2025
      </div>
    </footer>
  </div>
</template>

<!-- No script section on purpose: static layout only -->

<style scoped>
/* Optional: keep minimal placeholders; full styles come from preview2.css */
</style>


