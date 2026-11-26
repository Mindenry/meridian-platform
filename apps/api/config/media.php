<?php

return [
    'disk' => env('MEDIA_DISK', 'public'),
    'public_url' => rtrim((string) env('MEDIA_URL', env('APP_URL', 'http://localhost:8000').'/storage'), '/'),

    'max_size' => (int) env('MEDIA_MAX_SIZE', 10 * 1024 * 1024),

    'allowed_mimes' => [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        'application/pdf',
    ],

    'image_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],

    'variants' => [
        'thumb' => ['width' => 480, 'height' => null],
        'medium' => ['width' => 960, 'height' => null],
        'large' => ['width' => 1600, 'height' => null],
    ],

    'quality' => 82,
];
