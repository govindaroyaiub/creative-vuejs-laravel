<?php

/**
 * Canonical sidebar item hrefs, by section.
 *
 * Mirrors `resources/js/lib/sidebar-nav.ts`. The backend uses this
 * list to validate user-submitted sidebar preferences — only known
 * hrefs are accepted, so a tampered payload can't smuggle arbitrary
 * routes into the user's `nav_preferences` JSON.
 *
 * If you add a new entry to AppSidebar.vue / sidebar-nav.ts, add the
 * matching href here too.
 */
return [
    'main' => [
        '/dashboard',
        '/previews',
        '/color-palettes',
        '/clients',
        '/creative-sizes',
        '/bills',
        '/file-transfers',
        '/medias',
        '/play/tetris',
        '/templates',
        '/reports/checks',
    ],

    'footer' => [
        '/user-managements/designations',
        '/cache-management',
        '/documentations',
        '/pulse',
    ],
];
