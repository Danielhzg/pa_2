<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Izinkan API dan cookie Sanctum
    'allowed_methods' => ['*'], // Izinkan semua metode HTTP
    'allowed_origins' => ['*'], // Izinkan semua origin untuk development
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'], // Izinkan semua header
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false, // Set false untuk allowed_origins => ['*']
];