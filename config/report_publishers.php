<?php

/**
 * Per-publisher rules used by /reports/checks parsers to filter
 * cross-publisher source files (GAM, Ogury, SeedTag, ShowHeroes, Teads,
 * Outbrain, Analytics) and verify per-publisher files (Adform, Adhese)
 * match the chosen publisher.
 *
 * Add a new publisher = add a new top-level key here. The validator
 * will pick it up automatically (the key is the slug used in the
 * `publisher` column of `report_checks`).
 */
return [

    'f1maximaal' => [
        'display_name'           => 'F1Maximaal',
        'adform_placement_prefix' => 'F1M_',
        'gam_root_prefix'        => 'VM_F1Maximaal',
        'ogury_asset'            => 'f1maximaal.nl',
        'seedtag_publisher'      => 'f1maximaal.nl',
        'showheroes_site'        => 'f1maximaal.nl',
        'teads_site'             => 'f1maximaal.nl',
        'adhese_site'            => 'f1maximaal.nl',
        'outbrain_publisher'     => 'f1maximaal.nl',
        'analytics_content_group' => 'F1Maximaal.nl',
    ],

    // Reserved for later — same shape, populate when we onboard topgear.
    // 'topgear' => [
    //     'display_name'            => 'topgear.nl',
    //     'adform_placement_prefix' => 'TG_',
    //     'gam_root_prefix'         => 'VDS_Topgear',
    //     'ogury_asset'             => 'topgear.nl',
    //     'seedtag_publisher'       => 'topgear.nl',
    //     'showheroes_site'         => 'topgear.nl',
    //     'teads_site'              => 'topgear.nl',
    //     'adhese_site'             => 'topgear.nl',
    //     'outbrain_publisher'      => 'topgear.nl',
    //     'analytics_content_group' => 'topgear.nl',
    // ],

];
