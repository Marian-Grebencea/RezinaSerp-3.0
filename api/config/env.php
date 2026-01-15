<?php

return [
    'DB_HOST' => '127.0.0.1',
    'DB_NAME' => 'rezinaserp',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_CHARSET' => 'utf8mb4',
    'DEBUG' => false,
    'AUTH_MODE' => 'session',
    'JWT_SECRET' => 'change_me',
    'JWT_TTL' => 3600,
    'SESSION_COOKIE' => [
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
        'path' => '/RezinaSerp_3.0/',
    ],
    'CORS_ENABLED' => false,
    'CORS_ORIGINS' => [
        'http://localhost',
    ],
];
