<?php

return [
    /*
    | Installing Aura SEO is deliberately inert. A hostname must be mapped to
    | an enabled SiteProfile and every content Resource must be registered.
    */
    'sites' => [
        // 'www.example.com' => ['profile_id' => 1, 'team_id' => 1],
    ],

    'resources' => [],

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
        'diagnostics' => true,
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
