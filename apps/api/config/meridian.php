<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public website
    |--------------------------------------------------------------------------
    */
    'website_url' => rtrim((string) env('PUBLIC_SITE_URL', 'http://localhost:3000'), '/'),
    'studio_url' => rtrim((string) env('STUDIO_URL', 'http://localhost:3001'), '/'),
    'default_locale' => 'th',
    'fallback_locale' => 'en',

    'locales' => [
        'th' => ['label' => 'ไทย', 'short' => 'TH', 'html' => 'th', 'route' => 'th'],
        'en' => ['label' => 'English', 'short' => 'EN', 'html' => 'en', 'route' => 'en'],
        'zh-CN' => ['label' => '简体中文', 'short' => '中文', 'html' => 'zh-CN', 'route' => 'zh-cn'],
    ],

    'demo_mode' => (bool) env('DEMO_MODE', true),

    'pagination' => [
        'public' => 12,
        'admin' => 20,
    ],

    'search' => [
        'driver' => env('SEARCH_DRIVER', 'database'),
        'meilisearch_host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'meilisearch_key' => env('MEILISEARCH_KEY'),
    ],

    'integrations' => [
        'google_search_console' => [
            'label' => 'Google Search Console',
            'env' => ['GOOGLE_SEARCH_CONSOLE_CLIENT_ID', 'GOOGLE_SEARCH_CONSOLE_CLIENT_SECRET'],
        ],
        'google_analytics_4' => [
            'label' => 'Google Analytics 4',
            'env' => ['GA4_PROPERTY_ID', 'GOOGLE_SERVICE_ACCOUNT_JSON'],
        ],
        'google_tag_manager' => [
            'label' => 'Google Tag Manager',
            'env' => ['GTM_CONTAINER_ID'],
        ],
    ],
];
