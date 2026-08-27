<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    // 'app' n'est volontairement pas declare ici : assets/app.js est compile
    // par Webpack Encore (webpack.config.js, .addEntry('app')) et servi par
    // encore_entry_script_tags(). Le declarer en entrypoint AssetMapper le
    // chargeait une seconde fois et imposait a AssetMapper de resoudre
    // 'bootstrap', qui n'existe que dans node_modules.
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
];
