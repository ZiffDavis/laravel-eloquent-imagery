<?php

return [
    'filesystem' => env('IMAGERY_FILESYSTEM', 'local'),
    'cache_store' =>  env('IMAGERY_CACHE_STORE', 'disk'),
    'enable_render_cache' => env('IMAGERY_ENABLE_CACHE', false),
    'render_cache_ttl' => env('IMAGERY_CACHE_TTL', 60),
    'browser_cache_max_age' => env('BROWSER_CACHE_MAX_AGE', 31536000),
    'enable_fallback_placeholders' => env('IMAGERY_ENABLE_FALLBACK_PLACEHOLDERS', false),
    'enable_placeholder_route' => env('IMAGERY_ENABLE_PLACEHOLDER_ROUTE', false),
    'enable_render_route' => env('IMAGERY_ENABLE_RENDER_ROUTE', true),
    'placholder_route' => '/_p',
    'render_route' => '/_i',
];