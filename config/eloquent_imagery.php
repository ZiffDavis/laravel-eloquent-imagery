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

            /**
             * Header TTL
             */
            'ttl' => 60
        ],

        /**
         * What to set the browsers max age cache header to from the render route
         */
        'browser_cache_max_age' => 31536000
    ],

    /**
     * Force unmodified images to be rendered anyway
     *
     * By default, images that have no modifiers will be directed to the file storage's
     * url as represented by Filesystem->url() method call (this could be a storage:link
     * directory, or the direct path to an S3 object).
     *
     * When set to true, all images will be attempted to be rendered if rendering is on.
     */
    'force_unmodified_image_rendering' => env('IMAGERY_FORCE_UNMODIFIED_IMAGE_RENDERING', false)
];