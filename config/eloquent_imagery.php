<?php

return [

    /**
     * Which filesystem to store images onto?
     * If using the default public filesystem, remember to `artisan storage:link` to a public location
     */
    'filesystem' => env('IMAGERY_FILESYSTEM', 'public'),

    /**
     * The route to use to render with
     */
    'render' => [

        /**
         * enable? (true or false only)
         */
        'enable' => true,

        /**
         * with path to respond to
         */
        'path' => '/imagery',

        /**
         * Placeholder Support
         *
         * This is useful for dev purposes
         */
        'placeholder' => [

            /**
             * enable placeholder support (true or false only)
             *
             * (Very useful for development environments)
             */
            'enable' => env('IMAGERY_RENDER_PLACEHOLDER_ENABLE', false),

            /**
             * the base of the filename (without the extension part) to be
             * matched in order to specify to generate a placeholder
             */
            'filename' => '_placeholder_',

            /**
             * When a file is missing on the filesystem, should the renderer
             * fallback and utilize placeholders?
             *
             * (This is useful for dev enviroment where a copy of all the production
             *  images is not available)
             */
            'use_for_missing_files' => env('IMAGERY_RENDER_PLACEHOLDER_USE_FOR_MISSING_FILES', false)
        ],

        /**
         * Caching image (render, full response caching)
         */
        'caching' => [
            /**
             * Whether or not to use caching (true or false only)
             */
            'enable' => env('IMAGERY_RENDER_CACHING_ENABLE', true),

            /**
             * Which driver to use for caching
             */
            'driver' => env('IMAGERY_RENDER_CACHING_DRIVER', 'disk'),
            'ttl' => 60
        ],

        'browser_cache_max_age' => 31536000
    ],

    'force_unmodified_image_rendering' => env('IMAGERY_FORCE_UNMODIFIED_IMAGE_RENDERING', false),


    //
    // // 'cache_store' =>  env('IMAGERY_CACHE_STORE', 'disk'),
    // // 'enable_render_cache' => env('IMAGERY_ENABLE_CACHE', false),
    // 'render_cache_ttl' => env('IMAGERY_CACHE_TTL', 60),
    // 'browser_cache_max_age' => env('BROWSER_CACHE_MAX_AGE', 31536000),
    // 'enable_fallback_placeholders' => env('IMAGERY_ENABLE_FALLBACK_PLACEHOLDERS', false),
    // 'enable_placeholder_route' => env('IMAGERY_ENABLE_PLACEHOLDER_ROUTE', false),
    // 'enable_render_route' => env('IMAGERY_ENABLE_RENDER_ROUTE', true),
    // 'render_unmodified_images' => false,
    // 'placholder_route' => '/_p',
    // 'render_route' => '/_i',
];