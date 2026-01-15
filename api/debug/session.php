<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/cors.php';

$config = require __DIR__ . '/../config/env.php';

handleCors($config);

$sessionCookie = $config['SESSION_COOKIE'] ?? [];
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $sessionCookie['path'] ?? '/',
    'domain' => '',
    'secure' => (bool) ($sessionCookie['secure'] ?? false),
    'httponly' => (bool) ($sessionCookie['httponly'] ?? true),
    'samesite' => $sessionCookie['samesite'] ?? 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

jsonResponse([
    'success' => true,
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? null,
]);
