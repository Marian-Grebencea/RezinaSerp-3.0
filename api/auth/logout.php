<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/cors.php';
require_once __DIR__ . '/../controllers/AuthController.php';

$config = require __DIR__ . '/../config/env.php';

handleCors($config);

$sessionCookie = $config['SESSION_COOKIE'] ?? [];
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (bool) ($sessionCookie['secure'] ?? false),
    'httponly' => (bool) ($sessionCookie['httponly'] ?? true),
    'samesite' => $sessionCookie['samesite'] ?? 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'POST') {
    errorResponse('method_not_allowed', 'Method not allowed.', 405);
}

AuthController::logout($config);
