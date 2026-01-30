<?php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',

    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://cybernet2.cyberline.com.pe',
        'https://*.cyberline.com.pe',

    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400, // 24 horas en segundos

    'supports_credentials' => true, 
];
