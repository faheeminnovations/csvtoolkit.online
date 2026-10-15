<?php
/**
 * Route map and application bootstrap.
 * Maps clean URL slugs to their handler files.
 */

// ── Route definitions ─────────────────────────────────────────────────────────
// Each entry: 'slug' => ['view' => path_relative_to_root, 'title' => page title]
return [
    // ── Static pages ──────────────────────────────────────────────────────────
    ''                   => ['view' => 'views/home/index.php',             'type' => 'home'],
    'about'              => ['view' => 'pages/about.php',                  'type' => 'page'],
    'contact'            => ['view' => 'pages/contact.php',                'type' => 'page'],
    'privacy-policy'     => ['view' => 'pages/privacy-policy.php',        'type' => 'page'],
    'terms'              => ['view' => 'pages/terms.php',                  'type' => 'page'],
    'blog'               => ['view' => 'pages/blog.php',                   'type' => 'page'],

    // ── Tools ─────────────────────────────────────────────────────────────────
    'csv-repair'             => ['view' => 'tools/csv-repair/index.php',           'type' => 'tool'],
    'csv-encoding-detector'  => ['view' => 'tools/csv-encoding-detector/index.php','type' => 'tool'],
    'csv-delimiter-converter'=> ['view' => 'tools/csv-delimiter-converter/index.php','type'=>'tool'],
    'csv-validator'          => ['view' => 'tools/csv-validator/index.php',        'type' => 'tool'],
    'remove-duplicates'      => ['view' => 'tools/remove-duplicates/index.php',    'type' => 'tool'],
    'csv-to-json'            => ['view' => 'tools/csv-to-json/index.php',          'type' => 'tool'],
    'csv-merger'             => ['view' => 'tools/csv-merger/index.php',           'type' => 'tool'],
    'csv-splitter'           => ['view' => 'tools/csv-splitter/index.php',         'type' => 'tool'],
    'csv-file-comparison'    => ['view' => 'tools/csv-file-comparison/index.php',  'type' => 'tool'],
    'csv-viewer'             => ['view' => 'tools/csv-viewer/index.php',           'type' => 'tool'],

    // ── Admin (handled separately — auth required) ────────────────────────────
    'admin'              => ['view' => 'admin/index.php',                  'type' => 'admin'],
];
