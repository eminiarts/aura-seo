<?php

return [
    'resources' => [],

    'settings' => [
        'enabled' => false,
        'site_name' => env('APP_NAME', 'Aura'),
        'canonical_base_url' => env('APP_URL', 'http://localhost'),
        'separator' => '|',
        'title_pattern' => '[Post Title] [Separator] [Site Name]',
        'default_description' => null,
        'default_open_graph_image' => null,
        'default_twitter_image' => null,
        'locale' => 'en',
        'robots_index' => false,
        'robots_follow' => false,
        'robots_rules' => null,
        'sitemap_enabled' => true,
        'robots_route_enabled' => true,
    ],

    /* Optional callback resolving an Aura Image value to an absolute URL. */
    'image_url_resolver' => null,

    'fallbacks' => [
        'title' => env('APP_NAME', 'Aura'),
        'description' => null,
        'image' => null,
        'locale' => 'en',
        'index' => false,
        'follow' => false,
    ],

    'canonical' => [
        'allow_external' => false,
        'trailing_slash' => false,
    ],

    'routes' => [
        'sitemap' => true,
        'robots' => true,
    ],

    'cache' => [
        'store' => null,
        'ttl' => 3600,
    ],

    'sitemap' => [
        'chunk_size' => 1000,
    ],

    'permissions' => [
        'manage' => 'manage-aura-seo',
        'diagnose' => 'diagnose-aura-seo',
    ],

    'admin_middleware' => config('aura-settings.middleware.aura-admin', ['web', 'auth']),
];
