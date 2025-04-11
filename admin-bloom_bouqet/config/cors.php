<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Izinkan API dan cookie Sanctum
    'allowed_methods' => ['*'], // Izinkan semua metode HTTP
    'allowed_origins' => ['http://localhost:3000', 'http://10.0.2.2:8000'], // Tambahkan URL Flutter
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'], // Izinkan semua header
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // Izinkan kredensial
];